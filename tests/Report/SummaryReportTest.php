<?php

namespace Pingback\Report;

use Symfony\Component\HttpFoundation\Request;

class SummaryReportTest extends \PHPUnit_Framework_TestCase {

  public function getExamples() {
    $es = [];

    // $es[] = ['mockDataFile.json', 'userVersion', 'expectedSeverity', 'expectedTitle', 'expectedMessage'];

    $es[] = ['ex1.json', '5.0.0', 'info', 'CiviCRM Up-to-Date', '<p>The site is running 5.0.0, the latest increment of 5.0.</p>'];

    $es[] = ['ex1.json', '4.7.31', 'notice', 'CiviCRM Release Available', '<p>The site is running 4.7.31, the latest increment of 4.7.</p><p>Newer releases are available:</p> <ul><li><em>5.0</em> (The latest version is 5.0.0 from 2018-04-04.)</em></li></ul>'];
    $es[] = ['ex1.json', '4.7.29', 'warning', 'CiviCRM Patch Available', '<p>The site is running 4.7.29. The following patches are available:</p><ul><li><em>4.7.30 released on 2018-02-07</em></li> <li><em>4.7.31 released on 2018-03-07</em></li></ul><p>Newer releases are available:</p> <ul><li><em>5.0</em> (The latest version is 5.0.0 from 2018-04-04.)</em></li></ul>'];
    $es[] = ['ex1.json', '4.7.25', 'critical', 'CiviCRM Security Patch Needed', '<p>The site is running 4.7.25. The following patches are available:</p><ul><li><em>4.7.26 (security) released on 2017-11-01</em></li> <li><em>4.7.27 released on 2017-11-01</em></li> <li><em>4.7.28 released on 2017-12-06</em></li> <li><em>4.7.29 released on 2017-12-20</em></li> <li><em>4.7.30 released on 2018-02-07</em></li> <li><em>4.7.31 released on 2018-03-07</em></li></ul><p>Newer releases are available:</p> <ul><li><em>5.0</em> (The latest version is 5.0.0 from 2018-04-04.)</em></li></ul>'];

    $es[] = ['ex1.json', '4.6.36', 'notice', 'CiviCRM Release Available', '<p>The site is running 4.6.36, the latest increment of 4.6.</p><p>Newer releases are available:</p> <ul><li><em>4.7</em> (The latest version is 4.7.31 from 2018-03-07.)</em></li> <li><em>5.0</em> (The latest version is 5.0.0 from 2018-04-04.)</em></li></ul>'];
    $es[] = ['ex1.json', '4.6.34', 'warning', 'CiviCRM Patch Available', '<p>The site is running 4.6.34. The following patches are available:</p><ul><li><em>4.6.35 released on 2018-02-07</em></li> <li><em>4.6.36 released on 2018-03-07</em></li></ul><p>Newer releases are available:</p> <ul><li><em>4.7</em> (The latest version is 4.7.31 from 2018-03-07.)</em></li> <li><em>5.0</em> (The latest version is 5.0.0 from 2018-04-04.)</em></li></ul>'];
    $es[] = ['ex1.json', '4.6.32', 'critical', 'CiviCRM Security Patch Needed', '<p>The site is running 4.6.32. The following patches are available:</p><ul><li><em>4.6.33 (security) released on 2017-11-01</em></li> <li><em>4.6.34 released on 2017-12-20</em></li> <li><em>4.6.35 released on 2018-02-07</em></li> <li><em>4.6.36 released on 2018-03-07</em></li></ul><p>Newer releases are available:</p> <ul><li><em>4.7</em> (The latest version is 4.7.31 from 2018-03-07.)</em></li> <li><em>5.0</em> (The latest version is 5.0.0 from 2018-04-04.)</em></li></ul>'];

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
    $report = new SummaryReport($request, dirname(__DIR__) . DIRECTORY_SEPARATOR . $jsonFile);
    $response = $report->handleRequest();
    $output = json_decode($response->getContent(), 1);

    $msg = sprintf('(fullResponse=%s)', $response->getContent());

    $this->assertEquals($severity, $output[0]['severity'], $msg);
    $this->assertEquals($title, $output[0]['title'], $msg);
    $this->assertEquals($message, $output[0]['message'], $msg);
  }

}
