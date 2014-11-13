function deck_stats_cc(cards) {
	var statsdiv = document.getElementById('stats_graphs') ;
	node_empty(statsdiv) ;
	statsdiv.appendChild(create_text(cards.length+' cards')) ;
	if ( cards.length <= 0 )
		return false ;
	// Data computing
	var raw_color = {} ;
	var raw_mana = {} ;
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
		for ( var j = 0 ; j < card.attrs.manas.length ; j++ ) {
			var mana = card.attrs.manas[j] ;
			for ( var k = 0 ; k < mana.length ; k++ ) { // Hybrid mana
				var color = mana[k] ;
				if ( isn(parseInt(color)) )
					color = 'X' ;
				if ( ['P', 'X'].indexOf(color) > -1 ) // Phyrexian part of mana, X in costs
					continue ;
				if ( isn(raw_mana[color]) )
					raw_mana[color] += 1
				else
					raw_mana[color] = 1 ;
			}
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
		if ( iso(card.attrs.provide) )
			for ( var j = 0 ; j < card.attrs.provide.length ; j++ ) {
				var color = card.attrs.provide[j] ;
				if ( isn(parseInt(color)) ) // For permanents giving several colorless
					color = 'X' ;
				if ( isn(raw_provide[color]) )
					raw_provide[color] += 1 ;
				else
					raw_provide[color] = 1 ;
			}
	}
	// Graph color data
	var colormatch = {
		'X': {'label': 'Colorless', 'pie': {'explode': 5, 'fillColor': 'Olive', 'color':'Olive'}},
		'W': {'label': 'White', 'pie': {'fillColor': 'white', 'color': 'white'}},
		'U': {'label': 'Blue', 'pie': {'fillColor': 'blue', 'color': 'blue'}},
		'B': {'label': 'Black', 'pie': {'fillColor': 'black', 'color': 'black'}},
		'R': {'label': 'Red', 'pie': {'fillColor': 'red', 'color': 'red'}},
		'G': {'label': 'Green', 'pie': {'fillColor': 'green', 'color': 'green'}}
	} ;
	var typematch = {
		'artifact': {'label': 'Artifact', 'pie': {'fillColor': 'blue', 'color': 'blue'}},
		'creature': {'label': 'Creature', 'pie': {'fillColor': 'green', 'color': 'green'}},
		'enchantment': {'label': 'Enchantment', 'pie': {'fillColor': 'white', 'color': 'white'}},
		'instant': {'label': 'Instant', 'pie': {'fillColor': 'black', 'color': 'black'}},
		'land': {'label':  'Land', 'pie': {'fillColor': 'olive', 'color': 'olive'}},
		'planeswalker': {'label': 'Planeswalker', 'pie': {'fillColor': 'violet', 'color': 'violet'}},
		'sorcery': {'label': 'Sorcery', 'pie': {'fillColor': 'red', 'color': 'red'}},
		'tribal': {'label': 'Tribal', 'pie': {'fillColor': 'orange', 'color': 'orange'}}
	} ;
	function data(match, raw) {
		var result = [] ;
		for ( var i in match )
			if ( raw[i] ) {
				var val = raw[i] ;
				var label = match[i].label+' - '+disp_percent(val/cards.length) ;
				result.push({'label': match[i].label,
					'data': [[label, val]],
					'pie': match[i].pie}) ;
			}
		return result ;
	}
	// Graph data
	var data_color = data(colormatch, raw_color) ;
	var data_mana = data(colormatch, raw_mana) ;
	var data_type = data(typematch, raw_type) ;
	var data_provide = data(colormatch, raw_provide) ;
	var data_cost = [] ;
	for ( var i = 0 ; i < raw_cost.length ; i++ )
		data_cost.push([i, raw_cost[i]]) ;
	// Display
	if ( raw_type['land'] ) {
		var active = cards.length - raw_type['land'] ;
		statsdiv.appendChild(create_text(' ('+active+' active, '+raw_type['land']+' land)')) ;
	}
	pie_options = {
		HtmlText: false,
		grid: { verticalLines: false, horizontalLines: false },
		xaxis: { showLabels: false },
		yaxis: { showLabels: false },
		pie: {
			show: true,
			explode: 2,
			shadowSize: 0,
			labelFormatter: function (total, value) {
				return value ;
				var pct = (100 * value / total).toFixed(0)+'%' ;
				//return  pct ;
				//return value+' ('+pct+')';
				//return pct+' ('+value+')';

			}
		},
		mouse: { track: true, trackDecimals: 0 },
		legend: { show: false }
	} ;
	bar_options = {
		xaxis : {
			max : data_cost[data_cost.length-1][0] + .5, // Max CC
			min : -.5,
			tickDecimals: 0
		}, 
		yaxis : { min : 0, tickDecimals: 0 }, 
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
		mouse: { track: true, trackDecimals: 0 }
	} ;
	//pie(statsdiv, 'stats_color', 'Cards colors', data_color) ; // Color pie
	pie(statsdiv, 'stats_mana', 'Mana symbols', data_mana) ; // Mana pie
	pie(statsdiv, 'stats_provide', 'Mana color providers', data_provide) ; // Provide pie
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
		var div = create_div() ;
		statsdiv.appendChild(div) ;
		div.id = 'stats_cost' ;
		var mean = 0 ;
		if ( nb > 0 )
			mean = round(sum/nb, 2) ;
		div.appendChild(create_div('Mana curve (mean = '+mean+')')) ;
		var content = create_div() ;
		content.style.height = '100px' ;
		div.appendChild(content) ;
		Flotr.draw(content, [data_cost], bar_options) ;
	}
	pie(statsdiv, 'stats_type', 'Cards types', data_type) ; // Type pie
	return [raw_color, raw_mana, raw_cost, raw_type, raw_provide] ;

}
function pie(statsdiv, id, name, data) {
	var div = create_div() ;
	div.id = id ;
	div.appendChild(create_div(name)) ;
	var content = create_div() ;
	content.style.height = '100px' ;
	div.appendChild(content) ;
	statsdiv.appendChild(div) ;
	Flotr.draw(content, data, pie_options) ;
}
