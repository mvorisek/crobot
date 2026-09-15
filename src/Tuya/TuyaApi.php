<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tuya;

use Mvorisek\Crobot\HttpUtil;

class TuyaApi
{
    protected HttpUtil $httpUtil;

    /** @var array{domain: string, accessKey: string, secretKey: string} */
    private ?array $credentials = null;

    private ?string $accessToken = null;
    private ?\DateTime $accessTokenExpireDt = null;

    public function __construct()
    {
        $this->httpUtil = new HttpUtil();
    }

    public function logLine(string $v): void
    {
        echo $v . "\n";
    }

    /**
     * @return array{domain: string, accessKey: string, secretKey: string}
     */
    private function getCedentials(): array
    {
        if ($this->credentials === null) {
            $this->credentials = require __DIR__ . '/../../tuya-credentials.php.local'; // @phpstan-ignore require.fileNotFound
        }

        return $this->credentials; // @phpstan-ignore return.type
    }

    /**
     * @param 'get'|'post' $method
     */
    protected function signRequest(string $method, string $path, int $timestampMs, string $body): string
    {
        $credentials = $this->getCedentials();

        $pathParts = explode('?', $path, 2);
        if (count($pathParts) > 1) {
            $queryParts = explode('&', $pathParts[1]);
            sort($queryParts);
            $path = $pathParts[0] . '?' . implode('&', $queryParts);
        }

        $signString = $credentials['accessKey'] . $this->accessToken . $timestampMs . strtoupper($method) . "\n"
            . hash('sha256', $body) . "\n\n"
            . urldecode($path);

        return strtoupper(hash_hmac('sha256', $signString, $credentials['secretKey']));
    }

    private function refreshAccessToken(): void
    {
        if ($this->accessToken !== null) {
            $expireSeconds = $this->accessTokenExpireDt->getTimestamp() - (new \DateTime('now'))->getTimestamp();
            if ($expireSeconds - 30 > 0) {
                return;
            }
        }

        try {
            $this->accessToken = '';
            $this->accessTokenExpireDt = new \DateTime('now');
            $response = $this->sendRequest('get', '/v1.0/token?grant_type=1');

            $this->accessToken = $response['access_token'];
            $this->accessTokenExpireDt->modify('+' . (int) $response['expire_time'] . ' seconds');
        } catch (\Throwable $e) {
            $this->accessToken = null;
            $this->accessTokenExpireDt = null;

            throw $e;
        }
    }

