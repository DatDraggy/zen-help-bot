<?php
//$config['url'] = 'https://api.telegram.org/bot' . $config['token'] . '/';
function sendMessage($chatId, $text, $replyMarkup = '', $replyTo = '') {
  global $config;
  $response = file_get_contents($config['url'] . "sendMessage?disable_web_page_preview=true&parse_mode=html&chat_id=$chatId&text=" . urlencode($text) . "&reply_to_message_id=$replyTo&reply_markup=$replyMarkup");
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
      $adminKey = array_search($admin, $admins, true);
      unset($admins[$adminKey]);
    }
  }

  foreach ($admins as $admin) {
    $is_bot = $admin['user']['is_bot'];
    $firstName = $admin['user']['first_name'];
    $lastName = '';
    if (isset($admin['user']['last_name'])) {
      $lastName = ' ' . $admin['user']['last_name'];
    }
    if (empty($is_bot)) {
      $result = $result . $firstName . $lastName . '
';
    }
  }
  return $result;
}

function countThanks($repliedToUserId, $repliedToName, $repliedToUsername) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);

  //Select where replied to userid, get score for convenience
  try {
    $sql = "SELECT `score` FROM thanks WHERE user_id = '$repliedToUserId'";
    $stmt = $dbConnection->prepare("SELECT `score` FROM thanks WHERE user_id = :repliedToUserId");
    $stmt->bindParam(':repliedToUserId', $repliedToUserId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    $to = $config['mail'];
    $subject = 'Database Select Score Count Score';
    $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
    $headers = 'From: ' . $config['mail'];
    mail($to, $subject, $txt, $headers);
  }
  if (!empty($row)) {
    $score = $row['score'] + 1;
    //Updating usernames and score+1
    try {
      $sql = "UPDATE `thanks` SET `score`=`score`+1 WHERE user_id = '$repliedToUserId'";
      $stmt = $dbConnection->prepare("UPDATE `thanks` SET `score`=`score`+1 WHERE user_id = :repliedToUserId");
      $stmt->bindParam(':repliedToName', $repliedToName);
      $stmt->bindParam(':repliedToUsername', $repliedToUsername);
      $stmt->bindParam(':repliedToUserId', $repliedToUserId);
      $stmt->execute();
    } catch (PDOException $e) {
      $to = $config['mail'];
      $subject = 'Database update thank score';
      $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
      $headers = 'From: ' . $config['mail'];
      mail($to, $subject, $txt, $headers);
    }
    try{
      $sql = "UPDATE `users` SET `name`='$repliedToName', `username`='$repliedToUsername' WHERE user_id = '$repliedToUserId'";$stmt = $dbConnection->prepare("UPDATE `users` SET `name`=:repliedToName, `username`=:repliedToUsername WHERE user_id = :repliedToUserId");
    $stmt->bindParam(':repliedToName', $repliedToName);
    $stmt->bindParam(':repliedToUsername', $repliedToUsername);
    $stmt->bindParam(':repliedToUserId', $repliedToUserId);
    $stmt->execute();
  } catch (PDOException $e) {
    $to = $config['mail'];
    $subject = 'Database update thank score';
    $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
    $headers = 'From: ' . $config['mail'];
    mail($to, $subject, $txt, $headers);
  }
  } else {
    $score = 1;
    //if not exist, create entry
    try {
      $sql = "INSERT INTO `thanks`(`user_id`, `score`) VALUES ('$repliedToUserId', '1')";
      $stmt = $dbConnection->prepare("INSERT INTO `thanks`(`user_id`, `score`) VALUES (:repliedToUserId, '1')");
      $stmt->bindParam(':repliedToUserId', $repliedToUserId);
      $stmt->execute();
    } catch (PDOException $e) {
      $to = $config['mail'];
      $subject = 'Database insert';
      $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
      $headers = 'From: ' . $config['mail'];
      mail($to, $subject, $txt, $headers);
    }try {
      $sql = "INSERT INTO `users`(`user_id`, `name`, `username`) VALUES ('$repliedToUserId', '$repliedToName', '$repliedToUsername')";
      $stmt = $dbConnection->prepare("INSERT INTO `users`(`user_id`, `name`, `username`) VALUES (:repliedToUserId, :repliedToName, :repliedToUsername)");
      $stmt->bindParam(':repliedToUserId', $repliedToUserId);
      $stmt->bindParam(':repliedToName', $repliedToName);
      $stmt->bindParam(':repliedToUsername', $repliedToUsername);
      $stmt->execute();
    } catch (PDOException $e) {
      $to = $config['mail'];
      $subject = 'Database insert';
      $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
      $headers = 'From: ' . $config['mail'];
      mail($to, $subject, $txt, $headers);
    }
    //sendMessage();
  }
  return $score;
}

function buildDatabaseConnection($config) {
  //Connect to DB only here to save response time on other commands
  try {
    $dbConnection = new PDO('mysql:dbname=' . $config['dbname'] . ';host=' . $config['dbserver'] . ';charset=utf8mb4', $config['dbuser'], $config['dbpassword']);
    $dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    $to = $config['mail'];
    $subject = 'Database Connect';
    $txt = 'Error: ' . $e->getMessage();
    $headers = 'From: ' . $config['mail'];
    mail($to, $subject, $txt, $headers);
  }
  return $dbConnection;
}

function getOwnThankScore($senderUserId) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);

  //Select where replied to userid, get score for convenience
  try {
    $sql = "SELECT `score` FROM thanks WHERE user_id = '$senderUserId'";
    $stmt = $dbConnection->prepare("SELECT `score` FROM thanks WHERE user_id = :senderUserId");
    $stmt->bindParam(':senderUserId', $senderUserId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    $to = $config['mail'];
    $subject = 'Database Select ownScore';
    $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
    $headers = 'From: ' . $config['mail'];
    mail($to, $subject, $txt, $headers);
  }
  $ownScore = 0;
  if (!empty($row)) {
    $ownScore = $row['score'];
  }
  return $ownScore;
}

function getScoreboard() {
  $scoreboard = '';
  global $config;
  $dbConnection = buildDatabaseConnection($config);
  try {
    $sql = 'SELECT `name`, `username`, `score` FROM `users` INNER JOIN `thanks` ON `thanks`.`user_id` = `users`.`user_id` ORDER BY score DESC LIMIT 3';
    foreach ($dbConnection->query($sql) as $row) {
      if (empty($row['username'])) {
        $scoreboard .= $row['name'] . ': ' . $row['score'];
      } else {
        $scoreboard .= '@' . $row['username'] . ': ' . $row['score'];
      }
    }
  } catch (PDOException $e) {
    $to = $config['mail'];
    $subject = 'Database Select ownScore';
    $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
    $headers = 'From: ' . $config['mail'];
    mail($to, $subject, $txt, $headers);
  }
  return $scoreboard;
}