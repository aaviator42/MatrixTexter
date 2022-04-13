<?php
//https://github.com/aaviator42/MatrixTexter

require 'MatrixTexter.php';

$homeserver = "https://matrix.org";
$username = "USERNAME_GOES_HERE";
$password = "PASSWORD_GOES_HERE";
$roomID = "!ROOM_ID_GOES_HERE:matrix.org";

$accessToken = \MatrixTexter\login($homeserver, $username, $password);

$message = "Test Message!";

\MatrixTexter\sendMessage($homeserver, $accessToken, $roomID, $message);