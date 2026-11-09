import network
import time
import urequests
import socket

# Initialize Wi-Fi in station mode
sta_if = network.WLAN(network.STA_IF)

# softreset handling https://share.google/aimode/T7P9fsOSGdKauWE0k

# Activate the interface
sta_if.active(True)

# Connect to your wireless network
# https://github.com/espressif/esp-emulator/blob/v0.51.0/README.md?plain=1#L377
ssid = 'myssid'
password = 'mypassword'

if not sta_if.isconnected():
    print('Connecting to network...')
    sta_if.connect(ssid, password)

    # Wait until connected
    while not sta_if.isconnected():
        # with timeout https://share.google/aimode/T7P9fsOSGdKauWE0k
        time.sleep(0.1)

print('Connected!')
print('Network configuration:', sta_if.ifconfig())

print( socket.getaddrinfo('www.google.com', 80) )

print("--- HTTP Headers Only ---")
response = urequests.get("https://status.cloud.google.com/incidents.json")
for key, value in response.headers.items():
    print(f"{key}: {value}")

# Free up memory
response.close()
