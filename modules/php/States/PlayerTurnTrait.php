<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;


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


/*
TODO
        if( self::getGameStateValue( 'scoringAtTheEndOfTurn' ) > 0 )
            self::scoringRound();   // Trigger a scoring round if needed

        if( $bEndOfGame )
        {
            $this->notifyAllPlayers( "endOfGame", clienttranslate('The last building has been drawn: this is the end of the game!'), array() );
            $this->gamestate->nextState( 'notEnoughBuilding');
        }
        else
*/
      $this->gamestate->nextState( 'playerTurn');
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




  // If there are building to place, go to corresponding step
  // Otherwise end current player turn
  function endTurnOrPlaceBuildings()
  {
    $this->gamestate->nextState("endTurn");
  /*

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
      else
      {
          $buildings_to_place = $this->buildings->countCardInLocation( "bought" );

          if( $buildings_to_place == 0 )
          {
              $this->gamestate->nextState( "endTurn" );   // Normal case
          }
          else
          {
              $this->gamestate->nextState( "buildingToPlace" );
          }
      }
      */
  }

}
