<?php

require_once dirname(__FILE__).'/../lib/vendor/symfony/lib/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->setPlugins(array(
      'sfDoctrinePlugin',
##PLUGINS##
    ));

    $this->dispatcher->connect('command.pre_command', array($this, 'preCommand'));
    $this->dispatcher->connect('command.post_command', array($this, 'postCommand'));
  }

  public function configureDoctrine(Doctrine_Manager $manager)
  {
    $manager->setAttribute(Doctrine::ATTR_IDXNAME_FORMAT, '%s');
    $manager->setAttribute(Doctrine::ATTR_USE_NATIVE_ENUM, true);
  }

  public function preCommand(sfEvent $event)
  {
    $task = $event->getSubject();

    if ($task instanceof sfBaseTask)
    {
      foreach (array('cache', 'log', 'upload') as $name)
      {
        $task->getFilesystem()->mkdirs(sfConfig::get('sf_'.$name.'_dir'));
      }
    }
  }

  public function postCommand(sfEvent $event)
  {
    $task = $event->getSubject();

    if ($task instanceof sfGenerateAppTask)
    {
      $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);
      $finder = sfFinder::type('dir')->relative()->maxdepth(0);
      foreach ($finder->in(sfConfig::get('sf_apps_dir')) as $app)
      {
        if (file_exists($file = sfConfig::get('sf_apps_dir').'/'.$app.'/lib/myUser.class.php'))
        {
          $task->getFilesystem()->mkdirs($directory = dirname($file).'/user');
          $task->getFilesystem()->rename($file, $file = $directory.'/'.$app.'User.class.php');
          $task->getFilesystem()->replaceTokens($file, '##', '##', array('APP_NAME' => $app));
        }

        $task->getFilesystem()->replaceTokens(sfConfig::get('sf_apps_dir').'/'.$app.'/config/view.yml', '##', '##', array(
          'PROJECT' => $properties['symfony']['name'],
        ));
      }
    }
  }
}
