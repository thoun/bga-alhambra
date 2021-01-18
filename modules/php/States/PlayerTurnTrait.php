<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;
use ALH\Notifications;
use ALH\Stats;


trait PlayerTurnTrait {
  /************************************
  **** NEXT PLAYER / START OF TURN ****
  ************************************/
  function stNextPlayer()
  {
    $pId = Globals::getTurnNumber() == 0? self::getActivePlayerId() : self::activeNextPlayer();
    self::giveExtraTime($pId);

    if(Globals::isFirstPlayer($pId)){
      Globals::startNewTurn();
    }

    Money::fillPool();
    $bEndOfGame = Buildings::fillPool();

    if(Globals::isScoringRound())
      $this->scoringRound();   // Trigger a scoring round if needed

/*
TODO

        if( $bEndOfGame )
        {
            $this->notifyAllPlayers( "endOfGame", clienttranslate('The last building has been drawn: this is the end of the game!'), array() );
            $this->gamestate->nextState( 'notEnoughBuilding');
        }
        else
*/
      $this->gamestate->nextState( 'playerTurn');
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
        throw new \feException( "This money card is not available in your hand" );
    }

    // Check if building was available
    if($building['location'] != 'buildingsite')
      throw new \feException( "This building isn't available for buying" );

    // Check that the cards are in the good money
    $amount = 0;
    foreach($cards as $card){
      $amount += $card['value'];
      if($card['type'] != $building['pos']){
        throw new \feException( "Try to pay with money which does not correspond to building" );
      }

      Money::move($card['id'], 'discard', 0);
    }

    // Check the total amount
    if($amount < $building['cost'])
      throw new \feException( "Not enough money to buy this building" );

    // Buy the building, ie place it in player's "to place" location, and notify
    Buildings::move($building['id'], 'bought', $building['pos']);
    Notifications::buyBuilding($player, $cards, $building);
    $player->updateMoneyCount();


    if($amount == $building['cost']){
      Stats::exactAmount($player);
      Notifications::exactAmount($player);
      $this->gamestate->nextState( "replay" );
    }
    else {
      // More money than expected => go to "place building" step
      $this->gamestate->nextState( "buildingToPlace" );
    }
  }



  /**********************
  ****  END OF TURN  ****
  **********************/

  // If there are building to place, go to corresponding step
  // Otherwise end current player turn
  function endTurnOrPlaceBuildings()
  {
    $player = Players::getCurrent();
    $newState = Buildings::countInLocation("bought") == 0? "endTurn" : "buildingToPlace";
    $this->gamestate->nextState($newState);

  /*
    TODO
      global $g_user;
      $state = $this->gamestate->state();

      if( $state['name'] == 'placeLastBuildings' )
      {
          $all_buildings = $this->buildings->getCardsInLocation( 'bought' );
          $buildings_to_place = 0;

          foreach( $all_buildings as $building )
          {
              if( self::getGameStateValue( 'lastbuilding_'.$building['location_arg'] ) == $g_user->get_id() )
                  $buildings_to_place ++;
          }

          if( $buildings_to_place == 0 )
          {
              // Specific case: this is the last turn and this player placed all the building he gets
              $this->gamestate->setPlayerNonMultiactive( $g_user->get_id(), "noMoreBuilding" );
          }


      }
      */
  }

}
