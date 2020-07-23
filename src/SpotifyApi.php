<?php
/*
	***************************************************************************
	*   Spotify Api for PHP
	*   @package   spotify-php-api
	*   @author    Alperen Çetin										
	*   This code is licensed under MIT license (see LICENSE for details)
	***************************************************************************
*/
namespace SpotifyPHPApi;
class SpotifyApi {
	private $ClientID; 
	private $ClientSecret;
	private $RedirectURL;
	private const SCOPES = array(
		'ugc-image-upload','user-read-playback-state','user-modify-playback-state','user-read-currently-playing',
		'streaming','app-remote-control','user-read-email','user-read-private','playlist-read-collaborative',
		'playlist-modify-public','playlist-read-private','playlist-modify-private','user-library-modify',
		'user-library-read','user-top-read','user-read-playback-position','user-read-recently-played',
		'user-follow-read','user-follow-modify'
	);
	function __construct() {

	}
	public function setClientId($clientid)
	{
		$clientid = self::clearIdSecret($clientid);
		if(strlen($clientid) == 32)
			$this->ClientID = $clientid;
		else
			throw new \Exception('Invalid client id.');
	}
	public function setClientSecret($secret)
	{
		$secret = self::clearIdSecret($secret);
		if(strlen($secret) == 32)
			$this->ClientSecret = $secret;
		else
			throw new \Exception('Invalid client secret.');
	}	
	public function setRedirectURL($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL))
			$this->RedirectURL = $url;
		else
			throw new \Exception('Invalid URL.');
	}	
	private function checkVars()
	{
		if(!empty($this->RedirectURL) && !empty($this->ClientID) && !empty($this->ClientSecret))
			return true;
		return false;
	}
	private static function checkScopes($scopes)
	{
		if(!is_array($scopes)) return false;
		foreach($scopes as $scope)
			if(!in_array($scope, self::SCOPES)) return false;
		return true;
	}
	public function getUserAuthHref($inputScopes)
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
	public  function getTokens($code)
	{
		if(!$this->checkVars()) throw new Exception('Required variable(s) not set.');
		else
		{
			$code = self::clearToken($code);
			$data = array(
				'grant_type' => 'authorization_code',
				'code' => $code,
				'redirect_uri' => $this->RedirectURL
			);
			$result = self::webRequest($data);
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
	public function refreshToken($refreshToken)
	{
		if(!$this->checkVars()) throw new Exception('Required variable(s) not set.');
		else
		{
			$refreshToken = self::clearToken($refreshToken);
			$data = array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken
			);
			$result = self::webRequest($data);
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
	public static function webRequestApi($urlSuffix, $authorization_code)
	{
		$authorization_code = self::clearToken($authorization_code);
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
	private function webRequest($post)
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
	private static function clearIdSecret($string)
	{
		return preg_replace("/[^a-z0-9]+/", "", $string);
	}
	private static function clearToken($token)
	{
		return preg_replace("/[^a-zA-Z0-9_-]+/", "", $token);
	}
}
?>