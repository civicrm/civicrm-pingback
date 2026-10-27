<?php

namespace Pingback;

class VersionsFileTest extends \PHPUnit\Framework\TestCase {

  public function tearDown(): void {
    parent::tearDown();
    Date::set(NULL);
  }

  public function getStatusExamples() {
    $es = [];

    $es[] = ['ex1.json', '2017-01-01', '5.0', 'stable'];
    $es[] = ['ex1.json', '2018-01-01', '5.0', 'stable'];
    $es[] = ['ex1.json', '2019-01-01', '5.0', 'stable'];

    $es[] = ['ex2-dates.json', '2018-11-01', '5.2', 'eol'];
    $es[] = ['ex2-dates.json', '2018-11-01', '5.3', 'stable'];
    $es[] = ['ex2-dates.json', '2018-11-01', '5.4', 'stable'];

    $es[] = ['ex2-dates.json', '2018-12-10', '5.2', 'eol'];
    $es[] = ['ex2-dates.json', '2018-12-10', '5.3', 'deprecated'];
    $es[] = ['ex2-dates.json', '2018-12-10', '5.4', 'stable'];

    $es[] = ['ex2-dates.json', '2018-12-15', '5.2', 'eol'];
    $es[] = ['ex2-dates.json', '2018-12-15', '5.3', 'deprecated'];
    $es[] = ['ex2-dates.json', '2018-12-15', '5.4', 'deprecated'];

    $es[] = ['ex2-dates.json', '2019-01-09', '5.2', 'eol'];
    $es[] = ['ex2-dates.json', '2019-01-09', '5.3', 'deprecated'];
    $es[] = ['ex2-dates.json', '2019-01-09', '5.4', 'deprecated'];

    $es[] = ['ex2-dates.json', '2019-01-10', '5.2', 'eol'];
    $es[] = ['ex2-dates.json', '2019-01-10', '5.3', 'eol'];
    $es[] = ['ex2-dates.json', '2019-01-10', '5.4', 'deprecated'];

    $es[] = ['ex2-dates.json', '2019-01-10', '5.2', 'eol'];
    $es[] = ['ex2-dates.json', '2019-01-10', '5.3', 'eol'];
    $es[] = ['ex2-dates.json', '2019-01-15', '5.4', 'eol'];

    return $es;
  }

  /**
   * @param $file
   * @param $today
   * @param $checkVer
   * @param $expectStatus
   *
   * @dataProvider getStatusExamples
   */
  public function testAutomaticStatusChanges($file, $today, $checkVer, $expectStatus) {
    Date::set($today);
    $vf = VersionsFile::read(__DIR__ . '/' . $file);
    $this->assertEquals($expectStatus, $vf[$checkVer]['status']);
  }

  public function testValidateAcceptsPublishedVersions() {
    $versions = json_decode(file_get_contents(__DIR__ . '/../versions.json'), TRUE);
    $this->assertEquals([], VersionsFile::validate($versions));
  }

  public function testValidateRejectsReleaseDatedBeforePreviousRelease() {
    $versions = [
      '6.9' => [
        'status' => 'stable',
        'releases' => [
          ['version' => '6.9.0', 'date' => '2025-12-03'],
          ['version' => '6.9.2', 'date' => '2025-01-07'],
          ['version' => '6.9.1', 'date' => '2025-12-17'],
        ],
      ],
    ];
    $this->assertEquals(
      ['Release "6.9.2" (2025-01-07) is dated before release "6.9.1" (2025-12-17)'],
      VersionsFile::validate($versions)
    );
  }

  public function testValidateAcceptsReleasesOnTheSameDay() {
    $versions = [
      '6.10' => [
        'status' => 'stable',
        'releases' => [
          ['version' => '6.10.0', 'date' => '2026-01-07'],
          ['version' => '6.10.1', 'date' => '2026-01-07'],
        ],
      ],
    ];
    $this->assertEquals([], VersionsFile::validate($versions));
  }

}
