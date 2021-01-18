<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;
use ALH\Notifications;
use ALH\Stats;


trait EndOfGameTrait {
  function stLastBuildingPick()
  {
    // Last building pick: buildings that are still on the building site are gived to players that has the biggest
    // qt of corresponding money

    $buildings = Buildings::getInLocation("buildingsite");
    $sites = [];
    foreach($buildings as $building){
      $sites[$building['pos']] = $building;
    }

    // Fill money players
    $moneyPlayers = [];   // player_id => money_type => money in hand
    foreach(Players::getAll() as $pId => $player){
      $moneyPlayers[$pId] = [];
      for($type = 1; $type <= 4; $type++)
        $moneyPlayers[$pId][$type] = 0;

      foreach($player->getMoneyCards() as $card){
        $moneyPlayers[$pId][$card['type']] += $card['value'];
      }
    }


    $needToBeActivated = [];

    // For each money type, see who is the best one
    for($type = 1; $type <= 4; $type++) {
      // Is there really a building to pick
      if(!isset($sites[$type]))
        continue;
      $building = $sites[$type];

      // Compute max
      $max = 0;
      $maxPlayer = null;
      $bTie = false;
      foreach($moneyPlayers as $pId => $player){
        if($player[$type] > $max){
          $max = $player[$type];
          $maxPlayer = $pId;
          $bTie = false;
        }
        else if($player[$type] == $max)
          $bTie = true;
      }

      // No one has cards or tie => no one get the building
      if($max == 0 || $bTie){
        Notifications::noGetBuilding($building, $type, $bTie);
        continue;
      }

      // Okay, max_player take this building
      // Place it in player's "to place" location
      Buildings::move($building['id'], 'bought', $type);
      $player = Players::get($maxPlayer);
      Notifications::getFreeBuilding($player, $building, $type);

      $needToBeActivated[] = $maxPlayer;
      Globals::setLastBuilding($type, $maxPlayer);
    }

    // Update players active
    Players::DB()->update(['player_is_multiactive' => 1])->whereIn($needToBeActivated)->run();
    // Jump to next state
    $transition = empty($sites)? "noMoreBuilding" : "buildingToPlace";
    $this->gamestate->nextState($transition);
  }


  function argPlaceLastBuilding()
  {
    $args = [ '_private' => [] ];

    foreach(Players::getAll() as $player){
      $board = $player->getBoard();
      $buildings = Buildings::getInLocation('bought');
      $result = ['remove' => [], 'buildings' => [] ];
      foreach($buildings as &$building){
        // Remove from "bought" the buildings that are not from you
        if(Globals::getLastBuilding($building['pos']) != $player->getId()){
          $result['remove'][] = $building;
          continue;
        }

        $building['availablePlaces'] = $board->getAvailablePlaces($building);
        $building['canGoToStock'] = true;
        $result['buildings'][] = $building;
      }

      $args['_private'][$player->getId()] = $result;
    }

    return $args;
  }



  function stPlaceLastBuildings()
  {
    $this->gamestate->updateMultiactiveOrNextState("noMoreBuilding");
  }

  function stLastScoringRound()
  {
    Globals::setScoringRound(3);
    $this->scoringRound();
    $this->gamestate->nextState();
  }
}
