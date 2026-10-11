<?php

namespace Pingback\Command;

class ReleaseAddCommandTest extends \PHPUnit\Framework\TestCase {

  public function testAddUpdateReleaseDoesNotLooseMatchDifferentVersions() {
    $command = new class extends ReleaseAddCommand {

      public function runAddUpdateRelease(&$versions, $newRelease) {
        return $this->addUpdateRelease($versions, $newRelease);
      }

    };

    $versions = [
      '6.1' => [
        'status' => 'stable',
        'releases' => [
          ['version' => '6.1.0', 'date' => '2025-01-01'],
        ],
      ],
      '6.10' => [
        'status' => 'stable',
        'releases' => [
          ['version' => '6.10.0', 'date' => '2026-01-01'],
        ],
      ],
    ];

    $newRelease = ['version' => '6.1.1', 'date' => '2025-02-01'];
    $result = $command->runAddUpdateRelease($versions, $newRelease);

    $this->assertEquals('added', $result);
    $this->assertCount(2, $versions['6.1']['releases']);
    $this->assertEquals('6.1.0', $versions['6.1']['releases'][0]['version']);
    $this->assertEquals('6.1.1', $versions['6.1']['releases'][1]['version']);
  }

  public function testAddUpdateReleaseUpdatesMatchingVersion() {
    $command = new class extends ReleaseAddCommand {

      public function runAddUpdateRelease(&$versions, $newRelease) {
        return $this->addUpdateRelease($versions, $newRelease);
      }

    };

    $versions = [
      '6.0' => [
        'status' => 'stable',
        'releases' => [
          ['version' => '6.0.0', 'date' => '2026-01-01', 'security' => 'false'],
        ],
      ],
    ];

    $newRelease = ['version' => '6.0.0', 'date' => '2026-01-02', 'security' => 'true'];
    $result = $command->runAddUpdateRelease($versions, $newRelease);

    $this->assertEquals('updated', $result);
    $this->assertCount(1, $versions['6.0']['releases']);
    $this->assertEquals('2026-01-02', $versions['6.0']['releases'][0]['date']);
  }

}
