<?php

class Alhambra extends Table
{

    // Get all datas (complete reset request from client side)
    protected function getAllDatas()
    {

        $result['neutral_player'] = self::getGameStateValue( 'neutral_player');

        // Money pool
        $result['money_name'] = $this->money_name;

        // PLayer hand
        $result['card_count'] = $this->money->countCardsByLocationArgs( 'hand' );

        $state = ( $this->gamestate->state());
        if( $state['name'] == 'placeLastBuildings')
        {
            // Remove from "bought" the buildings that are not from you
            $new_bought = array();
            foreach( $result['to_place'] as $id => $card )
            {
                if( self::getGameStateValue('lastbuilding_'.$card['location_arg'] ) == $g_user->get_id())
                {
                    // Okay, this last building must be placed by current user
                    $new_bought[ $id ] = $card;
                }
                else
                {
                    // This is just an available building
                    $result['buildingsite'][ $id ] = $card;
                }
            }
            $result['to_place'] = $new_bought;
        }

        // Alhambras of all players
	    $sql = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location_arg location_arg, ";
	    $sql .= "card_x x, card_y y ";
    	$sql .= "FROM building ";
    	$sql .= " WHERE card_location='alamb' ";
 	    $dbres = self::DbQuery( $sql );

 	    $result['alamb'] = array();
 	    $result['alamb_base'] = array();    // Id of the base fountain of alhambra for each player
 	    $result['alamb_stats'] = array();   // number of buildings by type for stats
        while( $row = mysql_fetch_assoc( $dbres ) )
        {
            $player_id = $row[ 'location_arg'];
            if( ! isset( $result['alamb'][ $player_id ] ) ) // Note: location_arg = owner of the alhambra
            {
                $result['alamb'][ $player_id ] = array();
                $result['alamb_stats'][ $player_id ] = array();
            }

            $row['typedetails'] = $this->building_tiles[ $row['type_arg'] ];
            $result['alamb'][ $player_id ][] = $row;

            if( $row['type'] == 0 ) // Fountain => store its id
                $result['alamb_base'][ $player_id ] = $row['id'];
            else
            {
                if( ! isset( $result['alamb_stats'][ $player_id ][ $row['type'] ] ) )
                    $result['alamb_stats'][ $player_id ][ $row['type'] ] = 0;
                $result['alamb_stats'][ $player_id ][ $row['type'] ] ++;
            }
        }

        $result['is_scoring_round'] = self::getGameStateValue( 'scoringAtTheEndOfTurn' );

        return $result;
    }

    function getGameProgression()
    {
        // Game progression: get the number of buildings remaining in the deck
        $remaining = $this->buildings->countCardInLocation( "deck" );
        $initial = 54;

        return 100-ceil( 100*( $remaining/$initial) );   // Note: player score from 2 to 10
    }



    // Trigger a scoring round
    function scoringRound( $forcevalue=null )
    {
        $round_no = self::getGameStateValue( 'scoringAtTheEndOfTurn' );
        self::setGameStateValue( 'scoringAtTheEndOfTurn', 0 );

        if( $forcevalue !== null )
            $round_no = $forcevalue;

        // Get players points and points details
        $points = self::countPlayersPoints( $round_no );

      //  var_dump( $points );
        //die('ok');

        // Increase player scores
        foreach( $points['players'] as $player_id => $player_result )
        {
            $points_wins = $player_result['points'];
            $sql = "UPDATE player SET player_score=player_score+'$points_wins' WHERE player_id='$player_id' ";
            self::DbQuery( $sql );

            $stat_name = 'points_win_'.$round_no;
            if( $player_id != 0)
                self::setStat( $points_wins, $stat_name, $player_id );
        }

        self::notifyAllPlayers( "scoringRound", self::_('Scoring round !'),
                                 $points );

        if( self::getGameStateValue('neutral_player') == 1 )
        {
            if( $round_no == 1 )
                $this->giveTilesToNeutral(1);
            else if( $round_no == 2 )
                $this->giveTilesToNeutral(2);
        }
    }

