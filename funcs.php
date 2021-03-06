<?php
//$config['url'] = 'https://api.telegram.org/bot' . $config['token'] . '/';
function sendMessage($chatId, $text, $replyTo = '', $replyMarkup = '') {
  $data = array(
    'disable_web_page_preview' => true,
    'parse_mode' => 'html',
    'chat_id' => $chatId,
    'text' => $text,
    'reply_to_message_id' => $replyTo,
    'reply_markup' => $replyMarkup
  );
  return makeApiRequest('sendMessage', $data);
}

function makeApiRequest($method, $data) {
  global $config, $client;
  if (!($client instanceof \GuzzleHttp\Client)) {
    $client = new \GuzzleHttp\Client(['base_uri' => $config['url']]);
  }
  try {
    $response = $client->request('POST', $method, array('json' => $data));
  } catch (\GuzzleHttp\Exception\BadResponseException $e) {
    $body = $e->getResponse()->getBody();
    mail($config['mail'], 'Error', print_r($body->getContents(), true) . "\n" . print_r($data, true) . "\n" . __FILE__);
    return false;
  }
  return json_decode($response->getBody(), true)['result'];
}

function getCurrentSecureReward() {
  //https://docs.google.com/spreadsheets/d/18EpBevxlpQFAxYN0YY-UvSBUGp-qyzLniahz5nwLiz4/
  $zenMinedPerMonth = 216000;

  $json = file_get_contents('https://securenodes.zensystem.io/api/srvstats');
  $data = json_decode($json, true);
  $secNodesAmount = $data['global']['up'];
  $estEarnMonthly = number_format($zenMinedPerMonth * '0.1' / $secNodesAmount, 8);
  $estEarnDaily = number_format($estEarnMonthly / 30, 8);
  $estEarnYearly = number_format($estEarnMonthly * 12, 8);
  return "Current reward per day: $estEarnDaily
Reward per month: $estEarnMonthly
Reward per Year: $estEarnYearly";
}

function getCurrentSuperReward() {
  //https://docs.google.com/spreadsheets/d/18EpBevxlpQFAxYN0YY-UvSBUGp-qyzLniahz5nwLiz4/
  $zenMinedPerMonth = 216000;

  $json = file_get_contents('https://supernodes.zensystem.io/api/srvstats');
  $data = json_decode($json, true);
  $supNodesAmount = $data['global']['up'];
  $estEarnMonthly = number_format($zenMinedPerMonth * '0.1' / $supNodesAmount, 8);
  $estEarnDaily = number_format($estEarnMonthly / 30, 8);
  $estEarnYearly = number_format($estEarnMonthly * 12, 8);
  return "Current reward per day: $estEarnDaily
Reward per month: $estEarnMonthly
Reward per Year: $estEarnYearly";
}

function getCurrentPrice() {
  $coinmarketJson = file_get_contents('https://api.coinmarketcap.com/v1/ticker/zencash/');
  $bittrexJson = file_get_contents('https://bittrex.com/api/v1.1/public/getmarketsummary?market=btc-zen');
  $pricesCoinmarket = json_decode($coinmarketJson, true)[0];
  $pricesBittrex = json_decode($bittrexJson, true)['result'][0];
  //return print_r($pricesBittrex, true);
  return 'Last ZEN price: ' . number_format($pricesBittrex['Last'], 8) . '
24h High: ' . number_format($pricesBittrex['High'], 8) . '
24h Low: ' . number_format($pricesBittrex['Low'], 8) . '
Price in Dollars: $' . number_format($pricesCoinmarket['price_usd'], 2) . '
CMC Rank: ' . number_format($pricesCoinmarket['rank'], 0);
  //return 'Price in Dollars: $'.number_format($pricesCoinmarket['price_usd'], 2);
}

