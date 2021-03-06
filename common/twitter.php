<?php

require 'Autolink.php';
require 'Extractor.php';

menu_register(array(
	'' => array(
		'callback' => 'twitter_home_page',
		'accesskey' => '0',
	),
	'status' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_status_page',
	),
	'update' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_update',
	),
	'twitter-retweet' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_retweet',
	),
	'replies' => array(
		'security' => true,
		'callback' => 'twitter_replies_page',
		'accesskey' => '1',
	),
	'favourite' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_mark_favourite_page',
	),
	'unfavourite' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_mark_favourite_page',
	),
	'directs' => array(
		'security' => true,
		'callback' => 'twitter_directs_page',
		'accesskey' => '2',
	),
	'search' => array(
		'security' => true,
		'callback' => 'twitter_search_page',
		'accesskey' => '3',
	),
	'user' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_user_page',
	),
	'follow' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_follow_page',
	),
	'unfollow' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_follow_page',
	),
	'confirm' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_confirmation_page',
	),
	'block' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_block_page',
	),
	'unblock' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_block_page',
	),
	'spam' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_spam_page',
	),
	'favourites' => array(
		'security' => true,
		'callback' =>  'twitter_favourites_page',
	),
	'followers' => array(
		'security' => true,
		'callback' => 'twitter_followers_page',
	),
	'friends' => array(
		'security' => true,
		'security' => true,
		'callback' => 'twitter_friends_page',
	),
	'delete' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_delete_page',
	),
	'retweet' => array(
		'hidden' => true,
		'security' => true,
		'callback' => 'twitter_retweet_page',
	),
	'hash' => array(
		'security' => true,
		'hidden' => true,
		'callback' => 'twitter_hashtag_page',
	),
	'twitpic' => array(
		'security' => true,
		'callback' => 'twitter_twitpic_page',
	),
	'trends' => array(
		'security' => true,
		'callback' => 'twitter_trends_page',
	),
	'retweets' => array(
		'security' => true,
		'callback' => 'twitter_retweets_page',
	)
));

function long_url($shortURL)
{
	if (!defined('LONGURL_KEY'))
	{
		return $shortURL;
	}
	$url = "http://www.longurlplease.com/api/v1.1?q=" . $shortURL;
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($curl_handle,CURLOPT_URL,$url);
	$url_json = curl_exec($curl_handle);
	curl_close($curl_handle);

	$url_array = json_decode($url_json,true);

	$url_long = $url_array["$shortURL"];

	if ($url_long == null)
	{
		return $shortURL;
	}

	return $url_long;
}


function friendship_exists($user_a) {
	$request = API_URL.'friendships/show.json?target_screen_name=' . $user_a;
	$following = twitter_process($request);

	if ($following->relationship->target->following == 1) {
		return true;
	} else {
		return false;
	}
}

function friendship($user_a)
{
	$request = API_URL.'friendships/show.json?target_screen_name=' . $user_a;
	return twitter_process($request);
}


function twitter_block_exists($query)
{
	//http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-blocks-blocking-ids
	//Get an array of all ids the authenticated user is blocking
	$request = API_URL.'blocks/blocking/ids.json';
	$blocked = (array) twitter_process($request);

	//bool in_array  ( mixed $needle  , array $haystack  [, bool $strict  ] )
	//If the authenticate user has blocked $query it will appear in the array
	return in_array($query,$blocked);
}

function twitter_trends_page($query)
{
	$woeid = $_GET['woeid'];
	if($woeid == '') $woeid = '1'; //worldwide
	
	//fetch "local" names
	$request = API_URL.'trends/available.json';
	$local = twitter_process($request);
	$header = '<form method="get" action="trends"><select name="woeid">';
	$header .= '<option value="1"' . (($woeid == 1) ? ' selected="selected"' : '') . '>Worldwide</option>';
	foreach($local as $l) {
		if($l->woeid != 1) {
			$header .= '<option value="' . $l->woeid . '"' . (($l->woeid == $woeid) ? ' selected="selected"' : '') . '>' . $l->name . '</option>';
		}
	}
	$header .= '</select> <input type="submit" value="Go" /></form>';
	
	$request = API_URL.'trends/' . $woeid . '.json';
	$trends = twitter_process($request);
	$search_url = 'search?query=';
	foreach($trends[0]->trends as $trend) {
		$row = array('<strong><a href="' . str_replace('http://search.twitter.com/search?q=', $search_url, $trend->url) . '">' . $trend->name . '</a></strong>');
		$rows[] = array('data' => $row,  'class' => 'tweet');
	}
	$headers = array($header);
	$content = theme('table', $headers, $rows, array('class' => 'timeline'));
	theme('page', 'Trends', $content);
}

function js_counter($name, $length='140')
{
	$script = '<script type="text/javascript">
function updateCount() {
var remaining = ' . $length . ' - document.getElementById("' . $name . '").value.length;
document.getElementById("remaining").innerHTML = remaining;
if(remaining < 0) {
 var colour = "#FF0000";
 var weight = "bold";
} else {
 var colour = "";
 var weight = "";
}
document.getElementById("remaining").style.color = colour;
document.getElementById("remaining").style.fontWeight = weight;
setTimeout(updateCount, 400);
}
updateCount();
</script>';
	return $script;
}

function twitter_twitpic_page($query) {
	if (user_type() == 'oauth') {
		//V2 of the Twitpic API allows for OAuth
		//http://dev.twitpic.com/docs/2/upload/

		//Has the user submitted an image and message?
		if ($_POST['message']) {
			$twitpicURL = 'http://api.twitpic.com/2/upload.json';

			//Set the initial headers
			$header = array(
				'X-Auth-Service-Provider: https://api.twitter.com/1/account/verify_credentials.json', 
				'X-Verify-Credentials-Authorization: OAuth realm="http://api.twitter.com/"'
			);

			//Using Abraham's OAuth library
			require_once('OAuth.php');

			// instantiating OAuth customer
			$consumer = new OAuthConsumer(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET);

			// instantiating signer
			$sha1_method = new OAuthSignatureMethod_HMAC_SHA1();

			// user's token
			list($oauth_token, $oauth_token_secret) = explode('|', $GLOBALS['user']['password']);
			$token = new OAuthConsumer($oauth_token, $oauth_token_secret);

			// Generate all the OAuth parameters needed
			$signingURL = 'https://api.twitter.com/1/account/verify_credentials.json';
			$request = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $signingURL, array());
			$request->sign_request($sha1_method, $consumer, $token);

			$header[1] .= ", oauth_consumer_key=\"" . $request->get_parameter('oauth_consumer_key') ."\"";
			$header[1] .= ", oauth_signature_method=\"" . $request->get_parameter('oauth_signature_method') ."\"";
			$header[1] .= ", oauth_token=\"" . $request->get_parameter('oauth_token') ."\"";
			$header[1] .= ", oauth_timestamp=\"" . $request->get_parameter('oauth_timestamp') ."\"";
			$header[1] .= ", oauth_nonce=\"" . $request->get_parameter('oauth_nonce') ."\"";
			$header[1] .= ", oauth_version=\"" . $request->get_parameter('oauth_version') ."\"";
			$header[1] .= ", oauth_signature=\"" . urlencode($request->get_parameter('oauth_signature')) ."\"";

			//open connection
			$ch = curl_init();
										
			//Set paramaters
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL,$twitpicURL);
										
			//TwitPic requires the data to be sent as POST
			$media_data = array(
				'media' => '@'.$_FILES['media']['tmp_name'],
				'message' => ' ' . stripslashes($_POST['message']), //A space is needed because twitpic b0rks if first char is an @
				'key'=>TWITPIC_API_KEY
			);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$media_data);

			//execute post
			$result = curl_exec($ch);
			$response_info=curl_getinfo($ch);

			//close connection
			curl_close($ch);

			if ($response_info['http_code'] == 200) { //Success
				//Decode the response
				$json = json_decode($result);
				$id = $json->id;
				$twitpicURL = $json->url;
				$text = $json->text;
				$message = trim($text) . " " . $twitpicURL;

				//Send the user's message to twitter
				$request = API_URL.'statuses/update.json';

				$post_data = array('source' => 'dabr', 'status' => $message);
				$status = twitter_process($request, $post_data);

				//Back to the timeline
				twitter_refresh("twitpic/confirm/$id");
			}
			else {
				$content = "<p>Twitpic upload failed. No idea why!</p>";
				$content .=  "<pre>";
				$json = json_decode($result);
				$content .= "<br / ><b>message</b> " . urlencode($_POST['message']);
				$content .= "<br / ><b>json</b> " . print_r($json);
				$content .= "<br / ><b>Response</b> " . print_r($response_info);
				$content .= "<br / ><b>header</b> " . print_r($header);
				$content .= "<br / ><b>media_data</b> " . print_r($media_data);
				$content .= "<br /><b>URL was</b> " . $twitpicURL;
				$content .= "<br /><b>File uploaded was</b> " . $_FILES['media']['tmp_name'];
				$content .= "</pre>";
			}
		}
		elseif ($query[1] == 'confirm') {
			$content = "<p>Upload success. Image posted to Twitter.</p><p><img src='http://twitpic.com/show/thumb/{$query[2]}' alt='' /></p>";
		}
		else {
			$content = "<form method='post' action='twitpic' enctype='multipart/form-data'>Image <input type='file' name='media' /><br />Message (optional):<br /><textarea name='message' style='width:90%; max-width: 400px;' rows='3' id='message'></textarea><br><input type='submit' value='Send'><span id='remaining'>110</span></form>";
			$content .= js_counter("message", "110");
		}

		return theme('page', 'Twitpic Upload', $content);
	}
}

