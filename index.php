<?php
/*
Description: Twitter PHP code
Author: Saket Saurabh
Version: 1.0.0
*/

/** Method to make twitter api call for the users timeline in XML */ 
function twitter_status($twitter_id) {	
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, "http://api.twitter.com/1/statuses/user_timeline.xml?id=$twitter_id&count=200&page=1");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($c, CURLOPT_TIMEOUT, 5);
	$response = curl_exec($c);
	$responseInfo = curl_getinfo($c);
	curl_close($c);
	if (intval($responseInfo['http_code']) == 200) {
		if (class_exists('SimpleXMLElement')) {
			$xml = new SimpleXMLElement($response);
			return $xml;
		} else {
			return $response;
		}
	} else {
		return false;
	}
}

/** Method to add hyperlink html tags to any urls, twitter ids or hashtags in the tweet */ 
function processLinks($text) 
	{
	$text = utf8_decode( $text );
	$text = preg_replace('@(https?://([-\w\.]+)+(d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>',  $text );
	$text = preg_replace("#(^|[\n ])@([^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://www.twitter.com/\\2\" >@\\2</a>'", $text);  
	$text = preg_replace("#(^|[\n ])\#([^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://hashtags.org/search?query=\\2\" >#\\2</a>'", $text);
	return $text;
	}

/** Main method to retrieve the tweets and return html for display */
function get_tweets($twitter_id, 
					$nooftweets=100, 
					$dateFormat="D jS M y H:i", 
					$includeReplies=false, $dateTimeZone="Europe/London",
					$beforeTweetsHtml="<ul>", 
					$tweetStartHtml="<li class=\"tweet\"><span class=\"tweet-status\">",
					$tweetMiddleHtml="</span><br/><span class=\"tweet-details\">",
					$tweetEndHtml="</span></li>", 
					$afterTweetsHtml="</ul>") 
	{    
	date_default_timezone_set($dateTimeZone);
	$result = $beforeTweetsHtml;
	$tweets = array();
	$i = 0;
   	if ( $twitter_xml = twitter_status($twitter_id) ) 
		{
		foreach ($twitter_xml->status as $key => $status) 
			{
			if ($includeReplies == true | substr_count($status->text,"@") == 0 | strpos($status->text,"@") != 0) 
				{
				$tweets[$i]['msg'] = utf8_decode($status->text);
				$tweets[$i]['date'] = date($dateFormat,strtotime($status->created_at));
				$message = processLinks($status->text);
				$result.=$tweetStartHtml.$message.$tweetMiddleHtml.date($dateFormat,strtotime($status->created_at)).$tweetEndHtml;
				++$i;
				if ($i == $nooftweets) break;
    			}
    		}
			$result.=$afterTweetsHtml;
		} 
	else 
		{
        $result.= $beforeTweetsHtml."<li id='tweet'>Twitter seems to be unavailable at the moment</li>".$afterTweetsHtml;
		$tweets[$i]['msg'] = "<li id='tweet'>Twitter seems to be unavailable at the moment</li>";
		$tweets[$i]['date'] = "-1";
		}	
    //echo $result;
	return $tweets;
	}

	if(isset($_GET['id']))
		$twitter_id = $_GET['id'];
	else
		{
		echo ":P fu !";
		exit;
		}
	
	$tweets = get_tweets($twitter_id);
	$movies = array();
	$i=0;
	foreach ($tweets as $status) 
		if(preg_match("#(^|[\n ])\#watching([^ \"\t\n\r<]*)#ise",$status['msg']))
			{
			$movies[$i]['msg']= $status['msg'];
			$movies[$i]['movie']= preg_replace("/#watching/", "", $status['msg']);
			preg_match('@(https?://([-\w\.]+)+(d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $status['msg'], $match );
			$movies[$i]['link']= $match[0] ;
			$movies[$i]['movie'] = preg_replace('@(https?://([-\w\.]+)+(d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '',  $movies[$i]['movie'] );
			$movies[$i]['movie'] = preg_replace('/-/', '',  $movies[$i]['movie'] );
			$movies[$i]['movie'] = trim($movies[$i]['movie'] );
			$i++;
			if ($i == 10) break;
			}
	//echo "<pre>";
	//print_r($movies);
	//echo "</pre>";
?>
<head>
<title>Last 10 Movies Watched by <?php echo $twitter_id; ?></title>
<head>
<body>
	<ul>
		<?php
			foreach ($movies as $temp)
				echo "<li><a href='".$temp['link']."'>".$temp['movie']." </a></li>";
		?>
	</ul>
</body>