<?php
//MatrixTexter
//by @aaviator42
//v1.2 : 2022-04-23
//https://github.com/aaviator42/MatrixTexter

namespace MatrixTexter;
const CURL_PEM_FILE = NULL; // path to certificate file for TLS requests
const TOKEN_CACHE_FILE = 'token-cache.txt'; // path to token cache file, null = cache disabled
const CACHE_TIMEOUT = 24; // cache timeout in hours
const ALLOW_INSECURE = true; // true = skip SSL verification
const MAX_FETCH_BATCHES = 10; // max pagination batches for sinceSender mode

use Exception;

function login($homeserver, $username, $password){

	// Check token cache
	if(!empty(TOKEN_CACHE_FILE) && file_exists(TOKEN_CACHE_FILE)){
		$fileAge = (time() - filemtime(TOKEN_CACHE_FILE)) / 3600;
		if($fileAge < CACHE_TIMEOUT){
			$cached = file_get_contents(TOKEN_CACHE_FILE);
			if($cached !== false && !empty($cached)){
				return $cached;
			}
		}
	}

	$payload = array (
		"type" => "m.login.password",
		"identifier" => array (
			"type" => "m.id.user",
			"user" => $username
		),
		"password" => $password
	);

	$URL = $homeserver . "/_matrix/client/v3/login";

	$response = sendRequest("POST", $URL, NULL, $payload);
	$response = json_decode($response, true);

	if(isset($response["access_token"])){
		// Write token to cache
		if(!empty(TOKEN_CACHE_FILE)){
			file_put_contents(TOKEN_CACHE_FILE, $response["access_token"]);
		}
		return $response["access_token"];
	} else {
		throw new Exception("Unable to login: " . $response["errcode"] . PHP_EOL);
	}
}

function logout($homeserver, $accessToken){

	$URL = $homeserver . "/_matrix/client/v3/logout";

	$response = sendRequest("POST", $URL, NULL, NULL, $accessToken);
	$response = json_decode($response, true);
	if(isset($response["errcode"])){
		throw new Exception("Unable to log out: " . $response["errcode"] . PHP_EOL);
	} else {
		// Delete cached token since it's now invalidated
		if(!empty(TOKEN_CACHE_FILE) && file_exists(TOKEN_CACHE_FILE)){
			unlink(TOKEN_CACHE_FILE);
		}
		return true;
	}
}

function sendMessage($homeserver, $accessToken, $roomID, $message){
	
	$payload = array(
		"msgtype" => "m.text",
		"body" => $message
	);
	
	$params = array(
		"access_token" => $accessToken
	);

	$URL = $homeserver . '/_matrix/client/v3/rooms/' . $roomID . '/send/m.room.message/' . uniqid();
	
	$response = sendRequest("PUT", $URL, $params, $payload);
	$response = json_decode($response, true);
	
	if(!isset($response["errcode"])){
		return $response["event_id"];
	} else {
		if(!empty(TOKEN_CACHE_FILE) && file_exists(TOKEN_CACHE_FILE)){
			unlink(TOKEN_CACHE_FILE);
		}
		throw new Exception("Unable to send message: " . $response["errcode"] . PHP_EOL);
	}
}

