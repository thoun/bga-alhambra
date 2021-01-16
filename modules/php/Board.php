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
    return $this->buildings;
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
    if($bSkipFreeCheckAndHoles)
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

  /*
  function transformAlhambraRemove( $building_id )
  {
      self::checkAction( "transformAlhambra" );

      $player_id = self::getActivePlayerId();

      // Now we remove the building
      $this->buildings->moveCard( $building_id, 'stock', $player_id );
      $this->notifyAllPlayers( "placeBuilding", '',
                               array( "player_name" => self::getActivePlayerName(),
                                      "player" => $player_id,
                                      "building_id" => $building_id,
                                      "building" => $building,
                                      "stock" => 1,
                                      "removed" => 1
                                      ) );


      self::updateAlhambraStats( $player_id );

      self::incStat( 1, "transformation_nbr", $player_id );

      self::endTurnOrPlaceBuildings();
  }
  */


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




// Count players buildings (in Alhambra) by type
// (for all player if player_id = null)
// (for a single player otherwise)
function countPlayersBuildings( $player_id = null )
{
    $result = array();  // building_type => player => nbr
    $buildings = $this->buildings->getCardsInLocation( 'alamb', $player_id );
    foreach( $buildings as $building )
    {
        $building_type = $building['type'];
        if( $building_type != 0 )   // Filter fountains
        {
            if( $player_id == null )
            {
                if( ! isset( $result[ $building_type ] ) )
                    $result[ $building_type ] = array();

                $this_player_id = $building['location_arg'];
                if( ! isset( $result[ $building_type ][ $this_player_id ] ) )
                    $result[ $building_type ][ $this_player_id ] = 0;

                $result[ $building_type ][ $this_player_id ] ++;
            }
            else
            {
                if( ! isset( $result[ $building_type ] ) )
                    $result[ $building_type ] = 0;

                $result[ $building_type ] ++;
            }

        }
    }



    return $result;
}

function buildPlayerWallNet( $player_id )
{
    $net = array();
  $sql = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location_arg location_arg, ";
  $sql .= "card_x x, card_y y ";
  $sql .= "FROM building ";
  $sql .= " WHERE card_location='alamb' AND card_location_arg='$player_id' ";
  $dbres = self::DbQuery( $sql );

    while( $building = mysql_fetch_assoc( $dbres ) )
    {
        // Find walls of this building
        $building_tile_id = $building['type_arg'];
        $walls = $this->building_tiles[ $building_tile_id ]['wall'];
        $tile_x = $building['x'];
        $tile_y = $building['y'];

        foreach( $walls as $wall )
        {
            if( $wall == 0 )
            {
                $wallcoord1 = $tile_x.'x'.$tile_y;
                $wallcoord2 = ($tile_x+1).'x'.$tile_y;
            }
            else if( $wall == 1 )
            {
                $wallcoord1 = ($tile_x+1).'x'.$tile_y;
                $wallcoord2 = ($tile_x+1).'x'.($tile_y+1);
            }
            else if( $wall == 2 )
            {
                $wallcoord1 = ($tile_x+1).'x'.($tile_y+1);
                $wallcoord2 = $tile_x.'x'.($tile_y+1);
            }
            else if( $wall == 3 )
            {
                $wallcoord1 = $tile_x.'x'.($tile_y+1);
                $wallcoord2 = $tile_x.'x'.$tile_y;
            }

            // Add link "$wallcoord1 <-> $wallcoord2" in the net
            // Note: if the link is already present, remove it (double wall inside the alhambra does not count)
            if( !isset( $net[$wallcoord1] ) )
                $net[$wallcoord1] = array();
            if( !isset( $net[$wallcoord2] ) )
                $net[$wallcoord2] = array();

            // Add new wall
            $net[$wallcoord1][] = $wallcoord2;
            $net[$wallcoord2][] = $wallcoord1;
        }
    }


    // Remove doubles (= internal walls)
    foreach( $net as $key => $node )
    {
        $values_in_double = array_diff_key( $node , array_unique( $node ) );

        if( count( $values_in_double ) > 0 )
        {
            $net[$key] = array_diff( $node, $values_in_double );
        }
    }

    return $net;
}



