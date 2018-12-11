<?php
namespace Pingback;

class Date {
  private static $date = NULL;

  public static function get() {
    return static::$date === NULL ? date('Y-m-d') : self::$date;
  }
  
  public static function set($date) {
    if ($date !== NULL && !preg_match(';^2\d\d\d-\d\d-\d\d$;', $date)) {
      throw new \Exception('Malformed date');
    }
    self::$date = $date;
  }

}