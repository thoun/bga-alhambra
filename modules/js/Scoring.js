define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("alhambra.scoringTrait", null, {
    constructor(){
      this._notifications.push(
        ['startScoringRound', 100],
        ['scoringRound', null],
        ['endOfGame', 10]
      );
      this.scoringCurrentRound = null;
      this.scoringAnimationToPlay = [];
    },


    notif_endOfGame(n){
      this.showMessage( _('The last building has been drawn: this is the end of the game!'), 'info' );
    },

    notif_startScoringRound(n){
      debug("Notif: start scoring round", n);
      this.showMessage( _("Starting a scoring round."), 'info');
      dojo.attr('token-crown', 'data-round', n.args.round);

      this.scoringCurrentRound = round;
      this.scoringAnimationToPlay = [];

      // Display the scoring panel
      dojo.style('round_scoring_' + round, "display", "block");
      dojo.style('scoring_panel', {
        display: "block",
        opacity: 0,
      });
      dojo.fadeIn( {node:'scoring_panel'}).play();
    },

    notif_scoringRound(n){
      debug("Notif: scoring round", n);
      var round = n.args.round_no;



      // Compute animations
      for(type = 1; type <= 6; type++){
        if(n.args.buildingdetails[type] == undefined)
          continue;

        debug("Displaying building type " + type);
        var players = n.args.buildingdetails[type];

        for(var rank = 1; rank <= 3; rank++){
          let playersToScore = players.filter(player => player.rank == rank);
          let score = playersToScore.length == 0? 0 : playersToScore[0].points;

          if(playersToScore.length > 0 && score > 0){
            // Something to do here!
            this.scoringAnimationToPlay.push({
              type: type,
              rank: rank,
              score: score,
              players: playersToScore.map(player => player.player),
            });
          }
        }
      }

      // Finally, add walls
      this.scoringWalls = n.args.players;

      this.pause(1000).then( () => this.processScoring() );
    },


    slideScoringTmp(type, pId, score, from){
      let target = pId == 0? 'playername_0' : 'player_score_' + pId;
      debug(from, target);
      return this.slideTemporary('jstpl_tmpScoring', { pId, type, score }, from, from, target, 800);
    },

    incScore(pId, score){
      if(pId != 0)
        this.scoreCtrl[pId].incValue(score);
    },

    processScoring(){
      if( this.scoringAnimationToPlay.length == 0 ){
        // End of scoring round
        this.wallScoringAnimation();
        return;
      }

      // Getting next scoring to animate
      var scoring = this.scoringAnimationToPlay.shift();

      // Highlight scoring board
      dojo.query('.highlighted').removeClass( 'highlighted');
      let scoringId = 'scoring_' + this.scoringCurrentRound + '_'+scoring.type + '_' + scoring.rank;
      if($(scoringId))
        dojo.addClass(scoringId, 'highlighted' );

      this.pause(500)
      // highlight the buildings
      .then(() => {
        scoring.players.forEach(pId =>
          dojo.query('#player_board_'+ pId +' .stat-' + scoring.type).addClass('highlighted')
        );

        return this.pause(500);
      })
      // move the score to panel
      .then( () => {
        scoring.players.forEach(pId => {
          this.slideScoringTmp(scoring.type, pId, scoring.score, scoringId)
          .then( () => this.incScore(pId, scoring.score) );
        });

        return this.pause(1000);
      })
      // move on to next scoring
      .then(() => this.processScoring());
    },

    wallScoringAnimation(){
      dojo.query('.highlighted').removeClass( 'highlighted');
      dojo.style('round_scoring_' + this.scoringCurrentRound, "display", "none");
      dojo.fadeOut( {node:'scoring_panel', onEnd: () => dojo.style("scoring_panel", "display", "none") }).play();
      dojo.attr('token-crown', 'data-round', 0);

      // Scoring walls
      Object.keys(this.scoringWalls).forEach(pId => {
        let score = this.scoringWalls[pId].walls;
        this.slideScoringTmp("wall", pId, score, 'stat-' + pId + '-wall')
        .then( () => this.incScore(pId, score) );
      });
      endnotif();
    }
  });
});
