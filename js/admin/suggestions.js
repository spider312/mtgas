function start() { // On page load
	game = {} ;
	game.options = new Options(true) ;
	// Suggestions
	suggest_init('suggest_sealed') ;
	suggest_init('suggest_draft') ;
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
			// Actions
			let action_row = row.insertCell() ;
				// Position switcher (use number input as position changer, by inverting steps buttons behaviour)
			let input = create_input('position', line.position) ;
			input.classList.add('position')
			input.type = 'number' ;
			input.setAttribute("oldvalue", line.position) ; // For step invertion
			input.addEventListener('change', function(ev) {
				// Invert step (down arrow button sets value +1 & vice versa, as it's a position)
				let offset = this.value - this.getAttribute("oldvalue") ;
				this.value = this.value - 2 * offset ;
				// Cap value
				if ( this.value < 0 ) {
					this.value = array.length ;
				} else if ( this.value >= array.length ) {
					this.value = -1 ;
				}
				this.setAttribute("oldvalue",this.value);
				suggest_reorder(line.id, this.value, cluster) ;
			}, false)
			action_row.appendChild(input) ;
				// Del button
			action_row.appendChild(create_button('Del', function(ev) {
				if ( confirm('Delete '+line.name+' ('+line.value+')') ) {
					let xhr = new XMLHttpRequest();
					xhr.addEventListener("load", function(ev) {
						suggest_fill(cluster) ;
					}, false);
					xhr.open("GET", "json/del_config.php?id="+line.id);
					xhr.send();
				}
			})) ;
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
