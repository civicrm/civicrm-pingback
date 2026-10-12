<?php

namespace Pingback\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command {

  protected function configure() {
    $this
      ->setName('install')
      ->setDescription('Install Pingback configuration and database')
      ->addOption('db', NULL, InputOption::VALUE_REQUIRED, 'Database DSN (e.g. mysql://user:pass@127.0.0.1:3307/dbname)')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Override existing config files and database tables');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $dbDsn = $input->getOption('db');
    if (empty($dbDsn)) {
      throw new \InvalidArgumentException("The --db option is required.");
    }

    $dbConfig = $this->parseDsn($dbDsn);

    $webDir = dirname(__DIR__, 2);
    $tplFile = $webDir . '/config.tpl.php';
    $configFile = $webDir . '/config.php';
    $sqlFile = $webDir . '/db_create.sql';

    if (!file_exists($sqlFile)) {
      throw new \RuntimeException("SQL file not found: $sqlFile");
    }

    $this->importDatabase($dbConfig, $sqlFile, $input->getOption('force'));
    $output->writeln("<info>Database schema initialized successfully.</info>");

    $this->createConfigFile($dbConfig, $tplFile, $configFile, $input->getOption('force'));
    $output->writeln("<info>Created $configFile successfully.</info>");

    return 0;
  }

  /**
   * Parse database DSN string into array of credentials.
   *
   * @param string $dsn
   * @return array
   */
  public function parseDsn($dsn) {
    $parsed = parse_url($dsn);
    if ($parsed === FALSE || !is_array($parsed)) {
      throw new \InvalidArgumentException("Invalid database DSN: '$dsn'");
    }

    $dbConfig = [
      'host' => $parsed['host'] ?? '127.0.0.1',
      'port' => isset($parsed['port']) ? (int) $parsed['port'] : NULL,
      'user' => isset($parsed['user']) ? urldecode($parsed['user']) : '',
      'pass' => isset($parsed['pass']) ? urldecode($parsed['pass']) : '',
      'dbname' => isset($parsed['path']) ? ltrim($parsed['path'], '/') : '',
    ];

    if (empty($dbConfig['dbname'])) {
      throw new \InvalidArgumentException("Database name missing in DSN: '$dsn'");
    }

    return $dbConfig;
  }

  /**
   * Import SQL file into database.
   *
   * @param array $dbConfig
   * @param string $sqlFile
   * @param bool $force
   */
  protected function importDatabase(array $dbConfig, $sqlFile, bool $force) {
    $mysqli = @new \mysqli(
      $dbConfig['host'],
      $dbConfig['user'],
      $dbConfig['pass'],
      '',
      $dbConfig['port'] ?: 3306
    );

    if ($mysqli->connect_error) {
      throw new \RuntimeException("Database connection failed: " . $mysqli->connect_error);
    }

    $dbname = $dbConfig['dbname'];
    $escapedDbName = '`' . str_replace('`', '``', $dbname) . '`';
    if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS $escapedDbName")) {
      throw new \RuntimeException("Failed to create database $dbname: " . $mysqli->error);
    }

    if (!$mysqli->select_db($dbname)) {
      throw new \RuntimeException("Failed to select database $dbname: " . $mysqli->error);
    }

    foreach (['stats', 'entities', 'extensions'] as $table) {
      if ($this->hasTable($mysqli, $table) && !$force) {
        throw new \RuntimeException("Database ($dbname) already has table ($table). Use --force to overwrite all pre-existing data.");
      }
    }

    $sql = file_get_contents($sqlFile);
    if (!$mysqli->multi_query($sql)) {
      throw new \RuntimeException("Failed to execute SQL in $sqlFile: " . $mysqli->error);
    }

    do {
      if ($result = $mysqli->store_result()) {
        $result->free();
      }
    } while ($mysqli->more_results() && $mysqli->next_result());

    $mysqli->close();
  }

  protected function hasTable($mysqli, string $tableName): bool {
    try {
      if ($mysqli->query("SHOW CREATE TABLE `$tableName`")) {
        return TRUE;
      }
    }
    catch (\mysqli_sql_exception $e) {
    }
    return FALSE;
  }

  /**
   * Generate config.php from config.tpl.php or template.
   *
   * @param array $dbConfig
   * @param string $tplFile
   * @param string $configFile
   * @param bool $force
   */
  public function createConfigFile(array $dbConfig, $tplFile, $configFile, bool $force) {
    if (file_exists($configFile) && !$force) {
      throw new \Exception("Config file already exists ($configFile). Use --force to overwrite all pre-existing data.");
    }

    if (file_exists($tplFile)) {
      $content = file_get_contents($tplFile);
    }
    else {
      $content = "<?php\n"
        . "\$dbhost = 'mysql_host';\n"
        . "\$dbport = 3306;\n"
        . "\$dbname = 'pingback';\n"
        . "\$user   = 'mysql_user';\n"
        . "\$pass   = 'mysql_pass';\n"
        . "\$verbose = FALSE;\n";
    }

    $hostExport = var_export($dbConfig['host'], TRUE);
    $portExport = $dbConfig['port'] !== NULL ? (int) $dbConfig['port'] : 'NULL';
    $dbnameExport = var_export($dbConfig['dbname'], TRUE);
    $userExport = var_export($dbConfig['user'], TRUE);
    $passExport = var_export($dbConfig['pass'], TRUE);

    $content = preg_replace('/^\$dbhost\s*=\s*[^;]+;/m', "\$dbhost = $hostExport;", $content);
    $content = preg_replace('/^\$dbport\s*=\s*[^;]+;/m', "\$dbport = $portExport;", $content);
    $content = preg_replace('/^\$dbname\s*=\s*[^;]+;/m', "\$dbname = $dbnameExport;", $content);
    $content = preg_replace('/^\$user\s*=\s*[^;]+;/m', "\$user   = $userExport;", $content);
    $content = preg_replace('/^\$pass\s*=\s*[^;]+;/m', "\$pass   = $passExport;", $content);

    file_put_contents($configFile, $content);
  }

}
