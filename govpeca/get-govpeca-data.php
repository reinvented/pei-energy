<?php
/**
  * get-govpeca-data.php
  *
  * A PHP script to retrieve Prince Edward Island energy load and
  * generation data from the Province of Prince Edward Island website
  * (http://www.gov.pe.ca/energy/js/chart.php) and return in JSON,
  * Pachube (Cosm) or XML format.
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
  * @version 0.1, June 20, 2012
  * @link https://github.com/reinvented/pei-energy
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2012, Reinvented Inc.
  * @license http://www.fsf.org/licensing/licenses/gpl.txt GNU Public License
  */

/**
  * Set the output format to either "json" or "xml" depending on your needs.
  * Can be set either on command line -- get-govpeca-data.php xml or as a parameter
  * if served as a web page -- .../get-govpeca-data.php?format=xml
  * Default is to serve XML.
  */
if ($_GET) {
  $format = $_GET['format'];
}
else if ($argv[1]) {
  $format = $argv[1];
}
if (!$format) {
  $format = "xml";
}

/**
  * Set the default time zone to Atlantic Standard Time.
  */
date_default_timezone_set("America/Halifax");

/**
  * These are the columns of data present on http://www.gov.pe.ca/energy/js/chart-values.php
  */
$govpeca_columns = array(
                    "data1" => array("id" => "on-island-load",     "tags" => array("PrinceEdwardIsland","electricty","load")),
                    "data2" => array("id" => "on-island-wind",     "tags" => array("PrinceEdwardIsland","electricty","wind","generation")),
                    "data3" => array("id" => "on-island-fossil",   "tags" => array("PrinceEdwardIsland","electricty","fossil","export")),
                    "data4" => array("id" => "wind-local",         "tags" => array("PrinceEdwardIsland","electricty","wind","local")),
                    "data5" => array("id" => "wind-export",        "tags" => array("PrinceEdwardIsland","electricty","wind","export")),
                  );

/**
  * Grab the PEI data as JSON.
  */
$govpeca = "http://www.gov.pe.ca/energy/js/chart-values.php";
$handle = fopen($govpeca,'rb');
$json = stream_get_contents($handle);
fclose($handle);
$data = json_decode($json);
$updatetime = $data[0]->updateDate;

/**
  * Take the data points returned and stuff them into an associative array
  * called $govpeca_data, keyed to the actual names of the data columns we set in $govpeca_columns.
  */
foreach($data[0] as $key => $value) {   
  if ($govpeca_columns[$key]) {
    $govpeca_data[$govpeca_columns[$key]["id"]] = $data[0]->{$key};
    $govpeca_tags[$govpeca_columns[$key]["id"]] = $govpeca_columns[$key]["tags"];
  }
}   

$govpeca_data['percentage-wind'] = ($data[0]->data2 / $data[0]->data1);
$govpeca_tags['percentage-wind'] = array("PrinceEdwardIsland","electricty","wind","load","renewable","percentage");

/**
  * If the format is XML, then use the PEAR XML_Serializer to turn our result array into XML.
  * Otherwise output it as JSON (using PHP's built-in JSON juju).
  */
if ($format == "xml") {
  header('Content-type: application/xml');
  header('Content-Disposition: attachment; filename="govpeca.xml"');

  require_once("XML/Serializer.php");

  $options = array(
    "indent"          => "    ",
    "linebreak"       => "\n",
    "typeHints"       => false,
    "addDecl"         => true,
    "encoding"        => "UTF-8",
    "rootName"        => "govpeca",
    "defaultTagName"  => "measurement",
    "attributesArray" => "_attributes"
    );

  $serializer = new XML_Serializer($options);
  $result = $serializer->serialize($govpeca_data);
  if($result === true) {
   print $serializer->getSerializedData();
  }
}
else if ($format == "json") {
  header('Content-type: application/json');
  header('Content-Disposition: attachment; filename="govpeca.json"');

  print json_encode($govpeca_data);
}
else if ($format == "pachube") {
  header('Content-type: application/json');
  header('Content-Disposition: attachment; filename="govpeca.json"');
 
  $govpeca_pachube['title'] = "Prince Edward Island Electricity Load and Generation";
  $govpeca_pachube['description'] = "Data from the Government of Prince Edward Island concerning electricity generation and load.";
  $govpeca_pachube['feed'] = "http://energy.reinvented.net/pei-energy/govpeca/get-govpeca-data.php?format=pachube";
  $govpeca_pachube['website'] = "http://www.gov.pe.ca/energy/js/chart.php";
  $govpeca_pachube['email'] = "reinvented+govpeca@gmail.com";
  $govpeca_pachube['version'] = "1.0.0";  
  $govpeca_pachube['updated'] = strftime("%Y-%m-%dT%H:%M:%S",$updatetime);
  $govpeca_pachube['location']['name'] = "Prince Edward Island";
  $govpeca_pachube['location']['lat'] = "46.2336";
  $govpeca_pachube['location']['lon'] = "-63.1283";
  $govpeca_pachube['tags'] = array("energy","load","generation","pei");
 
  foreach($govpeca_data as $id => $value) {
    $govpeca_pachube['datastreams'][] = array("id" => $id,"tags" => $govpeca_tags[$id], "current_value" => ($value),"unit" => array("type" => "derivedSI","label" => "Megawatts","symbol" => "MW"));
  }
  
  print json_encode($govpeca_pachube);
 
}