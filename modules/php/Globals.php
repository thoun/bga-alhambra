<?php
namespace ALH;
use Alhambra;

/*
 * Globals
 */
class Globals
{
  /* Exposing methods from Table object singleton instance */
  protected static function init($name, $value){
    Alhambra::get()->setGameStateInitialValue($name, $value);
  }

  protected static function set($name, $value){
    Alhambra::get()->setGameStateValue($name, $value);
  }

  public static function get($name){
    return Alhambra::get()->getGameStateValue($name);
  }

  protected static function inc($name, $value = 1){
    return Alhambra::get()->incGameStateValue($name, $value);
  }


  /*
   * Declare globas (in the constructor of game.php)
   */
  private static $globals = [
    "scoringAtTheEndOfTurn" => 0,
    "neutral_player" => 0,
    "first_player" => 0,
    "lastbuilding_1" => 0,
    "lastbuilding_2" => 0,
    "lastbuilding_3" => 0,
    "lastbuilding_4" => 0,
    "turn_number" => 0,
  ];

  public static function declare($game){
    // Game options label
    $labels = [];

    // Add globals with indexes starting at 10
    $id = 10;
    foreach(self::$globals as $name => $initValue){
      $labels[$name] = $id++;
    }
    $game->initGameStateLabels($labels);
  }

  /*
   * Init
   */
  public static function setupNewGame(){
    foreach(self::$globals as $name => $initValue){
      self::init($name, $initValue);
    }
  }




  /*
   * Getters
   */
  public static function getTurnNumber(){
    return (int) self::get('turn_number');
  }

  public static function isNeutral(){
    return (bool) self::get('neutral_player');
  }

  public static function getFirstPlayerId(){
    return (int) self::get('first_player');
  }

  public static function isFirstPlayer($pId){
    return $pId == self::getFirstPlayerId();
  }

  public static function isScoringRound(){
    return self::get('scoringAtTheEndOfTurn') > 0;
  }

  public static function getScoringRound(){
    return self::get('scoringAtTheEndOfTurn');
  }

  public static function getLastBuilding($type){
    return self::get('lastbuilding_'.$type);
  }


  /*
   * Setters
   */
  public static function setNeutral($value){
    self::set('neutral_player', $value? 1 : 0);
  }

  public static function setFirstPlayer($pId){
    self::set('first_player', $pId);
  }

  public static function startNewTurn(){
    self::inc('turn_number');
  }

  public static function setScoringRound($round){
    self::set('scoringAtTheEndOfTurn', $round);
  }

  public static function setLastBuilding($type, $maxPlayer){
    self::set( 'lastbuilding_'.$type, $maxPlayer);
  }
}
