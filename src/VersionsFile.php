<?php
namespace Pingback;

class VersionsFile {

  public static function getFileName() {
    return dirname(__DIR__) . "/versions.json";
  }

  /**
   * Read a list of versions from a file.
   *
   * @param string $jsonFile
   * @return array
   * @throws \Exception
   */
  public static function read($jsonFile) {
    $versions = json_decode(file_get_contents($jsonFile), TRUE);
    if (!$versions) {
      throw new \Exception("Failed to read $jsonFile");
    }
    return self::normalize($versions);
  }

  /**
   * Write a list of versions to a file.
   *
   * @param string $jsonFile
   * @param string $versions
   * @throws \Exception
   */
  public static function write($jsonFile, $versions) {
    if (empty($versions)) {
      throw new \Exception("Malformed versions array");
    }

    $versions = self::normalize($versions);
    $jsOpt = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
    file_put_contents($jsonFile, json_encode($versions, $jsOpt));
  }

  /**
   * @param array $versions
   * @return mixed
   *   Updated list of versions, sorted in normal order.
   */
  protected static function normalize($versions) {
    uksort($versions, function ($a, $b) {
      return version_compare($a, $b);
    });
    foreach (array_keys($versions) as $mv) {
      usort($versions[$mv]['releases'], function ($a, $b) {
        return version_compare($a['version'], $b['version']);
      });
    }
    return $versions;
  }

}
