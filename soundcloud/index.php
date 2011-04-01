<?php
/**
  * index.php
  *
  * A PHP script to retrieve current New Brunswick to Prince Edward Island
  * energy interchange from the New Brunswick System Operator via Pachube
  * and then retrieve tracks from Soundcloud.com and output an HTML5
  * audio element to play them.
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
  * @version 0.1, April 1, 2011
  * @link https://github.com/reinvented/pei-energy
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2011, Reinvented Inc.
  * @license http://www.fsf.org/licensing/licenses/gpl.txt GNU Public License
  */

require_once("./api-keys-include.php");

/**
  * Retrieve the current New Brunswick to Prince Edward Island Energy Interchange from
  * Pachube. This number, handily for our purposes, is in the same range as BPM for music.
  */
$energy = simplexml_load_file("http://api.pachube.com/v2/feeds/21695/datastreams/PEI.xml?key=" . $pachube_api_key);
$bpm = (string)$energy->environment->data->current_value;

/**
  * Retrieve Soundcloud tracks with the BPM figure we got from Pachube.
  */
$tracks = simplexml_load_file("http://api.soundcloud.com/tracks?consumer_key=" . $souncloud_client_id . "&bpm[from]=$bpm&bpm[to]=$bpm");

/**
  * Output HTML page.
  */
print "<!DOCTYPE html>\n";
print "<head>\n";
print "<title>New Brunswick to Prince Edward Island Energy Flow as SoundCloud Tracks</title>\n";
print "<link href=\"index.css\" media=\"all\" rel=\"stylesheet\" type=\"text/css\" />\n";
print "<link rel=\"apple-touch-icon\" href=\"listen-icon.png\" />\n";
print "<meta name=\"viewport\" content=\"width=device-width\">\n";
print "</head>\n";
print "<body>\n";
print "<h1>New Brunswick to Prince Edward Island Energy Flow</h1>\n";

/**
  * Output the tracks as an HTML5 audio element.
  */
print "<audio id=\"audioplayer\" controls=\"controls\" autoplay=\"autoplay\">\n";
foreach($tracks as $key => $track) {
  print "<source src=\"" . $track->{"stream-url"} . "?consumer_key=" . $souncloud_client_id . "\" type=\"audio/mpeg\" />\n";
}
print "</audio>\n";

print "<h2>Current Energy Interchange</h2>\n";
print "<div id=\"energy\">" . $bpm . " megawatts</div>";
print "<p>Takes the current <a href=\"http://www.pachube.com/feeds/21695\">New Brunswick to Prince Edward Island energy interchange</a> in megawatts, and uses this to find <a href=\"soundcloud.com\">SoundCloud.com</a> tracks of that beat-per-minute, allowing you to \"hear\" the flow.</p>\n";

print "</body>";
