<?php
require_once(__DIR__ . "/funcs.php");
require_once(__DIR__ . "/config.php");

$response = file_get_contents('php://input');
$data = json_decode($response, true);
$dump = print_r($data, true);

$chatId = $data['message']['chat']['id'];
$chatType = $data['message']['chat']['type'];
$message = $data['message']['text'];
$messageId = $data['message']['message_id'];
$senderUserId = $data['message']['from']['id'];
if(isset($data['message']['reply_to_message'])){
  $replyToMessage = $data['message']['reply_to_message'];
  $repliedToMessageId = $replyToMessage['message_id'];
  $repliedToUserId = $replyToMessage['from']['id'];
  $repliedToName = $replyToMessage['from']['first_name'];
  if(isset($replyToMessage['from']['last_name'])){
    $repliedToName = $repliedToName . ' ' . $replyToMessage['from']['last_name'];
  }
  $repliedToUsername = NULL;
  if(isset($replyToMessage['from']['username'])){
    $repliedToUsername = $replyToMessage['from']['username'];
  }
}

if (substr($message, '0', '1') == '/') {
  $messageArr = explode(' ', $message);
  $command = $messageArr[0];
  if (isset($messageArr[1]) && $messageArr[1] == 'zencommands') {
    $command = '/zencommands';
  }
}
else {
  die();
}

$nodeText = 'We have <a href="https://zencash.com/securenodes">secure nodes</a> and will hopefully deploy the <b>super nodes</b> in July.
A <i>secure</i> node needs 42 ZEN in a transparent address, whereas the <i>super</i> node requires 500 ZEN.';

