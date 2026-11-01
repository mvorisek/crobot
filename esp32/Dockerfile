FROM debian
WORKDIR /usr/local/app

RUN apt -y update && apt -y upgrade

RUN echo "deb [trusted=yes] https://dl.espressif.com/dl/eim/apt/ stable main" > /etc/apt/sources.list.d/espressif.list
RUN apt -y update
RUN apt -y install eim-cli
RUN eim install
