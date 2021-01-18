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


        $result['is_scoring_round'] = self::getGameStateValue( 'scoringAtTheEndOfTurn' );

        return $result;
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
