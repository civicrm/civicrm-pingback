<?php

namespace Pingback;

use Symfony\Component\HttpFoundation\Response;

/**
 * Collection of basic response-renderers.
 */
class Responses {

  /**
   * Generate a primitive HTML page, with negligible styling or assets.
   *
   * @param string $html
   * @param int $statusCode
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function basicHtml(string $html, int $statusCode = 200): Response {
    return new Response(sprintf("<html><body>\n%s\n</body></html>\n", $html), $statusCode);
  }

  /**
   * Generate a CSV document.
   *
   * @param array $table
   * @param int $statusCode
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function csv(array $table, int $statusCode = 200): Response {
    return new Response(\Pingback\CsvGenerator::create($table), $statusCode, ['Content-Type' => 'text/csv']);
  }

  /**
   * Generate JSON document.
   *
   * @param array $output
   * @param int $statusCode
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function json(array $output, int $statusCode = 200): Response {
    $json = json_encode($output, JSON_UNESCAPED_SLASHES);
    return new Response($json, $statusCode, [
      'Content-Type' => 'application/json',
    ]);
  }

}
