<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tests\Tuya;

use Atk4\Core\Phpunit\TestCase;
use Mvorisek\Crobot\Tuya\TuyaApi;

class TuyaApiTest extends TestCase
{
    public function testAccountBasic(): void
    {
        $tuyaApi = new TuyaApi();

        $deviceIdSwitch = 'bff84a63235f3a1b014nkg'; // "zásuvka ven obě"

        $res = $tuyaApi->queryDeviceList();
        self::assertArrayHasKey($deviceIdSwitch, $res);
        self::assertSame($deviceIdSwitch, $res[$deviceIdSwitch]['id']);
        self::assertSame('Smart Plug', $res[$deviceIdSwitch]['name']);
    }

    public function testDeviceBasic(): void
    {
        $tuyaApi = new TuyaApi();

        $deviceIdSwitch = 'bff84a63235f3a1b014nkg'; // "zásuvka ven obě"

        $res = $tuyaApi->queryDeviceDetails($deviceIdSwitch);
        self::assertSame('Smart Plug', $res['name']);

        $res = $tuyaApi->queryDeviceSignalStrength($deviceIdSwitch);
        self::assertSame('RSSI', $res['indicatorType']);
        self::assertLessThan(-10, $res['signal']);

        $res = $tuyaApi->queryDeviceProperties($deviceIdSwitch);
        self::assertSame(1, $res['switch_1']['dp_id']);
        self::assertSame('bool', $res['switch_1']['type']);
        self::assertIsBool($res['switch_1']['value']);
        self::assertGreaterThan((230 - 50) * 10, $res['cur_voltage']['value']);
        self::assertLessThan((230 + 50) * 10, $res['cur_voltage']['value']);

        $res = $tuyaApi->queryDeviceFunctions($deviceIdSwitch);
        self::assertSame(['type' => 'Enum', 'values' => ['range' => ['power_off', 'power_on', 'last']]], $res['relay_status']);

        // test write - sadly it is not synchronous
        if (false) { // @phpstan-ignore if.alwaysFalse
            $tuyaApi->sendCommand($deviceIdSwitch, ['relay_status' => 'power_off']);
            $res = $tuyaApi->queryDeviceProperties($deviceIdSwitch);
            self::assertSame('off', $res['relay_status']['value']);
        }
        $tuyaApi->sendCommand($deviceIdSwitch, ['relay_status' => 'last']);
        $res = $tuyaApi->queryDeviceProperties($deviceIdSwitch);
        self::assertSame('memory', $res['relay_status']['value']);
    }
}