function twitter_process($url, $post_data = false)
{
	if ($post_data === true)
	{
		$post_data = array();
	}

	if (user_type() == 'oauth' && ( strpos($url, '/twitter.com') !== false || strpos($url, 'api.twitter.com') !== false))
	{
		user_oauth_sign($url, $post_data);
	}

	elseif (strpos($url, 'api.twitter.com') !== false && is_array($post_data))
	{
		// Passing $post_data as an array to twitter.com (non-oauth) causes an error :(
		$s = array();
		foreach ($post_data as $name => $value)
		$s[] = $name.'='.urlencode($value);
		$post_data = implode('&', $s);
	}

	$api_start = microtime(1);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);

	if($post_data !== false && !$_GET['page'])
	{
		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
	}

	if (user_type() != 'oauth' && user_is_authenticated())
	{
		curl_setopt($ch, CURLOPT_USERPWD, user_current_username().':'.$GLOBALS['user']['password']);
	}

	//from  http://github.com/abraham/twitteroauth/blob/master/twitteroauth/twitteroauth.php
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);

	$response = curl_exec($ch);
	$response_info=curl_getinfo($ch);
	$erno = curl_errno($ch);
	$er = curl_error($ch);
	curl_close($ch);

	global $api_time;
	$api_time += microtime(1) - $api_start;

	switch( intval( $response_info['http_code'] ) )
	{
		case 200:
		case 201:
			$json = json_decode($response);
			if ($json)
			{
				return $json;
			}
			return $response;
		case 401:
			user_logout();
			theme('error', "<p>Error: Login credentials incorrect.</p><p>{$response_info['http_code']}: {$result}</p><hr><p>$url</p>");
		case 0:
			$result = $erno . ":" . $er . "<br />" ;
			/*
			 foreach ($response_info as $key => $value)
			 {
				$result .= "Key: $key; Value: $value<br />";
				}
				*/
			theme('error', '<h2>Twitter timed out</h2><p>Dabr gave up on waiting for Twitter to respond. They\'re probably overloaded right now, try again in a minute. <br />'. $result . ' </p>');
		default:
			$result = json_decode($response);
			$result = $result->error ? $result->error : $response;
			if (strlen($result) > 500)
			{
				$result = 'Something broke on Twitter\'s end.';
				/*
				 $result .= $erno . ":" . $er . "<br />" ;
				 foreach ($response_info as $key => $value)
				 {
					$result .= "Key: $key; Value: $value<br />";
					}
					*/
			}
			theme('error', "<h2>An error occured while calling the Twitter API</h2><p>{$response_info['http_code']}: {$result}</p><hr><p>$url</p>");
	}
}

function twitter_url_shorten($text) {
	return preg_replace_callback('#((\w+://|www)[\w\#$%&~/.\-;:=,?@\[\]+]{33,1950})(?<![.,])#is', 'twitter_url_shorten_callback', $text);
}

function twitter_url_shorten_callback($match) {
	if (preg_match('#http://www.flickr.com/photos/[^/]+/(\d+)/#', $match[0], $matches)) {
		return 'http://flic.kr/p/'.flickr_encode($matches[1]);
	}
	if (!defined('BITLY_API_KEY')) return $match[0];
	$request = 'http://api.bit.ly/shorten?version=2.0.1&longUrl='.urlencode($match[0]).'&login='.BITLY_LOGIN.'&apiKey='.BITLY_API_KEY;
	$json = json_decode(twitter_fetch($request));
	if ($json->errorCode == 0) {
		$results = (array) $json->results;
		$result = array_pop($results);
		return $result->shortUrl;
	} else {
		return $match[0];
	}
}

function twitter_fetch($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}

class Dabr_Autolink extends Twitter_Autolink {
	function replacementURLs($matches) {
		$replacement  = $matches[2];
		$url = $matches[3];
		if (!preg_match("#^https{0,1}://#i", $url)) {
			$url = "http://{$url}";
		}
		if (setting_fetch('gwt') == 'on') {
			$encoded = urlencode($url);
			$replacement .= "<a href='http://google.com/gwt/n?u={$encoded}' target='_blank'>{$url}</a>";
		} else {
			$replacement .= theme('external_link', $url);
		}
		return $replacement;
	}
}

function twitter_parse_tags($input, $entities = false) {

	//Expanded t.co links to find thumbnails etc
	if($entities) {
		foreach($entities->urls as $urls) {
			if($urls->expanded_url != "") {
				$input = str_replace($urls->url, $urls->expanded_url, $input);
			}
		}
	}

	$urls = Twitter_Extractor::extractURLS($input);

	$out = $input;

	foreach ($urls as $value)
	{
		$out = str_replace ($value, long_url($value) , $out) ;
	}

	$autolink = new Dabr_Autolink();
	$out = $autolink->autolink($out);

	//If this is worksafe mode - don't display any images
	if (!in_array(setting_fetch('browser'), array('text', 'worksafe')))
	{
		//Add in images
		$out = twitter_embed_thumbnails($out);
	}

	//Linebreaks.  Some clients insert \n for formatting.
	$out = nl2br($out);

	//Return t.co links back else breaking Twitter T&Cs
	if($entities) {
		foreach($entities->urls as $urls) {
			if($urls->expanded_url != "") {
				$out = preg_replace('#(?<=(\"|\'|\>))'.preg_quote($urls->expanded_url,'#').'(?=(\"|\'|\<))#i', $urls->url, $out);
			}
		}
	}

	//Return the completed string
	return $out;
}

function flickr_decode($num) {
	$alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
	$decoded = 0;
	$multi = 1;
	while (strlen($num) > 0) {
		$digit = $num[strlen($num)-1];
		$decoded += $multi * strpos($alphabet, $digit);
		$multi = $multi * strlen($alphabet);
		$num = substr($num, 0, -1);
	}
	return $decoded;
}

function flickr_encode($num) {
	$alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
	$base_count = strlen($alphabet);
	$encoded = '';
	while ($num >= $base_count) {
		$div = $num/$base_count;
		$mod = ($num-($base_count*intval($div)));
		$encoded = $alphabet[$mod] . $encoded;
		$num = intval($div);
	}
	if ($num) $encoded = $alphabet[$num] . $encoded;
	return $encoded;
}

