<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;


/*
 * Handle first step of game
 */
trait StartOfGameTrait
{
  /****************************
  **** INITIAL MONEY STATE ****
  ****************************/
  function argInitialMoney()
  {
    return Players::getAll()->assocMap(function($player){
      return $player->getMoneyCards();
    });
  }

  function stInitialMoney()
  {
    $this->gamestate->setAllPlayersMultiactive();
  }

  function actAcceptMoney()
  {
    self::checkAction("acceptMoney");
    $this->gamestate->setPlayerNonMultiactive(self::getCurrentPlayerId(), '');
  }
}
