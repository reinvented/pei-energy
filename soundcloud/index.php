<?php
/**
  * index.php
  *
  * A PHP script to retrieve current New Brunswick to Prince Edward Island
  * energy interchange from the New Brunswick System Operator via Pachube
  * and then retrieve tracks from Soundcloud.com and output an HTML5
  * audio element to play them.
  *
  * Icons are Freeware from http://www.iconarchive.com/show/I-like-buttons-icons-by-mazenl77.html
  * 
  * Play icon from http://icons.iconarchive.com/icons/mazenl77/I-like-buttons/128/Style-Play-icon.png
  * Pause icon from http://icons.iconarchive.com/icons/mazenl77/I-like-buttons/128/Style-Pause-icon.png
  * Next icon from http://icons.iconarchive.com/icons/mazenl77/I-like-buttons/64/Style-Next-icon.png
  * Previous icon from http://icons.iconarchive.com/icons/mazenl77/I-like-buttons/64/Style-Previous-icon.png
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
  * @version 0.2, April 3, 2011
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
$bpm = abs($bpm);
/**
  * Retrieve Soundcloud tracks with the BPM figure we got from Pachube.
  */
$tracks = simplexml_load_file("http://api.soundcloud.com/tracks?consumer_key=" . $souncloud_client_id . "&bpm[from]=$bpm&bpm[to]=$bpm&duration[to]=120000");

/**
  * Output HTML page.
  */
?>
<!DOCTYPE html>
<head>
<title>Energy Flow</title>
<link href="index.css" media="all" rel="stylesheet" type="text/css" />
<link rel="apple-touch-icon" href="listen-icon.png" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport" content="maximum-scale=1.6, width=device-width, user-scalable=no, minimum-scale=1.0">
<script>

  audio = new Audio();
  tracks = Array();
  titles = Array();
  current = 1;
  isPlaying = 0;
  debug = false;
  
<?php
  /**
    * Loop through the tracks we pulled from SoundCloud and stuff them in an array.
    * @todo Add the track length and title information so this can be displayed to user.
    */
  $tracknumber = 1;
  foreach($tracks as $key => $track) {
    if ($track->{"stream-url"}) {
      print "  tracks[" . $tracknumber . "] = \"" . $track->{"stream-url"} . "?consumer_key=" . $souncloud_client_id . "\";\n";
      print "  titles[" . $tracknumber . "] = \"<a href='" . $track->{"permalink-url"} . "' target=_BLANK>" . $track->title . "</a>\";\n";
      $tracknumber++;
    }
  }
  print "  maxtracks = " . ($tracknumber - 1) . ";\n";
?>
  
  function playTrack() {
    playpause = document.getElementById('playpause');
    trackinfo = document.getElementById('trackinfo');
    if (isPlaying) {
      if (debug) { console.log("isPlaying was true; pausing track " + current) }
      audio.setAttribute("src", tracks[current]);
      playpause.setAttribute("src","button-play.png");
      audio.pause();
      isPlaying = false;
    }
    else {
      if (debug) { console.log("isPlaying was false; playing track " + current) }
      audio.setAttribute("src", tracks[current]);
      playpause.setAttribute("src","button-loading.png");
      trackinfo.innerHTML = titles[current];
      audio.play();
      isPlaying = true;
    }
  }
  
  function prevSong() {
    current--;
    if (current < 1) {
      current = maxtracks;
    }
    isPlaying = false;
    if (debug) { console.log("Track ended. Incrementing to track " + current) }
    playTrack();
  }
  
  function nextSong() {
    current++;
    if (current > maxtracks) {
      current = 1;
    }
    isPlaying = false;
    if (debug) { console.log("Track ended. Incrementing to track " + current) }
    playTrack();
  }
  
  function showPauseButton() {
    playpause = document.getElementById('playpause');
    playpause.setAttribute("src","button-pause.png");
  }
  
  audio.addEventListener("ended", nextSong, false);
  audio.addEventListener("playing", showPauseButton, false);
  
</script>
</head>
<body>
<h1>New Brunswick to Prince Edward Island Energy Flow</h1>
<div id="buttonwrapper">
<img onclick="javascript:prevSong()" class="buttons" id="previous" src="button-previous.png" width="64" height="64">
<img onclick="javascript:playTrack()" class="buttons" id="playpause" src="button-play.png" width="128" height="128">
<img onclick="javascript:nextSong()" class="buttons" id="next" src="button-next.png" width="64" height="64">
</div>
<div id="trackinfo">&nbsp;</div>
<h2>Current Energy Interchange</h2>
<div id="energy"><?php echo $bpm; ?> megawatts</div>
<img id="pachube" src="http://www.pachube.com/feeds/21695/datastreams/PEI/history.png?w=280&h=120&r=2&s=3&b=true">
</body>
