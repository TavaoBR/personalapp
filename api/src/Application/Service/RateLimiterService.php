<?php

declare(strict_types=1);

namespace App\Application\Service;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class RateLimiterService
{
    private string $storageDir;

    public function __construct(string $cacheDir)
    {
        $this->storageDir = rtrim($cacheDir, '/\\') . '/rate_limits';
    }

    /**
     * @return array{limit: int, remaining: int, reset_in_seconds: int}
     */
    public function consume(string $identifier, int $maxRequests = 60, int $windowSeconds = 60): array
    {
        $safeIdentifier = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $identifier);
        $filePath = sprintf('%s/%s.json', $this->storageDir, $safeIdentifier);

        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0775, true);
        }

        $now = time();
        $record = ['window_start' => $now, 'count' => 0];

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $parsed = $content ? json_decode($content, true) : null;
            if (is_array($parsed) && isset($parsed['window_start'], $parsed['count'])) {
                if ($now - $parsed['window_start'] < $windowSeconds) {
                    $record = $parsed;
                }
            }
        }

        $record['count']++;
        file_put_contents($filePath, json_encode($record));

        $remaining = max(0, $maxRequests - $record['count']);
        $resetInSeconds = max(1, $windowSeconds - ($now - $record['window_start']));

        if ($record['count'] > $maxRequests) {
            $exception = new TooManyRequestsHttpException(
                $resetInSeconds,
                sprintf('Limite de requisições excedido. Tente novamente em %d segundos.', $resetInSeconds)
            );
            $exception->setHeaders([
                'X-RateLimit-Limit' => (string) $maxRequests,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) ($now + $resetInSeconds),
                'Retry-After' => (string) $resetInSeconds,
            ]);
            throw $exception;
        }

        return [
            'limit' => $maxRequests,
            'remaining' => $remaining,
            'reset_in_seconds' => $resetInSeconds,
        ];
    }

    public function reset(string $identifier): void
    {
        $safeIdentifier = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $identifier);
        $filePath = sprintf('%s/%s.json', $this->storageDir, $safeIdentifier);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
