var app = new Vue({

	// Element
	el    : '#app',

	// Ready
	mounted() {
		this.init();
	},

	// Functions
	methods : {

		init : () => {

			var ctx = document.getElementById('myChart');
			var myChart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: times,
					datasets: [{
						label           : 'Temperature',
						data            : temps,
						borderWidth     : 2,
						color           : 'rgba(255, 0, 0, 1)',
						backgroundColor : 'rgba(255, 0, 0, 0.3)'
					},{
						label           : 'Required Temperature',
						data            : rTemps,
						borderWidth     : 1,
						fill            : false,
						borderColor     : 'rgba(255, 0, 0, 0.2)'
					},{
						label           : 'Humidity',
						data            : humid,
						borderWidth     : 2,
						color           : 'rgba(0, 255, 255, 1)',
						backgroundColor : 'rgba(0, 255, 255, 0.3)'
					},{
						label           : 'Min Humidity',
						data            : minHumid,
						borderWidth     : 1,
						fill            : false,
						borderColor     : 'rgba(0, 255, 255, 0.2)'
					}]
				}
			});
		}
	}
})