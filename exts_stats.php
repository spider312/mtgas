<html>
 <head>
  <script type="text/javascript" src="js/lib/Flotr2/flotr2.min.js"></script>
  <script type="text/javascript">
"use strict"
function start(ev) {
	let flottrData = {
		legend: {
			backgroundColor: '#D2E8FF' // Light blue
		},
		bars: {
			show: true,
			stacked: true,
			horizontal: false,
			barWidth: 0.8,
			lineWidth: 1,
			shadowSize: 0
		},
		xaxis: {
		},
		yaxis: {
			autoScale: true,
		},
		grid: {
			verticalLines: false,
			horizontalLines: true
		},
	 }
	let form = document.getElementById('form')
	form.addEventListener('submit', function(ev) {
		ev.preventDefault() ;
		let xhr = new XMLHttpRequest();
		let url = 'exts_stats_data.php' ;
		let urlGet = '' ;
		let period = parseInt(this.period.value, 10);
		if ( ( ! isNaN(period) ) && ( period !== 0 ) ) {
			urlGet += 'period=' + period ;
		}
		let nb = parseInt(this.nb.value, 10);
		if ( ( ! isNaN(nb) ) && ( nb !== 0 ) ) {
			if ( urlGet !== '' ) {
				urlGet += '&'
			}
			urlGet += 'nb=' + nb ;
		}
		if ( urlGet !== '' ) {
			url += '?' + urlGet ;
		}
		xhr.open('GET', url, true);
		xhr.onreadystatechange = function (ev) {
			if (this.readyState == 4) {
				if ( this.status !== 200 ) {
					alert(this.status) ;
				} else {
					let data = JSON.parse(this.responseText) ;
					let container = document.getElementById('graph') ;
					container.style.width = '100%' ;
					container.style.height = '90%' ;
					Flotr.draw(container, data, flottrData) ;
				}
			}
		};
		xhr.send();
	}, false) ;
}
  </script>
 </head>

 <body onload="start(event)">
  <form id="form">
   <input type="text" name="period" placeholder="Days">
   <input type="text" name="nb" placeholder="Extensions">
   <input type="submit">
  </form>

  <div id="graph"></div>
 </body>
</html>