    /**
     * @return mixed
     */
    private function jsonDecode(string $json)
    {
        return json_decode($json, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
    }

    /**
     * @param 'get'|'post'                                      $method
     * @param ($method is 'post' ? array<string, mixed> : null) $data
     *
     * @return ($method is 'post' ? bool : array<string, mixed>)
     */
    public function sendRequest(string $method, string $path, ?array $data = null)
    {
        assert(str_starts_with($path, '/'));

        if ($this->accessToken !== '') {
            $this->refreshAccessToken();
        }

        $credentials = $this->getCedentials();

        $body = $data !== null
            ? json_encode($data, \JSON_THROW_ON_ERROR, 512)
            : null;

        $url = 'https://' . $credentials['domain'] . $path;
        $timestampMs = (int) round(microtime(true) * 1000);
        $signature = $this->signRequest($method, $path, $timestampMs, $body ?? '');

        $this->logLine("\n" . '>>> ' . strtoupper($method) . ' ' . $url);

        $response = $this->httpUtil->sendRequest(
            $method,
            $url,
            array_merge(
                [
                    'Accept' => 'application/json',
                    'client_id' => $credentials['accessKey'],
                    't' => (string) $timestampMs,
                    'sign' => $signature,
                    'sign_method' => 'HMAC-SHA256',
                ],
                $this->accessToken === null ? [] : ['access_token' => $this->accessToken],
                $method === 'post' ? ['Content-Type' => 'application/json'] : [],
            ),
            $body
        );

        $this->logLine('    ' . $response[0]);

        assert($response[0] === 200);

        $responseData = $this->jsonDecode($response[2]);

        if (!$responseData['success']) {
            $this->logLine('    API code: ' . ($responseData['code'] ?? 'n/a'));
            $this->logLine('    API message: ' . ($responseData['msg'] ?? 'n/a'));

            throw new TuyaRequestFailedException();
        }

        return $responseData['result'];
    }

    /* /**
     * @return array<string, xxx> https://developer.tuya.com/en/docs/archived-documents/997abb41b9?id=Ka7kk116tsy0c
     * /
    public function queryDeviceList(): array {} */

    /**
     * @return array<string, scalar> https://developer.tuya.com/en/docs/cloud/3829469013?id=Kcp2l2v9wma0m (older: https://developer.tuya.com/en/docs/cloud/7d3f13ae55?id=Kb2rzcwpmvaci)
     */
    public function queryDeviceDetails(string $deviceId): array
    {
        return $this->sendRequest('get', '/v2.0/cloud/thing/' . $deviceId);
    }

    /**
     * @return array{eventTime: 0}|array{eventTime: int, indicatorType: string, signalLevel: string, signal: int} https://developer.tuya.com/en/docs/cloud/2287954993?id=Kdqf1hiwhdc7f
     */
    public function queryDeviceSignalStrength(string $deviceId): array
    {
        $response = $this->sendRequest('post', '/v2.0/cloud/thing/signal/detection/issue', [
            'device_id' => $deviceId,
            'device_type' => 'WiFi',
        ]);
        assert($response === true);

        return $this->sendRequest('get', '/v2.0/cloud/thing/' . $deviceId . '/WiFi/signal'); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, scalar> https://developer.tuya.com/en/docs/cloud/1ef1a3044b?id=Kconf2usgnfwo
     *
     * @deprecated this API can query up too 20 devices per request, but the response does not contain the last value updated time
     */
    public function queryDevicePropertiesV1(string $deviceId): array
    {
        $response = $this->sendRequest('get', '/v1.0/iot-03/devices/' . $deviceId . '/status');

        return array_combine(
            array_map(static fn ($v) => $v['code'], $response),
            array_map(static fn ($v) => $v['value'], $response)
        );
    }

    /**
     * @return array<string, array{custom_name: string, dp_id: int, time: int, type: string, value: scalar}> https://developer.tuya.com/en/docs/cloud/116cc8bf6f?id=Kcp2kwfrpe719
     */
    public function queryDeviceProperties(string $deviceId): array
    {
        $response = $this->sendRequest('get', '/v2.0/cloud/thing/' . $deviceId . '/shadow/properties');

        return array_combine( // @phpstan-ignore return.type
            array_map(static fn ($v) => $v['code'], $response['properties']),
            array_map(static fn ($v) => array_diff_key($v, ['code' => true]), $response['properties'])
        );
    }

    /**
     * @return array{type: string, values: array<string, scalar|list<string>>} https://developer.tuya.com/en/docs/cloud/3ac29198c9?id=Kag2ybepz3arq
     */
    public function queryDeviceFunctions(string $deviceId): array
    {
        $response = $this->sendRequest('get', '/v1.0/iot-03/devices/' . $deviceId . '/functions');

        return array_combine( // @phpstan-ignore return.type
            array_map(static fn ($v) => $v['code'], $response['functions']),
            array_map(function ($v) {
                $v['values'] = $this->jsonDecode($v['values']);

                return array_diff_key($v, ['code' => true, 'desc' => true, 'name' => true]);
            }, $response['functions'])
        );
    }

    /**
     * @param array<string, scalar> $data
     *
     * @see https://developer.tuya.com/en/docs/cloud/e2512fb901?id=Kag2yag3tiqn5
     */
    public function sendCommand(string $deviceId, array $data): void
    {
        $this->sendRequest('post', '/v1.0/iot-03/devices/' . $deviceId . '/commands', [
            'commands' => array_map(static fn ($k, $v) => ['code' => $k, 'value' => $v], array_keys($data), $data),
        ]);
    }
}
