<?php
namespace ALH;
use Alhambra;

class Stats
{
  protected static function init($type, $name, $value = 0){
    Alhambra::get()->initStat($type, $name, $value);
  }

  public static function inc($name, $player = null, $value = 1, $log = true){
// TODO : keep ?
    $pId = is_null($player)? null : ( ($player instanceof \ALH\Player)? $player->getId() : $player );
//    Log::insert($pId, 'changeStat', [ 'name' => $name, 'value' => $value ]);
    Alhambra::get()->incStat($value, $name, $pId);
  }


  public static function get($name, $player = null){
    Alhambra::get()->getStat($name, $player);
  }

  protected static function set($value, $name, $player = null){
    $pId = is_null($player)? null : ( ($player instanceof \ALH\Player)? $player->getId() : $player );
    Alhambra::get()->setStat($value, $name, $pId);
  }


  public static function setupNewGame(){
    $stats = Alhambra::get()->getStatTypes();

    self::init('table', 'turn_number');
    self::init('table', 'longest_wall_all');

    foreach ($stats['player'] as $key => $value) {
      if($value['id'] > 10 && $value['type'] == 'int')
        self::init('player', $key);
    }
  }


  public static function startNewTurn(){
    self::inc('turn_number');
  }

  public static function takeMoney($player, $total){
    self::inc('money_taken', $player->getId(), $total);
  }

  public static function exactAmount($player){
    self::inc('exact_amount', $player->getId());
  }
}

?>
