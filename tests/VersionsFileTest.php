<?php

namespace Pingback;

class VersionsFileTest extends \PHPUnit_Framework_TestCase {

  public function tearDown() {
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

}