<?php
namespace Pingback\Report;

use Pingback\E as E;
use Pingback\VersionAnalyzer;
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
    try {
      $sr = new SummaryReport($request, \Pingback\VersionsFile::getFileName());
      return $sr->handleRequest();
    }
    catch (\Exception $e) {
      error_log(sprintf("SummaryReport failed (%s): %s\n%s", get_class($e), $e->getMessage(), $e->getTraceAsString()));
      $response = self::createJson([
        'malformed' => [
          'name' => 'malformed',
          'severity' => 'warning',
          'title' => 'Version Check Failed',
          'message' => E::ts('The server failed to report on available versions. Perhaps the request was malformed.'),
        ],
      ]);
      $response->setStatusCode(500);
      return $response;
    }
  }

  /**
   * @var \Pingback\VersionAnalyzer
   */
  protected $va;

  /**
   * @var string
   *   Ex: '4.7.29'
   */
  protected $userVer;

  public function __construct(Request $request, $fileName) {
    $this->va = new VersionAnalyzer(\Pingback\VersionsFile::read($fileName));
    $this->userVer = VersionNumber::getPatch($request->get('version', ''));
    VersionNumber::assertWellFormed($this->userVer);
  }

  public function handleRequest() {
    $msgs = [];

    if ($msg = $this->createPatchMessage()) {
      $msgs[$msg['name']] = $msg;
    }

    if ($msg = $this->createUpgradeMessage()) {
      $msgs[$msg['name']] = $msg;
    }

    return self::createJson($msgs);
  }

  /**
   * Create advice about whether to upgrade to the next X.Y.Z.
   *
   * @return array
   */
  public function createPatchMessage() {
    $va = $this->va;
    $userVer = $this->userVer;
    $tsVars = [
      '{userVer}' => htmlentities($userVer),
      '{userBranch}' => htmlentities(VersionNumber::getMinor($userVer)),
    ];

    if ($va->isCurrentInBranch($userVer)) {
      return NULL;
      //      return [
      //        'name' => 'patch',
      //        'severity' => 'info',
      //        'title' => E::ts('CiviCRM Up-to-Date'),
      //        'message' => _para(E::ts('The site is running {userVer}.', $tsVars)),
      //      ];
    }
    elseif (!$va->isSecureVersion($userVer)) {
      return [
        'name' => 'patch',
        'severity' => 'critical',
        'title' => E::ts('CiviCRM Security Patch Needed'),
        'message' => _para(E::ts('The site is running {userVer}. Additional patches are available:', $tsVars))
          . $this->createPatchList(),
      ];
    }
    else {
      return [
        'name' => 'patch',
        'severity' => $va->findHighestPatchSeverity($userVer),
        'title' => E::ts('CiviCRM Patch Available'),
        'message' => _para(E::ts('The site is running {userVer}. Additional patches are available:', $tsVars))
          . $this->createPatchList(),
      ];
    }
  }

  /**
   * @return array|NULL
   */
  public function createUpgradeMessage() {
    $va = $this->va;
    $userVer = $this->userVer;
    $userBranch = VersionNumber::getMinor($userVer);
    $latestStableBranch = $va->findLatestBranchByStatus('stable');
    $latestStableVer = $va->findLatestRelease($latestStableBranch);

    $tsVars = [
      '{userVer}' => htmlentities($userVer),
      '{userBranch}' => htmlentities($userBranch),
      '{latestStableVer}' => htmlentities($latestStableVer['version']),
      '{latestStableBranch}' => htmlentities($latestStableBranch),
    ];

    if ($userBranch === $latestStableBranch) {
      return NULL;
    }

    switch ($va->findBranchStatus($userBranch)) {
      case 'testing':
        return NULL;

      case 'eol':
        $result = [
          'name' => 'upgrade',
          'severity' => 'warning',
          'title' => E::ts('CiviCRM Version End-of-Life'),
          'message' =>
            _para(E::ts('CiviCRM {userBranch} has reached its end-of-life. Security updates are not provided anymore. Please upgrade to the latest stable release.', $tsVars))
            . _para(E::ts('<strong>Release history</strong>'))
            . $this->createBranchList(),
        ];
        break;

      case 'deprecated':
        $result = [
          'name' => 'upgrade',
          'severity' => 'warning',
          'title' => E::ts('CiviCRM Upgrade Available'),
          'message' => $this->createBranchList(),
        ];
        break;

      case 'stable':
      default:
        $result = [
          'name' => 'upgrade',
          'severity' => 'notice',
          'title' => E::ts('CiviCRM Upgrade Available'),
          'message' => $this->createBranchList(),
        ];
        break;
    }
    return $result;
  }

  /**
   * Create an HTML formatted list of patches, starting from userVer
   * [exclusive] up to the latest release in the same X.Y (inclusive).
   *
   * @return string
   *   HTML <UL> listing which identifies each of the patches
   */
  public function createPatchList() {
    $va = $this->va;
    $userVer = $this->userVer;

    $parts = [];
    foreach ($va->findPatchReleases($userVer) as $release) {
      $tsVars = [
        '{version}' => _link(
          $release['version'] . (empty($release['security']) ? '' : ' ' . E::ts('(security)')),
          'https://download.civicrm.org/about/' . $release['version']
        ),
        '{date}' => isset($release['date']) ? htmlentities($release['date']) : '',
        '{message}' => isset($release['message']) ? $release['message'] : '',
      ];

      if (empty($release['message'])) {
        $parts[] = _li(E::ts('{version} ({date})', $tsVars));
      }
      else {
        $parts[] = _li(E::ts('{version} ({date}): {message}', $tsVars));
      }
    }
    return _list($parts);
  }

  public function createBranchList() {
    $va = $this->va;
    $userVer = $this->userVer;

    $branchVers = $va->findNewerBranches($userVer);
    if (!$branchVers) {
      return '';
    }

    $branchVerSnippets = [];
    foreach ($branchVers as $branchVer) {
      $firstRelease = $va->findReleaseByVersion($branchVer . ".0");
      $latestRelease = $va->findLatestRelease($branchVer);
      $tsVars = [
        '{branch}' => htmlentities($branchVer),
        '{branchMessage}' => $va->findBranchMessage($branchVer, ''),
        '{firstVersion}' => _link($firstRelease['version'], 'https://download.civicrm.org/about/' . $firstRelease['version']), // htmlentities($firstRelease['version']),
        '{firstDate}' => htmlentities($firstRelease['date']),
        '{latestVersion}' => _link($latestRelease['version'], 'https://download.civicrm.org/about/' . $latestRelease['version']), // htmlentities($latestRelease['version']),
        '{latestDate}' => isset($latestRelease['date']) ? htmlentities($latestRelease['date']) : '',
      ];

      if ($firstRelease['version'] === $latestRelease['version']) {
        $branchVerSnippets[$branchVer] = _br(trim(E::ts('{firstVersion} was released on {firstDate}. {branchMessage}', $tsVars)));
      }
      else {
        $branchVerSnippets[$branchVer] = _br(trim(E::ts('{firstVersion} was released on {firstDate}. The latest patch revision is {latestVersion} ({latestDate}). {branchMessage}', $tsVars)));
      }
    }

    ksort($branchVerSnippets, SORT_NUMERIC);
    return implode('', $branchVerSnippets);
  }

  protected static function createJson($output) {
    return new Response(json_encode($output), 200, [
      'Content-Type' => 'application/json',
    ]);
  }

}

function _para($s) {
  if (is_array($s)) {
    $s = implode(' ', $s);
  }
  return "<p>$s</p>";
}

function _list($s) {
  if (is_array($s)) {
    $s = implode(' ', $s);
  }
  return "<ul>\n$s</ul>";
}

function _li($s) {
  if (is_array($s)) {
    $s = implode(' ', $s);
  }
  return "<li>$s</li>";
}

function _br($s) {
  if (is_array($s)) {
    $s = implode(' ', $s);
  }
  return "$s<br/>";
}

function _link($label, $url, $target = '_blank') {
  return sprintf("<a href=\"%s\" target=\"%s\">%s</a>", htmlentities($url), htmlentities($target), htmlentities($label));
}
