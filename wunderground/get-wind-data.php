<?php
/**
  * get-wunderground-data.php
  *
  * A PHP script to retrieve wind speed for a given location and from
  * Wunderground.com and format it for feeding to Pachube.
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

$stationquery = "East Point, Prince Edward Island";

/**
  * Set the default time zone to Atlantic Standard Time.
  */
date_default_timezone_set("America/Halifax");

$xml = simplexml_load_file("http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=" . urlencode($stationquery));

$kmh = 1.609344 * (string)$xml->wind_mph;

header('Content-type: application/json');
header('Content-Disposition: attachment; filename="wind.json"');

$pachube['title'] = "Wind Speed at " . $stationquery;
$pachube['description'] = "Current wind speed, retrieved using Wunderground.com API, for " . $stationquery;
$pachube['feed'] = "http://energy.reinvented.net/pei-energy/wunderground/get-wunderground-data.php";
$pachube['website'] = "http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=" . urlencode($stationquery);
$pachube['email'] = "reinvented+wunderground@gmail.com";
$pachube['version'] = "1.0.0";  
$pachube['updated'] = strftime("%Y-%m-%dT%H:%M:%S");
$pachube['location']['name'] = "East Point, Prince Edward Island, Canada";
$pachube['location']['lat'] = "46.45000076";
$pachube['location']['lon'] = "-61.97000122";
$pachube['tags'] = array("weather","wind");

$pachube['datastreams'][] = array("id" => "windspeed", "current_value" => $kmh,"unit" => array("type" => "derivedSI", "label" => "Kilometers Per Hour","symbol" => "km/h"));

print json_encode($pachube);
