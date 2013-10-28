<?php
include 'lib.php' ;
html_head(
	'Deck builder',
	array(
		'style.css'
		, 'deckbuilder.css'
		, 'menu.css'
		, 'options.css'
	),
	array(
		'lib/jquery.js',
		'lib/jquery.cookie.js', 
		'image.js',
		'math.js',
		'menu.js',
		'deck.js',
		'html.js',
		'options.js',
		'../variables.js.php',
		'deckbuilder.js',
		'stats.js',
		'lib/Flotr2/flotr2.min.js'
	)
) ;
?>

 <body onload="load(this, '<?php echo mysql_real_escape_string($_GET['deck']) ; ?>' );">

<?php
html_menu() ;
?>

  <!-- Search form -->
  <div id="search" class="section">
   <h1 class="hideable">Cards</h1>
   <form id="search_cards" method="get">
    <input type="hidden" name="page" value="1">
    <input id="cardname" type="text" name="name" placeholder="name" autocomplete="off" title="Search inside card name, use % as a joker">
    <div id="hidden_form" class="hidden">
     <input id="cardtypes" type="text" name="types" placeholder="supertype - type - subtype" autocomplete="off" title="Search inside card supertypes (legendary, basic, snow), types (creature, land ...) or subtypes (elf, equipment, aura), use % as a joker">
     <input id="cardtext" type="text" name="text" placeholder="text" autocomplete="off" title="Search inside card text, use % as a joker">
     <input id="cardcost" type="text" name="cost" placeholder="cost" autocomplete="off" title="Search inside card cost, use % as a joker">
     <label>Cards per page : 
     <input id="cardlimit" type="text" name="limit" value="20" title="Number of cards to display" size="2" maxlength="2" title="Number of results to display per page"></label>
     <select id="language" name="lang"></select>
    </div>
    <input type="submit" name="submit" value="Search" title="Search all cards matching all criteria">
    <span id="advanced_search" title="Show/hide advanced search parameters"></span>
    <div id="pagination"></div>
   </form>

   <!-- List of all cards found under search form -->
   <table id="cardlist">
    <thead>
     <tr>
      <th>Ext</th>
      <th>Card</th>
     </tr>
    </thead>
    <tbody id="search_result">
    </tbody>
   </table>
  </div>

  <!-- Buttons, between search form's results and deck -->
  <div id="builder_buttons" class="section">
   <ul>
    <li><button id="add_md" accesskey="a" title="Add card to deck"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/1rightarrow.png" alt="=&gt;"></button></li>
    <li><button id="add_sb" accesskey="b" title="Add card to sideboard"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/2rightarrow.png" alt="&gt;&gt;"></button>
    <li><button id="del" accesskey="d" title="Remove card from deck / sideboard"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/1leftarrow.png" alt="&lt;="></button>
    <li><hr>
    <li><button id="up" accesskey="u" title="up card"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/1uparrow.png" alt="&gt;&gt;"></button>
    <li><button id="down" accesskey="j" title="down card"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/1downarrow.png" alt="&lt;="></button>
    <li><hr>
    <li><button id="comment" accesskey="c" title="Add a comment over selected line"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/edit.png" alt="comment"></button>
    <li><hr>
    <li><button id="save" accesskey="s" title="Save deck"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/filesave.png" alt="save"></button>
    <li><button id="saveas" accesskey="v" title="Save deck giving it another name"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/filesaveas.png" alt="save"></button>
   </ul>
  </div>

  <!-- List of cards in current deck, middle of the page -->
  <div id="decksection" class="section">
   <h1 class="hideable">Deck</h1>
   <div>
    <button id="sort" title="Sort deck by type, then converted cost">Sort</button>
    <label title="Add comments to separate each group of type"><input id="sort_comments" type="checkbox" checked="checked">Add types</label>
   </div>
   <select id="deck_language" name="lang"></select>
   <table id="deck">
    <thead>
     <tr>
      <th>.</th>
      <th>Ext</th>
      <th>Card</th>
      <th class="buttonlist">Act.</th>
     </tr>
    </thead>
    <tbody id="maindeck">
    </tbody>
    <tbody id="sideboard">
    </tbody>
   </table>
   <label title="Save as .dec, meaning no extension information is saved"><input id="noextensions" type="checkbox">Don't save chosen extension</label>
  </div>

  <!-- Zoom image on center right of the page -->
  <div id="infos" class="section">
   <h1 class="hideable">Infos</h1>
   <img id="zoom" src="<?php echo $cardimages_default ; ?>back.jpg" title="Left click : zoom in, Right click : zoom out, middle click : open on MCI">
  </div>

   <!-- Deck stats on right -->
  <div id="stats" class="section">
   <h1 class="hideable">Stats</h1>
   <div id="stats_color"></div>
   <div id="stats_cost"></div>
   <div id="stats_typelist"></div>
   <div id="stats_type"></div>
  </div>

  <!-- Logs -->
  <textarea id="log" class="hidden"></textarea>

<?php
if ( is_file('footer.php') )
	include 'footer.php' ;
?>
 </body>
</html>
