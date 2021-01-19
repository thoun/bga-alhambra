<?php
namespace ALH;
use Alhambra;

/*
 * Log: a class that allows to log some actions
 *   and then fetch these actions latter
 */
class Log extends \ALH\Helpers\DB_Manager
{
  protected static $table = 'log';
  protected static $primary = 'log_id';
  protected static $associative = false;
  protected static function cast($row)
  {
    return [
      'id' => (int) $row['log_id'],
      'pId' => (int) $row['player_id'],
      'turn' => (int) $row['turn'],
      'action' => $row['action'],
      'arg' => json_decode($row['action_arg'], true),
    ];
  }

  /*
   * Utils : where filter with player and current turn
   */
  private function getFilteredQuery($pId){
    return self::DB()->where('player_id', $pId)->where('turn', Globals::getTurnNumber() )->orderBy("log_id", "DESC");
  }

////////////////////////////////
////////////////////////////////
//////////   Adders   //////////
////////////////////////////////
////////////////////////////////

  /*
   * insert: add a new log entry
   * params:
   *   - mixed $player : either the id or an object of the player who is making the action
   *   - string $action : the name of the action
   *   - array $args : action arguments
   */
  public static function insert($player, $action, $args = [])
  {
    $pId = is_integer($player)? $player : $player->getId();
    $turn = Globals::getTurnNumber();
    $actionArgs = json_encode($args);
    self::DB()->insert([
      'turn' => $turn,
      'player_id' => $pId,
      'action' => $action,
      'action_arg' => $actionArgs,
    ]);
  }


/////////////////////////////////
/////////////////////////////////
//////////   Getters   //////////
/////////////////////////////////
/////////////////////////////////
  public static function getLastActions($pId)
  {
    return self::getFilteredQuery($pId)->get();
  }

  public static function getLastAction($action, $pId, $limit = 1)
  {
    return self::getFilteredQuery($pId)->where('action', $action)->limit($limit)->get($limit == 1);
  }


  public static function hasSomethingToCancel($pId)
  {
    return !empty(self::getLastActions($pId));
  }



/////////////////////////////////
/////////////////////////////////
//////////   Setters   //////////
/////////////////////////////////
/////////////////////////////////
  public static function clearTurn($pId)
  {
    // Cancel the game notifications
    foreach(self::getFilteredQuery($pId)->get(false) as $action){
      if($action['action'] == "changeStat"){
        Stats::inc($action['arg']['name'], $action['pId'], -$action['arg']['value'], false);
      }

      // Cancel taking money
      if($action['action'] == "takeMoney"){
        foreach($action['arg']['cards'] as $card){
          Money::move($card['id'], 'pool');
        }
      }

      // Cancel buying building
      if($action['action'] == "buyBuilding"){
        foreach($action['arg']['cards'] as $card){
          Money::move($card['id'], 'hand', $pId);
        }
        $building = $action['arg']['building'];
        Buildings::move($building['id'], 'buildingsite', $building['pos']);
      }

      // Cancel Alhambra placement
      if($action['action'] == "placeBuilding"){
        $building = $action['arg']['building'];
        Buildings::move($building['id'], $building['location'], $building['location_arg']);
      }

      // Cancel Alhambra swap
      if($action['action'] == "swapBuildings"){
        $building = $action['arg']['building'];
        $piece = $action['arg']['piece'];
        $x = $action['arg']['x'];
        $y = $action['arg']['y'];
        Buildings::move($building['id'], "stock", $pId);
        Buildings::placeAt($piece['id'], $pId, $x, $y);
      }

      // Cancel buildings giving
      if($action['action'] == "giveToNeutral"){
        $buildings = $action['arg']['buildings'];
        foreach($buildings as $building)
          Buildings::move($building['id'], $building['location'], $building['location_arg']);
      }
    }

    // Clear the log
    self::getFilteredQuery($pId)->delete()->run();

    // Cancel the notifications
    return self::cancelNotifs($pId);
  }



//////////////////////////////////////////////
//////////////////////////////////////////////
//////////   CANCEL NOTIFICATIONS   //////////
//////////////////////////////////////////////
//////////////////////////////////////////////

  /*
   * getCancelMoveIds : get all cancelled notifs IDs from BGA gamelog, used for styling the notifications on page reload
   */
  protected function extractNotifIds($notifications){
    $notificationUIds = [];
    foreach($notifications as $notification){
      $data = \json_decode($notification, true);
      array_push($notificationUIds, $data[0]['uid']);
    }
    return $notificationUIds;
  }


  public function getCanceledNotifIds()
  {
    return self::extractNotifIds(self::getObjectListFromDb("SELECT `gamelog_notification` FROM gamelog WHERE `cancel` = 1", true));
  }



  /*
   * getLastStartTurnNotif : find the packet_id of the last notifications
   */
  protected function getLastStartTurnNotif(){
    $packets = self::getObjectListFromDb("SELECT `gamelog_packet_id`, `gamelog_notification` FROM gamelog WHERE `gamelog_player` IS NULL ORDER BY gamelog_packet_id DESC");
    foreach($packets as $packet){
      $data = \json_decode($packet['gamelog_notification'], true);
      foreach($data as $notification){
        if($notification['type'] == 'startTurn')
          return $packet['gamelog_packet_id'];
      }
    }
    return 0;
  }


  protected function cancelNotifs($pId)
  {
    $packetId = self::getLastStartTurnNotif();
    $whereClause = "WHERE `gamelog_current_player` = $pId AND `gamelog_packet_id` > $packetId";
    $notifIds = self::extractNotifIds(self::getObjectListFromDb("SELECT `gamelog_notification` FROM gamelog $whereClause", true));
    self::DbQuery("UPDATE gamelog SET `cancel` = 1 $whereClause");
    return $notifIds;
  }

}
