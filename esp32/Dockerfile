FROM debian:latest
WORKDIR /usr/local/app

RUN apt-get update && apt-get -y upgrade \
    && apt-get -y install ca-certificates apt-transport-https

RUN echo "deb [trusted=yes] https://dl.espressif.com/dl/eim/apt/ stable main" > /etc/apt/sources.list.d/espressif.list \
    && apt-get update \
    && apt-get -y install eim-cli
RUN eim install

RUN echo 55
