<?php
namespace Pingback\Command;

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
      ->addArgument('status', InputArgument::REQUIRED, 'The maintenance status of the branch (Ex: ' . implode(',', $this->getStatuses()) . ')');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $versions = VersionsFile::read(VersionsFile::getFileName());

    $branch = $input->getArgument('branch');
    $status = $input->getArgument('status');
    if (!in_array($status, $this->getStatuses())) {
      throw new \Exception("Malformed status. Choose one of: " . implode(',', $this->getStatuses()));
    }

    if (!isset($versions[$branch])) {
      $versions[$branch] = [
        'status' => $status,
        'releases' => [],
      ];
    }
    else {
      $versions[$branch]['status'] = $status;
    }

    VersionsFile::write(VersionsFile::getFileName(), $versions);
  }

  protected function getStatuses() {
    return ['testing', 'stable', 'lts', 'deprecated', 'eol'];
  }

}
