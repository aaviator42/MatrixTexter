<?php
require 'MatrixTexter.php';

$homeserver = "https://matrix.org";
$username = "USERNAME_GOES_HERE";
$password = "PASSWORD_GOES_HERE";
$roomID = "!ROOM_ID_GOES_HERE:matrix.org";

$accessToken = \MatrixTexter\login($homeserver, $username, $password);

// Mode 1: last 5 messages
echo "=== Last 5 messages ===" . PHP_EOL;
$messages = \MatrixTexter\getMessages($homeserver, $accessToken, $roomID, 5);
foreach($messages as $msg){
	echo $msg["sender"] . ": " . $msg["body"] . PHP_EOL;
}

echo PHP_EOL;

// Mode 2: all messages since last message by user test_user
echo "=== Messages since last test_user message ===" . PHP_EOL;
$messages = \MatrixTexter\getMessages($homeserver, $accessToken, $roomID, 50, "@test_user:matrix.org");
foreach($messages as $msg){
	echo $msg["sender"] . ": " . $msg["body"] . PHP_EOL;
}

echo PHP_EOL;

// Mode 3: last 5 messages by test_user
echo "=== Last 5 messages by test_user ===" . PHP_EOL;
$messages = \MatrixTexter\getMessages($homeserver, $accessToken, $roomID, 5, NULL, "@test_user:matrix.org");
foreach($messages as $msg){
	echo $msg["sender"] . ": " . $msg["body"] . PHP_EOL;
}
