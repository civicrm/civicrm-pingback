<?php
namespace Pingback\Report;

use Pingback\E as E;
use Pingback\VersionAnalyzer;
use Pingback\VersionsFile;
use Pingback\VersionNumber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DevPreviewReport
 * @package Pingback\Report
 *
 * This report provides a developer-oriented preview of how the upgrade messages
 * are rendered across many different versions.
 */
class DevPreviewReport {

  /**
   * Send an HTML document with the executive summary.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function generate(Request $request) {
    $report = new static($request);
    return $report->handleRequest();
  }

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @param string $file
   *   Ex: '' or 'ex1.json'
   * @return string
   *   Ex: '/var/foo/bar.json'.
   * @throws \Exception
   */
  public function findVersionsFile($file) {
    switch ($file) {
      case 'ex1.json':
        return dirname(dirname(__DIR__)) . '/tests/ex1.json';

      case '':
        return VersionsFile::getFileName();

      default:
        throw new \Exception('Invalid versionsFile');
    }
  }

  public function __construct(Request $request) {
    $this->request = $request;
  }

  public function handleRequest() {
    $versions = explode(',', $this->request->get('versions', '5.0.0,5.0.beta1,4.7.32,4.7.29,4.6.36,4.6.32,4.5.10'));

    $buf = [];

    foreach ($versions as $version) {
      VersionNumber::assertWellFormed($version);
      $fakeRequest = new Request([
        'format' => 'summary',
        'version' => $version,
      ]);

      $sr = new SummaryReport($fakeRequest, $this->findVersionsFile($this->request->get('versionsFile', '')));
      $msgs = json_decode($sr->handleRequest()->getContent(), 1);
      $buf[] = self::renderMessages($version, $msgs);
    }

    return new Response(
      sprintf("<html><body>\n%s\n</body></html>\n", implode("\n<br/><hr/><br/>\n", $buf))
    );
  }

  public static function renderMessages($version, $msgs) {
    $title = "Preview perspective of $version";
    $rows = [];

    if (empty($msgs)) {
      $rows[] = strtr("<tr><td>{version}</td><td><em>(No messages found)</em></td></tr>\n", [
        '{version}' => htmlentities($version),
      ]);
    }
    foreach ($msgs as $msg) {
      $fields = [];
      foreach ($msg as $k => $v) {
        $fields[] = strtr("<tr><td valign='top'><code>{version}:{msgName}:{key}</code></td><td valign='top'>{value}</td></tr>\n", [
          '{version}' => htmlentities($version),
          '{msgName}' => htmlentities($msg['name']),
          '{key}' => htmlentities($k),
          '{value}' => $v,
        ]);
      }
      $rows[] = implode('', $fields);
    }

    return strtr("<h1>{title}</h1>\n<table><tbody>\n{fields}</tbody></table>\n", [
      '{title}' => htmlentities($title),
      '{fields}' => implode('', $rows),
    ]);
  }

}
