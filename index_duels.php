   <div id="duels" class="section"><!-- ---------- Duels ---------- -->
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
   </div><!-- id="duels" --><!-- ---------- / Duels ---------- -->
