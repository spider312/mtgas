// Tournament specific methods (shared between index and tournament play page)
function tournament_constructed(type) {
	switch ( type ) {
		case 'draft' :
		case 'sealed' :
			return false ;
		case 'vintage' :
		case 'legacy' :
		case 'modern' :
		case 'extend' :
		case 'standard' :
		case 'edh' :
			return true ;
		default :
			return null ;
	}
}
function tournament_limited(type) {
	switch ( type ) {
		case 'draft' :
		case 'sealed' :
			return true ;
		case 'vintage' :
		case 'legacy' :
		case 'modern' :
		case 'extend' :
		case 'standard' :
		case 'edh' :
			return false ;
		default :
			return null ;
	}
}
function player_status(stat, ready) {
	var result = 'Initializing' ;
	switch ( parseInt(stat) ) {
		case 0 :
			result = 'Waiting' ;
			break;
		case 1 :
			result = 'Redirecting' ;
			break;
		case 2 :
			if ( ready == 1 )
				result = 'Finished drafting' ;
			else
				result = 'Drafting' ;
			break;
		case 3 :
			if ( ready == 1 )
				result = 'Finished building' ;
			else
				result = 'Building' ;
			break;
		case 4 :
			if ( ready == 1 )
				result = 'Finished playing' ;
			else
				result = 'Playing' ;
			break;
		case 5 :
			result = 'Ended' ;
			break;
		case 6 :
			result = 'BYE' ;
			break;
		case 7 :
			result = 'Dropped' ;
			break;
		default : 
			result = 'Unknown status : '+stat ;
	}
	return result ;
}
function tournament_status(stat) {
	var result = 'Initializing' ;
	switch ( parseInt(stat) ) {
		case 0 :
			result = 'Canceled' ;
			break;
		case 1 :
			result = 'Pending' ;
			break;
		case 2 :
			result = 'Waiting players' ;
			break;
		case 3 :
			result = 'Drafting' ;
			break;
		case 4 :
			result = 'Building' ;
			break;
		case 5 :
			result = 'Playing' ;
			break;
		case 6 :
			result = 'Ended' ;
			break;
		default : 
			result = 'Unknown status : '+stat ;
	}
	return result ;
}
function tournament_log_message(line, nick) {
	var msg = 'default message' ;
	switch ( line.type ) {
		case 'create' :
			msg = 'Tournament created by '+nick ;
			break ;
		case 'register' :
			msg = nick+' registered' ;
			break ;
		case 'players' :
			msg = 'Tournament has enough players' ;
			break ;
		case 'spectactor' :
			msg = nick+' joined as spectactor' ;
			break ;
		case 'draft' :
			msg = 'Draft started' ;
			break ;
		case 'build' :
			msg = 'Build started' ;
			break ;
		case 'save' :
			msg = nick+' saved a deck' ;
			break ;
		case 'ready' :
			if ( line.value == '1' )
				msg = nick+' is ready' ;
			else
				msg = nick+' isn\'t ready anymore' ;
			break ;
		case 'start' :
			msg = 'Tournament started' ;
			break ;
		case 'win' :
			msg = nick+' won its match' ;
			break ;
		case 'round' :
			msg = 'Round '+line.value+' started' ;
			break ;
		case 'end' :
			msg = 'Tournament ended' ;
			if ( pid != '' ) // New fashion winner
				msg += ', congratulations to '+nick ;
			else
				if ( line.value != '' ) // Old fashion winner
					msg += ', congratulations to '+line.value ;
				else
					msg += ', can\'t fnid a winner' ;
			break ;
		case 'msg' :
			msg = '<'+nick+'> '+line.value ;
			break ;
		default :
			msg = line.type+' : '+line.value+' (raw)' ;
	}
	return msg ;
}
