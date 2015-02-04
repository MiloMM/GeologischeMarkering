<div class="container">
    <div class="login-default-box">
        <h1>Choose a picture OR <a action="<?php echo URL; ?>game/play" href="<?php echo URL; ?>game/play/">Play a random one</a></h1>
		<?php foreach ($this->game as $photo) { ?>
		<a href="<?php echo URL; ?>game/play/<?php echo $photo->id; ?>"> <img class="library" src="<?php echo URL . 'public/img/' . $photo->filename; ?>"></a>
		<?php } ?>
         
    </div>
</div>
 