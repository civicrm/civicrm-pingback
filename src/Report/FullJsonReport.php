<?php
namespace Pingback\Report;

use Pingback\VersionsFile;
use Pingback\VersionNumber;
use Symfony\Component\HttpFoundation\Request;

class FullJsonReport extends ReportBase {

  /**
   * Send a list of available versions in JSON format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string|\Symfony\Component\HttpFoundation\Response
   */
  public static function generate(Request $request) {
    $versionInfo = VersionsFile::read();
    $versionInfo = self::downgradeStatuses($versionInfo);

    $requestVersion = $request->get('version', '');

    if (empty($requestVersion) || !VersionNumber::isWellFormed($requestVersion)) {
      // No valid version specified, just return all info
      return self::createJson($versionInfo);
    }
    else {
      // If a version has been specified, we only return info >= to that version
      $version = VersionNumber::getMinor($requestVersion);
      $output = array();
      foreach ($versionInfo as $branchVer => $branchDef) {
        if ($branchVer >= $version) {
          $output[$branchVer] = $branchDef;
        }
      }

      return self::createJson($output);
    }
  }

  /**
   * Filter statuses that were not supported circa 4.6/4.7.
   *
   * @return array
   */
  public static function downgradeStatuses($versions) {
    // Older clients won't recognize these statuses...
    $map = [
      'deprecated' => 'stable',
    ];

    $result = [];
    foreach ($versions as $k => $v) {
      if (isset($map[$v['status']])) {
        $v['status'] = $map[$v['status']];
      }
      $result[$k] = $v;
    }
    return $result;
  }

}
