<?php
namespace Pingback;

/**
 * Class VersionNumber
 * @package Pingback
 *
 * Utility functions for manipulating version numbers.
 */
class VersionNumber {

  /**
   * @param string $ver
   *   Ex: '5.1.2'
   * @return string
   *   Ex: '5'
   */
  public static function getMajor($ver) {
    $parts = preg_split('/[\.\+]/', $ver);
    return $parts[0];
  }

  /**
   * @param string $ver
   *   Ex: '5.1.2'
   * @return string
   *   Ex: '5.1'
   */
  public static function getMinor($ver) {
    $parts = preg_split('/[\.\+]/', $ver);
    return $parts[0] . '.' . $parts[1];
  }

  /**
   * @param string $ver
   *   Ex: '5.1.2'
   * @return string
   *   Ex: '5.1.2'
   */
  public static function getPatch($ver) {
    $parts = preg_split('/[\.\+]/', $ver);
    return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
  }

  /**
   * Compare the first two parts of the version number (MAJOR.MINOR),
   * ignoring the patch version.
   *
   * @param string $a
   *   Ex: '5.1.2'
   * @param string $b
   *   Ex: '5.2.3'
   * @return mixed
   * @see version_compare()
   */
  public static function compareMinor($a, $b, $operator = NULL) {
    return version_compare(self::getMinor($a), self::getMinor($b), $operator);
  }

  /**
   * @param string $ver
   *   Ex: '4.7.10', '5.1.beta1', '<script>'.
   * @return bool
   *   TRUE if the string is a well-formed version number.
   */
  public static function isWellFormed($ver) {
    return (bool) preg_match(';^[0-9]+\.[0-9a-z_\-\.]+$;', $ver);
  }

  public static function assertWellFormed($ver) {
    if (!self::isWellFormed($ver)) {
      throw new \Exception('Malformed version');
    }
  }

}
