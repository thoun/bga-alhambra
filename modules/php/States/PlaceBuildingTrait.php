<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;
use ALH\Notifications;
use ALH\Stats;
use ALH\Log;


trait PlaceBuildingTrait {


  function argPlaceBuilding()
  {
    $player = Players::getActive();
    $board = $player->getBoard();
    $buildings = Buildings::getInLocation('bought');
    foreach($buildings as &$building){
      $building['availablePlaces'] = $board->getAvailablePlaces($building);
      $building['canGoToStock'] = true;
    }

    return [
      'buildings' => $buildings,
      'cancelable' => $player->hasSomethingToCancel(),
    ];
  }


  /*
   * Check the placing action against args
   */
  function checkPlaceBuilding($buildingId, $zone)
  {
    $args = $this->gamestate->state()['args'];
    if($this->gamestate->state()['name'] == 'placeLastBuildings')
      $args = $args['_private'][$this->getCurrentPlayerId()];
    $buildings = $args['buildings'];

    // Try to find the building
    $building = \array_reduce($buildings, function($carry, $building) use ($buildingId){
      return $carry ?? ($building['id'] == $buildingId? $building : null);
    }, null);

    if(is_null($building))
      throw new \feException("You cannot place this building");

    if($zone == 'stock' && !$building['canGoToStock'])
      throw new \feException("You cannot place this building in your stock");

    if($zone != 'stock' && !in_array($zone, $building['availablePlaces']))
      throw new \feException("You cannot place this building at this position");

    return $building;
  }


  function actPlaceBuildingOnStock($buildingId)
  {
    self::checkAction("placeBuilding");
    $building = $this->checkPlaceBuilding($buildingId, 'stock');
    $player = Players::getCurrent();
    $player->placeBuildingInStock($building);
    Log::insert($player, 'placeBuilding', ['building' => $building]);

    if($building['location'] == 'alam'){
      Stats::transform($player);
    }
    $this->endTurnOrPlaceBuildings();
  }



  function actPlaceBuildingOnAlhambra($buildingId, $x, $y)
  {
    self::checkAction("placeBuilding");
    $building = $this->checkPlaceBuilding($buildingId, ['x' => $x, 'y' => $y]);
    $player = Players::getCurrent();

    // Is there a piece already at this position ?
    $piece = Buildings::getAt($player->getId(), $x, $y);
    if(is_null($piece)){
      // No, that's just a usual place then
      Log::insert($player, 'placeBuilding', ['building' => $building]);
      $player->placeBuilding($building, $x, $y);
    } else {
      // Yes, we must first place this one to stock before placing this one instead
      Log::insert($player, 'swapBuildings', ['building' => $building, 'piece' => $piece, 'x' => $x, 'y' => $y]);
      $player->swapBuildings($building, $piece, $x, $y);
      Stats::transform($player);
    }

    $player->updateAlhambraStats();
    $this->endTurnOrPlaceBuildings();
  }


  function actGiveneutral()
  {
    self::checkAction('placeBuilding');
    // Get all buildings to place and place them into neutral player alhambra
    $buildings = Buildings::getInLocation('bought');
    $player = Players::getCurrent();
    Log::insert($player, 'giveToNeutral', ['buildings' => $buildings ]);
    Buildings::giveTilesToNeutral($buildings, false, $player);
    self::endTurnOrPlaceBuildings();
  }
}
