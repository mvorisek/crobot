<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tests;

use Atk4\Core\Phpunit\TestCase;
use Mvorisek\Crobot\HttpUtil;

class HttpUtilTest extends TestCase
{
    public function testSendRequest(): void
    {
        $httpUtil = new HttpUtil();

        $response = $httpUtil->sendRequest('get', 'https://ifconfig.io/ip', ['x-test' => 'y']);
        self::assertSame(200, $response[0]);
        self::assertStringStartsWith('text/plain', $response[1]['content-type']);
        self::assertMatchesRegularExpression('~^[^\n]+\n$~', $response[2]);
        self::assertStringContainsString("\r\nx-test: y\r\n", $response[3]['request_header']);

        $response = $httpUtil->sendRequest('get', 'https://ifconfig.io/404');
        self::assertSame(404, $response[0]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('Curl request failed: Could not resolve host: -.cz');
        $httpUtil->sendRequest('get', 'https://-.cz/');
    }
}
