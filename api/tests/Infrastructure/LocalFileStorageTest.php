<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Infrastructure\Storage\LocalFileStorage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LocalFileStorageTest extends TestCase
{
    private string $tempDir;
    private LocalFileStorage $storage;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/personalapp_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0777, true);
        $this->storage = new LocalFileStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testStoreAndRetrieveFile(): void
    {
        $tenantId = 'tenant_123';
        $subPath = 'students/456/profile';
        $filename = 'avatar.png';
        $content = 'binary_avatar_data_example';

        $relativePath = $this->storage->store($tenantId, $subPath, $filename, $content);

        $this->assertStringContainsString('avatar.png', $relativePath);
        $this->assertTrue($this->storage->exists($tenantId, $relativePath));
        $this->assertSame($content, $this->storage->get($tenantId, $relativePath));
    }

    public function testDeleteFile(): void
    {
        $tenantId = 'tenant_abc';
        $relativePath = $this->storage->store($tenantId, 'documents', 'doc.pdf', 'document_content');

        $this->assertTrue($this->storage->exists($tenantId, $relativePath));
        $this->assertTrue($this->storage->delete($tenantId, $relativePath));
        $this->assertFalse($this->storage->exists($tenantId, $relativePath));
        $this->assertNull($this->storage->get($tenantId, $relativePath));
    }

    public function testIsolationBetweenTenants(): void
    {
        $tenantA = 'tenant_alpha';
        $tenantB = 'tenant_beta';

        $relPathA = $this->storage->store($tenantA, 'records', 'report.pdf', 'tenant_a_data');

        $this->assertTrue($this->storage->exists($tenantA, $relPathA));
        // Tenant B cannot access Tenant A file at the same relative path
        $this->assertFalse($this->storage->exists($tenantB, $relPathA));
        $this->assertNull($this->storage->get($tenantB, $relPathA));
    }

    public function testRejectsDirectoryTraversalInTenantId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->storage->store('../sneaky_tenant', 'sub', 'file.txt', 'data');
    }

    public function testRejectsDirectoryTraversalInPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->storage->store('tenant_123', '../../outside', 'file.txt', 'data');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
