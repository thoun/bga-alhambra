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


            if( gamedatas.is_scoring_round != '0' )
            {   dojo.style( $('scoring_round_alert'), 'display', 'block' );  }

        },

        //////////////////////////////////////////////////////////
        //// UI events

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
   });
});
