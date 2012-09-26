function search_players(input, names) {
	var names = eval(input.nextSibling.nextSibling.value) ;
	var td = input.parentNode ;
	var tr = td.parentNode
	var tbody = tr.parentNode
	var table = tbody.parentNode
	var n = 0 ;
	for ( var i = 0 ; i < table.rows.length ; i++ ) {
		var row = table.rows[i] ;
		if ( row == tr )
			row.cells[1].childNodes[3].checked = true ;
		else {
			if ( row.cells[1].childNodes.length > 8 ) {
				var linenames = eval(row.cells[1].childNodes[7].value) ;
				var intersect = linenames.filter(function(n) {
					if(names.indexOf(n) == -1)
						return false;
					return true;
				});
				if ( intersect.length > 0 ) {
					row.cells[1].childNodes[1].checked = true ;
					n++ ;
				} else
					row.cells[1].childNodes[1].checked = false ;
			}
		}
	}
	input.value = n+" found" ;
}
