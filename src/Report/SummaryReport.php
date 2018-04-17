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
    $sr = new SummaryReport($request, \Pingback\VersionsFile::getFileName());
    return $sr->handleRequest();
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
    list ($severity, $title) = $this->createTitle();

    return self::createJson([
      [
        'name' => 'main',
        'severity' => $severity,
        'title' => $title,
        'message' => $this->createPatchMessage() . $this->createBranchMessage(),
      ],
    ]);
  }

  /**
   * Determine the overall message (i.e. severity and title).
   *
   * @return array
   *   Ex: ['critical', 'System be totally whack, yo'].
   */
  public function createTitle() {
    $va = $this->va;
    $userVer = $this->userVer;
    $userBranch = VersionNumber::getMinor($this->userVer);

    if (!$va->isCurrentInBranch($userVer)) {
      if (!$va->isSecureVersion($userVer)) {
        return ['critical', E::ts('CiviCRM Security Patch Needed')];
      }
      else {
        return ['warning', E::ts('CiviCRM Patch Available')];
      }
    }
    else {
      if ($va->findBranchStatus($userBranch) === 'eol') {
        return ['warning', E::ts('CiviCRM Patch Available')];
      }
      $latestStableBranch = $va->findLatestBranchByStatus('stable');
      if (version_compare($userBranch, $latestStableBranch, '<')) {
        return ['notice', E::ts('CiviCRM Release Available')];
      }
      else {
        return ['info', E::ts('CiviCRM Up-to-Date')];
      }
    }
  }

  /**
   * Create an HTML formatted explanation about the current patch-level release
   * (e.g. are we current within the branch or are we behind?).
   *
   * @return string
   *   Ex: '<p>Your version is OLD AND SCARY because:</p><ul><li>It jaywalks all the time.</li></ul>'
   */
  public function createPatchMessage() {
    $va = $this->va;
    $userVer = $this->userVer;
    $tsVars = [
      '{userVer}' => htmlentities($userVer),
      '{userBranch}' => htmlentities(VersionNumber::getMinor($userVer)),
    ];

    if ($va->isCurrentInBranch($userVer)) {
      return _para(E::ts('The site is running {userVer}, the latest increment of {userBranch}.', $tsVars));
    }
    else {
      return _para(E::ts('The site is running {userVer}. The following patches are available:', $tsVars))
          . $this->createPatchList();
    }
  }

  /**
   * Create an HTML formatted list of patches.
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
        '{version}' => htmlentities($release['version'])
          . (empty($release['security']) ? '' : ' ' . E::ts('(security)')),
        '{date}' => isset($release['date']) ? htmlentities($release['date']) : '',
        '{message}' => isset($release['message']) ? $release['message'] : '',
      ];

      if (empty($release['message'])) {
        $parts[] = _li(E::ts('<em>{version} released on {date}</em>', $tsVars));
      }
      else {
        $parts[] = _li(E::ts('<em>{version} released on {date}</em>: {message}', $tsVars));
      }
    }
    return _list($parts);

  }

  public function createBranchMessage() {
    $va = $this->va;
    $userVer = $this->userVer;
    $parts = [];

    $userBranch = VersionNumber::getMinor($userVer);
    $latestStableBranch = $va->findLatestBranchByStatus('stable');
    $latestTestingBranch = $va->findLatestBranchByStatus('testing');

    //    $tsVars = [
    //      '{userVer}' => htmlentities($userVer),
    //      '{userBranch}' => htmlentities($userBranch),
    //    ];
    //    if ($latestStableBranch && $userBranch === $latestStableBranch) {
    //      $parts[] = _para(E::ts('{userBranch} is the current stable release.', $tsVars));
    //    }
    //    elseif ($latestTestingBranch && $userBranch === $latestTestingBranch) {
    //      $parts[] = _para(E::ts('{userBranch} is the current testing release.', $tsVars));
    //    }

    $branchVers = $va->findNewerBranches($userVer);
    if ($branchVers) {
      $branchVerSnippets = [];
      foreach ($branchVers as $branchVer) {
        $release = $va->findLatestRelease($branchVer);
        $tsVars = [
          '{branch}' => htmlentities($branchVer),
          '{version}' => htmlentities($release['version']),
          '{date}' => isset($release['date']) ? htmlentities($release['date']) : '',
        ];

        $branchVerSnippets[] = _li(E::ts('<em>{branch}</em> (The latest version is {version} from {date}.)</em>', $tsVars));
      }

      $parts[] = _para(E::ts('Newer releases are available:'));
      $parts[] = _list($branchVerSnippets);
    }

    return implode(' ', $parts);
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
  return "<ul>$s</ul>";
}

function _li($s) {
  if (is_array($s)) {
    $s = implode(' ', $s);
  }
  return "<li>$s</li>";
}
