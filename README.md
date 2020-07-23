# Spotify PHP Api
A simple Spotify Web Api, written with PHP

[Spotify Web Api](https://developer.spotify.com/documentation/web-api/guides/) & [Spotify Console Docs](https://developer.spotify.com/console/) & [Spotify Developer Dashboard](https://developer.spotify.com/dashboard/)
## Install
**Via Composer**

    $ composer require alperencetin/spotify-php-api
**Manual**

Download and use **src/Spotify.Api.php**

## Using
**Via Composer**

    require_once __DIR__ . '/vendor/autoload.php';
    $spotifyApi = new SpotifyPHPApi\SpotifyApi();
**Manual**

    require_once('src/SpotifyApi.php');
    $spotifyApi = new SpotifyPHPApi\SpotifyApi();

[Examples](tests)

Examples are for private use. I recommend hide tokens variables for public use.
