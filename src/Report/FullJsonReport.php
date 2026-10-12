<?php
namespace Pingback\Report;

use Pingback\Responses;
use Pingback\VersionsFile;
use Pingback\VersionNumber;
use Symfony\Component\HttpFoundation\Request;

class FullJsonReport extends ReportBase {

  /**
   * Send a list of available versions in JSON format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string|null $jsonFile
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function generate(Request $request, ?string $jsonFile = NULL) {
    $versionInfo = VersionsFile::read($jsonFile);
    $versionInfo = self::downgradeStatuses($versionInfo);

    $requestVersion = $request->get('version', '');

    if (empty($requestVersion) || !VersionNumber::isWellFormed($requestVersion)) {
      // No valid version specified, just return all info
      return Responses::json($versionInfo);
    }
    else {
      // If a version has been specified, we only return info >= to that version
      $version = VersionNumber::getMinor($requestVersion);
      $output = array_filter(
        $versionInfo,
        fn($branchVer) => version_compare($branchVer, $version, '>='),
        ARRAY_FILTER_USE_KEY
      );

      return Responses::json($output);
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
