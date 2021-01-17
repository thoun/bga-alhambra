<?php

class Alhambra extends Table
{

    // Get all datas (complete reset request from client side)
    protected function getAllDatas()
    {
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

        $result['is_scoring_round'] = self::getGameStateValue( 'scoringAtTheEndOfTurn' );

        return $result;
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
        // By default, common method uses 'player_rank' field to create this object
        $result = self::getStandardGameResultObject();
        // Adding stats
        return $result;
    }
}

?>
