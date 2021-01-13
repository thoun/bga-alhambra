<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;
use ALH\Notifications;


trait PlaceBuildingTrait {


  // Place a bought building in the Alhambra
  // if is_bougth=false, take the building from the stock. In that case, if destination is not empty, perform an exchange
  // if x and y are null => place building in stock

  function stPlaceBuildingInStock($buildingId)
  {
    self::checkAction("placeBuilding");

    // Check if this building is in "to place" or "stock" zone of current player
    $building = Buildings::get($buildingId);

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

}
