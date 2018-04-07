<?php
namespace Pingback\Report;

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

    $requestVersion = $request->get('version', '');

    $output = array();

    // If a version has been specified, we only return info >= to that version
    $versionParts = explode('.', $requestVersion);

    if (empty($versionParts[0]) || empty($versionParts[1]) || !is_numeric($versionParts[0]) || !is_numeric($versionParts[1])) {
      // No valid version specified, just return all info
      return new Response(json_encode($versionInfo), 200, [
        'Content-Type' => 'application/json',
      ]);
    }
    $version = $versionParts[0] . '.' . $versionParts[1];
    foreach ($versionInfo as $majorVersion => $info) {
      if ($majorVersion >= $version) {
        $output[$majorVersion] = $info;
      }
    }

    return new Response(json_encode($output), 200, [
      'Content-Type' => 'application/json',
    ]);
  }

}
