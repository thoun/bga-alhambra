<?php

/////////////////////////////////////////////////////////////////////
///// Game statistics description
/////

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "longest_wall_all" => array(   "id"=> 10,
                                "name" => totranslate("Longest wall length"), 
                                "type" => "int" ),
                                
        "turn_number" => array(   "id"=> 11,
                                "name" => totranslate("Number of turn"), 
                                "type" => "int" )
    
    ),
    
    // Statistics existing for each player
    "player" => array(
    
        "longest_wall" => array(   "id"=> 10,
                                "name" => totranslate("Longest wall length"), 
                                "type" => "int" ),
        "points_win_1" => array(   "id"=> 11,
                                "name" => totranslate("Points wins during the first scoring round"), 
                                "type" => "int" ),
        "points_win_2" => array(   "id"=> 12,
                                "name" => totranslate("Points wins during the second scoring round"), 
                                "type" => "int" ),
        "points_win_3" => array(   "id"=> 13,
                                "name" => totranslate("Points wins during the third scoring round"), 
                                "type" => "int" ),
        "transformation_nbr" => array(   "id"=> 14,
                                "name" => totranslate("Number of Alhambra transformation action"), 
                                "type" => "int" ),
        "money_taken" => array(   "id"=> 15,
                                "name" => totranslate("Value of money taken"), 
                                "type" => "int" ),
        "exact_amount" => array(   "id"=> 16,
                                "name" => totranslate("Number of exact amount replay"), 
                                "type" => "int" ),

    
    )

);

?>
