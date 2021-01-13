<?php

///// Materials used in Alhambra

$this->building_types = array(
    0 => 'fountain',    // start
    1 => "pavillon",    // blue
    2 => "seraglio",    // red
    3 => "arcades",     // brown
    4 => "chambers",    // white
    5 => "garden",      // green
    6 => "tower"        // purple
);

$this->building_types_tr = array(
    0 => self::_('fountain'),    // start
    1 => self::_("pavillon"),    // blue
    2 => self::_("seraglio"),    // red
    3 => self::_("arcades"),     // brown
    4 => self::_("chambers"),    // white
    5 => self::_("garden"),      // green
    6 => self::_("tower"),        // purple
    10 => clienttranslate('fountain'),    // start
    11 => clienttranslate("pavillon"),    // blue
    12 => clienttranslate("seraglio"),    // red
    13 => clienttranslate("arcades"),     // brown
    14 => clienttranslate("chambers"),    // white
    15 => clienttranslate("garden"),      // green
    16 => clienttranslate("tower"),        // purple
);

// Points score for each building type:
// scoring round no (1 to 3) => building => rank
$this->scoring = array(

    // First scoring round
    1 => array(
        1 => array( 1 => 1 ),
        2 => array( 1 => 2 ),
        3 => array( 1 => 3 ),
        4 => array( 1 => 4 ),
        5 => array( 1 => 5 ),
        6 => array( 1 => 6 )
    ),

    // Second scoring round
    2 => array(
        1 => array( 1 => 8, 2 => 1 ),
        2 => array( 1 => 9, 2 => 2 ),
        3 => array( 1 => 10, 2 => 3 ),
        4 => array( 1 => 11, 2 => 4 ),
        5 => array( 1 => 12, 2 => 5 ),
        6 => array( 1 => 13, 2 => 6 )
    ),

    // Third (and last) scoring round
    3 => array(
        1 => array( 1 => 16, 2 => 8, 3 => 1 ),
        2 => array( 1 => 17, 2 => 9, 3 => 2 ),
        3 => array( 1 => 18, 2 => 10, 3 => 3 ),
        4 => array( 1 => 19, 2 => 11, 3 => 4 ),
        5 => array( 1 => 20, 2 => 12, 3 => 5 ),
        6 => array( 1 => 21, 2 => 13, 3 => 6 )
    )


);

