<?php
namespace Pingback;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

  protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output) {
    $config = dirname(__DIR__) . '/config.php';
    if (file_exists($config)) {
      require_once $config;
    }

    return parent::doRunCommand($command, $input, $output);
  }

}
