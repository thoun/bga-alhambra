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
 * gameoptions.inc.php
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

require_once('modules/php/constants.inc.php');

$game_options = [];


$game_preferences = [
  CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'values' => [
      CONFIRM_TIMER     => ['name' => totranslate('Enabled with timer')],
      CONFIRM_ENABLED   => ['name' => totranslate('Enabled')],
      CONFIRM_DISABLED  => ['name' => totranslate('Disabled')],
    ]
  ],
];
