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
    $versions = VersionsFile::read();
    $va = new VersionAnalyzer($versions);

    $rows = [];
    foreach ($versions as $branchVer => $branchRec) {
      $latestRelease = $va->findLatestRelease($branchVer);
      $rows[] = [
        $branchVer,
        $branchRec['status'],
        sprintf('%s (%s)', $latestRelease['date'], $latestRelease['version']),
        $branchRec['schedule']['deprecated'] ?? '',
        $branchRec['schedule']['eol'] ?? '',
      ];
    }

    $table = new Table($output);
    $table->setHeaders(['Branch', 'Current Status', 'Last Release', 'Deprecated Date', 'EOL Date']);
    $table->setRows($rows);
    $table->render();
  }

}
