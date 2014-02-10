function deck_stats_cc(cards) {
	if ( cards.length <= 0 ) {
		node_empty(document.getElementById('stats_color')) ;
		node_empty(document.getElementById('stats_cost')) ;
		node_empty(document.getElementById('stats_type')) ;
		node_empty(document.getElementById('stats_provide')) ;
		return false ;
	}
	// Data computing
		// Raw
	var raw_color = {} ;
	var raw_cost = [] ;
	var raw_type = {} ;
	var raw_provide = {} ;
	for ( var i = 0 ; i < cards.length ; i++ ) {
		var card = cards[i] ;
		for ( var j = 0 ; j < card.attrs.color.length ; j++ ) { // Card may be multiple colors
			var color = card.attrs.color[j] ;
			if ( isn(raw_color[color]) )
				raw_color[color] += 1 ;
			else
				raw_color[color] = 1 ;
		}
		var cc = card.attrs.converted_cost ;
		if ( isn(raw_cost[cc]) )
			raw_cost[cc] += 1 ;
		else
			raw_cost[cc] = 1 ;
		for ( var j in card.attrs.types ) { // Subdivision by type
			var type = card.attrs.types[j] ;
			if ( raw_type[type] )
				raw_type[type] += 1 ;
			else
				raw_type[type] = 1 ;
		}
		for ( var j = 0 ; j < card.attrs.provide.length ; j++ ) {
			var color = card.attrs.provide[j] ;
			if ( isn(raw_provide[color]) )
				raw_provide[color] += 1 ;
			else
				raw_provide[color] = 1 ;
		}
	}
		// Color
	var data_color = [] ;
	if ( raw_color['X'] )
		data_color.push({'label': 'Colorless', 'data': [['Colorless', raw_color['X']]], pie: {'explode': 5, 'fillColor': 'Olive', 'color': 'Olive'}}) ;
	if ( raw_color['W'] )
		data_color.push({'label': 'White', 'data': [['White', raw_color['W']]], pie: {'fillColor': 'white', 'color': 'white'}}) ;
	if ( raw_color['U'] )
		data_color.push({'label': 'Blue', 'data': [['Blue', raw_color['U']]], pie: {'fillColor': 'blue', 'color': 'blue'}}) ;
	if ( raw_color['B'] )
		data_color.push({'label': 'Black', 'data': [['Black', raw_color['B']]], pie: {'fillColor': 'black', 'color': 'black'}}) ;
	if ( raw_color['R'] )
		data_color.push({'label': 'Red', 'data': [['Red', raw_color['R']]], pie: {'fillColor': 'red', 'color': 'red'}}) ;
	if ( raw_color['G'] )
		data_color.push({'label': 'Green', 'data': [['Green', raw_color['G']]], pie: {'fillColor': 'green', 'color': 'green'}}) ;
		// Cost
	var data_cost = [] ;
	for ( var i = 0 ; i < raw_cost.length ; i++ )
		data_cost.push([i, raw_cost[i]]) ;
		// Type
	var data_type = [] ;
	for ( var i in raw_type )
		data_type.push({'label': i, 'data': [[i, raw_type[i]]]}) ;
		// Provide
	var data_provide = [] ;
	if ( raw_provide['X'] )
		data_provide.push({'label': 'Colorless', 'data': [['Colorless', raw_provide['X']]], pie: {'explode': 5, 'fillColor': 'Olive', 'color': 'Olive'}}) ;
	if ( raw_provide['W'] )
		data_provide.push({'label': 'White', 'data': [['White', raw_provide['W']]], pie: {'fillColor': 'white', 'color': 'white'}}) ;
	if ( raw_provide['U'] )
		data_provide.push({'label': 'Blue', 'data': [['Blue', raw_provide['U']]], pie: {'fillColor': 'blue', 'color': 'blue'}}) ;
	if ( raw_provide['B'] )
		data_provide.push({'label': 'Black', 'data': [['Black', raw_provide['B']]], pie: {'fillColor': 'black', 'color': 'black'}}) ;
	if ( raw_provide['R'] )
		data_provide.push({'label': 'Red', 'data': [['Red', raw_provide['R']]], pie: {'fillColor': 'red', 'color': 'red'}}) ;
	if ( raw_provide['G'] )
		data_provide.push({'label': 'Green', 'data': [['Green', raw_provide['G']]], pie: {'fillColor': 'green', 'color': 'green'}}) ;

	// Display
		// Color pie
	var div = document.getElementById('stats_color') ;
	node_empty(div) ;
	div.appendChild(create_div('Color pie')) ;
	var content = create_div() ;
	content.style.height = '100px' ;
	div.appendChild(content) ;
	Flotr.draw(content, data_color, {
		HtmlText: false,
		grid: {
			verticalLines: false,
			horizontalLines: false
		},
		xaxis: {
			showLabels: false
		},
		yaxis: {
			showLabels: false
		},
		pie: {
			show: true,
			explode: 2,
			shadowSize: 0,
			labelFormatter: function (total, value) {
				return (100 * value / total).toFixed(0)+'%';
			}
		},
		mouse: {
			track: true,
			trackDecimals: 0
		},
		legend: {
			show: false
		}
	});
		// Mana curve
	if ( data_cost.length > 0 ) {
		var sum = 0 ;
		var nb = 0 ;
		for ( var i = 1 ; i < data_cost.length ; i++ ) {
			var c = data_cost[i] ;
			if ( isn(c[1]) ) {
				sum += c[0] * c[1] ;
				nb += c[1] ;
			}
		}
		var div = document.getElementById('stats_cost') ;
		node_empty(div) ;
		div.appendChild(create_div('Mana curve (mean = '+round(sum/nb, 2)+')')) ;
		var content = create_div() ;
		content.style.height = '100px' ;
		div.appendChild(content) ;
		Flotr.draw(content, [data_cost], {
		   xaxis : {
		     max : data_cost[data_cost.length-1][0] + .5, // Max CC
		     min : -.5,
		     tickDecimals: 0
		   }, 
		   yaxis : {
		     min : 0,
		     tickDecimals: 0
		   }, 
			bars: {
				show: true,
				shadowSize: 0,
				lineWidth: 1,
				barWidth: .9,
				fillColor: 'white',
				fillOpacity: .9,
				color: 'black',
			},
			centered: false,
			mouse: {
				track: true,
				trackDecimals: 0
			}
		}) ;
	}
		// Type pie
	var div = document.getElementById('stats_type') ;
	node_empty(div) ;
	div.appendChild(create_div('Types pie')) ;
	var content = create_div() ;
	content.style.height = '100px' ;
	div.appendChild(content) ;
	Flotr.draw(content, data_type, {
		HtmlText: false,
		grid: {
			verticalLines: false,
			horizontalLines: false
		},
		xaxis: {
			showLabels: false
		},
		yaxis: {
			showLabels: false
		},
		pie: {
			show: true,
			explode: 2,
			shadowSize: 0,
			labelFormatter: function (total, value) {
				return (100 * value / total).toFixed(0)+'%';
			}
		},
		mouse: {
			track: true,
			trackDecimals: 0
		},
		legend: {
			show: false
		}
	});
		// Provide pie
	var div = document.getElementById('stats_provide') ;
	node_empty(div) ;
	div.appendChild(create_div('Provide pie')) ;
	var content = create_div() ;
	content.style.height = '100px' ;
	div.appendChild(content) ;
	Flotr.draw(content, data_provide, {
		HtmlText: false,
		grid: {
			verticalLines: false,
			horizontalLines: false
		},
		xaxis: {
			showLabels: false
		},
		yaxis: {
			showLabels: false
		},
		pie: {
			show: true,
			explode: 2,
			shadowSize: 0,
			labelFormatter: function (total, value) {
				return (100 * value / total).toFixed(0)+'%';
			}
		},
		mouse: {
			track: true,
			trackDecimals: 0
		},
		legend: {
			show: false
		}
	});

}
