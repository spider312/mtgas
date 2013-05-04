   <div id="decks" class="section"><!-- ---------- Decks ---------- -->
    <h1><?=__('index.decks');?></h1>
    <!-- Deck list header -->
    <table id="decks_table">
     <thead>
      <tr>
       <th class="first_col leftalign"><?=__('index.decks.name');?></th>
       <th colspan="2" class="rightalign nowrap"><?=__('index.decks.actions');?>
        <button id="deck_edit" title="<?=__('index.decks.actions.edit.title');?>"><?=__('index.decks.actions.edit');?></button>
        <button id="deck_delete" title="<?=__('index.decks.actions.delete.title');?>"><?=__('index.decks.actions.delete');?></button>
        <button id="deck_export" title="<?=__('index.decks.actions.export.title');?>"><?=__('index.decks.actions.export');?></button>
       </th>
      </tr>
     </thead>
     <!-- Deck list -->
     <tbody id="decks_list">
      <tr><td colspan="6"><?=__('index.decks.waiting');?></td></tr>
     </tbody>
     <tfoot>
      <!-- Create -->
      <tr title="<?=__('index.decks.create.title');?>">
       <form id="deck_create" method="get" action="deckbuilder.php">
        <th class="nowrap"><?=__('index.decks.create');?></th>
        <td><input type="text" name="deck" placeholder="<?=__('index.decks.create.name');?>" title="<?=__('index.decks.create.name.title');?>"></td>
        <th class="last_col"><input type="submit" value="<?=__('index.decks.create.create');?>" class="fullwidth"></th>
       </tr>
      </form>
      <!-- Upl -->
      <tr title="<?=__('index.decks.upload.title');?>">
       <th class="nowrap"><?=__('index.decks.upload');?></th>
       <td>
        <form id="upload">
         <input type="file" multiple id="deckfile" name="deckfile" class="fullwidth" accesskey="u" title="<?=__('index.decks.upload.file.title');?>">
      </form>
       </td>
       <th><input type="submit" value="<?=__('index.decks.upload.file.upload');?>" class="fullwidth"></th>
      </tr>
      <!-- Download -->
      <form id="download" action="" method="get">
       <tr title="<?=__('index.decks.download.title');?>">
        <th class="nowrap"><?=__('index.decks.download');?></th>
        <td>
          <input id="deck_url" type="text" name="deck_url" placeholder="<?=__('index.decks.download.placeholder');?>" title="<?=__('index.decks.download.url.title');?>">
        </td>
        <th><input type="submit" value="<?=__('index.decks.download.download');?>" class="fullwidth"></th>
       </tr>
      </form>
     </tfoot>
    </table>
   </div><!-- id="decks" --><!-- ---------- / Decks ---------- -->
