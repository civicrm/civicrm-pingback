<?php
namespace Pingback\Command;

use Pingback\VersionsFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseListCommand extends Command {

  protected function configure() {
    $this
      ->setName('release:list')
      ->setDescription('List releases');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $versions = VersionsFile::read();

    $rows = [];
    foreach ($versions as $majorVer => $majorVerRec) {
      foreach ($majorVerRec['releases'] as $release) {
        $rows[] = [$majorVer, $release['version'], $release['date'], $release['security'] ?? ''];
      }
    }

    $table = new Table($output);
    $table->setHeaders(['Major', 'Minor', 'Date', 'Security']);
    $table->setRows($rows);
    $table->render();

    return 0;
  }

}
