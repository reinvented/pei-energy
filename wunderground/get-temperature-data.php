<?php
/**
  * get-temperature-data.php
  *
  * A PHP script to retrieve current temperature for a given location and from
  * Wunderground.com and format it for feeding to Cosm.
  *
  * Requires XML_Serializer -- install with 'pear install XML_Serializer'.
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or (at
  * your option) any later version.
  *
  * This program is distributed in the hope that it will be useful, but
  * WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
  * General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
  * USA
  *
  * @version 0.1, March 30, 2011
  * @link https://github.com/reinvented/pei-energy
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2011, Reinvented Inc.
  * @license http://www.fsf.org/licensing/licenses/gpl.txt GNU Public License
  */

/**
  * Set the weather station. This needs to match a *single* Wunderground.com
  * weather station.
  */
if ($_GET) {
  $stationquery = $_GET['stationquery'];
}
else if ($argv[1]) {
  $stationquery = $argv[1];
}
if (!$stationquery) {
  $stationquery = "Charlottetown, Prince Edward Island";
}

/**
  * Set the default time zone to Atlantic Standard Time.
  */
date_default_timezone_set("America/Halifax");

$xml = simplexml_load_file("http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=" . urlencode($stationquery));

header('Content-type: application/json');
header('Content-Disposition: attachment; filename="temperature.json"');

$pachube['title'] = "Temperature at " . $stationquery;
$pachube['description'] = "Current temperature, retrieved using Wunderground.com API, for " . $stationquery;
$pachube['feed'] = "http://energy.reinvented.net/pei-energy/wunderground/get-temperature-data.php?stationquery=" . urlencode($stationquery);
$pachube['website'] = "http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=" . urlencode($stationquery);
$pachube['email'] = "reinvented+wunderground@gmail.com";
$pachube['version'] = "1.0.0";  
$pachube['updated'] = strftime("%Y-%m-%dT%H:%M:%S");
$pachube['location']['name'] = $stationquery;
$pachube['location']['lat'] = (string)$xml->display_location->latitude;
$pachube['location']['lon'] = (string)$xml->display_location->longitude;
$pachube['tags'] = array("weather","temperature");

$pachube['datastreams'][] = array("id" => "temperature", "current_value" => (string)$xml->temp_c,"unit" => array("type" => "derivedSI", "label" => "Degrees C","symbol" => "°C"));

print json_encode($pachube);
