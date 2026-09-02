<?php

declare(strict_types=1);

namespace Mvorisek\Crobot;

class HttpUtil
{
    /**
     * @param 'get'|'put'           $method
     * @param array<string, string> $headers
     *
     * @return array{int<100, 999>, array<string, string>, string, array<string, mixed>}
     */
    public function sendRequest(string $method, string $url, array $headers): array
    {
        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, array_map(static fn ($k, $v) => $k . ': ' . $v, array_keys($headers), $headers));
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HEADER, true);
        curl_setopt($ch, \CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, \CURLOPT_TIMEOUT, 30);

        try {
            $response = curl_exec($ch);

            $eCode = curl_errno($ch);
            if ($eCode !== 0) {
                throw new \Exception('Curl request failed: ' . curl_error($ch), $eCode);
            }

            $info = curl_getinfo($ch);
        } finally {
            curl_close($ch);
        }

        [$responseHeader, $responseBody] = explode("\r\n\r\n", $response, 2);

        $responseHeadersList = explode("\r\n", $responseHeader);
        array_shift($responseHeadersList);
        $responseHeaders = [];
        foreach ($responseHeadersList as $line) {
            [$k, $v] = explode(':', $line, 2);
            $k = strtolower($k);
            $v = trim($v);

            if (!isset($responseHeaders[$k])) {
                $responseHeaders[$k] = $v;
            } else {
                if (!is_array($responseHeaders[$k])) {
                    $responseHeaders[$k] = [$responseHeaders[$k]];
                }

                $responseHeaders[$k][] = $v;
            }
        }

        return [$info['http_code'], $responseHeaders, $responseBody, $info];
    }
}