switch ($command) {
  case '/start':
    if ($chatType === 'private') {
      sendMessage($chatId, 'Hello!
    
I\'m the ZenCash Help Bot. I can provide quick information about topics when I see a command that I know.
To get a list of all commands I know, simply send /zencommands to me.

I am also open source, so if you like you can add your own commands by creating a pull request here: https://github.com/DatDraggy/zencash-help-bot', '', $messageId);
    }
    break;

  case '/zenprice':
    sendMessage($chatId, getCurrentPrice() . '

<code>Source: Bittrex</code>', '', $messageId);
    break;

  case '/nodes':
    sendMessage($chatId, $nodeText, '', $messageId);
    break;

  case '/securenode':
  case '/securenodes':
    sendMessage($chatId, '
For a secure node, you need 42 ZEN and a small VPS with a single core cpu, ~20GB space, 3-4GB RAM + some swap and a domain. 

More info can be found here: https://zencash.com/securenodes 
', '', $messageId);
    break;

  case '/securenodesreward':
    //ToDo: Add calculations
    sendMessage($chatId, 'Current earnings per day: ' . getCurrentReward() . '

You can see the current daily reward for a secure node here: https://securenodes.zensystem.io
', '', $messageId);
    break;

  case '/masternodes':
    sendMessage($chatId, '
We do not have masternodes. ' . $nodeText . '
', '', $messageId);
    break;

  case '/zencommands':
  case '/zenhelp':
  case '/zenhelp@ZenCashHelp_bot':
    if ($chatType === 'private') {
      sendMessage($chatId, '
Here is a small list of available commands. Click them to find out what they say.

/zenprice
/zengroups
/start
/nodes
/securenodes
/securenodesreward
/masternodes
/zencommands
/zenhelp
/ping
/zenadmins
/wallets
/freezen
/helpdesk
', '', $messageId);
    } else {
      $replyMarkup = array('inline_keyboard' => array(array(array("text" => "/zencommands", "url" => "https://telegram.me/zencashhelp_bot?start=zencommands"))));
      //ToDo: Check last use of command/create a timeout
      sendMessage($chatId, '
Click here to get a list of all commands:
', json_encode($replyMarkup), $messageId);
    }
    break;

  case '/ping':
    sendMessage($chatId, 'Pong.', '', $messageId);
    break;
  case '/zenadmins':
    if ($chatType !== 'private') {
      $adminText = 'Here is a list of all admins in this group:

';
      sendMessage($chatId, $adminText . getAdmins($chatId), '', $messageId);
    } else {
      sendMessage($chatId, 'Send this command in a group I\'m in. We are the only admins in this private chat. 😉', '', $messageId);
    }
    break;
  case '/wallets':
    $walletText = 'We currently have two types of wallet clients. There\'s the full wallet, called <a href="https://github.com/ZencashOfficial/zencash-swing-wallet-ui/releases">Swing</a>, and then there is also the light wallets, called <a href="https://github.com/ZencashOfficial/arizen/releases">Arizen</a> and <a href="https://play.google.com/store/apps/details?id=io.zensystem.zencashwallet">ZenCash Mobile</a>.

The full wallet is capable of using z-addresses, which are also known as private addresses. Swing needs to download the entire blockchain, but that will take a while and is going to use some space on your harddrive. It\'t availalbe for macOS, Windows 7+ 64bit and Linux.

The light wallets on the other hand don\'t need the full blockchain and can only <i>send</i> to z-addrs, but can send and receive on t-addresses. Arizen is available on macOS, Windows and Linux. The mobile wallet only on Android, but you can use Coinomi on iOS.

If you would rather use a web wallet, a paper wallet or want to find out more about the wallets, take a look here: https://zencash.com/wallets/
';
    sendMessage($chatId, $walletText, '', $messageId);
    break;
  case '/freezen':
    sendMessage($chatId, 'You can get small amounts of ZenCash from our free faucet, <a href="http://getzen.cash">getzen.cash</a>. 

You will have to register and can only receive free ZEN every 20 hours.', '', $messageId);
    break;
  case '/helpdesk':
  case '/zensupport':
    sendMessage($chatId, 'Our <a href="https://blog.zencash.com/zenhelp-first-cryptocurrency-help-desk/">ZenHelp</a> #helpdesk is available around the clock. If you need help with something, try asking there. 
    https://support.zencash.com', '', $messageId);
    break;
  case '/thanks':
    if ($chatType === 'private') {
      sendMessage($chatId, 'You can thank users by replying to their helping message with /thanks. 
Their thank-score will be raised which will hopefully encourage in more people helping.', '', $messageId);
    } else {
      if (isset($repliedToMessageId)) {
        if ($senderUserId !== $repliedToUserId) {
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


          //Select where replied to userid, get score for convenience
          try {
            $sql = "SELECT `score` FROM thanks WHERE user_id = '$repliedToUserId'";
            $stmt = $dbConnection->prepare("SELECT `user_id` FROM thanks WHERE user_id = :repliedToUserId");
            $stmt->bindParam(':repliedToUserId', $repliedToUserId);
            $stmt->execute();
            $row = $stmt->fetch();
          } catch (PDOException $e) {
            $to = $config['mail'];
            $subject = 'Database insert';
            $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
            $headers = 'From: ' . $config['mail'];
            mail($to, $subject, $txt, $headers);
          }
          if (!empty($row)) {
            $score = $row['score'];
            //Updating usernames and score+1
            try {
              $sql = "UPDATE `thanks` SET `name`='$repliedToName', `username`='$repliedToUsername', `score`=`score`+1 WHERE user_id = '$repliedToUserId'";
              $stmt = $dbConnection->prepare("UPDATE `thanks` SET `name`=:repliedToName, `username`=:repliedToUsername, `score`=`score`+1 WHERE user_id = :repliedToUserId");
              $stmt->bindParam(':repliedToName', $repliedToName);
              $stmt->bindParam(':repliedToUserame', $repliedToUsername);
              $stmt->bindParam(':repliedToUserId', $repliedToUserId);
              $stmt->execute();
            } catch (PDOException $e) {
              $to = $config['mail'];
              $subject = 'Database insert';
              $txt = __FILE__ . ' ' . $sql . ' Error: ' . $e;
              $headers = 'From: ' . $config['mail'];
              mail($to, $subject, $txt, $headers);
            }
          } else {
            //if not exist, create entry
            try {
              $sql = "INSERT INTO `thanks`(`user_id`, `name`, `username`, `score`) VALUES ('$repliedToUserId', '$repliedToName', '$repliedToUsername', '1')";
              $stmt = $dbConnection->prepare("INSERT INTO `thanks`(`user_id`, `name`, `username`, `score`) VALUES (:useriedToUserId, :repliedToName, :repliedToUsername, '1')");
              $stmt->bindParam(':repliedToUserId', $repliedToUserId);
              $stmt->bindParam(':repliedToName', $repliedToName);
              $stmt->bindParam(':repliedToUserame', $repliedToUsername);
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
          /* get replied to username and messageId
           * count + in database and update username + name
           * select count
           * sendReply($chatId, $messageId, 'Awesome! @\'s thank-score is now ' . $thankCount . ' thank-yous');
           */
        }
      }
    }
    break;
  case '/testdev':
    require_once('testdev.php');
    break;
  default:
    if ($chatType === 'private') {
      sendMessage($chatId, 'Unknown command! Use /zencommands if you need assistance or contact @DatDraggy.');
    }
}