<?php

namespace Pingback\Report;

use Symfony\Component\HttpFoundation\Request;

class FullJsonReportTest extends \PHPUnit\Framework\TestCase {

  /**
   * Test that filtering branches uses semantic version comparison,
   * avoiding PHP float comparison pitfalls (e.g. '5.8' vs '5.75', '5.9' vs '5.10').
   *
   * @param string $requestedVersion
   * @param array $expectedPresentBranches
   * @param array $expectedAbsentBranches
   * @dataProvider getVersionFilterExamples
   */
  public function testVersionFiltering(string $requestedVersion, array $expectedPresentBranches, array $expectedAbsentBranches) {
    $request = new Request(['version' => $requestedVersion]);
    $response = FullJsonReport::generate($request);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('application/json', $response->headers->get('Content-Type'));

    $data = json_decode($response->getContent(), TRUE);
    $this->assertIsArray($data);

    foreach ($expectedPresentBranches as $branch) {
      $this->assertArrayHasKey($branch, $data, "Expected branch $branch to be included for version $requestedVersion");
    }

    foreach ($expectedAbsentBranches as $branch) {
      $this->assertArrayNotHasKey($branch, $data, "Expected branch $branch to be excluded for version $requestedVersion");
    }
  }

  public function getVersionFilterExamples() {
    return [
      // 5.75: '5.8' must NOT match even though ('5.8' >= '5.75') is true numerically in PHP.
      'filter 5.75 excludes 5.8' => [
        '5.75.0',
        ['5.75', '5.76', '6.0', '6.10'],
        ['5.8', '5.9', '5.10', '5.74', '4.7'],
      ],
      // 5.10: '5.9' must NOT match even though ('5.9' >= '5.10') is true numerically in PHP.
      'filter 5.10 excludes 5.9' => [
        '5.10.0',
        ['5.10', '5.11', '5.75', '6.0'],
        ['5.8', '5.9', '4.7'],
      ],
      // 6.10: '6.9' must NOT match even though ('6.9' >= '6.10') is true numerically in PHP.
      'filter 6.10 excludes 6.9' => [
        '6.10.0',
        ['6.10', '6.11', '6.19'],
        ['6.9', '6.8', '5.80', '5.75'],
      ],
    ];
  }

  public function testEmptyOrInvalidVersionReturnsAll() {
    $allResponse = FullJsonReport::generate(new Request());
    $allData = json_decode($allResponse->getContent(), TRUE);

    $invalidResponse = FullJsonReport::generate(new Request(['version' => 'not-a-version']));
    $invalidData = json_decode($invalidResponse->getContent(), TRUE);

    $this->assertEquals(200, $allResponse->getStatusCode());
    $this->assertEquals(200, $invalidResponse->getStatusCode());
    $this->assertEquals(array_keys($allData), array_keys($invalidData));
    $this->assertArrayHasKey('5.8', $allData);
    $this->assertArrayHasKey('5.75', $allData);
  }

  public function testCustomVersionsFile() {
    $fixtureFile = dirname(__DIR__) . '/ex3.json';
    $request = new Request(['version' => '5.10.0']);
    $response = FullJsonReport::generate($request, $fixtureFile);

    $data = json_decode($response->getContent(), TRUE);
    $this->assertArrayHasKey('5.10', $data);
    $this->assertArrayNotHasKey('5.9', $data);
  }

  public function testDowngradeStatuses() {
    $request = new Request();
    $response = FullJsonReport::generate($request);
    $data = json_decode($response->getContent(), TRUE);

    foreach ($data as $branchVer => $branchDef) {
      $this->assertNotEquals('deprecated', $branchDef['status'], "Branch $branchVer status should be downgraded from deprecated to stable");
    }
  }

}
