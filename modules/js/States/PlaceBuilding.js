define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("alhambra.placeBuildingTrait", null, {
    constructor(){
      /*
      this._notifications.push(
        ['newHand', 100],
        ['giveCard', 1000],
        ['receiveCard', 1000]
      );
      */
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


  });
});
