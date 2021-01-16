<?php
namespace ALH;

class Player extends Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  public function __construct($row)
  {
    $this->id = (int) $row['player_id'];
    $this->no = (int) $row['player_no'];
    $this->name = $row['player_name'];
    $this->color = $row['player_color'];
    $this->score = (int) $row['player_score'];
    $this->eliminated = $row['player_eliminated'] == 1;
    $this->zombie = $row['player_zombie'] == 1;
  }

  private $id;
  private $no; // natural order
  private $name;
  private $color;
  private $eliminated = false;
  private $zombie = false;
  private $score;


  /////////////////////////////////
  /////////////////////////////////
  //////////   Getters   //////////
  /////////////////////////////////
  /////////////////////////////////
  public function getId(){ return $this->id; }
  public function getNo(){ return $this->no; }
  public function getName(){ return $this->name; }
  public function getColor(){ return $this->color; }
  public function isEliminated(){ return $this->eliminated; }
  public function isZombie(){ return $this->zombie; }

  public function getUiData($pId)
  {
    return [
      'id'        => $this->id,
      'no'        => $this->no,
      'name'      => $this->name,
      'color'     => $this->color,
      'score'     => $this->score,
      'hand'      => $pId == $this->id? $this->getMoneyCards() : [],
      'stock'     => $this->getStock(),
      'board'     => $this->getBoard()->getUiData(),
    ];
  }

  // Construct the alhambra board object of player
  public function getBoard()
  {
    return new Board($this->id);
  }

  public function getStock()
  {
    return Buildings::getInLocation('stock', $this->id);
  }


  public function getMoneyCards()
  {
    return Money::getInLocation('hand', $this->id);
  }

  public function countMoneyCards()
  {
    return Money::countInLocation('hand', $this->id);
  }


  public function takeMoney($cards, $total)
  {
    // Move the cards
    foreach($cards as $card){
      Money::move($card['id'], 'hand', $this->id);
    }

    // Increase the stat
    Stats::takeMoney($this, $total);

    // Notify new cards
    Notifications::takeMoney($this, $cards);

    // Update money count
    $this->updateMoneyCount();
  }


  public function updateMoneyCount()
  {
    Notifications::updateMoneyCount($this);
  }

  public function placeBuildingInStock($building)
  {
    Buildings::move($building['id'], "stock", $this->id);
    Notifications::placeInStock($this, $building);
  }


  public function placeBuilding($building, $x, $y)
  {
    Buildings::placeAt($building['id'], $this->id, $x, $y);
    Notifications::placeAt($this, $building, $x, $y);
  }

  public function swapBuildings($buildingFromStock, $buildingOnAlhambra, $x, $y)
  {
    Buildings::move($buildingOnAlhambra['id'], "stock", $this->id);
    Buildings::placeAt($buildingFromStock['id'], $this->id, $x, $y);
    Notifications::swapBuildings($this, $buildingFromStock, $buildingOnAlhambra, $x, $y);
  }

}
