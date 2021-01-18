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


        notif_endOfGame: function( notif )
        {
            this.showMessage( _('The last building has been drawn: this is the end of the game!'), 'info' );
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
   });
});
