<?php
namespace Pingback;

class VersionsFile {

  /**
   * @param string $name
   *   Ex: 'versions.json' or 'ex1.json'
   * @return string
   *   Ex: '/var/foo/bar.json'.
   * @throws \Exception
   */
  public static function getFileName($name = 'versions.json') {
    switch ($name) {
      case 'ex1.json':
        return dirname(__DIR__) . '/tests/ex1.json';

      case 'ex2.json':
      case 'ex2-dates.json':
        return dirname(__DIR__) . '/tests/ex2-dates.json';

      case 'staging.json':
        return dirname(__DIR__) . '/versions.staging.json';

      case '':
      case 'versions.json':
        return dirname(__DIR__) . "/versions.json";

      default:
        throw new \Exception('Invalid versionsFile');
    }
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
    // Sort branches
    uksort($versions, function ($a, $b) {
      return version_compare($a, $b);
    });

    // Sort each release for each branch
    foreach (array_keys($versions) as $mv) {
      usort($versions[$mv]['releases'], function ($a, $b) {
        return version_compare($a['version'], $b['version']);
      });
    }

    // Evaluate 'schedule' and update 'status' of each branch.
    foreach (array_keys($versions) as $mv) {
      if (!empty($versions[$mv]['schedule'])) {
        $versions[$mv]['status'] = self::pickStatus($versions[$mv]);
      }
    }

    return $versions;
  }

  /**
   * @param array $verRec
   *   Ex: [
   *     'status' => 'stable',
   *     'schedule' => ['eol' => '2019-01-01'],
   * @return string
   *   Ex: 'stable' (if before eol date) or 'eol' (if after date)
   */
  protected static function pickStatus($verRec) {
    $actualStatus = $verRec['status'];
    foreach ($verRec['schedule'] as $tgtStatus => $tgtDate) {
      if (
        self::cmpStatus($tgtStatus, $actualStatus) > 0
        && strtotime($tgtDate) <= strtotime(Date::get())
      ) {
        $actualStatus = $tgtStatus;
      }
    }
    return $actualStatus;
  }

  protected function cmpStatus($a, $b) {
    $val = [
      'stable' => 10,
      'lts' => 20,
      'deprecated' => 30,
      'eol' => 40,
    ];

    if (!isset($val[$a])) {
      throw new \RuntimeException("Unrecognized status: $a");
    }
    if (!isset($val[$b])) {
      throw new \RuntimeException("Unrecognized status: $b");
    }

    return $val[$a] - $val[$b];
  }

}
