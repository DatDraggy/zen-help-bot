<?php
$json = '{"inline_keyboard": {"text": "Hello World","url": "https://telegram.me/zencashhelp_bot?start=commands"}}';
$json = '{"inline_keyboard": [["Test","https://google.com"],["Test2", "https://google.com"]]}';
$json = '{"keyboard":[["Yes","No"],["Maybe"],["1","2","3"]],"one_time_keyboard":true}';
$json = '[[{"text":"text 1"},{"text":"Some button text 2","url":"https://botpress.org"},{"text":"Some button text 3"}]]';
$replyMarkup = array(
     array(
        array("A", "B")
    )
);
$json = json_encode($replyMarkup);
echo $json;
sendMessage($chatId, 'Test', $json);
