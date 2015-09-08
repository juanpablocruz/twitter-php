<?php


class Tweet {
	public $consumerKey;
	public $consumerSecret;
	public $username;
	public $bearer;
	public $tweets;
	private $num_tweets;
	
	function __construct($ck,$cs,$username, $num_tweets = 2){
		$this->consumerKey = $ck;
		$this->consumerSecret = $cs;
		$this->num_tweets = $num_tweets;
		$this->username = $username;
		$this->get_bearer_token();
		$this->tweets = $this->get_tweet_list();
	}

	public function get_bearer_token(){
		$ch = curl_init();
	 
		//set the endpoint url
		curl_setopt($ch,CURLOPT_URL, 'https://api.twitter.com/oauth2/token');
		// has to be a post
		curl_setopt($ch,CURLOPT_POST, true);
		$data = array();
		$data['grant_type'] = "client_credentials";
		curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
		 
		// here's where you supply the Consumer Key / Secret from your app:        
		curl_setopt($ch,CURLOPT_USERPWD, $this->consumerKey . ':' . $this->consumerSecret);
		 
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		 
		//execute post
		$result = curl_exec($ch);
		 
		//close connection
		curl_close($ch);
		 
		// show the result, including the bearer token (or you could parse it and stick it in a DB)       
		$this->bearer = (json_decode($result)->access_token);
	}
	
	function get_tweet_list(){
		$feed = "https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name={$this->username}&count={$this->num_tweets}&include_rts=1";
		$cache_file = dirname(__FILE__).'/cache/'.'twitter-cache';
		$modified = filemtime( $cache_file );
		$now = time();
		$interval = 600; // ten minutes
		// check the cache file
		if ( !$modified || ( ( $now - $modified ) > $interval ) ) {
		  $context = stream_context_create(array(
		    'http' => array(
		      'method'=>'GET',
		      'header'=>"Authorization: Bearer " . $this->bearer
		      )
		  ));
		  
		  $json = file_get_contents( $feed, false, $context );
		  
		  if ( $json ) {
		    $cache_static = fopen( $cache_file, 'w' );
		    fwrite( $cache_static, $json );
		    fclose( $cache_static );
		  }
		}
		return json_decode($json);
	}

	function processText($tweet){
		$text = $this->tweets[$tweet]->text;
		$text = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $text);
		$text = preg_replace('/@(\w+)/', ' <a href="https://twitter.com/$1"  target="_blank">@$1</a>', $text);
		$text = preg_replace('/#(\w+)/', ' <a href="https://twitter.com/hashtag/$1"  target="_blank">#$1</a>', $text);
		echo $text;
	}

	function getTime ($tweet)
	{
		$time = strtotime($this->tweets[$tweet]->created_at);
	    $time = time() - $time; // to get the time since that moment
	    $time = ($time<1)? 1 : $time;
	    $tokens = array (
	        31536000 => 'año',
	        2592000 => 'mes',
	        604800 => 'w',
	        86400 => 'd',
	        3600 => 'h',
	        60 => 'm',
	        1 => 's'
	    );

	    foreach ($tokens as $unit => $text) {
	        if ($time < $unit) continue;
	        $numberOfUnits = floor($time / $unit);
	        return $numberOfUnits.''.$text.(($numberOfUnits>1)?'':'');
	    }

	}
}
$consumerKey = "xxxxxxxxxxx";
$consumerSecret = "xxxxxxxx";
$username "xxxxxxxx";
$twiter = new Tweet($consumerKey,$consumerSecret,$username);
?>