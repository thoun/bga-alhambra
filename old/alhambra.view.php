<?php
 /**
  * alhambra.view.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * alhambra main static view construction
  *
  */

  require_once( APP_BASE_PATH."view/common/game.view.php" );

  class view_alhambra_alhambra extends game_view
  {
    function getGameName() {
        return "alhambra";
    }

  	function build_page( $viewArgs )
  	{
  	    global $g_user;

  	    // Get players
        $players = $this->game->loadPlayersBasicInfos();
        self::watch( "players", $players );

        $this->page->begin_block( "alhambra_alhambra", "other_alhambra" );
        foreach( $players as $player_id => $player )
        {
            if( $player_id != $g_user->get_id() )
            {
                $this->page->insert_block( "other_alhambra", array( "PLAYER_ID" => $player['player_id'],
                                                            "PLAYER_NAME" => $player['player_name'] ) );
            }
        }

        $this->tpl['MY_ALHAMBRA'] = _("My Alhambra");
        $this->tpl['MY_MONEY'] = _("My money");
        $this->tpl['MY_STOCK'] = _("My stock");
        $this->tpl['SCORING_ROUND_AT_THE_END_OF_THIS_TURN'] = _("Scoring at the end of current turn!");

        if( $this->game->getGameStateValue('neutral_player') == 1 )
        {
          // Insert neutral player
          $this->page->insert_block( "other_alhambra", array( "PLAYER_ID" => 0,
            "PLAYER_NAME" => _("Dirk (neutral player)") ) );
        }
  	}
  }

?>
