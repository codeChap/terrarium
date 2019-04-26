<?php
	$this->title = 'Terrarium Monitor';
?>

<div class="ui grid">
	<div class="ui row">
		<div class="ui column">
			<h3 class="ui inverted header">Flytrap Terrarium</h3>
			<canvas id="myChart" style="width:90vw; height:90vh"></canvas>
		</div>
	</div>
</div>

<script>
	const temps    = <?= json_encode($temps); ?>;
	const rTemps   = <?= json_encode($rTemps); ?>;
	const humid    = <?= json_encode($humid); ?>;
	const minHumid = <?= json_encode($minHumid); ?>;
	const times    = <?= json_encode($times); ?>;
</script>