<?php
//https://github.com/aaviator42/MatrixTexter

require 'MatrixTexter.php';

$homeserver = "https://matrix.org";
$username = "USERNAME_GOES_HERE";
$password = "PASSWORD_GOES_HERE";
$roomID = "!ROOM_ID_GOES_HERE:matrix.org";

//authenticate with homeserver
$accessToken = \MatrixTexter\login($homeserver, $username, $password);

$message = "Test Message!";

//send message to room
\MatrixTexter\sendMessage($homeserver, $accessToken, $roomID, $message);

//inavlidate access token
\MatrixTexter\logout($homeserver, $accessToken); 