<?php
require_once(__DIR__ . "/funcs.php");
require_once(__DIR__ . "/config.php");

$response = file_get_contents('php://input');
$data = json_decode($response, true);
$dump = print_r($data, true);

$chatId = $data['message']['chat']['id'];
$chatType = $data['message']['chat']['type'];
$message = $data['message']['text'];

if (substr($message, '0', '1') == '/') {
  $messageArr = explode(' ', $message);
  $command = $messageArr[0];
  if($messageArr[1] == 'commands'){
    $command = '/commands';
  }
}
else{
  die();
}

$nodeText = 'We have <a href="https://zencash.com/securenodes">secure nodes</a> and will hopefully deploy the <b>super nodes</b> in July.
A <i>secure</i> node needs 42 ZEN in a transparent address, whereas the <i>super</i> node requires 500 ZEN.';

switch ($command) {
  case '/start':
    if ($chatType === 'private') {
      sendMessage($chatId, 'Hello!
    
I\'m the ZenCash Help Bot. I can provide quick information about topics when I see a command that I know.
To get a list of all commands I know, simply send /commands to me.

I am also open source, so if you like you can add your own commands by creating a pull request here: https://github.com/DatDraggy/zencash-help-bot');
    }
      break;

  case '/price':
    sendMessage($chatId, getCurrentPrice() . '

<code>Source: Bittrex</code>');
    break;

  case '/nodes':
    sendMessage($chatId, $nodeText);
    break;

  case '/securenode':
  case '/securenodes':
    sendMessage($chatId, '
For a secure node, you need 42 ZEN and a small VPS with a single core cpu, ~20GB space, 3-4GB RAM + some swap and a domain. 

More info can be found here: https://zencash.com/securenodes 
');
    break;

  case '/securenodesreward':
    //ToDo: Add calculations
    sendMessage($chatId, 'Current earnings per day: '.getCurrentReward().'

You can see the current daily reward for a secure node here: https://securenodes.zensystem.io
');
    break;

  case '/masternodes':
    sendMessage($chatId, '
We do not have masternodes. ' . $nodeText . '
');
    break;

  case '/commands':
  case '/help':
    if ($chatType === 'private') {
      sendMessage($chatId, '
Here is a small list of available commands. Click them to find out what they say.

/price
/start
/nodes
/securenodes
/securenodesreward
/masternodes
/commands
/help
/ping
');
    }
    else {
      //ToDo: Check last use of command/create a timeout
      sendMessage($chatId, '
Click here to get a list of all commands:
https://telegram.me/zencashhelp_bot?start=commands
');
    }
    break;

  case '/ping':
    sendMessage($chatId, 'Pong.');
    break;

  default:
    if ($chatType === 'private') {
      sendMessage($chatId, 'Unknown command! Use /commands if you need assistance or contact @DatDraggy.');
    }
}