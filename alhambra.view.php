<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Alhambra implementation : ©  Gregory Isabelli
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * alhambra.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in emptygame_emptygame.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
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
	    // Get players & players number
      $players = $this->game->loadPlayersBasicInfos();
      $players_nbr = count( $players );

      $this->tpl['MY_ALHAMBRA'] = _("My Alhambra");
      $this->tpl['MY_MONEY'] = _("My money");
      $this->tpl['MY_STOCK'] = _("My stock");

      /*********** Place your code below:  ************/

      /*
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

      $this->tpl['SCORING_ROUND_AT_THE_END_OF_THIS_TURN'] = _("Scoring at the end of current turn!");

      if( $this->game->getGameStateValue('neutral_player') == 1 )
      {
        // Insert neutral player
        $this->page->insert_block( "other_alhambra", array( "PLAYER_ID" => 0,
          "PLAYER_NAME" => _("Dirk (neutral player)") ) );
      }
      */

      /*********** Do not change anything below this line  ************/
	}
}
