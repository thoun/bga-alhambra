<?php
namespace ALH;
use Alhambra;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */
class Players extends \ALH\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \ALH\Player($row);
  }


  public function setupNewGame($players)
  {
    // Create players
    self::DB()->delete();

    $colors = ["ff0000", "187b00", "0000ff", "ffff00", "ffffff", "ff8000"];
    $query = self::DB()->multipleInsert(['player_id', 'player_color', 'player_canal', 'player_name', 'player_avatar']);
    $values = [];
    foreach ($players as $pId => $player) {
      $color = array_shift($colors);
      $values[] = [ $pId, $color, $player['player_canal'], $player['player_name'], $player['player_avatar'] ];
    }
    $query->values($values);

    // Reattribute colors
    $gameInfos = Alhambra::get()->getGameinfos();
    Alhambra::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);
    Alhambra::get()->reloadPlayersBasicInfos();

    Globals::setNeutral(count($players) == 2);
  }



  public function getActiveId()
  {
    return Alhambra::get()->getActivePlayerId();
  }

  public function getCurrentId()
  {
    return Alhambra::get()->getCurrentPId();
  }

  public function getAll(){
    return self::DB()->get(false);
  }


  /*
   * Return the number of players
   */
  public function count()
  {
    return self::DB()->count();
  }


  /*
   * get : returns the Player object for the given player ID
   */
  public function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()->where($pId)->get();
  }

  public function getActive()
  {
    return self::get();
  }

  public function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public function getNextId($player)
  {
    $table = Alhambra::get()->getNextPlayerTable();
    return $table[$player->getId()];
  }

  public function getPrevId($player)
  {
    $table = Alhambra::get()->getPrevPlayerTable();
    return $table[$player->getId()];
  }


  /*
   * getUiData : get all ui data of all players : id, no, name, team, color, powers list, farmers
   */
  public function getUiData($pId)
  {
    return self::getAll()->assocMap(function($player) use ($pId){ return $player->getUiData($pId); });
  }

  public function getNeutral()
  {
    $row = [
      'player_id' => 0,
      'player_no' => -1,
      'player_color' => "000000",
      'player_name' => clienttranslate('Dirk'),
      'player_score' => 0,
      'player_eliminated' => 0,
      'player_zombie' => 0,
      'player_longest_wall' => 0,
    ];

    return new \ALH\Player($row);
  }

  public function getBuildingCounts()
  {
    $data = self::getAll()->assocMap(function($player){ return $player->getBoard()->getBuildingCounts(); });
    if(Globals::isNeutral()){
      $data[0] = self::getNeutral()->getBoard()->getBuildingCounts();
    }
    return $data;
  }
}
