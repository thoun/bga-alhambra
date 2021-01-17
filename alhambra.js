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
 ], function (dojo, declare) {
    return declare("bgagame.alhambra", [
      customgame.game,
      alhambra.moneyCardTrait,
      alhambra.buildingTrait,
      alhambra.placeBuildingTrait,
      alhambra.playerTrait,
      alhambra.alhambraBoardTrait,
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
        this.inherited(arguments);
      },


      onUpdateActionButtons(){
      },

      onScreenWidthChange() {
        this.adaptPlayerHandOverlap();

/*
TODO
          for( player_id in this.gamedatas.alamb )
          {
              this.adaptAlhambra( player_id );
          }
          */
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


      onScreenWidthChange() {
        this.adaptPlayerHandOverlap();
        /*
         TODO handle Dirk
          for( player_id in this.gamedatas.alamb )
          {
              this.adaptAlhambra( player_id );
          }
        */
      },

   });
});
