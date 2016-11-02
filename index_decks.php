   <div id="decks" class="section"><!-- - - - - - - - - - - Decks - - - - - - - - - - -->
    <h1><?=__('index.decks');?></h1>
    <!-- Deck list header -->
    <table id="decks_table">
     <thead>
      <tr>
       <th class="first_col leftalign"><?=__('index.decks.name');?></th>
       <th colspan="2" class="rightalign nowrap"><?=__('index.decks.actions')."\n";?>
        <button id="deck_edit" title="<?=__('index.decks.actions.edit.title');?>">
		 <?=__('index.decks.actions.edit')."\n";?>
		</button>
        <button id="deck_delete" title="<?=__('index.decks.actions.delete.title');?>">
		 <?=__('index.decks.actions.delete')."\n";?>
		</button>
        <button id="deck_export" title="<?=__('index.decks.actions.export.title');?>">
		 <?=__('index.decks.actions.export')."\n";?>
		</button>
       </th>
      </tr>
     </thead>
     <!-- Deck list -->
     <tbody id="decks_list">
      <tr><td colspan="3"><?=__('index.decks.waiting');?></td></tr>
     </tbody>
     <tfoot>
      <!-- Create -->
      <tr title="<?=__('index.decks.create.title');?>">
       <th class="nowrap"><?=__('index.decks.create');?></th>
       <td>
        <input type="text" name="deck" placeholder="<?=__('index.decks.create.name');?>"
		 title="<?=__('index.decks.create.name.title');?>" form="deck_create">
       </td>
       <th class="last_col">
        <form id="deck_create" method="get" action="deckbuilder.php">
         <input type="submit" value="<?=__('index.decks.create.create');?>" class="fullwidth">
        </form>
       </th>
      </tr>
      <!-- Upl -->
      <tr title="<?=__('index.decks.upload.title');?>">
       <th class="nowrap"><?=__('index.decks.upload');?></th>
       <td>
        <input type="file" multiple id="deckfile" name="deckfile" class="fullwidth" accesskey="u"
         title="<?=__('index.decks.upload.file.title');?>" form="upload">
       </td>
       <th>
        <form id="upload">
         <input type="submit" value="<?=__('index.decks.upload.file.upload');?>" class="fullwidth">
        </form>
       </th>
      </tr>
      <!-- Download -->
      <tr title="<?=__('index.decks.download.title');?>">
       <th class="nowrap">
        <a href="http://img.mogg.fr/scrot/deck_download.png" target="_blank"><?=__('index.decks.download');?></a>
       </th>
       <td>
        <input id="deck_url" type="text" name="deck_url" form="download"
         placeholder="<?=__('index.decks.download.placeholder');?>"
         title="<?=__('index.decks.download.url.title');?>">
       </td>
       <th>
        <form id="download">
		 <input id="deck_download" type="submit" value="<?=__('index.decks.download.download');?>" class="fullwidth">
        </form>
       </th>
      </tr>
     </tfoot>
    </table>
   </div><!-- id="decks" --><!-- - - - - - - - - - - / Decks - - - - - - - - - - -->
