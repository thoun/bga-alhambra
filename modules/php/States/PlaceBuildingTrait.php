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

    // TODO
    /*
    self::updateAlhambraStats( $g_user->get_id() );

      if( ! $is_bought )
    */

    $this->endTurnOrPlaceBuildings();
  }

  /*
  TODO
  if( $buildingAlreadyThere )
  {
      if( $buildingAlreadyThere['card_x']==0 && $buildingAlreadyThere['card_y']==0 )
          throw new feException( self::_("You can't replace the fountain"), true, true );

      // The building here must be moved to player's stock
      $this->buildings->moveCard( $buildingAlreadyThere['id'], 'stock', $g_user->get_id() );

      $this->notifyAllPlayers( "placeBuilding", '',
                               array( "player_name" => self::getCurrentPlayerName(),
                                      "player" => $g_user->get_id(),
                                      "building_id" => $buildingAlreadyThere['id'],
                                      "building" => $buildingAlreadyThere,
                                      "stock" => 1
                                      ) );
  }
*/
}
