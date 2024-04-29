<?php

namespace Pingback;

use Symfony\Component\Console\Output\OutputInterface;

class CivicrmOrgUpdate {

  public function autoClear(OutputInterface $output): void {
    $va = new VersionAnalyzer(VersionsFile::read(VersionsFile::getFileName()));
    $releaseVer = $va->findLatestStableRelease()['version'] ?? NULL;
    if ($releaseVer === NULL) {
      throw new \RuntimeException("Failed to determine latest stable release");
    }

    $output->writeln("<info>Verifying civicrm.org lists $releaseVer as current...</info>\n");

    if ($this->isLatestPublished($releaseVer)) {
      $output->writeln("<comment>civicrm.org already lists $releaseVer as current\n");
      return;
    }

    $this->clearCache();
    if (!$this->isLatestPublished($releaseVer)) {
      throw new \RuntimeException("civicrm.org download does not mention $releaseVer!");
    }
  }

  public function clearCache(): void {
    if (empty($GLOBALS['clearCache'])) {
      throw new \RuntimeException("Missing global option: clearCache");
    }
    system($GLOBALS['clearCache'], $result);
    if ($result !== 0) {
      throw new \RuntimeException("failed to update");
    }
  }

  public function isLatestPublished(string $version): bool {
    $url = 'https://civicrm.org/download';
    $content = file_get_contents($url);
    return str_contains($content, $version);
  }

}
