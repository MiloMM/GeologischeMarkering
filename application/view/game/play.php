<!DOCTYPE HMTL>
<html>
  <head>
    <style type="text/css">
      html, body, #map-canvas { height: 600px; margin: 10px; padding: 10px;}
    </style>
  </head>
  <img class="ingame" src="<?php echo URL . "public/img/" . $this->game->filename ?>">
  <body>
<div id="map-canvas"></div>
   <div id="data">
  	<button id="guess">Guess</button>
  	<form id="scoreBoard" style="display: none;" method="POST" action="<?php echo URL ?>game/score">
  	 <input id="distance" value=""  name="distance" readonly type="hidden">
  	 <input id="photo_id" value=""  name="photo_id" readonly type="hidden">
  	</form>
   </div>
  </body>

</html>