<?php

namespace Pingback\Report;

use Pingback\VersionsFile;
use Symfony\Component\HttpFoundation\Request;

class SummaryReportTest extends \PHPUnit_Framework_TestCase {

  public function getExamples() {
    $es = [];

    // $es[] = ['mockDataFile.json', 'userVersion', 'msgName', 'expectedSeverity', 'expectedTitle'];

    $es[] = ['ex1.json', '5.0.0', 'patch', NULL, NULL];
    $es[] = ['ex1.json', '5.0.alpha1', 'patch', 'warning', 'CiviCRM Patch Available'];

    $es[] = ['ex1.json', '4.7.31', 'patch', NULL, NULL];
    $es[] = ['ex1.json', '4.7.29', 'patch', 'warning', 'CiviCRM Patch Available'];
    $es[] = ['ex1.json', '4.7.25', 'patch', 'critical', 'CiviCRM Security Patch Needed'];

    $es[] = ['ex1.json', '4.6.36', 'patch', NULL, NULL];
    $es[] = ['ex1.json', '4.6.34', 'patch', 'warning', 'CiviCRM Patch Available'];
    $es[] = ['ex1.json', '4.6.32', 'patch', 'critical', 'CiviCRM Security Patch Needed'];

    $es[] = ['ex1.json', '5.0.0', 'upgrade', NULL, NULL];
    $es[] = ['ex1.json', '5.0.alpha1', 'upgrade', NULL, NULL];

    $es[] = ['ex1.json', '4.7.31', 'upgrade', 'notice', 'CiviCRM Upgrade Available'];
    $es[] = ['ex1.json', '4.7.29', 'upgrade', 'notice', 'CiviCRM Upgrade Available'];
    $es[] = ['ex1.json', '4.6.36', 'upgrade', 'notice', 'CiviCRM Upgrade Available'];
    $es[] = ['ex1.json', '4.5.10', 'upgrade', 'warning', 'CiviCRM Version End-of-Life'];

    return $es;
  }

  /**
   * @param string $jsonFile
   * @param string $version
   * @param string $msgName
   * @param string $expectSeverity
   * @param string $expectTitle
   * @dataProvider getExamples
   */
  public function testSeverityAndTitle($jsonFile, $version, $msgName, $expectSeverity, $expectTitle) {
    $request = new Request([
      'format' => 'summary',
      'version' => $version,
    ]);
    $report = new SummaryReport($request, dirname(__DIR__) . DIRECTORY_SEPARATOR . $jsonFile);
    $response = $report->handleRequest();
    $output = json_decode($response->getContent(), 1);

    $msg = sprintf('(fullResponse=%s)', $response->getContent());

    if ($expectSeverity === NULL && $expectTitle === NULL) {
      $this->assertTrue(!isset($output[$msgName]));
    }
    else {
      $this->assertEquals($msgName, $output[$msgName]['name'], $msg);
      $this->assertEquals($expectSeverity, $output[$msgName]['severity'], $msg);
      $this->assertEquals($expectTitle, $output[$msgName]['title'], $msg);
    }
  }

  /**
   * @return array
   */
  public function getVersionNumbers() {
    return [
      ['4.5.beta1'],
      ['4.5.0'],
      ['4.5.10'],
      ['4.6.alpha1'],
      ['4.6.0'],
      ['4.6.99'],
      ['4.7.alpha2'],
      ['4.7.0'],
      ['4.7.28'],
      ['5.0.beta1'],
      ['5.0.0'],
      ['5.0.1'],
      ['5.999.beta1'],
      ['5.999.0'],
    ];
  }

  /**
   * This is a superficial test to ensure that everything is well-formed
   * and not crashy (when using the default `versions.json` config).
   *
   * @param string $version
   * @dataProvider getVersionNumbers
   */
  public function testWellFormedWithDefaultData($version) {
    $validSeverities = ['info', 'notice', 'warning', 'critical'];

    $request = new Request([
      'format' => 'summary',
      'version' => $version,
    ]);
    $report = new SummaryReport($request, VersionsFile::getFileName());
    $response = $report->handleRequest();
    $this->assertTrue(!empty($response->getContent()));
    $this->assertEquals(200, $response->getStatusCode());
    $msgs = json_decode($response->getContent(), 1);
    $this->assertTrue(is_array($msgs));
    foreach ($msgs as $msgName => $msg) {
      $this->assertEquals($msgName, $msg['name']);
      $this->assertTrue(!empty($msg['title']));
      $this->assertTrue(!empty($msg['message']));
      $this->assertTrue(in_array($msg['severity'], $validSeverities));
    }
  }

}
