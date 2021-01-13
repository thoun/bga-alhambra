<?php
namespace ALH;
use Alhambra;

/*
 * Buildings
 *
 * `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 * `card_type` varchar(16) NOT NULL,
 * `card_type_arg` int(11) NOT NULL,
 * `card_location` varchar(16) NOT NULL,
 * `card_location_arg` int(11) NOT NULL,
 * `card_x` int(11) DEFAULT NULL COMMENT 'place in the alhambra',
 * `card_y` int(11) DEFAULT NULL COMMENT 'place in the alhambra',
 */
class Buildings extends \ALH\Helpers\Deck
{
  protected static $table = 'building';
  protected static $deck = null;
  public static function init()
  {
    self::$deck = self::getNew("module.common.deck");
    self::$deck->init(self::$table);
  }

  protected static function cast($row)
  {
    // Type arg contains unique id of building, static info are in $buildings
    $data = self::$buildings[$row['type_arg']];

    $data['id'] = (int) $row['id'];
    $data['location'] = $row['location'];
    $data['x'] = $row['card_x'] ?? null;
    $data['y'] = $row['card_y'] ?? null;

    if($row['location'] == "hand")
      $data['pId'] = (int) $row['location_arg'];

    if(in_array($row['location'], ['buildingsite', 'bought']))
      $data['pos'] = (int) $row['location_arg'];

    return $data;
  }

  protected static $buildings = [
    // 6 startings fountains
    0 => [ "type" => FONTAIN, "cost" => 0, "wall" => [] ],
    1 => [ "type" => FONTAIN, "cost" => 0, "wall" => [] ],
    2 => [ "type" => FONTAIN, "cost" => 0, "wall" => [] ],
    3 => [ "type" => FONTAIN, "cost" => 0, "wall" => [] ],
    4 => [ "type" => FONTAIN, "cost" => 0, "wall" => [] ],
    5 => [ "type" => FONTAIN, "cost" => 0, "wall" => [] ],

    // Pavillon (blue)
    6 => [ "type" => PAVILLON, "cost" => 2, "wall" => [ 0,1,3 ] ],
    7 => [ "type" => PAVILLON, "cost" => 3, "wall" => [ 2,3 ] ],
    8 => [ "type" => PAVILLON, "cost" => 4, "wall" => [ 1,2 ] ],
    9 => [ "type" => PAVILLON, "cost" => 5, "wall" => [ 0,3 ] ],
    10 => [ "type" => PAVILLON, "cost" => 6, "wall" => [ 0 ] ],
    11 => [ "type" => PAVILLON, "cost" => 7, "wall" => [ 1 ] ],
    12 => [ "type" => PAVILLON, "cost" => 8, "wall" => [] ],

    // Red (seraglio)
    13 => [ "type" => SERAGLIO, "cost" => 3, "wall" => [ 1,2,3 ] ],
    14 => [ "type" => SERAGLIO, "cost" => 4, "wall" => [ 0,1 ] ],
    15 => [ "type" => SERAGLIO, "cost" => 5, "wall" => [ 2,3 ] ],
    16 => [ "type" => SERAGLIO, "cost" => 6, "wall" => [ 1,2 ] ],
    17 => [ "type" => SERAGLIO, "cost" => 7, "wall" => [ 3 ] ],
    18 => [ "type" => SERAGLIO, "cost" => 8, "wall" => [ 2 ] ],
    19 => [ "type" => SERAGLIO, "cost" => 9, "wall" => [] ],

    // Brown (arcades)
    20 => [ "type" => ARCADE, "cost" => 4, "wall" => [ 0,1,2 ] ],
    21 => [ "type" => ARCADE, "cost" => 5, "wall" => [ 0,3 ] ],
    22 => [ "type" => ARCADE, "cost" => 6, "wall" => [ 2,3 ] ],
    23 => [ "type" => ARCADE, "cost" => 6, "wall" => [ 0,1 ] ],
    24 => [ "type" => ARCADE, "cost" => 7, "wall" => [ 1,2 ] ],
    25 => [ "type" => ARCADE, "cost" => 8, "wall" => [ 0 ] ],
    26 => [ "type" => ARCADE, "cost" => 8, "wall" => [ 1 ] ],
    27 => [ "type" => ARCADE, "cost" => 9, "wall" => [] ],
    28 => [ "type" => ARCADE, "cost" => 10, "wall" => [] ],

    // Chambers (white)
    29 => [ "type" => CHAMBER, "cost" => 5, "wall" => [ 0,2,3 ] ],
    30 => [ "type" => CHAMBER, "cost" => 6, "wall" => [ 1,2 ] ],
    31 => [ "type" => CHAMBER, "cost" => 7, "wall" => [ 2,3 ] ],
    32 => [ "type" => CHAMBER, "cost" => 7, "wall" => [ 0,1 ] ],
    33 => [ "type" => CHAMBER, "cost" => 8, "wall" => [ 0,3 ] ],
    34 => [ "type" => CHAMBER, "cost" => 9, "wall" => [ 3 ] ],
    35 => [ "type" => CHAMBER, "cost" => 9, "wall" => [ 2 ] ],
    36 => [ "type" => CHAMBER, "cost" => 10, "wall" => [] ],
    37 => [ "type" => CHAMBER, "cost" => 11, "wall" => [] ],

    // Green (garden)
    38 => [ "type" => GARDEN, "cost" => 6, "wall" => [ 1,2,3 ] ],
    39 => [ "type" => GARDEN, "cost" => 7, "wall" => [ 0,2,3 ] ],
    40 => [ "type" => GARDEN, "cost" => 8, "wall" => [ 2,3 ] ],
    41 => [ "type" => GARDEN, "cost" => 8, "wall" => [ 0,1 ] ],
    42 => [ "type" => GARDEN, "cost" => 8, "wall" => [ 0,3 ] ],
    43 => [ "type" => GARDEN, "cost" => 9, "wall" => [ 1 ] ],
    44 => [ "type" => GARDEN, "cost" => 10, "wall" => [ 0 ] ],
    45 => [ "type" => GARDEN, "cost" => 10, "wall" => [ 3 ] ],
    46 => [ "type" => GARDEN, "cost" => 10, "wall" => [] ],
    47 => [ "type" => GARDEN, "cost" => 11, "wall" => [] ],
    48 => [ "type" => GARDEN, "cost" => 12, "wall" => [ 2 ] ],

    // Towers (purple)
    49 => [ "type" => TOWER, "cost" => 7, "wall" => [ 0,1,3 ] ],
    50 => [ "type" => TOWER, "cost" => 8, "wall" => [ 0,1,2 ] ],
    51 => [ "type" => TOWER, "cost" => 9, "wall" => [ 1,2 ] ],
    52 => [ "type" => TOWER, "cost" => 9, "wall" => [ 0,1 ] ],
    53 => [ "type" => TOWER, "cost" => 9, "wall" => [ 0,3 ] ],
    54 => [ "type" => TOWER, "cost" => 10, "wall" => [ 3 ] ],
    55 => [ "type" => TOWER, "cost" => 11, "wall" => [ 0 ] ],
    56 => [ "type" => TOWER, "cost" => 11, "wall" => [ 2 ] ],
    57 => [ "type" => TOWER, "cost" => 11, "wall" => [] ],
    58 => [ "type" => TOWER, "cost" => 12, "wall" => [] ],
    59 => [ "type" => TOWER, "cost" => 13, "wall" => [ 1 ] ]
  ];

