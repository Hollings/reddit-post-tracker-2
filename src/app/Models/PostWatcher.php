<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rudolf\OAuth2\Client\Provider\Reddit;
use App\Models\PostHistory;
use GuzzleHttp;
use Illuminate\Support\Facades\Log;

class PostWatcher extends Model
{

    public static function test()
    {
        $reddit = new Reddit([
            'clientId'      => config('reddit.clientId'),
            'clientSecret'  => config('reddit.clientSecret'),
            'redirectUri'   => config('reddit.redirectUri'),
            'userAgent'     => config('reddit.userAgent'),
            'scopes'        => config('reddit.scopes'),
        ]);
        $accessToken = $reddit->getAccessToken('client_credentials');
        $headers = $reddit->getHeaders($accessToken);

        $client = new GuzzleHttp\Client();
        dump($headers);

        $res = $client->get('https://www.reddit.com/r/videos/new.json?limit=25', $headers);
        dump($res->getStatusCode()); // 200
        dump(json_decode($res->getBody())); // { "type": "User", ....
    }


    public function posthistory()
    {
        return $this->hasMany('App\Models\PostHistory');
    }

    public static function frontPage($sub = "all")
    {
        $r = new Reddit;
        $link = "/r/all";
        $response = $r->getRawJSON($link);
        $frontPageIds = [];
        foreach ($response->data->children as $key => $child) {
            array_push($frontPageIds, $child->data->id);
        }
        return $frontPageIds;
        return json_encode($response);
    }

    public function getCurrentData()
    {
    	log::info('updating '.$this->reddit_permalink);

    	$r = new Reddit;
    	$h = new PostHistory;

    	$permalink = $this->reddit_permalink;
    	$response = $r->getRawJSON($permalink);

    	if (is_null($response??null)){
            return "API Request Failed";
        }
    	echo(json_encode($permalink));

        // Parse the link json and save it
        $linkData = $response[0]->data->children[0]->data;
        $h->score = $linkData->score;
        $h->num_comments = $linkData->num_comments;
        $h->upvote_ratio = $linkData->upvote_ratio;
        $h->save();
        $this->posthistory()->save($h);

    }

    public static function GetRandomFromRising($count = 25)
    {
    	$r = new Reddit;
    	$response = $r->getListing('all/rising',25);
    	$children = $response->data->children??[];
    	$rand_keys = array_rand($children, 3);
    	$reddit_idList = [];
    	foreach ($rand_keys as $key => $value) {
    		$reddit_idList[] = $children[$value]->data->id;
    	}


    	// $response = $r->getListing('all/new',50);
    	// $children = $response->data->children??[];
    	// $rand_keys = array_rand($children, 2);
    	// foreach ($rand_keys as $key => $value) {
    	// 	$reddit_idList[] = $children[$value]->data->id;
    	// }

        foreach ($reddit_idList as $key => $reddit_id) {
            PostWatcher::getOrCreatePostWatcherFromRedditId($reddit_id);
        }
        return $reddit_idList;
    }

    public static function getOrCreatePostWatcherFromRedditId($reddit_id)
    {
        $postWatcher = PostWatcher::where('reddit_id',$reddit_id)->first();
        if (!count($postWatcher)) {
            $postWatcher = new PostWatcher;
            $postWatcher->reddit_id = $reddit_id;
            $postWatcher->save();
        }
        return $postWatcher;
    }

    public static function byId($fullnames)
    {
    	$r = new Reddit;


    	$response = $r->byId($fullnames);
    	// echo (json_encode($response))."<br><br>".PHP_EOL;
    	if (is_null($response??null)){
            return "API Request Failed";
        }
        $frontPage = PostWatcher::frontPage();
    	$posts = $response->data->children??[];
    	// echo json_encode($posts);
        // Parse the link json and save it
        foreach ($posts as $key => $post) {

        	// Log::info(json_encode($post));
	        $linkData = $post->data;
	        $p = PostWatcher::where('reddit_id',$linkData->id)->first();
	        if (!count($p)) {
	        	return [];
	        }
	        $h = new PostHistory;
	        $h->score = $linkData->score;
	        $h->on_front_page = in_array($linkData->id, $frontPage);
            $h->num_comments = $linkData->num_comments;
	        $h->save();
	        $p->reddit_permalink = $linkData->permalink;
	        if ($p->starting_karma == 0) {
	        	$p->starting_karma =  $linkData->score;
	        }
	        $p->current_karma =  $linkData->score;
            if (strlen($linkData->title) < 250) {
                // Posts with emojis are too extremely long sometimes
                $p->title = $linkData->title;
            }
            $p->thumbnail = $linkData->thumbnail;
            $p->raw = json_encode($linkData);
	        $p->touch();
	        $p->save();
	        $p->posthistory()->save($h);
        }

    }
}