function twitter_embed_thumbnails($text) 
{
	if (setting_fetch('hide_inline')) {
		return $text;
	}
	$images = array();
	$tmp = strip_tags($text);
	
	//Using oEmbed from http://api.embed.ly/
	$embedly_re = "/http:\/\/(.*youtube\.com\/watch.*|.*\.youtube\.com\/v\/.*|youtu\.be\/.*|.*\.youtube\.com\/user\/.*|.*\.youtube\.com\/.*#.*\/.*|m\.youtube\.com\/watch.*|m\.youtube\.com\/index.*|.*\.youtube\.com\/profile.*|.*justin\.tv\/.*|.*justin\.tv\/.*\/b\/.*|.*justin\.tv\/.*\/w\/.*|www\.ustream\.tv\/recorded\/.*|www\.ustream\.tv\/channel\/.*|www\.ustream\.tv\/.*|qik\.com\/video\/.*|qik\.com\/.*|qik\.ly\/.*|.*revision3\.com\/.*|.*\.dailymotion\.com\/video\/.*|.*\.dailymotion\.com\/.*\/video\/.*|www\.collegehumor\.com\/video:.*|.*twitvid\.com\/.*|www\.break\.com\/.*\/.*|vids\.myspace\.com\/index\.cfm\?fuseaction=vids\.individual&videoid.*|www\.myspace\.com\/index\.cfm\?fuseaction=.*&videoid.*|www\.metacafe\.com\/watch\/.*|www\.metacafe\.com\/w\/.*|blip\.tv\/file\/.*|.*\.blip\.tv\/file\/.*|video\.google\.com\/videoplay\?.*|.*revver\.com\/video\/.*|video\.yahoo\.com\/watch\/.*\/.*|video\.yahoo\.com\/network\/.*|.*viddler\.com\/explore\/.*\/videos\/.*|liveleak\.com\/view\?.*|www\.liveleak\.com\/view\?.*|animoto\.com\/play\/.*|dotsub\.com\/view\/.*|www\.overstream\.net\/view\.php\?oid=.*|www\.livestream\.com\/.*|www\.worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|teachertube\.com\/viewVideo\.php.*|www\.teachertube\.com\/viewVideo\.php.*|www1\.teachertube\.com\/viewVideo\.php.*|www2\.teachertube\.com\/viewVideo\.php.*|bambuser\.com\/v\/.*|bambuser\.com\/channel\/.*|bambuser\.com\/channel\/.*\/broadcast\/.*|www\.schooltube\.com\/video\/.*\/.*|bigthink\.com\/ideas\/.*|bigthink\.com\/series\/.*|sendables\.jibjab\.com\/view\/.*|sendables\.jibjab\.com\/originals\/.*|www\.xtranormal\.com\/watch\/.*|dipdive\.com\/media\/.*|dipdive\.com\/member\/.*\/media\/.*|dipdive\.com\/v\/.*|.*\.dipdive\.com\/media\/.*|.*\.dipdive\.com\/v\/.*|v\.youku\.com\/v_show\/.*\.html|v\.youku\.com\/v_playlist\/.*\.html|www\.snotr\.com\/video\/.*|snotr\.com\/video\/.*|.*yfrog\..*\/.*|tweetphoto\.com\/.*|www\.flickr\.com\/photos\/.*|flic\.kr\/.*|twitpic\.com\/.*|www\.twitpic\.com\/.*|twitpic\.com\/photos\/.*|www\.twitpic\.com\/photos\/.*|.*imgur\.com\/.*|.*\.posterous\.com\/.*|post\.ly\/.*|twitgoo\.com\/.*|i.*\.photobucket\.com\/albums\/.*|s.*\.photobucket\.com\/albums\/.*|phodroid\.com\/.*\/.*\/.*|www\.mobypicture\.com\/user\/.*\/view\/.*|moby\.to\/.*|xkcd\.com\/.*|www\.xkcd\.com\/.*|imgs\.xkcd\.com\/.*|www\.asofterworld\.com\/index\.php\?id=.*|www\.asofterworld\.com\/.*\.jpg|asofterworld\.com\/.*\.jpg|www\.qwantz\.com\/index\.php\?comic=.*|23hq\.com\/.*\/photo\/.*|www\.23hq\.com\/.*\/photo\/.*|.*dribbble\.com\/shots\/.*|drbl\.in\/.*|.*\.smugmug\.com\/.*|.*\.smugmug\.com\/.*#.*|emberapp\.com\/.*\/images\/.*|emberapp\.com\/.*\/images\/.*\/sizes\/.*|emberapp\.com\/.*\/collections\/.*\/.*|emberapp\.com\/.*\/categories\/.*\/.*\/.*|embr\.it\/.*|picasaweb\.google\.com.*\/.*\/.*#.*|picasaweb\.google\.com.*\/lh\/photo\/.*|picasaweb\.google\.com.*\/.*\/.*|dailybooth\.com\/.*\/.*|brizzly\.com\/pic\/.*|pics\.brizzly\.com\/.*\.jpg|img\.ly\/.*|www\.tinypic\.com\/view\.php.*|tinypic\.com\/view\.php.*|www\.tinypic\.com\/player\.php.*|tinypic\.com\/player\.php.*|www\.tinypic\.com\/r\/.*\/.*|tinypic\.com\/r\/.*\/.*|.*\.tinypic\.com\/.*\.jpg|.*\.tinypic\.com\/.*\.png|meadd\.com\/.*\/.*|meadd\.com\/.*|.*\.deviantart\.com\/art\/.*|.*\.deviantart\.com\/gallery\/.*|.*\.deviantart\.com\/#\/.*|fav\.me\/.*|.*\.deviantart\.com|.*\.deviantart\.com\/gallery|.*\.deviantart\.com\/.*\/.*\.jpg|.*\.deviantart\.com\/.*\/.*\.gif|.*\.deviantart\.net\/.*\/.*\.jpg|.*\.deviantart\.net\/.*\/.*\.gif|plixi\.com\/p\/.*|plixi\.com\/profile\/home\/.*|plixi\.com\/.*|www\.fotopedia\.com\/.*\/.*|fotopedia\.com\/.*\/.*|photozou\.jp\/photo\/show\/.*\/.*|photozou\.jp\/photo\/photo_only\/.*\/.*|instagr\.am\/p\/.*|skitch\.com\/.*\/.*\/.*|img\.skitch\.com\/.*|https:\/\/skitch\.com\/.*\/.*\/.*|https:\/\/img\.skitch\.com\/.*|share\.ovi\.com\/media\/.*\/.*|www\.questionablecontent\.net\/|questionablecontent\.net\/|www\.questionablecontent\.net\/view\.php.*|questionablecontent\.net\/view\.php.*|questionablecontent\.net\/comics\/.*\.png|www\.questionablecontent\.net\/comics\/.*\.png|picplz\.com\/user\/.*\/pic\/.*\/|twitrpix\.com\/.*|.*\.twitrpix\.com\/.*|www\.someecards\.com\/.*\/.*|someecards\.com\/.*\/.*|some\.ly\/.*|www\.some\.ly\/.*|pikchur\.com\/.*|achewood\.com\/.*|www\.achewood\.com\/.*|achewood\.com\/index\.php.*|www\.achewood\.com\/index\.php.*|www\.whitehouse\.gov\/photos-and-video\/video\/.*|www\.whitehouse\.gov\/video\/.*|wh\.gov\/photos-and-video\/video\/.*|wh\.gov\/video\/.*|www\.hulu\.com\/watch.*|www\.hulu\.com\/w\/.*|hulu\.com\/watch.*|hulu\.com\/w\/.*|.*crackle\.com\/c\/.*|www\.fancast\.com\/.*\/videos|www\.funnyordie\.com\/videos\/.*|www\.funnyordie\.com\/m\/.*|funnyordie\.com\/videos\/.*|funnyordie\.com\/m\/.*|www\.vimeo\.com\/groups\/.*\/videos\/.*|www\.vimeo\.com\/.*|vimeo\.com\/groups\/.*\/videos\/.*|vimeo\.com\/.*|vimeo\.com\/m\/#\/.*|www\.ted\.com\/talks\/.*\.html.*|www\.ted\.com\/talks\/lang\/.*\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/lang\/.*\/.*\.html.*|.*nfb\.ca\/film\/.*|www\.thedailyshow\.com\/watch\/.*|www\.thedailyshow\.com\/full-episodes\/.*|www\.thedailyshow\.com\/collection\/.*\/.*\/.*|movies\.yahoo\.com\/movie\/.*\/video\/.*|movies\.yahoo\.com\/movie\/.*\/trailer|movies\.yahoo\.com\/movie\/.*\/video|www\.colbertnation\.com\/the-colbert-report-collections\/.*|www\.colbertnation\.com\/full-episodes\/.*|www\.colbertnation\.com\/the-colbert-report-videos\/.*|www\.comedycentral\.com\/videos\/index\.jhtml\?.*|www\.theonion\.com\/video\/.*|theonion\.com\/video\/.*|wordpress\.tv\/.*\/.*\/.*\/.*\/|www\.traileraddict\.com\/trailer\/.*|www\.traileraddict\.com\/clip\/.*|www\.traileraddict\.com\/poster\/.*|www\.escapistmagazine\.com\/videos\/.*|www\.trailerspy\.com\/trailer\/.*\/.*|www\.trailerspy\.com\/trailer\/.*|www\.trailerspy\.com\/view_video\.php.*|www\.atom\.com\/.*\/.*\/|fora\.tv\/.*\/.*\/.*\/.*|www\.spike\.com\/video\/.*|www\.gametrailers\.com\/video\/.*|gametrailers\.com\/video\/.*|www\.koldcast\.tv\/video\/.*|www\.koldcast\.tv\/#video:.*|techcrunch\.tv\/watch.*|techcrunch\.tv\/.*\/watch.*|mixergy\.com\/.*|video\.pbs\.org\/video\/.*|www\.zapiks\.com\/.*|tv\.digg\.com\/diggnation\/.*|tv\.digg\.com\/diggreel\/.*|tv\.digg\.com\/diggdialogg\/.*|www\.trutv\.com\/video\/.*|www\.nzonscreen\.com\/title\/.*|nzonscreen\.com\/title\/.*|app\.wistia\.com\/embed\/medias\/.*|https:\/\/app\.wistia\.com\/embed\/medias\/.*|hungrynation\.tv\/.*\/episode\/.*|www\.hungrynation\.tv\/.*\/episode\/.*|hungrynation\.tv\/episode\/.*|www\.hungrynation\.tv\/episode\/.*|indymogul\.com\/.*\/episode\/.*|www\.indymogul\.com\/.*\/episode\/.*|indymogul\.com\/episode\/.*|www\.indymogul\.com\/episode\/.*|channelfrederator\.com\/.*\/episode\/.*|www\.channelfrederator\.com\/.*\/episode\/.*|channelfrederator\.com\/episode\/.*|www\.channelfrederator\.com\/episode\/.*|tmiweekly\.com\/.*\/episode\/.*|www\.tmiweekly\.com\/.*\/episode\/.*|tmiweekly\.com\/episode\/.*|www\.tmiweekly\.com\/episode\/.*|99dollarmusicvideos\.com\/.*\/episode\/.*|www\.99dollarmusicvideos\.com\/.*\/episode\/.*|99dollarmusicvideos\.com\/episode\/.*|www\.99dollarmusicvideos\.com\/episode\/.*|ultrakawaii\.com\/.*\/episode\/.*|www\.ultrakawaii\.com\/.*\/episode\/.*|ultrakawaii\.com\/episode\/.*|www\.ultrakawaii\.com\/episode\/.*|barelypolitical\.com\/.*\/episode\/.*|www\.barelypolitical\.com\/.*\/episode\/.*|barelypolitical\.com\/episode\/.*|www\.barelypolitical\.com\/episode\/.*|barelydigital\.com\/.*\/episode\/.*|www\.barelydigital\.com\/.*\/episode\/.*|barelydigital\.com\/episode\/.*|www\.barelydigital\.com\/episode\/.*|threadbanger\.com\/.*\/episode\/.*|www\.threadbanger\.com\/.*\/episode\/.*|threadbanger\.com\/episode\/.*|www\.threadbanger\.com\/episode\/.*|vodcars\.com\/.*\/episode\/.*|www\.vodcars\.com\/.*\/episode\/.*|vodcars\.com\/episode\/.*|www\.vodcars\.com\/episode\/.*|confreaks\.net\/videos\/.*|www\.confreaks\.net\/videos\/.*|video\.allthingsd\.com\/video\/.*|aniboom\.com\/animation-video\/.*|www\.aniboom\.com\/animation-video\/.*|clipshack\.com\/Clip\.aspx\?.*|www\.clipshack\.com\/Clip\.aspx\?.*|grindtv\.com\/.*\/video\/.*|www\.grindtv\.com\/.*\/video\/.*|ifood\.tv\/recipe\/.*|ifood\.tv\/video\/.*|ifood\.tv\/channel\/user\/.*|www\.ifood\.tv\/recipe\/.*|www\.ifood\.tv\/video\/.*|www\.ifood\.tv\/channel\/user\/.*|logotv\.com\/video\/.*|www\.logotv\.com\/video\/.*|lonelyplanet\.com\/Clip\.aspx\?.*|www\.lonelyplanet\.com\/Clip\.aspx\?.*|streetfire\.net\/video\/.*\.htm.*|www\.streetfire\.net\/video\/.*\.htm.*|trooptube\.tv\/videos\/.*|www\.trooptube\.tv\/videos\/.*|www\.godtube\.com\/featured\/video\/.*|godtube\.com\/featured\/video\/.*|www\.godtube\.com\/watch\/.*|godtube\.com\/watch\/.*|www\.tangle\.com\/view_video.*|mediamatters\.org\/mmtv\/.*|www\.clikthrough\.com\/theater\/video\/.*|soundcloud\.com\/.*|soundcloud\.com\/.*\/.*|soundcloud\.com\/.*\/sets\/.*|soundcloud\.com\/groups\/.*|snd\.sc\/.*|www\.last\.fm\/music\/.*|www\.last\.fm\/music\/+videos\/.*|www\.last\.fm\/music\/+images\/.*|www\.last\.fm\/music\/.*\/_\/.*|www\.last\.fm\/music\/.*\/.*|www\.mixcloud\.com\/.*\/.*\/|www\.radionomy\.com\/.*\/radio\/.*|radionomy\.com\/.*\/radio\/.*|www\.entertonement\.com\/clips\/.*|www\.rdio\.com\/#\/artist\/.*\/album\/.*|www\.rdio\.com\/artist\/.*\/album\/.*|www\.zero-inch\.com\/.*|.*\.bandcamp\.com\/|.*\.bandcamp\.com\/track\/.*|.*\.bandcamp\.com\/album\/.*|freemusicarchive\.org\/music\/.*|www\.freemusicarchive\.org\/music\/.*|freemusicarchive\.org\/curator\/.*|www\.freemusicarchive\.org\/curator\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/templates\/story\/story\.php.*|huffduffer\.com\/.*\/.*|www\.audioboo\.fm\/boos\/.*|audioboo\.fm\/boos\/.*|boo\.fm\/b.*|www\.xiami\.com\/song\/.*|xiami\.com\/song\/.*|www\.saynow\.com\/playMsg\.html.*|www\.saynow\.com\/playMsg\.html.*|listen\.grooveshark\.com\/s\/.*|radioreddit\.com\/songs.*|www\.radioreddit\.com\/songs.*|radioreddit\.com\/\?q=songs.*|www\.radioreddit\.com\/\?q=songs.*|espn\.go\.com\/video\/clip.*|espn\.go\.com\/.*\/story.*|abcnews\.com\/.*\/video\/.*|abcnews\.com\/video\/playerIndex.*|washingtonpost\.com\/wp-dyn\/.*\/video\/.*\/.*\/.*\/.*|www\.washingtonpost\.com\/wp-dyn\/.*\/video\/.*\/.*\/.*\/.*|www\.boston\.com\/video.*|boston\.com\/video.*|www\.facebook\.com\/photo\.php.*|www\.facebook\.com\/video\/video\.php.*|www\.facebook\.com\/v\/.*|cnbc\.com\/id\/.*\?.*video.*|www\.cnbc\.com\/id\/.*\?.*video.*|cnbc\.com\/id\/.*\/play\/1\/video\/.*|www\.cnbc\.com\/id\/.*\/play\/1\/video\/.*|cbsnews\.com\/video\/watch\/.*|www\.google\.com\/buzz\/.*\/.*\/.*|www\.google\.com\/buzz\/.*|www\.google\.com\/profiles\/.*|google\.com\/buzz\/.*\/.*\/.*|google\.com\/buzz\/.*|google\.com\/profiles\/.*|www\.cnn\.com\/video\/.*|edition\.cnn\.com\/video\/.*|money\.cnn\.com\/video\/.*|today\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/ns\/.*|today\.msnbc\.msn\.com\/id\/.*\/ns\/.*|multimedia\.foxsports\.com\/m\/video\/.*\/.*|msn\.foxsports\.com\/video.*|www\.globalpost\.com\/video\/.*|www\.globalpost\.com\/dispatch\/.*|guardian\.co\.uk\/.*\/video\/.*\/.*\/.*\/.*|www\.guardian\.co\.uk\/.*\/video\/.*\/.*\/.*\/.*|bravotv\.com\/.*\/.*\/videos\/.*|www\.bravotv\.com\/.*\/.*\/videos\/.*|video\.nationalgeographic\.com\/.*\/.*\/.*\.html|dsc\.discovery\.com\/videos\/.*|animal\.discovery\.com\/videos\/.*|health\.discovery\.com\/videos\/.*|investigation\.discovery\.com\/videos\/.*|military\.discovery\.com\/videos\/.*|planetgreen\.discovery\.com\/videos\/.*|science\.discovery\.com\/videos\/.*|tlc\.discovery\.com\/videos\/.*|.*amazon\..*\/gp\/product\/.*|.*amazon\..*\/.*\/dp\/.*|.*amazon\..*\/dp\/.*|.*amazon\..*\/o\/ASIN\/.*|.*amazon\..*\/gp\/offer-listing\/.*|.*amazon\..*\/.*\/ASIN\/.*|.*amazon\..*\/gp\/product\/images\/.*|.*amazon\..*\/gp\/aw\/d\/.*|www\.amzn\.com\/.*|amzn\.com\/.*|www\.shopstyle\.com\/browse.*|www\.shopstyle\.com\/action\/apiVisitRetailer.*|api\.shopstyle\.com\/action\/apiVisitRetailer.*|www\.shopstyle\.com\/action\/viewLook.*|gist\.github\.com\/.*|twitter\.com\/.*\/status\/.*|twitter\.com\/.*\/statuses\/.*|www\.twitter\.com\/.*\/status\/.*|www\.twitter\.com\/.*\/statuses\/.*|mobile\.twitter\.com\/.*\/status\/.*|mobile\.twitter\.com\/.*\/statuses\/.*|https:\/\/twitter\.com\/.*\/status\/.*|https:\/\/twitter\.com\/.*\/statuses\/.*|https:\/\/www\.twitter\.com\/.*\/status\/.*|https:\/\/www\.twitter\.com\/.*\/statuses\/.*|https:\/\/mobile\.twitter\.com\/.*\/status\/.*|https:\/\/mobile\.twitter\.com\/.*\/statuses\/.*|www\.crunchbase\.com\/.*\/.*|crunchbase\.com\/.*\/.*|www\.slideshare\.net\/.*\/.*|www\.slideshare\.net\/mobile\/.*\/.*|slidesha\.re\/.*|.*\.scribd\.com\/doc\/.*|screenr\.com\/.*|polldaddy\.com\/community\/poll\/.*|polldaddy\.com\/poll\/.*|answers\.polldaddy\.com\/poll\/.*|www\.5min\.com\/Video\/.*|www\.howcast\.com\/videos\/.*|www\.screencast\.com\/.*\/media\/.*|screencast\.com\/.*\/media\/.*|www\.screencast\.com\/t\/.*|screencast\.com\/t\/.*|issuu\.com\/.*\/docs\/.*|www\.kickstarter\.com\/projects\/.*\/.*|www\.scrapblog\.com\/viewer\/viewer\.aspx.*|ping\.fm\/p\/.*|chart\.ly\/symbols\/.*|chart\.ly\/.*|maps\.google\.com\/maps\?.*|maps\.google\.com\/\?.*|maps\.google\.com\/maps\/ms\?.*|.*\.craigslist\.org\/.*\/.*|my\.opera\.com\/.*\/albums\/show\.dml\?id=.*|my\.opera\.com\/.*\/albums\/showpic\.dml\?album=.*&picture=.*|tumblr\.com\/.*|.*\.tumblr\.com\/post\/.*|www\.polleverywhere\.com\/polls\/.*|www\.polleverywhere\.com\/multiple_choice_polls\/.*|www\.polleverywhere\.com\/free_text_polls\/.*|www\.quantcast\.com\/wd:.*|www\.quantcast\.com\/.*|siteanalytics\.compete\.com\/.*|statsheet\.com\/statplot\/charts\/.*\/.*\/.*\/.*|statsheet\.com\/statplot\/charts\/e\/.*|statsheet\.com\/.*\/teams\/.*\/.*|statsheet\.com\/tools\/chartlets\?chart=.*|.*\.status\.net\/notice\/.*|identi\.ca\/notice\/.*|brainbird\.net\/notice\/.*|shitmydadsays\.com\/notice\/.*|www\.studivz\.net\/Profile\/.*|www\.studivz\.net\/l\/.*|www\.studivz\.net\/Groups\/Overview\/.*|www\.studivz\.net\/Gadgets\/Info\/.*|www\.studivz\.net\/Gadgets\/Install\/.*|www\.studivz\.net\/.*|www\.meinvz\.net\/Profile\/.*|www\.meinvz\.net\/l\/.*|www\.meinvz\.net\/Groups\/Overview\/.*|www\.meinvz\.net\/Gadgets\/Info\/.*|www\.meinvz\.net\/Gadgets\/Install\/.*|www\.meinvz\.net\/.*|www\.schuelervz\.net\/Profile\/.*|www\.schuelervz\.net\/l\/.*|www\.schuelervz\.net\/Groups\/Overview\/.*|www\.schuelervz\.net\/Gadgets\/Info\/.*|www\.schuelervz\.net\/Gadgets\/Install\/.*|www\.schuelervz\.net\/.*|myloc\.me\/.*|pastebin\.com\/.*|pastie\.org\/.*|www\.pastie\.org\/.*|redux\.com\/stream\/item\/.*\/.*|redux\.com\/f\/.*\/.*|www\.redux\.com\/stream\/item\/.*\/.*|www\.redux\.com\/f\/.*\/.*|cl\.ly\/.*|cl\.ly\/.*\/content|speakerdeck\.com\/u\/.*\/p\/.*|www\.kiva\.org\/lend\/.*|www\.timetoast\.com\/timelines\/.*|storify\.com\/.*\/.*|.*meetup\.com\/.*|meetu\.ps\/.*|www\.dailymile\.com\/people\/.*\/entries\/.*|.*\.kinomap\.com\/.*|www\.metacdn\.com\/api\/users\/.*\/content\/.*|www\.metacdn\.com\/api\/users\/.*\/media\/.*|prezi\.com\/.*\/.*|.*\.uservoice\.com\/.*\/suggestions\/.*|formspring\.me\/.*|www\.formspring\.me\/.*|formspring\.me\/.*\/q\/.*|www\.formspring\.me\/.*\/q\/.*|twitlonger\.com\/show\/.*|www\.twitlonger\.com\/show\/.*|tl\.gd\/.*|www\.qwiki\.com\/q\/.*|crocodoc\.com\/.*|.*\.crocodoc\.com\/.*|https:\/\/crocodoc\.com\/.*|https:\/\/.*\.crocodoc\.com\/.*|4sq\.com\/.*|.*\.4sq\.com\/.*)/i";

	//Tokenise the string (on whitespace) and search through it
	$tok = strtok($tmp, " \n\t");
	while ($tok !== false) 
	{
		if (preg_match_all($embedly_re, $tok, $matches, PREG_PATTERN_ORDER) > 0)
		{
			foreach ($matches[1] as $key => $match)
			{
				//Should use &maxwidth, but hard to know width of device - so using tinysrc to resize to 50%
				$url = "http://api.embed.ly/1/oembed?url=" . $match . "&format=json";
				
				$embedly_json = twitter_fetch($url);
				$embedly_data = json_decode($embedly_json);
				$thumb = $embedly_data->thumbnail_url;
				
				//We can use the height and width for better HTML, but some thumbnails are very large. Using tinysrc for now.
				$height = $embedly_data->thumbnail_height;
				$width = $embedly_data->thumbnail_width;
				
				if ($thumb) //Not all services have thumbnails
				{
					$images[] = theme('external_link', "http://$match", "<img src='http://i.tinysrc.mobi/x50/$thumb' />");
				}
			}
		}
		$tok = strtok(" \n\t");
	}
	
	if (empty($images)) return $text;
	return implode('<br />', $images).'<br />'.$text;
}