function stPlaceBuilding($buildingId, $is_bought, $x = null, $y = null)
{
    if( $is_bought && $this->gamestate->checkPlayerAction('takeMoney', false) && self::getActivePlayerId()==self::getCurrentPlayerId())
    {
        throw new feException( self::_("You will be able to build this building at the end of your turn: now you must take money or buy another building."), true );
    }

    if( $is_bought )
        self::checkAction( "placeBuilding" );
    else
        self::checkAction( "transformAlhambra" );

    $bPlaceInStock = ( $x===null || $y===null );

    if( $bPlaceInStock && !$is_bought )
        throw new feException( 'moving building from stock to stock' );

    // Check if this building is in "to place" or "stock" zone of current player
    global $g_user;
    $building = $this->buildings->getCard( $building_id );
    $building['typedetails'] = $this->building_tiles[ $building['type_arg'] ];

    if( ! $building )
        throw new feException( "This building does not exists" );

    if( $is_bought )
    {
        if( $building['location'] != 'bought' )
            throw new feException( "You have not bought this building" );
    }
    else
    {
        if( $building['location'] != 'stock' || $building['location_arg'] != $g_user->get_id() )
            throw new feException( "You havent this building in stock" );
    }

    if( ! $bPlaceInStock )
    {
        $buildingAlreadyThere = null;

        // Get neighbours
      $sql = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location_arg location_arg, card_x, card_y ";
      $sql .= "FROM building ";
      $sql .= "WHERE card_x>='".($x-1)."' AND card_x<='".($x+1)."' ";
      $sql .= "AND card_y>='".($y-1)."' AND card_y<='".($y+1)."' ";
      $sql .= "AND card_location='alamb' AND card_location_arg='".$g_user->get_id()."' ";
      $dbres = self::DbQuery( $sql );
      $neighbours = array();
      while( $row = mysql_fetch_assoc( $dbres ) )
      {
          $neighbours[ $row['card_x'].'x'.$row['card_y'] ] = $row;

          if( $row['card_x']==$x && $row['card_y']==$y )
              $buildingAlreadyThere = $row;
      }

        $bSkipFreeCheckAndHoles = false;
        if( ! $is_bought )
        {
            if( $buildingAlreadyThere )
                $bSkipFreeCheckAndHoles = true; // in case the building is coming from the stock, it can replace an already placed building
        }
        self::canPlaceAlhambraPiece( $building, $x, $y, $neighbours, $bSkipFreeCheckAndHoles );     // Note: throw an exception in case of error

        // Okay, now we are sure that we are allowed to place this building here.

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

        // Move the building card to alhambra, right place.
        $sql = "UPDATE building ";
        $sql .= "SET card_location='alamb', card_location_arg='".$g_user->get_id()."', ";
        $sql .= "card_x='$x', card_y='$y' ";
        $sql .= "WHERE card_id='$building_id' ";
        self::DbQuery( $sql );

        // Notify
        $this->notifyAllPlayers( "placeBuilding", clienttranslate('${player_name} places a ${building_type_pre}${building_name}${building_type_post}'),
                                 array( "i18n" => array( "building_name" ),
                                        "player_name" => self::getCurrentPlayerName(),
                                        "player" => $g_user->get_id(),
                                        "building_id" => $building_id,
                                        "building" => $building,
                                        "building_type_pre" => '<span class="buildingtype buildingtype_'.$building['typedetails']['type'].'">',
                                        "building_type_post" => '</span>',
                                        "building_name" => ( $this->building_types[ $building['typedetails']['type'] ] ),
                                        "x" => $x,
                                        "y" => $y
                                        ) );

    }

    self::updateAlhambraStats( $g_user->get_id() );

    if( ! $is_bought )
        self::incStat( 1, "transformation_nbr", $g_user->get_id() );

    self::endTurnOrPlaceBuildings();
}

}
