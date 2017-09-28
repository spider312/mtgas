/*
admin/config.js : functions managing 'config' SQL table, used to store [multivalues] configurations options
*/

/*
Init config : create table
*/
function config_init(cluster, table_id) {
	let table = document.getElementById(table_id) ;

	// Head : columns headers
	let head = table.createTHead() ;
	node_empty(head) ;
	let head_row = head.insertRow() ;
	head_row.insertCell().appendChild(document.createTextNode('Name')) ;
	head_row.insertCell().appendChild(document.createTextNode('Value')) ;
	head_row.insertCell().appendChild(document.createTextNode('Action')) ;

	// Body : list
	let body = create_element('tbody') ;
	table.appendChild(body) ;
	config_fill(cluster, body) ;

	// Foot : add form
	let foot = table.createTFoot() ;
	node_empty(foot) ;
	let foot_row = foot.insertRow() ;
	let foot_cell = foot_row.insertCell() ;
	foot_cell.colSpan = 3 ;
	let form = create_form() ;
	foot_cell.appendChild(form) ;
	form.appendChild(create_input('name', null, null, 'name')) ;
	form.appendChild(create_input('value', null, null, 'value')) ;
	form.appendChild(create_submit(null, 'Add')) ;
	form.addEventListener('submit', function(ev) {
		let xhr = new XMLHttpRequest();
		xhr.addEventListener("load", function(ev) {
			form.name.value = '' ;
			form.value.value = '' ;
			config_fill(cluster, body) ;
		}, false);
		xhr.open("GET", "json/add_config.php?cluster="+cluster+"&name="+this.name.value+"&value="+this.value.value);
		xhr.send();
		return eventStop(ev) ;
	}, false) ;

}

/*
Fills target (3 columns tbody) with configs from cluster
*/
function config_fill(cluster, target) {
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
			input.title = line.position ;
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
				// Save old value in case
				this.setAttribute("oldvalue",this.value);
				// Send new position to server
				let xhr = new XMLHttpRequest() ;
				xhr.addEventListener("load", function(ev) {
					config_fill(cluster, target) ;
				}, false) ;
				xhr.open("GET", "json/reorder_config.php?id="+line.id+"&to="+this.value) ;
				xhr.send() ;
			}, false)
			action_row.appendChild(input) ;
				// Del button
			action_row.appendChild(create_button('Del', function(ev) {
				if ( confirm('Delete '+line.name+' ('+line.value+')') ) {
					let xhr = new XMLHttpRequest();
					xhr.addEventListener("load", function(ev) {
						config_fill(cluster, target) ;
					}, false);
					xhr.open("GET", "json/del_config.php?id="+line.id);
					xhr.send();
				}
			})) ;
		}) ;
	}, false);
	xhr.open("GET", "json/config.php?config="+cluster);
	xhr.send();
}
