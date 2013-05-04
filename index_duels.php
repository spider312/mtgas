   <div id="duels" class="section"><!-- ---------- Duels ---------- -->
    <h1><?=__('index.duels');?></h1>
    <div id="duel_join" class="hidden">
     <h2><?=__('index.duels.join');?></h2>
     <table id="games_list">
      <thead>
       <tr>
        <td><?=__('index.duels.id');?></td>
        <td><?=__('index.duels.name');?></td>
        <td><?=__('index.duels.creator');?></td>
        <td><?=__('index.duels.age');?></td>
        <td><?=__('index.duels.inactivity');?></td>
       </tr>
      </thead>
      <tbody id="cell_no" style="display: none ;">
       <tr>
        <td colspan="6"><?=__('index.duels.no');?></td>
       </tr>
      </tbody>
      <tbody id="pending_games">
       <tr>
        <td colspan="6"><?=__('index.duels.wait');?></td>
       </tr>
      </tbody>
     </table>
    </div><!-- id="duel_join" -->
    <h2><?=__('index.duels.create');?></h2>
    <form id="game_create" action="json/game_create.php" method="post"><?/* method=post because of amount of data contained in a deckfile */ ?>
     <input id="creator_nick" type="hidden" name="nick" value="">
     <input id="creator_avatar" type="hidden" name="avatar" value="">
     <input id="creator_deck" type="hidden" name="deck" value="">
     <label title="<?=__('index.duels.create.title');?>">
      <?=__('index.duels.create.name');?>
      <input id="game_name" type="text" name="name" placeholder="<?=__('index.duels.create.name.placeholder');?>" size="64" title="<?=__('index.duels.create.name.title');?>">
      <input class="create" type="submit" value="<?=__('index.duels.create.create');?>" accesskey="c" title="<?=__('index.duels.create.create.title');?>">
     </label>
    </form>
    <div id="duel_view" class="hidden">
     <h2><?=__('index.duels.view');?></h2>
     <table id="running_games_list">
      <thead>
       <tr>
        <td><?=__('index.duels.name');?></td>
        <td><?=__('index.duels.creator');?></td>
        <td colspan="2"><?=__('index.duels.score');?></td>
        <td><?=__('index.duels.joiner');?></td>
        <td><?=__('index.duels.age');?></td>
        <td><?=__('index.duels.inactivity');?></td>
       </tr>
      </thead>
      <tbody id="running_games_no" style="display: none ;">
       <tr>
        <td colspan="7"><?=__('index.duels.view.no');?></td>
       </tr>
      </tbody>
      <tbody id="running_games">
       <tr>
        <td colspan="7"><?=__('index.duels.waiting');?></td>
       </tr>
      </tbody>
     </table>
    </div> <!-- id="duel_view" -->
   </div><!-- id="duels" --><!-- ---------- / Duels ---------- -->
