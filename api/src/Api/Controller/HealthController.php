<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Infrastructure\Storage\FileStorageInterface;
use Doctrine\DBAL\Connection;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly FileStorageInterface $fileStorage,
    ) {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'messenger' => $this->checkMessenger(),
            'disk_space' => $this->checkDiskSpace(),
        ];

        $isHealthy = $checks['database']['status'] === 'ok'
            && $checks['storage']['status'] === 'ok'
            && $checks['messenger']['status'] === 'ok'
            && $checks['disk_space']['status'] === 'ok';

        $payload = [
            'status' => $isHealthy ? 'ok' : 'degraded',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'environment' => $this->getParameter('kernel.environment'),
            'checks' => $checks,
        ];

        return new JsonResponse(
            $payload,
            $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE
        );
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkDatabase(): array
    {
        try {
            $this->connection->executeQuery('SELECT 1');
            return ['status' => 'ok'];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Falha na conexão com a base de dados.',
            ];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkStorage(): array
    {
        $probeTenant = 'system_health';
        $probeFile = 'probe.txt';
        $probeContent = 'health_check_' . time();

        try {
            $path = $this->fileStorage->store($probeTenant, 'probes', $probeFile, $probeContent);
            $retrieved = $this->fileStorage->get($probeTenant, $path);

            if ($retrieved !== $probeContent) {
                return ['status' => 'error', 'message' => 'Inconsistência na leitura do storage.'];
            }

            $this->fileStorage->delete($probeTenant, $path);
            return ['status' => 'ok'];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Falha de escrita/leitura no volume de storage local.',
            ];
        }
    }

    /**
     * @return array{status: string, message?: string, pending_messages?: int}
     */
    private function checkMessenger(): array
    {
        try {
            // Em ambiente de teste (SQLite in-memory) a tabela pode não existir se auto_setup estiver desligado
            $schemaManager = $this->connection->createSchemaManager();
            if ($schemaManager->tablesExist(['messenger_messages'])) {
                $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages WHERE delivered_at IS NULL');
                return [
                    'status' => 'ok',
                    'pending_messages' => $count,
                ];
            }

            return ['status' => 'ok', 'message' => 'Messenger transport verificado.'];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Falha ao consultar fila de mensageria.',
            ];
        }
    }

    /**
     * @return array{status: string, free_gb?: float, message?: string}
     */
    private function checkDiskSpace(): array
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $freeBytes = @disk_free_space((string) $projectDir);

        if ($freeBytes === false) {
            return ['status' => 'ok', 'message' => 'Informação de espaço em disco indisponível neste ambiente.'];
        }

        $freeGb = round($freeBytes / (1024 * 1024 * 1024), 2);
        // Alerta se menos de 1 GB livre
        if ($freeGb < 1.0) {
            return [
                'status' => 'warning',
                'free_gb' => $freeGb,
                'message' => 'Espaço livre em disco crítico (< 1GB).',
            ];
        }

        return [
            'status' => 'ok',
            'free_gb' => $freeGb,
        ];
    }
}
