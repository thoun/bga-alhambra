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


                $('wallnbr_'+player_id).innerHTML = player.longest_wall;

                if( gamedatas.alamb_stats[ player_id ] )
                {
                    for( var building_type_id in gamedatas.alamb_stats[ player_id ] )
                    {
                        $('btnbr_'+building_type_id+'_'+player_id).innerHTML = gamedatas.alamb_stats[ player_id ][ building_type_id ];
                    }
                }

            }

            // "stock" zone

            // Alhambra
            this.freeplace_index = {};
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

            case 'playerTurn':
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

        onScreenWidthChange: function()
        {
            this.adaptPlayerHandOverlap();

            for( player_id in this.gamedatas.alamb )
            {
                this.adaptAlhambra( player_id );
            }
        },

        // Add specified building to corresponding player alhambra
        // Create places if needed



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
