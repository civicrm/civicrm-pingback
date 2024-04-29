<?php
namespace Pingback;

class Application extends \Symfony\Component\Console\Application {

  /**
   * Primary entry point for execution of the standalone command.
   */
  public static function main($binDir) {
    $application = new Application('pb', '@package_version@');
    $application->run();
  }

  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {
    parent::__construct($name, $version);
    $this->setCatchExceptions(TRUE);
    $this->addCommands($this->createCommands());
  }

  /**
   * Construct command objects
   *
   * @return array of Symfony Command objects
   */
  public function createCommands($context = 'default') {
    $commands = array();
    $commands[] = new \Pingback\Command\ReleaseAddCommand();
    $commands[] = new \Pingback\Command\ReleaseListCommand();
    $commands[] = new \Pingback\Command\BranchStatusCommand();
    $commands[] = new \Pingback\Command\BranchListCommand();
    $commands[] = new \Pingback\Command\EditCommand();
    return $commands;
  }

}
