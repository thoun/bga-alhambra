define(["dojo", "dojo/_base/declare", g_gamethemeurl + "modules/js/modal.js"], (dojo, declare) => {
  const CARD_W = 112; // 125 ??
  const CARD_H = 173;


  return declare("alhambra.moneyCardTrait", null, {
    constructor(){
      this._notifications.push(
        ['takeMoney', 100],
        ['newMoneyCards', 500]
      );

      this.initialMoneyDlg = null;
      this.moneyDeckCounter = null;
      this.playerHand = null;
      this._cardInStacks = [];
      this._selectedStacks = [];
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
          <h3>${name}</h3>
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
    setupMoneyPool(){
      // Connect events
      [0,1,2,3].forEach(i => dojo.connect($('money-spot-' + i), 'click', () => this.onMoneyPoolChangeSelection(i) ) );

      // Add cards to pool
      this.addToMoneyPool(this.gamedatas.moneyCards.pool);

      // Setup deck counter and update
      this.moneyDeckCounter = new ebg.counter();
      this.moneyDeckCounter.create('money-count');
      this.updateMoneyDeckCount();
    },

    updateMoneyDeckCount(){
      this.moneyDeckCounter.setValue(this.gamedatas.moneyCards.count);
    },


    // Add to money pool this list of money cards (deck format)
    addToMoneyPool(cards, animate = false){
      debug("Adding money cards to pool", cards);

      cards.forEach((card, j) => {
        let i = this.findFirstFreeSpot();
        this._cardInStacks[i] = card;
        this.addCard(card, 'money-spot-' + i);

        if(animate){

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
      stacks = stacks ?? [0,1,2,3];
      stacks.forEach(i => {
        dojo.query('#money-spot-' + i).removeClass("unselectable").addClass("selectable");
      });
    },

    updateSelectableStacks(){
      var selectedTotal = this._selectedStacks.reduce((carry, i) => carry + this._cardInStacks[i].value, 0);
      debug(selectedTotal)
      let stacks = null;
      if(selectedTotal != 0){
        stacks = [];
        this._cardInStacks.forEach((card, i) => {
          if(this._selectedStacks.includes(i) || card.value + selectedTotal <= 5)
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
      if(this._selectedStacks.includes(stack)){
        this._selectedStacks = this._selectedStacks.filter(stackId => stackId != stack);
        dojo.removeClass('money-spot-' + stack, "selected");
      }
      // Select if not too much
      else {
        var selectedTotal = this._selectedStacks.reduce((carry, i) => carry + this._cardInStacks[i].value, 0);
        if(selectedTotal != 0 && this._cardInStacks[stack].value + selectedTotal > 5){
          this.showMessage( _("If you take several money cards their total value should not exceed 5"), "error" );
          return;
        }

        this._selectedStacks.push(stack);
        dojo.addClass('money-spot-' + stack, "selected");
      }

      // Update selectable stacks depending on current total
      this.updateSelectableStacks();

      // Update buttons and take action if needed
      if(this._selectedStacks.length > 0){
        // Compute (un)selected cards
        var selectedCards = [], unselectedCards = [];
        this._cardInStacks.forEach((card, i) => {
          if(this._selectedStacks.includes(i))
            selectedCards.push(card);
          else
            unselectedCards.push(card);
        });


        if(selectedCards.length == 1 && selectedCards[0].value >= 5){
          this.takeAction('takeMoney', { cardIds: selectedCards[0].id });
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
      let cardIds = this._selectedStacks.map(stack => this._cardInStacks[stack].id).join(';');
      this.takeAction('takeMoney', { cardIds: cardIds});
    },

    /*
     * Clear multiselection
     */
    onCancelTakeMoney(){
      this._selectedStacks = [];
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
    },


    // Add money card to current player hand.
    // If card exist in pool, then destroy and slide from there
    addToPlayerHand(cards) {
      cards.forEach(card => {
        let from = $('card-' + card.id)? ('card-' + card.id) : undefined;
        this.playerHand.addToStockWithId(card.stockType, card.id, from);
        dojo.destroy('card-' + card.id);
      })

      this.adaptPlayerHandOverlap();
    },


    adaptPlayerHandOverlap() {
      if(this.playerHand === null)
        return; // Can be trigger when loading by onScreenWidthChange

      let nCards = this.playerHand.getItemNumber();
      let cardWidth = CARD_W + 10; // 5 = margin
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



  });
});
