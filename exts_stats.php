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
		topPadding: 0,
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
	// Query unicity
	if ( form.submit.disabled ) {
		return;
	}
	form.submit.disabled = true ;
	// Cleanup
	let container = document.getElementById('graph') ;
	while ( container.hasChildNodes() ) {
		container.removeChild(container.firstChild) ;
	}
	// Query preparation
	let url = 'json/exts_stats.php' ;
	let urlParams = new URLSearchParams() ;
	let period = parseInt(form.period.value, 10);
	if( ( ! isNaN(period) ) && ( period !== 0 ) ) {
		urlParams.append('period', period);
	}
	let nb = parseInt(form.nb.value, 10);
	if ( ( ! isNaN(nb) ) && ( nb !== 0 ) ) {
		urlParams.append('nb', nb);
	}
	if ( form.percent.checked ) {
		urlParams.append('percent', true);
	}
	url += '?' + urlParams ;
	// Query sending
	let xhr = new XMLHttpRequest();
	xhr.open('GET', url, true);
	let sent = new Date() ;
	xhr.onreadystatechange = function (ev) {
		if (this.readyState == 4) {
			form.submit.disabled = false ;
			form.submit.title = ( new Date() - sent ) + 'ms' ;
			if ( this.status !== 200 ) {
				alert(this.status) ;
			} else {
				try {
					let data = JSON.parse(this.responseText) ;
					container.style.width = '100%' ;
					container.style.height = '90%' ;
					Flotr.draw(container, data, flottrData) ;
				} catch ( e ) {
					alert(e + "\n" + this.responseText) ;
				}
			}
		}
	};
	xhr.send();
}
  </script>
  <style>
.number_2 {
	width: 4em;
}
.number_3 {
	width: 6em;
}
  </style>
 </head>

 <body onload="start(event)">
  <form id="form">
   <label>Days : <input type="number" name="period" placeholder="Days" value="100" size="4" min="1" class="number_3"></label>
   <label>Extensions : <input type="number" name="nb" placeholder="Extensions" value="5" size="2" min="1" class="number_2"></label>
   <label><input type="checkbox" name="percent">Percent</label>
   <input type="submit" name="submit">
  </form>

  <div id="graph"></div>
 </body>
</html>
