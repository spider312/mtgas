function start() { // On page load
	game = {} ;
	game.options = new Options(true) ;
	// Metagame report loading
	var report_list = document.getElementById('report_list') ;
	report_list.addEventListener('change', function(ev) {
		var select = this ;
		var option = select.options[select.selectedIndex] ;
		var spl = option.value.split('|') ;
		if ( spl.length < 5 ) {
			alert('Not 5 parts in '+el.value) ;
		}
		var form = document.getElementById('stats_create') ;
		var i = 0 ;
		form.name.value = spl[i++] ;
		form.date.value = spl[i++] ;
		form.format.value = spl[i++] ;
		form.exts.value = spl[i++] ;
		form.mask.value = spl[i++] ;
		form.imask.value = spl[i++] ;
	}, false) ;
}