// Fetch messages from a room
// Mode 1: getMessages($hs, $token, $room, 10)                          - last X messages
// Mode 2: getMessages($hs, $token, $room, 50, "@bot:matrix.org")       - all messages since last message by sender
// Mode 3: getMessages($hs, $token, $room, 10, NULL, "@user:matrix.org") - last X messages by a specific user
function getMessages($homeserver, $accessToken, $roomID, $limit = 10, $sinceSender = NULL, $bySender = NULL){

	$params = array(
		"access_token" => $accessToken,
		"dir" => "b",
		"filter" => '{"types":["m.room.message"]}'
	);

	$URL = $homeserver . '/_matrix/client/v3/rooms/' . $roomID . '/messages';

	if($bySender !== NULL){
		// Mode 3: last X messages by a specific user
		$messages = array();
		$from = NULL;
		$batches = 0;

		while(count($messages) < $limit && $batches < MAX_FETCH_BATCHES){
			$batchParams = $params;
			$batchParams["limit"] = $limit;
			if($from !== NULL){
				$batchParams["from"] = $from;
			}

			$response = sendRequest("GET", $URL, $batchParams, NULL);
			$response = json_decode($response, true);

			if(isset($response["errcode"])){
				if(!empty(TOKEN_CACHE_FILE) && file_exists(TOKEN_CACHE_FILE)){
					unlink(TOKEN_CACHE_FILE);
				}
				throw new Exception("Unable to fetch messages: " . $response["errcode"] . PHP_EOL);
			}

			if(empty($response["chunk"])){
				break;
			}

			foreach($response["chunk"] as $event){
				if($event["sender"] === $bySender){
					$messages[] = array(
						"sender" => $event["sender"],
						"body" => $event["content"]["body"] ?? "",
						"timestamp" => $event["origin_server_ts"]
					);
					if(count($messages) >= $limit){
						break;
					}
				}
			}

			if(!isset($response["end"])){
				break;
			}
			$from = $response["end"];
			$batches++;
		}

		return array_reverse($messages);

	} else if($sinceSender === NULL){
		// Mode 1: last X messages
		$params["limit"] = $limit;

		$response = sendRequest("GET", $URL, $params, NULL);
		$response = json_decode($response, true);

		if(isset($response["errcode"])){
			if(!empty(TOKEN_CACHE_FILE) && file_exists(TOKEN_CACHE_FILE)){
				unlink(TOKEN_CACHE_FILE);
			}
			throw new Exception("Unable to fetch messages: " . $response["errcode"] . PHP_EOL);
		}

		$messages = array();
		foreach($response["chunk"] as $event){
			$messages[] = array(
				"sender" => $event["sender"],
				"body" => $event["content"]["body"] ?? "",
				"timestamp" => $event["origin_server_ts"]
			);
		}
		return array_reverse($messages);

	} else {
		// Mode 2: fetch backwards until we find a message from $sinceSender
		$messages = array();
		$from = NULL;
		$batches = 0;

		while($batches < MAX_FETCH_BATCHES){
			$batchParams = $params;
			$batchParams["limit"] = $limit;
			if($from !== NULL){
				$batchParams["from"] = $from;
			}

			$response = sendRequest("GET", $URL, $batchParams, NULL);
			$response = json_decode($response, true);

			if(isset($response["errcode"])){
				if(!empty(TOKEN_CACHE_FILE) && file_exists(TOKEN_CACHE_FILE)){
					unlink(TOKEN_CACHE_FILE);
				}
				throw new Exception("Unable to fetch messages: " . $response["errcode"] . PHP_EOL);
			}

			if(empty($response["chunk"])){
				break;
			}

			foreach($response["chunk"] as $event){
				if($event["sender"] === $sinceSender){
					return array_reverse($messages);
				}
				$messages[] = array(
					"sender" => $event["sender"],
					"body" => $event["content"]["body"] ?? "",
					"timestamp" => $event["origin_server_ts"]
				);
			}

			if(!isset($response["end"])){
				break;
			}
			$from = $response["end"];
			$batches++;
		}

		return array_reverse($messages);
	}
}

//From https://github.com/aaviator42/ZaapRemote
function sendRequest($method = NULL, $URL = NULL, $params = NULL, $payload = NULL, $bearerToken = NULL){
	
	if(empty($method) || empty($URL)){
		throw new Exception("Method or URL not specified");
	}
	
	if(!empty($params)){
		rtrim($URL, '?');
		$URL .= "?";
		foreach($params as $key => $value){
			$URL = $URL . $key . "=" . $value . "&";
		}
	}
	
	$ch = curl_init();
	$options = array(
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_URL => $URL,
		CURLOPT_USERAGENT => "MatrixTexter by @aaviator42 (v1.0)",
		CURLOPT_TIMEOUT => 60,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => !ALLOW_INSECURE,
		CURLOPT_SSL_VERIFYHOST => ALLOW_INSECURE ? 0 : 2);
	
	$headers = array();

	if(!empty($bearerToken)){
		$headers[] = 'Authorization: Bearer ' . $bearerToken;
	}

	if(!empty($payload)){
		$payload  = json_encode($payload);
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Content-Length: ' . strlen($payload);
		$options[CURLOPT_POSTFIELDS] = $payload;
	}

	if(!empty($headers)){
		$options[CURLOPT_HTTPHEADER] = $headers;
	}
	
	if(!empty(CURL_PEM_FILE)){
		$options[CURLOPT_CAINFO] = CURL_PEM_FILE;
	}

	curl_setopt_array($ch, $options);
	$content = curl_exec($ch);		
	
	if(curl_errno($ch)){
		throw new Exception("cURL Error: " . curl_error($ch));
	}
	
	return $content;
}
