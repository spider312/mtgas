function start() { // On page load
	game = {} ;
	game.options = new Options(true) ;
	// Suggestions
	suggest_init('suggest_sealed') ;
	suggest_init('suggest_draft') ;
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

function suggest_fill(cluster) {
	// Get target table
	let target = document.getElementById(cluster) ;
	if ( target === null ) {
		return alert('Target table for '+cluster+' not found') ;
	}
	let xhr = new XMLHttpRequest();
	xhr.addEventListener("load", function(ev) {
		node_empty(target) ;
		JSON.parse(this.response).forEach((line, index, array) => {
			let row = target.insertRow() ;
			row.insertCell().appendChild(create_text(line.name)) ;
			row.insertCell().appendChild(create_text(line.value)) ;
			let action_row = row.insertCell() ;
			action_row.appendChild(create_button('Del', function(ev) {
				let xhr = new XMLHttpRequest();
				xhr.addEventListener("load", function(ev) {
					suggest_fill(cluster) ;
				}, false);
				xhr.open("GET", "json/del_config.php?id="+line.id);
				xhr.send();
			})) ;
			let up_down_but = create_ul() ;
			up_down_but.classList.add('up_down') ;
			if ( index > 0 ) {
				up_down_but.appendChild(create_li(create_button('▲', function(ev) {
					suggest_reorder(line.id, parseInt(line.position, 10)-1, cluster) ;
				}))) ;
			}
			if ( index < array.length - 1 ) {
				up_down_but.appendChild(create_li(create_button('▼', function(ev) {
					suggest_reorder(line.id, parseInt(line.position, 10)+1, cluster) ;
				}))) ;
			}
			action_row.appendChild(up_down_but) ;
		}) ;
	}, false);
	xhr.open("GET", "json/config.php?config="+target.id);
	xhr.send();
}

function suggest_reorder(from_id, to_position, cluster) {
	let xhr = new XMLHttpRequest() ;
	xhr.addEventListener("load", function(ev) {
		suggest_fill(cluster) ;
	}, false) ;
	xhr.open("GET", "json/reorder_config.php?id="+from_id+"&to="+to_position) ;
	xhr.send() ;
}

function suggest_init(cluster) {
	// Get data to fill it
	suggest_fill(cluster) ;
	// Intercept form submission
	let form = document.getElementById(cluster+'_add') ;
	if ( form === null ) {
		return alert('Form for '+cluster+' not found') ;
	}
	form.addEventListener('submit', function(ev) {
		let xhr = new XMLHttpRequest();
		let form = this ;
		xhr.addEventListener("load", function(ev) {
			form.name.value = '' ;
			form.value.value = '' ;
			suggest_fill(cluster) ;
		}, false);
		xhr.open("GET", "json/add_config.php?cluster="+cluster+"&name="+this.name.value+"&value="+this.value.value);
		xhr.send();
		return eventStop(ev) ;
	}, false) ;
}
