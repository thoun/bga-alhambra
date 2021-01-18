define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  const DIRK = 0;
  return declare("alhambra.playerTrait", null, {
    constructor(){
      this._notifications.push(
        ['alhambraStats', 10],
        ['updateMoneyCount', 10]
      );
      this.statCounters = {};
    },

    setupPlayers(){
      Object.values(this.gamedatas.players).forEach(player => {
        let pId = player.id;

        if(this.player_id == pId){
          this.place('jstpl_currentPlayerPanel', player, 'upper-part');
          this.setupMoneyHand(player.hand);
        } else {
          this.place('jstpl_playerPanel', player, 'bottom-part');
        }

        // Setup stock of player
        this.setupStock(player);

        // Setup Alhambra of player
        this.setupAlhambra(player);

        // Setup board for stats
        this.setupPlayerStats(player);
        this.updatePlayerStats(player);
      });


      let neutral = this.gamedatas.neutral;
      if(this.gamedatas.isNeutral){
        neutral.name = _('Dirk (neutral player)');
        this.place('jstpl_playerPanel', neutral, 'bottom-part');
        this.setupAlhambra(neutral);

        this.place('jstpl_neutralPlayerBoard', neutral, 'player_boards');
        this.setupPlayerStats(neutral);
        this.updatePlayerStats(neutral);
      }
    },


    setupPlayerStats(player){
      let pId = player.id;
      this.place('jstpl_playerStats', player, 'player_board_' + pId);
      this.statCounters[pId] = {};
      for(var i = 1; i <= 6; i++){
        this.statCounters[pId][i] = new ebg.counter();
        this.statCounters[pId][i].create("stat-" + pId + "-" + i);
      }
      this.statCounters[pId]["wall"] = new ebg.counter();
      this.statCounters[pId]["wall"].create("stat-" + pId + "-wall");
      this.statCounters[pId]["card"] = new ebg.counter();
      this.statCounters[pId]["card"].create("card-" + pId + "-nbr");
    },

    updatePlayerStats(player){
      for(var i = 1; i <= 6; i++){
        this.statCounters[player.id][i].toValue(player.board.stats[i]);
      }
      this.statCounters[player.id]["wall"].toValue(player.board.wall);
      this.statCounters[player.id]["card"].toValue(player.cardCount);
    },


    // Change one player's alhambra stats (in right panel)
    notif_alhambraStats(n){
      debug("Notif: updating alhambra stats", n);
      let player = n.args.player_id == 0? this.gamedatas.neutral : this.gamedatas.players[n.args.player_id];
      player.board.wall = n.args.walls;
      player.board.stats = n.args.buildings;
      this.updatePlayerStats(player);
    },

    notif_updateMoneyCount(n){
      debug("Notif: updating money count", n);
      let player = n.args.player_id == 0? this.gamedatas.neutral : this.gamedatas.players[n.args.player_id];
      player.cardCount = n.args.count;
      this.updatePlayerStats(player);
    },
  });
});
