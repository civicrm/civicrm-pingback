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

  public function __construct(Request $request) {
    $this->request = $request;
  }

  public function handleRequest() {
    $versions = explode(',', $this->request->get('versions', '5.0.0,5.0.beta1,4.7.32,4.7.29,4.6.36,4.6.32,4.5.10'));

    $msgs = [];
    foreach ($versions as $version) {
      VersionNumber::assertWellFormed($version);
      $fakeRequest = new Request([
        'format' => 'summary',
        'version' => $version,
      ]);

      $sr = new SummaryReport($fakeRequest, VersionsFile::getFileName($this->request->get('versionsFile', '')));
      $msgs[$version] = json_decode($sr->handleRequest()->getContent(), 1);
    }

    switch ($this->request->get('format')) {
      case 'devPreview':
      case 'devPreviewHtml':
        $buf = [];
        foreach ($msgs as $version => $verMsgs) {
          $buf[] = self::renderMessages($version, $verMsgs);
        }
        return new Response(sprintf("<html><body>\n%s\n</body></html>\n", implode("\n<br/><hr/><br/>\n", $buf)));

      case 'devPreviewJson':
        return new Response(json_encode($msgs), 200, ['Content-Type' => 'application/json']);

      case 'devPreviewCsv':
        return new Response(self::renderCsv($msgs), 200, ['Content-Type' => 'text/csv']);

      default:
        throw new \RuntimeException('Unrecognized format');
    }
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

  /**
   * @param array $msgs
   *   Array(string $version => array $msgList)
   * @return string
   */
  public static function renderCsv($msgs) {
    $rows = [];
    $rows[] = ['tgt.version', 'msg.name', 'msg.severity', 'msg.title', 'msg.message'];
    foreach ($msgs as $version => $verMsgs) {
      foreach ($verMsgs as $msg) {
        $rows[] = [$version, $msg['name'], $msg['severity'], $msg['title'], $msg['message']];
      }
    }
    return \Pingback\CsvGenerator::create($rows);
  }

}
