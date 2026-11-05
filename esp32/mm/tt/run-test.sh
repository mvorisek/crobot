# Ujistěte se, že cílíte na ESP32
idf.py set-target esp32
idf.py build

# Spuštění testu v emulátoru
pytest --embedded-services idf,qemu

