<?php
namespace Pingback\Command;

use Pingback\VersionsFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseListCommand extends Command {

  protected function configure() {
    $this
      ->setName('release:list')
      ->setDescription('List releases');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $versions = VersionsFile::read(VersionsFile::getFileName());

    $rows = array();
    foreach ($versions as $majorVer => $majorVerRec) {
      foreach ($majorVerRec['releases'] as $release) {
        $rows[] = array($majorVer, $release['version'], $release['date'], isset($release['security']) ? $release['security'] : '');
      }
    }

    $table = new Table($output);
    $table->setHeaders(array('Major', 'Minor', 'Date', 'Security'));
    $table->setRows($rows);
    $table->render();
  }

}
