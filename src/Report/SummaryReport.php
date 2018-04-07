<?php
namespace Pingback\Report;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SummaryReport {

  /**
   * Send an HTML document with the executive summary.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function generate(Request $request) {
    $version = $request->get('version', '');

    $output = array(
      'title' => 'TODO',
      'message' => sprintf("<div>TODO (%s)</div>", htmlentities($version)),
      'severity' => 'critical|info|warning|notice',
    );

    return new Response(json_encode($output), 200, [
      'Content-Type' => 'application/json',
    ]);

  }

}
