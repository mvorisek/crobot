FROM docker.io/espressif/idf:v5.5.4
WORKDIR /usr/local/app

RUN apt-get update && apt-get -y upgrade \
    && apt-get -y install expect

ENV IDF_PATH_FORCE=1

RUN git clone --branch v1.29.0 --depth 1 https://github.com/micropython/micropython.git
WORKDIR /usr/local/app/micropython

# https://github.com/micropython/micropython/blob/v1.29.0/ports/esp32/README.md#building-the-firmware
RUN make -C mpy-cross
WORKDIR /usr/local/app/micropython/ports/esp32
RUN . /opt/esp/idf/export.sh && make submodules
RUN . /opt/esp/idf/export.sh && make BOARD=ESP32_GENERIC BOARD_VARIANT=""

# never cache build since this line
ADD "https://www.random.org/cgi-bin/randbyte?nbytes=64&format=h" skipcache

RUN . /opt/esp/idf/export.sh && expect -c 'set timeout 60; spawn make qemu; expect ">>>"; send "print(1+1)\r"; expect ">>>"; sleep 300'
