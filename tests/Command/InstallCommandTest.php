<?php

namespace Pingback\Command;

use PHPUnit\Framework\TestCase;

class InstallCommandTest extends TestCase {

  public function testParseDsnValid() {
    $command = new InstallCommand();

    $parsed = $command->parseDsn('mysql://user:pass@127.0.0.1:3307/mydb');
    $this->assertEquals('127.0.0.1', $parsed['host']);
    $this->assertEquals(3307, $parsed['port']);
    $this->assertEquals('user', $parsed['user']);
    $this->assertEquals('pass', $parsed['pass']);
    $this->assertEquals('mydb', $parsed['dbname']);

    $parsedDefaultPort = $command->parseDsn('mysql://myuser:mypass@localhost/testdb');
    $this->assertEquals('localhost', $parsedDefaultPort['host']);
    $this->assertNull($parsedDefaultPort['port']);
    $this->assertEquals('myuser', $parsedDefaultPort['user']);
    $this->assertEquals('mypass', $parsedDefaultPort['pass']);
    $this->assertEquals('testdb', $parsedDefaultPort['dbname']);
  }

  public function testParseDsnMissingDbName() {
    $this->expectException(\InvalidArgumentException::class);
    $command = new InstallCommand();
    $command->parseDsn('mysql://user:pass@127.0.0.1:3307/');
  }

  public function testCreateConfigFile() {
    $command = new InstallCommand();

    $tplFile = tempnam(sys_get_temp_dir(), 'tpl');
    $configFile = tempnam(sys_get_temp_dir(), 'cfg');

    file_put_contents($tplFile, "<?php\n\$dbhost = 'mysql_host';\n\$dbport = 3306;\n\$dbname = 'db_template';\n\$user = 'mysql_user';\n\$pass = 'mysql_pass';\n");

    $dbConfig = [
      'host' => 'localhost',
      'port' => 3308,
      'dbname' => 'new_db',
      'user' => 'test_user',
      'pass' => 'secret_pass',
    ];

    $command->createConfigFile($dbConfig, $tplFile, $configFile, TRUE);

    $content = file_get_contents($configFile);
    $this->assertStringContainsString("\$dbhost = 'localhost';", $content);
    $this->assertStringContainsString("\$dbport = 3308;", $content);
    $this->assertStringContainsString("\$dbname = 'new_db';", $content);
    $this->assertStringContainsString("\$user   = 'test_user';", $content);
    $this->assertStringContainsString("\$pass   = 'secret_pass';", $content);

    unlink($tplFile);
    unlink($configFile);
  }

}
