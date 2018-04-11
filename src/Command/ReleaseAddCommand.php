<?php
namespace Pingback\Command;

use Pingback\VersionNumber;
use Pingback\VersionsFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseAddCommand extends Command {

  protected function configure() {
    $this
      ->setName('release:add')
      ->setDescription('Add a new release')
      ->addOption('date', NULL, InputOption::VALUE_REQUIRED, 'Release date',
        'now')
      ->addOption('security', NULL, InputOption::VALUE_REQUIRED,
        'Is this a  security release? (true/false)', 'false')
      ->addArgument('version', InputArgument::REQUIRED);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $versions = VersionsFile::read(VersionsFile::getFileName());
    $releaseVer = $input->getArgument('version');
    VersionNumber::assertWellFormed($releaseVer);
    $branchVer = VersionNumber::getMinor($releaseVer);
    $newRelease = $this->createReleaseRecord($releaseVer,
      $input->getOption('date'),
      $input->getOption('security'));

    if (!isset($versions[$branchVer])) {
      throw new \Exception("versions.json does not have branch $branchVer");
    }

    $output->writeln("<info>Release</info>: " . json_encode($newRelease));
    $result = $this->addUpdateRelease($versions, $newRelease);
    $output->writeln("... $result");

    VersionsFile::write(VersionsFile::getFileName(), $versions);
  }

  /**
   * @param string $releaseVer
   *   Ex: '5.1.2'
   * @param string $date
   *   Ex: '2018-01-02'
   * @param string $security
   *   Ex: 'true' or 'false'
   * @return array
   * @throws \Exception
   */
  protected function createReleaseRecord($releaseVer, $date, $security) {
    $release = array(
      'version' => $releaseVer,
      'date' => $date == 'now' ? date('Y-m-d') : $date,
    );

    if ($security === 'true') {
      $release['security'] = 'true';
    }
    elseif ($security === 'false') {
      // OK.
    }
    else {
      throw new \Exception("--security must be true or false");

    }

    return $release;
  }

  protected function addUpdateRelease(
    &$versions,
    $newRelease
  ) {
    $releaseVer = $newRelease['version'];
    $branchVer = VersionNumber::getMinor($releaseVer);

    foreach ($versions[$branchVer]['releases'] as $k => $release) {
      if ($release['version'] == $releaseVer) {
        $versions[$branchVer]['releases'][$k] = $newRelease;
        return 'updated';
      }

    }

    $versions[$branchVer]['releases'][] = $newRelease;
    return 'added';
  }

}
