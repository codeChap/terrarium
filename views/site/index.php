<?php
	$this->title = 'Terrarium Monitor';
?>

<div id="app" class="ui grid">
	<div class="ui row">
		<div class="ui column">
			<h3 class="ui inverted header">Flytrap Terrarium</h3>
			<canvas id="myChart"></canvas>
		</div>
	</div>
	<div class="ui row">
		<div class="ui center aligned column">
			<div v-if="watering==0" v-on:click="water(1)" class="ui basic blue button">Water</div>
			<div v-else v-on:click="water(0)" class="ui blue button">Water</div>
			<div v-if="warming==0" v-on:click="warm(1)" class="ui basic orange button">Warm</div>
			<div v-else v-on:click="warm(0)" class="ui orange button">Warm</div>
			<div v-if="kitchening==0" v-on:click="kitchen(1)" class="ui basic green button">Kitchen</div>
			<div v-else v-on:click="kitchen(0)" class="ui green button">Kitchen</div>
			<br>
		</div>
	</div>
	<div class="ui row">
		<div class="ui center aligned column">
			<div><u>Planted</u>: <?= date('l, d M Y', $planted); ?></div>
			<div><u>Expected Germination</u>: <?= date('l, d M Y', strtotime('+15 days', $planted) ); ?></div>
		</div>
	</div>
</div>

<script>
	const temps    = <?= json_encode($temps); ?>;
	const rTemps   = <?= json_encode($rTemps); ?>;
	const humid    = <?= json_encode($humid); ?>;
	const minHumid = <?= json_encode($minHumid); ?>;
	const times    = <?= json_encode($times); ?>;
	const heating  = <?= json_encode($heating); ?>;
	const watering = <?= json_encode($watering); ?>;
</script>