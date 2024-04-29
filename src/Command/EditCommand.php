<?php
namespace Pingback\Command;

use Pingback\Util\CliEditor;
use Pingback\VersionsFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EditCommand extends Command {

  /**
   * @var \Civi\Cv\Util\CliEditor
   */
  protected $editor;

  protected function configure() {
    $this
      ->setName('edit')
      ->setDescription('Edit the list of versions');
  }

  public function __construct($name = NULL) {
    parent::__construct($name);
    $this->editor = new CliEditor();
    $this->editor->setValidator(function ($file) {
      $data = json_decode(file_get_contents($file), TRUE);
      if ($data === NULL) {
        return array(
          FALSE,
          "// JSON is malformed. Please resolve syntax errors and then remove this message.\n\n",
        );
      }
      $errors = VersionsFile::validate($data);
      if (!empty($errors)) {
        return [
          FALSE,
          "// JSON is malformed. Please resolve the content errors and then remove this message.\n"
          . implode("", array_map(fn($msg) => "// - $msg\n", $errors)),
        ];
      }
      return array(TRUE, '');
    });
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $versions = file_get_contents(VersionsFile::getFileName());
    $newJson = $this->editor->editBuffer($versions, '.json', 10);
    if ($newJson === NULL) {
      fprintf(STDERR, "File edit failed. Too many failed attempts.\n");
      return 1;
    }
    else {
      file_put_contents(VersionsFile::getFileName(), $newJson);
      echo "Updated versions.json\n";
      return 0;
    }
  }

}
