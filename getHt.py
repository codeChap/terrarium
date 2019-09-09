import Adafruit_DHT
h,t = Adafruit_DHT.read_retry(Adafruit_DHT.DHT11,19)
print '{0:0},{1:0}'.format(t,h)
