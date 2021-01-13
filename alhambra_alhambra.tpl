{OVERALL_GAME_HEADER}
<div id="alhambra_wrapper">

<span id="scoring_round_alert">{SCORING_ROUND_AT_THE_END_OF_THIS_TURN} !</span>

    <div id="upper-part">
        <div id="left-part">

            <div id="board">
                <div id="building-deck" class="building-spot">
                  <div id="building-count"></div>
                </div>
                <div id="building-spot-0" class="building-spot"></div>
                <div id="building-spot-1" class="building-spot"></div>
                <div id="building-spot-2" class="building-spot"></div>
                <div id="building-spot-3" class="building-spot"></div>


                <div id="money-deck" class="money-spot">
                  <div id="money-count"></div>
                </div>
                <div id="money-spot-0" class="money-spot"></div>
                <div id="money-spot-1" class="money-spot"></div>
                <div id="money-spot-2" class="money-spot"></div>
                <div id="money-spot-3" class="money-spot"></div>
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

var jstpl_currentPlayerPanel = `
<div id="right-part">
  <div id="money-wrap">
    <h3>{MY_MONEY}</h3>
    <div id="player-hand-wrapper">
      <div id="player-hand"></div>
    </div>
  </div>

  <div id="alhambra_wrap_{CURRENT_PLAYER_ID}" class="whiteblock alhambra_wrap_current">
      <h3>{MY_ALHAMBRA}</h3>
      <div id="alhambra_{CURRENT_PLAYER_ID}" class="alhambra"><div id="alhambra_{CURRENT_PLAYER_ID}_inner"></div></div>
  </div>
</div>
`;


var jstpl_moneyCard = `
<div id='card-\${id}' class='money-card' data-type='\${type}' data-value='\${value}'>
  <div class='money-card-back'></div>
  <div class='money-card-front'></div>
</div>
`;


var jstpl_building = `
<div id="building-tile-\${id}" class="building-tile" data-type="\${type}">
  <div class="wall-n" data-wall="\${wallN}"></div>
  <div class="wall-e" data-wall="\${wallE}"></div>
  <div class="wall-s" data-wall="\${wallS}"></div>
  <div class="wall-w" data-wall="\${wallW}"></div>

  <div class="building-surface" id="building-surface-\${id}"></div>
  <a id="remove-building-\${id}" class="remove-building" href="#">
    <i class="fa fa-times-circle" aria-hidden="true"></i>
  </a>
</div>
`;

var jstpl_building_tile = '<div id="building_tile_${id}" class="building_tile ${additional_style}" style="background-position: ${back_x}% ${back_y}%"></div>';
var jstpl_building_tile_surface = '<div class="building_surface" id="building_surface_${id}"></div><a id="rm_${id}" class="remove_building" href="#"><i class="fa fa-times-circle" aria-hidden="true"></i></a>';


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
