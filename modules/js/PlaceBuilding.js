define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("alhambra.placeBuildingTrait", null, {
    constructor(){
      this._notifications.push(
        ['placeBuilding', 1000],
        ['swapBuildings', 1000],
        ['updatePlacementOptions', 10]
      );

      this.draggables = [];
      this.draggableBuildings = [];
      this.draggedBuilding = null;
      this.posBeforeDrag = null;
      this.droppableZone = null;
      this.clickedBuilding = null;
      this.bDragged = false;
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
      if(this.gamedatas.isNeutral){
        this.onEnteringStatePlaceBuildingsWithNeutral();
      }
    },


    /*
     * LAST BUILDINGS
     */
    onEnteringStatePlaceLastBuildings(args){
      args._private.remove.forEach(building => {
        dojo.removeClass('building-tile-' + building.id, "bought");
        dojo.style('building-tile-' + building.id, { top:0, left: 0});
      });

      if(this.isCurrentPlayerActive()){
        this.addToBuildingSiteToPlace(args._private.buildings, true);
        this.makeBuildingsDraggable(args._private.buildings);
      }
    },

    notif_updatePlacementOptions(n){
      debug("Notif: update options");
      this.clearPossible();
      this.makeBuildingsDraggable(n.args.buildings);
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
        if(pId != 0)
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



    /*###############
    ###### DIRK #####
    ###############*/
    onEnteringStatePlaceBuildingsWithNeutral(){
      this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturndirk;
      this.updatePageTitle();
      this.addActionButton('brnGiveDirk', _('Give them to neutral player'), 'onGiveToNeutral');
    },


    onGiveToNeutral() {
      this.confirmationDialog(
        _("Are you sure you want to give away these buildings to Neutral player?"),
        () => this.takeAction('giveNeutral')
      );
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

        // Click'n'click
        // TODO dojo.addClass('building-tile-' + building.id, 'clickable');
        this.connect($('building-tile-' + building.id), 'onclick', (evt) => this.onClickOnBuildingToDrag(evt, building) );
      });

      // Click n click
      this.connect($('player-stock'), 'onclick', () => this.onClickOnStockToDrop() );
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
      this.bDragged = true;

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
      this.bDragged = bDragged;
      if(!bDragged)
        return;

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

      this.actPlaceBuilding();
    },


    actPlaceBuilding(){
      if(this.droppableZone == "stock"){
        this.takeAction("placeBuildingOnStock", {
          buildingId: this.draggedBuilding.id,
        });
      } else {
        this.takeAction("placeBuildingOnAlhambra", {
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
    onClickOnBuildingToDrag(evt, building){
      if(this.bDragged)
        return;

      dojo.stopEvent(evt);
      if(this.clickedBuilding == null){
        this.onStartDraggingBuilding(building.id);
        this.clickedBuilding = building;
        this.bDragged = false;
        dojo.addClass('building-tile-' + building.id, 'drag-selected');
      } else {
        dojo.removeClass('building-tile-' + this.clickedBuilding.id, 'drag-selected');
        this.clickedBuilding = null;
        dojo.query('.droppable').removeClass('droppable');
      }
    },


    onClickOnStockToDrop(){
      // Is is really a click n click ?
      if(this.bDragged || this.clickedBuilding == null)
        return;

      // Can be dropped on stock ?
      if(!this.clickedBuilding.canGoToStock)
        return;

      this.droppableZone = 'stock';
      this.draggedBuilding = this.clickedBuilding;
      this.actPlaceBuilding();
    },

    onClickFreePlaceToDrop(x, y){
      debug("test", x, y, this.bDragged);
      // Is is really a click n click ?
      if(this.bDragged || this.clickedBuilding == null)
        return;

      // Can be dropped on this free place ?
      if(this.clickedBuilding.availablePlaces.find(pos => pos.x == x && pos.y == y) == undefined)
        return;

      this.droppableZone = { x, y };
      this.draggedBuilding = this.clickedBuilding;
      this.actPlaceBuilding();
    },
  });
});
