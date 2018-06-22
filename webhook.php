<?php
require_once(__DIR__ . "/funcs.php");
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/infos.php");
//test
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
  $senderName .= ' ' . $data['message']['from']['last_name'];
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

I am also open source, so if you like you can add your own commands by creating a pull request here: https://github.com/DatDraggy/zencash-help-bot

If you would like to donate, this is my ZenCash address: zni7tRLevBnJxWMzkUoMVze1e6RCSPDdbfw');
    }
    break;

  case '/zenprice':
    sendMessage($chatId, getCurrentPrice() . '

<code>Source: Bittrex, Coinmarketcap</code>', '', $messageIdToReplyTo);
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
    sendMessage($chatId, getCurrentReward() . '

Keep in mind that these estimates are very rough and that the number of secure nodes can raise/fall at any time, therefor chaning the estimates.
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
/myaddress
/51
/roi
');
    }
    else {
      $replyMarkup = array(
        'inline_keyboard' => array(
          array(
            array(
              "text" => "/zencommands",
              "url"  => "https://telegram.me/zencashhelp_bot?start=zencommands"
            )
          )
        )
      );
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
    }
    else {
      sendMessage($chatId, 'Send this command in a group I\'m in. We are the only admins in this private chat. 😉');
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
    }
    else {
      if (isset($repliedToMessageId)) {
        if ($senderUserId !== $repliedToUserId && $repliedToUserId !== 555449685) {
          $newScore = countThanks($repliedToUserId, $repliedToName, $repliedToUsername);
          sendMessage($chatId, 'Awesome! ' . $repliedToName . '\'s thank-score is now ' . $newScore . '.');
          zlog('/thanks', 'Added thanks to user ' . substr($repliedToUserId, '0', strlen($repliedToUserId) - 3));
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
    if ($chatType === 'private') {
      if (empty($messageArr[1])) {
        $address = getUserAddress($senderUserId);
        $messageToSend = 'No Address supplied. Use <code>/myaddress t_addr</code> to set your address. It will be used for the monthly giveaway for the top 3 helpers.';
        if (!empty($address)) {
          $messageToSend .= '
          
Your current address is ' . $address;
        }
        sendMessage($chatId, $messageToSend);
      }
      else if (strlen($messageArr[1]) === 35) {
        addUserAddress($senderUserId, $messageArr[1], $senderName, $senderUsername);
        sendMessage($chatId, 'Your address has been set to ' . $messageArr[1]);
        zlog('/myaddress', 'Added address to user ' . substr($repliedToUserId, '0', strlen($repliedToUserId) - 3));
      }
      else {
        sendMessage($chatId, 'Your address is invalid. Please try again. Remember, only t-addresses are accepted.');
      }
    }
    break;
  case '/scoreboard':
    if ($chatType === 'private') {
      $scoreboard = getScoreboard();
      sendMessage($chatId, 'These are the top 3 people with the most thanks received:
' . $scoreboard);
    }
    break;
  case '/zengroups':
    sendMessage($chatId, 'Here is a list of all official chats: 

' . $infos['groups']);
    break;
  case '/51':
    sendMessage($chatId, 'ZenCash suffered a 51% attack on June 2nd. More info: https://blog.zencash.com/zen-is-antifragile-beyond-a-51-attack/', '', $messageIdToReplyTo);
    break;
  case '/community':
    break;
  case '/roi':
    $roiMessage = calculateRoi();
    sendMessage($chatId, $roiMessage, '', $messageIdToReplyTo);
    break;
  /*
   * TIPPING BOT
  */
  case '/mybalance':
    /*
     * SELECT address FROM users WHERE userId =
     * execute zen-cli, get balance addr
     * sendMessage
     */
    break;

  case '/tip':
    /*
     * SELECT address FROM tipping WHERE userId = fromUserId if empty tell user
     * get balance
     * amountToSend = toSend - 0.0001
     * newBalance = balance - toSend
     * if new balance < 0 sendMessage Error die
     * else if newBalance = 0 do only one send
     * else if newBalance > 0 do send change to same address
     * if second arr elem contains @ do SELECT address FROM users WHERE username = @ if empty generate
     * send
     * else if repliedToUserId isset do SELECT address FROM users WHERE userId = repliedToUserId if empty generate
     * send
     *
     * sendMessage Succ
     */
    break;

  case '/deposit':
    /*
     * if private SELECT address FROM users WHERE userId = fromUserId if empty generate sendMessage else sendMessage address
     * else tell to use private
     */
    if($chatType === 'private){
        $address = getDepositAddress($senderUserId);
        sendMessage($chatId, "
Here is your deposit address:
$address

Send any amount of ZEN to it. You'll be able to widthdraw it at any time.");
    }
    break;

  case '/withdraw':
    /*
     *
     */
    break;

  /*
   * TIPPING BOT
   */
  case '/id':
    if ($chatType === 'private') {
      sendMessage($chatId, $chatId);
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
