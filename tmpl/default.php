<?php defined('_JEXEC') or die; ?>

<div class="flexbannergroup<?php echo $moduleclass_sfx ?>" >
<?php foreach ($displayobjs as $item) : ?>
	<div class="flexbanneritem<?php echo $moduleclass_sfx ?>" >
		<?php echo $item;?>
	</div>
<?php endforeach; ?>	
</div>
