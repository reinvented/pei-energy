temphumid.py
============

A Python script to poll an Arduino for temperature and humidity data (in my case from a DHT22 sensor) and echo this to the screen, upload to cosm.com, thingspeak.com and open.sen.se.

Code adapted from [http://community.cosm.com/?q=node/114](http://community.cosm.com/?q=node/114)

Requirements
------------

 * An Arduino sketch sending temperature and humidity data over a serial port.
 * python-eeml - [https://github.com/petervizi/python-eeml/](https://github.com/petervizi/python-eeml/)
 * pySerial - [http://pyserial.sourceforge.net/](http://pyserial.sourceforge.net/)
 * Account on [cosm.com](http://cosm.com)
 * Account on [thingspeak.com](http://thingspeak.com)
 * Account on [open.sen.se](http://open.sen.se)
 
Arduino Setup
-------------

Install the sketch dht22.ino on your Arduino and monitor the serial port to ensure that you're seeing temperature and humidity data flowing. You'll find helpful resources at:

 * [http://learn.adafruit.com/dht/connecting-to-a-dhtxx-sensor](http://learn.adafruit.com/dht/connecting-to-a-dhtxx-sensor)
 * [http://playground.arduino.cc/Main/DHTLib](http://playground.arduino.cc/Main/DHTLib)

Configuration
-------------

Create a configuration file at ~/.temphumid.cfg with these settings:

	[Arduino]
	arduino_serial_port = /dev/tty.usbserial-A6008jtr

	[COSM]
	cosm_api_key = **Put your cosm.com API key here**
	cosm_api_url = **Put your cosm.xom API URL here**

	[ThingSpeak]
	thingspeak_api_key = **Put your thingspeak.com API key here**
	
	[Sen.se]
	sen_se_api_key = **Put your open.sen.se API key here**
	sen_se_temperature_id = **Put your open.sen.se temperature feed ID here**
	sen_se_humidity_id = **Put your open.sen.se humidity feed ID here**
	
You can find the value for *arduino_serial_port* in your Arduino editor.

The value for *cosm_api_url* will look like */v2/feeds/104026.xml*.

Usage
-----

	python ./temphumid.py	
	
	