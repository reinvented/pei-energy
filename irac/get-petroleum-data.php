<?php
/**
  * get-petroleum-data.php
  *
  * A PHP script to retrieve "Approved Minimum and Maximum Prices" data for
  * petroleum products in Prince Edward Island, Canada from the Island
  * Regulatory and Appeals Commission from:
  * http://www.irac.pe.ca/document.asp?file=petrol/currentprices.asp
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
  * @version 0.1, April 16, 2011
  * @link https://github.com/reinvented/pei-energy
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2011, Reinvented Inc.
  * @license http://www.fsf.org/licensing/licenses/gpl.txt GNU Public License
  */

/**
  * Set the output format to either "json" or "xml" depending on your needs.
  * Can be set either on command line -- get-petroleum-data.php xml or as a parameter
  * if served as a web page -- .../get-petroleum-data.php?format=xml
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
  * These are the data points present on http://www.irac.pe.ca/document.asp?file=petrol/currentprices.asp
  */
$irac_columns = array("Regular-Self-Serve-Min",
                      "Regular-Self-Serve-Max",
                      "Regular-Full-Serve-Min",
                      "Regular-Full-Serve-Max",
                      "Mid-Grade-Self-Serve-Min",
                      "Mid-Grade-Self-Serve-Max",
                      "Mid-Grade-Full-Serve-Min",
                      "Mid-Grade-Full-Serve-Max",                      
                      "Premium-Self-Serve-Min",
                      "Premium-Self-Serve-Max",
                      "Premium-Full-Serve-Min",
                      "Premium-Full-Serve-Max",                    
                      "Diesel-Self-Serve-Min",
                      "Diesel-Self-Serve-Max",
                      "Diesel-Full-Serve-Min",
                      "Diesel-Full-Serve-Max");                   

/**
  * Grab the IRAC Approved Minimum and Maximum Prices web page so that data can be scraped out of it.
  */
$nsbo = "http://www.irac.pe.ca/document.asp?file=petrol/currentprices.asp";
$handle = fopen($nsbo,'rb');
$html = stream_get_contents($handle);
fclose($handle);

/**
  * The data points in the HTML returned from IRAC can be pulled out using a regular
  * expression. The lines with data look like this:
  *
  * <font face="Trebuchet MS" size="2">126.0</font></td>
  *
  * So that's what we regex for.
  */
preg_match_all("/<font face=\"Trebuchet MS\" size=\"2\">(\d\d\d\.\d)<\/font><\/td>/",$html,$matches,PREG_SET_ORDER);

/**
  * Take the data points returned in the $matches array and stuff them into an associative array
  * called $irac_data, keyed to the actual names of the data columns we set in $irac_columns.
  */
foreach($matches as $key => $value) {
  $irac_data[$irac_columns[$key]] = $value[1];
}

/**
  * If the format is XML, then use the PEAR XML_Serializer to turn our result array into XML.
  * Otherwise output it as JSON (using PHP's built-in JSON juju).
  */
if ($format == "xml") {
  header('Content-type: application/xml');
  header('Content-Disposition: attachment; filename="irac.xml"');

  require_once("XML/Serializer.php");

  $options = array(
    "indent"          => "    ",
    "linebreak"       => "\n",
    "typeHints"       => false,
    "addDecl"         => true,
    "encoding"        => "UTF-8",
    "rootName"        => "irac",
    "defaultTagName"  => "grade",
    "attributesArray" => "_attributes"
    );

  $serializer = new XML_Serializer($options);
  $result = $serializer->serialize($irac_data);
  if($result === true) {
   print $serializer->getSerializedData();
  }
}
else if ($format == "json") {
  header('Content-type: application/json');
  header('Content-Disposition: attachment; filename="irac.json"');

  print json_encode($irac_data);
}
else if ($format == "pachube") {
  header('Content-type: application/json');
  header('Content-Disposition: attachment; filename="nbso.json"');
 
  $nbso_pachube['title'] = "Prince Edward Island Approved Minimum and Maximum Prices for Petroleum";
  $nbso_pachube['description'] = "Data from the Island Regulatory and Appeals Commission in Prince Edward Island, Canada on Approved Minimum and Maximum Prices for Petroleum. NOTE that this is NOT an official project of IRAC.";
  $nbso_pachube['feed'] = "http://energy.reinvented.net/pei-energy/irac/get-petroleum-data.php?format=pachube";
  $nbso_pachube['website'] = "http://www.irac.pe.ca/document.asp?file=petrol/currentprices.asp";
  $nbso_pachube['email'] = "reinvented+irac@gmail.com";
  $nbso_pachube['version'] = "1.0.0";  
  $nbso_pachube['updated'] = strftime("%Y-%m-%dT%H:%M:%S");
  $nbso_pachube['location']['name'] = "Island Regulatory and Appeals Commission";
  $nbso_pachube['location']['lat'] = "46.2359456";
  $nbso_pachube['location']['lon'] = "-63.1280203 ";
  $nbso_pachube['tags'] = array("energy","petroleum","gasoline","gas","petrol","prices","regulated");
 
  foreach($irac_data as $id => $value) {
    $nbso_pachube['datastreams'][] = array("id" => $id, "current_value" => ($value),"unit" => array("label" => "Cents per Litre","symbol" => "cpl"));
  }
  
  print json_encode($nbso_pachube);
 
}