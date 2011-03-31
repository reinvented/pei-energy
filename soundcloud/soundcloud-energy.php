<?php
/**
  * soundcloud-energy.php
  *
  * A PHP script to retrieve current New Brunswick to Prince Edward Island
  * energy interchange from the New Brunswick System Operator via Pachube
  * and then retrieve and play tracks from Soundcloud.com in iTunes using 
  * the interchange figure as the beats-per-minute search parameter.
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
  * @version 0.1, March 31, 2011
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
  * Make a suitable-for-iTunes playlist M3U file of these tracks.
  */
$m3u = fopen("/tmp/soundcloud-pei-energy.m3u","w");
fwrite($m3u,"#EXTM3U\n");
foreach($tracks as $key => $track) {
  fwrite($m3u,"#EXTINF:-1," . (string)$track->title . " - " . (string)$track->user->username . "\n");
  fwrite($m3u,$track->{"stream-url"} . "?consumer_key=" . $souncloud_client_id . "\n");
}
fclose($m3u);

/**
  * Play the playlist we just made. In this case I'm playing with iTunes from the Mac OS X
  * command line, but in theory any command line-driven music player could be used here.
  */
system("open -a iTunes /tmp/soundcloud-pei-energy.m3u");
