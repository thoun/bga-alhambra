define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("alhambra.playerTrait", null, {
    constructor(){
      /*
      this._notifications.push(
        ['newHand', 100],
        ['giveCard', 1000],
        ['receiveCard', 1000]
      );
      */
    },

    setupPlayers(){
      Object.values(this.gamedatas.players).forEach(player => {


        if(this.player_id == player.id){
          this.place('jstpl_currentPlayerPanel', player, 'upper-part');
          this.setupMoneyHand(player.hand);
        }
      });

      /*
      for( player_id in gamedatas.players )
      {
          var player = gamedatas.players[player_id];
          var player_board_div = $('player_board_'+player_id);
          dojo.place( this.format_block('jstpl_player_board', {id: player.id } ), player_board_div );

          this.alamb_stock[ player_id ] = new ebg.zone();
          this.alamb_stock[ player_id ].create( this, $('stock_'+player_id), 95, 95 );
          if( player_id != this.player_id )
          {
              this.alamb_stock[ player_id ].autowidth = true;
          }
          this.alamb_stock[ player_id ].setFluidWidth();

          $('wallnbr_'+player_id).innerHTML = player.longest_wall;

          if( gamedatas.alamb_stats[ player_id ] )
          {
              for( var building_type_id in gamedatas.alamb_stats[ player_id ] )
              {
                  $('btnbr_'+building_type_id+'_'+player_id).innerHTML = gamedatas.alamb_stats[ player_id ][ building_type_id ];
              }
          }
      }
*/
    },
  });
});
