<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;
use ALH\Notifications;
use ALH\Stats;
use ALH\Log;


trait PlayerTurnTrait {
  /************************************
  **** NEXT PLAYER / START OF TURN ****
  ************************************/
  function stNextPlayer()
  {
    $pId = (Globals::getTurnNumber() == 0 && Log::DB()->count() == 0)? self::getActivePlayerId() : self::activeNextPlayer();
    self::giveExtraTime($pId);
    Notifications::startNewTurn();

    if(Globals::isFirstPlayer($pId)){
      Globals::startNewTurn();
    }

    Money::fillPool();
    $bEndOfGame = Buildings::fillPool();

    if(Globals::isScoringRound()){
      $this->scoringRound();   // Trigger a scoring round if needed, must be called after filling pool
    }

    $transition = 'playerTurn';
    if($bEndOfGame) {
      Notifications::endOfGame();
      $transition = 'notEnoughBuilding';
    }

    $this->gamestate->nextState($transition);
  }



  function argPlayerTurn()
  {
    $player = Players::getActive();
    $board = $player->getBoard();

    // Compute removable building
    $buildings = $board->getBuildings();
    foreach($buildings as &$building){
      $building['availablePlaces'] = [];
      if($board->canBeRemoved($building))
        $building['canGoToStock'] = true;
    }

    // Compute buildings that can be moved from stock
    $stock = $player->getStock();
    foreach($stock as &$building){
      $building['availablePlaces'] = $board->getAvailablePlaces($building, true);
      $building['canGoToStock'] = false;
    }

    return [
      'buildings' => array_merge($buildings, $stock),
      'buildingsite' => Buildings::getInLocation('buildingsite'),
      'cancelable' => $player->hasSomethingToCancel(),
    ];
  }


  /*******************
  **** TAKE MONEY ****
  *******************/
  function actTakeMoney($cardIds)
  {
    self::checkAction("takeMoney");

    $player = Players::getActive();
    $cards = Money::get($cardIds);

    // Check they all are in "pool"
    foreach($cards as $card) {
      if($card['location'] != 'pool')
        throw new feException( "This money card is not available in money pool" );
    }

    // Check that player has the right to pick all these cards
    $total = \array_reduce($cards, function($carry, $card){ return $carry + $card['value']; }, 0);
    if( count($cards) > 1 && $total > 5)
      throw new feException( "You can't take several cards with a value exceeding 5" );

    // Move the cards in player's hand and notify
    $player->takeMoney($cards, $total);
    Log::insert($player, 'takeMoney', ['cards' => $cards, 'total' => $total]);

    // This action ends player turn
    $this->endTurnOrPlaceBuildings();
  }




  /**********************
  **** BUY BUILDING  ****
  **********************/
  function actBuyBuilding($bId, $cardIds)
  {
    self::checkAction("buyBuilding");
    $player = Players::getActive();
    $cards = Money::get($cardIds);
    $building = Buildings::get($bId);

    // Check that theses cards exists and are in player hand
    foreach($cards as $card){
      if( $card['location'] != 'hand' || $card['pId'] != $player->getId())
        throw new \BgaUserException(self::_("This money card is not available in your hand"));
    }

    // Check if building was available
    if($building['location'] != 'buildingsite')
      throw new \BgaUserException(self::_("This building isn't available for buying" ));

    // Check that the cards are in the good money
    $amount = 0;
    foreach($cards as $card){
      $amount += $card['value'];
      if($card['type'] != $building['pos']){
        throw new \BgaUserException(self::_("Try to pay with money which does not correspond to building" ));
      }

      Money::move($card['id'], 'discard', 0);
    }

    // Check the total amount
    if($amount < $building['cost'])
      throw new \BgaUserException(self::_("Not enough money to buy this building" ));

    // Buy the building, ie place it in player's "to place" location, and notify
    Buildings::move($building['id'], 'bought', $building['pos']);
    Notifications::buyBuilding($player, $cards, $building);
    Log::insert($player, 'buyBuilding', ['cards' => $cards, 'building' => $building]);
    $player->updateMoneyCount();


    if($amount == $building['cost']){
      Stats::exactAmount($player);
      Notifications::exactAmount($player);
      $this->gamestate->nextState( "replay" );
    }
    else {
      // More money than expected => go to "place building" step
      $this->gamestate->nextState("buildingToPlace");
    }
  }



  /**********************
  ****  END OF TURN  ****
  **********************/

  function argConfirmTurn()
  {
    $pId = $this->getActivePlayerId();
    return ['nActions' => count(Log::getLastActions($pId))];
  }

  // If there are building to place, go to corresponding step
  // Otherwise end current player turn
  function endTurnOrPlaceBuildings()
  {
    $player = Players::getCurrent();

    $state = $this->gamestate->state();
    if($state['name'] != 'placeLastBuildings'){
      $newState = Buildings::countInLocation("bought") == 0? "endTurn" : "buildingToPlace";
      $this->gamestate->nextState($newState);
    } else {
      $args = $this->argPlaceLastBuilding();
      $buildings = $args['_private'][$player->getId()]['buildings'];
      if(empty($buildings)){
        $this->gamestate->setPlayerNonMultiactive($player->getId(), "noMoreBuilding" );
      } else {
        Notifications::updatePlacementOptions($player, $buildings);
      }
    }
  }


  function cancelTurn()
  {
    self::checkAction("restart");
    $player = Players::getCurrent();
    $notifIds = Log::clearTurn($player->getId());
    Notifications::clearTurn($player, $notifIds);

    $this->gamestate->nextState("restart");
  }

  function confirmTurn()
  {
    self::checkAction("confirm");
    $this->gamestate->nextState("confirm");
  }

}
