<?php
 /**
  * alhambra.action.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * alhambra main action entry point
  *
  */
  
  
  class action_alhambra extends APP_GameAction
  { 
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "alhambra_alhambra";
            self::trace( "Complete reinitialization of board game" );
      }


  	} 
    public function acceptMoney()
    {
        self::setAjaxMode();     
        $result = $this->game->acceptMoney();
        self::ajaxResponse( );
    }
    public function takeMoney()
    {
        self::setAjaxMode();     
        $cards_raw = self::getArg( "cards", AT_numberlist, true );
        if( substr( $cards_raw, -1 ) == ';' )
            $cards_raw = substr( $cards_raw, 0, -1 );
        $cards = explode( ';', $cards_raw );
        $result = $this->game->takeMoney( $cards );
        self::ajaxResponse( );
    }
    public function buyBuilding()
    {
        self::setAjaxMode();     
        $building_id = self::getArg( "building", AT_posint, true );
        $cards_raw = self::getArg( "cards", AT_numberlist, true );
        if( substr( $cards_raw, -1 ) == ';' )
            $cards_raw = substr( $cards_raw, 0, -1 );
        $cards = explode( ';', $cards_raw );
        $result = $this->game->buyBuilding( $building_id, $cards );
        self::ajaxResponse( );
    }

    public function transformAlhambraPlace()
    {
        self::setAjaxMode();     
        $building_id = self::getArg( "building", AT_posint, true );
        $x = self::getArg( "x", AT_int, true );
        $y = self::getArg( "y", AT_int, true );
        $this->game->placeBuilding( $building_id, false, $x, $y );
        self::ajaxResponse( );
    }
    public function transformAlhambraRemove()
    {
        self::setAjaxMode();     
        $building_to_remove = self::getArg( "remove", AT_posint, true );
        $this->game->transformAlhambraRemove( $building_to_remove );
        self::ajaxResponse( );
    }
    
    public function placeBuilding()
    {
        self::setAjaxMode();     
        $building_id = self::getArg( "building", AT_posint, true );
        if( self::isArg( 'x' ) )    // place building in the alhambra
        {
            $x = self::getArg( "x", AT_int, true );
            $y = self::getArg( "y", AT_int, true );
            $this->game->placeBuilding( $building_id, true, $x, $y );
        }
        else
        {
            // place building in stock
            $this->game->placeBuilding( $building_id, true );
        }
        self::ajaxResponse( );
    }

    public function giveneutral()
    {
        self::setAjaxMode();     
        $this->game->giveneutral();
        self::ajaxResponse( );

    }

  }
  
?>
