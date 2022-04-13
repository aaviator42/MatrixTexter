# MatrixTexter
`v1.0`: `2022-04-13`

### What?

This library allows you to easily send messages to [matrix.org](https://matrix.org/) chat rooms from your PHP scripts.

I use it to send server cron job alerts to my devices, and it works beautifully. 


### Setup and usage
I'm using the [Element](https://app.element.io) Matrix client, but it really doesn't matter.

1. Create a Matrix account that'll be used to send the messages.
2. Create or join a chat room in which you want to send messages.  
   Note the room ID (in Element, this is visible in the URL bar when you open the chat room. Looks something like `!bAZNrHWkNobgqyTJiU:matrix.org`).  
   Make sure end-to-end encryption is turned *off*.
4. Include `MatrixTexter.php` in your script, and use as below:

```php
require 'MatrixTexter.php';

$homeserver = "https://matrix.org";
$username = "USERNAME_GOES_HERE";
$password = "PASSWORD_GOES_HERE";
$roomID = "!ROOM_ID_GOES_HERE:matrix.org";

$accessToken = \MatrixTexter\login($homeserver, $username, $password);

$message = "Test Message!";

\MatrixTexter\sendMessage($homeserver, $accessToken, $roomID, $message);
```

### Functions
#### 1. `login($homeserver, $username, $password)`
Authenticates with the homeserver and returns an access token that should be used while sending messages.

#### 2. `sendMessage($homeserver, $accessToken, $roomID, $message)`
Send the message to the specified chat room.

### Notes

1. You can specify a PEM file for SSL requests at the top of `MatrixTexter.php`. 
2. Access tokens do not expire (at least, not when the homesever is running [Synapse](https://github.com/matrix-org/synapse)), and should be cached. You do not need to call `login()` every time you want to send a message. 

Relevant documentation: [[1]](https://spec.matrix.org/v1.2/client-server-api/), [[2]](https://www.postman.com/recaptime-dev/workspace/matrix-api-spec/documentation/13093388-4285b9b9-66c6-4180-8a8d-bffd91d40351).

------------
Documentation updated: `2022-04-13`.
