<?php
namespace Pingback\Command;

use Pingback\VersionAnalyzer;
use Pingback\VersionsFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchListCommand extends Command {

  protected function configure() {
    $this
      ->setName('branch:list')
      ->setDescription('Branch release summary');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $versions = VersionsFile::read(VersionsFile::getFileName());
    $va = new VersionAnalyzer($versions);

    $rows = array();
    foreach ($versions as $branchVer => $branchRec) {
      $latestRelease = $va->findLatestRelease($branchVer);
      $rows[] = [
        $branchVer,
        $branchRec['status'],
        sprintf('%s (%s)', $latestRelease['date'], $latestRelease['version']),
      ];
    }

    $table = new Table($output);
    $table->setHeaders(array('Branch', 'Status', 'Last Release'));
    $table->setRows($rows);
    $table->render();
  }

}
