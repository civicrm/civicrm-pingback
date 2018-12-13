<?php
namespace Pingback\Report;

use Pingback\VersionsFile;
use Pingback\VersionNumber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FullJsonReport {

  /**
   * Send a list of available versions in JSON format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string|\Symfony\Component\HttpFoundation\Response
   */
  public static function generate(Request $request) {
    $versionInfo = \Pingback\VersionsFile::read(\Pingback\VersionsFile::getFileName());
    $versionInfo = self::downgradeStatuses($versionInfo);

    $requestVersion = $request->get('version', '');

    if (empty($requestVersion) || !VersionNumber::isWellFormed($requestVersion)) {
      // No valid version specified, just return all info
      return new Response(json_encode($versionInfo), 200, [
        'Content-Type' => 'application/json',
      ]);
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

      return new Response(json_encode($output), 200, [
        'Content-Type' => 'application/json',
      ]);
    }
  }

  /**
   * Filter statuses that were not supported circa 4.6/4.7.
   *
   * @return array
   */
  public static function downgradeStatuses($versions) {
    // Older clients won't recognize these statuses...
    $map = array(
      'deprecated' => 'stable',
    );

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
