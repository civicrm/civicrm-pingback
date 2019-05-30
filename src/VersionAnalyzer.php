<?php
namespace Pingback;

/**
 * Class VersionAnalyzer
 * @package Pingback
 *
 * A collection of utilities for extracting bits of information out of the
 * versions listing.
 */
class VersionAnalyzer {

  /**
   * @var array
   *   Ex: ['4.7' => ['status' => 'eol', 'releases' => [...]]]
   */
  protected $versions;

  /**
   * VersionAnalyzer constructor.
   * @param array $versions
   *   Ex: ['4.7' => ['status' => 'eol', 'releases' => [...]]]
   */
  public function __construct(array $versions) {
    $this->versions = $versions;
  }

  /**
   * Find the latest branch with a given status.
   *
   * @param array|string $statuses
   *   Ex: 'testing', 'stable', 'eol'
   * @return string|NULL
   *   Ex: '5.1'
   */
  public function findLatestBranchByStatus($statuses) {
    $statuses = (array) $statuses;
    $max = NULL;
    foreach ($this->versions as $branchVer => $branchDef) {
      if (in_array($branchDef['status'], $statuses)) {
        if ($max === NULL || version_compare($branchVer, $max, '>=')) {
          $max = $branchVer;
        }
      }
    }
    return $max;
  }

  /**
   * For a given branch, find the latest release.
   *
   * @param string $branch
   *   Ex: '5.1'.
   * @return array|NULL
   *   Ex: ['version' => '5.1.2', 'security' => FALSE, 'date' => '2018-01-01'].
   */
  public function findLatestRelease($branch) {
    if (!isset($this->versions[$branch]['releases'])) {
      return NULL;
    }
    $max = NULL;
    foreach ($this->versions[$branch]['releases'] as $release) {
      if ($max === NULL || version_compare($release['version'], $max['version'], '>=')) {
        $max = $release;
      }
    }
    return $max;
  }

  /**
   * Get a list of newer branches.
   *
   * @param string $targetVer
   *   Ex: '4.6' or '4.6.20'
   * @return array
   *   Ex: ['4.7', '5.0', '5.1']
   */
  public function findNewerBranches($targetVer) {
    $targetVer = VersionNumber::getMinor($targetVer);
    $branchVers = [];
    foreach ($this->versions as $branchVer => $branchDef) {
      if (version_compare($branchVer, $targetVer, '>')) {
        $branchVers[] = $branchVer;
      }
    }
    return $branchVers;
  }

  /**
   * Lookup the release record.
   *
   * @param string $version
   *   Ex: '5.1'.
   * @return string|NULL
   *   Ex: ['version' => '5.1.2', 'security' => FALSE, 'date' => '2018-01-01'].
   */
  public function findReleaseByVersion($version) {
    $major = VersionNumber::getMinor($version);
    if (isset($this->versions[$major]['releases'])) {
      foreach ($this->versions[$major]['releases'] as $release) {
        if ($release['version'] === $version) {
          return $release;
        }
      }
    }
    return NULL;
  }

  /**
   * Get a list of all newer patch releases (in the same branch).
   *
   * @param string $version
   *   Ex: '5.0.0'.
   * @return array
   */
  public function findPatchReleases($version) {
    $branchVer = VersionNumber::getMinor($version);
    $releases = [];
    if (isset($this->versions[$branchVer]['releases'])) {
      foreach ($this->versions[$branchVer]['releases'] as $release) {
        if (version_compare($release['version'], $version, '>')) {
          $releases[] = $release;
        }
      }
    }
    return $releases;
  }

