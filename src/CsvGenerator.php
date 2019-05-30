<?php
namespace Pingback;

class CsvGenerator {

  /**
   * @param array $rows
   * @return string
   *   CSV content
   */
  public static function create($rows) {
    $buffer = fopen('php://temp', 'r+');

    foreach ($rows as $row) {
      fputcsv($buffer, $row);
    }

    rewind($buffer);
    $csv = stream_get_contents($buffer);
    fclose($buffer);
    return $csv;
  }

}
