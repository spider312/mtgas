<?php
include 'lib.php' ;
html_head(
	'Main page',
	array(
		'style.css'
		, 'index.css'
		, 'options.css'
	),
	array(
		'lib/jquery.js'
		, 'lib/jquery.cookie.js'
		, '../variables.js.php'
		, 'deck.js'
		, 'html.js'
		, 'index.js'
		, 'math.js'
		, 'tournament/lib.js'
		, 'options.js'
		, 'image.js'
	), 
	array(
		'Canceled tournaments' => 'rss/tournaments.php?status=0', 
		'Pending tournaments' => 'rss/tournaments.php?status=1', 
		'Started tournaments' => 'rss/tournaments.php?status=5', 
		'Ended tournaments' => 'rss/tournaments.php?status=6' 
	)
) ;
?>

 <body>
<?php
html_menu() ;
include 'includes/Browser.php' ;
$browser = new Browser();
if( ( $browser->getBrowser() != Browser::BROWSER_FIREFOX ) || ( $browser->getVersion() < 5 ) ) {
?>
  <div id="browser" class="section">
   <p>In order to enjoy every functionnalities of this game, i <strong>really</strong> encourage you to play it under <a href="http://www.mozilla.com/">Firefox 5.0+</a> (and you are <strong>not</strong>)</p>
   <p>If you want to know why : <a href="doc/browsers.php">browser functionnalities needed by this game</a></p>
  </div>
<?php
}
?>

  <div id="left_col">
   <div id="games" class="section">
    <h1>Duels</h1>

    <div id="duel_join" class="hidden">
     <h2>Join</h2>
     <table id="games_list">
      <thead>
       <tr>
        <td>ID</td>
        <td>Game name</td>
        <td>Creator</td>
        <td>Age</td>
        <td>Inactivity</td>
       </tr>
      </thead>
      <tbody id="cell_no" style="display: none ;">
       <tr>
        <td colspan="6">No pending games</td>
       </tr>
      </tbody>
      <tbody id="pending_games">
       <tr>
        <td colspan="6">Waiting for list of pending games</td>
       </tr>
      </tbody>
     </table>
    </div><!-- id="duel_join" -->

    <h2>Create</h2>
    <form id="game_create" action="json/game_create.php" method="post"><?/* method=post because of amount of data contained in a deckfile */ ?>
     <input id="creator_nick" type="hidden" name="nick" value="">
     <input id="creator_avatar" type="hidden" name="avatar" value="">
     <input id="creator_deck" type="hidden" name="deck" value="">
     <label title="Game's name">
      Name : 
      <input id="game_name" type="text" name="name" placeholder="Game's name" size="64" title="Please specify type, rules applied">
      <input class="create" type="submit" value="Create" accesskey="c" title="Create game">
     </label>
    </form>

    <div id="duel_view" class="hidden">
     <h2>View</h2>
     <table id="running_games_list">
      <thead>
       <tr>
        <td>Game name</td>
        <td>Creator</td>
        <td colspan="2">Score</td>
        <td>Joiner</td>
        <td>Age</td>
        <td>Inactivity</td>
       </tr>
      </thead>
      <tbody id="running_games_no" style="display: none ;">
       <tr>
        <td colspan="7">No running games</td>
       </tr>
      </tbody>
      <tbody id="running_games">
       <tr>
        <td colspan="7">Waiting for list of running games</td>
       </tr>
      </tbody>
     </table>
    </div> <!-- id="duel_view" -->

   </div><!-- id="games" -->

   <div id="decks" class="section">
    <h1>Decks</h1>
    <!-- Deck list header -->
    <table id="decks_table">
     <thead>
      <tr>
       <th class="first_col leftalign">Name</th>
       <th colspan="2" class="rightalign nowrap">Actions : <button id="deck_edit" title="View and change selected deck's list">Edit</button><button id="deck_delete" title="Remove selected deck from list">Delete</button><button id="deck_export" title="Save selected deck as a .mwdeck file">Export</button>
       </th>
      </tr>
     </thead>
     <!-- Deck list -->
     <tbody id="decks_list">
      <tr><td colspan="6">Waiting for preloaded decks to load</td></tr>
     </tbody>
     <tfoot>
      <!-- Create -->
      <tr title="A deck from scratch">
       <form id="deck_create" method="get" action="deckbuilder.php">
        <th class="nowrap">From scratch</th>
        <td><input type="text" name="deck" placeholder="Deck name" title="Name of the deck"></td>
        <th class="last_col"><input type="submit" value="Create" class="fullwidth"></th>
       </tr>
      </form>
      <!-- Load -->
      <tr title="Deck files on your computer">
       <th class="nowrap">From your computer</th>
       <td>
        <form id="upload">
         <input type="file" multiple id="deckfile" name="deckfile" class="fullwidth" accesskey="u" title="Deck files (in MWS (.mwDeck) or Aprentice (.dec) file format). You can select multiple with Ctrl, Shift or mouse selection">
      </form>
       </td>
       <th><input type="submit" value="Import" class="fullwidth"></th>
      </tr>
      <!-- Download -->
      <form id="download" action="" method="get">
       <tr title="A deck file hosted by a web-server, paste the 'export MWS' link on a deck on mtgtop8.com for example">
        <th class="nowrap">From a website</th>
        <td>
          <input id="deck_url" type="text" name="deck_url" placeholder="Deck URL" title="URL of the deck file (in MWS (.mwDeck) or Aprentice (.dec) file format)">
        </td>
        <th><input type="submit" value="Download" class="fullwidth"></th>
       </tr>
      </form>
     </tfoot>
    </table>
   </div><!-- id="decks" -->
  </div><!-- id="left_col" -->

  <div id="right_col">
   <div id="tournaments" class="section">
    <h1>Tournaments</h1>
    <div id="tournament_join" class="hidden">
     <h2>Join</h2>
     <table id="tournament_list">
      <thead>
       <tr>
        <td>ID</td>
        <td>Type</td>
        <td>Game name</td>
        <td>Age</td>
        <td>Slots</td>
        <td>Players</td>
        <td>View</td>
       </tr>
      </thead>
      <tbody id="tournament_no" style="display: none ;">
       <tr>
        <td colspan="7">No pending tournaments</td>
       </tr>
      </tbody>
      <tbody id="pending_tournaments">
       <tr>
        <td colspan="7">Waiting for list of pending tournaments</td>
       </tr>
     </tbody>
     </table>
    </div><!-- id="tournament_join" -->

    <h2>Create</h2>
    <form id="tournament_create" action="tournament/json/create.php" method="post">
     <label title="Tournament's format">Format : 
      <select id="tournament_type" name="type">
      <optgroup label="Limited">
        <option value="draft">Draft</option>
        <option value="sealed">Sealed</option>
       </optgroup>
       <optgroup label="Constructed">
        <option value="vintage">Vintage (T1)</option>
        <option value="legacy">Legacy (T1.5)</option>
        <option value="extended">Extended (T1.X)</option>
        <option value="standard">Standard (T2)</option>
        <option value="edh">EDH</option>
       </optgroup>
      </select>
     </label>
     <label title="Tournament's name">Name : 
      <input type="text" id="tournament_name" name="name" placeholder="Tournament's name" size="64">
     </label>
     <label title="Number of players">Players : 
      <input type="text" id="tournament_players" name="players" value="2" size="2" maxlength="2">
     </label>
     <div id="limited">
      <label id="tournament_suggestions_label" title="Suggestions for tournament's boosters">Boosters suggestions : 
       <select id="tournament_suggestions"></select>
      </label>
      <label id="tournament_boosters_label" title="Tournament's boosters">Boosters for tournament : 
       <input type="text" id="tournament_boosters" name="boosters" value="" maxlength="128">
       <input type="button" id="boosters_reset" value="Reset">
      </label>
      <label id="booster_suggestions_label" title="Add one booster selected in list">Custom booster : 
       <select id="booster_suggestions">
        <option disabled="disabled">Waiting for list</option>
       </select>
       <input id="booster_add" type="button" value="Add">
      </label>
     </div><!-- id="limited" -->
     <fieldset id="tournament_options" class="shrinked" title="Click the + to get more options">
      <legend><input type="button" id="tournament_options_toggle" value="+">Options</legend>
      <label title="Force more rounds even if not enough players">Number of rounds : 
       <input type="text" name="rounds_number" value="0">
      </label>
      <label title="Change duration of rounds, in minutes">Rounds duration : 
       <input type="text" name="rounds_duration" value="<?php echo round($round_duration/60) ?>">
      </label>
      <label title="All players in the sealed will have the same pool">Clone sealed : 
       <input type="checkbox" name="clone_sealed" value="true">
      </label>
     </fieldset>
     <input type="hidden" id="draft_boosters" name="draft_boosters" value="">
     <input type="hidden" id="sealed_boosters" name="sealed_boosters" value="">
     <input class="create" type="submit" value="Create">
    </form>

    <div id="tournament_view" class="hidden">
     <h2>View</h2>
     <table id="running_tournament_list">
      <thead>
       <tr>
        <td>Type</td>
        <td>Name</td>
        <td>Status</td>
        <td title="Before end of current phase, or round">Time left</td>
        <td>Players</td>
       </tr>
      </thead>
      <tbody id="running_tournament_no" style="display: none ;">
       <tr>
        <td colspan="6">No running tournaments</td>
       </tr>
      </tbody>
      <tbody id="running_tournaments">
       <tr>
        <td colspan="6">Waiting for list of running tournaments</td>
       </tr>
      </tbody>
     </table>
    </div><!-- id="tournament_view" -->

   </div><!-- id="tournaments" -->

  </div><!-- id="right_col" -->

  <div id="footer" class="section"><a href="https://github.com/spider312/mtgas">MTGAS developpement version</a>, hosted by <a href="mailto:mtg@spiderou.net">SpideR</a></div>
<?php
if ( is_file('footer.php') )
	include 'footer.php' ;
?>
 </body>
</html>
