{OVERALL_GAME_HEADER}


<div id="alhambra_wrapper">

<span id="scoring_round_alert">{SCORING_ROUND_AT_THE_END_OF_THIS_TURN} !</span>

    <div id="upper_part">
        <div id="left_part">
        
            <div id="board">

                <div id="buildingdeck" class="buildingsite_place"><div id="building_count">x32</div></div>            
            
                <div id="buildingsite_1" class="buildingsite_place"></div>
                <div id="buildingsite_2" class="buildingsite_place"></div>
                <div id="buildingsite_3" class="buildingsite_place"></div>
                <div id="buildingsite_4" class="buildingsite_place"></div>            


                <div id="deck" class="moneyplace"><div id="money_count">x32</div></div>            

                <div id="moneyplace_1" class="moneyplace"></div>            
                <div id="moneyplace_2" class="moneyplace"></div>            
                <div id="moneyplace_3" class="moneyplace"></div>            
                <div id="moneyplace_4" class="moneyplace"></div>            

                
            </div>
            <div id="player_aid">
            </div>
            <div id="player_stock" class="whiteblock">
                <h3>{MY_STOCK}</h3>
                    <div class="alhambra_stock" id="alhambra_stock_current_player">
                        <div id="stock_{CURRENT_PLAYER_ID}" class="stock_current_player" ></div>
                    </div>                
            </div>
        
        </div>

        <div id="right_part">
        
        
            <div id="money_wrap"  class="whiteblock" >
                <h3>{MY_MONEY}</h3>
                <div id="player_hand">
                </div>
                <div id="scoring_panel">
                    <div id="round_scoring_1" class="round_scoring">
                        <div class="scoring_items">
                            <div id="scoring_1_1_1" class="scoring_zone"></div>
                            <div id="scoring_1_2_1" class="scoring_zone"></div>
                            <div id="scoring_1_3_1" class="scoring_zone"></div>
                            <div id="scoring_1_4_1" class="scoring_zone"></div>
                            <div id="scoring_1_5_1" class="scoring_zone"></div>
                            <div id="scoring_1_6_1" class="scoring_zone"></div>
                        </div>
                    </div>
                    <div id="round_scoring_2" class="round_scoring">
                        <div class="scoring_items">
                            <div id="scoring_2_1_1" class="scoring_zone"></div>
                            <div id="scoring_2_1_2" class="scoring_zone"></div>
                            <div id="scoring_2_2_1" class="scoring_zone"></div>
                            <div id="scoring_2_2_2" class="scoring_zone"></div>
                            <div id="scoring_2_3_1" class="scoring_zone"></div>
                            <div id="scoring_2_3_2" class="scoring_zone"></div>
                            <div id="scoring_2_4_1" class="scoring_zone"></div>
                            <div id="scoring_2_4_2" class="scoring_zone"></div>
                            <div id="scoring_2_5_1" class="scoring_zone"></div>
                            <div id="scoring_2_5_2" class="scoring_zone"></div>
                            <div id="scoring_2_6_1" class="scoring_zone"></div>
                            <div id="scoring_2_6_2" class="scoring_zone"></div>
                        </div>
                    </div>
                    <div id="round_scoring_3" class="round_scoring">
                        <div class="scoring_items">
                            <div id="scoring_3_1_1" class="scoring_zone"></div>
                            <div id="scoring_3_1_2" class="scoring_zone"></div>
                            <div id="scoring_3_1_3" class="scoring_zone"></div>
                            <div id="scoring_3_2_1" class="scoring_zone"></div>
                            <div id="scoring_3_2_2" class="scoring_zone"></div>
                            <div id="scoring_3_2_3" class="scoring_zone"></div>
                            <div id="scoring_3_3_1" class="scoring_zone"></div>
                            <div id="scoring_3_3_2" class="scoring_zone"></div>
                            <div id="scoring_3_3_3" class="scoring_zone"></div>
                            <div id="scoring_3_4_1" class="scoring_zone"></div>
                            <div id="scoring_3_4_2" class="scoring_zone"></div>
                            <div id="scoring_3_4_3" class="scoring_zone"></div>
                            <div id="scoring_3_5_1" class="scoring_zone"></div>
                            <div id="scoring_3_5_2" class="scoring_zone"></div>
                            <div id="scoring_3_5_3" class="scoring_zone"></div>
                            <div id="scoring_3_6_1" class="scoring_zone"></div>
                            <div id="scoring_3_6_2" class="scoring_zone"></div>
                            <div id="scoring_3_6_3" class="scoring_zone"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="alhambra_wrap_{CURRENT_PLAYER_ID}" class="whiteblock alhambra_wrap_current">
                <h3>{MY_ALHAMBRA}</h3>
                <div id="alhambra_{CURRENT_PLAYER_ID}" class="alhambra"><div id="alhambra_{CURRENT_PLAYER_ID}_inner"></div></div>
            </div>
        
        </div>
    
    
    </div>

    <div id="other_alhambras">
    </div>


