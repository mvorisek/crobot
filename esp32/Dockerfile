FROM debian
WORKDIR /usr/local/app

ARG DEBIAN_FRONTEND=noninteractive
RUN apt update && apt upgrade

RUN echo "deb [trusted=yes] https://dl.espressif.com/dl/eim/apt/ stable main" > /etc/apt/sources.list.d/espressif.list
RUN apt update
RUN apt install eim-cli
RUN eim install
