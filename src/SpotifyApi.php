<?php
namespace SpotifyPHPApi;
class SpotifyApi {
	private $ClientID; 
	private $ClientSecret;
	private $RedirectURL;
	const SCOPES = array(
		'ugc-image-upload','user-read-playback-state','user-modify-playback-state','user-read-currently-playing',
		'streaming','app-remote-control','user-read-email','user-read-private','playlist-read-collaborative',
		'playlist-modify-public','playlist-read-private','playlist-modify-private','user-library-modify',
		'user-library-read','user-top-read','user-read-playback-position','user-read-recently-played',
		'user-follow-read','user-follow-modify'
	);
	function __construct() {

	}
	function setClientId($clientid)
	{
		$clientid = $this->clearIdSecret($clientid);
		if(strlen($clientid) == 32)
			$this->ClientID = $clientid;
		else
			throw new \Exception('Invalid client id.');
	}
	function setClientSecret($secret)
	{
		$secret = $this->clearIdSecret($secret);
		if(strlen($secret) == 32)
			$this->ClientSecret = $secret;
		else
			throw new \Exception('Invalid client secret.');
	}	
	function setRedirectURL($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL))
			$this->RedirectURL = $url;
		else
			throw new \Exception('Invalid URL.');
	}	
	function checkVars()
	{
		if(!empty($this->RedirectURL) && !empty($this->ClientID) && !empty($this->ClientSecret))
			return true;
		return false;
	}
	function checkScopes($scopes)
	{
		if(!is_array($scopes)) return false;
		foreach($scopes as $scope)
			if(!in_array($scope, $this::SCOPES)) return false;
		return true;
	}
	function getUserAuthHref($inputScopes)
	{
		$scopes = array();
		if(!empty($inputScopes))
			if(!is_array($inputScopes)) $scopes = explode(',', $inputScopes);
		if(!$this->checkVars()) throw new Exception('Required variable(s) not set.');
		else if(!$this->checkScopes($scopes) && count($scopes)>0) throw new Exception('Invalid Scope(s).');
		else
		return 'https://accounts.spotify.com/authorize' . 
		'?response_type=code' .
		'&client_id=' . $this->ClientID .
		( (count($scopes) > 0) ? '&scope=' . urlencode(implode(',', $scopes)) : '' ). '&redirect_uri=' . urlencode($this->RedirectURL);
		return "";
	}
	function getTokens($code)
	{
		if(!$this->checkVars()) throw new Exception('Required variable(s) not set.');
		else
		{
			$code = $this->clearToken($code);
			$data = array(
				'grant_type' => 'authorization_code',
				'code' => $code,
				'redirect_uri' => $this->RedirectURL
			);
			$result = $this->webRequest($data);
			if($result['success'] == false)
			{
				throw new Exception($result['error']);
			}
			else 
			{
				return $result;
			}
		}
	}
	function refreshToken($refreshToken)
	{
		if(!$this->checkVars()) throw new Exception('Required variable(s) not set.');
		else
		{
			$refreshToken = $this->clearToken($refreshToken);
			$data = array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken
			);
			$result = $this->webRequest($data);
			if($result['success'] == false)
			{
				throw new Exception($result['error']);
			}
			else 
			{
				return $result;
			}
		}		
	}
	function webRequestApi($urlSuffix, $authorization_code)
	{
		$authorization_code = $this->clearToken($authorization_code);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com'.$urlSuffix);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$headers = array();
		$headers[] = 'Accept: application/json';
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Authorization: Bearer '.$authorization_code;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$result = array('success' => false, 'error' => 'curl_error', 'error_description' => curl_error($ch));
		}
		else if(json_decode($result) != TRUE)
		{
			$result = array('success' => false, 'error' => 'json_error', 'error_description' => 'JSON parse data error.');
		}
		else {
			$json = json_decode($result, true);
			if(array_key_exists('error', $json))
				$result = array_merge(array('success' => false), json_decode($result,true));
			else 
				$result = array_merge(array('success' => true), json_decode($result,true));
		}
		curl_close($ch);
		return $result;		
	}
	function webRequest($post)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
		$headers = array();
		$headers[] = 'Authorization: Basic '.base64_encode($this->ClientID . ':' . $this->ClientSecret);
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$result = array('success' => false, 'error' => 'curl_error', 'error_description' => curl_error($ch));
		}
		else if(json_decode($result) != TRUE)
		{
			$result = array('success' => false, 'error' => 'json_error', 'error_description' => 'JSON parse data error.');
		}
		else {
			$json = json_decode($result, true);
			if(array_key_exists('error', $json))
				$result = array_merge(array('success' => false), json_decode($result,true));
			else 
				$result = array_merge(array('success' => true), json_decode($result,true));
		}
		curl_close($ch);
		return $result;		
	}
	function clearIdSecret($string)
	{
		return preg_replace("/[^a-z0-9]+/", "", $string);
	}
	function clearToken($token)
	{
		return preg_replace("/[^a-zA-Z0-9_-]+/", "", $token);
	}
}