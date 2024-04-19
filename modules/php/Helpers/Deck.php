<?php
namespace ALH\Helpers;

/*
 * Static deck module
 */
class Deck extends DB_Manager
{
  protected static $table = 'card';
  protected static $primary = 'card_id';
  protected static function cast($row){
    return $row;
  }

  protected static function castArray($rows)
  {
    $result = [];
    foreach($rows as $row){
      $result[] = static::cast($row);
    }
    return $result;
  }


  /*******************
  ***** SETTERS ******
  *******************/

  public static function shuffle($location = 'deck')
  {
    static::$deck->shuffle($location);
  }

  public static function move($cardId, $location, $location_arg = 0)
  {
    static::$deck->moveCard($cardId, $location, $location_arg);
  }


  /*******************
  ***** GETTERS ******
  *******************/
  public static function pickCard($pId)
  {
    return self::pickForLocation('deck', 'hand', $pId);
  }

  public static function pickForLocation($source, $location, $location_arg = 0)
  {
    return static::cast(static::$deck->pickCardForLocation($source, $location, $location_arg));
  }


  public static function getInLocation($location, $location_arg = null, $order_by = null)
  {
    return self::castArray(static::$deck->getCardsInLocation($location, $location_arg, $order_by));
  }

  public static function countInLocation($location, $location_arg = null)
  {
    return static::$deck->countCardsInLocation($location, $location_arg);
  }

  public static function get($cardIds)
  {
    return static::castArray(static::$deck->getCards($cardIds));
  }
}