    // Return an object with player points depending on round no
    function countPlayersPoints( $round_no )
    {
        // Result structure:
        // array( "players" => array( "<player_id>" => array( "points" => <total number of points wins>,
        //                                                    "wall" => <points wins with longest wall> ),
        //                            "<player_id2>" => ... ),
        //
        //        "buildingdetails" => array(  <type> => array( <player_id> => array( 'nb' =>, 'rank'=>, 'points'=> ) ) )

        $result = array( "round_no" => $round_no, "players" => array(), "buildingdetails" => array() );

        //////// Walls //////////
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $result['players'][$player_id] = array( 'points'=>0, 'walls'=>0 );

            // Get wall length
            $sql = "SELECT player_longest_wall FROM player WHERE player_id='$player_id' ";
            $dbres = self::DbQuery( $sql );
            $row = mysql_fetch_assoc( $dbres );
            $result['players'][$player_id]['walls'] = $row['player_longest_wall'];
            $result['players'][$player_id]['points'] = $row['player_longest_wall'];
        }

        if( self::getGameStateValue('neutral_player') == 1 )
        {
            $result['players'][0] = array( 'points'=>0, 'walls'=>0 );
        }

        //////// Buildings //////

        $building_points = $this->scoring[ $round_no ];

        $building_count = self::countPlayersBuildings();

        foreach( $building_count as $building_type_id => $players )
        {
            $result['buildingdetails'][ $building_type_id ] = array();

            // Sort the players according to building numbers
            asort( $players );
            $players = array_reverse( $players, true );

            self::trace( "\nbuilding type: $building_type_id\n" );
            self::trace( "Classement: ".implode( ',', array_keys( $players ) )."\n" );

            $rank_to_points = $building_points[ $building_type_id ];

            // Process ranks
            $rank_to_players = array();
            $rank = 0;
            $previous_score = 0;
            $nbr_player_tie = 1;
            $index = 0;
            $player_to_index = array();
            foreach( $players as $player_id => $building_nbr )
            {
                if( $building_nbr == $previous_score )
                {
                    // This player is tie with the previous one
                    $nbr_player_tie ++;
                }
                else
                {
                    $rank += $nbr_player_tie;
                    $nbr_player_tie = 1;
                }

                $rank_to_players[ $rank ][] = $player_id;
                $previous_score = $building_nbr;

                $result['buildingdetails'][ $building_type_id ][ ] = array( 'player' => $player_id, 'nb' => $building_nbr, 'rank'=>$rank, 'points'=>0 );
                $player_to_index[ $player_id ] = $index;    // Note: with this method, we ensure that the player order by rank will be kept
                $index ++;
            }

            // Process points
            foreach( $rank_to_players as $rank => $players )
            {
                self::trace(  "rank $rank: " );
                $nbr_player_at_this_rank = count( $players );
                self::trace( "at this rank: $nbr_player_at_this_rank " );
                if( $nbr_player_at_this_rank == 0 )
                    throw new feException( "no player at this rank: ".$rank );

                // All players at this rank are sharing the points corresponding to all the rank
                // they was supposed to occupied if they were not tie
                $points_to_share = 0;
                for( $rank_to_share = $rank; $rank_to_share < ($rank+$nbr_player_at_this_rank); $rank_to_share++ )
                {
                    if( isset( $rank_to_points[ $rank_to_share ] ) )
                        $points_to_share += $rank_to_points[ $rank_to_share ];
                }

                self::trace( "points to share: $points_to_share \n" );

                // Compute points per player (rouded floor according to game rules
                $points_per_player = intval( floor( $points_to_share / $nbr_player_at_this_rank ) );

                foreach( $players as $player_id )
                {
                    $player_index = $player_to_index[ $player_id ];
                    $result['buildingdetails'][ $building_type_id ][ $player_index ]['points'] = $points_per_player;

                    $result['players'][$player_id]['points'] += $points_per_player;
                }
            }

        }