$this->building_tiles = array(

    // 6 startings fountains
    0 => array( "type" => 0, "cost" => 0, "wall" => array(), 'img' => array('x' => 0, 'y' => 0 ) ),
    1 => array( "type" => 0, "cost" => 0, "wall" => array(), 'img' => array('x' => 0, 'y' => 1 ) ),
    2 => array( "type" => 0, "cost" => 0, "wall" => array(), 'img' => array('x' => 0, 'y' => 2 ) ),
    3 => array( "type" => 0, "cost" => 0, "wall" => array(), 'img' => array('x' => 0, 'y' => 3 ) ),
    4 => array( "type" => 0, "cost" => 0, "wall" => array(), 'img' => array('x' => 0, 'y' => 4 ) ),
    5 => array( "type" => 0, "cost" => 0, "wall" => array(), 'img' => array('x' => 0, 'y' => 5 ) ),

    // Pavillon (blue)
    6 => array( "type" => 1, "cost" => 2, "wall" => array( 0,1,3 ), 'img' => array('x' => 1, 'y' => 6 ) ),
    7 => array( "type" => 1, "cost" => 3, "wall" => array( 2,3 ), 'img' => array('x' => 1, 'y' => 5 ) ),
    8 => array( "type" => 1, "cost" => 4, "wall" => array( 1,2 ), 'img' => array('x' => 1, 'y' => 4 ) ),
    9 => array( "type" => 1, "cost" => 5, "wall" => array( 0,3 ), 'img' => array('x' => 1, 'y' => 3 ) ),
    10 => array( "type" => 1, "cost" => 6, "wall" => array( 0 ), 'img' => array('x' => 1, 'y' => 2 ) ),
    11 => array( "type" => 1, "cost" => 7, "wall" => array( 1 ), 'img' => array('x' => 1, 'y' => 1 ) ),
    12 => array( "type" => 1, "cost" => 8, "wall" => array(  ), 'img' => array('x' => 1, 'y' => 0 ) ),

    // Red (seraglio)
    13 => array( "type" => 2, "cost" => 3, "wall" => array( 1,2,3 ), 'img' => array('x' => 2, 'y' => 6 ) ),
    14 => array( "type" => 2, "cost" => 4, "wall" => array( 0,1 ), 'img' => array('x' => 2, 'y' => 5 ) ),
    15 => array( "type" => 2, "cost" => 5, "wall" => array( 2,3 ), 'img' => array('x' => 2, 'y' => 4 ) ),
    16 => array( "type" => 2, "cost" => 6, "wall" => array( 1,2 ), 'img' => array('x' => 2, 'y' => 3 ) ),
    17 => array( "type" => 2, "cost" => 7, "wall" => array( 3 ), 'img' => array('x' => 2, 'y' => 2 ) ),
    18 => array( "type" => 2, "cost" => 8, "wall" => array( 2 ), 'img' => array('x' => 2, 'y' => 1 ) ),
    19 => array( "type" => 2, "cost" => 9, "wall" => array( ), 'img' => array('x' => 2, 'y' => 0 ) ),

    // Brown (arcades)
    20 => array( "type" => 3, "cost" => 4, "wall" => array( 0,1,2 ), 'img' => array('x' => 3, 'y' => 8 ) ),
    21 => array( "type" => 3, "cost" => 5, "wall" => array( 0,3 ), 'img' => array('x' => 3, 'y' => 7 ) ),
    22 => array( "type" => 3, "cost" => 6, "wall" => array( 2,3 ), 'img' => array('x' => 3, 'y' => 6 ) ),
    23 => array( "type" => 3, "cost" => 6, "wall" => array( 0,1 ), 'img' => array('x' => 3, 'y' => 5 ) ),
    24 => array( "type" => 3, "cost" => 7, "wall" => array( 1,2 ), 'img' => array('x' => 3, 'y' => 4 ) ),
    25 => array( "type" => 3, "cost" => 8, "wall" => array( 0 ), 'img' => array('x' => 3, 'y' => 3 ) ),
    26 => array( "type" => 3, "cost" => 8, "wall" => array( 1 ), 'img' => array('x' => 3, 'y' => 2 ) ),
    27 => array( "type" => 3, "cost" => 9, "wall" => array( ), 'img' => array('x' => 3, 'y' => 1 ) ),
    28 => array( "type" => 3, "cost" => 10, "wall" => array( ), 'img' => array('x' => 3, 'y' => 0 ) ),

    // Chambers (white)
    29 => array( "type" => 4, "cost" => 5, "wall" => array( 0,2,3 ), 'img' => array('x' => 4, 'y' => 8 ) ),
    30 => array( "type" => 4, "cost" => 6, "wall" => array( 1,2 ), 'img' => array('x' => 4, 'y' => 7 ) ),
    31 => array( "type" => 4, "cost" => 7, "wall" => array( 2,3 ), 'img' => array('x' => 4, 'y' => 6 ) ),
    32 => array( "type" => 4, "cost" => 7, "wall" => array( 0,1 ), 'img' => array('x' => 4, 'y' => 5 ) ),
    33 => array( "type" => 4, "cost" => 8, "wall" => array( 0,3 ), 'img' => array('x' => 4, 'y' => 4 ) ),
    34 => array( "type" => 4, "cost" => 9, "wall" => array( 3 ), 'img' => array('x' => 4, 'y' => 3 ) ),
    35 => array( "type" => 4, "cost" => 9, "wall" => array( 2 ), 'img' => array('x' => 4, 'y' => 2 ) ),
    36 => array( "type" => 4, "cost" => 10, "wall" => array( ), 'img' => array('x' => 4, 'y' => 1 ) ),
    37 => array( "type" => 4, "cost" => 11, "wall" => array( ), 'img' => array('x' => 4, 'y' => 0 ) ),

    // Green (garden)
    38 => array( "type" => 5, "cost" => 6, "wall" => array( 1,2,3 ), 'img' => array('x' => 5, 'y' => 10 ) ),
    39 => array( "type" => 5, "cost" => 7, "wall" => array( 0,2,3 ), 'img' => array('x' => 5, 'y' => 9 ) ),
    40 => array( "type" => 5, "cost" => 8, "wall" => array( 2,3 ), 'img' => array('x' => 5, 'y' => 8 ) ),
    41 => array( "type" => 5, "cost" => 8, "wall" => array( 0,1 ), 'img' => array('x' => 5, 'y' => 7 ) ),
    42 => array( "type" => 5, "cost" => 8, "wall" => array( 0,3 ), 'img' => array('x' => 5, 'y' => 6 ) ),
    43 => array( "type" => 5, "cost" => 9, "wall" => array( 1 ), 'img' => array('x' => 5, 'y' => 5 ) ),
    44 => array( "type" => 5, "cost" => 10, "wall" => array( 0 ), 'img' => array('x' => 5, 'y' => 4 ) ),
    45 => array( "type" => 5, "cost" => 10, "wall" => array( 3 ), 'img' => array('x' => 5, 'y' => 3 ) ),
    46 => array( "type" => 5, "cost" => 10, "wall" => array(  ), 'img' => array('x' => 5, 'y' => 2 ) ),
    47 => array( "type" => 5, "cost" => 11, "wall" => array( ), 'img' => array('x' => 5, 'y' => 1 ) ),
    48 => array( "type" => 5, "cost" => 12, "wall" => array( 2 ), 'img' => array('x' => 5, 'y' => 0 ) ),

    // Towers (purple)
    49 => array( "type" => 6, "cost" => 7, "wall" => array( 0,1,3 ), 'img' => array('x' => 6, 'y' => 10 ) ),
    50 => array( "type" => 6, "cost" => 8, "wall" => array( 0,1,2 ), 'img' => array('x' => 6, 'y' => 9 ) ),
    51 => array( "type" => 6, "cost" => 9, "wall" => array( 1,2 ), 'img' => array('x' => 6, 'y' => 8 ) ),
    52 => array( "type" => 6, "cost" => 9, "wall" => array( 0,1 ), 'img' => array('x' => 6, 'y' => 7 ) ),
    53 => array( "type" => 6, "cost" => 9, "wall" => array( 0,3 ), 'img' => array('x' => 6, 'y' => 6 ) ),
    54 => array( "type" => 6, "cost" => 10, "wall" => array( 3 ), 'img' => array('x' => 6, 'y' => 5 ) ),
    55 => array( "type" => 6, "cost" => 11, "wall" => array( 0 ), 'img' => array('x' => 6, 'y' => 4 ) ),
    56 => array( "type" => 6, "cost" => 11, "wall" => array( 2 ), 'img' => array('x' => 6, 'y' => 3 ) ),
    57 => array( "type" => 6, "cost" => 11, "wall" => array(  ), 'img' => array('x' => 6, 'y' => 2 ) ),
    58 => array( "type" => 6, "cost" => 12, "wall" => array( ), 'img' => array('x' => 6, 'y' => 1 ) ),
    59 => array( "type" => 6, "cost" => 13, "wall" => array( 1 ), 'img' => array('x' => 6, 'y' => 0 ) )

);


