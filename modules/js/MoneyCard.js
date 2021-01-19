define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  const CARD_W = 112; // 125 ??
  const CARD_H = 173;

  const BUILDING_THEN_MONEY = 1;
  const MONEY_THEN_BUILDING = 2;


  return declare("alhambra.moneyCardTrait", null, {
    constructor(){
      this._notifications.push(
        ['takeMoney', 600],
        ['newMoneyCards', 10]
      );

      this.initialMoneyDlg = null;
      this.moneyDeckCounter = null;
      this.playerHand = null;
      this.cardInStacks = [];
      this.selectedStacks = [];
      this.selectableStacks = [];

      this.selectableCards = [];
    },


    addCard(card, container){
      this.place('jstpl_moneyCard', card, container);
      // TODO : add tooltip
    },


  /*#######################
  #########################
  ##### INITIAL MONEY #####
  #########################
  #######################*/
    onEnteringStateInitialMoney(args){
      if(this.isCurrentPlayerActive()){
        this.showInitialMoneyDialog(args);
      }
    },

    showInitialMoneyDialog(cards)
    {
      this.initialMoneyDlg = new customgame.modal("moneyDialog", {
        autoShow:true,
        class:"alhambra_popin",
        closeIcon:null,
        closeWhenClickOnUnderlay:false,
        verticalAlign:'flex-start',
        title: _("Initial money for players"),
      });

      // Add money cards
      Object.keys(cards).forEach(pId => {
        let name = this.gamedatas.players[pId].name;
        let tpl = `
        <div class="money-dialog-player">
          <h4>${name}</h4>
          <div class="money-dialog-holder" id="money-dialog-${pId}"></div>
        </div>`;
        dojo.place(tpl, 'popin_moneyDialog_contents');

        cards[pId].forEach(card => this.addCard(card, 'money-dialog-' + pId));
      });

      this.addPrimaryActionButton('btnCloseMoneyDlg', _('Ok'), () => this.onAcceptMoney(), 'popin_moneyDialog_contents');
    },

    onAcceptMoney() {
      if(!this.checkAction('acceptMoney'))
        return;

      this.takeAction('acceptMoney').then(() => this.initialMoneyDlg.destroy() );
    },




  /*#######################
  #########################
  ###### MONEY POOL #######
  #########################
  #######################*/
    setupMoneyPool(initialSetup = true){
      // Add cards to pool
      this.addToMoneyPool(this.gamedatas.moneyCards.pool);

      // Useful in case of cancel turn
      if(!initialSetup)
        return;

      // Connect events
      [0,1,2,3].forEach(i => dojo.connect($('money-spot-' + i), 'click', () => this.onMoneyPoolChangeSelection(i) ) );

      // Setup deck counter and update
      this.moneyDeckCounter = new ebg.counter();
      this.moneyDeckCounter.create('money-count');
      this.updateMoneyDeckCount();
    },

    updateMoneyDeckCount(){
      this.moneyDeckCounter.setValue(this.gamedatas.moneyCards.count);
    },

    // Called after a restart of the turn
    clearMoneyPool(){
      [0,1,2,3].forEach(i => dojo.empty('money-spot-' + i) );
    },


    // Add to money pool this list of money cards (deck format)
    addToMoneyPool(cards, animate = false){
      debug("Adding money cards to pool", cards);

      cards.forEach((card, j) => {
        let i = this.findFirstFreeSpot();
        this.cardInStacks[i] = card;
        this.addCard(card, 'money-spot-' + i);

        if(animate){
          let id = 'card-' + card.id;
          dojo.addClass(id, "flipped animate");
          this.placeOnObject(id, "money-deck");
          this.slidePos(id, "money-spot-" + i, 0, 0, 800)
          .then(() => {
            dojo.removeClass(id, "flipped");
            setTimeout(() => dojo.removeClass(id, "animate"), 500);
          });
        }
      })
    },

    // Find the first free spot in pool
    findFirstFreeSpot(){
      for(var i = 0; i < 4; i++){
        if($('money-spot-' + i).children.length == 0)
          return i;
      }
      return -1;
    },



    makeMoneyPoolSelectable(stacks = null){
      dojo.query('.money-spot').removeClass('selectable').addClass('unselectable');
      if(stacks == null)
        stacks = [0,1,2,3];

      stacks.forEach(i => {
        dojo.query('#money-spot-' + i).removeClass("unselectable").addClass("selectable");
      });
      this.selectableStacks = stacks;
    },

    updateSelectableStacks(){
      var selectedTotal = this.selectedStacks.reduce((carry, i) => carry + this.cardInStacks[i].value, 0);
      let stacks = null;
      if(selectedTotal != 0){
        stacks = [];
        this.cardInStacks.forEach((card, i) => {
          if(this.selectedStacks.includes(i) || card.value + selectedTotal <= 5)
            stacks.push(i);
        });
      }

      this.makeMoneyPoolSelectable(stacks);
    },

    /*
     * Clicking on a stack of the pool
     */
    onMoneyPoolChangeSelection(stack) {
      debug("Cliclink on stack", stack);

      if(!this.checkAction('takeMoney'))
        return;

      // Unselect
      if(this.selectedStacks.includes(stack)){
        this.selectedStacks = this.selectedStacks.filter(stackId => stackId != stack);
        dojo.removeClass('money-spot-' + stack, "selected");
      }
      // Select if not too much
      else {
        var selectedTotal = this.selectedStacks.reduce((carry, i) => carry + this.cardInStacks[i].value, 0);
        if(selectedTotal != 0 && this.cardInStacks[stack].value + selectedTotal > 5){
          this.showMessage( _("If you take several money cards their total value should not exceed 5"), "error" );
          return;
        }

        this.selectedStacks.push(stack);
        dojo.addClass('money-spot-' + stack, "selected");
      }

      // Update selectable stacks depending on current total
      this.updateSelectableStacks();

      // Update buttons and take action if needed
      if(this.selectedStacks.length > 0){
        // Compute (un)selected cards
        var selectedCards = [], unselectedCards = [];
        this.cardInStacks.forEach((card, i) => {
          if(this.selectedStacks.includes(i))
            selectedCards.push(card);
          else
            unselectedCards.push(card);
        });

        if(selectedCards.length == 1 && (selectedCards[0].value >= 5 || this.selectableStacks.length == 1)){ // 1 and not 0 since we can unselect
          this.onConfirmTakeMoney();
        }
        else {
          this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturnmoney;
          this.updatePageTitle();
          this.addSecondaryActionButton('btnCancelMoneyChoice', _('Cancel'), () => this.onCancelTakeMoney());
          this.addPrimaryActionButton('btnConfirmMoneyChoice', _('Confirm'), () => this.onConfirmTakeMoney());
        }
      }
      else {
        this.onCancelTakeMoney();
      }
    },

    /*
     * Confirm the multiselection of cards => send that to backend
     */
    onConfirmTakeMoney(){
      let cardIds = this.selectedStacks.map(stack => this.cardInStacks[stack].id).join(';');
      this.takeAction('takeMoney', { cardIds: cardIds});
    },

    /*
     * Clear multiselection
     */
    onCancelTakeMoney(){
      this.selectedStacks = [];
      dojo.query('.money-spot').removeClass('selected');
      this.updateSelectableStacks();
      dojo.destroy('btnConfirmMoneyChoice');
      dojo.destroy('btnCancelMoneyChoice');
      this.resetPageTitle();
    },



    notif_takeMoney(n) {
      debug("Notif: takin money", n);
      let pId = n.args.player_id,
          cards = n.args.cards;

      if(pId == this.player_id){
        this.addToPlayerHand(cards);
      }
      else {
        cards.forEach(card => this.slideAndDestroy('card-' + card.id, 'player_name_' + pId, 500) );
      }
    },



    notif_newMoneyCards(n) {
      debug("New money cards", n);
      this.addToMoneyPool(n.args.cards, true); // True to animate
      this.gamedatas.moneyCards.count = n.args.count;
      this.updateMoneyDeckCount();
    },


  /*#######################
  #########################
  ######### HAND ##########
  #########################
  #######################*/
    setupMoneyHand(cards){
      // Init stock component
      this.playerHand = new ebg.stock();
      this.playerHand.create(this, $('player-hand'), CARD_W, CARD_H );
      this.playerHand.setSelectionAppearance('class');
      this.playerHand.image_items_per_row = 9;

      // Create types
      var pos = 0;
      for(var type = 1; type <= 4; type++){
        for(var value = 1; value <= 9; value++) {
          var typeId = type*10 + value;
          this.playerHand.addItemType(typeId, typeId, g_gamethemeurl + 'img/money.jpg', pos++);
        }
      }

      this.addToPlayerHand(cards);
      dojo.connect(this.playerHand, 'onChangeSelection', this, 'onChangeHandSelection');
    },

    // Called after a restart
    refreshPlayerHand(cards){
      this.playerHand.removeAll();
      this.addToPlayerHand(cards);
    },

    // Add money card to current player hand.
    // If card exist in pool, then destroy and slide from there
    addToPlayerHand(cards) {
      cards.forEach(card => {
        let from = $('card-' + card.id)? ('card-' + card.id) : undefined;
        this.playerHand.addToStockWithId(card.stockType, card.id, from);
        dojo.destroy('card-' + card.id);
      })

      // TODO : wait for the animation to end and readapt after
      this.adaptPlayerHandOverlap();
    },


    /*
     * Adapt stock overlap so it fits in one row
     */
    adaptPlayerHandOverlap() {
      if(this.playerHand === null)
        return; // Can be trigger when loading by onScreenWidthChange

      let nCards = this.playerHand.getItemNumber();
      let cardWidth = CARD_W + 10; // margin
      let spaceNeeded = cardWidth * nCards;
      var spaceAvailable = dojo.coords('player-hand').w;

      this.playerHand.setOverlap(75);
      if(spaceAvailable < spaceNeeded && nCards > 1){
        let overlap = 100 * (spaceAvailable - cardWidth) / ((nCards - 1) * cardWidth);
        this.playerHand.setOverlap(Math.floor(overlap));
      }
      else {
        this.playerHand.setOverlap(0);
      }
    },


    /*
     * Get total value of each color in hand
     */
    getTotalValueByColorInHand(onlySelected = false){
      onlySelected = onlySelected || this.playerHand.getSelectedItems().length > 0;

      var cards = onlySelected? this.playerHand.getSelectedItems() : this.playerHand.getAllItems();
      var totals = { 1:0, 2:0, 3:0, 4:0};
      cards.forEach(card => {
        let type = Math.floor(card.type / 10),
            value = card.type % 10;
        totals[type] += value;
      });

      return totals;
    },

    /*
     * Get card ids of a given color(s)
     */
    getCardsInHandOfColors(colors){
      return this.playerHand.getAllItems().filter(item => {
        let color = Math.floor(item.type / 10);
        return colors.includes(color);
      }).map(item => item.id);
    },

    /*
     * Update list of cards selectable
     */
    updateSelectableCards(){
      dojo.query("#player-hand .stockitem").removeClass('selectable').addClass('unselectable');
      this.selectableCards = [];

      let selected = this.playerHand.getSelectedItems();
      let colors = null;
      if(selected.length > 0){
        // Filter all cards of this color
        colors = [Math.floor(selected[0].type / 10)];
      } else {
        // Get colors of selectable buildings
        colors = this.selectableBuildings.map(building => building.pos);
      }

      this.selectableCards = this.getCardsInHandOfColors(colors);
      this.selectableCards.forEach(cId => dojo.query("#player-hand_item_" + cId).removeClass('unselectable').addClass('selectable') );
    },

    /*
     * Called when a card is clicked
     */
    onChangeHandSelection(control_name, cardId){
      if(cardId == undefined)
        return;

      // Check if item was selected
      if(!this.selectableCards.includes(+cardId)){
        this.playerHand.unselectItem(cardId);
      }


      // "MONEY_THEN_BUILDING" mode
      if(this.selectionMode == null || this.selectionMode == MONEY_THEN_BUILDING){
        if(this.playerHand.getSelectedItems().length == 0){
          this.onCancelBuyBuilding()
          return;
        }

        this.selectionMode = MONEY_THEN_BUILDING;
        this.updateSelectableBuildings();

        this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate.descriptionmyturnmoneyforbuilding;
        this.updatePageTitle();
      }

      // Already in "building then money" mode => unselect selected building if it was clicked
      else if(this.selectionMode == BUILDING_THEN_MONEY){
        // Compare value with selected total
        let total = this.getTotalValueByColorInHand(true); // true = only selected
        if(total[this.selectedBuilding.pos] >= this.selectedBuilding.cost)
          this.addPrimaryActionButton('btnConfirmBuyBuilding', _('Buy'), () => this.onConfirmBuyBuilding());
        else
          dojo.destroy('btnConfirmBuyBuilding');
      }
    },
  });
});
