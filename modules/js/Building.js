define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  const BUILDING_THEN_MONEY = 1;
  const MONEY_THEN_BUILDING = 2;
  const DIRK = 0;

  return declare("alhambra.buildingTrait", null, {
    constructor(){
      this._notifications.push(
        ['buyBuilding', 1000],
        ['newBuildings', 1500],
        ['newBuildingsForNeutral', 1500]
      );
      this.buildingDeckCounter = null;
      this.buildingSite = [];
      this.selectableBuildings = [];
      this.selectedBuilding = null;
      this.stockZones = [];
    },



    // Create a new building div with argument in deck format
    addBuilding(building, container = null){
      if($('building-tile-' + building.id))
        return;

      building.wallN = building.wall.includes(0);
      building.wallE = building.wall.includes(1);
      building.wallS = building.wall.includes(2);
      building.wallW = building.wall.includes(3);

      this.place('jstpl_building', building, container ?? 'board');
    },


    // Setup stock of a player
    setupStock(player){
      let pId = player.id;

      // Init component
      this.stockZones[pId] = new ebg.zone();
      this.stockZones[pId].create(this, $('stock-' + pId), 95, 95);
      if(pId != this.player_id) {
        this.stockZones[pId].autowidth = true;
      }
      this.stockZones[pId].setFluidWidth();

      // Insert buildings
      player.stock.forEach(building => {
        this.addBuilding(building);
        this.stockZones[pId].placeInZone('building-tile-' + building.id);
      });

      // TODO : useless ??
      //dojo.connect($('player_stock'), 'onclick', this, 'onPlaceOnStock');
    },


    // Add to building site the list of building (deck format) to be placed in the Alhambra
    addToBuildingSiteToPlace(buildings, animate = false){
      debug("Adding building on site to place", buildings);
      buildings.forEach(building => {
        if(!$('building-tile-' + building.id))
          this.addBuilding(building, 'building-spot-' + building.pos);

        dojo.addClass('building-tile-' + building.id, 'bought');
        this.buildingSite[building.pos] = building;

        if(animate){
          this.slideToObjectPos( $('building-tile-' + building.id ), $('building-spot-' + building.pos), -16, -20 ).play();
        } else {
          dojo.style('building-tile-' + building.id , { left:"-16xp", top:"-20px" });
        }
      });
    },



    /*#######################
    #########################
    #### BUILDING POOL ######
    #########################
    #######################*/
    setupBuildingsPool(){
      // Add buildings to pool
      this.addToBuildingSite(this.gamedatas.buildings.buildingsite);
      // Add buildings to place
      this.addToBuildingSiteToPlace(this.gamedatas.buildings.toPlace);

      // Setup deck counter and update
      this.buildingDeckCounter = new ebg.counter();
      this.buildingDeckCounter.create('building-count');
      this.updateBuildingDeckCount();
    },

    updateBuildingDeckCount(){
      this.buildingDeckCounter.setValue(this.gamedatas.buildings.count);
    },


    // Add to building site the list of building
    addToBuildingSite(buildings, animate = false){
      debug("Adding building on site", buildings);
      buildings.forEach(building => {
        this.addBuilding(building, 'building-spot-' + building.pos);
        this.buildingSite[building.pos] = building;

        if(animate){
          let id = 'building-tile-' + building.id;
          dojo.addClass(id, "flipped animate");
          this.placeOnObject(id, "building-deck");
          this.slide(id, 'building-spot-' + building.pos, 800)
          .then(() => {
            dojo.removeClass(id, "flipped");
            setTimeout(() => dojo.removeClass(id, "animate"), 500);
          });
        }
      });
    },



    /*
     * Compute the set of selectable buildings :
     *  - if a building is already selected (BUILDING_THEN_MONEY mode) => only one clickable to be unselected
     *  - if no building selected, check which ones can be built with two cases :
     *     + no cards selected : compute all possibilities
     *     + cards of one color selected : compute possibilities with respect to these cards
     */
    updateSelectableBuildings(){
      dojo.query(".building-spot .building-tile").removeClass('selectable').addClass('unselectable');
      this.selectableBuildings = [];

      if(this.selectedBuilding != null){
        // Highlight this one only
        dojo.query('#building-tile-' + this.selectedBuilding.id).removeClass('unselectable').addClass('selectable selected');
        this.selectableBuildings.push(this.selectedBuilding)
      }
      else {
        // Check cards to see which one can be selected
        let totals = this.getTotalValueByColorInHand();
        for(var type = 1; type <= 4; type++){
          if(this.buildingSite[type] && this.buildingSite[type].cost <= totals[type])
            this.selectableBuildings.push(this.buildingSite[type]);
        }

        this.selectableBuildings.forEach(building => dojo.query("#building-tile-" + building.id).removeClass('unselectable').addClass('selectable') );
      }

      this.updateSelectableCards();
    },


    onBuyBuilding(building){
      if(!this.checkAction('buyBuilding'))
        return;


      // No selection mode => switch building then money cards
      if(this.selectionMode == null){
        if(!this.selectableBuildings.map(b => b.id).includes(building.id)){
          this.showMessage( _("You don't have enough of this currency to build this building"), "error" );
          return;
        }

        this.selectionMode = BUILDING_THEN_MONEY;
        this.selectedBuilding = building;
        this.updateSelectableBuildings();

        this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturnbuilding;
        this.updatePageTitle();
        this.addSecondaryActionButton('btnCancelBuildingChoice', _('Cancel'), () => this.onCancelBuyBuilding());
      }

      // Already in "building then money" mode => unselect selected building if it was clicked
      else if(this.selectionMode == BUILDING_THEN_MONEY){
        if(building.id == this.selectedBuilding.id)
          this.onCancelBuyBuilding();
      }

      // Already in "money then building" mode => check cost and send action
      else if(this.selectionMode == MONEY_THEN_BUILDING){
        this.selectedBuilding = building;
        this.onConfirmBuyBuilding();
      }
    },


    /*
     * Cancel current buying process : unselect everything
     */
    onCancelBuyBuilding(){
      this.selectionMode = null;
      this.selectedBuilding = null;
      dojo.query('.building-spot .building-tile').removeClass('selected');
      this.playerHand.unselectAll();
      this.updateSelectableBuildings();
      dojo.destroy('btnCancelBuildingChoice');
      dojo.destroy('btnConfirmBuyBuilding');
      this.resetPageTitle();
    },


    /*
     * Confirm buy : called when both cards and building are selected
     */
    onConfirmBuyBuilding(){
      // If we are here, cards and building should be selected
      let cardIds = this.playerHand.getSelectedItems().map(item => item.id);
      this.takeAction('buyBuilding', {
        buildingId: this.selectedBuilding.id,
        cardIds: cardIds.join(';'),
      });
    },


    /*
     * Notification received after a building is bought
     */
    notif_buyBuilding(n){
      debug("Notif: buying a building", n);
      let pId = n.args.player_id,
          building = n.args.building;

      // Slide the cards
      n.args.cards.forEach(card => {
        if(this.player_id == pId){
          this.playerHand.removeFromStockById(card.id, 'building-tile-' + building.id);
          this.adaptPlayerHandOverlap();
        } else {
          this.addCard(card, 'alhambra_wrapper');
          this.placeOnObject('card-' + card.id, 'player_name_' + pId);
          this.slideAndDestroy('card-' + card.id, 'building-tile-' + building.id);
        }
      });

      setTimeout(() => this.addToBuildingSiteToPlace([building], true), 500);
    },



    notif_newBuildings(n){
      debug("New buildings", n);
      this.addToBuildingSite(n.args.buildings, true); // True to animate
      this.gamedatas.buildings.count = n.args.count;
      this.updateBuildingDeckCount();
    },


    notif_newBuildingsForNeutral(n){
      debug("New buildings for neutral player", n);
      n.args.buildings.forEach(building => this.addToAlhambra(building, DIRK) );
      this.gamedatas.buildings.count = n.args.count;
      this.updateBuildingDeckCount();
    },



    // Get building for free at the end of the game
    notif_getBuilding: function( notif )
    {
        console.log( 'notif_getBuilding' );
        console.log( notif );

        if( notif.args.player == this.player_id )
        {
            dojo.removeClass( 'building_tile_'+notif.args.building_id, 'building_available' );
            dojo.addClass( 'building_tile_'+notif.args.building_id, 'building_bought' );
            this.makeBuildingDraggable( notif.args.building_id );

            this.slideToObjectPos( $('building_tile_'+notif.args.building_id ), $('buildingsite_'+this.building_to_zone[ notif.args.building_id ] ), -16, -20 ).play();
        }
    },
  });
});
