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
     g_gamethemeurl + "modules/js/game.js",
     g_gamethemeurl + "modules/js/modal.js",

     g_gamethemeurl + "modules/js/MoneyCard.js",
     g_gamethemeurl + "modules/js/Building.js",
     g_gamethemeurl + "modules/js/Player.js",
 ], function (dojo, declare) {
    return declare("bgagame.alhambra", [
      customgame.game,
      alhambra.moneyCardTrait,
      alhambra.buildingTrait,
      alhambra.playerTrait,
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


      onEnteringStatePlayerTurn(){
        this.makeMoneyPoolSelectable();
        this.updateSelectableBuildings();
      },

      resetPageTitle(){
        this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturngeneric;
        this.updatePageTitle();
      },

      clearPossible(){
        if(this.initialMoneyDlg)
          this.initialMoneyDlg.destroy();

        this.selectedStacks = [];
        dojo.query(".money-spot").removeClass('selected selectable unselectable');
        dojo.query(".stockitem").removeClass('stockitem_selected selectable unselectable');
        this.onCancelBuyBuilding();

        this.inherited(arguments);
      },

   });
});
