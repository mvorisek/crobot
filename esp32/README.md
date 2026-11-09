# Docker build with cache

Buildx with local cache (`--cache-from type=local`) and `actions/cache` is much faster than with native GHA cache `--cache-from type=sha`, example:

```
      - name: Setup Docker Buildx
        uses: docker/setup-buildx-action@v4

      - name: Cache Docker layers
        uses: actions/cache@v5
        with:
          path: ${{ runner.temp }}/.buildx-cache
          key: ${{ runner.os }}-buildx-${{ hashFiles('esp32/Dockerfile') }}
          restore-keys: |
            ${{ runner.os }}-buildx-

      - name: Build Docker image
        run: |
          cd esp32
          docker buildx build \
          --cache-from type=local,src=${{ runner.temp }}/.buildx-cache \
          --cache-to type=local,dest=${{ runner.temp }}/.buildx-cache,mode=max,reset=true \
          --tag app:latest --load .
```

# Upload MicroPython

Create venv + `pip install esptool` first.

```
esptool.py --port /dev/ttyUSB0 erase-flash
esptool.py --port /dev/ttyUSB0 write-flash --flash-mode dio --flash-freq 40m --flash-size 2MB 0x10000 /tmp/hello_world.bin
esptool.py --port /dev/ttyUSB0 write-flash --flash-mode dio --flash-freq 40m --flash-size 2MB 0x1000 ESP32_GENERIC-20260824-v1.29.0.bin
```

(TODO is flash mode/freq/size/offset really needed to be explicit?)

# Upload deploy script into newly installed MicroPython

`mpremote` is official MicroPython tool, has more reliable data transfer (according to Gemini) and is already more popular then `ampy`.

# Why MicroPython?

- IDF/Arduino needs compilation
- MicroPython is more than 10x popular than Lua (at least for EPS32)

# Why ESP32

- RP2040 is slower and has less RAM, no flash, no WiFi/ethernet
- ESP8266 is obsolete and less powerful (single core) and has no DAC, no HW PWM, no ethernet
- ESP32 has thernet
- EPS32-S3 is similar but no ethenet, but supports BLE5 and USB OTG
- ESP32-S31 is expensive, but has 1 Gbps ethernet
- WROOM = integrated antenna
- WROVER = ESP32 with external PSRAM
- https://www.olimex.com/Products/IoT/ESP32/ESP32-POE/ cheap board with ethernet with isolated PoE

# TODO - OTA

https://mischianti.org/esp32-self-ota-update-firmware-from-server-1/

https://randomnerdtutorials.com/esp32-esp8266-micropython-ota-updates/

https://github.com/orgs/micropython/discussions/12751

# TODO - Tuya out of the box akternatives

Tasmota, ESPHome, Home Assistant

https://www.youtube.com/watch?v=llA2mdCh7Kc

# TODO - Testing with pytest

https://blog.gistre.epita.fr/posts/brice.parent-2023-09-11-introduction_to_testing_esp32_code_with_pytest/

```
# Spuštění testu v emulátoru
pytest --embedded-services idf,qemu
```

```
import pytest

@pytest.mark.qemu
def test_hello_world_on_qemu(dut):
    # 'dut' automaticky zastupuje běžící instanci v QEMU
    dut.expect_exact("Hello world!")
```