function getAdmins($chatId) {
  $data = array('chat_id' => $chatId);
  $admins = makeApiRequest('getChatAdministrators', $data);
  $result = '';
  foreach ($admins as $admin) {
    $is_bot = $admin['user']['is_bot'];
    $firstName = $admin['user']['first_name'];
    $name = $admin['user']['first_name'];
    if (isset($admin['user']['last_name'])) {
      $name .= ' ' . $admin['user']['last_name'];
    }
    $username = '';
    if (isset($admin['user']['username'])) {
      //Is there seriously nothing more elegant than this?
      $username = $admin['user']['username'];
    }
    if (!empty($username) && empty($is_bot)) {
      //Replace username with first & last in future version?
      $result = $result . '<a href="https://t.me/' . $username . '">' . $name . '</a>' . '
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
  //sendMessage(175933892, print_r($result, true));

  return $result;
}

function buildDatabaseConnection($config) {
  //Connect to DB only here to save response time on other commands
  try {
    $dbConnection = new PDO('mysql:dbname=' . $config['dbname'] . ';host=' . $config['dbserver'] . ';port=' . $config['dbport'] . ';charset=utf8mb4', $config['dbuser'], $config['dbpassword'], array(PDO::ATTR_TIMEOUT => 25));
    $dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    notifyOnException('Database Connection', $config, '', $e);
  }
  return $dbConnection;
}

function notifyOnException($subject, $config, $sql = '', $e = '') {
  global $chatId;
  sendMessage($chatId, 'Internal Error! The administrator has been notified.');
  sendMessage(175933892, 'Bruv, sometin in da database is ded, innit? Check it out G. ' . $e);
  $to = $config['mail'];
  $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
  $headers = 'From: ' . $config['mail'];
  mail($to, $subject, $txt, $headers);
  http_response_code(200);
  die();
}

function countThanks($repliedToUserId, $repliedToName, $repliedToUsername) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);

  //Select where replied to userid, get score for convenience
  try {
    $sql = "SELECT score FROM users WHERE user_id = '$repliedToUserId'";
    $stmt = $dbConnection->prepare("SELECT score FROM users WHERE user_id = :repliedToUserId");
    $stmt->bindParam(':repliedToUserId', $repliedToUserId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  if (!empty($row)) {
    $score = $row['score'] + 1;
    //Updating usernames and score+1
    try {
      $sql = "UPDATE users SET name='$repliedToName', username='$repliedToUsername', score=score+1 WHERE user_id = '$repliedToUserId'";
      $stmt = $dbConnection->prepare("UPDATE users SET name=:repliedToName, username=:repliedToUsername, score=score+1 WHERE user_id = :repliedToUserId");
      $stmt->bindParam(':repliedToUserId', $repliedToUserId);
      $stmt->bindParam(':repliedToName', $repliedToName);
      $stmt->bindParam(':repliedToUsername', $repliedToUsername);
      $stmt->execute();
    } catch (PDOException $e) {
      notifyOnException('Database Update', $config, $sql, $e);
    }
    zlog(__FUNCTION__, 'Updated user ' . substr($repliedToUserId, '0', strlen($repliedToUserId) - 3));
  } else {
    $score = 1;
    //if not exist, create entry
    try {
      $sql = "INSERT INTO users(user_id, name, username, score) VALUES ('$repliedToUserId', '$repliedToName', '$repliedToUsername', '1')";
      $stmt = $dbConnection->prepare("INSERT INTO users(user_id, name, username, score) VALUES (:repliedToUserId, :repliedToName, :repliedToUsername, '1')");
      $stmt->bindParam(':repliedToUserId', $repliedToUserId);
      $stmt->bindParam(':repliedToName', $repliedToName);
      $stmt->bindParam(':repliedToUsername', $repliedToUsername);
      $stmt->execute();
    } catch (PDOException $e) {
      notifyOnException('Database Insert', $config, $sql, $e);
    }
    zlog(__FUNCTION__, 'Inserted user ' . substr($repliedToUserId, '0', strlen($repliedToUserId) - 3));
  }
  return $score;
}


function getStakeNodeRatio() {
  $superJson = file_get_contents('https://supernodes.zensystem.io/api/srvstats');
  $coinmarketJson = file_get_contents('https://api.coinmarketcap.com/v1/ticker/zencash/');
  $secureJson = file_get_contents('https://securenodes.zensystem.io/api/srvstats');
  $totalSupply = json_decode($coinmarketJson, true)[0]['total_supply'];
  $supNodesAmount = json_decode($superJson, true)['global']['up'];
  $secNodesAmount = json_decode($secureJson, true)['global']['up'];

  return number_format(((($secNodesAmount * 42) + ($supNodesAmount * 500)) / $totalSupply) * 100, 2);
}

function checkLastExecute($timeouts, $command, $type, $id) {
  if ($type === 'private') {
    return $timeouts;
  }
  global $config;
  $now = time();
  $lastExecute = $timeouts[$id][$command];

  if ($now < $lastExecute + $config['commandInterval']) {
    die();
  }

  $timeouts[$id][$command] = $now;
  return $timeouts;
}

function getOwnThankScore($senderUserId) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);

  //Select where replied to userid, get score for convenience
  try {
    $sql = "SELECT score FROM users WHERE user_id = '$senderUserId'";
    $stmt = $dbConnection->prepare("SELECT score FROM users WHERE user_id = :senderUserId");
    $stmt->bindParam(':senderUserId', $senderUserId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
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
    $sql = 'SELECT name, username, score FROM users WHERE score <> 0 ORDER BY score DESC LIMIT 5';
    foreach ($dbConnection->query($sql) as $row) {
      if (empty($row['username'])) {
        $scoreboard .= '
' . $row['name'] . ': ' . $row['score'];
      } else {
        $scoreboard .= '
@' . $row['username'] . ': ' . $row['score'];
      }
    }
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  return $scoreboard;
}

function addUserAddress($userId, $address, $name, $username) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);
  try {
    $sql = "SELECT user_id FROM users WHERE user_id = '$userId'";
    $stmt = $dbConnection->prepare("SELECT user_id FROM users WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  if (!empty($row)) {
    try {
      $sql = "UPDATE users SET address = '$address' WHERE user_id = '$userId'";
      $stmt = $dbConnection->prepare("UPDATE users SET address = :address WHERE user_id = :userId");
      $stmt->bindParam(':address', $address);
      $stmt->bindParam(':userId', $userId);
      $stmt->execute();
    } catch (PDOException $e) {
      notifyOnException('Database Update', $config, $sql, $e);
    }
    zlog(__FUNCTION__, 'Updated user ' . substr($userId, '0', strlen($userId) - 3));
  } else {
    try {
      $sql = "INSERT INTO users(user_id, name, username, address) VALUES ('$userId', '$name', '$username', '$address')";
      $stmt = $dbConnection->prepare("INSERT INTO users(user_id, name, username, address) VALUES (:userId, :name, :username, :address)");
      $stmt->bindParam(':userId', $userId);
      $stmt->bindParam(':name', $name);
      $stmt->bindParam(':username', $username);
      $stmt->bindParam(':address', $address);
      $stmt->execute();
    } catch (PDOException $e) {
      notifyOnException('Database Insert', $config, $sql, $e);
    }
    zlog(__FUNCTION__, 'Inserted user ' . substr($userId, '0', strlen($userId) - 3));
  }
}

function getUserAddress($userId) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);
  try {
    $sql = "SELECT address FROM users WHERE user_id = '$userId'";
    $stmt = $dbConnection->prepare("SELECT address FROM users WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  $address = '';
  if (!empty($row)) {
    $address = $row['address'];
  }
  return $address;
}

function zlog($func, $data) {
  $putData = '[' . date("Y-m-d H:i:s") . '] ' . $func . ": $data\n";
  file_put_contents('log.txt', $putData, FILE_APPEND | LOCK_EX);
}

function calculateSecureRoi() {
  $amountNodes = json_decode(file_get_contents('https://securenodes.zensystem.io/api/srvstats'), true)['global']['up'];
  $coinmarketJson = file_get_contents('https://api.coinmarketcap.com/v1/ticker/zencash/');
  $pricesCoinmarket = json_decode($coinmarketJson, true)[0];
  $valueUsd = number_format($pricesCoinmarket['price_usd'], 2);
  $minedPerMonth = 216000;
  $vpsCost = 5.00;
  $monthlyRewardZen = $minedPerMonth * 0.1 / $amountNodes;
  $monthlyReward = $monthlyRewardZen * $valueUsd;
  $monthlyProfit = $monthlyReward - $vpsCost;
  $annualProfit = $monthlyProfit * 12;
  $annualProfitZen = $annualProfit / $valueUsd;
  $roi = number_format($annualProfitZen / 42 * 100, 2);
  $roiText = "Rough Secure Node ROI: $roi%

ZEN Value in Dollars: $$valueUsd
VPS Cost: $" . number_format($vpsCost, 2) . "
Amount of Nodes: $amountNodes
Keep in mind that this is only theoretically and the amount of nodes can raise/fall at any time.";
  return $roiText;
}

function calculateSuperRoi() {
  $amountNodes = json_decode(file_get_contents('https://supernodes.zensystem.io/api/srvstats'), true)['global']['up'];
  $coinmarketJson = file_get_contents('https://api.coinmarketcap.com/v1/ticker/zencash/');
  $pricesCoinmarket = json_decode($coinmarketJson, true)[0];
  $valueUsd = number_format($pricesCoinmarket['price_usd'], 2);
  $minedPerMonth = 216000;
  $vpsCost = 12.00;
  $monthlyRewardZen = $minedPerMonth * 0.1 / $amountNodes;
  $monthlyReward = $monthlyRewardZen * $valueUsd;
  $monthlyProfit = $monthlyReward - $vpsCost;
  $annualProfit = $monthlyProfit * 12;
  $annualProfitZen = $annualProfit / $valueUsd;
  $roi = number_format($annualProfitZen / 500 * 100, 2);
  $roiText = "Rough Super Node ROI: $roi%

ZEN Value in Dollars: $$valueUsd
VPS Cost: $" . number_format($vpsCost, 2) . "
Amount of Nodes: $amountNodes
Keep in mind that this is only theoretically and the amount of nodes can raise/fall at any time.";
  return $roiText;
}

function getDepositAddress($userId) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);
  try {
    $sql = "SELECT tipping FROM users WHERE user_id = '$userId'";
    $stmt = $dbConnection->prepare("SELECT tipping FROM users WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  if (!empty($row['tipping'])) {
    $tippingAddress = $row['tipping'];
  } else if (!empty($row)) {
    $tippingAddress = getNewAddress($config);
    try {
      $sql = "UPDATE users SET address='$tippingAddress' WHERE user_id = '$userId'";
      $stmt = $dbConnection->prepare("UPDATE users SET tipping=:tipping WHERE user_id = :userId");
      $stmt->bindParam(':tipping', $tippingAddress);
      $stmt->bindParam(':userId', $userId);
      $stmt->execute();
    } catch (PDOException $e) {
      notifyOnException('Database Update', $config, $sql, $e);
    }
  } else {
    $tippingAddress = getNewAddress($config);
    try {
      $sql = "INSERT INTO users(user_id, tipping) VALUES ('$userId', '$tippingAddress')";
      $stmt = $dbConnection->prepare("INSERT INTO users(user_id, tipping) VALUES (:userId, :tippingAddress)");
      $stmt->bindParam(':userId', $userId);
      $stmt->bindParam(':tippingAddress', $tippingAddress);
      $stmt->execute();
    } catch (PDOException $e) {
      notifyOnException('Database Insert', $config, $sql, $e);
    }
  }
  return $tippingAddress;
}

