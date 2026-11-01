FROM debian
WORKDIR /usr/local/app

RUN apt-get -y update && apt-get -y upgrade && apt-get -y install ca-certificates apt-transport-https

RUN echo "deb [trusted=yes] https://dl.espressif.com/dl/eim/apt/ stable main" > /etc/apt/sources.list.d/espressif.list
RUN apt-get -y update
#RUN apt-get -y install eim-cli
#RUN eim install
