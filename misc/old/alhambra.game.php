<?php
 /**
  * alhambra.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * alhambra main game core
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

define( "SCORING_CARD_TYPE", 0 );

class Alhambra extends Table
{
	function __construct( )
	{
        require( 'materials.inc.php' );

        // scoringAtTheEndOfTurn: 0 if no scoring, 1 if first scoring round, 2 if second scoring round
        parent::__construct();self::initGameStateLabels( array( 
                "scoringAtTheEndOfTurn" => 10,
                "neutral_player" => 11,
                "first_player" => 12,
                "lastbuilding_1" => 13,
                "lastbuilding_2" => 14,
                "lastbuilding_3" => 15,
                "lastbuilding_4" => 16
        ) );

        $this->buildings = self::getNew( "module.common.deck" );
        $this->buildings->init( "building" );

        $this->money = self::getNew( "module.common.deck" );
        $this->money->init( "money" );
	}

    function getGameName() {
        return "alhambra";
    }

    protected function setupNewGame( $players, $options = array() )
    {
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql );

        self::setGameStateInitialValue( 'neutral_player', '0' );
        $bIsNeutral = false;
        if( count( $players ) == 2 )
        {
            self::setGameStateValue( 'neutral_player', '1' );
            $bIsNeutral = true;
        }

        ////////////////////////////////////////////
        // Building tiles

        // Building deck: fountains
        $sql = "INSERT INTO building ( card_type, card_type_arg, card_location, card_location_arg, card_x, card_y ) VALUES ";
        $values = array();
        $i = 0;
        foreach( $players as $player_id => $player )
        {
            $values[] = "( '0', '$i', 'alamb', '$player_id', '0','0' )";
            $i++;
        }

        // Building deck: deck
        foreach( $this->building_tiles as $tile_id => $tile )
        {
            if( $tile['type'] != 0 )    // Fountains: already done
            {
                $values[] = "( '".$tile['type']."', '$tile_id', 'deck', '0', NULL, NULL )";
            }
        }

        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );

        // Shuffle building deck
        $this->buildings->shuffle( 'deck' );

        //////////////////////////////////
        // Money cards

        if( $bIsNeutral )
        {
            // Remove one third of the money
            foreach( $this->money_cards as $key => $money )
            {
                if( $money['nbr'] == 3 )
                    $this->money_cards[$key]['nbr'] = 2;
            }
        }
        $this->money->createCards( $this->money_cards );

        // Remove scoring from the deck to a temporary location
        self::DbQuery( "UPDATE money SET card_location='scoring' WHERE card_type='".SCORING_CARD_TYPE."' " );

        // Shuffle the deck
        $this->money->shuffle('deck');

        // Initial money distribution to players
        // (everyone received at least 20)
        $player_initial_money = array();
        foreach( $players as $player_id => $player )
        {
            $player_initial_money[ $player_id ] = array( "value" => 0, "nbr" => 0 );
            while( $player_initial_money[ $player_id ]['value'] < 20 )
            {
                $card = $this->money->pickCard( 'deck', $player_id );
                $player_initial_money[ $player_id ]['value'] += $card['type_arg'];
                $player_initial_money[ $player_id ]['nbr']++;
            }
        }

        // Now we sort players depending on number of cards receiving
        // in case of equality we look at card value

        if( self::is_testmode() )
        {
            $testmodedatas = self::testmodedatas();
            $player_initial_money = $testmodedatas['player_initial_money'];
        }

        uasort( $player_initial_money, array( "Alhambra", "playFirst" ) );  // Note: in test mode, we keep

        // Create players in the previous right order
        $default_color = array( "ff0000", "187b00", "0000ff", "ffff00", "ffffff", "ff8000" );
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_color );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, array( "ff0000", "00ff00", "0000ff", "ffff00", "ffffff", "ff8000" ) );
        self::reloadPlayersBasicInfos();




        foreach( $players as $player_id => $player )
        {
            self::initStat( 'player', 'money_taken', $player_initial_money[ $player_id ]['value'], $player_id );
        }

        // Get scoring round cards ids
        $scoring_cards = $this->money->getCardsInLocation("scoring");

        $total_card = $this->money->countCardInLocation( "deck" );

        // Insert scoring round cards:

        foreach( $scoring_cards as $card_id => $scoring_card )
        {
            if( $scoring_card['type_arg'] == 2 )
                $pos = bga_rand( ceil( $total_card*1/5 ), floor( $total_card*2/5 ) );  // 1st is inserted in the packet "2/5"
            else if( $scoring_card['type_arg'] == 1 )
                $pos = bga_rand( ceil( $total_card*3/5 ), floor( $total_card*4/5 ) );  // 1st is inserted in the packet "4/5"
            else
                throw new feException( "extra scoring card" );

            $this->money->insertCard( $card_id, 'deck', $pos );
        }

        if( self::is_testmode() )
        {
            self::applyTestModeDbFixture( "removeall" );
            self::applyTestModeDbFixture( "money" );
            self::applyTestModeDbFixture( "building" );
        }

        if( $bIsNeutral )
        {
            $this->giveTilesToNeutral(0);   // 6 initial tile to neutral player
        }

        self::setGameStateInitialValue( 'scoringAtTheEndOfTurn', '0' );
        self::setGameStateInitialValue( 'first_player', '0' );

        // Statistics
        self::initStat( 'table', 'longest_wall_all', 0 );
        self::initStat( 'table', 'turn_number', 0 );

        self::initStat( 'player', 'longest_wall', 0 );
        self::initStat( 'player', 'points_win_1', 0 );
        self::initStat( 'player', 'points_win_2', 0 );
        self::initStat( 'player', 'points_win_3', 0 );
        self::initStat( 'player', 'transformation_nbr', 0 );
        self::initStat( 'player', 'exact_amount', 0 );
    }

    // Get all datas (complete reset request from client side)
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
        global $g_user;

        // Add players specific infos
        $sql = "SELECT player_id id, player_score score, player_longest_wall longest_wall ";
        $sql .= "FROM player ";
        $sql .= "WHERE 1 ";
        $dbres = self::DbQuery( $sql );
        while( $player = mysql_fetch_assoc( $dbres ) )
        {
            $result['players'][ $player['id'] ] = $player;
        }

        $result['neutral_player'] = self::getGameStateValue( 'neutral_player');

        // Building pool
        $buildingsite = $this->buildings->getCardsInLocation( 'buildingsite' );
        foreach( $buildingsite as $id => $card )
        {
            $buildingsite[ $id ]['typedetails'] = $this->building_tiles[ $card['type_arg'] ];
        }
        $result['buildingsite'] = $buildingsite;

        // Money pool
        $result['money_pool'] = $this->money->getCardsInLocation( 'pool' );
        $result['money_name'] = $this->money_name;
        $result['money_count'] = $this->money->countCardInLocation( 'deck' );

        // PLayer hand
        $result['player_hand'] = $this->money->getCardsInLocation( 'hand', $g_user->get_id() );
        $result['card_count'] = $this->money->countCardsByLocationArgs( 'hand' );

        // Buildings to place
        $result['to_place'] = $this->buildings->getCardsInLocation( 'bought' );
        foreach( $result['to_place'] as $id => $card )
        {
            $result['to_place'][ $id ]['typedetails'] = $this->building_tiles[ $card['type_arg'] ];
        }


        $result['building_count'] = $this->buildings->countCardInLocation( 'deck' );

        // Buildings in stock (all players)
        $result['stock'] = $this->buildings->getCardsInLocation( 'stock' );
        foreach( $result['stock'] as $id => $card )
        {
            $result['stock'][ $id ]['typedetails'] = $this->building_tiles[ $card['type_arg'] ];
        }

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

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions    (functions used everywhere)
////////////

    // Used to know which player plays first at the beginning of the game
    static function playFirst($a, $b) {
        if( $a['nbr'] < $b['nbr'] )
            return -1;
        else if( $a['nbr'] > $b['nbr'] )
            return 1;
        else
        {
            if( $a['value'] < $b['value'] )
                return -1;
            else if( $a['value'] > $b['value'] )
                return 1;
            else
                return 0;
        }
    }

    // If there are building to place, go to corresponding step
    // Otherwise end current player turn
    function endTurnOrPlaceBuildings()
    {
        global $g_user;
        $state = $this->gamestate->state();

        if( $state['name'] == 'placeLastBuildings' )
        {
            $all_buildings = $this->buildings->getCardsInLocation( 'bought' );
            $buildings_to_place = 0;

            foreach( $all_buildings as $building )
            {
                if( self::getGameStateValue( 'lastbuilding_'.$building['location_arg'] ) == $g_user->get_id() )
                    $buildings_to_place ++;
            }
            
            if( $buildings_to_place == 0 )
            {
                // Specific case: this is the last turn and this player placed all the building he gets
                $this->gamestate->setPlayerNonMultiactive( $g_user->get_id(), "noMoreBuilding" );
            }


        }
        else
        {
            $buildings_to_place = $this->buildings->countCardInLocation( "bought" );

            if( $buildings_to_place == 0 )
            {
                $this->gamestate->nextState( "endTurn" );   // Normal case
            }
            else
            {
                $this->gamestate->nextState( "buildingToPlace" );
            }
        }

    }

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

    // Initial "ok" to accept cards in hand
    function acceptMoney()
    {
        self::checkAction( "acceptMoney" );
        global $g_user;
        $this->gamestate->setPlayerNonMultiactive( $g_user->get_id(), '' );
    }

    // Take some money cards in the pool (money_cards = array of card id to take)
    function takeMoney( $money_cards )
    {
        self::checkAction( "takeMoney" );

        $player_id = self::getActivePlayerId();

        // Get all these cards infos
        $cards = $this->money->getCards( $money_cards );

        // Check they all are in "pool"
        foreach( $cards as $card )
        {
            if( $card['location'] != 'pool' )
                throw new feException( "This money card is not available in money pool" );
        }

        // Check that player has the right to pick all these cards
        $value_sum = 0;
        $description = '';
        $description_args = array( "i18n" => array() );
        $i = 0;
        foreach( $cards as $card )
        {
            $value_sum += $card['type_arg'];

            if( $description != '' )
                $description .= ', ';
            $description .= '<span class="moneytype moneytype_'.$card['type'].'">'.$card['type_arg'].' ${money_name_'.$i.'}'.'</span>';
            $description_args[ 'money_name_'.$i ] = $this->money_name[ $card['type'] ];
            $description_args[ 'i18n' ][] = 'money_name_'.$i;
        }

        if( count( $money_cards ) > 1 )
        {
            if( $value_sum > 5 )
                throw new feException( "You can't take several cards with a value exceeding 5" );
        }

        // Okay, move these cards to player hand
        foreach( $cards as $card )
        {
            $this->money->moveCard( $card['id'], 'hand', $player_id );
        }

        self::incStat( $value_sum, 'money_taken', $player_id );

        // Notify
        $this->notifyAllPlayers( "takeMoney", clienttranslate('${player_name} takes ${description}'),
                                 array( "player_name" => self::getCurrentPlayerName(),
                                        "player" => $player_id,
                                        "cards" => $cards,
                                        "description" => array(
                                            "log" => $description,
                                            "args" => $description_args
                                          )
                                        ) );

        self::updateMoneyCount( $player_id );
                                        
        // This action ends player turn
        self::endTurnOrPlaceBuildings();
    }

    function updateMoneyCount( $player_id )
    {
        self::notifyAllPlayers( 'updateMoneyCount', '', array( 'player' => $player_id, 'count' => $this->money->countCardsInLocation('hand', $player_id)));
    }

    // Buy a building with these cards from player hand
    function buyBuilding( $building_id, $money_cards )
    {
        self::checkAction( "buyBuilding" );

        $player_id = self::getActivePlayerId();

        // Check that theses cards exists and are in player hand
        $cards = $this->money->getCards( $money_cards );

        // Check they all are in "pool"
        foreach( $cards as $card )
        {
            if( $card['location'] != 'hand' )
                throw new feException( "This money card is not available in your hand" );

            if( $card['location_arg'] != $player_id )
                throw new feException( "This money card is not available in your hand" );
        }

        // Check that this building exists and cost what it is supposed to cost
        $building = $this->buildings->getCard( $building_id );
        if( ! $building )
            throw new feException( "This building does not exists" );

        if( $building['location'] != 'buildingsite' )
            throw new feException( "This building isn't available for buying" );

        $building_type_id = $building['type_arg'];
        $building_type = $this->building_tiles[ $building_type_id ];
        $building_cost = $building_type['cost'];
        $money_type_id = $building['location_arg']; // Note: location_arg correspond to the building site zone, which correspond exactly to money type id

        // Check that the cards are in the good money with the needed amount
        $amount = 0;
        $cards_values = array();
        foreach( $cards as $card )
        {
            $amount += $card['type_arg'];   // Amount of money
            if( $card['type'] != $money_type_id )
            {
                throw new feException( "Try to pay with money which does not correspond to building" );
            }

            $cards_values[] = $card['type_arg'];

            $this->money->moveCard( $card['id'], 'discard', 0 );
        }

        if( $amount < $building_cost )
            throw new feException( "Not enough money to buy this building" );

        // Buy the building, ie place it in player's "to place" location
        $this->buildings->moveCard( $building_id, 'bought', $money_type_id );

        // Notify
        $this->notifyAllPlayers( "buyBuilding", clienttranslate('${player_name} buys: ${building_type_pre}${building_type}${building_type_post} with cards: ${cards_value_string}'),
                                 array( "i18n" => array( "building_type" ),
                                        "player_name" => self::getActivePlayerName(),
                                        "player" => $player_id,
                                        "building_id" => $building_id,
                                        "building_type" => $this->building_types[ $building_type['type'] ],
                                        "cards_value_string" => implode( ', ', $cards_values ),
                                        "building_type_pre" => '<span class="buildingtype buildingtype_'.$building_type['type'].'">',
                                        "building_type_post" => '</span>',
                                        "cards" => $cards
                                        ) );

        self::updateMoneyCount( $player_id );

        if( $amount == $building_cost )
        {
            // Exact amount of money => replay !
            $this->notifyAllPlayers( "exactAmount", clienttranslate('${player_name} pays with the exact amount and replay !'),
                                 array( "player_name" => self::getActivePlayerName(),
                                        "player" => $player_id  ) );

            self::incStat( 1, "exact_amount", $player_id );

            $this->gamestate->nextState( "replay" );
        }
        else
        {
            // More money than expected => go to "place building" step
            $this->gamestate->nextState( "buildingToPlace" );
        }
    }

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
    function transformAlhambraRemove( $building_id )
    {
        self::checkAction( "transformAlhambra" );

        $player_id = self::getActivePlayerId();

        // Check that this building is on the alhambra and is not a fountain
        $sql = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg, card_x, card_y ";
        $sql .= "FROM building ";
        $sql .= "WHERE card_id='$building_id' ";
        $dbres = self::DbQuery( $sql );
        $building = mysql_fetch_assoc( $dbres );

        if( ! $building )
            throw new feException( "this building does not exist:".$building_id );

        if( $building['location'] != 'alamb' )
            throw new feException( "this building is not in an alhambra" );

        if( $building['location_arg'] != $player_id )
            throw new feException( "this building is not in your alhambra" );

        $x = $building['card_x'];
        $y = $building['card_y'];
        if( $x == 0 && $y == 0 )
            throw new feException( "you can't remove the initial fountain" );


        // We must check that no "hole" is created
        // Note: a hole is created only if there are buildings in four directions (N/E/S/W) => very simple to check

        // Get neighbours
        $sql = "SELECT card_id id, card_x, card_y ";
        $sql .= "FROM building ";
        $sql .= "WHERE card_x>='".($x-1)."' AND card_x<='".($x+1)."' ";
        $sql .= "AND card_y>='".($y-1)."' AND card_y<='".($y+1)."' ";
        $sql .= "AND card_location='alamb' AND card_location_arg='$player_id' ";
        $dbres = self::DbQuery( $sql );
        $neighbour_count = 0;
        while( $row = mysql_fetch_assoc( $dbres ) )
        {
            if( $row['card_x'] == $building['card_x'] || $row['card_y'] == $building['card_y'] )    // immediate neighbour
            {
                if( $row['card_x']!=$building['card_x'] || $row['card_y']!=$building['card_y'] )    // ... and not the one to remove
                    $neighbour_count++;
            }
        }
        if( $neighbour_count == 4 )
            throw new feException( self::_("You can't make 'holes' in your Alhambra"), true, true );

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

        // We must check that it is still possible to go eveywhere in the alhambra from the foutain without this building
        // => we have no many choices at this step than reconstitue the full alhambra net and make this check

        $net = self::buildPlayerAlhambraNet( $player_id );
        $o_path = self::getNewUnique( "module.common.path" );
        if( ! $o_path->is_connex( $net ) )
            throw new feException( self::_('You must be able to go from fountain to this building without crossing wall'), true, true );

        self::updateAlhambraStats( $player_id );

        self::incStat( 1, "transformation_nbr", $player_id );

        self::endTurnOrPlaceBuildings();
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

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argInitialMoney()
    {
        // Get initial money received by players
        $player_to_cards = array();
        $cards_in_hands = $this->money->getCardsInLocation( "hand" );
        foreach( $cards_in_hands as $card_in_hand )
        {
            $player_id = $card_in_hand[ 'location_arg' ];
            if( ! isset( $player_to_cards[ $player_id ] ) )
                $player_to_cards[ $player_id ] = array();

            $player_to_cards[ $player_id ][] = $card_in_hand;
        }

        return $player_to_cards;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state reactions   (reactions to game planned states from state machine)
////////////

    function stInitialMoney()
    {
        $this->gamestate->setAllPlayersMultiactive();
    }

    function stNextPlayer()
    {
        if( self::getGameStateValue( 'first_player') == 0 )
        {
            // Starting the game!
            // First player = player with the least cards (or least values in case of tie)

            $cards = $this->money->getCardsInLocation( 'hand');

            $player_to_starting_value = array();    // Note: 1 card = 100 points + its value

            foreach( $cards as $card )
            {
                $player_id = $card['location_arg'];
                if( ! isset( $player_to_starting_value[$player_id]))
                    $player_to_starting_value[$player_id] = 0;

                $player_to_starting_value[$player_id] += 100;// For the card
                $player_to_starting_value[$player_id] += $card['type_arg'];// For the card value
            }

            // Get the lowest
            $active_player = getKeyWithMaximum( $player_to_starting_value, false, false );

            self::setGameStateValue( 'first_player', $active_player );

            $this->gamestate->changeActivePlayer( $active_player );
        }
        else
        {
            $active_player = self::activeNextPlayer();
        }

        self::giveExtraTime( $active_player );

        $players = self::loadPlayersBasicInfos();
        reset( $players );
        $first_player = key( $players );
        if( $first_player == $active_player )
            self::incStat( 1, 'turn_number' );

        // Fill the money pool (to 4 cards)
        $money_pool_cnt = $this->money->countCardInLocation( "pool" );
        $new_money_pool = array();
        while( $money_pool_cnt < 4 )
        {
            $card = $this->money->pickCardForLocation( 'deck', 'pool' );

            if( $card == null )
            {
                // No more money card
                $this->money->moveAllCardsInLocation( 'discard', 'deck' );
                $this->money->shuffle( 'deck' );

                if( self::is_testmode() )
                {
                    self::DbQuery( "DELETE FROM money WHERE 1" );
                    self::applyTestModeDbFixture( "money_after_reshuffle" );
                }

                $card = $this->money->pickCardForLocation( 'deck', 'pool' );

                $this->notifyAllPlayers( "noMoreMoney", clienttranslate("No more money card: recreating a deck"), array() );

                if( $card == null )
                    throw new feException( "no more money card" );
            }

            if( $card['type'] == SCORING_CARD_TYPE )
            {
                // Scoring at the end of turn
                $this->money->moveCard( $card['id'], 'retired', 0 );
                self::setGameStateValue( 'scoringAtTheEndOfTurn', $card['type_arg'] );
                $this->notifyAllPlayers( "scoringCard", clienttranslate('A scoring round card has been picked !'), array( "scoring_round" => $card['type_arg'] ) );
            }
            else
            {
                // Standard money card
                $new_money_pool[] = $card;
                $money_pool_cnt ++;
            }
        }

        $money_count = $this->money->countCardsInLocation( 'deck' );
        $this->notifyAllPlayers( "newMoneyCards", '', array( "cards" => $new_money_pool, "count" => $money_count ) );

        // Fill the building pool
        $building_in_place = $this->buildings->getCardsInLocation( "buildingsite" );
        $building_count = count( $building_in_place );
        // Get the free places
        $free_places = array( 1=>1, 2=>2, 3=>3, 4=>4 );
        $new_buildings = array();
        foreach( $building_in_place as $building )
        {
            unset( $free_places[ $building['location_arg'] ] );
        }

        $bEndOfGame = false;
        while( $building_count < 4 )
        {
            // get the first free
            $free_place = array_shift( $free_places );
            if( ! $free_place )
                throw new feException( "Fatal error: no more free places for buildings in building site" );

            $newbuilding = $this->buildings->pickCardForLocation( "deck", "buildingsite", $free_place );

            if( $newbuilding == null )
            {
                $bEndOfGame = true;
                $building_count = 4;
            }
            else
            {
                $newbuilding['typedetails'] = $this->building_tiles[ $newbuilding['type_arg'] ];
                $new_buildings[] = $newbuilding;
                $building_count ++;
            }
        }

        $building_count = $this->buildings->countCardsInLocation( 'deck' );

        // Note: in French edition, end of game is triggered when deck is empty, while on original edition it is triggered when there is a free space left
//        if( $building_count == 0 )
//            $bEndOfGame = true;

        $this->notifyAllPlayers( "newBuildings", '', array( "buildings" => $new_buildings, 'count' => $building_count ) );


        if( self::getGameStateValue( 'scoringAtTheEndOfTurn' ) > 0 )
            self::scoringRound();   // Trigger a scoring round if needed

        if( $bEndOfGame )
        {
            $this->notifyAllPlayers( "endOfGame", clienttranslate('The last building has been drawn: this is the end of the game!'), array() );
            $this->gamestate->nextState( 'notEnoughBuilding');
        }
        else
            $this->gamestate->nextState( 'playerTurn');
    }

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

        //  $result = array(   "table" => array( "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),       // game statistics
        //                     "result" => array(
        //                                     array( "rank" => 1,
        //                                            "tie" => false,
        //                                            "score" => 354,
        //                                            "player" => 45,
        //                                            "name" => "Kara Thrace",
        //                                            "zombie" => 0,

        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) ),
        //                                     array( "rank" => 2,
        //                                            "tie" => false,
        //                                            "score" => 312,
        //                                            "player" => 46,
        //                                            "name" => "Lee Adama",
        //                                            "zombie" => 0,
        //                                            "stats" => array( 1 => 0.554, 2 => 54, 3 => 56 ) )
        //                                     )
        //              )
        //


        // By default, common method uses 'player_rank' field to create this object
        $result = self::getStandardGameResultObject();

        // Adding stats

        return $result;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    function zombieTurn( $state, $active_player )
    {
        if( $state['name'] == 'initialMoney' )
        {
            self::acceptMoney();
        }
        else if( $state['name'] == 'playerTurn'
           || $state['name'] == 'placeBuildings' )
        {
            $this->gamestate->nextState( "zombiePass" );
        }
        else if( $state['name'] == 'placeLastBuildings' )
        {
            global $g_user;
            $sql = "UPDATE player SET player_is_multiactive='0' WHERE player_id = '".$g_user->get_id()."' ";
            self::DbQuery( $sql );
            $this->gamestate->updateMultiactiveOrNextState( "noMoreBuilding" );
        }
        else
            throw new feException( "Zombie mode not supported at this game state:".$state['name'] );
    }

}

?>
