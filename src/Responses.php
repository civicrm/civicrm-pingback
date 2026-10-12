<?php

namespace Pingback;

use Symfony\Component\HttpFoundation\Response;

/**
 */
class Responses {

  public static function json(array $output, int $statusCode = 200): Response {
    $json = json_encode($output, JSON_UNESCAPED_SLASHES);
    return new Response($json, $statusCode, [
      'Content-Type' => 'application/json',
    ]);
  }

}
