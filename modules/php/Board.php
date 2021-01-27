<?php
namespace ALH;
use ALH\Helpers\Utils;
use Alhambra;

/*
 * Class that will handle everything about the alhambra of a player
 */
class Board {
  protected $board;
  protected $buildings;

  protected static $directions = [
    ['x' =>  0, 'y' => -1], // NORTH
    ['x' =>  1, 'y' => 0], // EAST
    ['x' =>  0, 'y' => 1], // SOUTH
    ['x' => -1, 'y' => 0], // WEST
  ];

  protected static $directionsWithDiagonals = [
    ['x' => -1, 'y' => -1], // NW
    ['x' =>  0, 'y' => -1], // N
    ['x' =>  1, 'y' => -1], // NE
    ['x' =>  1, 'y' =>  0], // E
    ['x' =>  1, 'y' =>  1], // SE
    ['x' =>  0, 'y' =>  1], // S
    ['x' => -1, 'y' =>  1], // SW
    ['x' => -1, 'y' =>  0], // W
  ];

  public function __construct($pId)
  {
    $this->board = [];
    $this->buildings = Buildings::getInLocation('alam', $pId);
    foreach($this->buildings as $building){
      $this->addBuilding($building);
    }
  }

  public function getUiData()
  {
    return [
      'buildings' => $this->buildings,
      'stats' => $this->getBuildingCounts(),
      'wall' => max(0, count($this->getLongestWall()) - 1),
    ];
  }
  public function getBuildings()
  {
    return $this->buildings;
  }


  public function addBuilding($building)
  {
    $this->board[$building['x'] .'x'. $building['y'] ] = $building;
  }

  public function getAt($x, $y)
  {
    return $this->board[$x .'x' .$y] ?? null;
  }

  public function getInDir($x, $y, $dir)
  {
    return $this->getAt($x + $dir['x'], $y + $dir['y']);
  }

  public function getPosInDir($pos, $dir)
  {
    return [
      'x' => $pos['x'] + $dir['x'],
      'y' => $pos['y'] + $dir['y'],
    ];
  }

  public function isFree($x, $y = null)
  {
    if(\is_array($x)){
      $y = $x['y'];
      $x = $x['x'];
    }
    return is_null($this->getAt($x, $y));
  }

  public function isFreeInDir($x, $y, $dir)
  {
    return $this->isFree($x + $dir['x'], $y + $dir['y']);
  }


  /*
   * Test if you can place a new piece in position x,y in player alhambra.
   * If bSkipFreeCheckAndHoles = true, we don't make the free place test & the hole test (useful for building replacement)
   */
  function canPlaceAlhambraPiece($building, $x, $y, $bSkipFreeCheckAndHoles = false )
  {
    if((!$bSkipFreeCheckAndHoles && !$this->isFree($x, $y)) || ($x == 0 && $y == 0))
      return false;

    // At least one neighbour
    $hasNeighbour = array_reduce(self::$directions, function($hasNeighbour, $dir) use ($x,$y){
      return $hasNeighbour || !$this->isFreeInDir($x, $y, $dir);
    }, false);

    if(!$hasNeighbour)
      return false;


    // Check walls
    $isReachable = false;
    foreach(self::$directions as $dirId => $dir){
      $neighbour = $this->getInDir($x, $y, $dir);
      if(is_null($neighbour))
        continue;

      $oppositeDirId = ($dirId + 2) % 4;
      $hasWall = in_array($dirId, $building['wall']);
      $neighbourHasWall = in_array($oppositeDirId, $neighbour['wall']);

      if($hasWall XOR $neighbourHasWall)
        return false;

      if(!$neighbourHasWall)
        $isReachable = true; // Can walk from the fountain using this neighbour
    }
    if(!$isReachable)
      return false;


    // Check holes now
    if(!$this->isFree($x, $y) && $bSkipFreeCheckAndHoles)
      return true;

    // Compute holes in all 8 directions
    $holes = array_map(function($dir) use ($x,$y){
      return $this->isFreeInDir($x,$y,$dir);
    }, self::$directionsWithDiagonals);

    // Compute nbr of changes (including wrapping at the end of array)
    $nbChange = 0;
    for($direction = 0; $direction < 8; $direction++){
      $previousDirection = ($direction + 7) % 8;

      if($holes[$direction] XOR $holes[$previousDirection])
        $nbChange ++;
    }

    // if we starts from direction 0 and go to 7, we must change 1 time from "hole => building" and one time from "building => hole"
    if($nbChange != 2)
      return false;

    return true;
  }


