<?php
namespace Pingback;

class VersionsFile {

  public static function getFileName() {
    return dirname(__DIR__) . "/versions.json";
  }

  /**
   * @param string $jsonFile
   * @return array
   * @throws \Exception
   */
  public static function read($jsonFile) {
    $versions = json_decode(file_get_contents($jsonFile), TRUE);
    if (!$versions) {
      throw new \Exception("Failed to read $jsonFile");
    }
    return $versions;
  }

  /**
   * @param string $jsonFile
   * @param string $versions
   * @throws \Exception
   */
  public static function write($jsonFile, $versions) {
    if (empty($versions)) {
      throw new \Exception("Malformed versions array");
    }

    uksort($versions, function ($a, $b) {
      return version_compare($a, $b);
    });
    foreach (array_keys($versions) as $mv) {
      usort($versions[$mv]['releases'], function ($a, $b) {
        return version_compare($a['version'], $b['version']);
      });
    }
    file_put_contents($jsonFile, json_encode($versions));
  }

}
