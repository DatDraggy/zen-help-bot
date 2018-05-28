<?php
function sendMessage($chatId, $text) {
  global $config;
  $url = 'https://api.telegram.org/bot' . $config['token'] . '/';
  $response = file_get_contents($url . 'sendMessage?disable_web_page_preview=true&parse_mode=html&chat_id=' . $chatId . '&text=' . urlencode($text));
}