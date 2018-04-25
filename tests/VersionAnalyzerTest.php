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

  public function testFindReleaseSeverity() {
    $this->assertEquals('notice', $this->createVA()->findReleaseSeverity('4.7.32'));
    $this->assertEquals('warning', $this->createVA()->findReleaseSeverity('4.7.30'));
    $this->assertEquals('critical', $this->createVA()->findReleaseSeverity('4.7.26'));
    $this->assertEquals('warning', $this->createVA()->findReleaseSeverity('4.7.25'));
  }

  public function testFindHighestPatchSeverity() {
    $this->assertEquals('notice', $this->createVA()->findHighestPatchSeverity('4.7.31'));
    $this->assertEquals('warning', $this->createVA()->findHighestPatchSeverity('4.7.30'));
    $this->assertEquals('warning', $this->createVA()->findHighestPatchSeverity('4.7.26'));
    $this->assertEquals('critical', $this->createVA()->findHighestPatchSeverity('4.7.25'));
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