function format_interval($timestamp, $granularity = 2) {
	$units = array(
	'year' => 31536000,
	'day'  => 86400,
	'hour' => 3600,
	'min'  => 60,
	'sec'  => 1
	);
	$output = '';
	foreach ($units as $key => $value) {
		if ($timestamp >= $value) {
			$output .= ($output ? ' ' : ''). pluralise($key, floor($timestamp / $value), true);
			$timestamp %= $value;
			$granularity--;
		}
		if ($granularity == 0) {
			break;
		}
	}
	return $output ? $output : '0 sec';
}

function twitter_status_page($query) {
	$id = (string) $query[1];
	if (is_numeric($id)) {
		$request = API_URL."statuses/show/{$id}.json?include_entities=true";
		$status = twitter_process($request);
		$content = theme('status', $status);
		if (!$status->user->protected) {
			$thread = twitter_thread_timeline($id);
		}
		if ($thread) {
			$content .= '<p>And the experimental conversation view...</p>'.theme('timeline', $thread);
			$content .= "<p>Don't like the thread order? Go to <a href='settings'>settings</a> to reverse it. Either way - the dates/times are not always accurate.</p>";
		}
		theme('page', "Status $id", $content);
	}
}

function twitter_thread_timeline($thread_id) {
	$request = "http://search.twitter.com/search/thread/{$thread_id}";
	$tl = twitter_standard_timeline(twitter_fetch($request), 'thread');
	return $tl;
}

