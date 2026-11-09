FROM debian:stable-slim
WORKDIR /usr/local/app

RUN apt-get update && apt-get -y upgrade \
    && apt-get -y install curl python3-venv

RUN curl -fsSL https://raw.githubusercontent.com/espressif/esp-emulator/main/install.sh | sh -s -- --version 1.0.0-rc2

RUN python3 -m venv .venv
RUN . .venv/bin/activate && pip install mpremote

RUN curl -fsSL -o fw-micropython-esp32s3.bin https://micropython.org/resources/firmware/ESP32_GENERIC_S3-20260824-v1.29.0.bin

# never cache build since this line
ADD "https://www.random.org/cgi-bin/randbyte?nbytes=64&format=h" skipcache

COPY deploy.py .

RUN ~/.local/bin/esp-emu --chip esp32s3 --firmware fw-micropython-esp32s3.bin --uart-tcp 127.0.0.1:5555 --timeout 5s \
        --inject-on '>>> ' --inject 'print("hello")\n' --exit-on '__my_done__' & \
    . .venv/bin/activate && sleep 1 && mpremote connect socket://127.0.0.1:5555 + run deploy.py