        return $result;
    }

    // Update alhambra statistics for current player:
    // _ update longest wall in DB
    // _ send building count & longest wall by notification to everyone
    // MUST be called after each alhambra update
    function updateAlhambraStats( $player_id )
    {
        // We build a net with this player's walls
        $player_wall_net = self::buildPlayerWallNet( $player_id );
        $o_path = self::getNewUnique( "module.common.path" );
        $longest_path = $o_path->longest( $player_wall_net );
        $longest_wall = max( 0, $longest_path['l']-1 );   // Note: number of walls = number of points - 1

        // Update DB
        if( $player_id != 0 )
        {
            $sql = "UPDATE player SET player_longest_wall='$longest_wall' WHERE player_id='$player_id' ";
            self::DbQuery( $sql );
            self::setStat( $longest_wall, 'longest_wall', $player_id );

            $sql = "SELECT MAX( player_longest_wall ) longest FROM player ";
            $dbres = self::DbQuery( $sql );
            $row = mysql_fetch_assoc( $dbres );
            self::setStat( $row['longest'], 'longest_wall_all' );

        }


        // Count buildings
        $building_count = self::countPlayersBuildings( $player_id );

        if( $player_id == 0 )
            $longest_wall = 0;  // Note: always 0 for longest wall

        $this->notifyAllPlayers( "alhambraStats", '', array( "player"=> $player_id, "walls" => $longest_wall, "buildings" => $building_count ) );
     }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    function giveneutral()
    {
        self::checkAction('placeBuilding');

        $player_id = self::getActivePlayerId();

        // Get all buildings to place and place them into neutral player alhambra
        $buildings = $this->buildings->getCardsInLocation( 'bought' );

        // Get target location
        $max_y = self::getUniqueValueFromDb( "SELECT MAX(card_y)  FROM `building` WHERE `card_location` LIKE 'alamb' AND `card_location_arg` = 0" );
        if( $max_y === null )
            $base_y=0;
        else
            $base_y = $max_y+1;

        $base_x = 0;

        $x = $base_x;
        $y = $base_y;
        $tiles_width = 4;

        foreach( $buildings as $building )
        {
            $building_id = $building['id'];
            $building['typedetails'] = $this->building_tiles[ $building['type_arg'] ];

            // Move the building card to alhambra, right place.
            $sql = "UPDATE building ";
            $sql .= "SET card_location='alamb', card_location_arg='0', ";
            $sql .= "card_x='$x', card_y='$y' ";
            $sql .= "WHERE card_id='$building_id' ";
            self::DbQuery( $sql );

            // Notify
            $this->notifyAllPlayers( "placeBuilding", clienttranslate('${player_name} gives a ${building_name} to Neutral player'),
                                        array( "i18n" => array( "building_name" ),
                                            "player_name" => self::getActivePlayerName(),
                                            "player" => 0,
                                            "building_id" => $building_id,
                                            "building" => $building,
                                            "building_name" => self::_( $this->building_types[ $building['typedetails']['type'] ] ),
                                            "x" => $x,
                                            "y" => $y
                                            ) );

            $x++;
            if( $x >= $tiles_width )
            {
                $x=0;
                $y++;
            }
        }

        self::updateAlhambraStats( 0 );

        self::endTurnOrPlaceBuildings();

    }

    function giveTilesToNeutral( $scoring_round )
    {
        $to_draw = 6;

        $tiles_width = 4;

        $max_y = self::getUniqueValueFromDb( "SELECT MAX(card_y)  FROM `building` WHERE `card_location` LIKE 'alamb' AND `card_location_arg` = 0" );
        if( $max_y === null )
            $base_y=0;
        else
            $base_y = $max_y+1;

        $base_x = 0;

        if( $scoring_round == 2 )
        {
            $total = $this->buildings->countCardsInLocation( 'deck' );
            $to_draw = floor( $total / 3 );
        }

        $x = $base_x;
        $y = $base_y;

        $new_buildings = array();
        for( $i=0; $i<$to_draw; $i++ )
        {
            $newbuilding = $this->buildings->pickCardForLocation( "deck", "alamb", 0 );
            $building_id = $newbuilding['id'];

            // Move the building card to alhambra, right place.
            $sql = "UPDATE building ";
            $sql .= "SET card_location='alamb', card_location_arg='0', ";
            $sql .= "card_x='$x', card_y='$y' ";
            $sql .= "WHERE card_id='$building_id' ";
            self::DbQuery( $sql );

            $newbuilding['x'] = $x;
            $newbuilding['y'] = $y;

            if( $newbuilding == null )
            {
                throw new feException("Cannot pick tile for neutral player: no more tile!");
            }
            else
            {
                $newbuilding['typedetails'] = $this->building_tiles[ $newbuilding['type_arg'] ];
                $new_buildings[] = $newbuilding;
            }

            $x++;
            if( $x >= $tiles_width )
            {
                $x=0;
                $y++;
            }
        }

        $building_count = $this->buildings->countCardsInLocation( 'deck' );
        $this->notifyAllPlayers( "newBuildings", clienttranslate('${nb} tiles are drawn from the deck for the Neutral player'), array( "buildings" => $new_buildings, 'nb' => $to_draw, 'count' => $building_count ) );
        self::updateAlhambraStats( 0 );
    }


    // Remove a building from the alhambra

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////




    function stLastBuildingPick()
    {
        // Last building pick: buildings that are still on the building site are gived to players that has the biggest
        // qt of corresponding money

        // Get buildings to pick
        $buildings_to_pick = $this->buildings->getCardsInLocation( "buildingsite" );
        $money_type_to_building = array();
        foreach( $buildings_to_pick as $building_to_pick )
        {
            $money_type_to_building[ $building_to_pick['location_arg'] ] = $building_to_pick;
        }

        // Fill money players
        $money_players = array();   // player_id => money_type => money in hand

        $money_cards = $this->money->getCardsInLocation( "hand" );
        foreach( $money_cards as $money )
        {
            $money_type = $money['type'];
            $value = $money['type_arg'];
            $player = $money['location_arg'];
            if( ! isset( $money_players[ $player ] ) )
                $money_players[ $player ] = array();
            if( ! isset( $money_players[ $player ][ $money_type ] ) )
                $money_players[ $player ][ $money_type ] = 0;
            $money_players[ $player ][ $money_type ] += $value;
        }

        $get_at_least_a_building = array();

        // For each money type, see who is the best one
        for( $money_type=1; $money_type<=4 ; $money_type++ )
        {
            if( isset( $money_type_to_building[ $money_type ] ) )   // Is there really a building to pick
            {
                $building_to_pick = $money_type_to_building[ $money_type ];
                $max = 0;
                $max_player = null;
                $bTie = false;

                foreach( $money_players as $player_id => $player )
                {
                    if( isset( $player[ $money_type ] ) )
                    {
                        if( $player[ $money_type ] > $max )
                        {
                            $max = $player[ $money_type ];
                            $max_player = $player_id;
                            $bTie = false;
                        }
                        else if( $player[ $money_type ] == $max )
                            $bTie = true;
                    }
                }

                $building_type_name = self::_( $this->building_types[ $building_to_pick['type'] ] );
                $money_name = self::_( $this->money_name[ $money_type ]  );

                if( $max == 0 )
                {   // No one take this building because nobody has cards of that money
                     $this->notifyAllPlayers( "nogetBuilding", clienttranslate('Nobody has any ${money_name}, ${building_type} stays in buildingsite'),
                                             array( "i18n" => array( "building_type", "money_name" ),
                                                    "building_type" => $building_type_name,
                                                    "money_name" => $money_name
                                                    ) );
                }
                else if( $bTie )
                {   // No one take this building because several player has the same card value
                     $this->notifyAllPlayers( "nogetBuilding", clienttranslate('Several players has the same value in ${money_name}, ${building_type} stays in buildingsite'),
                                             array( "i18n" => array( "building_type", "money_name" ),
                                                    "building_type" => $building_type_name,
                                                    "money_name" => $money_name
                                                    ) );
                }
                else
                {
                    // Okay, max_player take this building

                    // Place it in player's "to place" location
                    $this->buildings->moveCard( $building_to_pick['id'], 'bought', $money_type );

                    // Notify
                    $players = self::loadPlayersBasicInfos();
                    $this->notifyAllPlayers( "getBuilding", clienttranslate('${player_name} gets ${building_type} because he has the most ${money_name}'),
                                             array( "i18n" => array( "building_type", "money_name" ),
                                                    "player_name" => $players[ $max_player ]['player_name'],
                                                    "player" => $max_player,
                                                    "building_id" => $building_to_pick['id'],
                                                    "building_type" => $building_type_name,
                                                    "money_name" => $money_name
                                                    ) );

                    if( ! in_array( $max_player, $get_at_least_a_building ) )
                        $get_at_least_a_building[] = $max_player;

                    self::setGameStateValue( 'lastbuilding_'.$money_type, $max_player );
                }
            }
        }

        // Set multiactive for the next round
        $sql = "UPDATE player SET player_is_multiactive='1' WHERE player_id IN ('".implode("','", $get_at_least_a_building)."')";
        self::DbQuery( $sql );

        if( count( $money_type_to_building ) > 0 )
            $this->gamestate->nextState( 'buildingToPlace');
        else
            $this->gamestate->nextState( 'noMoreBuilding');
    }

    function stPlaceLastBuildings()
    {
        $this->gamestate->updateMultiactiveOrNextState( "noMoreBuilding" );
    }

    function stLastScoringRound()
    {
        self::setGameStateValue( 'scoringAtTheEndOfTurn', 3 );
        self::scoringRound();

        $this->gamestate->nextState();
    }

//////////////////////////////////////////////////////////////////////////////
//////////// End of game management
////////////

    protected function getGameRankInfos()
    {
        // By default, common method uses 'player_rank' field to create this object
        $result = self::getStandardGameResultObject();
        // Adding stats
        return $result;
    }
}

?>
