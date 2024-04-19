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
 * alhambra.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

$swdNamespaceAutoload = function ($class)
{
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'ALH') {
    array_shift($classParts);
    $file = dirname(__FILE__) . "/modules/php/" . implode(DIRECTORY_SEPARATOR, $classParts) . ".php";
    if (file_exists($file)) {
      require_once($file);
    } else {
      var_dump("Impossible to load Alhambra class : $class");
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


class Alhambra extends Table
{
  use ALH\States\StartOfGameTrait;
  use ALH\States\PlayerTurnTrait;
  use ALH\States\PlaceBuildingTrait;
  use ALH\States\ScoringRoundTrait;
  use ALH\States\EndOfGameTrait;

  public static $instance = null;
  public function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    ALH\Globals::declare($this);
    ALH\Buildings::init();
    ALH\Money::init();
  }

  public static function get()
  {
    return self::$instance;
  }

  protected function getGameName()
  {
      return "alhambra";
  }



  /*
   * setupNewGame:
   *  This method is called only once, when a new game is launched.
   * params:
   *  - array $players
   *  - mixed $options
   */
  protected function setupNewGame($players, $options = [])
  {
    ALH\Globals::setupNewGame();
    ALH\Players::setupNewGame($players);
    ALH\Stats::setupNewGame();
    ALH\Buildings::setupNewGame($players);
    $firstPlayerId = ALH\Money::setupNewGame($players);

    ALH\Globals::setFirstPlayer($firstPlayerId );
    $this->gamestate->changeActivePlayer($firstPlayerId);
//    self::activeNextPlayer();
  }


  /*
   * getAllDatas:
   *  Gather all informations about current game situation (visible by the current player).
   *  The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
   */
  protected function getAllDatas()
  {
    $pId = self::getCurrentPlayerId();
    return [
      'players' => ALH\Players::getUiData($pId),
      'isNeutral' => ALH\Globals::isNeutral(),
      'neutral' => ALH\Players::getNeutral()->getUiData(0),
      'buildings' => ALH\Buildings::getUiData(),
      'moneyCards' => ALH\Money::getUiData(),
      'scoreRound' => ALH\Globals::getScoringRound(),
      'canceledNotifIds' => ALH\Log::getCanceledNotifIds(),
    ];
  }


  /*
   * getGameProgression:
   *  Compute and return the current game progression approximation
   *  This method is called each time we are in a game state with the "updateGameProgression" property set to true
   */
  public function getGameProgression()
  {
    // Game progression: get the number of buildings remaining in the deck
    $remaining = ALH\Buildings::countInLocation('deck');
    $initial = 54;

    return 100 - ceil(100*($remaining/$initial));
  }




  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayer)
  {
    if( $state['name'] == 'initialMoney') {
      self::actAcceptMoney();
    }
    else if(in_array($state['name'], ['playerTurn', 'placeBuildings'])) {
      $this->gamestate->nextState( "zombiePass" );
    }
    else if( $state['name'] == 'placeLastBuildings') {
      $this->gamestate->setPlayerNonMultiactive($activePlayer, 'noMoreBuilding');
    }
    else {
      throw new BgaVisibleSystemException('Zombie player ' . $activePlayer . ' stuck in unexpected state ' . $state['name']);
    }
  }


  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
    if ($from_version <= 2011201610) {
      $sql = "ALTER TABLE `DBPREFIX_gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0";
      self::applyDbUpgradeToAllDB( $sql );

      $sql = "CREATE TABLE IF NOT EXISTS `DBPREFIX_log` (
        `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `turn` int(11) NOT NULL,
        `player_id` int(11) NOT NULL,
        `action` varchar(16) NOT NULL,
        `action_arg` json,
        PRIMARY KEY (`log_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
      self::applyDbUpgradeToAllDB( $sql );
    }

    if($from_version <= 2101182335){
      $sql = "UPDATE `DBPREFIX_building` SET card_location = 'alam' WHERE card_location = 'alamb'";
      self::applyDbUpgradeToAllDB( $sql );
    }

    if($from_version <= 2101210030){
     $result = self::getUniqueValueFromDB("SHOW COLUMNS FROM `gamelog` LIKE 'cancel'");
     if(is_null($result)){
       $sql = "ALTER TABLE `DBPREFIX_gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0";
       self::applyDbUpgradeToAllDB( $sql );
     }
    }

   if($from_verison <= 2101222317){
     $sql = "CREATE TABLE IF NOT EXISTS `DBPREFIX_log` (
       `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
       `turn` int(11) NOT NULL,
       `player_id` int(11) NOT NULL,
       `action` varchar(16) NOT NULL,
       `action_arg` json,
       PRIMARY KEY (`log_id`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
     self::applyDbUpgradeToAllDB( $sql );     
   }
  }


  ///////////////////////////////////////////////////////////
  // Exposing proteced method, please use at your own risk //
  ///////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId(){
    return self::$instance->getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text){
    return self::$instance->_($text);
  }
}
