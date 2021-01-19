<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Alhambra implementation : © Gregory Isabelli
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * thecrew game states description
 *
 */

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = [
  // The initial state. Please do not modify.
  STATE_SETUP => [
    "name" => "gameSetup",
    "description" => "",
    "type" => "manager",
    "action" => "stGameSetup",
    "transitions" => [
      "" => STATE_INIT_MONEY
    ]
  ],


  STATE_INIT_MONEY => [
    "name" => "initialMoney",
    "description" => clienttranslate("Everyone must accept initial money"),
    "descriptionmyturn" => clienttranslate("Everyone must accept initial money"),
    "type" => "multipleactiveplayer",
    "args" => "argInitialMoney",
    "action" => "stInitialMoney",
    "possibleactions" => ["acceptMoney"],
    "transitions" => [
      "" => STATE_NEXT_PLAYER
    ]
  ],


  // Game main flow
  STATE_NEXT_PLAYER => [
    "name" => "nextPlayer",
    "description" => '',
    "type" => "game",
    "action" => "stNextPlayer",
    "updateGameProgression" => true,
    "transitions" => [
      "playerTurn" => STATE_PLAYER_TURN,
      "notEnoughBuilding" => STATE_EOG_LAST_BUILDINGS
    ]
  ],

  STATE_PLAYER_TURN => [
    "name" => "playerTurn",
    "description" => clienttranslate('${actplayer} must pick money, buy building or transform Alhambra'),
    "descriptionmyturn" => clienttranslate('${you} must pick money, buy building or transform Alhambra'),
    "descriptionmyturngeneric" => clienttranslate('${you} must pick money, buy building or transform Alhambra'),
    "descriptionmyturnmoney" => clienttranslate('${you} may take several money card with total value at most 5'),
    "descriptionmyturnbuilding" => clienttranslate('${you} must select money card(s) to pay the price of the building'),
    "descriptionmyturnmoneyforbuilding" => clienttranslate('${you} must select money card(s) and then select the building to buy'),
    "type" => "activeplayer",
    "args" => "argPlayerTurn",
    "possibleactions" => ["takeMoney", "buyBuilding", "placeBuilding", "restart"],
    "transitions" => [
      "replay" => STATE_PLAYER_TURN,
      "endTurn" => ST_CONFIRM_TURN,
      "buildingToPlace" => STATE_PLACE_BUILDING,
      "zombiePass" => STATE_NEXT_PLAYER,
      "restart" => STATE_PLAYER_TURN,
    ]
  ],

  STATE_PLACE_BUILDING => [
    "name" => "placeBuildings",
    "description" => clienttranslate('${actplayer} must place new buildings'),
    "descriptionmyturn" => clienttranslate('${you} must place your new building(s) in your Alhambra or in your stock'),
    "descriptionmyturndirk" => clienttranslate('${you} must place your new building(s) in your Alhambra or in your stock or give them to neutral player'),
    "type" => "activeplayer",
    "args" => "argPlaceBuilding",
    "possibleactions" => ["placeBuilding", "restart"],
    "transitions" => [
      "buildingToPlace" => STATE_PLACE_BUILDING,
      "endTurn" => ST_CONFIRM_TURN,
      "zombiePass" => STATE_NEXT_PLAYER,
      "restart" => STATE_PLAYER_TURN,
    ]
  ],

  ST_CONFIRM_TURN => [
    "name" => "confirmTurn",
    "description" => clienttranslate('${actplayer} must confirm or restart its turn'),
    "descriptionmyturn" => clienttranslate('${you} must confirm or restart your turn'),
    "type" => "activeplayer",
    "possibleactions" => ["restart", "confirm"],
    "transitions" => [
      "restart" => STATE_PLAYER_TURN,
      "confirm" => STATE_NEXT_PLAYER,
      "zombiePass" => STATE_NEXT_PLAYER,
    ]
  ],


  // End of game => distribute remeaining buildings
  STATE_EOG_LAST_BUILDINGS => [
    "name" => "lastBuildingsPick",
    "description" => '',
    "type" => "game",
    "action" => "stLastBuildingPick",
    "transitions" => [
      "buildingToPlace" => STATE_EOG_PLACE_LAST_BUILDINGS,
      "noMoreBuilding" => STATE_EOG_SCORING
    ]
  ],

  // Place them (in parallel)
  STATE_EOG_PLACE_LAST_BUILDINGS => [
    "name" => "placeLastBuildings",
    "description" => clienttranslate('Players who gains buildings must place them'),
    "descriptionmyturn" => clienttranslate('Last buildings: ${you} must place your new building in your Alhambra or in your stock'),
    "type" => "multipleactiveplayer",
    "args" => "argPlaceLastBuilding",
    "possibleactions" => ["placeBuilding"],
    "action" => "stPlaceLastBuildings",
    "transitions" => [
      "noMoreBuilding" => STATE_EOG_SCORING
    ]
  ],

  // Compute scores
  STATE_EOG_SCORING => [
    "name" => "lastScoringRound",
    "description" => '',
    "type" => "game",
    "action" => "stLastScoringRound",
    "transitions" => [
      "" => STATE_END_OF_GAME
    ]
  ],



  // Final state.
  // Please do not modify (and do not overload action/args methods).
  STATE_END_OF_GAME => [
    "name" => "gameEnd",
    "description" => clienttranslate("End of game"),
    "type" => "manager",
    "action" => "stGameEnd",
    "args" => "argGameEnd"
  ]
];
