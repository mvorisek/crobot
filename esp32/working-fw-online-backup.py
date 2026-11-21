import time
startScriptTicksMs = time.ticks_ms()

_global_ = globals()["_global_"]

# idf_alloc: 152448, idf_free: 85232, idf_min_alltime: 46812
# gc_alloc: 17616, gc_free: 85488




# import json
# print(json.dumps(_global_.deviceInfo.__dict__))

# _global_.dumpFreeMemory()



globals().setdefault("__mvorisek__nesting_counter__", [0])

print(f"\n\nboot ID: {_global_.deviceInfo.bootId}, nesting counter: {globals()["__mvorisek__nesting_counter__"]}")


# TODO does not work with int not list (esp32.idf_heap_info(esp32.HEAP_DATA))!
def dump(obj) -> None:
    print(f"* DUMP {type(obj).__name__}#{id(obj)}:")
    for attr in dir(obj):
        if not attr.startswith("__"): # skip built-in dunder methods
            print(f"{attr} = {getattr(obj, attr)}")
    print("*")

import io
import os

# emulate https://docs.python.org/3/library/contextlib.html#contextlib.redirect_stdout
# TODO tests:
# - nested capture
# - print to stderr
class CaptureTerm(io.IOBase): 
    def __init__(self, slot = 0):
        self.slot = slot
        self.buf = io.BytesIO()

    def readinto(self, b):
        return None

    def write(self, data):
        return self.buf.write(data)

    def __enter__(self):
        self.orig = os.dupterm(self, self.slot)
        return self

    def __exit__(self, *args):
        os.dupterm(self.orig, self.slot)
        del self.orig

    def text(self):
        return self.buf.getvalue().decode()

class ExecuteFxWithCaptureTermAndExceptionResult:
    returnValue: Any
    termOutput: str
    exception: BaseException | None = None

def executeFxWithCaptureTermAndException(fx, *args, **kwargs) -> ExecuteFxWithCaptureTermAndExceptionResult:
  res = ExecuteFxWithCaptureTermAndExceptionResult()
  with CaptureTerm() as out:
    try:
      res.returnValue = fx(*args, **kwargs)
    except BaseException as e:
      res.returnValue = None
      res.exception = e
      print("\n* Unhandled exception:")
      print(_global_.formatException(e))
      print("*")
  res.termOutput = out.text()
  return res

def executeFxWithRetry(fx, maxRetries, *args, **kwargs) -> Any:
    i = 0
    while True:
        try:
          return fx(*args, **kwargs)
        except BaseException:
          if i >= maxRetries:
            raise
        i += 1

import machine
import network
def getLan() -> LAN | None:
    # for WT32-ETH01 board with LAN8720A ethernet chip
    # https://docs.micropython.org/en/v1.29.0/esp32/quickref.html#lan
    try:
        lan = network.LAN(
            mdc=machine.Pin(23),
            mdio=machine.Pin(18),
            power=machine.Pin(16),
            phy_type=network.PHY_LAN8720,
            phy_addr=1
        )
        lan.active(True)
        
        return lan
    except OSError as e:
        # board without LAN chip
        # https://github.com/micropython/micropython/blob/v1.29.0/ports/esp32/network_lan.c#L435
        if str(e) == "esp_eth_driver_install failed":
            return None
        raise

# needed only after static IP is assigned - DHCP is started automatically
def startLanDhcp(lan: LAN) -> None:
    try:
        lan.ifconfig("dhcp")
    except RuntimeError as e:
        # DHCP already started
        # https://github.com/espressif/esp-idf/blob/v6.1.1/components/esp_netif/lwip/esp_netif_lwip.c#L1695
        if str(e) != "Wifi Unknown Error 0x5004":
            raise

#lan = getLan()
if 0 and lan is not None:
    while not lan.isconnected():
        print("boot.py: Connecting to LAN...")
        time.sleep(0.2)

    print("Cable plugged in (Link Up):", lan.isconnected())
    print("Network config (IP, Subnet, GW, DNS):", lan.ifconfig())

import requests
def downloadScript():
    with requests.get(_global_.apiBaseUrl + '?action=get_latest_script', timeout=4) as resp:
        return resp.text

def uploadResult(v):
    with requests.post(_global_.apiBaseUrl + '?action=upload_latest_result', data=v, timeout=4) as resp:
        pass


print(f"Elapsed time since start: {time.ticks_diff(time.ticks_ms(), startScriptTicksMs)} ms")








# main loop - download script and upload the result
if len(globals()["__mvorisek__nesting_counter__"]) <= 1:
    while True:
        globals()["__mvorisek__nesting_counter__"].append(0)
        try:
            start = time.ticks_ms() # debug
            print("d")
            
            

            
            
            
            res = executeFxWithCaptureTermAndException(downloadScript)
            if res.exception is not None:
                print("# download failed")
                continue
            code = res.returnValue
            print("de") # debug
            print(f"Elapsed download time: {time.ticks_diff(time.ticks_ms(), start)} ms")
            
            res = executeFxWithCaptureTermAndException(exec, code)
            
            res = executeFxWithCaptureTermAndException(uploadResult, res.termOutput)
            if res.exception is not None:
                print("# upload failed")
                continue
            
            print('... main loop iteration (download script + upload result) completed ...')
        finally:
            del globals()["__mvorisek__nesting_counter__"][-1]
            globals()["__mvorisek__nesting_counter__"][-1] += 1
      






