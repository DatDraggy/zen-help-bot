<?php
require_once(__DIR__ . "/funcs.php");
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/infos.php");

$response = file_get_contents('php://input');
$data = json_decode($response, true);
$dump = print_r($data, true);

$chatId = $data['message']['chat']['id'];
$chatType = $data['message']['chat']['type'];
$message = $data['message']['text'];
$messageId = $data['message']['message_id'];
$messageIdToReplyTo = $messageId;
$senderUserId = $data['message']['from']['id'];
$senderName = $data['message']['from']['first_name'];
if (isset($replyToMessage['from']['last_name'])) {
  $senderName = $senderName . ' ' . $data['message']['from']['last_name'];
}
$senderUsername = NULL;
if (isset($data['message']['from']['username'])) {
  $senderUsername = $data['message']['from']['username'];
}
if (isset($data['message']['reply_to_message'])) {
  $replyToMessage = $data['message']['reply_to_message'];
  $repliedToMessageId = $replyToMessage['message_id'];
  $messageIdToReplyTo = $repliedToMessageId;
  $repliedToUserId = $replyToMessage['from']['id'];
  $repliedToName = $replyToMessage['from']['first_name'];
  if (isset($replyToMessage['from']['last_name'])) {
    $repliedToName = $repliedToName . ' ' . $replyToMessage['from']['last_name'];
  }
  $repliedToUsername = NULL;
  if (isset($replyToMessage['from']['username'])) {
    $repliedToUsername = $replyToMessage['from']['username'];
  }
}

if (substr($message, '0', '1') == '/') {
  $messageArr = explode(' ', $message);
  $command = $messageArr[0];
  if (isset($messageArr[1]) && $messageArr[1] == 'zencommands') {
    $command = '/zencommands';
  }
} else {
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

I am also open source, so if you like you can add your own commands by creating a pull request here: https://github.com/DatDraggy/zencash-help-bot');
    }
    break;

  case '/zenprice':
    sendMessage($chatId, getCurrentPrice() . '

<code>Source: Bittrex</code>', '', $messageIdToReplyTo);
    break;

  case '/nodes':
    sendMessage($chatId, $nodeText, '', $messageIdToReplyTo);
    break;

  case '/securenode':
  case '/securenodes':
    sendMessage($chatId, '
For a secure node, you need 42 ZEN and a small VPS with a single core cpu, ~20GB space, 3-4GB RAM + some swap and a domain. 

More info can be found here: https://zencash.com/securenodes 
', '', $messageIdToReplyTo);
    break;

  case '/securenodesreward':
    //ToDo: Add calculations
    sendMessage($chatId, 'Current earnings per day: ' . getCurrentReward() . '

You can see the current daily reward for a secure node here: https://securenodes.zensystem.io
', '', $messageIdToReplyTo);
    break;

  case '/masternodes':
    sendMessage($chatId, '
We do not have masternodes. ' . $nodeText . '
', '', $messageIdToReplyTo);
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
/thanks
/scoreboard
/mythanks
');
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
      sendMessage($chatId, $adminText . getAdmins($chatId), '', $messageIdToReplyTo);
    } else {
      sendMessage($chatId, 'Send this command in a group I\'m in. We are the only admins in this private chat. 😉', '', $messageIdToReplyTo);
    }
    break;
  case '/wallets':
    $walletText = 'We currently have two types of wallet clients. There\'s the full wallet, called <a href="https://github.com/ZencashOfficial/zencash-swing-wallet-ui/releases">Swing</a>, and then there is also the light wallets, called <a href="https://github.com/ZencashOfficial/arizen/releases">Arizen</a> and <a href="https://play.google.com/store/apps/details?id=io.zensystem.zencashwallet">ZenCash Mobile</a>.

The full wallet is capable of using z-addresses, which are also known as private addresses. Swing needs to download the entire blockchain, but that will take a while and is going to use some space on your harddrive. It\'t availalbe for macOS, Windows 7+ 64bit and Linux.

The light wallets on the other hand don\'t need the full blockchain and can only <i>send</i> to z-addrs, but can send and receive on t-addresses. Arizen is available on macOS, Windows and Linux. The mobile wallet only on Android, but you can use Coinomi on iOS.

If you would rather use a web wallet, a paper wallet or want to find out more about the wallets, take a look here: https://zencash.com/wallets/
';
    sendMessage($chatId, $walletText, '', $messageIdToReplyTo);
    break;
  case '/freezen':
    sendMessage($chatId, 'You can get small amounts of ZenCash from our free faucet, <a href="http://getzen.cash">getzen.cash</a>. 

You will have to register and can only receive free ZEN every 20 hours.', '', $messageIdToReplyTo);
    break;
  case '/helpdesk':
  case '/zensupport':
    sendMessage($chatId, 'Our <a href="https://blog.zencash.com/zenhelp-first-cryptocurrency-help-desk/">ZenHelp</a> #helpdesk is available around the clock. If you need help with something, try asking there. 
    https://support.zencash.com', '', $messageIdToReplyTo);
    break;
  case '/thanks':
    if ($chatType === 'private') {
      sendMessage($chatId, 'You can thank users by replying to their helping message with /thanks. 
Their thank-score will be raised which will hopefully encourage in more people helping.');
    } else {
      if (isset($repliedToMessageId)) {
        if ($senderUserId !== $repliedToUserId) {
          $newScore = countThanks($repliedToUserId, $repliedToName, $repliedToUsername);
          sendMessage($chatId, 'Awesome! ' . $repliedToName . '\'s thank-score is now ' . $newScore . '.');
        }
      }
    }
    break;
  case '/mythanks':
    if ($chatType === 'private') {
      $ownScore = getOwnThankScore($senderUserId);
      sendMessage($chatId, 'Your current thank-score is ' . $ownScore . '.');
      //ToDo: Add Scoreboard Position
    }
    break;
  case '/myaddress':
    if ($chatType === 'private'){
      if(empty($messageArr[1])){
        sendMessage($chatId, 'No Address supplied. Use <code>/myaddress t_addr</code>');
      }
      else if(strlen($messageArr[1]) === 35){
        addUserAddress($senderUserId, $messageArr[1], $senderName, $senderUsername);
        sendMessage($chatId, 'Your address has been set to '.$messageArr[1]);
      }
      else{
        sendMessage($chatId, 'Your address is invalid. Please try again. Remember, only t-addresses are accepted.');
      }

    }
    break;
  case '/scoreboard':
    if($chatType === 'private') {
      $scoreboard = getScoreboard();
      sendMessage($chatId, 'These are the top 3 people with the most thanks received: 
      
' . $scoreboard);
    }
    break;
  case '/zengroups':
    sendMessage($chatId, 'Here is a list of all official chats: 

' . $infos['groups']);
    break;
  case '/community':
    break;
  case '/testdev':
    require_once('testdev.php');
    break;
  default:
    if ($chatType === 'private') {
      sendMessage($chatId, 'Unknown command! Use /zencommands if you need assistance or contact @DatDraggy.', '', $messageId);
    }
}
