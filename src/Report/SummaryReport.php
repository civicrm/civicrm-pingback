<?php
namespace Pingback\Report;

use Pingback\E as E;
use Pingback\VersionAnalyzer;
use Pingback\VersionsFile;
use Pingback\VersionNumber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SummaryReport {

  /**
   * Send an HTML document with the executive summary.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function generate(Request $request) {
    return self::_generate($request, \Pingback\VersionsFile::getFileName());
  }

  public static function _generate(Request $request, $fileName) {
    $va = new VersionAnalyzer(\Pingback\VersionsFile::read($fileName));

    $userVer = VersionNumber::getPatch($request->get('version', ''));
    VersionNumber::assertWellFormed($userVer);
    $userBranch = VersionNumber::getMinor($userVer);
    $userBranchStatus = $va->findBranchStatus($userBranch);

    $latestUserRelease = $va->findLatestRelease($userBranch);

    $latestStableBranch = $va->findLatestBranchByStatus('stable');
    $latestStableRelease = $va->findLatestRelease($latestStableBranch);

    $latestTestingBranch = $va->findLatestBranchByStatus('testing');
    $latestTestingRelease = $va->findLatestRelease($latestTestingBranch);

    $hasPatch = version_compare($latestUserRelease['version'], $userVer, '>');
    $hasSecurityPatch = $hasPatch && !$va->isSecureVersion($userVer);

    //    $hasNewerStableBranch = version_compare($latestStableBranch, $userBranch, '>');
    //    $hasNewerTestingBranch = version_compare($latestTestingBranch, $userBranch, '>');

    // TODO: Make hyperlinks to release announcements.
    $vars = [
      '{userVer}' => htmlentities($userVer),
      '{userBranch}' => htmlentities($userBranch . '.x'),
      '{latestUserVer}' => htmlentities($latestUserRelease['version']),
      '{latestStableBranch}' => htmlentities($latestStableBranch . '.x'),
      '{latestStableVer}' => htmlentities($latestStableRelease['version']),
      '{latestTestingBranch}' => htmlentities($latestTestingBranch . '.x'),
      '{latestTestingVer}' => htmlentities($latestTestingRelease['version']),
    ];

    $id = ($hasPatch ? 'has_patch__' : 'no_patch__') . $userBranchStatus;
    switch ($id) {
      case 'no_patch__stable':
      case 'no_patch__lts':
      case 'no_patch__testing':
        $result = self::createJson([
          'severity' => 'info',
          'title' => E::ts('CiviCRM Up-to-Date'),
          'message' => E::ts('CiviCRM version {userVer} is up-to-date.', $vars),
        ]);
        return $result;

      case 'no_patch__deprecated':
        $result = self::createJson([
          'severity' => 'warning',
          'title' => E::ts('CiviCRM Update Available'),
          'message' => E::ts('New version {latestStableVer} is available. This site is currently running {userVer}, but {userBranch} is deprecated.', $vars),
        ]);
        return $result;

      case 'has_patch__stable':
      case 'has_patch__lts':
      case 'has_patch__testing':
        $result = self::createJson([
          'severity' => $hasSecurityPatch ? 'critical' : 'notice',
          'title' => $hasSecurityPatch ? E::ts('CiviCRM Security Update Needed') : E::ts('CiviCRM Update Available'),
          'message' => $hasSecurityPatch
            ? E::ts('New security release {latestUserVer} is available. The site is currently running {userVer}.', $vars)
            : E::ts('New version {latestUserVer} is available. The site is currently running {userVer}.', $vars),
        ]);
        return $result;

      case 'has_patch__deprecated':
        $result = self::createJson([
          'severity' => $hasSecurityPatch ? 'critical' : 'warning',
          'title' => $hasSecurityPatch ? E::ts('CiviCRM Security Update Needed') : E::ts('CiviCRM Update Available'),
          'message' => E::ts('New versions {latestUserVer} and {latestStableVer} are available. This site currently running {userVer}, but {userBranch} is deprecated.', $vars),
        ]);
        return $result;

      case 'has_patch__eol':
      case 'no_patch__eol':
        $result = self::createJson([
          'severity' => 'warning',
          'title' => E::ts('CiviCRM Update Needed'),
          'message' => E::ts("New version {latestStableVer} is available. The site is currently running {userVer}, but {userBranch} has reached its end of life.", $vars),
        ]);
        return $result;

      default:
        throw new \Exception("Unrecognized message id: $id");
    }
  }

  protected static function createJson($output) {
    return new Response(json_encode($output), 200, [
      'Content-Type' => 'application/json',
    ]);
  }

}
