<?php
namespace App\Models;

use GuzzleHttp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Rudolf\OAuth2\Client\Provider\Reddit;

class PostWatcher extends Model
{
    // TODO actually set fillable to something
    protected $guarded = [];

    public static function getHeaders()
    {
        $reddit = new Reddit([
            'clientId'     => config('reddit.clientId'),
            'clientSecret' => config('reddit.clientSecret'),
            'redirectUri'  => config('reddit.redirectUri'),
            'userAgent'    => config('reddit.userAgent'),
            'scopes'       => config('reddit.scopes'),
        ]);
        $accessToken = $reddit->getAccessToken('client_credentials');
        $headers = $reddit->getHeaders($accessToken);

        return $headers;
    }


    public function posthistory()
    {
        return $this->hasMany('App\Models\PostHistory');
    }

    public static function submissionData($sub = "all", $mode = 'hot', $count = 5)
    {
        $client = new GuzzleHttp\Client();
        $headers = self::getHeaders();
        $res = $client->get("https://www.reddit.com/r/$sub/$mode.json?limit=$count", $headers);
        $data = json_decode($res->getBody());
        return $data;
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
        $data = self::submissionData('all', 'rising', $count)->data->children;
        $rand_keys = array_rand($data, 3);

        foreach ($rand_keys as $key => $value) {
            $post = $data[$value]->data;
            PostWatcher::firstOrCreate([
                'reddit_id'        => $post->id,
                'reddit_permalink' => $post->permalink,
                'title'            => $post->title,
                'thumbnail'        => $post->thumbnail,
                'raw'              => json_encode($post),
                'interesting'      => false
            ],
                [
                    'current_karma'  => $post->score,
                    'starting_karma' => $post->score
                ]);

            $h = PostHistory::create([
                'score' => $post->score,
                // TODO 'on_front_page' => $post->
                'num_comments' => $post->num_comments
            ]);
        }


        // $response = $r->getListing('all/new',50);
        // $children = $response->data->children??[];
        // $rand_keys = array_rand($children, 2);
        // foreach ($rand_keys as $key => $value) {
        // 	$reddit_idList[] = $children[$value]->data->id;
        // }

//        foreach ($reddit_idList as $key => $reddit_id) {
//            PostWatcher::getOrCreatePostWatcherFromRedditId($reddit_id);
//        }
//        return $reddit_idList;
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
