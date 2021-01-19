<?php
namespace ALH;
use Alhambra;

/*
 * Money cards
 *  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *  `card_type` varchar(16) NOT NULL,
 *  `card_type_arg` int(11) NOT NULL,
 *  `card_location` varchar(16) NOT NULL,
 *  `card_location_arg` int(11) NOT NULL,
 */
class Money extends \ALH\Helpers\Deck
{
  protected static $table = 'money';
  protected static $deck = null;
  public static function init()
  {
    self::$deck = self::getNew("module.common.deck");
    self::$deck->init(self::$table);
  }

  protected static function cast($row)
  {
    if(is_null($row))
      return null;

    $data = [
      'id' => (int) $row['id'],
      'type' => (int) $row['type'],
      'value' => (int) $row['type_arg'],
      'location' => $row['location'],

      'stockType' => (int) 10*$row['type'] + $row['type_arg'], // Useful for stock component
    ];

    if($row['location'] == 'hand')
      $data['pId'] = $row['location_arg'];

    return $data;
  }


  /*******************
  ****** SETUP *******
  *******************/

  /*
   * Setup new game : create deck, deal cards to players and shuffle scoring cards
   */
  public function setupNewGame($players)
  {
    self::createDeck($players);

    // Remove scoring from the deck to a temporary location
    self::DB()->update(['card_location' => 'scoring'])->where('card_type', CARD_SCORING)->run();
    self::shuffle();

    // Deal cards and compute first player id
    $firstPlayerId = self::dealCards($players);

    // Put back the scoring cards inside the deck
    $nCards = self::$deck->countCardInLocation("deck");
    foreach(self::getInLocation('scoring') as $card){
      $start = $card['value'] == 1? 3 : 1; // 1/5 <-> 2/5  or  3/5 <-> 4/5
      $pos = bga_rand(ceil($nCards * $start / 5), floor($nCards * ($start + 1)/ 5));
      self::$deck->insertCard($card['id'], 'deck', $pos);
    }

    return $firstPlayerId;
  }

  /*
   * Create the money cards (including scoring cards)
   */
  public function createDeck($players)
  {
    $isNeutral = count($players) == 2;
    $cards = [
      // Scoring
      ["type" => CARD_SCORING, "type_arg" => 1,"nbr" => 1],
      ["type" => CARD_SCORING, "type_arg" => 2,"nbr" => 1],
    ];

    // 4 types * 9 values * 3 copies (or 2 if neutral)
    $types = [CARD_DUCAT, CARD_DINAR, CARD_DIRHAM, CARD_COURONNE];
    foreach($types as $type){
      for($i = 1; $i <= 9; $i++){
        $cards[] = [
          'type' => $type,
          'type_arg' => $i,
          'nbr' => $isNeutral? 2 : 3,
        ];
      }
    }

    self::$deck->createCards($cards);
  }


  /*
   * Initial money distribution to players
   */
  public function dealCards($players)
  {
    // (everyone received at least 20)
    $min = null;
    foreach($players as $pId => $player){
      $total = 0;
      $nbr = 0;

      while($total < 20){
        $card = self::pickCard($pId);
        $total += $card['value'];
        $nbr++;
      }

      if(is_null($min) || $min['nbr'] > $nbr || ($min['nbr'] == $nbr && $min['total'] > $total) ){
        $min = [
          'pId' => $pId,
          'nbr' => $nbr,
          'total' => $total,
        ];
      }
    }

    return $min['pId'];
  }


  /*******************
  ***** GETTERS ******
  *******************/

  /*
   * getUiData : get visible cards
   */
  public function getUiData()
  {
    return [
      'pool' => self::getInLocation('pool'),
      'count' => self::countInLocation('deck'),
    ];
  }



  /*******************
  ***** ACTIONS ******
  *******************/

  /*
   * Fill the money pool to 4 cards
   */
  public function fillPool()
  {
    $nCards = self::countInLocation("pool");
    $newCards = [];
    while($nCards < 4) {
      $card = self::pickForLocation('deck', 'pool');

      // No more money card => reform deck
      if(is_null($card)){
        self::$deck->moveAllCardsInLocation('discard', 'deck');
        self::shuffle();

        $card = self::pickForLocation('deck', 'pool');
        Notifications::reformingMoneyDeck();

        if(is_null($card))
          throw new \feException("no more money card");
      }


      // Card draw is a scoring card ?
      if($card['type'] == CARD_SCORING){
        // Scoring at the end of turn
        self::$deck->moveCard($card['id'], 'retired', 0);
        Globals::setScoringRound($card['value']);
      }
      // Otherwise, just add it to pool
      else {
        $newCards[] = $card;
        $nCards++;
      }
    }

    $nCardsLeft = self::countInLocation('deck');
    Notifications::newMoneyCards($newCards, $nCardsLeft);
  }
}