function getBalance($userId, $confirmations = 1) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);
  try {
    $sql = "SELECT tipping FROM users WHERE user_id = '$userId'";
    $stmt = $dbConnection->prepare("SELECT tipping FROM users WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  if (!empty($row['tipping'])) {
    $tippingAddress = $row['tipping'];
  } else {
    return '0';
  }
  //Use insight api maybe?
  return z_getBalance($config, $tippingAddress, $confirmations);
}

function sendTipToMessage($fromUserId, $toUserId, $amountToSend) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);
  try {
    $sql = "SELECT tipping FROM users WHERE user_id = '$fromUserId'";
    $stmt = $dbConnection->prepare("SELECT tipping FROM users WHERE user_id = :fromUserId");
    $stmt->bindParam(':fromUserId', $fromUserId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  if (!empty($row) && !empty($row['tipping'])) {
    $tippingFromAddress = $row['tipping'];
  } else {
    return 'no_balance';
  }
  try {
    $sql = "SELECT tipping FROM users WHERE user_id = '$toUserId'";
    $stmt = $dbConnection->prepare("SELECT tipping FROM users WHERE user_id = :toUserId");
    $stmt->bindParam(':toUserId', $toUserId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  if (!empty($row)) {
    if (!empty($row['tipping'])) {
      $tippingToAddress = $row['tipping'];
    } else {
      //Generate Address and update
      $tippingToAddress = getNewAddress($config);
      if ($tippingToAddress === FALSE) {
        notifyOnException('Generate new Address send tip', $config);
        return FALSE;
      }
      try {
        $sql = "UPDATE users SET tipping='$tippingToAddress' WHERE user_id = '$toUserId'";
        $stmt = $dbConnection->prepare("UPDATE users SET tipping=:tippingToAddress WHERE user_id = :toUserId");
        $stmt->bindParam(':tippingToAddress', $tippingToAddress);
        $stmt->bindParam(':toUserId', $toUserId);
        $stmt->execute();
        $row = $stmt->fetch();
      } catch (PDOException $e) {
        notifyOnException('Database Update', $config, $sql, $e);
      }
    }
  } else {
    //Generate address and insert
    $tippingToAddress = getNewAddress($config);
    if ($tippingToAddress === FALSE) {
      notifyOnException('Generate new Address send tip', $config);
      return FALSE;
    }
    try {
      $sql = "INSERT INTO users(user_id, tipping) VALUES ('$toUserId', '$tippingToAddress')";
      $stmt = $dbConnection->prepare("INSERT INTO users(user_id, tipping) VALUES (:toUserId, :tippingToAddress)");
      $stmt->bindParam(':toUserId', $toUserId);
      $stmt->bindParam(':tippingToAddress', $tippingToAddress);
      $stmt->execute();
      $row = $stmt->fetch();
    } catch (PDOException $e) {
      notifyOnException('Database Insert', $config, $sql, $e);
    }
  }

  $currentBalance = z_getBalance($config, $tippingFromAddress);
  if ($currentBalance === FALSE) {
    notifyOnException('Check balance on send', $config);
    return FALSE;
  }
  if ($currentBalance >= ($config['fee'] + $amountToSend)) {
    if (sendMany($config, $tippingFromAddress, $tippingToAddress, $amountToSend, $currentBalance) === FALSE) {
      return 'error';
    }
  } else {
    return 'no_balance';
  }

  return TRUE;
}

function withdraw($config, $userId, $amountToSend) {
  global $config;
  $dbConnection = buildDatabaseConnection($config);
  try {
    $sql = "SELECT tipping, address FROM users WHERE user_id = '$userId'";
    $stmt = $dbConnection->prepare("SELECT tipping, address FROM users WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $row = $stmt->fetch();
  } catch (PDOException $e) {
    notifyOnException('Database Select', $config, $sql, $e);
  }
  if (!empty($row) && !empty($row['tipping'])) {
    $withdrawFrom = $row['tipping'];
    $withdrawTo = $row['address'];
    if (empty($withdrawTo)) {
      return 'no_withdraw';
    }
  } else {
    return 'no_balance';
  }
  $currentBalance = z_getBalance($config, $withdrawFrom);
  if ($currentBalance === FALSE) {
    notifyOnException('Check balance on send', $config);
    return FALSE;
  }
  if ($currentBalance >= ($config['fee'] + $amountToSend)) {
    if (sendMany($config, $withdrawFrom, $withdrawTo, $amountToSend, $currentBalance) === FALSE) {
      notifyOnException('Send', $config);
      return FALSE;
    } else {
      return TRUE;
    }
  } else {
    return 'no_balance';
  }
}

function anonUserId($userId) {
  return substr($userId, '0', strlen($userId) - 3);
}

###############
#RPC FUNCTIONS#
###############
function doRpcCallOld($config, $json) {
  $user = $config['rpcuser'];
  $password = $config['rpcpass'];
  $port = $config['rpcport'];
  $address = $config['rpcaddress'];

  $opts = array(
    'http' => array(
      'method' => 'POST',
      'headers' => ['Content-Type' => 'text/plain'],
      'auth' => [
        $user,
        $password
      ],
    )
  );

  $opts['http']['json'] = $json;

  $context = stream_context_create($opts);
  $request = file_get_contents("http://$address:$port", false, $context);
  if ($request === FALSE) {
    notifyOnException('Error on RPC', $config, '', $request);
    return FALSE;
  } else {
    return $request;
  }
}

function doRpcCall($config, $json) {
  $user = $config['rpcuser'];
  $password = $config['rpcpass'];
  $port = $config['rpcport'];
  $address = $config['rpcaddress'];

  $ch = curl_init();
  $headers = array();
  $headers[] = "Content-Type: text/plain";
  curl_setopt($ch, CURLOPT_URL, "http://$address:$port/");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERPWD, "$user:$password");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  if (curl_errno($ch) || $result === FALSE || empty($result)) {
    notifyOnException('Error on RPC', $config, '', curl_error($ch));
    curl_close($ch);
    return FALSE;
  } else {
    curl_close($ch);
    return $result;
  }

}

function getNewAddress($config) {
  $command = 'getnewaddress';

  $json = "{'jsonrpc': '1.0', 'id': 'curl', 'method': 'getnewaddress', 'params': [] }";
  $json = '{"jsonrpc": "1.0", "id": "curl", "method": "$command", "params": [] }';
  $json = str_replace('$command', $command, $json);

  $response = doRpcCall($config, $json);
  if ($response === FALSE) {
    return FALSE;
  } else {
    $jsondec = json_decode($response, true);
    return $jsondec['result'];
  }
}

function sendMany($config, $fromAddr, $toAddr, $amount, $currentBalance) {
  $command = 'z_sendmany';
  $change = number_format($currentBalance - $amount - $config['fee'], 8);

  $json = "{'jsonrpc': '1.0', 'id': 'curl', 'method': '$command', 'params': ['$fromAddr', [{'address': '$toAddr', 'amount': $amount}, {'address': '$fromAddr', 'amount': $change}]]}";
  if ($change <= 0) {
    $json = '{"jsonrpc": "1.0", "id": "curl", "method": "$command", "params": ["$fromAddr", [{"address": "$toAddr", "amount": $amount}], 1, $fee]}';

  } else {
    $json = '{"jsonrpc": "1.0", "id": "curl", "method": "$command", "params": ["$fromAddr", [{"address": "$toAddr", "amount": $amount}, {"address": "$fromAddr", "amount": $change}], 1, $fee]}';
    $json = str_replace('$change', $change, $json);
  }
  $json = str_replace('$command', $command, $json);
  $json = str_replace('$fromAddr', $fromAddr, $json);
  $json = str_replace('$toAddr', $toAddr, $json);
  $json = str_replace('$amount', $amount, $json);
  $json = str_replace('$fee', $config['fee'], $json);
  //PDO bindParam like string building. Couldn't find a function for doing it so I just did it like this, looks much cleaner than weird ' . $var . ' stuff.

  $response = doRpcCall($config, $json);
  if ($response === FALSE) {
    return FALSE;
  } else {
    $jsondec = json_decode($response, true);
    return $jsondec['result'];
  }
}

function z_getBalance($config, $tipping, $confirmations = 1) {
  $command = 'z_getbalance';
  $json = "{'jsonrpc': '1.0', 'id': 'curl', 'method': '$command', 'params': ['$tipping', $confirmations] }";
  $json = '{"jsonrpc": "1.0", "id": "curl", "method": "$command", "params": ["$tipping", $confirmations] }';
  $json = str_replace('$command', $command, $json);
  $json = str_replace('$tipping', $tipping, $json);
  $json = str_replace('$confirmations', $confirmations, $json);

  $response = doRpcCall($config, $json);
  if ($response === FALSE) {
    return FALSE;
  } else {
    $jsondec = json_decode($response, true);
    return $jsondec['result'];
  }
}

function returnResponse() {
  ignore_user_abort(true);
  ob_start();
  header('Connection: close');
  header('Content-Length: ' . ob_get_length());
  header("Content-Encoding: none");
  header("Status: 200");
  ob_end_flush();
  ob_flush();
  flush();
}
