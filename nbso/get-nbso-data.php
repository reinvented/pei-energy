<?php
/**
  * get-nbso-data.php
  *
  * A PHP script to retrieve "Net Scheduled Interchange" data from the
  * NB Power public "System Information" web page
  * (http://tso.nbpower.com/Public/en/SystemInformation_realtime.asp) 
  * and return in JSON or XML.
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
  * Set the output format to either "json" or "xml" depending on your needs.
  * Can be set either on command line -- get-nbso-data.php xml or as a parameter
  * if served as a web page -- .../get-nbso-data.php?format=xml
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
  * These are the columns of data present on http://tso.nbpower.com/Public/en/SystemInformation_realtime.asp
  */
$nsbo_columns = array(
                  array("id" => "NB-Load",      "tags" => array("NewBrunswick","NBPower","nb","load")),
                  array("id" => "NB-Demand",    "tags" => array("NewBrunswick","NBPower","nb","demand")),
                  array("id" => "ISO-NE",       "tags" => array("NewEngland","interchange")),
                  array("id" => "NMISA",        "tags" => array("Maine","NorthernMaine","NMISA","interchange")),
                  array("id" => "HYDRO-QUEBEC", "tags" => array("Quebec","HydroQuebec","interchange")),
                  array("id" => "NOVA-SCOTIA",  "tags" => array("NovaScotia","ns","Emera","interchange")),
                  array("id" => "PEI",          "tags" => array("PrinceEdwardIsland","pei","MaritimeElectric","interchange"))
                  );

/**
  * Grab the NB Power System Information web page so that data can be scraped out of it.
  */
$nsbo = "http://tso.nbpower.com/Public/en/SystemInformation_realtime.asp";
$handle = fopen($nsbo,'rb');
$html = stream_get_contents($handle);
fclose($handle);

/**
  * The data points in the HTML returned from NBSO can be pulled out using a regular
  * expression. The lines with data look like this:
  *
  * <td nowrap bgcolor="" align="center">1776</td>
  *
  * So that's what we regex for.
  */
preg_match_all("/<td nowrap bgcolor=\"\" align=\"center\">(.*)<\/td>/",$html,$matches,PREG_SET_ORDER);

/**
  * Take the data points returned in the $matches array and stuff them into an associative array
  * called $nbso_data, keyed to the actual names of the data columns we set in $nsbo_columns.
  */
foreach($matches as $key => $value) {
  $nbso_data[$nsbo_columns[$key]["id"]] = $value[1];
  $nbso_tags[$nsbo_columns[$key]["id"]] = $nsbo_columns[$key]["tags"];
}

/**
  * If the format is XML, then use the PEAR XML_Serializer to turn our result array into XML.
  * Otherwise output it as JSON (using PHP's built-in JSON juju).
  */
if ($format == "xml") {
  header('Content-type: application/xml');
  header('Content-Disposition: attachment; filename="nbso.xml"');

  require_once("XML/Serializer.php");

  $options = array(
    "indent"          => "    ",
    "linebreak"       => "\n",
    "typeHints"       => false,
    "addDecl"         => true,
    "encoding"        => "UTF-8",
    "rootName"        => "nbso",
    "defaultTagName"  => "jurisduction",
    "attributesArray" => "_attributes"
    );

  $serializer = new XML_Serializer($options);
  $result = $serializer->serialize($nbso_data);
  if($result === true) {
   print $serializer->getSerializedData();
  }
}
else if ($format == "json") {
  header('Content-type: application/json');
  header('Content-Disposition: attachment; filename="nbso.json"');

  print json_encode($nbso_data);
}
else if ($format == "pachube") {
  header('Content-type: application/json');
  header('Content-Disposition: attachment; filename="nbso.json"');
 
  $nbso_pachube['title'] = "NB Power System Operator Net Scheduled Interchange";
  $nbso_pachube['description'] = "Data from the NB Power System Operator on Net Scheduled Interchange to and from Nova Scotia, Quebec, New England and Prince Edward Island. NOTE that this is NOT an official project of the NBSO. Negative values are energy sent TO New Brunswick and positive values are energy pulled FROM New Brunswick.";
  $nbso_pachube['feed'] = "http://energy.reinvented.net/pei-energy/nbso/get-nbso-data.php?format=pachube";
  $nbso_pachube['website'] = "http://tso.nbpower.com/Public/en/SystemInformation_realtime.asp";
  $nbso_pachube['email'] = "reinvented+nbso@gmail.com";
  $nbso_pachube['version'] = "2.0.0";  
  $nbso_pachube['updated'] = strftime("%Y-%m-%dT%H:%M:%S");
  $nbso_pachube['location']['name'] = "NB Power";
  $nbso_pachube['location']['lat'] = "45.9658658";
  $nbso_pachube['location']['lon'] = "-66.5975653";
  $nbso_pachube['tags'] = array("energy","interchange","reliabilitycoordinator","nerc");
 
  foreach($nbso_data as $id => $value) {
    $nbso_pachube['datastreams'][] = array("id" => $id,"tags" => $nbso_tags[$id], "current_value" => ($value),"unit" => array("type" => "derivedSI","label" => "Megawatts","symbol" => "MW"));
  }
  
  print json_encode($nbso_pachube);
 
}