  /*******************
  ****** SETUP *******
  *******************/
  public function setupNewGame($players)
  {
    // Insert by hand

    $query = self::DB()->multipleInsert(['card_type', 'card_type_arg', 'card_location', 'card_location_arg', 'card_x', 'card_y']);
    $values = [];

    // Start with fountains
    $id = 0;
    foreach ($players as $pId => $player) {
      $values[] = [ 0, $id++, 'alam', $pId, 0, 0];
    }

    // Other buildings
    foreach(self::$buildings as $tId => $tile){
      if($tile['type'] == 0)
        continue; // Fountains: already done

      $values[] = [$tile['type'], $tId, 'deck', 0, 'NULL', 'NULL'];
    }
    $query->values($values);

    // Shuffle building deck
    self::shuffle();

    /*
    TODO
    if( $bIsNeutral )
    {
        $this->giveTilesToNeutral(0);   // 6 initial tile to neutral player
    }
    */
  }



  /*******************
  ***** GETTERS ******
  *******************/
  /*
   * Overwrite some deck getters since they can't handle custom fields
   */
  protected function getSelectQuery(){
    return self::DB()->select([
      'id' => 'card_id',
      'location' => 'card_location',
      'location_arg' => 'card_location_arg',
      'type_arg' => 'card_type_arg',
      'x' => 'card_x',
      'y' => 'card_y'
    ]);
  }

  public function getInLocation($location, $location_arg = null, $orderBy = null){
    $query = self::getSelectQuery()->where('card_location', $location);

    if(!is_null($location_arg)){
      $query = $query->where('card_location_arg', $location_arg);
    }
    if(!is_null($orderBy)){
      $query = $query->orderBy($orderBy);
    }

    return $query->get(false)->toArray(); // False to force to have an array even if only one value
  }


  public function get($buildingId, $raiseException = true)
  {
    $building = self::getSelectQuery()->where('card_id', $buildingId)->get(true);
    if(is_null($building) && $raiseException)
      throw new \feException("This building does not exists" );
    return $building;
  }


  /*
   * getUiData : get visible cards
   */
  public function getUiData()
  {
    return [
      'buildingsite' => self::getInLocation('buildingsite'),
      'count' => self::countInLocation('deck'),
      'toPlace' => self::getInLocation('bought'),
    ];
  }



  /*
   * Fill the building pool to 4 buildings
   */
  public function fillPool()
  {
    $buildings = self::getInLocation("buildingsite");
    $nBuildings = count($buildings);

    // Get the free places
    $freeSpots = [1 => 1, 2 => 2, 3 => 3, 4 => 4];
    foreach($buildings as $building){
      unset($freeSpots[ $building['pos'] ]);
    }

    $newBuildings = [];
    $bEndOfGame = false;
    while($nBuildings < 4) {
      // Get the first free spot
      $spot = array_shift($freeSpots);
      if(is_null($spot))
        throw new \feException( "Fatal error: no more free places for buildings in building site" );

      $newbuilding = self::pickForLocation("deck", "buildingsite", $spot);
      if(is_null($newbuilding)) {
        // No more building ? End of game
        // Note: in French edition, end of game is triggered when deck is empty, while on original edition it is triggered when there is a free space left
        $bEndOfGame = true;
        $nBuildings = 4;
      }
      else {
        // Otherwise, just add to the pool
        $newBuildings[] = $newbuilding;
        $nBuildings ++;
      }
    }

    $nBuildingsLeft = self::countInLocation('deck');
    Notifications::newBuildings($newBuildings, $nBuildingsLeft);

    return $bEndOfGame;
  }
}
