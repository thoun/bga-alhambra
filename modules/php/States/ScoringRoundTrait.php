<?php
namespace ALH\States;
use ALH\Players;
use ALH\Globals;
use ALH\Money;
use ALH\Buildings;
use ALH\Notifications;
use ALH\Stats;


trait ScoringRoundTrait {

  // Trigger a scoring round
  function scoringRound($forcevalue = null)
  {
    $round = $forcevalue ?? Globals::getScoringRound();
    Globals::setScoringRound(0);

    // Get players points and points details
    $points = $this->countPlayersPoints($round);

    // Increase player scores
    foreach($points['players'] as $pId => $result){
      Players::get($pId)->score($result['points']);
      Stats::setScoringResult($pId, $result['points'], $round);
    }

    // Notify
    Notifications::scoringRound($points);

    // Neutral player
    if(Globals::isNeutral() && $round < 3){
      Buildings::giveTilesToNeutralScoringRound($round);
    }
  }


  // Return an object with player points depending on round no
  function countPlayersPoints($round)
  {
    // Result structure:
    // array( "players" => array( "<player_id>" => array( "points" => <total number of points wins>,
    //                                                    "wall" => <points wins with longest wall> ),
    //                            "<player_id2>" => ... ),
    //
    //        "buildingdetails" => array(  <type> => array( <player_id> => array( 'nb' =>, 'rank'=>, 'points'=> ) ) )
    $result = [
      "round_no" => $round,
      "players" => [],
      "buildingdetails" => []
    ];

    //////// Walls //////////
    foreach(Players::getAll() as $player){
      $result['players'][$player->getId()] = [
        'walls' => $player->getStoredLongestWall(),
        'points' => $player->getStoredLongestWall(),
      ];
    }

    if(Globals::isNeutral()){
      $result['players'][0] = ['points' => 0, 'walls' => 0];
    }


    //////// Buildings //////
    $buildingCounts = Players::getBuildingCounts();

    foreach($this->scoring as $type => $rankToPoints){
      $result['buildingdetails'][$type] = [];

      // Extract count of given type
      $buildingsOfType = array_map(function($scores) use ($type){ return $scores[$type];}, $buildingCounts);

      // Sort the players according to building numbers
      arsort($buildingsOfType);


      // Process ranks
      $rankToPlayers = [];
      $rank = 0;
      $previousCount = 0;
      $nbrPlayerTie = 1;
      $index = 0;
      $playerToIndex = [];
      foreach($buildingsOfType as $pId => $count){
        if($count == $previousCount){
          // This player is tie with the previous one
          $nbrPlayerTie ++;
        } else {
          $rank += $nbrPlayerTie;
          $nbrPlayerTie = 1;
        }

        $rankToPlayers[$rank][] = $pId;
        $previousCount = $count;

        $result['buildingdetails'][$type][$index] = ['player' => $pId, 'nb' => $count, 'rank' => $rank, 'points' => 0];
        $playerToIndex[$pId] = $index++;    // Note: with this method, we ensure that the player order by rank will be kept
      }

      //self::trace( "\nbuilding type: $building_type_id\n" );
      //self::trace( "Classement: ".implode( ',', array_keys( $players ) )."\n" );


      // Process points
      foreach($rankToPlayers as $rank => $players) {
        $n = count( $players );
        if($n == 0 )
          throw new feException( "no player at this rank: ".$rank);

        // All players at this rank are sharing the points corresponding to all the rank
        // they was supposed to occupied if they were not tie
        $pointsToShare = 0;
        for($i = $rank; $i < $rank + $n; $i++){
          $j = $i + 2 - $round;
          if(isset($rankToPoints[$j]))
            $pointsToShare += $rankToPoints[$j];
        }

        // Compute points per player (rouded floor according to game rules
        $pointsPerPlayer = intval(floor($pointsToShare / $n));

        foreach($players as $pId) {
          $index = $playerToIndex[$pId];
          $result['buildingdetails'][$type][$index]['points'] = $pointsPerPlayer;
          $result['players'][$pId]['points'] += $pointsPerPlayer;
        }
      }
    }

    return $result;
  }
}
