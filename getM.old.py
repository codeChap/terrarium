#!/usr/bin/python
import RPi.GPIO as GPIO
import time
 
#GPIO SETUP
channel = 26
GPIO.setmode(GPIO.BCM)
GPIO.setup(channel, GPIO.IN)
print GPIO.input(channel)