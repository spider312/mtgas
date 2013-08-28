$(function() { // On page load
	player_id = $.cookie(session_id) ;
	// Delays
	games_delay = document.getElementById('past_games_delay') ;
	games_delay.addEventListener('change', function(ev) {
		get_past_games() ;
	}, false) ;
	tournaments_delay = document.getElementById('past_tournaments_delay') ;
	tournaments_delay.addEventListener('change', function(ev) {
		get_past_tournaments() ;
	}, false) ;
	//
	save_restore('past_games_delay') ;
	save_restore('past_tournaments_delay') ;
	get_past_games() ;
	get_past_tournaments() ;
	options = new Options(true) ;
}) ;
function get_past_games() {
	var past_games = document.getElementById('past_games') ;
	var no_past_games = document.getElementById('no_past_games') ;
	$.getJSON('json/suscribed_games.php', {'player_id': player_id, 'games_delay': games_delay.value}, function(data) {
		// Past games
		node_empty(past_games) ; // Remove old lines
		if ( data.suscribed_games.length > 0 ) {
			no_past_games.style.display = 'none' ; // Hide table line "no suscribed games"
			nb_w = 0 ;
			nb_d = 0 ;
			nb_l = 0 ;
			for ( var i = 0 ; i < data.suscribed_games.length ; i++ ) {
				var game = data.suscribed_games[i] ;
				var url = 'play.php?id='+game.id+'&replay=1' ;
				if ( game.creator_id == player_id ) { // Creator
					var opponent_nick = game.joiner_nick ;
					var opponent_avatar = game.joiner_avatar ;
					var my_score = game.creator_score ;
					var opponent_score = game.joiner_score ;
				} else { // Joiner
					var opponent_nick = game.creator_nick ;
					var opponent_avatar = game.creator_avatar ;
					var my_score = game.joiner_score ;
					var opponent_score = game.creator_score ;
				}
				var score = my_score+' - '+opponent_score ;
				var img = create_img(opponent_avatar, opponent_nick+'\'s avatar', opponent_nick+'\'s avatar')
				img.style.maxWidth = '25px' ;
				img.style.maxHeight = '25px' ;
				var opponent = create_a(opponent_nick, url) ;
				opponent.insertBefore(img, opponent.firstChild) ;
				var s = create_a(score, url) ;
				s.classList.add('score') ;
				var tr = create_tr(past_games
					, create_a(game.name, url)
					, opponent
					, create_a(time_disp(game.age), url)
					, s
				) ;
				tr.title = "Replay '"+game.name+"' against "+opponent_nick ;
				if ( my_score > opponent_score ) {
					nb_w++ ;
					s.parentNode.classList.add('yes') ;
				} else {
					if ( opponent_score > my_score ) {
						nb_l++ ;
						s.parentNode.classList.add('no') ;
					} else {
						nb_d++ ;
						s.parentNode.classList.add('little') ;
					}
				}
			}
			var bilan = create_tr(past_games) ;
			var td = create_td(bilan, 'Total') ;
			td.colSpan = 3 ;
			var score = nb_w+' - ' ;
			if ( nb_d > 0 )
				score += nb_d+' - ' ;
			score += nb_l ;
			var td = create_td(bilan, score) ;
			td.classList.add('score') ;
		} else
			no_past_games.style.display = '' ; // Show table line "no suscribed games"
	}) ;
}
function get_past_tournaments() {
	var past_tournaments = document.getElementById('past_tournaments') ;
	var no_past_tournaments = document.getElementById('no_past_tournaments') ;
	$.getJSON('tournament/json/suscribed.php',
		{'player_id': player_id, 'tournaments_delay': tournaments_delay.value},
		function(data) {
			// Displays a list of past && current tournaments
			node_empty(past_tournaments) ;
			if ( data.suscribed_tournaments.length > 0 ) {
				no_past_tournaments.style.display = 'none' ; // Hide table line "no past tournaments"
				var ranks = [] ;
				for ( var i = 0 ; i < data.suscribed_tournaments.length ; i++ ) {
					var tournament = data.suscribed_tournaments[i] ;
					var url = 'tournament/?id='+tournament.id ;
					var tdata = JSON.parse(tournament.data) ;
					if ( iso(tdata.score) && iso(tdata.score[player_id]) )
						var rank = tdata.score[player_id].rank ;
					else
						var rank = 0 ;
					var a_date = create_a(tournament.creation_date, url) ;
					a_date.classList.add('nowrap') ;
					create_tr(past_tournaments
						, create_a(tournament.type, url)
						, create_a(tournament.name, url)
						, a_date
						, create_a(rank+' / '+tournament.min_players, url)
						, create_a(tournament_status(tournament.status), url)
					) ;
					if ( tournament.min_players > 1 ) {
						if ( isn(ranks[rank]) )
							ranks[rank]++ ;
						else
							ranks[rank] = 1 ;
					}
				}
				var str = '' ;
				for ( var i = 1 ; i < ranks.length ; i++ ) {
					str += ranks[i]+' * '+getGetOrdinal(i) ;
					if ( i != ranks.length - 1 )
						str +=', ' ;
				}
				var td = create_td(create_tr(past_tournaments), data.suscribed_tournaments.length+' tournaments, '+str, 5) ;
			} else
				no_past_tournaments.style.display = '' ; // Show table line "no past tournaments"
		}
	) ;
}
