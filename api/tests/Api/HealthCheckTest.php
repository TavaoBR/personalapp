<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthCheckTest extends WebTestCase
{
    public function testHealthEndpointReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertJson((string) $response->getContent());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('database', $data['checks']);
        $this->assertArrayHasKey('storage', $data['checks']);
        $this->assertSame('ok', $data['status']);
        $this->assertSame('ok', $data['checks']['database']['status']);
        $this->assertSame('ok', $data['checks']['storage']['status']);
    }
}
