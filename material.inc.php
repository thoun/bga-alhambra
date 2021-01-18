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
 * material.inc.php
 *
 * EmptyGame game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

require_once("modules/php/constants.inc.php");

// Points score for each building type:
$this->scoring = [
  PAVILLON => [ 16, 8, 1],
  SERAGLIO => [ 17, 9, 2],
  ARCADE =>   [18, 10, 3],
  CHAMBER =>  [19, 11, 4],
  GARDEN =>   [20, 12, 5],
  TOWER =>    [21, 13, 6],
];