  /*
   * Get the set of free places around existing buildings
   */
  function getFreePlaces($bSkipFreeCheckAndHoles = false)
  {
    $places = [];
    foreach($this->buildings as $building){
      foreach(self::$directions as $dir){
        $pos = $this->getPosInDir($building, $dir);
        if(($this->isFree($pos) || $bSkipFreeCheckAndHoles) && !in_array($pos, $places)){
          $places[] = $pos;
        }
      }
    }

    return $places;
  }

  /*
   * Get the set of available places to place a given building from stock/bought
   */
  function getAvailablePlaces($building, $bSkipFreeCheckAndHoles = false)
  {
    $freePlaces = $this->getFreePlaces($bSkipFreeCheckAndHoles);
    Utils::filter($freePlaces, function($place) use ($building, $bSkipFreeCheckAndHoles){
      return $this->canPlaceAlhambraPiece($building, $place['x'], $place['y'], $bSkipFreeCheckAndHoles);
    });
    return $freePlaces;
  }


  /*
   * Check if a building can be removed or not
   */
  function canBeRemoved($building)
  {
    if($building['type'] == FONTAIN)
      return false;

    $x = $building['x'];
    $y = $building['y'];

    // We must check that no "hole" is created
    // Note: a hole is created only if there are buildings in four directions (N/E/S/W) => very simple to check
    $neighbours = 0;
    foreach(self::$directions as $dir){
      $neighbours += $this->isFreeInDir($x, $y, $dir)? 0 : 1;
    }

    if($neighbours == 4)
      return false;

    // We must check that it is still possible to go eveywhere in the alhambra from the foutain without this building
    // => we have no many choices at this step than reconstitue the full alhambra net and make this check
    $graph = new Graph($this->buildAlhambraNet($x.'x'.$y));
    if(!$graph->isConnex())
      return false;

    return true;
  }


  // Build player alhambra net with node id = coords and link = footpath from one to another
  // $exceptCoord allows to ignore one node, useful to test if removal is possible
  function buildAlhambraNet($exceptCoord = null)
  {
    $net = [];
    foreach($this->buildings as $building){
      $coord = $building['x'].'x'.$building['y'];
      if($coord == $exceptCoord)
        continue;


      $net[$coord] = [];
      foreach(self::$directions as $dirId => $dir){
        if(in_array($dirId, $building['wall']))
          continue;

        $pos = $this->getPosInDir($building, $dir);
        $neighbour = $pos['x'].'x'.$pos['y'];
        if(isset($net[$neighbour]) && $neighbour != $exceptCoord) {
          $net[$neighbour][] = $coord;
          $net[$coord][] = $neighbour;
        }
      }
    }

    return $net;
  }



  protected function buildPlayerWallNet()
  {
    $net = [];
    foreach($this->buildings as $building){
      $x = $building['x'];
      $y = $building['y'];
      foreach($building['wall'] as $wall){
        $startX = $x + ($wall == 1? 1 : 0);
        $endX = $x + 1 - ($wall == 3? 1 : 0);
        $startY = $y + ($wall == 2? 1 : 0);
        $endY = $y + 1 - ($wall == 0? 1 : 0);

        $wallCoord1 = $startX.'x'.$startY;
        $wallCoord2 = $endX.'x'.$endY;

        // Add link "$wallcoord1 <-> $wallcoord2" in the net
        // Note: if the link is already present, remove it (double wall inside the alhambra does not count)
        if(!isset($net[$wallCoord1]))  $net[$wallCoord1] = [];
        if(!isset($net[$wallCoord2]))  $net[$wallCoord2] = [];

        if(in_array($wallCoord1, $net[$wallCoord2])){
          // Remove existing wall
          $net[$wallCoord1] = array_diff($net[$wallCoord1], [$wallCoord2]);
          $net[$wallCoord2] = array_diff($net[$wallCoord2], [$wallCoord1]);
        } else {
          // Add new wall
          $net[$wallCoord1][] = $wallCoord2;
          $net[$wallCoord2][] = $wallCoord1;
        }
      }
    }

    return $net;
  }

  function getLongestWall()
  {
    $graph = new Graph($this->buildPlayerWallNet());
    return $graph->longestPath();
  }


  // Count players buildings (in Alhambra) by type
  function getBuildingCounts()
  {
    $result = [
      PAVILLON => 0,
      SERAGLIO => 0,
      ARCADE => 0,
      CHAMBER => 0,
      GARDEN => 0,
      TOWER => 0,
    ];

    foreach($this->buildings as $building){
      $type = $building['type'];
      if($type == FONTAIN)
        continue;

      $result[$type]++;
    }

    return $result;
  }
}
