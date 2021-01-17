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


}
