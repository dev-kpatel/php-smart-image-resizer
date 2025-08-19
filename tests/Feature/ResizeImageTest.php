<?php
namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestRequest;

class ResizeImageTest extends TestCase
{
    public function testResizeSampleImage()
    {
        $imagePath = 'landing-10.jpg';

        $response = TestRequest::request(
            'GET',
            '/resize/' . $imagePath . '?w=100&h=100',
            ['headers' => ['Accept' => 'image/jpeg,image/webp;q=0.8']]
        );

        // Debug: Dump body if not 200
        if ($response->getStatusCode() !== 200) {
            fwrite(STDERR, "\nResize endpoint failed: " . (string)$response->getBody() . "\n");
        }

        $this->assertEquals(200, $response->getStatusCode(), 'Resize endpoint should return 200');

        $contentType = $response->getHeaderLine('Content-Type');
        $this->assertNotEmpty($contentType, 'Response must include Content-Type header');

        $this->assertTrue(
            str_starts_with(strtolower($contentType), 'image/'),
            "Expected image response, got: {$contentType}"
        );

        $this->assertGreaterThan(0, $response->getBody()->getSize(), 'Image data should not be empty');
    }
}
