<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;
use ALH\Notifications;
use ALH\Stats;


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
    ];
  }


  /*
   * Check the placing action against args
   */
  function checkPlaceBuilding($buildingId, $zone)
  {
    $buildings = $this->gamestate->state()['args']['buildings'];
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
    $player = Players::getActive();
    $player->placeBuildingInStock($building);
    if($building['location'] == 'alam'){
      Stats::transform($player);
    }
    $this->endTurnOrPlaceBuildings();
  }



  function actPlaceBuildingOnAlhambra($buildingId, $x, $y)
  {
    self::checkAction("placeBuilding");
    $building = $this->checkPlaceBuilding($buildingId, ['x' => $x, 'y' => $y]);
    $player = Players::getActive();

    // Is there a piece already at this position ?
    $piece = Buildings::getAt($player->getId(), $x, $y);
    if(is_null($piece)){
      // No, that's just a usual place then
      $player->placeBuilding($building, $x, $y);
    } else {
      // Yes, we must first place this one to stock before placing this one instead
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
    $player = Players::getActive();
    Buildings::giveTilesToNeutral($buildings, false, $player);
    self::endTurnOrPlaceBuildings();
  }
}
