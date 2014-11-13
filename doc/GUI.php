<?php
include '../lib.php' ;
html_head(
	'In-game keyboard and mouse shortcuts',
	array(
		'style.css'
		, 'index.css'
		, 'options.css'
	)
) ;
?>

 <body>
  <table border="1" class="section">
   <tr><th colspan="2">Steps icons and "next phase" button between battlefields</th></tr>
   <tr>
    <td><strong>Click "next phase" button</strong></td>
    <td>Default action for current step (untap, triggers upkeep, draw, declare attackers, damages), then enter next step</td>
   </tr>
   <tr>
    <td><strong>Click a step icon</strong></td>
    <td>Enter that step</td>
   </tr>
   <tr>
    <td><strong>Double click a step icon</strong></td>
    <td>Default action for that step, then enter next step</td>
   </tr>
   <tr>
    <td>Right click button on steps' right</td>
    <td>Enter previous step</td>
   </tr>
   <tr>
    <td>Ctrl + click button on steps' right</td>
    <td>Enter last step</td>
   </tr>
   <tr>
    <td>Ctrl + right click button on steps' right</td>
    <td>Enter first step</td>
   </tr>


   <tr><th colspan="2">Game keyboard shortcuts</th></tr>
   <tr>
    <td>Any printable character</td>
    <td>Focus to chatbox, then print the character</td>
   </tr>
   <tr>
    <td>Up/down</td>
    <td>Navigate in last sent messages (to easily recall one)</td>
   </tr>
   <tr>
    <td>Ctrl+Enter</td>
    <td>Next turn</td>
   </tr>
   <tr>
    <td>Ctrl+Space</td>
    <td>Next step</td>
   </tr>
   <tr>
    <td>Ctrl+Shift+Space</td>
    <td>Current step's default action, then next step</td>
   </tr>
   <tr>
    <td>Ctrl+i</td>
    <td>Roll a dice</td>
   </tr>
   <tr>
    <td>F9</td>
    <td>Opponent loses 1 life</td>
   </tr>
   <tr>
    <td>F10</td>
    <td>Opponent gains 1 life</td>
   </tr>
   <tr>
    <td>F11</td>
    <td>You lose 1 life</td>
   </tr>
   <tr>
    <td>F12</td>
    <td>You gain 1 life</td>
   </tr>
   <tr>
    <td>Ctrl+d</td>
    <td>Draw</td>
   </tr>
   <tr>
    <td><strong>Ctrl+m</strong></td>
    <td>Shuffle, then first draw / mulligan</td>
   </tr>
   <tr>
    <td>Ctrl+u</td>
    <td>Untap all my permanents</td>
   </tr>
   <tr>
    <td>Ctrl+s</td>
    <td>Shuffle library</td>
   </tr>
   <tr>
    <td><strong>Ctrl+z</strong></td>
    <td>Undo my last draw</td>
   </tr>

   <tr><th colspan="2">Selection keyboard shortcuts - affects each selected card</th></tr>
   <tr>
    <td>Ctrl+Suppr</td>
    <td>Send selected cards to graveyard</td>
   </tr>
   <tr>
    <td>Ctrl+p</td>
    <td>Set power / thoughness for selected cards</td>
   </tr>
   <tr>
    <td>Ctrl+o</td>
    <td>Set counters for selected cards</td>
   </tr>
   <tr>
    <td>Ctrl+n</td>
    <td>Set a note for selected cards</td>
   </tr>
   <tr>
    <td>Alt+d</td>
    <td>Duplicate selected cards/tokens</td>
   </tr>
   <tr>
    <td>Alt+PgUp</td>
    <td>Add a counter on selected cards</td>
   </tr>
   <tr>
    <td>Alt+PgDn</td>
    <td>Remove a counter from selected cards</td>
   </tr>
   <tr>
    <td>Ctrl+Alt+PgUp</td>
    <td>Add a counter and +1/+1 on selected cards</td>
   </tr>
   <tr>
    <td>Ctrl+Alt+PgDn</td>
    <td>Remove a counter and -1/-1 from selected cards (like removing a +1/+1 counter, not like adding a -1/-1 counter)</td>
   </tr>
   <tr>
    <td>Ctrl+up/down/left/right</td>
    <td>Move card on battlefield</td>
   </tr>

   <tr><th colspan="2">Creature boost - replace + by * or - by / to limit effect to current turn</th></tr>
   <tr>
    <td>Ctrl++</td>
    <td>Selected cards gets +1/+0</td>
   </tr>
   <tr>
    <td>Ctrl+-</td>
    <td>Selected cards gets -1/-0</td>
   </tr>
   <tr>
    <td>Alt++</td>
    <td>Selected cards gets +0/+1</td>
   </tr>
   <tr>
    <td>Alt+-</td>
    <td>Selected cards gets -0/-1</td>
   </tr>
   <tr>
    <td>Ctrl+Alt++</td>
    <td>Selected cards gets +1/+1</td>
   </tr>
   <tr>
    <td>Ctrl+Alt+-</td>
    <td>Selected cards gets -1/-1</td>
   </tr>

   <tr><th colspan="2">Mouse actions - Left button</th></tr>
   <tr>
    <td>Click a card on battlefield / hand</td>
    <td>Select only that card</td>
   </tr>
   <tr>
    <td>Shift+click a card on battlefield / hand</td>
    <td>Toggle that card's selection status</td>
   </tr>
   <tr>
    <td>Click on battlefield / hand's background</td>
    <td>Unselect all cards in zone</td>
   </tr>
   <tr>
    <td>Double click on battlefield / hand's background</td>
    <td>Select every card on double clicked line</td>
   </tr>
   <tr>
    <td>Double click library</td>
    <td>Action chosen in options (defaults to "look top N cards of library")</td>
   </tr>
   <tr>
    <td>Double click exile or graveyard</td>
    <td>Open card list for that zone</td>
   </tr>
   <tr>
    <td>Double click a card on BF</td>
    <td>Taps selected cards</td>
   </tr>
   <tr>
    <td>Ctrl+Double click a card on BF</td>
    <td>Declare selected as attacker without tapping
   </tr>
   <tr>
    <td>Double click a card in hand</td>
    <td>Plays selected cards</td>
   </tr>
   <tr>
    <td>Ctrl+Double click a card in hand</td>
    <td>Plays selected cards face down</td>
   </tr>
   <tr>
    <td>Double click a card in a list</td>
    <td>Move card to battlefield (or graveyard for battlefield list)</td>
   </tr>

   <tr><th colspan="2">Mouse actions - Right button</th></tr>
   <tr>
    <td><strong>Right click a card, zone, step, zoom, messages, phase name (for main menu)</strong></td>
    <td>Contextual menu</td>
   </tr>
   <tr>
    <td><strong>Right drag'n'drop from a card to a card or avatar, exile, graveyard or library</strong></td>
    <td>
     Target - Create an arrow from selected cards to droped element
     <ul>
      <li>Default arrow is <strong>yellow</strong> and going away on next <strong>phase</strong></li>
      <li><strong>Shift</strong> while draging makes the arrow <strong>red</strong> and going away on next <strong>step</strong></li>
      <li><strong>Ctrl</strong> while draging makes the arrow <strong>green</strong> and going away on next <strong>turn</strong></li>
      <li><strong>Alt</strong> while draging makes the arrow <strong>blue</strong> and <strong>never</strong> going away</li>
     </ul>
      <p>Creating an arrow deletes previous arrows between the same elements</p>
	  <p>A reminder box is displayed by default for keyboard modifiers. You can disable/enable it in options</p>
    </td>
   </tr>

   <tr><th colspan="2">Mouse actions - Drag'n'drop</th></tr>
   <tr>
    <td>Ctrl+Drag'n'drop cards to library, graveyard or exile</td>
    <td>Move selected card to bottom of that zone</td>
   </tr>
   <tr>
    <td>Ctrl+Drag'n'drop cards to a battlefield</td>
    <td>Plays selected cards face down</td>
   </tr>
   <tr>
    <td>Drag'n'drop cards to a card on battlefield</td>
    <td>Attach selected cards to that one</td>
   </tr>
   <tr>
    <td>Drag'n'drop cards from hand to manapool</td>
    <td>
	 <p>Check if cards are playable with current mana in pool</p>
	 <p>Playability is indicated by mouse cursor when draging over mana pool</p>
	 <p>If playable, droping cards play them, removing used mana from pool</p>
	</td>
   </tr>

   <!--tr><th colspan="2">Debug</th></tr>
   <tr><td colspan="2">A "debug" option adds entries to various menus, and displays log messages (essentially non blocking errors) in history</td></tr>
   <tr>
    <td>Ctrl+l</td>
    <td>Display log messages</td>
   </tr>
   <tr>
    <td>Ctrl+L</td>
    <td>Clear log messages</td>
   </tr>
   <tr>
    <td>Ctrl+k</td>
    <td>Display image cache informations</td>
   </tr-->
  </table>

 </body>
</html>
