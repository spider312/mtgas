function start() { // On page load
	game = {} ;
	game.options = new Options(true) ;
	// Suggestions
	config_init('suggest_sealed', 'suggest_sealed') ;
	config_init('suggest_draft', 'suggest_draft') ;
}
