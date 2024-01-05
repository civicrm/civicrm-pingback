<?php
namespace Pingback;

class E {

  private $messages;

  public function __construct($msgDir, $locales) {
    $this->messages = $this->buildMessages($msgDir, $locales);
  }

  /**
   * Translate a message.
   *
   * @code
   * $e->ts('Hello {name}!', ['{name}' => 'world']);
   * @endCode
   *
   * NOTE: This uses slightly different notation from Civi's;
   * the indices of the array are not numerical; instead, they
   * are the exact substring to match.
   */
  public function ts($message, $args = array()) {
    // TODO actually translate $message...
    if ($message[0] === '{' && isset($this->messages[$message])) {
      $message = $this->messages[$message];
    }
    return strtr($message, array_merge($this->messages, $args));
  }

  /**
   * @param $msgDir
   * @param $locales
   * @return array
   */
  private function buildMessages($msgDir, $locales) {
    $messages = [];
    foreach ($locales as $locale) {
      if (file_exists("$msgDir/$locale.json")) {
        $lm = json_decode(file_get_contents("$msgDir/$locale.json"), 1);
        if ($lm === NULL) {
          throw new \RuntimeException("Malformatted messages file ($locale)");
        }
        $messages = array_merge($lm, $messages);
      }
    }
    return $messages;
  }

}
