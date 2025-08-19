<?php
namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestRequest;

class ClearActionTest extends TestCase {
    public function testHealthz() {
        $response = TestRequest::request('GET', '/healthz');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('ok', (string)$response->getBody());
    }
}