  /**
   * Determine the status of a branch.
   *
   * @param string $branchVer
   *   Ex: '5.1' or '5.1.2'.
   * @returns string|NULL
   *   Ex: 'stable', 'lts', 'eol'.
   */
  public function findBranchStatus($branchVer) {
    $branchVer = VersionNumber::getMinor($branchVer);
    if (isset($this->versions[$branchVer]['status'])) {
      return $this->versions[$branchVer]['status'];
    }

    // $branchVer is so old that we don't have any metadata?
    $oldestBranch = min(array_keys($this->versions));
    if (version_compare($branchVer, $oldestBranch, '<')) {
      return 'eol';
    }

    // $branchVer is so new that we don't have any metadata?
    $newestBranch = max(array_keys($this->versions));
    if (version_compare($branchVer, $newestBranch, '>')) {
      return 'testing';
    }

    return NULL;
  }

  /**
   * @param string $userVer
   * @return bool
   */
  public function isCurrentInBranch($userVer) {
    $userBranch = VersionNumber::getMinor($userVer);
    $latestUserRelease = $this->findLatestRelease($userBranch);
    if ($latestUserRelease) {
      return (bool) version_compare($userVer, $latestUserRelease['version'], '>=');
    }
    else {
      return TRUE;
    }
  }

  /**
   * Find the highest patch severity among all PATCH releases for this
   * version.
   *
   * @param string $userVer
   *   Ex: '4.7.25'.
   *   The user's current version. We'll be checking for newer
   *   patches ('4.7.26', '4.7.27' etc) and gauging their severity.
   * @return string
   *   Ex: 'info', 'warning', 'notice', 'critical'.
   */
  public function findHighestPatchSeverity($userVer) {
    $weights = [
      'info' => 10,
      'notice' => 20,
      'warning' => 30,
      'critical' => 40,
    ];

    $max = NULL;
    foreach ($this->findPatchReleases($userVer) as $release) {
      $releaseSeverity = $this->findReleaseSeverity($release);
      if ($max === NULL || $max < $weights[$releaseSeverity]) {
        $max = $weights[$releaseSeverity];
      }
    }

    return array_search($max, $weights);
  }

  /**
   * Find the last date on which security updates were issued.
   *
   * @return string|NULL
   *   Ex: '2018-02-03'
   */
  public function findLatestSecurityDate() {
    $secDate = NULL;
    foreach ($this->versions as $branchVer => $branchDef) {
      if (empty($branchDef['releases'])) {
        continue;
      }
      foreach ($branchDef['releases'] as $release) {
        if (!empty($release['security'])) {
          if ($secDate === NULL || strtotime($release['date']) > strtotime($secDate)) {
            $secDate = $release['date'];
          }
        }
      }
    }
    return $secDate;
  }

  /**
   * Does $version have all available security updates from its branch?
   *
   * Note: This does NOT check whether the branch is still maintained; it
   * simply checks whether there are unapplied security updates within the
   * given branch.
   *
   * @param string $version
   * @return bool
   *   TRUE if there are any newer security releases in the given branch.
   */
  public function isSecureVersion($version) {
    $branch = VersionNumber::getMinor($version);
    foreach ($this->versions[$branch]['releases'] as $release) {
      if (
        !empty($release['security'])
        && version_compare($release['version'], $version, '>')
      ) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function findBranchMessage($version, $default = NULL) {
    $branch = VersionNumber::getMinor($version);
    return empty($this->versions[$branch]['message']) ? $default : $this->versions[$branch]['message'];
  }

  /**
   * Determine the severity of a particular release.
   *
   * @param array|string $release
   *   If a string, it's the version-number.
   *   If an array, it's the actual release record.
   * @return string
   */
  public function findReleaseSeverity($release) {
    if (is_string($release)) {
      $release = $this->findReleaseByVersion($release);
    }

    if (isset($release['severity'])) {
      $releaseSeverity = $release['severity'];
      return $releaseSeverity;
    }
    elseif (isset($release['security']) && $release['security'] === 'true') {
      $releaseSeverity = 'critical';
      return $releaseSeverity;
    }
    else {
      $releaseSeverity = 'notice';
      return $releaseSeverity;
    }
  }

}
