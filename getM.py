import RPi.GPIO as GPIO
import time
GPIO.setmode(GPIO.BCM)
channel = 26

#Define function to measure charge time
def RC_Analog(Pin):
	counter=0
	start_time = time.time()
	#Discharge capacitor
	GPIO.setup(channel, GPIO.OUT)
	GPIO.output(channel, GPIO.LOW)
	time.sleep(0.1) #in seconds, suspends execution.
	GPIO.setup(channel, GPIO.IN)
	#Count loops until voltage across capacitor reads high on GPIO
	while (GPIO.input(channel)==GPIO.LOW):
		counter=counter+1
	end_time = time.time()
	return end_time - start_time

	#Main program loop
#while True:
#	time.sleep(1)
reading = RC_Analog(4) #store counts in a variable
print reading #print counts using GPIO4
#	break

GPIO.cleanup()
