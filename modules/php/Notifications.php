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

  public static function startNewTurn(){
    // Useful as a sync point for undo
    self::notifyAll('startTurn', '', []);
  }

  public static function reformingMoneyDeck(){
    self::notifyAll("noMoreMoney", clienttranslate("No more money card: recreating a deck"), []);
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


  public static function giveToNeutral($player, $building, $x, $y){
    self::notifyAll("placeBuilding", clienttranslate('${player_name} gives a ${building_type_pre}${building_type}${building_type_post} to Neutral player'), [
      'player_name' => $player->getName(),
      'player_id' => 0,
      'building' => $building,
      'x' => $x,
      'y' => $y,
      'stock' => false,
    ]);
  }

  public static function newBuildingsForNeutral($buildings, $nToDraw, $deckCount){
    self::notifyAll("newBuildingsForNeutral", clienttranslate('${nb} tiles are drawn from the deck for the Neutral player'), [
      "buildings" => $buildings,
      'nb' => $nToDraw,
      'count' => $deckCount
    ]);
  }

  public static function updateAlhambraStats($player, $longestWallScore, $buildingCounts){
    self::notifyAll("alhambraStats", '',  [
      "player" => $player,
      "walls" => $longestWallScore,
      "buildings" => $buildingCounts
    ]);
  }


  public static function startScoringRound($round){
    self::notifyAll('startScoringRound', clienttranslate('Starting scoring round n°${round}'), [
      'round' => $round
    ]);
  }

  public static function scoringBlock($round, $type, $rank, $players, $pointsPerPlayer){
    if($rank > $round)
      return;

    $n = count($players);
    $data = [
      'pointsPerPlayer' => $pointsPerPlayer,
      'rank' => $rank,
    ];
    $msg = "";
    if($n == 1){
      $msg = clienttranslate('${player_name} has the ${rank} highest number of ${building_type} and is awarded ${pointsPerPlayer}');
    } else {
      if($rank == 1 && $n ==2) $msg = clienttranslate('${player_name1} and ${player_name2} have the ${rank} highest number of ${building_type} and are awarded ${pointsPerPlayer}')
    }

    self::notifyAll('scoringBlock', $msg);
  }


/*
  public static function scoringRound($points){
    self::notifyAll("scoringRound", clienttranslate('Scoring round !'), $points);
  }
*/

  public static function endOfGame(){
    self::notifyAll("endOfGame", clienttranslate('The last building has been drawn: this is the end of the game!'), []);
  }

  public static function noGetBuilding($building, $moneyType, $bTie){
    $msg = $bTie? clienttranslate('Several players has the same value in ${money_name}, ${building_type_pre}${building_type}${building_type_post} stays in buildingsite')
        : clienttranslate('Nobody has any ${money_name}, ${building_type_pre}${building_type}${building_type_post} stays in buildingsite');

    self::notifyAll("nogetBuilding", $msg, [
      'building' => $building,
      'money' => $moneyType,
    ]);
  }

  public static function getFreeBuilding($player, $building, $moneyType){
    self::notifyAll("getBuilding", clienttranslate('${player_name} gets ${building_type_pre}${building_type}${building_type_post} because he has the most ${money_name}'), [
      'player' => $player,
      'building' => $building,
      'money' => $moneyType,
    ]);
  }

  public static function updatePlacementOptions($player, $buildings){
    self::notify($player->getId(), 'updatePlacementOptions', '', [
      'buildings' => $buildings,
    ]);
  }


  public static function clearTurn($player, $notifIds){
    self::notify($player->getId(), 'clearTurnPrivate', '', [
      'hand' => $player->getMoneyCards(),
    ]);

    self::notifyAll('clearTurn', clienttranslate('${player_name} restart their turn'), [
      'player' => $player,
      'notifIds' => $notifIds,

      // Fetch again to make sur we don't have cached data
      'playerData' => Players::get($player->getId())->getUiData(null),
      'neutral' => Players::getNeutral()->getUiData(0),
      'buildings' => Buildings::getUiData(),
      'moneyCards' => Money::getUiData(),
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

    $moneyNames = [
      1 => clienttranslate("couronne"), // yellow
      2 => clienttranslate("dirham"),   // green
      3 => clienttranslate("dinar"),    // blue
      4 => clienttranslate("ducat")     // orange
    ];

    //#### CARDS #####
    if(isset($args['cards'])) {
      $description = '';
      $description_args = [];
      $i = 0;
      foreach($args['cards'] as $card ){
        if($description != '' )
            $description .= ', ';
        $description .= '<span class="moneytype moneytype_'.$card['type'].'">'.$card['value'].' <span class="moneyicon"></span><span class="moneyname">${money_name_'.$i.'}'.'</span></span>';
        $description_args['money_name_'.$i] = $moneyNames[ $card['type'] ];
        $description_args['i18n'][] = 'money_name_'.$i;
        $i++;
      }

      $args["description"] = [
        "log" => $description,
        "args" => $description_args
      ];
    }

    //#### MONEY ####
    if(isset($args['money'])) {
      $args['money_name'] = $moneyNames[$args['money']];
      $args['i18n'][] = 'money_name';
    }
  }
}

?>
