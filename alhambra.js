/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Alhambra implementation : © Gregory Isabelli
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * alhambra.js
 *
 * alhambra user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

 var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
 var debug = isDebug ? console.info.bind(window.console) : function () { };

 define([
     "dojo", "dojo/_base/declare",
     "ebg/core/gamegui",
     "ebg/counter",
     "ebg/stock",
     "ebg/draggable",
     "ebg/zone",
     "ebg/wrapper",
     g_gamethemeurl + "modules/js/game.js",
     g_gamethemeurl + "modules/js/modal.js",

     g_gamethemeurl + "modules/js/MoneyCard.js",
     g_gamethemeurl + "modules/js/Building.js",
     g_gamethemeurl + "modules/js/PlaceBuilding.js",
     g_gamethemeurl + "modules/js/Player.js",
     g_gamethemeurl + "modules/js/AlhambraBoard.js",
     g_gamethemeurl + "modules/js/Scoring.js",
 ], function (dojo, declare) {
    return declare("bgagame.alhambra", [
      customgame.game,
      alhambra.moneyCardTrait,
      alhambra.buildingTrait,
      alhambra.placeBuildingTrait,
      alhambra.playerTrait,
      alhambra.alhambraBoardTrait,
      alhambra.scoringTrait,
    ], {
      constructor(){
        this.selectionMode = null; // moneyThenBuilding or buildingThenMoney
      },


      /*
       * Setup:
       *	This method set up the game user interface according to current game situation specified in parameters
       *	The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
       *
       * Params :
       *	- mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
       */
      setup(gamedatas) {
      	debug('SETUP', gamedatas);

        this.setupMoneyPool();
        this.setupBuildingsPool();
        this.setupPlayers();
        this.setupTooltips();
        dojo.attr('token-crown', 'data-round', gamedatas.scoreRound);
        this.inherited(arguments);
      },


      onUpdateActionButtons(){
//        this.addPrimaryActionButton("coucou", "Coucou", () => this.notif_scoringRound();
      },

      onScreenWidthChange() {
        this.adaptPlayerHandOverlap();
        Object.values(this.gamedatas.players).forEach(player => this.adaptAlhambra(player.id) );
        if(this.gamedatas.isNeutral){
          this.adaptAlhambra(0);
        }
      },


      onEnteringStatePlayerTurn(args){
        if(!this.isCurrentPlayerActive())
          return;

        this.makeMoneyPoolSelectable();
        this.updateSelectableBuildings();
        this.makeBuildingsDraggable(args.buildings);

        // TODO : move somewhere else
        args.buildingsite.forEach(building => this.connect($('building-tile-' + building.id), 'onclick', () => this.onBuyBuilding(building) ) );
      },


      /*
       * Allow to put back the generic title of current state (useful only for player turn)
       */
      resetPageTitle(){
        if(!this.gamedatas.gamestate.descriptionmyturngeneric)
          return;

        this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturngeneric;
        this.updatePageTitle();
      },

      clearPossible(){
        if(this.initialMoneyDlg)
          this.initialMoneyDlg.destroy();

        this.selectedStacks = [];
        this.onCancelBuyBuilding();
        dojo.query(".money-spot").removeClass('selected selectable unselectable');
        dojo.query(".stockitem").removeClass('stockitem_selected selectable unselectable');
        dojo.query(".building-tile").removeClass('selected selectable unselectable');

        this.disableAllDraging();

        this.inherited(arguments);
      },


      setupTooltips(){
        this.addTooltip( 'player-aid', _("Points wins at each scoring round.<br/>Example: on the second scoring round, the player with the most red buildings wins 9 points and the second wins 2 points."), '' );
        this.addTooltip( 'money-count', _("Number of remaining money cards in the deck"), '' );
        this.addTooltip( 'building-count', _("Number of remaining building tiles (no more tiles = game end)"), '' );
        this.addTooltip( 'token-crown', _("A scoring round will take place at the end of this turn"), '' );

        this.addTooltipToClass( 'stat-1', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("pavillon")} ), '' );
        this.addTooltipToClass( 'stat-2', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("seraglio")} ), '' );
        this.addTooltipToClass( 'stat-3', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("arcades")} ), '' );
        this.addTooltipToClass( 'stat-4', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("chambers")} ), '' );
        this.addTooltipToClass( 'stat-5', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("garden")} ), '' );
        this.addTooltipToClass( 'stat-6', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("pavillon")} ), '' );
        this.addTooltipToClass( 'wall-stat', _("Length of the longest wall"), '' );
        this.addTooltipToClass( 'card-nbr', _("Number of cards in hand"), '' );
      },
   });
});
