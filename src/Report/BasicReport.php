<?php
namespace Pingback\Report;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicReport {

  /**
   * Legacy support: CiviCRM < 4.6 expect latest release number in plain text
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function generate(Request $request) {
    $versionInfo = \Pingback\VersionsFile::read(\Pingback\VersionsFile::getFileName());
    $majors = array_keys($versionInfo);
    usort($majors, function($a, $b){
      return -1 * version_compare($a, $b);
    });
    foreach ($majors as $majorVersion) {
      $info = $versionInfo[$majorVersion];
      if ($info['status'] == 'stable') {
        $latest = end($info['releases']);
        return new Response($latest['version']);
      }
    }
  }

}
