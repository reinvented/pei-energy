// Arduino sketch to read a DHT22 temperature/humidity sensor.
// Code based on http://playground.arduino.cc/Main/DHTLib

#include <dht.h>

dht DHT;

#define DHT22_PIN 7

void setup()
{
  Serial.begin(115200);
}

void loop()
{
  int chk = DHT.read22(DHT22_PIN);
  Serial.print(DHT.humidity, 1);
  Serial.print("\t");
  Serial.println(DHT.temperature, 1);

  delay(2000);
}
