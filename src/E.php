<?php
namespace Pingback;

class E {

  /**
   * Translate a message.
   *
   * @code
   * E::ts('Hello {name}!', ['{name}' => 'world']);
   * @endCode
   *
   * NOTE: This uses slightly different notation from Civi's;
   * the indices of the array are not numerical; instead, they
   * are the exact substring to match.
   */
  public static function ts($message, $args = array()) {
    // TODO actually translate $message...
    return strtr($message, $args);
  }

}
