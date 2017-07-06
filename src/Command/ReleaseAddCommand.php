<?php
namespace Pingback\Command;

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
    $minorVer = $input->getArgument('version');
    $majorVer = $this->parseMajorVersion($minorVer);
    $newRelease = $this->createReleaseRecord($minorVer,
      $input->getOption('date'),
      $input->getOption('security'));

    if (!isset($versions[$majorVer])) {
      throw new \Exception("versions.json does not have major version $majorVer");
    }

    $output->writeln("<info>Release</info>: " . json_encode($newRelease));
    $result = $this->addUpdateRelease($versions, $majorVer, $minorVer, $newRelease);
    $output->writeln("... $result");

    VersionsFile::write(VersionsFile::getFileName(), $versions);
  }

  /**
   * @param $minorVersion
   * @param $matches
   * @return mixed
   * @throws \Exception
   */
  protected function parseMajorVersion($minorVersion) {
    if (!preg_match('/^(\d+\.\d+)\./', $minorVersion, $matches)) {
      throw new \Exception("Malformed version");
    }
    $majorVer = $matches[1];
    return $majorVer;
  }

  /**
   * @param $minorVersion
   * @param $date
   * @param $security
   * @return array
   * @throws \Exception
   */
  protected function createReleaseRecord($minorVersion, $date, $security) {
    $release = array(
      'version' => $minorVersion,
      'date' => $date == 'now' ? date('Y-m-d') : $date,
    );

    if ($security === 'true') {
      $release['security'] = 'true';
      return $release;
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
    $majorVer,
    $minorVer,
    $newRelease
  ) {
    foreach ($versions[$majorVer]['releases'] as $k => $release) {
      if ($release['version'] == $minorVer) {
        $versions[$majorVer]['releases'][$k] = $newRelease;
        return 'updated';
      }

    }

    $versions[$majorVer]['releases'][] = $newRelease;
    return 'added';
  }

}
