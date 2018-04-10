<?php

namespace Pingback\Report;

use Symfony\Component\HttpFoundation\Request;

class SummaryReportTest extends \PHPUnit_Framework_TestCase {

  public function getExamples() {
    $es = [];

    // $es[] = ['mockDataFile.json', 'userVersion', 'expectedSeverity', 'expectedTitle', 'expectedMessage'];

    $es[] = ['ex1.json', '4.7.31', 'info', 'CiviCRM Up-to-Date', 'CiviCRM version 4.7.31 is up-to-date.'];
    $es[] = ['ex1.json', '4.7.29', 'notice', 'CiviCRM Update Available', 'New version 4.7.31 is available. The site is currently running 4.7.29.'];
    $es[] = ['ex1.json', '4.7.10', 'critical', 'CiviCRM Security Update Needed', 'New security release 4.7.31 is available. The site is currently running 4.7.10.'];

    $es[] = ['ex1.json', '4.6.36', 'info', 'CiviCRM Up-to-Date', 'CiviCRM version 4.6.36 is up-to-date.'];
    $es[] = ['ex1.json', '4.6.34', 'notice', 'CiviCRM Update Available', 'New version 4.6.36 is available. The site is currently running 4.6.34.'];
    $es[] = ['ex1.json', '4.6.32', 'critical', 'CiviCRM Security Update Needed', 'New security release 4.6.36 is available. The site is currently running 4.6.32.'];

    return $es;
  }

  /**
   * @param $version
   * @param $severity
   * @param $title
   * @param $message
   * @dataProvider getExamples
   */
  public function testGenerate($jsonFile, $version, $severity, $title, $message) {
    $request = new Request([
      'format' => 'summary',
      'version' => $version
    ]);
    $response = SummaryReport::_generate($request, dirname(__DIR__) . DIRECTORY_SEPARATOR . $jsonFile);
    $output = json_decode($response->getContent(), 1);

    $msg = sprintf('(fullResponse=%s)', $response->getContent());

    $this->assertEquals($severity, $output['severity'], $msg);
    $this->assertEquals($title, $output['title'], $msg);
    $this->assertEquals($message, $output['message'], $msg);
  }

}