</div>









<br class="clear" />




<!-- BEGIN other_alhambra -->

<div id="alhambra_wrap_{PLAYER_ID}" class="whiteblock">
    <h3>{PLAYER_NAME}</h3>

    <div class="alhambra_stock">
        <div id="stock_{PLAYER_ID}"></div>
    </div>
    <br class="clear" />

    <div id="alhambra_{PLAYER_ID}" class="alhambra">
        <div id="alhambra_{PLAYER_ID}_inner" class="alhambra"></div>
    </div>
</div>
    
<!-- END other_alhambra -->



<script type="text/javascript">

// Templates
var jstpl_player_board = '\
        <br class="clear" />\
        <div class="alamb_stats">\
            <div class="alamb_stat stat_1"><span id="btnbr_1_${id}" class="stat_nbr">0</span><span class="building_type_square bts_1"></span></div>\
            <div class="alamb_stat stat_2"><span id="btnbr_2_${id}" class="stat_nbr">0</span><span class="building_type_square bts_2"></span></div>\
            <div class="alamb_stat stat_3"><span id="btnbr_3_${id}" class="stat_nbr">0</span><span class="building_type_square bts_3"></span></div>\
            <div class="alamb_stat stat_4"><span id="btnbr_4_${id}" class="stat_nbr">0</span><span class="building_type_square bts_4"></span></div>\
            <div class="alamb_stat stat_5"><span id="btnbr_5_${id}" class="stat_nbr">0</span><span class="building_type_square bts_5"></span></div>\
            <div class="alamb_stat stat_6"><span id="btnbr_6_${id}" class="stat_nbr">0</span><span class="building_type_square bts_6"></span></div>\
            <div class="wall_stat"><div class="wallicon"></div><span id="wallnbr_${id}" class="wall_nbr">0</span><div class="wallicon"></div></div>\
            <div class="card_nbr"><div class="card_nbr_icon"></div><span id="card_nbr_${id}" class="card_nbr_nbr">0</span></div>\
        </div>';
        
var jstpl_neutral_player_board = '\<div class="alh_playerboard neutral-playerboard player-board">\
    <div class="player_board_inner">\
        <div id="player_score_0"></div><div id="playername_${id}" class="player-name" style="color: #${color}">${name}</div>\
    </div>\
    <div id="player_board_0" class="player_board_content">\
    </div>\
</div>';        

var jstpl_building_tile = '<div id="building_tile_${id}" class="building_tile ${additional_style}" style="background-position: ${back_x}% ${back_y}%"></div>';


var jstpl_building_tile_surface = '<div class="building_surface" id="building_surface_${id}"></div><a id="rm_${id}" class="remove_building" href="#"><i class="fa fa-times-circle" aria-hidden="true"></i></a>';

var jstpl_scoringDlgBuilding = '<div class="scoringBuilding">\
        <div class="building_tile_scoring" style="background-position: ${back_x}px 0px"></div>\
        <div class="scoring_list">${scores}</div>\
    </div>';
    
var jstpl_scoringDlgPlayer = '<div class="scoring_player">\
                                <span style="color:#${color};${color_back}">${name}</span> x${nb} (${rank}): +${points}<img src="{THEMEURL}img/common/point.png"/>\
                              </div>';

var jstpl_scoringDlgPlayerWalll = '<div class="scoring_player">\
                                <span style="color:#${color};${color_back}">${name}</span>: +${walls}<img src="{THEMEURL}img/common/point.png"/>\
                              </div>';


</script>  

{OVERALL_GAME_FOOTER}
