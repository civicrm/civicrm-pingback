<?php
namespace Pingback\Util;

class Process {

  /**
   * Evaluate a string, replacing `%s` tokens with escaped strings.
   *
   * Ex: sprintf('ls -lr %s', $theDir);
   *
   * @param string $expr
   * @return mixed
   * @see escapeshellarg()
   */
  public static function sprintf($expr) {
    $args = func_get_args();
    $newArgs = array();
    $newArgs[] = array_shift($args);
    foreach ($args as $arg) {
      $newArgs[] = preg_match(';^[a-zA-Z0-9\.\/]+$;', $arg) ? $arg : escapeshellarg($arg);
    }
    return call_user_func_array('sprintf', $newArgs);
  }


  /**
   * Determine full path to an external command (by searching PATH).
   *
   * @param string $name
   * @return null|string
   */
  public static function findCommand($name) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    foreach ($paths as $path) {
      if (file_exists("$path/$name")) {
        return "$path/$name";
      }
    }
    return NULL;
  }

  /**
   * Determine if $file is a shell script.
   *
   * @param string $file
   * @return bool
   */
  public static function isShellScript($file) {
    $firstLine = file_get_contents($file, FALSE, NULL, 0, 120);
    list($firstLine) = explode("\n", $firstLine);
    return (bool) preg_match(';^#.*bin.*sh;', $firstLine);
  }

}
