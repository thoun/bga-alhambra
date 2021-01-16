<?php
namespace ALH;
use Alhambra;

class Notifications
{
  protected static function notifyAll($name, $msg, $data){
    self::updateArgs($data);
    Alhambra::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($pId, $name, $msg, $data){
    self::updateArgs($data);
    Alhambra::get()->notifyPlayer($pId, $name, $msg, $data);
  }


  public static function message($txt, $args = []){
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = []){
    $pId = ($player instanceof \CREW\Player)? $player->getId() : $player;
    self::notify($pId, 'message', $txt, $args);
  }


  public static function newMoneyCards($newCards, $nCardsLeft){
    self::notifyAll("newMoneyCards", '', [
      "cards" => $newCards,
      "count" => $nCardsLeft,
    ]);
  }


  public static function newBuildings($newBuildings, $nBuildingsLeft){
    self::notifyAll("newBuildings", '', [
      "buildings" => $newBuildings,
      "count" => $nBuildingsLeft,
    ]);
  }


  public static function takeMoney($player, $cards){
    self::notifyAll("takeMoney", clienttranslate('${player_name} takes ${description}'), [
      "player" => $player,
      "cards" => $cards,
    ]);
  }


  public static function updateMoneyCount($player){
    self::notifyAll('updateMoneyCount', '', [
      'player' => $player,
      'count' => $player->countMoneyCards(),
    ]);
  }

  public static function buyBuilding($player, $cards, $building){
    self::notifyAll("buyBuilding", clienttranslate('${player_name} buys ${building_type_pre}${building_type}${building_type_post} with ${description}'), [
      'player' => $player,
      'cards' => $cards,
      'building' => $building,
    ]);
  }


  public static function exactAmount($player){
    self::notifyAll("exactAmount", clienttranslate('${player_name} pays with the exact amount and replay !'), [
      'player' => $player
    ]);
  }


  public static function placeInStock($player, $building){
    self::notifyAll('placeBuilding', clienttranslate('${player_name} places a ${building_type_pre}${building_type}${building_type_post} in stock'), [
      'player' => $player,
      'building' => $building,
      'stock' => true,
    ]);
  }

  public static function placeAt($player, $building, $x, $y){
    self::notifyAll('placeBuilding', clienttranslate('${player_name} places a ${building_type_pre}${building_type}${building_type_post} in its Alhambra'), [
      'player' => $player,
      'building' => $building,
      'stock' => false,
      'x' => $x,
      'y' => $y,
    ]);
  }


  public static function swapBuildings($player, $buildingFromStock, $buildingOnAlhambra, $x, $y){
    self::notifyAll('swapBuildings', clienttranslate('${player_name} transform its Alhambra by swapping a ${building_type_pre}${building_type}${building_type_post} and a ${building2_type_pre}${building2_type}${building2_type_post}'), [
      'player' => $player,
      'building' => $buildingOnAlhambra,
      'building2' => $buildingFromStock,
      'stock' => true,
      'x' => $x,
      'y' => $y,
    ]);
  }


  /*
   * Automatically adds some standard field about player and/or card/task
   */
  public static function updateArgs(&$args){
    //#### PLAYER #####
    if(isset($args['player'])){
      $args['player_name'] = $args['player']->getName();
      $args['player_id'] = $args['player']->getId();
      unset($args['player']);
    }

    //#### BUILDING #####
    if(isset($args['building'])){
      $names = [
        FONTAIN => clienttranslate('fountain'),    // start
        PAVILLON => clienttranslate("pavillon"),    // blue
        SERAGLIO => clienttranslate("seraglio"),    // red
        ARCADE => clienttranslate("arcades"),     // brown
        CHAMBER => clienttranslate("chambers"),    // white
        GARDEN => clienttranslate("garden"),      // green
        TOWER => clienttranslate("tower")        // purple
      ];

      if(!isset($args['i18n'])){
        $args['i18n'] = [];
      }
      $args['i18n'][] = 'building_type';
      $args['building_type'] = $names[$args['building']['type'] ];
      $args["building_type_pre"] = '<span class="buildingtype buildingtype_'.$args['building']['type'] .'">';
      $args["building_type_post"] = '</span>';

      if(isset($args['building2'])){
        $args['i18n'][] = 'building2_type';
        $args['building2_type'] = $names[$args['building2']['type'] ];
        $args["building2_type_pre"] = '<span class="buildingtype buildingtype_'.$args['building2']['type'] .'">';
        $args["building2_type_post"] = '</span>';
      }
    }


    //#### CARDS #####
    if(isset($args['cards'])) {
      $names = [
        1 => clienttranslate("couronne"), // yellow
        2 => clienttranslate("dirham"),   // green
        3 => clienttranslate("dinar"),    // blue
        4 => clienttranslate("ducat")     // orange
      ];

      $description = '';
      $description_args = [];
      $i = 0;
      foreach($args['cards'] as $card ){
        if($description != '' )
            $description .= ', ';
        $description .= '<span class="moneytype moneytype_'.$card['type'].'">'.$card['value'].' <span class="moneyicon"></span><span class="moneyname">${money_name_'.$i.'}'.'</span></span>';
        $description_args['money_name_'.$i] = $names[ $card['type'] ];
        $description_args['i18n'][] = 'money_name_'.$i;
        $i++;
      }

      $args["description"] = [
        "log" => $description,
        "args" => $description_args
      ];
    }
  }
}

?>
