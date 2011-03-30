<?php
/**
  * get-nbso-data.php
  *
  * A PHP script to retrieve "Net Scheduled Interchange" data from the
  * New Brunswick System Operator public "System Information" web page
  * (http://www.nbso.ca/Public/en/SystemInformation_realtime.asp) 
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
  */
$format = "json";

/**
  * These are the columns of data present on http://www.nbso.ca/Public/en/SystemInformation_realtime.asp
  */
$nsbo_columns = array("NB-Load","NB-Demand","ISO-NE","NMISA","HYDRO-QUEBEC","NOVA-SCOTIA","PEI");

/**
  * Grab the NBSO System Information web page so that data can be scraped out of it.
  */
$nsbo = "http://www.nbso.ca/Public/en/SystemInformation_realtime.asp";
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
  * called $nbso_data, keyed to the actual names of the data columns we set in $$nsbo_columns.
  */
foreach($matches as $key => $value) {
  $nbso_data[$nsbo_columns[$key]] = $value[1];
}

$nbso_data['unix_timestamp'] = mktime();

/**
  * If the format is XML, then use the PEAR XML_Serializer to turn our result array into XML.
  * Otherwise output it as JSON (using PHP's built-in JSON juju).
  */
if ($format == "xml") {
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
else {
  print json_encode($nbso_data);
}