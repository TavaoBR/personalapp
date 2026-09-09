<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Application\Service\RateLimiterService;
use App\Application\Service\TenantAwareCacheService;
use App\Infrastructure\Storage\FileStorageInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ProductionReadinessTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
    }

    public function testStorageHardeningRejectsDangerousExtensions(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var FileStorageInterface $storage */
        $storage = $container->get(FileStorageInterface::class);

        $dangerousFiles = [
            'malicious.php',
            'exploit.phtml',
            'script.sh',
            'payload.exe',
            'virus.bat',
            'archive.phar',
        ];

        foreach ($dangerousFiles as $filename) {
            try {
                $storage->store('tenant_security_test', 'uploads', $filename, '<?php phpinfo(); ?>');
                $this->fail(sprintf('O arquivo "%s" deveria ter sido rejeitado pela política de segurança de storage.', $filename));
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('não é permitida por políticas de segurança', $e->getMessage());
            }
        }
    }

    public function testStoragePreventsDirectoryTraversal(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var FileStorageInterface $storage */
        $storage = $container->get(FileStorageInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $storage->store('tenant_security_test', '../../etc', 'passwd.txt', 'malicious');
    }

    public function testTenantAwareCacheIsolation(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var TenantAwareCacheService $cache */
        $cache = $container->get(TenantAwareCacheService::class);

        $tenantA = 'tenant_alpha_123';
        $tenantB = 'tenant_beta_456';
        $cacheKey = 'dashboard_summary';

        $cache->set($tenantA, $cacheKey, ['revenue' => 5000, 'students' => 20], 3600);
        $cache->set($tenantB, $cacheKey, ['revenue' => 12000, 'students' => 60], 3600);

        $valA = $cache->get($tenantA, $cacheKey);
        $valB = $cache->get($tenantB, $cacheKey);

        $this->assertNotNull($valA);
        $this->assertNotNull($valB);
        $this->assertSame(5000, $valA['revenue']);
        $this->assertSame(12000, $valB['revenue']);
        $this->assertSame(20, $valA['students']);
        $this->assertSame(60, $valB['students']);

        // Invalidação isolada para o tenant A não afeta o tenant B
        $cache->invalidate($tenantA, $cacheKey);
        $this->assertNull($cache->get($tenantA, $cacheKey));
        $this->assertNotNull($cache->get($tenantB, $cacheKey));
        $this->assertSame(12000, $cache->get($tenantB, $cacheKey)['revenue']);
    }

    public function testRateLimiterLimitsRequestsAndThrows429(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var RateLimiterService $limiter */
        $limiter = $container->get(RateLimiterService::class);

        $identifier = 'test_client_ip_' . bin2hex(random_bytes(4));
        $limiter->reset($identifier);

        // Limite de 3 requisições por janela
        $r1 = $limiter->consume($identifier, 3, 60);
        $this->assertSame(2, $r1['remaining']);

        $r2 = $limiter->consume($identifier, 3, 60);
        $this->assertSame(1, $r2['remaining']);

        $r3 = $limiter->consume($identifier, 3, 60);
        $this->assertSame(0, $r3['remaining']);

        // 4ª requisição deve estourar o limite
        try {
            $limiter->consume($identifier, 3, 60);
            $this->fail('A 4ª requisição deveria ter lançado TooManyRequestsHttpException.');
        } catch (TooManyRequestsHttpException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertArrayHasKey('Retry-After', $e->getHeaders());
            $this->assertSame('0', $e->getHeaders()['X-RateLimit-Remaining']);
        }
    }

    public function testHealthCheckEndpointReturnsFullProductionStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertSame('ok', $res['status']);
        $this->assertArrayHasKey('timestamp', $res);
        $this->assertArrayHasKey('environment', $res);
        $this->assertArrayHasKey('checks', $res);

        // Verifica todos os 4 subsistemas monitorados
        $this->assertArrayHasKey('database', $res['checks']);
        $this->assertSame('ok', $res['checks']['database']['status']);

        $this->assertArrayHasKey('storage', $res['checks']);
        $this->assertSame('ok', $res['checks']['storage']['status']);

        $this->assertArrayHasKey('messenger', $res['checks']);
        $this->assertSame('ok', $res['checks']['messenger']['status']);

        $this->assertArrayHasKey('disk_space', $res['checks']);
        $this->assertSame('ok', $res['checks']['disk_space']['status']);
    }
}
