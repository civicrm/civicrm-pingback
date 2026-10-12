<?php

namespace Pingback;

class VersionAnalyzerTest extends \PHPUnit\Framework\TestCase {

  public function testFindReleaseByVersion() {
    $release = $this->createVA()->findReleaseByVersion('4.7.8');
    $this->assertMatchesRegularExpression(';^\d+\.\d+\.\d+;', $release['version']);
    $this->assertMatchesRegularExpression(';^\d\d\d\d-\d\d-\d\d$;', $release['date']);
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
    $this->assertEquals('notice', $this->createVA()->findReleaseSeverity('4.7.25'));
  }

  public function testFindHighestPatchSeverity() {
    $this->assertEquals('notice', $this->createVA()->findHighestPatchSeverity('4.7.31'));
    $this->assertEquals('notice', $this->createVA()->findHighestPatchSeverity('4.7.30'));
    $this->assertEquals('warning', $this->createVA()->findHighestPatchSeverity('4.7.26'));
    $this->assertEquals('critical', $this->createVA()->findHighestPatchSeverity('4.7.25'));
  }

  public function testIsSecureVersion() {
    $this->assertFalse($this->createVA()->isSecureVersion('4.7.10'));
    $this->assertTrue($this->createVA()->isSecureVersion('4.7.1000'));
  }

  public function testFindBranchStatus() {
    $va = new VersionAnalyzer([
      '5.9' => ['status' => 'eol'],
      '5.10' => ['status' => 'stable'],
      '5.19' => ['status' => 'stable'],
    ]);

    // An unlisted branch older than the oldest known branch should be EOL
    $this->assertEquals('eol', $va->findBranchStatus('5.8'));

    // An unlisted branch newer than the newest known branch should be testing
    $this->assertEquals('testing', $va->findBranchStatus('5.20'));

    // An unlisted branch between known branches should not be testing
    $this->assertNull($va->findBranchStatus('5.15'));

    $vaTwo = new VersionAnalyzer([
      '3.8' => ['status' => 'eol'],
      '3.10' => ['status' => 'stable'],
    ]);
    $this->assertEquals('eol', $vaTwo->findBranchStatus('3.7'));
    $this->assertNull($vaTwo->findBranchStatus('3.9'));
    $this->assertEquals('testing', $vaTwo->findBranchStatus('3.11'));
  }

  /**
   * @return VersionAnalyzer
   */
  protected function createVA() {
    return new VersionAnalyzer(VersionsFile::read(__DIR__ . '/ex1.json'));
  }

}
