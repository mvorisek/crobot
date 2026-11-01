# Dockerfile

```
FROM debian:latest
WORKDIR /usr/local/app

RUN apt-get -y update && apt-get -y upgrade && apt-get -y install ca-certificates apt-transport-https

RUN echo "deb [trusted=yes] https://dl.espressif.com/dl/eim/apt/ stable main" > /etc/apt/sources.list.d/espressif.list
RUN apt-get -y update
RUN apt-get -y install eim-cli
RUN eim install
```

# workflow - bez cache cca 8 minut, s nejlepší cachí 4 minuty (40 sekund se obnovuje, samotný build potom trvá cca 3 minuty i pokud jsou všechny vrstvy cache, importing/exporting "eim install" vytváří zřejmě hodně souborů)

```
      - name: Checkout
        uses: actions/checkout@v7

      - name: Setup Docker Buildx
        uses: docker/setup-buildx-action@v4

      - name: Cache Docker layers
        uses: actions/cache@v5
        with:
          path: ${{ runner.temp }}/.buildx-cache
          key: buildx-
          restore-keys: buildx-

      - name: Build Docker image
        run: |
          cd esp32
          docker buildx build --cache-from type=local,src=${{ runner.temp }}/.buildx-cache --cache-to type=local,dest=${{ runner.temp }}/.buildx-cache,mode=max,reset=true --tag app:latest --load .
```
