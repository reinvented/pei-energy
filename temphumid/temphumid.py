#!/usr/bin/python

import eeml
import eeml.datastream
import eeml.unit
import serial
import time
import sys
import urllib
import ConfigParser
import os
from eeml import *

config = ConfigParser.ConfigParser()
config.read(os.path.expanduser('~/.temphumid.cfg'))

# parameters
arduino_serial_port = config.get('Arduino', 'arduino_serial_port', 0)
cosm_api_key = config.get('COSM', 'cosm_api_key', 0)
cosm_api_url = config.get('COSM', 'cosm_api_url', 0)
thingspeak_api_key = config.get('ThingSpeak', 'thingspeak_api_key', 0)

arduino = serial.Serial(arduino_serial_port, 115200)

while 1:

	gotdata = 0

	readings = arduino.readline().strip().split('\t')

	if len(readings) < 2:
		print 'Invalid data received; skipping'
	else:
		temperature = readings[1]
		humidity = readings[0]
		if ((float(temperature) > -10) & (float(temperature) < 40)):

			# Print log to screen
			print time.asctime() + '\t' \
				+ readings[1] + ' degrees | ' \
				+ readings[0] + '% humidity'
			sys.stdout.flush() 
			
			# Send to COSM.com
			pac = eeml.datastream.Cosm(cosm_api_url, cosm_api_key)
			pac.update([eeml.Data(0, temperature, unit=eeml.unit.Celsius()), \
				eeml.Data(1, humidity, unit=eeml.unit.RH())])
	
			try:
				pac.put()
			except CosmError, e:
				print('ERROR: pac.put(): {}'.format(e))
			except StandardError:
				print('ERROR: StandardError')
			except:
				print('ERROR: Unexpected error: %s' % sys.exc_info()[0])
				
			# Send to Thingspeak
			urllib.urlopen("http://api.thingspeak.com/update?key=" \
				+ thingspeak_api_key \
				+ "&field1=" + temperature \
				+ "&field2=" + humidity)

	time.sleep(30) ## sleep for 60 seconds
