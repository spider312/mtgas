   <div id="decks" class="section"><!-- ---------- Decks ---------- -->
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
   </div><!-- id="decks" --><!-- ---------- / Decks ---------- -->
