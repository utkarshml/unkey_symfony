<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
class UnkeyService
{
    private HttpClientInterface $client;
    private string $baseUrl;
    private string $rootKey;
    private array $retry;
    private  ParameterBagInterface $params;
    private ?array $telemetry;

    public function __construct( ParameterBagInterface $params , HttpClientInterface $client, array $retry = [], bool $disableTelemetry = false )
    {
        $this->client = $client;
        $this->params = $params;

        $this->retry = [
            'attempts' => $retry['attempts'] ?? 5,
            'backoff' => $retry['backoff'] ?? fn ($n) => round(exp($n) * 10),
        ];
        $this->telemetry = $disableTelemetry ? null : $this->getTelemetry();
        $this->baseUrl = $this->params->get('UNKEY_API_URL'); // Get URL from the environment
        $this->rootKey = $this->params->get('UNKEY_API_KEY'); // Get API key from the environment
    }

    private function getTelemetry(): ?array
    {
        $platform = getenv('VERCEL') ? 'vercel' : (getenv('AWS_REGION') ? 'aws' : null);
        $runtime = defined('EdgeRuntime') ? 'edge-light' : 'php@' . phpversion();

        return [
            'platform' => $platform,
            'runtime' => $runtime,
        ];
    }

    private function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->rootKey,
        ];

        if ($this->telemetry) {
            if (!empty($this->telemetry['sdkVersions'])) {
                $headers['Unkey-Telemetry-SDK'] = implode(',', $this->telemetry['sdkVersions']);
            }
            if ($this->telemetry['platform']) {
                $headers['Unkey-Telemetry-Platform'] = $this->telemetry['platform'];
            }
            if ($this->telemetry['runtime']) {
                $headers['Unkey-Telemetry-Runtime'] = $this->telemetry['runtime'];
            }
        }

        return $headers;
    }

    private function fetch(array $req): array
    {
        $res = null;
        $err = null;

        for ($i = 0; $i <= $this->retry['attempts']; $i++) {
            $url = $this->baseUrl . '/' . implode('/', $req['path']);
            
            if (!empty($req['query'])) {
                $url .= '?' . http_build_query($req['query']);
            }

            try {
                $response = $this->client->request($req['method'], $url, [
                    'headers' => $this->getHeaders(),
                    'json' => $req['body'] ?? null,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode >= 200 && $statusCode < 300) {
                    return ['result' => $response->toArray()];
                }
                $err = $response->getContent();
                $backoff = call_user_func($this->retry['backoff'], $i);
                usleep($backoff * 1000); // wait before retrying
            } catch (\Throwable $e) {
                return [
                    'error' => [
                        'code' => 'FETCH_ERROR',
                        'message' => $e->getMessage() ?? 'No response',
                    ]
                    ];
            }
        }

        return [
            'error' => [
                'code' => 'FETCH_ERROR',
                'message' => $err ?? 'No response',
            ],
        ];
    }

    // Keys API
    public function createKey(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'keys.createKey'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function updateKey(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'keys.updateKey'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function verifyKey(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'keys.verifyKey'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function deleteKey(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'keys.deleteKey'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function updateRemaining(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'keys.updateRemaining'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function getKey(array $query): array
    {
        return $this->fetch([
            'path' => ['v1', 'keys.getKey'],
            'method' => 'GET',
            'query' => $query,
        ]);
    }

    public function getVerifications(array $query): array
    {
        return $this->fetch([
            'path' => ['v1', 'keys.getVerifications'],
            'method' => 'GET',
            'query' => $query,
        ]);
    }

    // APIs API
    public function createApi(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'apis.createApi'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function deleteApi(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'apis.deleteApi'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function getApi(array $query): array
    {
        return $this->fetch([
            'path' => ['v1', 'apis.getApi'],
            'method' => 'GET',
            'query' => $query,
        ]);
    }

    public function listKeys(array $query): array
    {
        return $this->fetch([
            'path' => ['v1', 'apis.listKeys'],
            'method' => 'GET',
            'query' => $query,
        ]);
    }

    // Rate limits API
    public function limitRate(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'ratelimits.limit'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    // Identities API
    public function createIdentity(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'identities.createIdentity'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function getIdentity(array $query): array
    {
        return $this->fetch([
            'path' => ['v1', 'identities.getIdentity'],
            'method' => 'GET',
            'query' => $query,
        ]);
    }

    public function listIdentities(array $query): array
    {
        return $this->fetch([
            'path' => ['v1', 'identities.listIdentities'],
            'method' => 'GET',
            'query' => $query,
        ]);
    }

    public function deleteIdentity(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'identities.deleteIdentity'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function updateIdentity(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'identities.updateIdentity'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    // Migrations API
    public function createKeysMigration(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'migrations.createKeys'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }

    public function enqueueKeysMigration(array $req): array
    {
        return $this->fetch([
            'path' => ['v1', 'migrations.enqueueKeys'],
            'method' => 'POST',
            'body' => $req,
        ]);
    }
}
