   <div id="tournaments" class="section"><!-- ---------- tournaments ---------- -->
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
   </div><!-- id="tournaments" --><!-- ---------- / tournaments ---------- -->
