<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Alhambra implementation : © Gregory Isabelli
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * alhambra.action.php
 *
 * alhambra main action entry point
 *
 */

class action_alhambra extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if( self::isArg( 'notifwindow') ) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
    } else {
      $this->view = "alhambra_alhambra";
      self::trace( "Complete reinitialization of board game" );
    }
  }


  public function acceptMoney()
  {
    self::setAjaxMode();
    $this->game->actAcceptMoney();
    self::ajaxResponse( );
  }


  public function takeMoney()
  {
    self::setAjaxMode();
    $raw = self::getArg("cardIds", AT_numberlist, true);
    $cardIds = explode(';', $raw);
    $this->game->actTakeMoney($cardIds);
    self::ajaxResponse();
  }


  public function buyBuilding()
  {
    self::setAjaxMode();
    $buildingId = self::getArg("buildingId", AT_posint, true );
    $raw = self::getArg("cardIds", AT_numberlist, true);
    $cardIds = explode(';', $raw);
    $this->game->actBuyBuilding($buildingId, $cardIds);
    self::ajaxResponse();
  }


public function transformAlhambraPlace()
{
self::setAjaxMode();
$building_id = self::getArg( "building", AT_posint, true );
$x = self::getArg( "x", AT_int, true );
$y = self::getArg( "y", AT_int, true );
$this->game->placeBuilding( $building_id, false, $x, $y );
self::ajaxResponse( );
}
public function transformAlhambraRemove()
{
self::setAjaxMode();
$building_to_remove = self::getArg( "remove", AT_posint, true );
$this->game->transformAlhambraRemove( $building_to_remove );
self::ajaxResponse( );
}

public function placeBuilding()
{
self::setAjaxMode();
$building_id = self::getArg( "building", AT_posint, true );
if( self::isArg( 'x' ) )    // place building in the alhambra
{
$x = self::getArg( "x", AT_int, true );
$y = self::getArg( "y", AT_int, true );
$this->game->placeBuilding( $building_id, true, $x, $y );
}
else
{
// place building in stock
$this->game->placeBuilding( $building_id, true );
}
self::ajaxResponse( );
}

public function giveneutral()
{
self::setAjaxMode();
$this->game->giveneutral();
self::ajaxResponse( );

}

}
