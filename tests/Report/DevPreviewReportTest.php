<?php

namespace Pingback\Report;

use Symfony\Component\HttpFoundation\Request;

class DevPreviewReportTest extends \PHPUnit_Framework_TestCase {

  public function getExamples() {
    $es = [];

    $es[] = ['../ex1.json', '5.0.0,5.0.beta1,4.7.31,4.7.29,4.6.36,4.6.32,4.5.10', 'DevPreview-ex1-a.html'];

    return $es;
  }

  /**
   * @param string $versionsFile
   * @param string $versionsList
   * @param string $expectHtmlFile
   * @dataProvider getExamples
   */
  public function testExampleFile($versionsFile, $versionsList, $expectHtmlFile) {
    $request = new Request([
      'format' => 'devPreview',
      'versions' => $versionsList,
    ]);
    $report = new DevPreviewReport($request, $this->createPath($versionsFile));
    $response = $report->handleRequest();
    $expectHtml = file_get_contents($this->createPath($expectHtmlFile));
    $this->assertEquals($expectHtml, $response->getContent());
  }

  protected function createPath($file) {
    return __DIR__ . DIRECTORY_SEPARATOR . $file;
  }

}
