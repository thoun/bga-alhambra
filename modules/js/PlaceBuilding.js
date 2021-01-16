define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("alhambra.placeBuildingTrait", null, {
    constructor(){
      this._notifications.push(
        ['placeBuilding', 1000],
        ['swapBuildings', 1000]
      );

      this.draggables = [];
      this.draggableBuildings = [];
      this.draggedBuilding = null;
      this.posBeforeDrag = null;
      this.droppableZone = null;
    },

// TODO : fix z-index when moving from stock to Alhambra and vice versa

    /*
     * At the end of turn, if player has bought buildings,
     *  he can put them in stock or in its alhambra
     */
    onEnteringStatePlaceBuildings(args){
      if(!this.isCurrentPlayerActive())
        return;

      this.makeBuildingsDraggable(args.buildings);
    },


    /*
     * Placing a building on an Alhambra
     */
    notif_placeBuilding(n){
      debug("Notif: placing a building", n);
      let pId = n.args.player_id,
          building = n.args.building;

      // What is the location ? stock or Alhambra ?
      if(n.args.stock){
        this.addBuilding(building, 'overall_player_board_' + pId);
        this.stockZones[pId].placeInZone('building-tile-' + building.id);
      } else {
        // In case it comes from the stock ...
        this.stockZones[pId].updateDisplay();

        // "Normal" case: add it to alhambra
        building.x = n.args.x;
        building.y = n.args.y;
        this.addToAlhambra(building, pId);
      }

      dojo.removeClass( 'building-tile-' + building.id, 'bought');
    },

    /*
     * Swapping two buildings
     */
    notif_swapBuildings(n){
      debug("Notif: swapping two buildings", n);
      this.notif_placeBuilding(n);
      n.args.building = n.args.building2;
      n.args.stock = false;
      this.notif_placeBuilding(n);
    },

  /*########################
  ##########################
  ###### DRAG N DROP #######
  ##########################
  ########################*/

    /*
     * Init draggable : create the draggable object and listen event
     */
    initDraggableBuilding(building){
      var draggable = new ebg.draggable();
      draggable.create( this, 'building-tile-' + building.id);
      dojo.connect(draggable, 'onStartDragging', () => this.onStartDraggingBuilding(building.id) );
      dojo.connect(draggable, 'onDragging', this, 'onDragging');
      dojo.connect(draggable, 'onEndDragging', this, 'onEndDraggingBuilding');
      draggable.is_disabled = true;
      this.draggables[building.id] = draggable;
      // TODO : seems useless ?
      //dojo.connect(  $('building_surface_'+building_id), 'onclick', this, 'onClickOnBuilding' );
    },


    /*
     * (Init) and enable draggable for given buildings
     */
    makeBuildingsDraggable(buildings){
      this.draggableBuildings = [];
      buildings.forEach(building => {
        // No available place and can't go to stock => can't really drag him around
        if(building.availablePlaces.length == 0 && !building.canGoToStock)
          return;

        this.draggableBuildings[building.id] = building;
        if(!this.draggables[building])
          this.initDraggableBuilding(building);

        this.draggables[building.id].enable();
        building.availablePlaces.forEach(pos => this.addFreePlace(pos.x, pos.y) );
      });
    },


    /*
     * Turn off the draggable buildings
     */
    disableAllDraging(){
      Object.values(this.draggables).forEach(draggable => draggable.disable());
      this.draggableBuildings = [];
      this.draggedBuilding = null;
      this.posBeforeDrag = null;
      this.droppableZone = null;
      dojo.query(".droppable").removeClass("droppable");
      dojo.query(".droppable-now").removeClass("droppable-now");
    },



    /*
     * When starting to drag => highlight the drop possibilities
     */
    onStartDraggingBuilding(bId){
      let building = this.draggableBuildings[bId];
      this.draggedBuilding = building;
      this.posBeforeDrag = {
        x : dojo.style('building-tile-' + bId, 'left'),
        y : dojo.style('building-tile-' + bId, 'top'),
      };


      if(building.canGoToStock)
        dojo.addClass('player-stock', 'droppable');

      dojo.query('.free-place').removeClass('droppable');
      building.availablePlaces.forEach(pos => {
        dojo.addClass('free-place-' + pos.x + '-' + pos.y, 'droppable');
      });
    },


    /*
     * Test whether some position x,y is over an element
     */
    isOver(x,y, elemId){
      let pos = dojo.position(elemId);
      return x >= pos.x && x <= pos.x + pos.w
        && y >= pos.y && y <= pos.y + pos.h;
    },

    /*
     * When dragging, compute the underlying element and highlight if droppable
     */
    onDragging(elemId, left, top, dx, dy){
      let building = this.draggedBuilding;
      let pos = dojo.position(elemId);
      let centerX = pos.x + pos.w / 2;
      let centerY = pos.y + pos.h / 2;

      let droppableZone = null;

      // Test stock
      if(building.canGoToStock && this.isOver(centerX, centerY, "player-stock"))
        droppableZone = 'stock';

      // Then test free places
      building.availablePlaces.forEach(pos => {
        if(this.isOver(centerX, centerY, 'free-place-' + pos.x + '-' + pos.y))
          droppableZone = pos;
      });

      // Remove previous droppable zone
      if(droppableZone == null || this.droppableZone != droppableZone){
        dojo.query(".droppable-now").removeClass("droppable-now");
      }

      this.droppableZone = droppableZone;
      if(droppableZone != null){
        let id = droppableZone == "stock"? "player-stock" : ('free-place-' + droppableZone.x + '-' + droppableZone.y);
        dojo.addClass(id, 'droppable-now');
      }
   },


    /*
     * When we stop dragging => check whether the drop zone is ok
     */
    onEndDraggingBuilding( item_id, left, top, bDragged){
      dojo.query('.droppable').removeClass('droppable');

      // Not on a valid droppable zone => reset to original position and stop
      if(this.droppableZone == null){
        dojo.fx.slideTo({
          node: $("building-tile-" + this.draggedBuilding.id),
          left: this.posBeforeDrag.x,
          top: this.posBeforeDrag.y,
          unit: "px"
        }).play();

        this.draggedBuilding = null;
        this.posBeforeDrag = null;
        return;
      }


      if(this.droppableZone == "stock"){
        debug("Building dropped on stock");
        this.takeAction("placeBuildingOnStock", {
          buildingId: this.draggedBuilding.id,
        });
        // TODO remove ? this.alamb_stock[ this.player_id ].updateDisplay();
      } else {
        // TODO test stateName instead
        let action = "placeBuildingOnAlhambra";
        /*
        else
        {
            url = "/alhambra/alhambra/transformAlhambraPlace.html";
        }
        */

        this.takeAction(action, {
          buildingId: this.draggedBuilding.id,
          x: this.droppableZone.x,
          y: this.droppableZone.y
        });
      }
    },


  /*##########################
  ############################
  ###### CLICK N CLICK #######
  ############################
  ##########################*/

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
