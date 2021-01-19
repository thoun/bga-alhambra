<?php

/*
 * State constants
 */
define("STATE_SETUP", 1);
define("STATE_INIT_MONEY", 2);

define("STATE_NEXT_PLAYER", 10);
define("STATE_PLAYER_TURN", 11);
define("STATE_PLACE_BUILDING", 12);


define("STATE_EOG_LAST_BUILDINGS", 30);
define("STATE_EOG_PLACE_LAST_BUILDINGS", 31);
define("STATE_EOG_SCORING", 32);

define("ST_CONFIRM_TURN", 40);

define("STATE_END_OF_GAME",99);



/*
 * Card constants
 */
define("CARD_SCORING", 0);
define("CARD_COURONNE", 1);
define("CARD_DIRHAM", 2);
define("CARD_DINAR", 3);
define("CARD_DUCAT", 4);


/*
 * Building constants
 */
define("FONTAIN", 0);
define("PAVILLON", 1);
define("SERAGLIO", 2);
define("ARCADE", 3);
define("CHAMBER", 4);
define("GARDEN", 5);
define("TOWER", 6);


/*
 * User preference option
 */
define('CONFIRM', 102);
define('CONFIRM_TIMER', 1);
define('CONFIRM_ENABLED', 2);
define('CONFIRM_DISABLED', 3);
