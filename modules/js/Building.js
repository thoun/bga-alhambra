define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  const BUILDING_THEN_MONEY = 1;
  const MONEY_THEN_BUILDING = 2;

  return declare("alhambra.buildingTrait", null, {
    constructor(){
      /*
      this._notifications.push(
        ['newHand', 100],
        ['giveCard', 1000],
        ['receiveCard', 1000]
      );
      */
      this.buildingDeckCounter = null;
      this.buildingSite = [];
      this.selectableBuildings = [];
      this.selectedBuilding = null;
    },



    // Create a new building div with argument in deck format
    addBuilding(building, container){
      if($('building-tile-' + building.id)){
        this.showMessage( "this building tile already exists !!", "error" );
        return;
      }

      building.wallN = building.wall.includes(0);
      building.wallE = building.wall.includes(1);
      building.wallS = building.wall.includes(2);
      building.wallW = building.wall.includes(3);

      this.place('jstpl_building', building, container);
      dojo.connect($('building-tile-' + building.id), 'onclick', () => this.onClickBuilding(building) );
    },


    onClickBuilding(building){
      if(building.location == 'buildingsite')
        this.onBuyBuilding(building);
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
    addToBuildingSite(buildings){
      debug("Adding building on site", buildings);
      buildings.forEach(building => {
        this.addBuilding(building, 'building-spot-' + building.pos);
        this.buildingSite[building.pos] = building;

        /* TODO : ????
        if( building.location == 'alamb')
        {
            // Specific case: must add it immediately to alhambra
            // (happens with Neutral player)
            this.addToAlhambra( building, 0 );
        }
        else
        */
/*
        // Generic case
        this.placeOnObject( $('building_tile_'+tile_id ), 'buildingdeck' );
        this.slideToObject( $('building_tile_'+tile_id ), $('buildingsite_'+building.location_arg ) ).play();
*/
      });
    },


    // Add to building site the list of building (deck format) to be placed in the Alhambra
    addToBuildingSiteToPlace(buildings)
    {
      debug("Adding building on site to place", buildings);
      buildings.forEach(building => {
        this.addBuilding(building, 'building-spot-' + building.pos);
        this.buildingSite[building.pos] = building;

        this.slideToObjectPos( $('building-tile-' + building.id ), $('building-spot-' + building.pos), -16, -20 ).play();
        // TODO : this.makeBuildingDraggable( tile_id );
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


    onCancelBuyBuilding(){
      this.selectionMode = null;
      this.selectedBuilding = null;
      dojo.query('.building-spot .building-tile').removeClass('selected');
      this.updateSelectableBuildings();
      dojo.destroy('btnCancelBuildingChoice');
      dojo.destroy('btnConfirmBuyBuilding');
      this.resetPageTitle();
    },


    onConfirmBuyBuilding(){
      // If we are here, cards and building should be selected
      let cardIds = this.playerHand.getSelectedItems().map(item => item.id);
      this.takeAction('buyBuilding', {
        buildingId: this.selectedBuilding.id,
        cardIds: cardIds.join(';'),
      });
    },




    notif_newBuildings: function( notif )
    {
        console.log( "notif_newBuildings" );
        console.log( notif );
        this.addToBuildingSite( notif.args.buildings );

        $('building_count').innerHTML = 'x'+notif.args.count;
    },

    notif_buyBuilding: function( notif )
    {
        console.log( 'notif_buyBuilding' );
        console.log( notif );

        if( notif.args.player == this.player_id )
        {
            // Remove money from player hand if current player
            for( var i in notif.args.cards )
            {
                var card = notif.args.cards[i];
                console.log( "removing card "+card.id );
                this.playerHand.removeFromStockById( card.id );
            }
            this.adaptPlayerHandOverlap();
        }

        // Mark this building as "bought"
        dojo.removeClass( 'building_tile_'+notif.args.building_id, 'building_available' );
        dojo.addClass( 'building_tile_'+notif.args.building_id, 'building_bought' );
        if( notif.args.player == this.player_id )
        {
            this.makeBuildingDraggable( notif.args.building_id );
        }

        this.slideToObjectPos( $('building_tile_'+notif.args.building_id ), $('buildingsite_'+this.building_to_zone[ notif.args.building_id ] ), -16, -20 ).play();

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

    notif_placeBuilding: function( notif )
    {
        console.log( 'notif_placeBuilding' );
        console.log( notif );

        // In case it comes from the stock ...
        this.alamb_stock[ notif.args.player ].updateDisplay();

        var building = notif.args.building;

        if( notif.args.stock )  // Should add this building to player stock
        {
            if( ! $('building_tile_'+notif.args.building_id ) )
            {
                this.newBuilding( building );
                this.placeOnObject( $('building_tile_'+notif.args.building_id ), $('overall_player_board_'+notif.args.player ) );
            }
            this.alamb_stock[ notif.args.player ].placeInZone( 'building_tile_'+notif.args.building_id );
            if( notif.args.player == this.player_id )
            {
                this.makeBuildingDraggable( notif.args.building_id );   // Make it draggable again (place in zone destroy its capabilities)

                if( notif.args.removed )
                {
                    // We must rebuild freeplaces
                    this.refreshAllFreePlaces();
                }
            }
        }
        else
        {
            // "Normal" case: add it to alhambra
            building.x = notif.args.x;
            building.y = notif.args.y;
            this.addToAlhambra( building, notif.args.player );
        }

        dojo.removeClass( 'building_tile_'+notif.args.building_id, 'building_bought');
    },

  });
});
