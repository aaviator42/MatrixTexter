# MatrixTexter
`v1.3`: `2026-03-09`

### What?

This library allows you to easily send and fetch messages from [matrix.org](https://matrix.org/) chat rooms using your PHP scripts.

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

// Send a message
$message = "Test Message!";
\MatrixTexter\sendMessage($homeserver, $accessToken, $roomID, $message);

// Fetch last 5 messages
$messages = \MatrixTexter\getMessages($homeserver, $accessToken, $roomID, 5);
foreach($messages as $msg){
	echo $msg["sender"] . ": " . $msg["body"] . PHP_EOL;
}
```

### Functions
If an action fails, the function will throw an exception with the error code received from the homeserver.

#### 1. `login($homeserver, $username, $password)`
Authenticates with the homeserver and returns an access token that should be used while sending messages. If token caching is enabled, returns the cached token when available.

#### 2. `logout($homeserver, $accessToken)`
Invalidates a user access token, so that it can no longer be used to send messages. Also deletes any cached token.

#### 3. `sendMessage($homeserver, $accessToken, $roomID, $message)`
Sends the message to the specified chat room. Returns the event ID of the sent message.

#### 4. `getMessages($homeserver, $accessToken, $roomID, $limit, $sinceSender, $bySender)`
Fetches messages from the specified chat room. Returns an array of messages, each containing `sender`, `body`, and `timestamp`. Supports three modes:

- **Last X messages**: `getMessages($hs, $token, $room, 10)` — fetches the last 10 messages.
- **Since sender**: `getMessages($hs, $token, $room, 50, "@bot:matrix.org")` — fetches all messages since the last message by the specified sender.
- **By sender**: `getMessages($hs, $token, $room, 10, NULL, "@user:matrix.org")` — fetches the last 10 messages sent by a specific user.

### Configuration

The following constants can be set at the top of `MatrixTexter.php`:

| Constant | Default | Description |
|---|---|---|
| `CURL_PEM_FILE` | `NULL` | Path to a PEM certificate file for TLS requests |
| `TOKEN_CACHE_FILE` | `'token-cache.txt'` | Path to token cache file. Set to `NULL` to disable caching |
| `CACHE_TIMEOUT` | `24` | Token cache timeout in hours |
| `ALLOW_INSECURE` | `true` | Skip SSL verification for cURL requests |
| `MAX_FETCH_BATCHES` | `10` | Max pagination batches when using `getMessages` in sinceSender or bySender mode |

### Notes

1. Access tokens do not expire (at least, not when the homeserver is running [Synapse](https://github.com/matrix-org/synapse)). Token caching is enabled by default via `TOKEN_CACHE_FILE` so that `login()` doesn't hit the homeserver on every call.
2. If a `sendMessage` or `getMessages` call fails with an auth error, the cached token is automatically deleted so the next `login()` call fetches a fresh token.

Relevant documentation: [[1]](https://spec.matrix.org/v1.2/client-server-api/), [[2]](https://www.postman.com/recaptime-dev/workspace/matrix-api-spec/documentation/13093388-4285b9b9-66c6-4180-8a8d-bffd91d40351).

------------
Documentation updated: `2026-03-09`.