function twitter_retweet_page($query) {
	$id = (string) $query[1];
	if (is_numeric($id)) {
		$request = API_URL."statuses/show/{$id}.json?include_entities=true";
		$tl = twitter_process($request);
		$content = theme('retweet', $tl);
		theme('page', 'Retweet', $content);
	}
}

function twitter_refresh($page = NULL) {
	if (isset($page)) {
		$page = BASE_URL . $page;
	} else {
		$page = $_SERVER['HTTP_REFERER'];
	}
	header('Location: '. $page);
	exit();
}

function twitter_delete_page($query) {
	twitter_ensure_post_action();

	$id = (string) $query[1];
	if (is_numeric($id)) {
		$request = API_URL."statuses/destroy/{$id}.json?page=".intval($_GET['page']);
		$tl = twitter_process($request, true);
		twitter_refresh('user/'.user_current_username());
	}
}

function twitter_ensure_post_action() {
	// This function is used to make sure the user submitted their action as an HTTP POST request
	// It slightly increases security for actions such as Delete, Block and Spam
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		die('Error: Invalid HTTP request method for this action.');
	}
}

function twitter_follow_page($query) {
	$user = $query[1];
	if ($user) {
		if($query[0] == 'follow'){
			$request = API_URL."friendships/create/{$user}.json";
		} else {
			$request = API_URL."friendships/destroy/{$user}.json";
		}
		twitter_process($request, true);
		twitter_refresh('friends');
	}
}

function twitter_block_page($query) {
	twitter_ensure_post_action();
	$user = $query[1];
	if ($user) {
		if($query[0] == 'block'){
			$request = API_URL."blocks/create/create.json?screen_name={$user}";
		} else {
			$request = API_URL."blocks/destroy/destroy.json?screen_name={$user}";
		}
		twitter_process($request, true);
		twitter_refresh("user/{$user}");
	}
}

function twitter_spam_page($query)
{
	//http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-report_spam
	//We need to post this data
	twitter_ensure_post_action();
	$user = $query[1];

	//The data we need to post
	$post_data = array("screen_name" => $user);

	$request = API_URL."report_spam.json";
	twitter_process($request, $post_data);

	//Where should we return the user to?  Back to the user
	twitter_refresh("user/{$user}");
}


function twitter_confirmation_page($query)
{
	// the URL /confirm can be passed parameters like so /confirm/param1/param2/param3 etc.
	$action = $query[1];
	$target = $query[2];	//The name of the user we are doing this action on
	$target_id = $query[3];	//The targets's ID.  Needed to check if they are being blocked.

	switch ($action) {
		case 'block':
			if (twitter_block_exists($target_id)) //Is the target blocked by the user?
			{
				$action = 'unblock';
				$content  = "<p>Are you really sure you want to <strong>Unblock $target</strong>?</p>";
				$content .= '<ul><li>They will see your updates on their home page if they follow you again.</li><li>You <em>can</em> block them again if you want.</li></ul>';
			}
			else
			{
				$content = "<p>Are you really sure you want to <strong>$action $target</strong>?</p>";
				$content .= "<ul><li>You won't show up in their list of friends</li><li>They won't see your updates on their home page</li><li>They won't be able to follow you</li><li>You <em>can</em> unblock them but you will need to follow them again afterwards</li></ul>";
			}
			break;

		case 'delete':
			$content = '<p>Are you really sure you want to delete your tweet?</p>';
			$content .= "<ul><li>Tweet ID: <strong>$target</strong></li><li>There is no way to undo this action.</li></ul>";
			break;

		case 'spam':
			$content  = "<p>Are you really sure you want to report <strong>$target</strong> as a spammer?</p>";
			$content .= "<p>They will also be blocked from following you.</p>";
			break;

	}
	$content .= "<form action='$action/$target' method='post'>
						<input type='submit' value='Yes please' />
					</form>";
	theme('Page', 'Confirm', $content);
}

function twitter_friends_page($query) {
	$user = $query[1];
	if (!$user) {
		user_ensure_authenticated();
		$user = user_current_username();
	}
	$request = API_URL."statuses/friends/{$user}.xml";
	$tl = lists_paginated_process($request);
	$content = theme('followers', $tl);
	theme('page', 'Friends', $content);
}

function twitter_followers_page($query) {
	$user = $query[1];
	if (!$user) {
		user_ensure_authenticated();
		$user = user_current_username();
	}
	$request = API_URL."statuses/followers/{$user}.xml";
	$tl = lists_paginated_process($request);
	$content = theme('followers', $tl);
	theme('page', 'Followers', $content);
}

function twitter_update() {
	twitter_ensure_post_action();
	$status = twitter_url_shorten(stripslashes(trim($_POST['status'])));
	if ($status) {
		$request = API_URL.'statuses/update.json';
		$post_data = array('source' => 'dabr', 'status' => $status);
		$in_reply_to_id = (string) $_POST['in_reply_to_id'];
		if (is_numeric($in_reply_to_id)) {
			$post_data['in_reply_to_status_id'] = $in_reply_to_id;
		}
		// Geolocation parameters
		list($lat, $long) = explode(',', $_POST['location']);
		$geo = 'N';
		if (is_numeric($lat) && is_numeric($long)) {
			$geo = 'Y';
			$post_data['lat'] = $lat;
			$post_data['long'] = $long;
	  // $post_data['display_coordinates'] = 'false';
		}
		setcookie_year('geo', $geo);
		$b = twitter_process($request, $post_data);
	}
	twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_retweet($query) {
	twitter_ensure_post_action();
	$id = $query[1];
	if (is_numeric($id)) {
		$request = API_URL.'statuses/retweet/'.$id.'.xml';
		twitter_process($request, true);
	}
	twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_replies_page() {
	$request = API_URL.'statuses/mentions.json?page='.intval($_GET['page']).'&include_entities=true';
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl, 'replies');
	$content = theme('status_form');
	$content .= theme('timeline', $tl);
	theme('page', 'Replies', $content);
}

function twitter_retweets_page() {
	$request = API_URL.'statuses/retweets_of_me.json?page='.intval($_GET['page']).'&include_entities=true';
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl, 'retweets');
	$content = theme('status_form');
	$content .= theme('timeline',$tl);
	theme('page', 'Retweets', $content);
}

function twitter_directs_page($query) {
	$action = strtolower(trim($query[1]));
	switch ($action) {
		case 'delete':
			$id = $query[2];
			if (!is_numeric($id)) return;
			$request = API_URL."direct_messages/destroy/$id.json";
			twitter_process($request, true);
			twitter_refresh();

		case 'create':
			$to = $query[2];
			$content = theme('directs_form', $to);
			theme('page', 'Create DM', $content);

		case 'send':
			twitter_ensure_post_action();
			$to = trim(stripslashes($_POST['to']));
			$message = trim(stripslashes($_POST['message']));
			$request = API_URL.'direct_messages/new.json';
			twitter_process($request, array('user' => $to, 'text' => $message));
			twitter_refresh('directs/sent');

		case 'sent':
			$request = API_URL.'direct_messages/sent.json?page='.intval($_GET['page']);
			$tl = twitter_standard_timeline(twitter_process($request), 'directs_sent');
			$content = theme_directs_menu();
			$content .= theme('timeline', $tl);
			theme('page', 'DM Sent', $content);

		case 'inbox':
		default:
			$request = API_URL.'direct_messages.json?page='.intval($_GET['page']).'&include_entities=true';
			$tl = twitter_standard_timeline(twitter_process($request), 'directs_inbox');
			$content = theme_directs_menu();
			$content .= theme('timeline', $tl);
			theme('page', 'DM Inbox', $content);
	}
}

