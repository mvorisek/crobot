# Proč použít hotový ESP IDF image?

Instalace přes git clone se submodules nebo přes `eim` (což interně volá taky rekurzivní git clone) je pomalá.

Build pomocí dockeru trvá okolo 12 minut a s nejrychlejší cache pro GHA trvá 4 minuty.

původní/pomalé workflow:
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

původní/pomalý Dockerfile:
```
FROM debian:latest
WORKDIR /usr/local/app

RUN apt-get update && apt-get -y upgrade \
    && apt-get -y install ca-certificates apt-transport-https

RUN echo "deb [trusted=yes] https://dl.espressif.com/dl/eim/apt/ stable main" > /etc/apt/sources.list.d/espressif.list \
    && apt-get update \
    && apt-get -y install eim-cli
RUN eim install
```
