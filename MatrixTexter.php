<?php
//MatrixTexter
//by @aaviator42
//v1.2 : 2022-04-23
//https://github.com/aaviator42/MatrixTexter

namespace MatrixTexter;
const CURL_PEM_FILE = NULL; //path to certificate file for TLS requests

use Exception;

function login($homeserver, $username, $password){
	
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
		return $response["access_token"];
	} else {
		throw new Exception("Unable to login.");
	}
}

function logout($homeserver, $accessToken){
	
	$URL = $homeserver . "/_matrix/client/v3/logout";
	$params = array(
		"access_token" => $accessToken
	);
	
	$response = sendRequest("POST", $URL, $params, NULL);
	var_dump($response);
	$response = json_decode($response, true);
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
	
	if(isset($response["event_id"])){
		return $response["event_id"];
	} else {
		throw new Exception("Unable to send message");
	}
}
	
//From https://github.com/aaviator42/ZaapRemote
function sendRequest($method = NULL, $URL = NULL, $params = NULL, $payload = NULL){
	
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
		CURLOPT_RETURNTRANSFER => true);
	
	if(!empty($payload)){
		$payload  = json_encode($payload);
		$headers = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($payload));
		$options[CURLOPT_POSTFIELDS] = $payload;
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
