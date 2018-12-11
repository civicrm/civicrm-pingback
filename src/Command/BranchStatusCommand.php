<?php
namespace Pingback\Command;

use Pingback\Date;
use Pingback\VersionsFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchStatusCommand extends Command {

  protected function configure() {
    $this
      ->setName('branch:status')
      ->setDescription('Set the status of a minor branch')
      ->addArgument('branch', InputArgument::REQUIRED, 'The branch to update (Ex: 4.7, 5.0, 5.1)')
      ->addArgument('status', InputArgument::OPTIONAL, 'The maintenance status of the branch (Ex: ' . implode(',', $this->getStatuses()) . ')')
      ->addOption('deprecated-date', 'D', InputOption::VALUE_REQUIRED, 'Automatically change to deprecated on given date (YYYY-MM-DD)')
      ->addOption('eol-date', 'E', InputOption::VALUE_REQUIRED, 'Automatically change to eol on given date (YYYY-MM-DD)');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $versions = VersionsFile::read(VersionsFile::getFileName());

    $branch = $input->getArgument('branch');

    if (!isset($versions[$branch])) {
      $versions[$branch] = [
        'status' => 'stable',
        'releases' => [],
      ];
    }

    // Apply <status> argument
    if ($status = $input->getArgument('status')) {
      if (!in_array($status, $this->getStatuses())) {
        throw new \Exception("Malformed status. Choose one of: " . implode(',', $this->getStatuses()));
      }
      $versions[$branch]['status'] = $status;
    }

    // Apply --deprecated-date=YYYY-MM-DD and --eol-date=YYYY-MM-DD
    foreach ($this->getStatuses() as $status) {
      $optName = "{$status}-date";
      if ($input->hasOption($optName) && $val = $input->getOption($optName)) {
        if (!Date::isDate($val)) {
          throw new \Exception("Malformed date: $val");
        }
        $versions[$branch]['schedule'][$status] = $input->getOption($optName);
      }
    }

    VersionsFile::write(VersionsFile::getFileName(), $versions);
  }

  protected function getStatuses() {
    return ['testing', 'stable', 'lts', 'deprecated', 'eol'];
  }

}
