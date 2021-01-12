// Alhmabra main javascript

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/zone",
    "ebg/wrapper",
    "ebg/draggable",
    "ebg/popindialog"
],
function (dojo, declare) {
    return declare("bgagame.alhambra", ebg.core.gamegui, {
        constructor: function(){
            console.log('alhambra constructor');
              
            this.initialMoneyDlg = null;
            this.moneyPool = {};
            this.playerHand = null;
            this.toPlace = null;
            this.zone_to_building = {};
            this.alhambra_wrapper = {};
            this.freeplace_index = {};  // XxY => 1 means "there is a free place at this coordinates"
            this.alamb_stock = {};
            this.building_to_zone = {};
        },
        setup: function( gamedatas )
        {
            console.log( "start creating player boards" );
            var player_id = null;
            for( player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                var player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_player_board', {id: player.id } ), player_board_div );
                
                this.alamb_stock[ player_id ] = new ebg.zone();
                this.alamb_stock[ player_id ].create( this, $('stock_'+player_id), 95, 95 );
                if( player_id != this.player_id )
                {
                    this.alamb_stock[ player_id ].autowidth = true; 
                }
                this.alamb_stock[ player_id ].setFluidWidth();
                
                $('wallnbr_'+player_id).innerHTML = player.longest_wall;
                
                if( gamedatas.alamb_stats[ player_id ] )
                {
                    for( var building_type_id in gamedatas.alamb_stats[ player_id ] )
                    {
                        $('btnbr_'+building_type_id+'_'+player_id).innerHTML = gamedatas.alamb_stats[ player_id ][ building_type_id ];
                    }                
                }
            }

            if( gamedatas.neutral_player == 1 )
            {
                // Add neutral player

                dojo.place( this.format_block('jstpl_neutral_player_board', {
                	id:0,
                    color:'000000',
                    name: _('Dirk (neutral player)')
                } ), $('player_boards') );                

                var player_id = 0;
                var player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_player_board', {id: player_id } ), player_board_div );
                
                this.alamb_stock[ player_id ] = new ebg.zone();
                this.alamb_stock[ player_id ].create( this, $('stock_'+player_id), 95, 95 );
                if( player_id != this.player_id )
                {
                    this.alamb_stock[ player_id ].autowidth = true; 
                }
                this.alamb_stock[ player_id ].setFluidWidth();
                
                $('wallnbr_'+player_id).innerHTML = player.longest_wall;
                
                if( gamedatas.alamb_stats[ player_id ] )
                {
                    for( var building_type_id in gamedatas.alamb_stats[ player_id ] )
                    {
                        $('btnbr_'+building_type_id+'_'+player_id).innerHTML = gamedatas.alamb_stats[ player_id ][ building_type_id ];
                    }                
                }

            }

            // Creating buildingsite
            this.addToBuildingSite( this.gamedatas.buildingsite );
            dojo.query( '.buildingsite_place' ).connect( 'onclick', this, 'onClickBuildingSitePlace' );
    
            // Create money pool
            for( var i=1;i<=4; i++ )
            {
                this.moneyPool[i] = new ebg.stock();
                this.moneyPool[i].create( this, $('moneyplace_'+i), 112, 173 );
                this.moneyPool[i].image_items_per_row = 9;
                this.moneyPool[i].setSelectionAppearance('class');    
            }
            this.playerHand = new ebg.stock();
            this.playerHand.create( this, $('player_hand'), 112, 173 );
            this.playerHand.setSelectionAppearance('class');
            this.playerHand.image_items_per_row = 9;
            var pos = 0;
            for( var money_type = 1; money_type <= 4 ; money_type ++ )
            {
                for( var money_value = 1; money_value <= 9; money_value ++ )
                {
                    var money_type_id = parseInt( money_type+''+money_value, 10 );
                    this.moneyPool[1].addItemType( money_type_id, money_type_id, g_gamethemeurl+'img/money.jpg', pos );
                    this.moneyPool[2].addItemType( money_type_id, money_type_id, g_gamethemeurl+'img/money.jpg', pos );
                    this.moneyPool[3].addItemType( money_type_id, money_type_id, g_gamethemeurl+'img/money.jpg', pos );
                    this.moneyPool[4].addItemType( money_type_id, money_type_id, g_gamethemeurl+'img/money.jpg', pos );
                    this.playerHand.addItemType( money_type_id, money_type_id, g_gamethemeurl+'img/money.jpg', pos );
                    pos++;
                }
            }
            this.addToMoneyPool( this.gamedatas.money_pool );
            dojo.connect( this.moneyPool[1], "onChangeSelection", this, "onMoneyPoolChangeSelection" );
            dojo.connect( this.moneyPool[2], "onChangeSelection", this, "onMoneyPoolChangeSelection" );
            dojo.connect( this.moneyPool[3], "onChangeSelection", this, "onMoneyPoolChangeSelection" );
            dojo.connect( this.moneyPool[4], "onChangeSelection", this, "onMoneyPoolChangeSelection" );
            
            $('money_count').innerHTML = 'x'+this.gamedatas.money_count;
            $('building_count').innerHTML = 'x'+this.gamedatas.building_count;

            for( var i in this.gamedatas.card_count )
            {
                $('card_nbr_'+i).innerHTML = 'x'+this.gamedatas.card_count[i];
            }

            // Player hand
            this.addToPlayerHand( this.gamedatas.player_hand, false );

            var i = null;
            var building = null;
            this.addToBuildingSiteToPlace( this.gamedatas.to_place );
            
            // "stock" zone
            for( i in gamedatas.stock )
            {
                building = gamedatas.stock[i];
                player_id = building.location_arg;
                this.newBuilding( building );
                this.alamb_stock[player_id].placeInZone( 'building_tile_'+building.id );
                if( player_id == this.player_id )
                {   this.makeBuildingDraggable( building.id );  }
            }
            dojo.connect($('player_stock'), 'onclick', this, 'onPlaceOnStock');
            
            // Alhambra
            this.freeplace_index = {};
            for( player_id in gamedatas.alamb )
            {
                if( player_id != 0 )
                {
                    this.alhambra_wrapper[ player_id ] = new ebg.wrapper();
                    this.alhambra_wrapper[ player_id ].create( this, $( 'alhambra_' + player_id ), $( 'alhambra_' + player_id +'_inner' ) );
                    this.alhambra_wrapper[ player_id ].item_size = 95;                    
                }
            }
            if( gamedatas.neutral_player == 1 )
            {
                var player_id = 0;
                this.alhambra_wrapper[ player_id ] = new ebg.wrapper();
                this.alhambra_wrapper[ player_id ].create( this, $( 'alhambra_' + player_id ), $( 'alhambra_' + player_id +'_inner' ) );
                this.alhambra_wrapper[ player_id ].item_size = 95;
            }

            for( player_id in gamedatas.alamb )
            {
                for( i in gamedatas.alamb[ player_id ] )
                {
                    building = gamedatas.alamb[ player_id ][ i ];
                    this.addToAlhambra( building, player_id );
                }
            }            
            
            dojo.query( '.building_last_placed' ).removeClass( 'building_last_placed' );    // Do not mark any building at setup time
            
            if( gamedatas.is_scoring_round != '0' )
            {   dojo.style( $('scoring_round_alert'), 'display', 'block' );  }
            
            this.addTooltip( 'player_aid', _("Points wins at each scoring round.<br/>Example: on the second scoring round, the player with the most red buildings wins 9 points and the second wins 2 points."), '' );
            this.addTooltip( 'money_count', _("Number of remaining money cards in the deck"), '' );
            this.addTooltip( 'building_count', _("Number of remaining building tiles (no more tiles = game end)"), '' );
            
            this.addTooltipToClass( 'stat_1', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("pavillon")} ), '' );
            this.addTooltipToClass( 'stat_2', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("seraglio")} ), '' );
            this.addTooltipToClass( 'stat_3', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("arcades")} ), '' );
            this.addTooltipToClass( 'stat_4', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("chambers")} ), '' );
            this.addTooltipToClass( 'stat_5', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("garden")} ), '' );
            this.addTooltipToClass( 'stat_6', dojo.string.substitute( _("Number of ${building} in this player palace"), {building: _("pavillon")} ), '' );
            this.addTooltipToClass( 'wall_stat', _("Length of the longest wall"), '' );
            this.addTooltipToClass( 'card_nbr', _("Number of cards in hand"), '' );

            this.setupNotifications();

            for( player_id in gamedatas.alamb )
            {
                this.adaptAlhambra( player_id );
            }
        },
        
        //////////////////////////////////////////////////////////
        //// UI events
        
        onAcceptMoney: function( evt )
        {       
            evt.preventDefault();
            
            if( ! this.checkAction( 'acceptMoney' ) )
            {   return; }
            
            this.ajaxcall( "/alhambra/alhambra/acceptMoney.html", { lock:true }, this, function( result ) {
                if( $('moneyDlgContent') )
                {   dojo.destroy('moneyDlgContent');    }
                if( this.initialMoneyDlg )
                {   this.initialMoneyDlg.hide();    }            
            }); 
        },
         
        onMoneyPoolChangeSelection: function()
        {
            console.log( "onMoneyPoolChangeSelection" );

            var selected = this.moneyPool[1].getSelectedItems().concat( this.moneyPool[2].getSelectedItems() ).concat( this.moneyPool[3].getSelectedItems() ).concat( this.moneyPool[4].getSelectedItems() );
            var unselected = this.moneyPool[1].getUnselectedItems().concat( this.moneyPool[2].getUnselectedItems() ).concat( this.moneyPool[3].getUnselectedItems() ).concat( this.moneyPool[4].getUnselectedItems() );
            console.log( selected );
            console.log( unselected );

            if( selected.length > 0 )
            {
                if( this.checkAction( 'takeMoney' ) )
                {
                
                    var bTakeCardsNow = false;
                    var iSelectedTotalValue = 0;
                    var iNonSelectedMinValue = 9;
                    var card_value = null;
                    var nbr_card_selected = selected.length;
                    var i = null;
                    var selected_string = '';
                                        
                    for( i in unselected )
                    {
                        card_value = unselected[i].type%10;
                        iNonSelectedMinValue = Math.min( iNonSelectedMinValue, card_value );
                    }
                    
                    for( i in selected )
                    {
                        card_value = selected[i].type%10;
                        iSelectedTotalValue = iSelectedTotalValue + card_value;
                        selected_string += ( selected[i].id+';' );
                    }
                    
                    console.log( "selected value = "+iSelectedTotalValue );
                    console.log( "non selected min value = "+iNonSelectedMinValue );
                    
                    // Cases possibles:
                    // _ take 1 card >5 => take it immediately
                    // _ take several cards <5 with possibility to take another one => wait
                    // _ take cards with no possibility to keep below 5 => take them immediately
                    
                    if( nbr_card_selected == 1 )
                    {
                        if( iSelectedTotalValue >= 5 )
                        {   bTakeCardsNow = true;  }
                        else if( iSelectedTotalValue+iNonSelectedMinValue > 5 )
                        {   bTakeCardsNow = true; }
                    }
                    else if( nbr_card_selected > 1 )
                    {
                        if( iSelectedTotalValue > 5 )
                        {   
                            this.showMessage( _("If you take several money cards their total value should not exceed 5"), "error" );
                            this.moneyPool[1].unselectAll();
                            this.moneyPool[2].unselectAll();
                            this.moneyPool[3].unselectAll();
                            this.moneyPool[4].unselectAll();
                        }
                        else
                        {
                            if( iSelectedTotalValue+iNonSelectedMinValue > 5 )
                            {   bTakeCardsNow = true; }
                        }                    
                    }
                                    
                    if( bTakeCardsNow )
                    {
                        console.log( "Take card(s) now" );
                        
                        this.ajaxcall( "/alhambra/alhambra/takeMoney.html", { cards: selected_string, lock:true }, this, function( result ) {}); 
                    }
                }
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

        onStartDraggingBuilding: function()
        {
            dojo.addClass( 'ebd-body', 'alhambra_drag_in_progress' );
        },
       
        onEndDraggingBuilding: function( item_id, left, top, bDragged )
        {
            console.log( "onEndDraggingBuilding "+item_id+" "+left+","+top );

            dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress' );
            dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress_from_stock' );

            // building_tile_<X>
            var building_id = item_id.substr( 14 );
            var building_div = $('building_tile_'+building_id);

            if( ! bDragged )
            {
                return ;    // (no drag but a click instead)
            }


            var item_pos = dojo.position( item_id );
            var item_size = this.alhambra_wrapper[ this.player_id ].item_size;
            var item_center_x = item_pos.x+item_size/2;
            var item_center_y = item_pos.y+item_size/2;
            
            // See first if we are on the player "stock"
            var stock_pos = dojo.position( "alhambra_stock_current_player" );
            if( item_center_x >= stock_pos.x && item_center_x <= (stock_pos.x+stock_pos.w) &&
                item_center_y >= stock_pos.y && item_center_y <= (stock_pos.y+stock_pos.h) )
            {
                console.log( "on stock !" );

                if( building_div.parentNode.id == 'board' )
                {
                    this.ajaxcall( "/alhambra/alhambra/placeBuilding.html", { building: building_id, lock:true }, this,
                        function( result ) {},
                        function( is_error )
                        {
                            this.alamb_stock[ this.player_id ].updateDisplay();
                        });  
                }
                else
                {
                    // It comes from the stock already!
                    this.alamb_stock[ this.player_id ].updateDisplay();
                }
                
            }
            else
            {
                // Compare this item position to player's alhambra fountain position
                var alamb_base_building_id = this.gamedatas.alamb_base[ this.player_id ];
                var alamb_pos = dojo.position( $('building_tile_'+alamb_base_building_id) );
                
                var x = Math.round( ( item_pos.x-alamb_pos.x ) / item_size );
                var y = Math.round( ( item_pos.y-alamb_pos.y ) / item_size );
                console.log( x+','+y );

                var url = '';                
                if( building_div.parentNode.id == 'board' )
                {
                    // Placing a building you just bought in the alhambra
                    url = "/alhambra/alhambra/placeBuilding.html";
                }
                else
                {
                    url = "/alhambra/alhambra/transformAlhambraPlace.html";
                }
                
                // Launch an ajaxcall to place this building here
                this.ajaxcall( url, { building: building_id, x:x, y:y, lock:true }, this,
                    function( result ) {},
                    function( is_error )
                    {
                        if( is_error )
                        {
                            // Return element to its original location
                            
                            if( $('building_tile_'+building_id).parentNode.id == 'board' ) //////// TODO: FAUX: on ne delete pas building_to_zone donc il peut encore y être même si il vient du stock => faut une méthode plus reliable pour savoir si il vient du stock!!!
                            {
                                this.slideToObjectPos( $('building_tile_'+building_id ), $('buildingsite_'+this.building_to_zone[building_id] ), -16, -20 ).play();
                            }
                            else
                            {
                                // Return it to the stock
                                this.alamb_stock[ this.player_id ].updateDisplay();
                            }
                        }
                    }); 
            }
        },

        onPlaceOnStock: function( evt )
        {
            dojo.stopEvent( evt );

            // Click on stock
            var selected = dojo.query('.selected');
            if( selected.length == 0 )
            {
                this.showMessage( _("You must select a building you bought first"), 'error' );
                return ;
            }

            selected = selected[0];

            var building_id = ( selected.id.split('_')[2]);
            var building_div = $('building_tile_'+building_id);


            this.ajaxcall( "/alhambra/alhambra/placeBuilding.html", { building: building_id, lock:true }, this,
            function( result ) {},
            function( is_error )
            {
                this.alamb_stock[ this.player_id ].updateDisplay();
            });  

            dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress' );
            dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress_from_stock' );
            dojo.query('.selected').removeClass('selected');            
        },

        onClickFreePlace: function( evt )
        {
            dojo.stopEvent( evt );

            // Click on free place
            var selected = dojo.query('.selected');
            if( selected.length == 0 )
            {
                this.showMessage( _("You must select a building you bought first"), 'error' );
                return ;
            }

            selected = selected[0];

            var building_id = ( selected.id.split('_')[2]);
            var building_div = $('building_tile_'+building_id);

            var parts = evt.currentTarget.id.split('_');
            var x = parts[3];
            var y = parts[4];

            var url = '';
            if( building_div.parentNode.id == 'board' )
            {
                // Placing a building you just bought in the alhambra
                url = "/alhambra/alhambra/placeBuilding.html";
            }
            else
            {
                url = "/alhambra/alhambra/transformAlhambraPlace.html";
            }
            
            // Launch an ajaxcall to place this building here
            this.ajaxcall( url, { building: building_id, x:x, y:y, lock:true }, this,
                function( result ) {},
                function( is_error )
                {
                });         
                
            dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress' );
            dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress_from_stock' );
            dojo.query('.selected').removeClass('selected');
        },
        
        // Player wants to remove a building from the Alhambra
        onRemoveBuilding: function( evt )
        {
            evt.preventDefault();
            
            if( ! this.checkAction( 'transformAlhambra' ) )
            {   return; }
            
            console.log( 'onRemoveBuilding' );
            var building_id = evt.currentTarget.id.substr( 3 );
            console.log( 'building = '+building_id );
            
            dojo.style( 'rm_'+building_id, 'display', 'none' );

            this.ajaxcall( "/alhambra/alhambra/transformAlhambraRemove.html", { remove: building_id, lock:true }, this,
                function( result ) {}, function( is_error ){});  
        },
        
        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function( stateName, args )
        {
           console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            case 'initialMoney':
                if( this.isCurrentPlayerActive() )
                {
                    this.showInitialMoneyDialog( args.args );               
                }
                break;
                
            case 'playerTurn':
                this.moneyPool[1].unselectAll();
                this.moneyPool[2].unselectAll();
                this.moneyPool[3].unselectAll();
                this.moneyPool[4].unselectAll();
                dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress' );
                dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress_from_stock' );    
                break;
                
            case 'dummmy':
                break;
            }
        },
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            case 'initialMoney':
                if( $('moneyDlgContent') )
                {   dojo.destroy('moneyDlgContent');    }
                if( this.initialMoneyDlg )
                {   this.initialMoneyDlg.hide();    }

                break;
            case 'dummy':
                break;
            }                
        }, 

        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                case 'placeBuildings':
                    if( this.gamedatas.neutral_player==1 )
                    {
                        this.addActionButton( 'giveDirk', _('or give them to neutral player'), 'onGiveToNeutral' );
                    }
                    break;
                }
            }
        },
 
 
        onGiveToNeutral: function()
        {
            this.confirmationDialog( _("Are you sure you want to give away these buildings to Neutral player?"), dojo.hitch( this, function(){

                this.ajaxcall( "/alhambra/alhambra/giveneutral.html", { lock:true }, this, function( result ) {} );
            } ) );
        },

        //////////////////////////////////////////////////////////
        //// UI adaptations
        
        showInitialMoneyDialog: function( player_to_cards )
        {
            console.log( "showInitialMoneyDialog" );
            console.log( player_to_cards );
            
            
            this.initialMoneyDlg = new ebg.popindialog();
            this.initialMoneyDlg.create('money_dialog' );
            this.initialMoneyDlg.setTitle( _("Initial money for players") );
            
            var html = '<div id="moneyDlgContent">';
            
            var i;
            for( var player_id in player_to_cards )
            {
                html += '<h3>'+this.gamedatas.players[ player_id ].name+'</h3>';
                for( i in player_to_cards[ player_id ] )
                {
                    var card = player_to_cards[ player_id ][i];
                    var back_y = -(card.type-1)*173;
                    var back_x = -(card.type_arg-1)*112;
                    html += '<div class="moneycard initialMoney" style="background-position: '+back_x+'px '+back_y+'px"></div>';
                }
                html += '<br class="clear" />';
            }

            html += "<p style='text-align:center'><a href='#' id='closeMoneyDlg_btn' class='bgabutton bgabutton_blue' onclick='return false;'>&nbsp;" + _('ok') + "&nbsp;</a></p>";
            html += "</div>";   // moneyDlgContent
            
            this.initialMoneyDlg.setContent( html );
            this.initialMoneyDlg.show();
            this.initialMoneyDlg.hideCloseIcon();

            dojo.connect( $('closeMoneyDlg_btn'), 'onclick', this, "onAcceptMoney" );

        },
        
        // Create a new building div with argument in deck format
        newBuilding: function( building )
        {
            console.log( "newBuilding" );

            var tile_id = building.id;

            if( $('building_tile_'+tile_id ) )
            {
                this.showMessage( "this building tile already exists !!", "error" );
            }

            var additional_style = '';
            if( building.typedetails.type == 0 )    // Fountain
            {
                additional_style = 'fountain';
            }

            var back_x = -building.typedetails.img.x*100;
            var back_y = -building.typedetails.img.y*100;
            dojo.place( this.format_block('jstpl_building_tile',
                    {   id: tile_id,
                        back_x: back_x,
                        back_y: back_y,
                        additional_style: additional_style
                    } ), 'board' ); 
                    

            
            // Add surface & remove icon
            dojo.place( this.format_block( 'jstpl_building_tile_surface', {id:tile_id} ), 'building_tile_'+tile_id, 'last' );
        },
        
        // Add to building site the list of building (deck format)
        addToBuildingSite: function( building_list )
        {
            console.log( 'addToBuildingSite' );
            console.log( building_list );
            
            for( var i in building_list )
            {
                var building = building_list[i];
                this.newBuilding( building );
                var tile_id = building.id;

                if( building.location == 'alamb')
                {
                    // Specific case: must add it immediately to alhambra
                    // (happens with Neutral player)
                    this.addToAlhambra( building, 0 );
                }
                else
                {                
                    // Generic case
                    this.placeOnObject( $('building_tile_'+tile_id ), 'buildingdeck' );
                    this.slideToObject( $('building_tile_'+tile_id ), $('buildingsite_'+building.location_arg ) ).play();
                
                    this.zone_to_building[ building.location_arg ] = building;
                    this.building_to_zone[ building.id ] = building.location_arg;
    
                    dojo.addClass( 'building_tile_'+tile_id, 'building_available' );
                    dojo.connect( $('building_tile_'+tile_id ), 'onclick', this, 'onBuyBuilding' );
    
                }
            }
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
        
        
        // Add to money pool this list of money cards (deck format)
        addToMoneyPool: function( money_cards )
        {
            console.log( 'addToMoneyPool' );
            console.log( money_cards );

            // Get the first free money pool
            for( var i in money_cards )
            {
                var card = money_cards[i];
                var money_type_id = parseInt( card.type+''+card.type_arg, 10 );

                for( var p=1; p<=4; p++ )
                {
                    if( this.moneyPool[p].getItemNumber() == 0 )
                    {
                        this.moneyPool[p].addToStockWithId( money_type_id, card.id, 'deck' );
                        break ;
                    }    
                }
    
            }
        },
        
        // Add money card to current player hand.
        // if from_pool = true, make the money come from the pool
        addToPlayerHand: function( money_cards, from_pool )
        {
            for( var i in this.gamedatas.player_hand )
            {
                var card = money_cards[i];
                var money_type_id = parseInt( card.type+''+card.type_arg, 10 );
                this.playerHand.addToStockWithId( money_type_id, card.id );            
            }        

            this.adaptPlayerHandOverlap();
        },

        onScreenWidthChange: function()
        {
            this.adaptPlayerHandOverlap();

            for( player_id in this.gamedatas.alamb )
            {
                this.adaptAlhambra( player_id );
            }
        },

        adaptPlayerHandOverlap: function()
        {
            if( this.playerHand === null )
            {
                return;
            }

            var width_one_card = 125;

            var cards_nbr = this.playerHand.getItemNumber();
            var space_needed = width_one_card * cards_nbr;
            var space_available = dojo.coords( 'player_hand').w;

//            document.title = 'n: '+space_needed+'  a: '+space_available;

            this.playerHand.setOverlap( 75 );
            if( space_available < space_needed && cards_nbr > 1 )
            {
                var overlap = 100* ( space_available-width_one_card) / ((cards_nbr-1)*width_one_card);
                this.playerHand.setOverlap( Math.floor( overlap ) );
            }
            else
            {
                this.playerHand.setOverlap(0);
            }
        },
        
        // Add specified building to corresponding player alhambra
        // Create places if needed
        addToAlhambra: function( building, player_id )
        {
            console.log( "addToAlhambra" );
            
            dojo.query( '.building_last_placed' ).removeClass( 'building_last_placed' );
            
            var bAlreadyExist = false;
            if( $('building_tile_'+building.id) )
            {
                // Already exists ...
                bAlreadyExist = true; 
                
                this.alamb_stock[ player_id ].removeFromZone( 'building_tile_'+building.id, false );
            }
            else
            {
                this.newBuilding( building );
                bAlreadyExist = false;
            }
            
            
            // Place this tile in player alhambra
            var building_div = $('building_tile_'+building.id);
            var x = parseInt( building.x, 10 );
            var y = parseInt( building.y, 10 );
            building_div = this.attachToNewParent( building_div, $('alhambra_'+player_id+'_inner') );

            // item position is relative to player's alhambra fountain position      
            var item_size = this.alhambra_wrapper[ player_id ].item_size;

            var tgt_x = (x*item_size);
            var tgt_y = (y*item_size);

            dojo.style( building_div, "width", (item_size)+'px' );
            dojo.style( building_div, "height", (item_size)+'px' );
            dojo.style( building_div, 'backgroundSize', (item_size*7) + 'px '+(item_size*11)+'px' );            
                        
            if( player_id == this.player_id )
            {
                this.freeplace_index[ x+'x'+y ] = true;
                
                // Remove free place at building place if exists
                var tile_id = 'building_tile_p'+this.player_id+'_'+x+'_'+y;
                if( $(tile_id) )
                {   dojo.destroy( tile_id );    }

                // Add "free" places if needed
                this.addFreePlace( x+1, y );
                this.addFreePlace( x-1, y );
                this.addFreePlace( x, y+1 );
                this.addFreePlace( x, y-1 );
            }
            
            if( bAlreadyExist )
            {
                // Already exists => slide to its position
                dojo.fx.slideTo( {  node: building_div,
                                top: tgt_y,
                                left: tgt_x ,
                                onEnd: dojo.hitch( this, function(){ 
                                        this.alhambra_wrapper[ player_id ].rewrap(); 
                                        this.adaptAlhambra( player_id );
                                } ),
                                unit: "px" } ).play();
            }
            else
            {
                dojo.style( building_div, "left", tgt_x+'px' );
                dojo.style( building_div, "top", tgt_y+'px' );
                this.alhambra_wrapper[ player_id ].rewrap();
                this.adaptAlhambra( player_id );
            }
            
            // Add the "last placed" class
            dojo.addClass( building_div, 'building_last_placed' );

            if( player_id == this.player_id )
            {
                if( parseInt( x, 10 ) !== 0 || parseInt( y, 10 ) !== 0 )  // Filter fountain
                {
                    // Add the "remove building" icon
                   this.addTooltip( 'rm_'+building.id, '', _("Remove this building from your Alhambra and place it in your stock") );
                   dojo.connect( $('rm_'+building.id), 'onclick', this, 'onRemoveBuilding' );
               }
            }            
        },

        adaptAlhambra: function( player_id )
        {
            if( typeof this.alhambra_wrapper[ player_id] == 'undefined')
            {
                return ;
            }

            // Adapt alhambra size & position to make sure it matches the current space
            var coords_container = dojo.position( 'alhambra_wrap_'+player_id );
            var max_width = coords_container.w;

            var coords_alhambra = dojo.position( 'alhambra_'+player_id );
            var width = coords_alhambra.w;
            var height = coords_alhambra.h;


            if( width > max_width  )
            {
                // The alhambra does not fit (in the width)
                var old_size = this.alhambra_wrapper[ player_id ].item_size;
                var new_size = toint( Math.floor( this.alhambra_wrapper[ player_id ].item_size / width * max_width ) );

                // Change tiles size to this size
                this.alhambra_wrapper[ player_id ].item_size = new_size;

                dojo.query( '#alhambra_'+player_id+' .building_tile' ).forEach( dojo.hitch( this, function( node ) {
                    dojo.style( node, 'left', ( dojo.style( node, 'left' ) * new_size / old_size ) + 'px' );
                    dojo.style( node, 'top', ( dojo.style( node, 'top' ) * new_size / old_size ) + 'px' );
                    dojo.style( node, 'width',  new_size + 'px' );
                    dojo.style( node, 'height', new_size + 'px' );
                    dojo.style( node, 'backgroundSize', (new_size*7) + 'px '+(new_size*11)+'px' );
                } ) );

                this.alhambra_wrapper[ player_id ].rewrap();

            }
            else
            {
                // It fits... but may it be larger?
                var old_size = this.alhambra_wrapper[ player_id ].item_size;
                var new_size = Math.min( 95, Math.floor( old_size * max_width / width ) );
                if( new_size > old_size )
                {
                    // We can enlarge the size !
                    this.alhambra_wrapper[ player_id ].item_size = new_size;

                    dojo.query( '#alhambra_'+player_id+' .building_tile' ).forEach( dojo.hitch( this, function( node ) {
                        dojo.style( node, 'left', ( dojo.style( node, 'left' ) * new_size / old_size ) + 'px' );
                        dojo.style( node, 'top', ( dojo.style( node, 'top' ) * new_size / old_size ) + 'px' );
                        dojo.style( node, 'width',  new_size + 'px' );
                        dojo.style( node, 'height', new_size + 'px' );
                        dojo.style( node, 'backgroundSize', (new_size*7) + 'px '+(new_size*11)+'px' );
                    } ) );
    
                    this.alhambra_wrapper[ player_id ].rewrap();

                }
            }

            // Center the new alhambra
            var coords_alhambra = dojo.position( 'alhambra_'+player_id );
            var width = coords_alhambra.w;
            var height = coords_alhambra.h;

            this.slideToObjectPos( 'alhambra_'+player_id, 'alhambra_wrap_'+player_id, (max_width-width)/2, 40 ).play();
        
        },

        // Add a free place to this x/y if needed (if there is not already alnother one)
        addFreePlace: function( x, y )
        {
            console.log( 'add free place '+x+'/'+y );
            
            var this_freeplace_index = x+'x'+y;
            if( this.freeplace_index[ this_freeplace_index ] )
            {
                console.log( "(skipped)" );    // Already done or occupied by a building
                return; 
            }
            
            var tile_id = 'building_tile_p'+this.player_id+'_'+x+'_'+y;
            dojo.place( this.format_block('jstpl_building_tile',
                    {   id: 'p'+this.player_id+'_'+x+'_'+y,
                        back_x: 0,
                        back_y: -700,
                        cost: '',
                        additional_style: 'freeplace'
                    } ), 'alhambra_'+this.player_id+'_inner' ); 
            tile_div = $( tile_id );

            var item_size = this.alhambra_wrapper[ this.player_id ].item_size;

            dojo.style( tile_div, "left", (x*item_size)+'px' );
            dojo.style( tile_div, "top", (y*item_size)+'px' );
            dojo.style( tile_div, "width", (item_size)+'px' );
            dojo.style( tile_div, "height", (item_size)+'px' );
            dojo.style( tile_div, 'backgroundSize', (item_size*7) + 'px '+(item_size*11)+'px' );
            this.freeplace_index[ this_freeplace_index ] = true;

            dojo.connect( tile_div, 'onclick', this, 'onClickFreePlace');
        },
        
       
        // Make building draggable. All buildings in "stock" and "toPlace" zone should be draggable
        makeBuildingDraggable: function( building_id )
        {
            var draggable = new ebg.draggable();
            draggable.create( this, 'building_tile_'+building_id );
            dojo.connect( draggable, 'onStartDragging', this, 'onStartDraggingBuilding' );
            dojo.connect( draggable, 'onEndDragging', this, 'onEndDraggingBuilding' );
            dojo.connect(  $('building_surface_'+building_id), 'onclick', this, 'onClickOnBuilding' );
            dojo.connect(  $('building_tile_'+building_id), 'onclick', this, 'onClickOnBuilding' );
        },

        onClickOnBuilding: function( evt )
        {
            dojo.stopEvent( evt );
            var building_id = evt.currentTarget.id.split('_')[2];

            if( dojo.hasClass( 'building_tile_'+building_id, 'selected' ) )
            {
                // Unselect
                dojo.removeClass( 'building_tile_'+building_id, 'selected' );
                dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress' );
                dojo.removeClass( 'ebd-body', 'alhambra_drag_in_progress_from_stock' );
            }
            else
            {
                dojo.query( '.selected').removeClass( 'selected');
                dojo.addClass( 'building_tile_'+building_id, 'selected' );
                dojo.addClass( 'ebd-body', 'alhambra_drag_in_progress' );
                if(  evt.currentTarget.parentNode.parentNode.parentNode.id  == 'alhambra_stock_current_player')
                {
                    dojo.addClass( 'ebd-body', 'alhambra_drag_in_progress_from_stock' );
                }
            }
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'takeMoney', this, "notif_takeMoney" );
            dojo.subscribe( 'newMoneyCards', this, "notif_newMoneyCards" );
            dojo.subscribe( 'newBuildings', this, "notif_newBuildings" );
            dojo.subscribe( 'buyBuilding', this, "notif_buyBuilding" );
            dojo.subscribe( 'getBuilding', this, "notif_getBuilding" );
            dojo.subscribe( 'placeBuilding', this, "notif_placeBuilding" );
            dojo.subscribe( 'scoringRound', this, "notif_scoringRound" );
            this.notifqueue.setSynchronous( 'scoringRound' );

            dojo.subscribe( 'endOfGame', this, "notif_endOfGame" );
            

            dojo.subscribe( 'alhambraStats', this, "notif_alhambraStats" );
            dojo.subscribe( 'scoringCard', this, "notif_scoringCard" );

            dojo.subscribe( 'updateMoneyCount', this, "notif_updateMoneyCount" );

            
            
        },  

        notif_updateMoneyCount: function( notif )
        {
            $('card_nbr_'+notif.args.player).innerHTML = 'x'+notif.args.count;
        },

        notif_endOfGame: function( notif )
        {
            this.showMessage( _('The last building has been drawn: this is the end of the game!'), 'info' );
        },
        
        notif_takeMoney: function( notif )
        {
            console.log( 'notif_takeMoney' );
            console.log( notif );
           
            var player_id = notif.args.player;
            // Remove from money pool
            for( var id in notif.args.cards )
            {
                console.log( "card id="+id );
                var card = notif.args.cards[id];
                for( var i=1;i<=4;i++ )
                {
                    var card_id_in_pool = this.moneyPool[i].getItemDivId( id );
                    if( $(card_id_in_pool ))
                    {                
                        if( player_id == this.player_id )
                        {
                            // Add to player hand
                            console.log( "add to player hand" );
                            var money_type_id = parseInt( card.type+''+card.type_arg, 10 );
                            this.playerHand.addToStockWithId( money_type_id, card.id, $(card_id_in_pool) );
        
                            console.log( "remove from pool" );
                            this.moneyPool[i].removeFromStockById( id );
                        }
                        else
                        {
                            console.log( "remove from pool" );
                            this.moneyPool[i].removeFromStockById( id, $('player_board_'+player_id) );
                        }
    
                    }
    
                }
            
            }

            this.adaptPlayerHandOverlap();

        },
        
        notif_newMoneyCards: function( notif )
        {
            console.log( "notif_newMoneyCards" );
            console.log( notif );
            this.addToMoneyPool( notif.args.cards );

            $('money_count').innerHTML = 'x'+notif.args.count;
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
        
        refreshAllFreePlaces: function()
        {
            dojo.query( '.freeplace' ).forEach( dojo.destroy );
            this.freeplace_index = {};  // Delete freeplace index
            var buildings_coordinates = [];

            var item_size = this.alhambra_wrapper[ this.player_id ].item_size;

            // Get all building tiles now in display and compute their coordinates
            dojo.query( '#alhambra_'+this.player_id+' .building_tile').forEach( function( node ){

                var x_alhambra = Math.round( dojo.style(node,'left') / item_size );
                var y_alhambra = Math.round( dojo.style(node,'top') / item_size );
                buildings_coordinates.push( {x:x_alhambra,y:y_alhambra});
            });

            console.log( buildings_coordinates );

            for( var i in buildings_coordinates )
            {
                var building = buildings_coordinates[i];
                this.freeplace_index[ building.x+'x'+building.y ] = true;
            }

            for( var i in buildings_coordinates )
            {
                var building = buildings_coordinates[i];
                this.addFreePlace( building.x+1, building.y );
                this.addFreePlace( building.x-1, building.y );
                this.addFreePlace( building.x, building.y+1 );
                this.addFreePlace( building.x, building.y-1 );
            }

        },

        notif_scoringRound: function( notif )
        {
            console.log( 'notif_scoringRound' );
            console.log( notif );
            var round_no = notif.args.round_no;

            this.scoringCurrentRound = round_no;
            this.scoringAnimationToPlay = [];
            
            dojo.style( 'scoring_round_alert', 'display', 'none' );
            
            dojo.style( 'round_scoring_'+round_no, 'display', 'block' );
            dojo.style( 'round_scoring_'+round_no, 'opacity', 0 );
            dojo.fadeIn( {node:'round_scoring_'+round_no}).play();

            for( building_type_id=1; building_type_id<=6; building_type_id++ )
            {
                if( notif.args.buildingdetails[ building_type_id ] )
                {
                    console.log( "Displaying building type "+building_type_id );
                    var players = notif.args.buildingdetails[ building_type_id ];

                    for( var rank=1;rank<=3;rank++)
                    {
                        var to_score;
                        var players_to_score = [];

                        // Get all players at this rank
                        for( var i in players )
                        {
                            scoreInfos = players[ i ];
                            player_id = scoreInfos.player;

                            if( scoreInfos.rank == rank )
                            {
                                // Player is at this rank!
                                to_score = scoreInfos.points;
                                players_to_score.push( player_id );
                            }
                        }

                        if( players_to_score.length > 0 )
                        {
                            // Something to do here!
                            this.scoringAnimationToPlay.push( {
                                building_type_id: building_type_id,
                                rank: rank,
                                score: to_score,
                                players_to_score: players_to_score
                            });
                        }
    
                    }

                }            
            }  
            
            // Finally, add walls
            this.scoringWalls = notif.args.players;
            
            setTimeout( dojo.hitch( this, 'processScoring'), 2000 );
        },

        processScoring: function()
        {
            if( this.scoringAnimationToPlay.length == 0 )
            {
                dojo.query('.highlighted').removeClass( 'highlighted');
                this.fadeOutAndDestroy( 'round_scoring_'+this.scoringCurrentRound );

                // Scoring walls
                for( var player_id in this.scoringWalls )
                {
                    var score = this.scoringWalls[ player_id ].walls;
                    var mobile_obj_html = '<div id="scoring_wall_'+player_id+'_'+score+'" class="scoring_nbr">'+'+'+score+'</div>';
                    var anim = this.slideTemporaryObject( mobile_obj_html, 'wallnbr_'+player_id, 'wallnbr_'+player_id, 'player_score_'+player_id, 2000, 0 );
                    dojo.connect( anim, 'onEnd', dojo.hitch( this, function(node){
                        var player_id = node.id.split('_')[2];
                        var score = node.id.split('_')[3];
                        console.log( 'increase '+player_id+' by '+score );
                        if( player_id != 0 )
                        {
                            this.scoreCtrl[ player_id ].incValue( score );        
                        }
                    }));
                    anim.play();

                }

                endnotif();
            }
            else
            {
                var scoring = this.scoringAnimationToPlay.shift();

                dojo.query('.highlighted').removeClass( 'highlighted');
                if( $('scoring_'+this.scoringCurrentRound+'_'+scoring.building_type_id+'_'+scoring.rank) )
                {
                    dojo.addClass( 'scoring_'+this.scoringCurrentRound+'_'+scoring.building_type_id+'_'+scoring.rank, 'highlighted' );
                }

                // 1s later, highlight the buildings
                setTimeout( dojo.hitch(this, function(){

                    for( var i in scoring.players_to_score )
                    {
                        //alert('#player_board_'+scoring.players_to_score[i]+' stat_'+scoring.building_type_id );
                        //alert( dojo.query('#player_board_'+scoring.players_to_score[i]+' stat_'+scoring.building_type_id ).length );
                        dojo.query('#player_board_'+scoring.players_to_score[i]+' .stat_'+scoring.building_type_id ).addClass('highlighted');
                    }
    
                }), 1000 );

                // 1.5s later, move the score to panel
                setTimeout( dojo.hitch(this, function(){

                    for( var i in scoring.players_to_score )
                    {
                        var player_id = scoring.players_to_score[i];
                        var mobile_obj_html = '<div id="scoring_'+scoring.building_type_id+'_'+player_id+'_'+scoring.score+'" class="scoring_nbr">'+'+'+scoring.score+'</div>';
                        var target_dest = 'player_score_'+scoring.players_to_score[i];
                        if( scoring.players_to_score[i] == 0 )
                        {
                            target_dest = 'playername_0';   // Because there is no score
                        }
                        var anim = this.slideTemporaryObject( mobile_obj_html, 'scoring_'+this.scoringCurrentRound+'_'+scoring.building_type_id+'_'+scoring.rank, 'scoring_'+this.scoringCurrentRound+'_'+scoring.building_type_id+'_'+scoring.rank, target_dest, 2000, 0 );
                        console.log( 'increase scoring_'+scoring.building_type_id+'_'+player_id+'_'+scoring.score );
                        dojo.connect( anim, 'onEnd', dojo.hitch( this, function(node){
                            var player_id = node.id.split('_')[2];
                            var score = node.id.split('_')[3];
                            console.log( 'increase '+player_id+' by '+score );
                            if( player_id != 0 )
                            {
                                this.scoreCtrl[ player_id ].incValue( score );    
                            }
                        }));
                        anim.play();
                    }
    
                }), 1500 );



                setTimeout( dojo.hitch( this, 'processScoring'), 4000 );
            }
        },
        
        // Change one player's alhambra stats (in right panel)
        notif_alhambraStats: function( notif )
        {
            console.log( "notif_alhambraStats" );
            console.log( notif );
            
            var player_id = notif.args.player;
            $('wallnbr_'+player_id).innerHTML = notif.args.walls;
            
            for( var building_type_id in notif.args.buildings )
            {
                $('btnbr_'+building_type_id+'_'+player_id).innerHTML = notif.args.buildings[ building_type_id ];
            }
        },
        
        notif_scoringCard: function( notif )
        {
            console.log( "notif_scoringCard" );
       //     dojo.style( $('scoring_round_alert'), 'display', 'block' );
            this.showMessage( _("Scoring a the end of this turn!"), 'info');
        }
   });
});


