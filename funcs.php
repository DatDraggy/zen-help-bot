<?php
//$config['url'] = 'https://api.telegram.org/bot' . $config['token'] . '/';
function sendMessage($chatId, $text, $replyMarkup = '') {
  global $config;
  $response = file_get_contents($config['url'] . 'sendMessage?disable_web_page_preview=true&parse_mode=html&chat_id=' . $chatId . '&text=' . urlencode($text) . '&reply_markup=' . $replyMarkup);
  //Might use http_build_query in the future
}

function getCurrentReward() {
  $json = file_get_contents('https://securenodes.eu.zensystem.io/api/grid/nodes?_search=false&nd=1527582142968&rows=30&page=1&sidx=fqdn&sord=asc');
  $data = json_decode($json, true);
  return $data['userdata']['estearn'];
}

function getCurrentPrice() {
  $bittrexJson = file_get_contents('https://bittrex.com/Api/v2.0/pub/market/GetMarketSummary?marketName=BTC-ZEN');
  $prices = json_decode($bittrexJson, true);
  return 'Last ZenCash price: ' . number_format($prices['result']['Last'], 8) . '
24h High: ' . number_format($prices['result']['High'], 8) . '
24h Low: ' . number_format($prices['result']['Low'], 8);
}

function getAdmins($chatId) {
  global $config;
  $response = file_get_contents($config['url'] . 'getChatAdministrators?chat_id=' . $chatId);
  //Do things
  $result = '';
  $admins = json_decode($response, true)['result'];
  foreach ($admins as $admin) {
    $is_bot = $admin['user']['is_bot'];
    $firstName = $admin['user']['first_name'];
    $username = '';
    if (isset($admin['user']['username'])) {
      //Is there seriously nothing more elegant than this?
      $username = $admin['user']['username'];
    }
    if (!empty($username) && empty($is_bot)) {
      //Replace username with first & last in future version?
      $result = $result . '<a href="https://t.me/' . $username . '">@' . $username . '</a>' . '
';
      unset($admins[$admin]);
    }
  }

  foreach ($admins as $admin) {
    $is_bot = $admin['user']['is_bot'];
    $firstName = $admin['user']['first_name'];
    $username = '';
    //you kno, just to be sure >.>
    if (isset($admin['user']['username'])) {
      $username = $admin['user']['username'];
    }
    if (empty($username) && empty($is_bot)) {
      $result = $result . $firstName . '
';
    }
  }
  return $result;
}