$this->money_name = array(
    // Note: money type 0 is scoring round card
    1 => "couronne", // yellow
    2 => "dirham",   // green
    3 => "dinar",    // blue
    4 => "ducat"     // orange
);


$this->money_cards = array(

    // Scoring
    array(  "type" => "0","type_arg" => 1,"nbr" => 1 ),
    array(  "type" => "0","type_arg" => 2,"nbr" => 1 ),

    // Couronne
    array(  "type" => "1","type_arg" => 1,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 2,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 3,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 4,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 5,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 6,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 7,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 8,"nbr" => 3 ),
    array(  "type" => "1","type_arg" => 9,"nbr" => 3 ),

    // Dirham
    array(  "type" => "2","type_arg" => 1,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 2,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 3,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 4,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 5,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 6,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 7,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 8,"nbr" => 3 ),
    array(  "type" => "2","type_arg" => 9,"nbr" => 3 ),

    // Dinar
    array(  "type" => "3","type_arg" => 1,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 2,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 3,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 4,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 5,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 6,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 7,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 8,"nbr" => 3 ),
    array(  "type" => "3","type_arg" => 9,"nbr" => 3 ),

    // Ducat
    array(  "type" => "4","type_arg" => 1,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 2,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 3,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 4,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 5,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 6,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 7,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 8,"nbr" => 3 ),
    array(  "type" => "4","type_arg" => 9,"nbr" => 3 )

);


?>
