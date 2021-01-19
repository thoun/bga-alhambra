{OVERALL_GAME_HEADER}
<div id="alhambra_wrapper">
  <div id="upper-part">
    <div id="left-part">
      <div id="board-wrapper" class="alhambra-block">
        <div id="board">
          <div id="token-crown" data-round="0"><div></div></div>
          <div id="building-deck" class="building-spot">
            <div id="building-count"></div>
          </div>
          <div id="building-spot-1" class="building-spot"></div>
          <div id="building-spot-2" class="building-spot"></div>
          <div id="building-spot-3" class="building-spot"></div>
          <div id="building-spot-4" class="building-spot"></div>


          <div id="money-deck" class="money-spot">
            <div id="money-count"></div>
          </div>
          <div id="money-spot-0" class="money-spot"></div>
          <div id="money-spot-1" class="money-spot"></div>
          <div id="money-spot-2" class="money-spot"></div>
          <div id="money-spot-3" class="money-spot"></div>
        </div>
      </div>

      <div id="player-aid-wrapper" class="alhambra-block">
        <div id="player-aid"></div>
      </div>

      <div id="player-stock" class="alhambra-block">
        <h3>{MY_STOCK}</h3>
        <div class="alhambra_stock" id="alhambra_stock_current_player">
            <div id="stock-{CURRENT_PLAYER_ID}" class="stock_current_player" ></div>
        </div>
      </div>
    </div>

    <!-- right part will be added by js if not spectator -->

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

  <div id="bottom-part"></div>
</div>




<script type="text/javascript">

/****************************
****** PLAYER PANNELS *******
****************************/
var jstpl_currentPlayerPanel = `
<div id="right-part">
  <div id="money-wrap" class="alhambra-block">
    <h3>{MY_MONEY}</h3>
    <div id="player-hand-wrapper">
      <div id="player-hand"></div>
    </div>
  </div>

  <div id="alhambra-wrapper-\${id}" class="alhambra-block alhambra-wrapper-current alhambra-wrapper">
      <div id="alhambra-\${id}" class="alhambra">
        <div id="alhambra-inner-\${id}"></div>
      </div>
  </div>
</div>
`;

var jstpl_playerPanel = `
<div id="alhambra-wrapper-\${id}" class="alhambra-block alhambra-wrapper">
  <h3 class="alhambra-block" style="color:#\${color}">\${name}</h3>

  <div class="alhambra-stock">
    <div id="stock-\${id}"></div>
  </div>

  <div id="alhambra-\${id}" class="alhambra">
      <div id="alhambra-inner-\${id}" class="alhambra"></div>
  </div>
</div>
`;


/******************************
***** MONEY AND BUILDINGS *****
******************************/

var jstpl_moneyCard = `
<div id='card-\${id}' class='money-card' data-type='\${type}' data-value='\${value}'>
  <div class='money-card-back'></div>
  <div class='money-card-front'></div>
</div>
`;

// building_tile is for wrapper...
var jstpl_building = `
<div id="building-tile-\${id}" class="building-tile building_tile" data-type="\${type}">
  <div class='building-tile-back'></div>
  <div class='building-tile-front'>
    <div class="wall-n" data-wall="\${wallN}"></div>
    <div class="wall-e" data-wall="\${wallE}"></div>
    <div class="wall-s" data-wall="\${wallS}"></div>
    <div class="wall-w" data-wall="\${wallW}"></div>

    <div class="building-cost">\${cost}</div>

    <div class="building-surface" id="building-surface-\${id}"></div>
    <a id="remove-building-\${id}" class="remove-building" href="#">
      <i class="fa fa-times-circle" aria-hidden="true"></i>
    </a>
  </div>
</div>
`;

// building_tile is for wrapper...
var jstpl_freePlace = '<div id="free-place-${x}-${y}" class="free-place building_tile"></div>';



/************************
***** PLAYER BOARDS *****
************************/
var jstpl_configPlayerBoard = `
<div id="player_config" class="player_board_content">
  <div id="player_config_row">
    <div id="show-scoresheet">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
        <g class="fa-group">
          <path class="fa-secondary" fill="currentColor" d="M544 464v32a16 16 0 0 1-16 16H112a16 16 0 0 1-16-16v-32a16 16 0 0 1 16-16h416a16 16 0 0 1 16 16z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M640 176a48 48 0 0 1-48 48 49 49 0 0 1-7.7-.8L512 416H128L55.7 223.2a49 49 0 0 1-7.7.8 48.36 48.36 0 1 1 43.7-28.2l72.3 43.4a32 32 0 0 0 44.2-11.6L289.7 85a48 48 0 1 1 60.6 0l81.5 142.6a32 32 0 0 0 44.2 11.6l72.4-43.4A47 47 0 0 1 544 176a48 48 0 0 1 96 0z"></path>
        </g>
      </svg>
    </div>

    <div id="show-settings">
      <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
        <g>
          <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
          <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
        </g>
      </svg>
    </div>
  </div>
  <div class='layoutControlsHidden' id="layout-controls-container">
  </div>
</div>
`;


var jstpl_neutralPlayerBoard = `
<div class="alh_playerboard neutral-playerboard player-board">
  <div class="player_board_inner">
      <div id="player_score_0"></div><div id="playername_\${id}" class="player-name" style="color: #\${color}">\${name}</div>
  </div>
  <div id="player_board_0" class="player_board_content"></div>
</div>
`;

var jstpl_playerStats = `
<div class="alhambra-stats">
    <div class="alhambra-stat stat-1"><div class="building-type-stat"></div><div id="stat-\${id}-1" class="stat-nbr"></div></div>
    <div class="alhambra-stat stat-2"><div class="building-type-stat"></div><div id="stat-\${id}-2" class="stat-nbr"></div></div>
    <div class="alhambra-stat stat-3"><div class="building-type-stat"></div><div id="stat-\${id}-3" class="stat-nbr"></div></div>
    <div class="alhambra-stat stat-4"><div class="building-type-stat"></div><div id="stat-\${id}-4" class="stat-nbr"></div></div>
    <div class="alhambra-stat stat-5"><div class="building-type-stat"></div><div id="stat-\${id}-5" class="stat-nbr"></div></div>
    <div class="alhambra-stat stat-6"><div class="building-type-stat"></div><div id="stat-\${id}-6" class="stat-nbr"></div></div>
    <div class="wall-stat">
      <div class="wallicon"></div>
      <span id="stat-\${id}-wall" class="wall-nbr"></span>
      <div class="wallicon"></div>
    </div>
    <div class="card-nbr">
      <div class="card-nbr-icon"></div>
      <span id="card-\${id}-nbr" class="card-nbr-nbr"></span>
    </div>
</div>
`;



/************************
******** SCORING ********
************************/
var jstpl_tmpScoring = '<div id="scoring_${type}_${pId}_${score}" class="scoring_nbr">+${score}</div>';

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