function theme_directs_menu() {
	return '<p><a href="directs/create">Create</a> | <a href="directs/inbox">Inbox</a> | <a href="directs/sent">Sent</a></p>';
}

function theme_directs_form($to) {
	if ($to) {

		if (friendship_exists($to) != 1)
		{
			$html_to = "<em>Warning</em> <b>" . $to . "</b> is not following you. You cannot send them a Direct Message :-(<br/>";
		}
		$html_to .= "Sending direct message to <b>$to</b><input name='to' value='$to' type='hidden'>";
	} else {
		$html_to .= "To: <input name='to'><br />Message:";
	}
	$content = "<form action='directs/send' method='post'>$html_to<br><textarea name='message' style='width:90%; max-width: 400px;' rows='3' id='message'></textarea><br><input type='submit' value='Send'><span id='remaining'>140</span></form>";
	$content .= js_counter("message");
	return $content;
}

function twitter_search_page() {
	$search_query = $_GET['query'];
	$content = theme('search_form', $search_query);
	if (isset($_POST['query'])) {
		$duration = time() + (3600 * 24 * 365);
		setcookie('search_favourite', $_POST['query'], $duration, '/');
		twitter_refresh('search');
	}
	if (!isset($search_query) && array_key_exists('search_favourite', $_COOKIE)) {
		$search_query = $_COOKIE['search_favourite'];
	}
	if ($search_query) {
		$tl = twitter_search($search_query);
		if ($search_query !== $_COOKIE['search_favourite']) {
			$content .= '<form action="search/bookmark" method="post"><input type="hidden" name="query" value="'.$search_query.'" /><input type="submit" value="Save as default search" /></form>';
		}
		$content .= theme('timeline', $tl);
	}
	theme('page', 'Search', $content);
}

function twitter_search($search_query) {
	$page = (int) $_GET['page'];
	if ($page == 0) $page = 1;
	$request = 'http://search.twitter.com/search.json?result_type=recent&q=' . urlencode($search_query).'&page='.$page.'&include_entities=true';
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl->results, 'search');
	return $tl;
}

function twitter_find_tweet_in_timeline($tweet_id, $tl) {
	// Parameter checks
	if (!is_numeric($tweet_id) || !$tl) return;

	// Check if the tweet exists in the timeline given
	if (array_key_exists($tweet_id, $tl)) {
		// Found the tweet
		$tweet = $tl[$tweet_id];
	} else {
		// Not found, fetch it specifically from the API
		$request = API_URL."statuses/show/{$tweet_id}.json?include_entities=true";
		$tweet = twitter_process($request);
	}
	return $tweet;
}

function twitter_user_page($query)
{
	$screen_name = $query[1];
	$subaction = $query[2];
	$in_reply_to_id = (string) $query[3];
	$content = '';

	if (!$screen_name) theme('error', 'No username given');

	// Load up user profile information and one tweet
	$user = twitter_user_info($screen_name);

	// If the user has at least one tweet
	if (isset($user->status)) {
		// Fetch the timeline early, so we can try find the tweet they're replying to
		$request = API_URL."statuses/user_timeline.json?screen_name={$screen_name}&include_rts=true&include_entities=true&page=".intval($_GET['page']);
		$tl = twitter_process($request);
		$tl = twitter_standard_timeline($tl, 'user');
	}

	// Build an array of people we're talking to
	$to_users = array($user->screen_name);

	// Are we replying to anyone?
	if (is_numeric($in_reply_to_id)) {
		$tweet = twitter_find_tweet_in_timeline($in_reply_to_id, $tl);
		$content .= "<p>In reply to:<br />{$tweet->text}</p>";

		if ($subaction == 'replyall') {
			$found = Twitter_Extractor::extractMentionedScreennames($tweet->text);
			$to_users = array_unique(array_merge($to_users, $found));
		}
	}

	// Build a status message to everyone we're talking to
	$status = '';
	foreach ($to_users as $username) {
		if (!user_is_current_user($username)) {
			$status .= "@{$username} ";
		}
	}

	$content .= theme('status_form', $status, $in_reply_to_id);
	$content .= theme('user_header', $user);
	$content .= theme('timeline', $tl);

	theme('page', "User {$screen_name}", $content);
}

function twitter_favourites_page($query) {
	$screen_name = $query[1];
	if (!$screen_name) {
		user_ensure_authenticated();
		$screen_name = user_current_username();
	}
	$request = API_URL."favorites/{$screen_name}.json?page=".intval($_GET['page']).'&include_entities=true';
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl, 'favourites');
	$content = theme('status_form');
	$content .= theme('timeline', $tl);
	theme('page', 'Favourites', $content);
}

function twitter_mark_favourite_page($query) {
	$id = (string) $query[1];
	if (!is_numeric($id)) return;
	if ($query[0] == 'unfavourite') {
		$request = API_URL."favorites/destroy/$id.json";
	} else {
		$request = API_URL."favorites/create/$id.json";
	}
	twitter_process($request, true);
	twitter_refresh();
}

function twitter_home_page() {
	user_ensure_authenticated();
	//$request = API_URL.'statuses/home_timeline.json?count=20&include_rts=true&page='.intval($_GET['page']);
	$request = API_URL.'statuses/home_timeline.json?count=20&include_rts=true&include_entities=true';

	if ($_GET['max_id'])
	{
		$request .= '&max_id='.$_GET['max_id'];
	}

	if ($_GET['since_id'])
	{
		$request .= '&since_id='.$_GET['since_id'];
	}
	//echo $request;
	$tl = twitter_process($request);
	$tl = twitter_standard_timeline($tl, 'friends');
	$content = theme('status_form');
	$content .= theme('timeline', $tl);
	theme('page', 'Home', $content);
}

function twitter_hashtag_page($query) {
	if (isset($query[1])) {
		$hashtag = '#'.$query[1];
		$content = theme('status_form', $hashtag.' ');
		$tl = twitter_search($hashtag);
		$content .= theme('timeline', $tl);
		theme('page', $hashtag, $content);
	} else {
		theme('page', 'Hashtag', 'Hash hash!');
	}
}

function theme_status_form($text = '', $in_reply_to_id = NULL) {
	if (user_is_authenticated()) {
		return "<form method='post' action='update'><input name='status' value='{$text}' maxlength='140' /> <input name='in_reply_to_id' value='{$in_reply_to_id}' type='hidden' /><input type='submit' value='Update' /></form>";
	}
}

function theme_status($status) {
	//32bit int / snowflake patch
	if($status->id_str) $status->id = $status->id_str;

	$time_since = theme('status_time_link', $status);
	$parsed = twitter_parse_tags($status->text, $status->entities);
	$avatar = theme('avatar', $status->user->profile_image_url);

	$out = theme('status_form', "@{$status->user->screen_name} ");
	$out .= "<div class='timeline'>\n";
	$out .= " <div class='tweet odd'>\n";
	$out .= "  <span class='avatar'>$avatar</span>\n";
	$out .= "  <span class='status shift'><b><a href='user/{$status->user->screen_name}'>{$status->user->screen_name}</a></b> $time_since<br />$parsed</span>\n";
	$out .= " </div>\n";
	$out .= "</div>\n";
	if (user_is_current_user($status->user->screen_name)) {
		$out .= "<form action='delete/{$status->id_str}' method='post'><input type='submit' value='Delete without confirmation' /></form>";
	}
	return $out;
}

function theme_retweet($status)
{
	$text = "RT @{$status->user->screen_name}: {$status->text}";
	$length = function_exists('mb_strlen') ? mb_strlen($text,'UTF-8') : strlen($text);
	$from = substr($_SERVER['HTTP_REFERER'], strlen(BASE_URL));

	if($status->user->protected == 0)
	{
		$content.="<p>Twitter's new style retweet:</p>
					<form action='twitter-retweet/{$status->id_str}' method='post'>
						<input type='hidden' name='from' value='$from' />
						<input type='submit' value='Twitter Retweet' />
					</form>
					<hr />";
	}
	else
	{
		$content.="<p>@{$status->user->screen_name} doesn't allow you to retweet them. You will have to use the  use the old style editable retweet</p>";
	}

	$content .= "<p>Old style editable retweet:</p>
					<form action='update' method='post'>
						<input type='hidden' name='from' value='$from' />
						<textarea name='status' style='width:90%; max-width: 400px;' rows='3' id='status'>$text</textarea>
						<br/>
						<input type='submit' value='Retweet' />
						<span id='remaining'>" . (140 - $length) ."</span>
					</form>";
	$content .= js_counter("status");

	return $content;
}

function twitter_tweets_per_day($user, $rounding = 1) {
	// Helper function to calculate an average count of tweets per day
	$days_on_twitter = (time() - strtotime($user->created_at)) / 86400;
	return round($user->statuses_count / $days_on_twitter, $rounding);
}

