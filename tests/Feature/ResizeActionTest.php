<?php
namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestRequest;

class ResizeActionTest extends TestCase {
    public function testHelloRoute() {
        $response = TestRequest::request('GET', '/hello');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello World', (string)$response->getBody());
    }
}
