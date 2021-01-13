define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("alhambra.buildingTrait", null, {
    constructor(){
      /*
      this._notifications.push(
        ['newHand', 100],
        ['giveCard', 1000],
        ['receiveCard', 1000]
      );
      this._callbackOnCard = null;
      this._selectableCards = [];
      */
      this.buildingDeckCounter = null;
    },


    addCard(card, container){
      this.place('jstpl_moneyCard', card, container);
      // TODO : add tooltip
    },


    /*#######################
    #########################
    #### BUILDING POOL ######
    #########################
    #######################*/
    setupBuildingsPool(){
      // Connect events
      [0,1,2,3].forEach(i => dojo.connect($('money-spot-' + i), 'click', () => this.onBuildingPoolChangeSelection(i) ) );

/*
this.addToBuildingSite( this.gamedatas.buildingsite );
dojo.query( '.buildingsite_place' ).connect( 'onclick', this, 'onClickBuildingSitePlace' );

var i = null;
var building = null;
this.addToBuildingSiteToPlace( this.gamedatas.to_place );
*/
      // Add buildings to pool
      this.addToBuildingSite(this.gamedatas.buildings.buildingsite);
      // Add buildings to place
      //this.addToBuildingSiteToPlace(this.gamedatas.buildings.toPlace);

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
      buildings.forEach((building, i) => {
        this.addBuilding(building, 'building-spot-' + i);

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

        this.zone_to_building[ building.location_arg ] = building;
        this.building_to_zone[ building.id ] = building.location_arg;

        dojo.addClass( 'building_tile_'+tile_id, 'building_available' );
        dojo.connect( $('building_tile_'+tile_id ), 'onclick', this, 'onBuyBuilding' );
*/
      });
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
    },



    // Add to building site the list of building (deck format) to be placed in the Alhambra
    addToBuildingSiteToPlace: function( building_list )
    {
        console.log( 'addToBuildingSiteToPlace' );
        console.log( building_list );

        for( var i in building_list )
        {
            var building = building_list[i];
            this.newBuilding( building );
            var tile_id = building.id;

            this.slideToObjectPos( $('building_tile_'+tile_id ), $('buildingsite_'+building.location_arg ), -16, -20 ).play();

            this.zone_to_building[ building.location_arg ] = building;
            this.building_to_zone[ building.id ] = building.location_arg;

            dojo.addClass( 'building_tile_'+tile_id, 'building_bought' );
            this.makeBuildingDraggable( tile_id );
        }
    },


    onBuyBuilding: function( evt )
    {
        console.log( "onBuyBuilding" );
        console.log( evt );
        evt.preventDefault();

        var tile_id = evt.currentTarget.id.split('_')[2];

        if( dojo.hasClass( evt.currentTarget, 'building_bought' ))
        {
            //Note: do not show this message, otherwise it is displayed when the building is dag n dropped
          //  this.showMessage( _("You already bought this building, and will be able to place it at the end of your turn."), 'error' );
            return ;
        }


        if( ! this.checkAction( 'buyBuilding' ) )
        {   return; }


        // buildingsite_zone_
        var zone_id = this.building_to_zone[ tile_id ]
        var building_money = zone_id;

        var building = this.zone_to_building[ zone_id ];
        console.log( building );
        var building_cost = building.typedetails.cost;

        // Get money cards selected
        var selected = this.playerHand.getSelectedItems();
        if( selected.length === 0 )
        {
            this.showMessage( _("You need to select money cards in your hand to buy a building"), "error" );
        }
        else
        {
            var iSelectedTotalValue = 0;
            var selected_string = '';
            var i = null;
            for( i in selected )
            {
                var card = selected[i];
                var card_value = card.type%10;
                var card_money = Math.floor( card.type/10 );
                iSelectedTotalValue = iSelectedTotalValue + card_value;
                selected_string += ( card.id+';' );

                if( card_money != building_money )
                {
                    if( building_money == 1 )
                    {
                        this.showMessage(_("This building must be buy with couronne only (yellow cards)"), 'error');
                    }
                    else if( building_money == 2 )
                    {
                        this.showMessage(_("This building must be buy with dirham only (green cards)"), 'error');
                    }
                    else if( building_money == 3 )
                    {
                        this.showMessage(_("This building must be buy with dinar only (blue cards)"), 'error');
                    }
                    else if( building_money == 4 )
                    {
                        this.showMessage(_("This building must be buy with ducat only (orange cards)"), 'error');
                    }

                    return;
                }
            }

            if( iSelectedTotalValue >= building_cost )
            {
                this.ajaxcall( "/alhambra/alhambra/buyBuilding.html", { building: building.id, cards: selected_string, lock:true }, this, function( result ) {});
            }
            else
            {
                this.showMessage( _("You need to select enough money card. This building cost: ")+building_cost, "error" );
            }

        }
    },



    onClickBuildingSitePlace: function( evt )
    {
        // DEPRECATED: see "onBuyBuilding"

        console.log( "onClickBuildingSitePlace" );
        console.log( evt );
        evt.preventDefault();

        if( ! this.checkAction( 'buyBuilding' ) )
        {   return; }

        // buildingsite_zone_
        var zone_id = evt.currentTarget.id.substr( 18 );
        console.log( zone_id );
        var building_money = zone_id;

        var building = this.zone_to_building[ zone_id ];
        console.log( building );
        var building_cost = building.typedetails.cost;

        // Get money cards selected
        var selected = this.playerHand.getSelectedItems();
        if( selected.length === 0 )
        {
            this.showMessage( _("You need to select money cards in your hand to buy a building"), "error" );
        }
        else
        {
            var iSelectedTotalValue = 0;
            var selected_string = '';
            var i = null;
            for( i in selected )
            {
                var card = selected[i];
                var card_value = card.type%10;
                var card_money = Math.floor( card.type/10 );
                iSelectedTotalValue = iSelectedTotalValue + card_value;
                selected_string += ( card.id+';' );

                if( card_money != building_money )
                {
                    this.showMessage(_("You must only pay with the money corresponding to this building"), 'error');
                    return;
                }
            }

            if( iSelectedTotalValue >= building_cost )
            {
                this.ajaxcall( "/alhambra/alhambra/buyBuilding.html", { building: building.id, cards: selected_string, lock:true }, this, function( result ) {});
            }
            else
            {
                this.showMessage( _("You need to select enough money card. This building cost: ")+building_cost, "error" );
            }

        }

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
