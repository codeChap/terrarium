import time
from w1thermsensor import W1ThermSensor
sensor = W1ThermSensor()

temperature = sensor.get_temperature()
print(temperature)
