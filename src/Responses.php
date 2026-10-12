<?php

namespace Pingback;

use Symfony\Component\HttpFoundation\Response;

/**
 */
class Responses {

  public static function basicHtml(string $buf, int $statusCode = 200): Response {
    return new Response(sprintf("<html><body>\n%s\n</body></html>\n", $buf), $statusCode);
  }

  public static function csv(array $table, int $statusCode = 200): Response {
    return new Response(\Pingback\CsvGenerator::create($table), $statusCode, ['Content-Type' => 'text/csv']);
  }

  public static function json(array $output, int $statusCode = 200): Response {
    $json = json_encode($output, JSON_UNESCAPED_SLASHES);
    return new Response($json, $statusCode, [
      'Content-Type' => 'application/json',
    ]);
  }

}
