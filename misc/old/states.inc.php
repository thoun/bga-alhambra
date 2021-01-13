<?php
 /**
  * states.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * Testlayout game states
  *
  */

/*
*
*   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
*   in a very easy way from this configuration file.
*
*
*   States types:
*   _ manager: game manager can make the game progress to the next state.
*   _ game: this is an (unstable) game state. the game is going to progress to the next state as soon as current action has been accomplished
*   _ activeplayer: an action is expected from the activeplayer
*
*   Arguments:
*   _ possibleactions: array that specify possible player actions on this step (for state types "manager" and "activeplayer")
*       (correspond to actions names)
*   _ action: name of the method to call to process the action (for state type "game")
*   _ transitions: name of transitions and corresponding next state
*       (name of transitions correspond to "nextState" argument)
*   _ description: description is displayed on top of the main content.
*   _ descriptionmyturn (optional): alternative description displayed when it's player's turn
*
*/

$machinestates = array(

    // Initialization
    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
    2 => array(
        "name" => "initialMoney",
        "description" => clienttranslate("Everyone must accept initial money"),
        "descriptionmyturn" => clienttranslate("Everyone must accept initial money"),
        "type" => "multipleactiveplayer",
        "args" => "argInitialMoney",
        "action" => "stInitialMoney",
        "possibleactions" => array( "acceptMoney" ),
        "transitions" => array( "" => 10 )
    ),
    
    // Game main flow
    10 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array( "playerTurn" => 11, "notEnoughBuilding" => 30 )
    ),

    11 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must pick money, buy building or transform Alhambra'),
        "descriptionmyturn" => clienttranslate('${you} must pick money, buy building or transform Alhambra'),
        "type" => "activeplayer",
        "possibleactions" => array( "takeMoney", "buyBuilding", "transformAlhambra" ), 
        "transitions" => array( "replay" => 11, "endTurn" => 10, "buildingToPlace" => 12, "zombiePass" => 10  )
    ),
    
    12 => array(
        "name" => "placeBuildings",
        "description" => clienttranslate('${actplayer} must place new buildings'),
        "descriptionmyturn" => clienttranslate('${you} must place your new building(s)   in your Alhambra or in your stock'),
        "type" => "activeplayer",
        "possibleactions" => array( "placeBuilding" ),        
        "transitions" => array( "buildingToPlace" => 12, "endTurn" => 10, "zombiePass" => 10 )
    ),
    
    
    // End of game
    30 => array(
        "name" => "lastBuildingsPick",
        "description" => '',
        "type" => "game",
        "action" => "stLastBuildingPick",
        "transitions" => array( "buildingToPlace" => 31, "noMoreBuilding" => 32  )
    ),
    
    // End of game
    31 => array(
        "name" => "placeLastBuildings",
        "description" => clienttranslate('Players who gains buildings must place them'),
        "descriptionmyturn" => clienttranslate('Last buildings: ${you} must place your new building in your Alhambra or in your stock'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "placeBuilding" ), 
        "action" => "stPlaceLastBuildings",       
        "transitions" => array( "noMoreBuilding" => 32  )
    ),

    // End of game
    32 => array(
        "name" => "lastScoringRound",
        "description" => '',
        "type" => "game",
        "action" => "stLastScoringRound",
        "transitions" => array( "" => 99  )
    ),
    
   
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);

?>
