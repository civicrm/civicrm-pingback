<?php

namespace Pingback;

class VersionAnalyzerTest extends \PHPUnit_Framework_TestCase {

  public function testFindReleaseByVersion() {
    $release = $this->createVA()->findReleaseByVersion('4.7.8');
    $this->assertRegexp(';^\d+\.\d+\.\d+;', $release['version']);
    $this->assertRegexp(';^\d\d\d\d-\d\d-\d\d$;', $release['date']);
  }

  public function testFindLatestRelease() {
    $latestPointRelease = $this->createVA()->findLatestRelease('4.7');
    $this->assertTrue(version_compare($latestPointRelease['version'], '4.7.0', '>='));
    $this->assertTrue(version_compare($latestPointRelease['version'], '4.8.0', '<'));
  }

  public function testIsSecureVersion() {
    $this->assertFalse($this->createVA()->isSecureVersion('4.7.10'));
    $this->assertTrue($this->createVA()->isSecureVersion('4.7.1000'));
  }

  /**
   * @return VersionAnalyzer
   */
  protected function createVA() {
    return new VersionAnalyzer(VersionsFile::read(__DIR__ . '/ex1.json'));
  }

}
