<?php
namespace Pingback\Report;

use Symfony\Component\HttpFoundation\Response;

abstract class ReportBase {

  protected static function createJson(array $output, int $statusCode = 200): Response {
    $json = json_encode($output, JSON_UNESCAPED_SLASHES);
    return new Response($json, $statusCode, [
      'Content-Type' => 'application/json',
    ]);
  }

}
