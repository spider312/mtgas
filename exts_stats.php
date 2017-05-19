<html>
 <head>
  <script type="text/javascript" src="js/lib/Flotr2/flotr2.min.js"></script>
  <script type="text/javascript">
function start(ev) {
	document.getElementById('form').addEventListener('submit', function(ev) {
		ev.preventDefault() ;
		let xhr = new XMLHttpRequest();
		let url = 'exts_stats_data.php' ;
		let period = parseInt(this.period.value, 10);
		if ( ( ! isNaN(period) ) && ( period !== 0 ) ) {
			url += '?period=' + period ;
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
					container.style.height = '500px' ;
					Flotr.draw(container, data, {
						bars: {
							show: true,
							stacked: true,
							horizontal: false,
							barWidth: 0.6,
							lineWidth: 1,
							shadowSize: 0
						},
					}) ;
				}
			}
		};
		xhr.send();
	}, false)
}
  </script>
 </head>

 <body onload="start(event)">
  <form id="form">
   <input type="text" name="period" placeholder="period">
   <input type="submit">
  </form>

  <div id="graph"></div>
 </body>
</html>
