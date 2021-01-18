define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("alhambra.scoringTrait", null, {
    constructor(){
      this._notifications.push(
        ['scoringCard', 100],
        ['scoringRound', null]
      );
    },

    notif_scoringCard(n){
      debug("Notif: scoring card");
      this.showMessage( _("Scoring a the end of this turn!"), 'info');
    },


    notif_scoringRound(n){
      debug("Notif: scoring round", n);
      return;

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

  });
});
