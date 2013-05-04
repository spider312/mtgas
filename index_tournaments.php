   <div id="tournaments" class="section"><!-- ---------- tournaments ---------- -->
    <h1><?=__('index.tournaments');?></h1>
    <div id="tournament_join" class="hidden">
     <h2><?=__('index.tournaments.join');?></h2>
     <table id="tournament_list">
      <thead>
       <tr>
        <td><?=__('index.tournaments.id');?></td>
        <td><?=__('index.tournaments.type');?></td>
        <td><?=__('index.tournaments.name');?></td>
        <td><?=__('index.tournaments.age');?></td>
        <td><?=__('index.tournaments.slots');?></td>
        <td><?=__('index.tournaments.players');?></td>
        <td><?=__('index.tournaments.view');?></td>
       </tr>
      </thead>
      <tbody id="tournament_no" style="display: none ;">
       <tr>
        <td colspan="7"><?=__('index.tournaments.join.no');?></td>
       </tr>
      </tbody>
      <tbody id="pending_tournaments">
       <tr>
        <td colspan="7"><?=__('index.tournaments.join.wating');?></td>
       </tr>
     </tbody>
     </table>
    </div><!-- id="tournament_join" -->
    <h2><?=__('index.tournaments.create');?></h2>
    <form id="tournament_create" action="tournament/json/create.php" method="post">
     <label title="<?=__('index.tournaments.create.format.title');?>"><?=__('index.tournaments.create.format');?>
      <select id="tournament_type" name="type">
      <optgroup label="<?=__('index.tournaments.create.limited');?>">
        <option value="draft"><?=__('index.tournaments.create.draft');?></option>
        <option value="sealed"><?=__('index.tournaments.create.sealed');?></option>
       </optgroup>
       <optgroup label="<?=__('index.tournaments.create.constructed');?>">
        <option value="vintage"><?=__('index.tournaments.create.vintage');?></option>
        <option value="legacy"><?=__('index.tournaments.create.legacy');?></option>
        <option value="extended"><?=__('index.tournaments.create.modern');?></option>
        <option value="standard"><?=__('index.tournaments.create.standard');?></option>
        <option value="edh"><?=__('index.tournaments.create.edh');?></option>
       </optgroup>
      </select>
     </label>
     <label title="<?=__('index.tournaments.create.name.title');?>"><?=__('index.tournaments.create.name');?>
      <input type="text" id="tournament_name" name="name" placeholder="<?=__('index.tournaments.create.name_placeholder');?>" size="64">
     </label>
     <label title="<?=__('index.tournaments.create.players.title');?>"><?=__('index.tournaments.create.players');?>
      <input type="text" id="tournament_players" name="players" value="2" size="2" maxlength="2">
     </label>
     <div id="limited">
      <label id="tournament_suggestions_label" title="<?=__('index.tournaments.create.suggestions.title');?>"><?=__('index.tournaments.create.suggestions');?>
       <select id="tournament_suggestions"></select>
      </label>
      <label id="tournament_boosters_label" title="<?=__('index.tournaments.create.boosters.title');?>"><?=__('index.tournaments.create.boosters');?>
       <input type="text" id="tournament_boosters" name="boosters" value="" maxlength="128">
       <input type="button" id="boosters_reset" value="<?=__('index.tournaments.create.reset');?>">
      </label>
      <label id="booster_suggestions_label" title="<?=__('index.tournaments.create.booster_add.title');?>"><?=__('index.tournaments.create.booster_add');?>
       <select id="booster_suggestions">
        <option disabled="disabled"><?=__('index.tournaments.create.waiting');?></option>
       </select>
       <input id="booster_add" type="button" value="<?=__('index.tournaments.create.add');?>">
      </label>
     </div><!-- id="limited" -->
     <fieldset id="tournament_options" class="shrinked" title="<?=__('index.tournaments.create.options.title');?>">
      <legend><input type="button" id="tournament_options_toggle" value="+"><?=__('index.tournaments.create.options');?></legend>
      <label title="<?=__('index.tournaments.create.round_number.title');?>"><?=__('index.tournaments.create.round_number');?>
       <input type="text" name="rounds_number" value="0">
      </label>
      <label title="<?=__('index.tournaments.create.round_duration.title');?>"><?=__('index.tournaments.create.round_duration');?>
       <input type="text" name="rounds_duration" value="<?php echo round($round_duration/60) ?>">
      </label>
      <label title="<?=__('index.tournaments.create.clone.title');?>"><?=__('index.tournaments.create.clone');?>
       <input type="checkbox" name="clone_sealed" value="true">
      </label>
     </fieldset>
     <input type="hidden" id="draft_boosters" name="draft_boosters" value="">
     <input type="hidden" id="sealed_boosters" name="sealed_boosters" value="">
     <input class="create" type="submit" value="<?=__('index.tournaments.create.create');?>">
    </form>
    <div id="tournament_view" class="hidden">
     <h2><?=__('index.tournaments.view');?></h2>
     <table id="running_tournament_list">
      <thead>
       <tr>
        <td><?=__('index.tournaments.type');?></td>
        <td><?=__('index.tournaments.name');?></td>
        <td><?=__('index.tournaments.status');?></td>
        <td title="<?=__('index.tournaments.time_left.title');?>"><?=__('index.tournaments.time_left');?></td>
        <td><?=__('index.tournaments.players');?></td>
       </tr>
      </thead>
      <tbody id="running_tournament_no" style="display: none ;">
       <tr>
        <td colspan="6"><?=__('index.tournaments.view.no');?></td>
       </tr>
      </tbody>
      <tbody id="running_tournaments">
       <tr>
        <td colspan="6"><?=__('index.tournaments.view.waiting');?></td>
       </tr>
      </tbody>
     </table>
    </div><!-- id="tournament_view" -->
   </div><!-- id="tournaments" --><!-- ---------- / tournaments ---------- -->
