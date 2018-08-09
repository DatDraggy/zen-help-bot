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
$fee = $config['fee'];
if ($chatType !== 'private') {
  $messageIdToReplyTo = $messageId;
}
else {
  $messageIdToReplyTo = '';
}
$ownId = explode(':', $config['token'])[0];
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
  if ($chatType !== 'private') {
    $messageIdToReplyTo = $repliedToMessageId;
  }
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
}
else {
  die();
}

$nodeText = 'We have <a href="https://zencash.com/securenodes">secure nodes</a> and also <a href="https://zencash.com/supernodes">super nodes</a>.
A <i>secure</i> node needs 42 ZEN in a transparent address, whereas the <i>super</i> node requires 500 ZEN.';
$nodeText = 'ZEN has <a href="https://zencash.com/securenodes">secure nodes</a> and <a href="https://zencash.com/supernodes">super nodes</a>.
A secure node needs 42 ZEN in a transparent address, whereas the <i>super</i> node requires 500 ZEN.';

switch ($command) {
  case '/start':
    if ($chatType === 'private') {
      sendMessage($chatId, 'Hello!
    
I\'m the ZEN Bot. I can provide quick information about topics when I see a command that I know.
To get a list of all of the commands I know, simply send “/zencommands” to me.
You can also use me to tip or thank people. For more info about that send “/tipbot” or “/thanks”

I am open source, so if you’d like you can add your own commands by creating a pull request here: https://github.com/DatDraggy/zencash-help-bot

If you would like to buy @DatDraggy a beer or two, this is his donation address: zni7tRLevBnJxWMzkUoMVze1e6RCSPDdbfw');
    }
    break;

  case '/zenprice':
    sendMessage($chatId, getCurrentPrice() . '

<code>Source: Bittrex, Coinmarketcap</code>', $messageIdToReplyTo);
    break;

  case '/nodes':
    sendMessage($chatId, $nodeText, $messageIdToReplyTo);
    break;

  case '/securenode':
  case '/securenodes':
    sendMessage($chatId, '
For a secure node, you need 42 ZEN and a small VPS with a single core cpu, ~20GB space, 3-4GB RAM + some swap and a domain. 

More info can be found here: https://zencash.com/securenodes 
', $messageIdToReplyTo);
    break;

  case '/securenodesreward':
    sendMessage($chatId, getCurrentSecureReward() . '

Keep in mind that these estimates are very rough and that the number of secure nodes can raise/fall at any time, therefore changing the estimates.
You can see the current daily reward for a secure node here: https://securenodes.zensystem.io

', $messageIdToReplyTo);
    break;

  case '/supernode':
  case '/supernodes':
    sendMessage($chatId, '
A super node requires  500 ZEN, a bigger VPS with a quad core cpu, +100GB space, ~8GB RAM and a domain. 

More info can be found here: https://zencash.com/supernodes
', $messageIdToReplyTo);
    break;

  case '/supernodesreward':
    sendMessage($chatId, getCurrentSuperReward() . '

Keep in mind that these estimates are very rough and that the number of secure nodes can raise/fall at any time, therefore changing the estimates. 

You can see the current daily reward for a secure node here: https://supernodes.zensystem.io
', $messageIdToReplyTo);
    break;

  case '/masternodes':
    sendMessage($chatId, '
We do not have masternodes. ' . $nodeText . '
', $messageIdToReplyTo);
    break;

  case '/zencommands':
  case '/zenhelp':
  case '/zenhelp@' . $config['botName']:
    if ($chatType === 'private') {
      sendMessage($chatId, '
Here is a list of available commands. Click them to find out what they do.

Knowledge Commands (click to get info):
/zenprice
/zengroups
/start
/nodes
/securenodes
/securenodesreward
/supernodes
/supernodesreward
/masternodes
/zencommands
/zenhelp
/ping
/zenadmins
/wallets
/freezen
/helpdesk
/51
/securenoderoi
/supernoderoi
/deposit

Reputationsystem:
/thanks
/scoreboard
/mythanks

Tippingbot:
How to use: /tipbot
/withdraw
/tip
/myaddress
/mybalance


<code>Text</code> - Indicates a command name
<b>Text</b> - Required parameter
<i>Text</i> - Optional parameter
');
    }
    else {
      $replyMarkup = array(
        'inline_keyboard' => array(
          array(
            array(
              "text" => "/zencommands",
              "url"  => "https://telegram.me/" . $config['botName'] . "?start=zencommands"
            )
          )
        )
      );
      //ToDo: Check last use of command/create a timeout
      sendMessage($chatId, '
Click here to get a list of all commands:
', $messageId, json_encode($replyMarkup));
    }
    break;

  case '/ping':
    sendMessage($chatId, 'Pong.', $messageId);
    break;
  case '/zenadmins':
    if ($chatType !== 'private') {
      $adminText = 'Here is a list of all admins in this group:

';
      sendMessage($chatId, $adminText . getAdmins($chatId), $messageIdToReplyTo);
    }
    else {
      sendMessage($chatId, 'Send this command in a group I\'m in. We are the only admins in this private chat. 😉');
    }
    break;
  case '/wallets':
    $walletText = '
ZEN has two types of wallets: full and light. The Swing wallet is a full wallet, while our Arizen and ZenCash mobile wallets are light wallets.

The full wallet is capable of using z-addresses, which are also known as private addresses. The Swing Wallet needs to download the entire blockchain, but that will take a while and is going to use some space on your hard drive. It\'s available for macOS, Windows 7+ 64bit, and Linux.

The light wallets, on the other hand, don’t need the full blockchain but can only use and send to t-addresses. They can receive from z-addresses and t-addresses. Arizen is available on macOS, Windows, and Linux. The mobile wallet is only available on Android, but you can use Coinomi on iOS.
It’s also possible to connect Arizen with a machine running zend (Swing without GUI) to use z-addresses in Arizen.

If you would rather use a web wallet, a paper wallet or want to find out more about the wallets, take a look here: https://zencash.com/wallets/
';
    sendMessage($chatId, $walletText, $messageIdToReplyTo);
    break;
  case '/freezen':
    sendMessage($chatId, 'You can get small amounts of ZenCash from our free faucet, https://getzen.cash . 

Registration is required. Once registered, you will be able to receive free ZEN every 24 hours.', $messageIdToReplyTo);
    break;
  case '/helpdesk':
  case '/zensupport':
    sendMessage($chatId,'Our <a href="https://support.zencash.com">ZenHelp</a> helpdesk is available around the clock. If you need help with something, try asking here: https://support.zencash.com
', $messageIdToReplyTo);
    break;
  case '/thanks':
    if ($chatType === 'private') {
      sendMessage($chatId, 'You can thank users by replying to their helping message with “/thanks”.
Their thank-score will increase which will hopefully encourage more people to help.');
    }
    else {
      if (isset($repliedToMessageId)) {
        if ($senderUserId !== $repliedToUserId && $repliedToUserId !== $ownId) {
          $newScore = countThanks($repliedToUserId, $repliedToName, $repliedToUsername);
          sendMessage($chatId, 'Awesome! ' . $repliedToName . '\'s thank-score is now ' . $newScore . '.', $messageIdToReplyTo);
          zlog('/thanks', 'Added thanks to user ' . anonUserId($repliedToUserId));
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
        $messageToSend = 'No Address supplied. Use “/myaddress t_addr” to set up your address. It will be used for the monthly giveaway for the top 3 helpers and withdrawing your tips.';
        if (!empty($address)) {
          $messageToSend .= '
          
Your current address is ' . $address;
        }
        sendMessage($chatId, $messageToSend);
      }
      else if (strlen($messageArr[1]) === 35) {
        addUserAddress($senderUserId, $messageArr[1], $senderName, $senderUsername);
        sendMessage($chatId, 'Your address has been set to ' . $messageArr[1]);
        zlog('/myaddress', 'Added address to user ' . anonUserId($senderUserId));
      }
      else {
        sendMessage($chatId, 'Your address is invalid. Please try again. Remember, only t-addresses are accepted.');
      }
    }
    break;
  case '/scoreboard':
    if ($chatType === 'private') {
      $scoreboard = getScoreboard();
      sendMessage($chatId, 'These are the top 5 people with the most thanks received:
' . $scoreboard);
    }
    break;
  case '/zengroups':
    sendMessage($chatId, 'Here is a list of all official chats: 

' . $infos['groups'], $messageIdToReplyTo);
    break;
  case '/51':
    sendMessage($chatId, 'ZenCash was hit by a double spend attack on June 2nd where criminals stole 23k ZEN from an exchange. A fix against double spend attacks is already in development. More info: https://blog.zencash.com/zen-is-antifragile-beyond-a-51-attack/ and https://blog.zencash.com/zencash-statement-on-double-spend-attack/', $messageIdToReplyTo);
    break;
  case '/community':
    break;
  case '/securenoderoi':
    $roiMessage = calculateSecureRoi();
    sendMessage($chatId, $roiMessage, $messageIdToReplyTo);
    break;
  case '/supernoderoi':
    $roiMessage = calculateSuperRoi();
    sendMessage($chatId, $roiMessage, $messageIdToReplyTo);
    break;
  /*
   * TIPPING BOT
  */
  case '/mybalance':
    if ($chatType === 'private') {
      $balance = number_format(getBalance($senderUserId), 8);
      if ($balance === FALSE) {
      }
      else {
        sendMessage($chatId, "Your current balance is: $balance
(If your address sent or received ZEN a short time ago this will show as 0.00000000)");
      }
    }
    break;

  case '/tip':
    if ($chatType === 'private') {
      sendMessage($chatId, "Sends a tip to the user you replied to. Without a reply attached to your message, this command won't do anything.
After tipping someone, you will have to wait for the transaction to be confirmed.

Usage: <code>/tip</code> <b>amount</b>

/tip 0.1");
      die();
    }
    if (!empty($messageArr[1]) && isset($repliedToUserId)) {
      if ($senderUserId !== $repliedToUserId && $repliedToUserId !== $config['botId']) {
        $tip = $messageArr[1];
        if (isset($repliedToMessageId)) {
          if (is_numeric($tip) && $tip > 0) {
            // /tip 0.1
            if ($tip <= 1) {
              $tipResult = sendTipToMessage($senderUserId, $repliedToUserId, $messageArr[1]);
              zlog('/tip', 'User ' . anonUserId($senderUserId) . ' sent tip ' . $tip . ' to ' . anonUserId($repliedToUserId));
              if ($tipResult === FALSE) {
              }
              else if ($tipResult === 'no_balance') {
                //sendMessage($chatId, "You either haven't created a deposit address yet, or your tipping address doesn't contain enough ZEN. Keep in mind that there is a <b>$fee</b> fee.", $messageId);
              }
              else if ($tipResult === TRUE) {
                if($senderUsername === NULL){
                  $senderUsername = $senderName;
                }
                sendMessage($chatId, "$senderUsername just sent you <b>$tip</b> ZEN as a tip!", $messageIdToReplyTo);
              }
            }
            else {
              sendMessage($chatId, "For security reasons you can't tip more than 1 ZEN.", $messageId);
            }
          }
        }
        else {
          //To implement later
          // /tip username 0.1
        }
      }
    }
    break;

  case '/deposit':
    if ($chatType === 'private') {
      $address = 'Feature Disabled';
      $address = getDepositAddress($senderUserId);
      zlog('/deposit', 'Requested depo addr for ' . anonUserId($senderUserId));
      sendMessage($chatId, "
This is your deposit address:
$address

Send any amount of ZEN to it. You'll be able to withdraw it at any time by using /withdraw.
When sending tips, a fee of $fee will be subtracted from your balance.");
    }
    break;

  case '/withdraw':
    if ($chatType === 'private') {
      if(!empty($messageArr[1]) && is_numeric($messageArr[1]) && $messageArr[1] > 0) {
        $amount = $messageArr[1];
        $result = withdraw($config, $senderUserId, $amount);

        if ($result === FALSE) {
        }
        else if ($result === 'no_balance') {
          sendMessage($chatId, "You either haven't created a deposit address yet, or your tipping address doesn't contain enough ZEN. Keep in mind that there is a <b>$fee</b> fee.");
        }
        else if ($result === 'no_withdraw') {
          sendMessage($chatId, "There is no withdrawal address associated with your account. 
You can add one with “/myaddress”.");
        }
        else if ($result === TRUE) {
          sendMessage($chatId, "Success. Your $amount ZEN are now on their way to your /myaddress address.");
          zlog('/withdraw', 'Withdraw ' . $amount . ' from ' . anonUserId($senderUserId));
        }
      }
      else{
        sendMessage($chatId, "
Sends the specified amount from your /deposit address, to your /myaddress address.

Usage: <code>/withdraw</code> <b>amount</b>

/withdraw 0.1");
        die();
      }
    }
    break;

  case '/tipbot':
    if ($chatType === 'private') {
      sendMessage($chatId, '
To use the tipping bot you have to get your deposit address by using “/deposit”. 

Send some ZEN to this address and wait for the transaction to confirm. Use the  “/mybalance” command to see if your ZEN arrived.

Once you have a balance, simply reply to a users message in a group chat with “/tip” as seen on the screenshot <a href="https://puu.sh/ATVvn/d765ac3c3d.png">here</a>.

To withdraw your tips or balance you\'ll firstly have to use /myaddress to set a withdrawal address.
Then, simply use “/withdraw” with the amount behind. `/withdraw 0.1` would withdraw 0.0999 from your tipping address and send it to the address you set with “/myaddress”.');
    }
    break;

  /*
   * TIPPING BOT
   */

  case '/id':
    sendMessage($chatId, $chatId . ' ' . $senderUserId);
    break;
  case '/testdev':
    require_once('testdev.php');
    break;
  default:
    if ($chatType === 'private') {
      sendMessage($chatId, 'Unknown command! Use /zencommands if you need assistance or contact @DatDraggy.');
    }
}
