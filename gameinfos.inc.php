<?php

$gameinfos = [
  // Name of the game in English (will serve as the basis for translation)
  'game_name' => "Alhambra",

  // Game designer (or game designers, separated by commas)
  'designer' => 'Dirk Henn',

  // Game artist (or game artists, separated by commas)
  'artist' => 'Jörg Asselborn & Christof Tisch',

  // Year of FIRST publication of this game. Can be negative.
  'year' => 2003,

  // Game publisher
  'publisher' => 'Queen Games',

  // Url of game publisher website
  'publisher_website' => 'https://queen-games.com/',

  // Board Game Geek ID of the publisher
  'publisher_bgg_id' => 47,

  // Board game geek if of the game
  'bgg_id' => 6249,


  // Players configuration that can be played (ex: 2 to 4 players)
  'players' => [2,3,4,5,6],

  // Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
  'suggest_player_number' => 3,

  // Discourage players to play with this number of players. Must be null if there is no such advice.
  'not_recommend_player_number' => [2],


  // Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
  'estimated_duration' => 41,
  // Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
  'fast_additional_time' => 35,
  // Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
  'medium_additional_time' => 45,
  // Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
  'slow_additional_time' => 60,


  // Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
  'is_beta' => 0,
  // Is this game cooperative (all players wins together or loose together)
  'is_coop' => 0,


  // Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
  'complexity' => 3,
  // Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
  'luck' => 3,
  // Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
  'strategy' => 3,
  // Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
  'diplomacy' => 1,


  // Colors attributed to players
  'player_colors' => ["ff0000", "00ff00", "0000ff", "ffff00", "ffffff", "ff8000" ],
  // Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
  // NB: this parameter is used only to flag games supporting this feature; you must use (or not use) reattributeColorsBasedOnPreferences PHP method to actually enable or disable the feature.
  'favorite_colors_support' => true,



  // Game interface width range (pixels)
  // Note: game interface = space on the left side, without the column on the right
  'game_interface_width' => [
    // Minimum width
    //  default: 740
    //  maximum possible value: 740 (ie: your game interface should fit with a 740px width (correspond to a 1024px screen)
    //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
    'min' => 943,

    // Maximum width
    //  default: null (ie: no limit, the game interface is as big as the player's screen allows it).
    //  maximum possible value: unlimited
    //  minimum possible value: 740
    'max' => null
  ],

  // Game presentation
  // Short game presentation text that will appear on the game description page, structured as an array of paragraphs.
  // Each paragraph must be wrapped with totranslate() for translation and should not contain html (plain text without formatting).
  // A good length for this text is between 100 and 150 words (about 6 to 9 lines on a standard display)
  'presentation' => [
    totranslate("Granada: at the foot of the sierra Nevada mountains, one of the most exciting and interesting projects of the Spanish middle ages begins: the construction of the Alhambra."),
    totranslate("In the role of master builders the players try to hire experts from all across Europe to erect buildings for their Palace, who all insist on their native currency."),
    totranslate("The players have to be smart about their purchases to build the most impressive Palace in the end."),
    totranslate("Alhambra won the prestigious Spiel des Jahres in 2003.")
  ],

  // Games categories
  //  You can attribute a maximum of FIVE "tags" for your game.
  //  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
  //  Please see the "Game meta information" entry in the BGA Studio documentation for a full list of available tags:
  //  http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
  //  IMPORTANT: this list should be ORDERED, with the most important tag first.
  //  IMPORTANT: it is mandatory that the FIRST tag is 1, 2, 3 and 4 (= game category)
  'tags' => [2],


//////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)
// simple : A plays, B plays, C plays, A plays, B plays, ...
// circuit : A plays and choose the next player C, C plays and choose the next player D, ...
// complex : A+B+C plays and says that the next player is A+B
'is_sandbox' => false,
'turnControl' => 'simple'
////////
];
