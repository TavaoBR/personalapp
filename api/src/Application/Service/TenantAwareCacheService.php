<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Tenant;
use InvalidArgumentException;

final class TenantAwareCacheService
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/\\') . '/tenant_cache';
    }

    /**
     * @template T
     * @param callable(): T $fallback
     * @return T
     */
    public function remember(Tenant|string $tenant, string $key, int $ttlSeconds, callable $fallback): mixed
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getId() : $tenant;
        $this->validateKey($key);

        $cached = $this->get($tenantId, $key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $fallback();
        $this->set($tenantId, $key, $value, $ttlSeconds);

        return $value;
    }

    public function get(string $tenantId, string $key): mixed
    {
        $filePath = $this->getFilePath($tenantId, $key);
        if (!file_exists($filePath)) {
            return null;
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            return null;
        }

        $data = @unserialize($raw);
        if (!is_array($data) || !isset($data['expires_at'], $data['value'])) {
            @unlink($filePath);
            return null;
        }

        if (time() > $data['expires_at']) {
            @unlink($filePath);
            return null;
        }

        return $data['value'];
    }

    public function set(string $tenantId, string $key, mixed $value, int $ttlSeconds): void
    {
        $filePath = $this->getFilePath($tenantId, $key);
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $payload = [
            'expires_at' => time() + max(1, $ttlSeconds),
            'value' => $value,
        ];

        file_put_contents($filePath, serialize($payload));
    }

    public function invalidate(Tenant|string $tenant, ?string $key = null): void
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getId() : $tenant;
        $tenantDir = sprintf('%s/%s', $this->cacheDir, $tenantId);

        if ($key !== null) {
            $filePath = $this->getFilePath($tenantId, $key);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            return;
        }

        if (is_dir($tenantDir)) {
            $files = glob($tenantDir . '/*') ?: [];
            foreach ($files as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
    }

    private function getFilePath(string $tenantId, string $key): string
    {
        $safeTenant = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $tenantId);
        $hashKey = sha1($key);

        return sprintf('%s/%s/%s.cache', $this->cacheDir, $safeTenant, $hashKey);
    }

    private function validateKey(string $key): void
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Chave de cache não pode ser vazia.');
        }
    }
}
