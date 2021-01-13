<?php

// Test if you can place a new piece (deck format) in position x,y
// in player alhambra.
// neighbours is an array of pieces (deck format) with index "XxY" (ex: -4x2)
// that contains at least pieces around $x,$y
// Throw an exception if this is not possible to place this piece here
// If bSkipFreeCheckAndHoles = true, we don't make the free place test & the hole test (useful for building replacement)
function canPlaceAlhambraPiece( $piece, $x, $y, $neighbours, $bSkipFreeCheckAndHoles = false )
{
    $direction_to_coord_delta = array(
        0 => array( 0, -1 ),
        1 => array( 1, 0 ),
        2 => array( 0, 1 ),
        3 => array( -1, 0 )
    );

    if( ! $bSkipFreeCheckAndHoles )
    {
        if( isset( $neighbours[ $x.'x'.$y ] ) )
            throw new feException( self::_("Place is not free"), true, true );
    }

    // Analyse piece to place type
    $type = $this->building_tiles[ $piece['type_arg'] ];
    $direction_to_wall = array( 0=>false, 1=>false, 2=>false, 3=>false );
    foreach( $type['wall'] as $wall )
    {
        $direction_to_wall[ $wall ] = true;
    }

    // Test if there is a wall / there is no wall accordingly (and if there is at least one neighbour)
    $at_least_one_neighbour = false;
    $at_least_one_neighbour_without_wall = false;
    for( $direction = 0; $direction<4; $direction++ )
    {
        $coord_delta = $direction_to_coord_delta[ $direction ];
        $neighbour_index = ( $x+$coord_delta[0] ).'x'.( $y+$coord_delta[1] );
        if( isset( $neighbours[ $neighbour_index ] ) )
        {
            $at_least_one_neighbour = true;

            $neighbour = $neighbours[ $neighbour_index ];
            $neighbour_type = $this->building_tiles[ $neighbour['type_arg'] ];
            $opposite_direction = ($direction+2)%4;

            $neighbour_has_wall = in_array( $opposite_direction, $neighbour_type['wall'] );
            $piece_has_wall = $direction_to_wall[ $direction ];

            if( ! $neighbour_has_wall )
                $at_least_one_neighbour_without_wall = true;

            if( ( $neighbour_has_wall && !$piece_has_wall )
             || ( !$neighbour_has_wall && $piece_has_wall ) )
            {
                throw new feException( self::_("A side with a wall can't touch a side without a wall"), true, true );
            }
        }
    }

    if( ! $at_least_one_neighbour )
        throw new feException( self::_('Each building must have at least a common side with another one'), true, true );

    if( ! $at_least_one_neighbour_without_wall )
        throw new feException( self::_('You must be able to go from fountain to this building without crossing wall'), true, true );

    if( ! $bSkipFreeCheckAndHoles )
    {
        // Test if there is a "hole" by testing if all neighbours (including corner neighbour are consecutives)
        // ... now we include corners ...
        $direction_to_coord_delta = array(
            0 => array( -1, -1 ),
            1 => array( 0, -1 ),
            2 => array( 1, -1 ),
            3 => array( 1, 0 ),
            4 => array( 1, 1 ),
            5 => array( 0, 1 ),
            6 => array( -1, 1 ),
            7 => array( -1, 0 )
        );

        // ... analyse corner neighbours one by one
        $holes_detected = array();
        foreach( $direction_to_coord_delta as $direction => $coord_delta )
        {
            $neighbour_index = ( $x+$coord_delta[0] ).'x'.( $y+$coord_delta[1] );
            if( ! isset( $neighbours[ $neighbour_index ] ) )
            {   // This is a "hole"
               array_push( $holes_detected, $direction );
            }
        }

        // ... see if all holes are contiguous
        // if we starts from direction 0 and go to 7, we must change 1 time from "hole => building" and one time from "building => hole"
        $nb_change = 0; // Should be 2 at the end
        for( $direction=0; $direction<8; $direction++ )
        {
            $direction_previous = ($direction+7)%8;
            $previous_is_hole = in_array( $direction_previous, $holes_detected );
            $current_is_hole = in_array( $direction, $holes_detected );

            if( ( $current_is_hole && !$previous_is_hole ) || ( !$current_is_hole && $previous_is_hole ) )
                $nb_change ++;
        }
        if( $nb_change != 2 )
            throw new feException( self::_("You can't make 'holes' in your Alhambra"), true, true );
    }
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

// Build player alhambra net with node id = coords and link = footpath from one to another
function buildPlayerAlhambraNet( $player_id )
{
    $net = array();
  $sql = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location_arg location_arg, ";
  $sql .= "card_x x, card_y y ";
  $sql .= "FROM building ";
  $sql .= " WHERE card_location='alamb' AND card_location_arg='$player_id' ";
  $dbres = self::DbQuery( $sql );

    while( $building = mysql_fetch_assoc( $dbres ) )
    {
        $id = $building['id'];
        $x = $building['x'];
        $y = $building['y'];
        $coord = $x.'x'.$y;

        if( ! isset( $net[$coord] ) )
            $net[$coord] = array();

        // Find walls of this building
        $building_tile_id = $building['type_arg'];
        $walls = $this->building_tiles[ $building_tile_id ]['wall'];

        $neighbours = array(
            0 => $x.'x'.($y-1),
            1 => ($x+1).'x'.$y,
            2 => $x.'x'.($y+1),
            3 => ($x-1).'x'.$y
        );

        foreach( $neighbours as $direction => $neighbour )
        {
            if( ! in_array( $direction, $walls ) )  // no walls on this direction
            {
                if( isset( $net[ $neighbour ] ) )
                {
                    $net[ $neighbour ][] = $coord;
                    $net[ $coord ][] = $neighbour;
                }
            }
        }
    }

    return $net;
}



// Place a bought building in the Alhambra
// if is_bougth=false, take the building from the stock. In that case, if destination is not empty, perform an exchange
// if x and y are null => place building in stock
function placeBuilding( $building_id, $is_bought, $x=null, $y=null )
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
    else
    {
        // Place in stock
        $this->buildings->moveCard( $building_id, "stock", $g_user->get_id() );

        // Notify
        $this->notifyAllPlayers( "placeBuilding", clienttranslate('${player_name} places a ${building_type_pre}${building_name}${building_type_post} in stock'),
                                 array( "i18n" => array( "building_name" ),
                                        "player_name" => self::getCurrentPlayerName(),
                                        "player" => $g_user->get_id(),
                                        "building_id" => $building_id,
                                        "building" => $building,
                                        "building_name" => ( $this->building_types[ $building['typedetails']['type'] ] ),
                                        "building_type_pre" => '<span class="buildingtype buildingtype_'.$building['typedetails']['type'].'">',
                                        "building_type_post" => '</span>',
                                        "stock" => 1
                                        ) );


    }

    self::updateAlhambraStats( $g_user->get_id() );

    if( ! $is_bought )
        self::incStat( 1, "transformation_nbr", $g_user->get_id() );

    self::endTurnOrPlaceBuildings();
}
