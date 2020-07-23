<?php 
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload
$spotify = new SpotifyPHPApi\SpotifyApi();
try {
	/* 
		CREATE APP FROM SPOTIFY DEVELOPER DASHBOARD
		https://developer.spotify.com/dashboard/applications/
	*/
	$spotify->setClientId('changeMe'); // 32 character App Client ID
	$spotify->setClientSecret('changeMe'); // 32 character App Client Secret
	$spotify->setRedirectURL('changeMe'); // App Redirect URL : also its must to be add to app settings from spotify dashboard
}
catch(Exception $err)
{
	echo $err;
	exit();
}

if(isset($_GET['code']))
{
	$tokens = $spotify->getTokens($_GET['code']);
	if($tokens['success'])
	{
		$array = array(
			'token' => $tokens['access_token'],
			'refresh' => $tokens['refresh_token']
		);
		header("Location: ?".http_build_query($array));
	}
	exit();
}
else if(isset($_GET['token']) && isset($_GET['refresh']))
{
	$result = $spotify->webRequestApi('/v1/me/player/recently-played', $_GET['token']);
	if($result['success'])
	{
		$items = array();
		foreach($result['items'] as $item)
		{
			if(!array_key_exists('track', $item)) continue;
			$artists = array();
			foreach($item['track']['artists'] as $artist) $artists[] = $artist['name'];
			$items[] = array(
				'name' => $item['track']['name'],
				'artists' => count($artists) > 0 ? implode(", ", $artists) : 'N/A',
				'image' => $item['track']['album']['images'][0]['url']
			);			
		}
		?>
		<table>
			<thead>
				<th></th>
				<th></th>
			</thead>
			<tbody>
			<?php
				foreach($items as $item)
				{
					echo '
					<tr>
						<td><img src="'.$item['image'].'" width="35px"></td>
						<td><b>'.$item['name'].'</b> - '.$item['artists']."</td>
					</tr>";
				}
			?>
			</tbody>
		</table>
		<?php
	}
	else
		if(array_key_exists('message', $result['error']))
			if($result['error']['message'] == 'The access token expired')
			{	
				$tokens = $spotify->refreshToken($_GET['refresh']);
				$array = array(
					'token' => $tokens['access_token'],
					'refresh' => $_GET['refresh']
				);
				header("Location: ?".http_build_query($array));
			}
	exit();
}
$authURL = $spotify->getUserAuthHref('user-read-recently-played');
echo '<a href="'.$authURL.'">Click for App Login</a>';