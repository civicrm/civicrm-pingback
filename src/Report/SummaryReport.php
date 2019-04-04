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
          'message' => 'The server failed to report on available versions. Perhaps the request was malformed.',
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

  /**
   * @var E
   */
  protected $e;

  public function __construct(Request $request, $fileName) {
    $this->va = new VersionAnalyzer(\Pingback\VersionsFile::read($fileName));
    $this->userVer = VersionNumber::getPatch($request->get('version', ''));
    $this->e = new E(dirname(dirname(__DIR__)) . '/messages', ['en_US']);
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
    $e = $this->e;
    $va = $this->va;
    $userVer = $this->userVer;
    $tsVars = [
      '{userVer}' => htmlentities($userVer),
      '{userBranch}' => htmlentities(VersionNumber::getMinor($userVer)),
      '{patchList}' => $this->createPatchList(),
    ];

    if ($va->isCurrentInBranch($userVer)) {
      return NULL;
      //      return [
      //        'name' => 'patch',
      //        'severity' => 'info',
      //        'title' => $this->e->ts('CiviCRM Up-to-Date'),
      //        'message' => _para($this->e->ts('The site is running {userVer}.', $tsVars)),
      //      ];
    }
    elseif (!$va->isSecureVersion($userVer)) {
      return [
        'name' => 'patch',
        'severity' => 'critical',
        'title' => $e->ts('{patch_insecure_title}'),
        'message' => $e->ts('{patch_insecure_message}', $tsVars),
      ];
    }
    else {
      return [
        'name' => 'patch',
        'severity' => $va->findHighestPatchSeverity($userVer),
        'title' => $e->ts('{patch_normal_title}'),
        'message' => $e->ts('{patch_normal_message}', $tsVars),
      ];
    }
  }

  /**
   * @return array|NULL
   */
  public function createUpgradeMessage() {
    $e = $this->e;
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
      '{branchList}' => $this->createBranchList(),
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
          'title' => $e->ts('{branch_eol_title}'),
          'message' => $e->ts('{branch_eol_message}', $tsVars),
        ];
        break;

      case 'deprecated':
        $result = [
          'name' => 'upgrade',
          'severity' => 'warning',
          'title' => $e->ts('{branch_deprecated_title}'),
          'message' => $e->ts('{branch_deprecated_message}', $tsVars),
        ];
        break;

      case 'stable':
      default:
        $result = [
          'name' => 'upgrade',
          'severity' => 'notice',
          'title' => $e->ts('{branch_stable_title}'),
          'message' => $e->ts('{branch_stable_message}', $tsVars),
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
    $e = $this->e;
    $va = $this->va;
    $userVer = $this->userVer;

    $parts = [];
    foreach ($va->findPatchReleases($userVer) as $release) {
      $tsVars = [
        '{version}' => _link(
          $release['version'] . (empty($release['security']) ? '' : ' ' . $this->e->ts('(security)')),
          'https://download.civicrm.org/about/' . $release['version']
        ),
        '{date}' => isset($release['date']) ? htmlentities($release['date']) : '',
        '{message}' => isset($release['message']) ? $release['message'] : '',
      ];

      if (empty($release['message'])) {
        $parts[] = _li($e->ts('{version} ({date})', $tsVars));
      }
      else {
        $parts[] = _li($e->ts('{version} ({date}): {message}', $tsVars));
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
        $branchVerSnippets[$branchVer] = _br(trim($this->e->ts('{branch_list_item}', $tsVars)));
      }
      else {
        $branchVerSnippets[$branchVer] = _br(trim($this->e->ts('{branch_list_item_patched}', $tsVars)));
      }
    }

    uksort($branchVerSnippets, function ($a, $b) {
      return version_compare($a, $b);
    });
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
