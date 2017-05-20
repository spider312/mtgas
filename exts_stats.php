<html>
 <head>
  <title>Boosters opened on mogg.fr</title>
  <script type="text/javascript" src="js/lib/Flotr2/flotr2.min.js"></script>
  <script type="text/javascript">
"use strict"
const flottrData = {
	legend: {
		backgroundColor: '#D2E8FF' // Light blue
	},
	bars: {
		show: true,
		stacked: true,
		horizontal: false,
		barWidth: 0.8,
		lineWidth: 1,
		shadowSize: 0,
		topPadding: 0.01,
	},
	xaxis: {
		mode: 'time',
		timeUnit: 'day',
	},
	grid: {
		verticalLines: false,
		horizontalLines: true
	},
	mouse: {
		track: true,
		position: 'ne',
		//relative: true,
		trackFormatter: function(data) {
			let date = new Date(parseFloat(data.x)*86400000) ;
			return parseInt(data.y) + ' ' + data.series.label + ' ' + date.toDateString();
		}
	}
}
function start(ev) {
	let form = document.getElementById('form')
	form.addEventListener('submit', function(ev) {
		ev.preventDefault() ;
		getData(this) ;
	}, false) ;
	getData(form) ;
}
function getData(form) {
	let container = document.getElementById('graph') ;
	while ( container.hasChildNodes() )
		container.removeChild(container.firstChild) ;
	let xhr = new XMLHttpRequest();
	let url = 'exts_stats_data.php' ;
	let urlGet = '' ;
	let period = parseInt(form.period.value, 10);
	if ( ( ! isNaN(period) ) && ( period !== 0 ) ) {
		urlGet += 'period=' + period ;
	} else {
		period = 100 ;
	}
	let nb = parseInt(form.nb.value, 10);
	if ( ( ! isNaN(nb) ) && ( nb !== 0 ) ) {
		if ( urlGet !== '' ) {
			urlGet += '&'
		}
		urlGet += 'nb=' + nb ;
	}
	if ( form.percent.checked ) {
		if ( urlGet !== '' ) {
			urlGet += '&'
		}
		urlGet += 'percent=true' ;
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
				container.style.width = '100%' ;
				container.style.height = '90%' ;
				Flotr.draw(container, data, flottrData) ;
			}
		}
	};
	xhr.send();
}
  </script>
 </head>

 <body onload="start(event)">
  <form id="form">
   <input type="text" name="period" placeholder="Days" value="100" size="4">
   <input type="text" name="nb" placeholder="Extensions" value="5" size="2">
   <label>percent <input type="checkbox" name="percent"></label>
   <input type="submit">
  </form>

  <div id="graph"></div>
 </body>
</html>