def fWriteIfChanged(path, content):
    with open(path, "w+", encoding="utf-8") as file:
        v = file.read()
        if v != content:
            file.write(content)
            return True
    return False

# boot.py and main.py files are executed on every boot (including wake-boot from deepsleep)
# https://github.com/micropython/micropython/blob/v1.29.0/ports/esp32/main.c#L155
fWriteIfChanged('/boot.py', """
import os
import machine
import binascii
import esp32
import esp
import io
import sys
import gc

class DeviceInfo:
    def __init__(self):
        self.osName = "MicroPython"
        self.osVersion = os.uname().version
        self.machineFamily = os.uname().machine
        self.machineId = binascii.hexlify(machine.unique_id()).decode()
        self.machineFrequency = machine.freq()
        self.dramSize = sum(v[0] for v in esp32.idf_heap_info(esp32.HEAP_DATA))
        self.flashSize = esp.flash_size()
        self.bootId = binascii.hexlify(os.urandom(16)).decode()

class _Global_:
    def __init__(self):
        self.deviceInfo = DeviceInfo()

    def formatException(self, e):
        buf = io.StringIO()
        sys.print_exception(e, buf)
        return buf.getvalue()

    def dumpFreeMemory(self):
        gc.collect()
        idfHeapInfo = esp32.idf_heap_info(esp32.HEAP_DATA)
        print(f"# idf_alloc: {sum(v[0] - v[1] for v in idfHeapInfo)}, idf_free: {sum(v[1] for v in idfHeapInfo)}, idf_min_alltime: {sum(v[3] for v in idfHeapInfo)}")
        gc.collect()
        print(f"# gc_alloc: {gc.mem_alloc()}, gc_free: {gc.mem_free()}")

globals()["_global_"] = _Global_()
_global_ = globals()["_global_"]

_global_.apiBaseUrl = "https://esp32.rostiapp.cz/main.php"

import time
led = machine.Pin(2, machine.Pin.OUT)
led.value(False)
time.sleep(0.1)
led.value(True)
time.sleep(0.1)
led.value(False)

# idf_alloc: 95848, idf_free: 141832, idf_min_alltime: 141808 (unchanged since script start)
# gc_alloc: 5056, gc_free: 161536

import network
wlan = network.WLAN(network.STA_IF)
wlan.active(True)
wlan.connect('netis_esp32', 'Esp100Heslo')

# idf_alloc: 145240, idf_free: 92440, idf_min_alltime: 92412
# gc_alloc: 5088, gc_free: 141024

while not wlan.isconnected():
    print("boot.py: Connecting to WLAN...")
    time.sleep(0.2)
led.value(True)

# idf_alloc: 151512, idf_free: 86168, idf_min_alltime: 85900
# gc_alloc: 5088, gc_free: 132832

print("boot.py: ***** completed *****")
print("")

""")

fWriteIfChanged('/main.py', """
import machine
import requests

_global_ = globals()["_global_"]

# idf_alloc: 151512, idf_free: 86168, idf_min_alltime: 86020
# gc_alloc: 7216, gc_free: 130704
print("main.py: Downloading remote code...")
responseStatusCode = -1
responseText = ""
try:
    with requests.get(_global_.apiBaseUrl + '?action=get_latest_script', timeout=4) as response:
        # idf_alloc: 183828, idf_free: 53852, idf_min_alltime: 47032
        # gc_alloc: 10096, gc_free: 93008
        # (idf_xxx varies a lot)
        # idf_alloc: 190464, idf_free: 47216, idf_min_alltime: 45948
        # gc_alloc: 10128, gc_free: 86832
        responseStatusCode = response.status_code
        responseText = response.text
except BaseException as e:
    print("main.py: GET exception:")
    print(_global_.formatException(e))

# idf_alloc: 152828, idf_free: 84852, idf_min_alltime: 47012
# gc_alloc: 12816, gc_free: 90288
exitMessage = "Remote code exit..."
if responseStatusCode != 200:
    print("main.py: Download failed!")
else:
    print("main.py: Executing remote code...")
    try:
        # idf_alloc: 152248, idf_free: 85432, idf_min_alltime: 46984
        # gc_alloc: 12976, gc_free: 92176
        exec(responseText)
        # idf_alloc: 152224, idf_free: 85456, idf_min_alltime: 47008
        # gc_alloc: 18448, gc_free: 84656
    except BaseException as e:
        exitMessage = "Remote code unhandled exception:\\n" + _global_.formatException(e) + "\\n"

print("")
print("main.py: " + exitMessage + " - remote code should loop forever")
try:
    with requests.post(_global_.apiBaseUrl + '?action=upload_latest_result', data="MAIN LOOP EXIT: " + exitMessage + " (time: " + str(int(time.time_ns() / 1000)) + ")", timeout=4) as response:
        # idf_alloc: 186296, idf_free: 51384, idf_min_alltime: 46088
        # gc_alloc: 21440, gc_free: 81664
        pass
except BaseException as e:
    print("main.py: POST exception:")
    print(_global_.formatException(e))

# idf_alloc: 152452, idf_free: 85228, idf_min_alltime: 46812
# gc_alloc: 17440, gc_free: 87712
print("")
time.sleep(0.5)
machine.reset()

""")
