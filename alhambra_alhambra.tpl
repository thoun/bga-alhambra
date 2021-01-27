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
    \${scoringRoundSpeed}
    <div id="layout-control-animation-speed-container">
      <svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="turtle" class="svg-inline--fa fa-turtle fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M464 128c-8.84 0-16 7.16-16 16s7.16 16 16 16 16-7.16 16-16-7.16-16-16-16zm81.59-19.77C510.52 83.36 487.21 63.89 458.64 64c-70.67.28-74.64 70.17-74.64 73v71.19c-.02 6.89-2.07 13.4-4.99 19.59C306.47-5.44 87.67 22.02 33.15 241.28c-1.28 5.16-1.28 10.24-.52 15.11C14.53 257.47 0 272.21 0 290.59c0 14.91 9.5 28.11 23.66 32.81l47.69 15.91L36.31 400c-5.78 10.02-5.78 21.98 0 32s16.16 16 27.72 16h36.94c11.38 0 22-6.12 27.72-16l33.88-58.66C183.78 379.75 204.75 384 240 384s56.22-4.25 77.44-10.66l33.88 58.69c5.72 9.84 16.34 15.97 27.72 15.97h36.94c11.56 0 21.94-5.98 27.72-16 5.78-10.02 5.78-21.98 0-32l-38.47-66.64c17.81-9.58 32.88-22.28 44.91-37.91 12.75-16.58 21.47-35.19 26.03-55.45h27.19c40.06 0 72.66-32.59 72.66-72.66-.02-23.39-11.4-45.48-30.43-59.11zM351.8 249.01c.89 3.59-1.52 6.99-4.04 6.99H68.25c-2.53 0-4.93-3.42-4.04-7 50.42-202.79 236.99-203.48 287.59.01zM503.34 208h-54.75l-1.75 14c-2.53 20.03-9.97 38.17-22.09 53.94-19.88 25.87-43.07 33.45-65.25 42.25L415.97 416H379l-46.75-81.05C303.17 344.49 284.62 352 240 352c-45.86 0-64.64-8-92.25-17.05L100.97 416H64l54.66-94.63L32 288h303.06c29.22 0 51.64-15.08 64.38-31.59 10.78-14.05 16.53-30.7 16.56-48.19V137c0-26.99 22.44-40.55 42.26-41 19.93-.45 36.75 15.44 68.71 38.26 10.66 7.62 17.03 20 17.03 33.08 0 22.43-18.25 40.66-40.66 40.66z"></path></svg>

      <div id="layout-control-animation-speed"></div>

      <svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="rabbit-fast" class="svg-inline--fa fa-rabbit-fast fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M511.99 223.99c-8.84 0-16 7.16-16 16s7.16 16 16 16 16-7.16 16-16c0-8.83-7.16-16-16-16zm90.89-32.78c-.61-.43-58.52-35.99-58.52-35.99-2.54-1.57-9.73-5.07-18.35-7.52a261.57 261.57 0 0 0-4.89-22.02c-5.89-21.97-28.67-93.67-74.52-93.68h-.01c-37.96 0-44.2 41.84-44.49 43.33-32.09-17.15-55.46-13.15-69.7 1.09-8.71 8.71-20.55 28.35-4.36 63.28-31.09-16.38-61.55-27.7-88.06-27.7-45.73 0-86.28 18.33-117.89 52.43C108.58 151.29 90.85 144 71.97 144c-19.23 0-37.32 7.49-50.91 21.09-28.07 28.07-28.07 73.75 0 101.82C34.66 280.51 52.74 288 71.98 288c12.73 0 24.8-3.57 35.51-9.8 3.59 6.33 7.69 12.45 12.83 18.02l54.04 58.54-25.01 13.52a47.925 47.925 0 0 0-21.38 39.94v23.73c0 17.3 8.94 32.83 23.91 41.51 7.53 4.36 15.81 6.55 24.1 6.55 8.16 0 16.34-2.12 23.81-6.39l55.19-31.53 25.49 27.61a32.008 32.008 0 0 0 23.52 10.29H464c17.68 0 32-14.33 32-32 0-35.29-28.71-64-64-64h-48l70.4-32h96.96c48.88 0 88.65-39.77 88.65-88.65-.01-28.56-13.89-55.53-37.13-72.13zM96.26 246.93c-24.53 19.16-46.88 3.04-52.58-2.65-15.62-15.62-15.62-40.95 0-56.57 15.61-15.61 40.95-15.63 56.57 0 1.31 1.31 2.21 2.83 3.25 4.27-7.81 17.43-10.34 36.49-7.24 54.95zm87.65 198.9c-10.53 6.09-23.94-1.52-23.94-13.89v-23.73c0-5.36 2.66-10.34 5.84-12.55L196.74 379l35.96 38.96-48.79 27.87zm367.44-125.84H447.99l-64 26.67v-2.26c0-49.75-33.41-94.03-81.22-107.68l-42.38-12.11c-20.46-5.8-29.09 24.97-8.81 30.78l42.38 12.11c34.19 9.75 58.04 41.37 58.04 76.9v71.59h80c17.66 0 32 14.36 32 32H303.98L143.83 274.51c-22.36-24.22-22.66-61.37-.81-86.06 20.15-22.76 51.33-44.45 96.96-44.45 57.33 0 152.74 75.22 208.01 111.99 0-31.16-.53-30.77 3.54-43.01-15.31-3.53-37.75-17.86-59.17-39.28-30.93-30.92-47.64-64.35-37.33-74.65 10.74-10.74 45.14 7.8 74.66 37.33 3.25 3.25 6.25 6.54 9.18 9.81-11.63-44.51-8.08-82.19 7.72-82.19 13.94 0 32.92 30.05 43.61 69.97 4.1 15.28 6.36 29.86 6.98 42.49 14.17-1.01 24.77 3.23 30.44 6.03l56.65 34.75a56.632 56.632 0 0 1 23.72 46.1c.01 31.29-25.36 56.65-56.64 56.65z"></path></svg>
    </div>
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