function theme_user_header($user) {
	$following = friendship($user->screen_name);
	$followed_by = $following->relationship->target->followed_by; //The $user is followed by the authenticating
	$following = $following->relationship->target->following;
	$name = theme('full_name', $user);
	$full_avatar = str_replace('_normal.', '.', $user->profile_image_url);
	$link = theme('external_link', $user->url);
	//Some locations have a prefix which should be removed (UbertTwitter and iPhone)
	//Sorry if my PC has converted from UTF-8 with the U (artesea)
	$cleanLocation = str_replace(array("iPhone: ","ÜT: "),"",$user->location);
	$raw_date_joined = strtotime($user->created_at);
	$date_joined = date('jS M Y', $raw_date_joined);
	$tweets_per_day = twitter_tweets_per_day($user, 1);
	$bio = twitter_parse_tags($user->description);
	$out = "<div class='profile'>";
    $out .= "<span class='avatar'>".theme('external_link', $full_avatar, theme('avatar', $user->profile_image_url))."</span>";
	$out .= "<span class='status shift'><b>{$name}</b><br />";
	$out .= "<span class='about'>";
	if ($user->verified == true) {
		$out .= '<strong>Verified Account</strong><br />';
	}
	if ($user->protected == true) {
		$out .= '<strong>Private/Protected Tweets</strong><br />';
	}

	$out .= "Bio: {$bio}<br />";
	$out .= "Link: {$link}<br />";
	$out .= "Location: <a href=\"http://maps.google.com/m?q={$cleanLocation}\" target=\"_blank\">{$user->location}</a><br />";
	$out .= "Joined: {$date_joined} (~" . pluralise('tweet', $tweets_per_day, true) . " per day)";
	$out .= "</span></span>";
	$out .= "<div class='features'>";
	$out .= pluralise('tweet', $user->statuses_count, true);

	//If the authenticated user is not following the protected used, the API will return a 401 error when trying to view friends, followers and favourites
	//This is not the case on the Twitter website
	//To avoid the user being logged out, check to see if she is following the protected user. If not, don't create links to friends, followers and favourites
	if ($user->protected == true && $followed_by == false) {
		$out .= " | " . pluralise('follower', $user->followers_count, true);
		$out .= " | " . pluralise('friend', $user->friends_count, true);
		$out .= " | " . pluralise('favourite', $user->favourites_count, true);
	}
	else {
		$out .= " | <a href='followers/{$user->screen_name}'>" . pluralise('follower', $user->followers_count, true) . "</a>";
		$out .= " | <a href='friends/{$user->screen_name}'>" . pluralise('friend', $user->friends_count, true) . "</a>";
		$out .= " | <a href='favourites/{$user->screen_name}'>" . pluralise('favourite', $user->favourites_count, true) . "</a>";
	}

	$out .= " | <a href='lists/{$user->screen_name}'>" . pluralise('list', $user->listed_count, true) . "</a>";
	$out .=	" | <a href='directs/create/{$user->screen_name}'>Direct Message</a>";
	//NB we can tell if the user can be sent a DM $following->relationship->target->following;
	//Would removing this link confuse users?

	//Deprecated http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-users%C2%A0show
	//if ($user->following !== true)
	if ($followed_by == false) {
		$out .= " | <a href='follow/{$user->screen_name}'>Follow</a>";
	}
	else {
		$out .= " | <a href='unfollow/{$user->screen_name}'>Unfollow</a>";
	}
	
	//We need to pass the User Name and the User ID.  The Name is presented in the UI, the ID is used in checking
	$out.= " | <a href='confirm/block/{$user->screen_name}/{$user->id}'>(Un)Block</a>";
	/*
	//This should work, but it doesn't. Grrr.
	$blocked = $following->relationship->source->blocking; //The $user is blocked by the authenticating
	if ($blocked == true)
	{
		$out.= " | <a href='confirm/block/{$user->screen_name}/{$user->id}'>Unblock</a>";
	}
	else
	{
		$out.= " | <a href='confirm/block/{$user->screen_name}/{$user->id}'>Block</a>";
	}
	*/

	$out .= " | <a href='confirm/spam/{$user->screen_name}/{$user->id}'>Report Spam</a>";
	$out .= " | <a href='search?query=%40{$user->screen_name}'>Search @{$user->screen_name}</a>";
	$out .= "</div></div>";
	return $out;
}

function theme_avatar($url, $force_large = false) {
	$size = $force_large ? 48 : 24;
	return "<img src='$url' height='$size' width='$size' />";
}

function theme_status_time_link($status, $is_link = true) {
	$time = strtotime($status->created_at);
	if ($time > 0) {
		if (twitter_date('dmy') == twitter_date('dmy', $time) && !setting_fetch('timestamp')) {
			$out = format_interval(time() - $time, 1). ' ago';
		} else {
			$out = twitter_date('H:i', $time);
		}
	} else {
		$out = $status->created_at;
	}
	if ($is_link)
		$out = "<a href='status/{$status->id}' class='time'>$out</a>";
	return $out;
}

function twitter_date($format, $timestamp = null) {
/*
	static $offset;
	if (!isset($offset)) {
		if (user_is_authenticated()) {
			if (array_key_exists('utc_offset', $_COOKIE)) {
				$offset = $_COOKIE['utc_offset'];
			} else {
				$user = twitter_user_info();
				$offset = $user->utc_offset;
				setcookie('utc_offset', $offset, time() + 3000000, '/');
			}
		} else {
			$offset = 0;
		}
	}
*/
	$offset = setting_fetch('utc_offset', 0) * 3600;
	if (!isset($timestamp)) {
		$timestamp = time();
	}
	return gmdate($format, $timestamp + $offset);
}

function twitter_standard_timeline($feed, $source) {
	$output = array();
	if (!is_array($feed) && $source != 'thread') return $output;
	
	//32bit int / snowflake patch
	if (is_array($feed)) {
		foreach($feed as $key => $status) {
			if($status->id_str) {
				$feed[$key]->id = $status->id_str;
			}
			if($status->in_reply_to_status_id_str) {
				$feed[$key]->in_reply_to_status_id = $status->in_reply_to_status_id_str;
			}
			if($status->retweeted_status->id_str) {
				$feed[$key]->retweeted_status->id = $status->retweeted_status->id_str;
			}
		}
	}
	
	switch ($source) {
		case 'favourites':
		case 'friends':
		case 'replies':
		case 'retweets':
		case 'user':
			foreach ($feed as $status) {
				$new = $status;
				if ($new->retweeted_status) {
					$retweet = $new->retweeted_status;
					unset($new->retweeted_status);
					$retweet->retweeted_by = $new;
					$retweet->original_id = $new->id;
					$new = $retweet;
				}
				$new->from = $new->user;
				unset($new->user);
				$output[(string) $new->id] = $new;
			}
			return $output;

		case 'search':
			foreach ($feed as $status) {
				$output[(string) $status->id] = (object) array(
					'id' => $status->id,
					'text' => $status->text,
					'source' => strpos($status->source, '&lt;') !== false ? html_entity_decode($status->source) : $status->source,
					'from' => (object) array(
						'id' => $status->from_user_id,
						'screen_name' => $status->from_user,
						'profile_image_url' => $status->profile_image_url,
					),
					'to' => (object) array(
						'id' => $status->to_user_id,
						'screen_name' => $status->to_user,
					),
					'created_at' => $status->created_at,
					'geo' => $status->geo,
				);
			}
			return $output;

		case 'directs_sent':
		case 'directs_inbox':
			foreach ($feed as $status) {
				$new = $status;
				if ($source == 'directs_inbox') {
					$new->from = $new->sender;
					$new->to = $new->recipient;
				} else {
					$new->from = $new->recipient;
					$new->to = $new->sender;
				}
				unset($new->sender, $new->recipient);
				$new->is_direct = true;
				$output[] = $new;
			}
			return $output;

		case 'thread':
			// First pass: extract tweet info from the HTML
			$html_tweets = explode('</li>', $feed);
			foreach ($html_tweets as $tweet) {
				$id = preg_match_one('#msgtxt(\d*)#', $tweet);
				if (!$id) continue;
				$output[$id] = (object) array(
					'id' => $id,
					'text' => strip_tags(preg_match_one('#</a>: (.*)</span>#', $tweet)),
					'source' => preg_match_one('#>from (.*)</span>#', $tweet),
					'from' => (object) array(
						'id' => preg_match_one('#profile_images/(\d*)#', $tweet),
						'screen_name' => preg_match_one('#twitter.com/([^"]+)#', $tweet),
						'profile_image_url' => preg_match_one('#src="([^"]*)"#' , $tweet),
					),
					'to' => (object) array(
						'screen_name' => preg_match_one('#@([^<]+)#', $tweet),
					),
					'created_at' => str_replace('about', '', preg_match_one('#info">\s(.*)#', $tweet)),
				);
			}
			// Second pass: OPTIONALLY attempt to reverse the order of tweets
			if (setting_fetch('reverse') == 'yes') {
				$first = false;
				foreach ($output as $id => $tweet) {
					$date_string = str_replace('later', '', $tweet->created_at);
					if ($first) {
						$attempt = strtotime("+$date_string");
						if ($attempt == 0) $attempt = time();
						$previous = $current = $attempt - time() + $previous;
					} else {
						$previous = $current = $first = strtotime($date_string);
					}
					$output[$id]->created_at = date('r', $current);
				}
				$output = array_reverse($output);
			}
			return $output;

		default:
			echo "<h1>$source</h1><pre>";
			print_r($feed); die();
	}
}

function preg_match_one($pattern, $subject, $flags = NULL) {
	preg_match($pattern, $subject, $matches, $flags);
	return trim($matches[1]);
}

function twitter_user_info($username = null) {
	if (!$username)
	$username = user_current_username();
	$request = API_URL."users/show.json?screen_name=$username&include_entities=true";
	$user = twitter_process($request);
	return $user;
}

function theme_timeline($feed)
{
	if (count($feed) == 0) return theme('no_tweets');
	$rows = array();
	$page = menu_current_page();
	$date_heading = false;
	$first=0;

	foreach ($feed as $status)
	{
		if ($first==0)
		{
			$since_id = $status->id;
			$first++;
		}
		else
		{
			$max_id =  $status->id;
			if ($status->original_id)
			{
				$max_id =  $status->original_id;
			}
		}
		$time = strtotime($status->created_at);
		if ($time > 0)
		{
			$date = twitter_date('l jS F Y', strtotime($status->created_at));
			if ($date_heading !== $date)
			{
				$date_heading = $date;
				$rows[] = array('data'  => array($date), 'class' => 'date');
			}
		}
		else
		{
			$date = $status->created_at;
		}
		$text = twitter_parse_tags($status->text, $status->entities);
		$link = theme('status_time_link', $status, !$status->is_direct);
		$actions = theme('action_icons', $status);
		$avatar = theme('avatar', $status->from->profile_image_url);
		$source = $status->source ? " from ".str_replace('rel="nofollow"', 'rel="nofollow" target="_blank"', preg_replace('/&(?![a-z][a-z0-9]*;|#[0-9]+;|#x[0-9a-f]+;)/i', '&amp;', $status->source)) : ''; //need to replace & in links with &amps and force new window on links
		if ($status->in_reply_to_status_id)	{
			$source .= " <a href='status/{$status->in_reply_to_status_id_str}'>in reply to {$status->in_reply_to_screen_name}</a>";
		}
		if ($status->retweet_count)	{
			$source .= " retweeted ";
			switch($status->retweet_count) {
				case(1) : $source .= "once"; break;
				case(2) : $source .= "twice"; break;
				default : $source .= $status->retweet_count . " times";
			}
		}
		if ($status->retweeted_by) {
			$retweeted_by = $status->retweeted_by->user->screen_name;
			$source .= "<br />retweeted to you by <a href='user/{$retweeted_by}'>{$retweeted_by}</a>";
		}
		$html = "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link<br />{$text} <small>$source</small>";

		unset($row);
		$class = 'status';
		
		if ($page != 'user' && $avatar)
		{
			$row[] = array('data' => $avatar, 'class' => 'avatar');
			$class .= ' shift';
		}
		
		$row[] = array('data' => $html, 'class' => $class);

		$class = 'tweet';
		if ($page != 'replies' && twitter_is_reply($status))
		{
			$class .= ' reply';
		}
		$row = array('data' => $row, 'class' => $class);

		$rows[] = $row;
	}
	$content = theme('table', array(), $rows, array('class' => 'timeline'));

	//$content .= theme('pagination');
	if ($page != '')
	{
		$content .= theme('pagination');
	}
	else
	{
		//Doesn't work. since_id returns the most recent tweets up to since_id, not since. Grrr
		//$links[] = "<a href='{$_GET['q']}?since_id=$since_id'>Newer</a>";

		//max_id - 1 fails on 32 bit php installs, so removed. uncomment line below if you want it back
		//$max_id = (float)$max_id - 1; //stops last tweet appearing as first tweet on next page
		$links[] = "<a href='{$_GET['q']}?max_id=$max_id' accesskey='9'>Older</a> 9";
		$content .= '<p>'.implode(' | ', $links).'</p>';
	}



	return $content;
}

function twitter_is_reply($status) {
	if (!user_is_authenticated()) {
		return false;
	}
	$user = user_current_username();
	return preg_match("#@$user#i", $status->text);
}

function theme_followers($feed, $hide_pagination = false) {
	$rows = array();
	if (count($feed) == 0 || $feed == '[]') return '<p>No users to display.</p>';

	foreach ($feed->users->user as $user) {

		$name = theme('full_name', $user);
		$tweets_per_day = twitter_tweets_per_day($user);
		$last_tweet = strtotime($user->status->created_at);
		$content = "{$name}<br /><span class='about'>";
		if($user->description != "")
			$content .= "Bio: {$user->description}<br />";
		if($user->location != "")
			$content .= "Location: {$user->location}<br />";
		$content .= "Info: ";
		$content .= pluralise('tweet', $user->statuses_count, true) . ", ";
		$content .= pluralise('friend', $user->friends_count, true) . ", ";
		$content .= pluralise('follower', $user->followers_count, true) . ", ";
		$content .= "~" . pluralise('tweet', $tweets_per_day, true) . " per day<br />";
		$content .= "Last tweet: ";
		if($user->protected == 'true' && $last_tweet == 0)
			$content .= "Private";
		else if($last_tweet == 0)
			$content .= "Never tweeted";
		else
			$content .= twitter_date('l jS F Y', $last_tweet);
		$content .= "</span>";

		$rows[] = array('data' => array(array('data' => theme('avatar', $user->profile_image_url), 'class' => 'avatar'),
		                                array('data' => $content, 'class' => 'status shift')),
		                'class' => 'tweet');

	}

	$content = theme('table', array(), $rows, array('class' => 'followers'));
	if (!$hide_pagination)
	$content .= theme('list_pagination', $feed);
	return $content;
}

function theme_full_name($user) {
	$name = "<a href='user/{$user->screen_name}'>{$user->screen_name}</a>";
	//THIS IF STATEMENT IS RETURNING FALSE EVERYTIME ?!?
	//if ($user->name && $user->name != $user->screen_name) {
	if($user->name != "") {
		$name .= " ({$user->name})";
	}
	return $name;
}

function theme_no_tweets() {
	return '<p>No tweets to display.</p>';
}

function theme_search_results($feed) {
	$rows = array();
	foreach ($feed->results as $status) {
		$text = twitter_parse_tags($status->text, $status->entities);
		$link = theme('status_time_link', $status);
		$actions = theme('action_icons', $status);

		$row = array(
		theme('avatar', $status->profile_image_url),
      "<a href='user/{$status->from_user}'>{$status->from_user}</a> $actions - {$link}<br />{$text}",
		);
		if (twitter_is_reply($status)) {
			$row = array('class' => 'reply', 'data' => $row);
		}
		$rows[] = $row;
	}
	$content = theme('table', array(), $rows, array('class' => 'timeline'));
	$content .= theme('pagination');
	return $content;
}

function theme_search_form($query) {
	$query = stripslashes(htmlentities($query,ENT_QUOTES,"UTF-8"));
	return "<form action='search' method='get'><input name='query' value=\"$query\" /><input type='submit' value='Search' /></form>";
}

function theme_external_link($url, $content = null) {
	//Long URL functionality.  Also uncomment function long_url($shortURL)
	if (!$content)
	{
		//Used to wordwrap long URLs
		//return "<a href='$url' target='_blank'>". wordwrap(long_url($url), 64, "\n", true) ."</a>";
		return "<a href='$url' target='_blank'>". long_url($url) ."</a>";
	}
	else
	{
		return "<a href='$url' target='_blank'>$content</a>";
	}

}

function theme_pagination()
{

	$page = intval($_GET['page']);
	if (preg_match('#&q(.*)#', $_SERVER['QUERY_STRING'], $matches))
	{
		$query = $matches[0];
	}
	if ($page == 0) $page = 1;
	$links[] = "<a href='{$_GET['q']}?page=".($page+1)."$query' accesskey='9'>Older</a> 9";
	if ($page > 1) $links[] = "<a href='{$_GET['q']}?page=".($page-1)."$query' accesskey='8'>Newer</a> 8";
	return '<p>'.implode(' | ', $links).'</p>';

	/*
	 if ($_GET['max_id'])
	 {
		$id = intval($_GET['max_id']);
		}
		elseif ($_GET['since_id'])
		{
		$id = intval($_GET['since_id']);
		}
		else
		{
		$id = 17090863233;
		}

		$links[] = "<a href='{$_GET['q']}?max_id=$id' accesskey='9'>Older</a> 9";
		$links[] = "<a href='{$_GET['q']}?since_id=$id' accesskey='8'>Newer</a> 8";

		return '<p>'.implode(' | ', $links).'</p>';
		*/
}


function theme_action_icons($status) {
	$from = $status->from->screen_name;
	$retweeted_by = $status->retweeted_by->user->screen_name;
	$retweeted_id = $status->retweeted_by->id;
	$geo = $status->geo;
	$actions = array();

	if (!$status->is_direct) {
		$actions[] = theme('action_icon', "user/{$from}/reply/{$status->id}", 'images/reply.png', '@');
	}
	//Reply All functionality.
	if(substr_count(($status->text), '@') >= 1)
	{
		$found = Twitter_Extractor::extractMentionedScreennames($status->text);
		$to_users = array_unique($found);
			
		$key = array_search(user_current_username(), $to_users); // Remove the username of the authenticated user
		if ($key != NULL || $key !== FALSE) // Depending on PHP version
		{
			unset($to_users[$key]); // remove the username from array
		}
			
		if (count($to_users) >= 1)
		{
			$actions[] = theme('action_icon', "user/{$from}/replyall/{$status->id}", 'images/replyall.png', 'REPLY ALL');
		}
	}
	if (!user_is_current_user($from)) {
		$actions[] = theme('action_icon', "directs/create/{$from}", 'images/dm.png', 'DM');
	}
	if (!$status->is_direct) {
		if ($status->favorited == '1') {
			$actions[] = theme('action_icon', "unfavourite/{$status->id}", 'images/star.png', 'UNFAV');
		} else {
			$actions[] = theme('action_icon', "favourite/{$status->id}", 'images/star_grey.png', 'FAV');
		}
		$actions[] = theme('action_icon', "retweet/{$status->id}", 'images/retweet.png', 'RT');
		if (user_is_current_user($from))
		{
			$actions[] = theme('action_icon', "confirm/delete/{$status->id}", 'images/trash.gif', 'DEL');
		}
		if ($retweeted_by) //Allow users to delete what they have retweeted
		{
			if (user_is_current_user($retweeted_by))
			{
				$actions[] = theme('action_icon', "confirm/delete/{$retweeted_id}", 'images/trash.gif', 'DEL');
			}
		}

	} else {
		$actions[] = theme('action_icon', "directs/delete/{$status->id}", 'images/trash.gif', 'DEL');
	}
	if ($geo !== null)
	{
		$latlong = $geo->coordinates;
		$lat = $latlong[0];
		$long = $latlong[1];
		$actions[] = theme('action_icon', "http://maps.google.co.uk/m?q={$lat},{$long}", 'images/map.png', 'MAP');
	}
	//Search for @ to a user
	$actions[] = theme('action_icon',"search?query=%40{$from}",'images/q.png','?');

	return implode(' ', $actions);
}

function theme_action_icon($url, $image_url, $text) {
	// alt attribute left off to reduce bandwidth by about 720 bytes per page
	if ($text == 'MAP')
	{
		return "<a href='$url' target='_blank'><img src='$image_url' /></a>";
	}

	return "<a href='$url'><img src='$image_url' /></a>";
}

function pluralise($word, $count, $show = FALSE) {
	if($show) $word = "{$count} {$word}";
	return $word . (($count != 1) ? 's' : '');
}
?>
