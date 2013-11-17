<?php   if ( ! defined('FS_PATH')) exit('No direct script access allowed');

/**
 * This class for managing social authorization operations
 *
 */

class Socialauth {

    public $FB_APP_KEY = '120957731257602';

    public $FB_APP_SECRET = '56872f414479b59b2f33ccee958e0d48';

    public $TWITTER_CALLBACK_URL = '';

    public $TWITTER_CONSUMER_KEY = 'o2j7ra547bcfpOqChBdqhw';

    public $TWITTER_CONSUMER_SECRET = 'c828vXnHcs7b8HkeixsVHOsSf1KZ8cw9bKrgCEIJQ';

    public $_NETWORKS = array("twitter", "facebook", "google", "linkedin", "yahoo");

    public function authenticate ($app = 'facebook') 
    {
        return $this->$app();
    }

    public  function google()
    {
        $openid = new LightOpenID($_SERVER['HTTP_HOST']);

        if ($openid->validate()) 
        {
            $returnVariables = $openid->getAttributes();
            $email = $returnVariables['contact/email'];

            $_SESSION["ologin"] = array();
            $_SESSION["ologin"]['username']  = $email;
            $_SESSION["ologin"]['email']  = $email;
            $_SESSION["ologin"]['auth']  = 'google';

            url::redirect(site_url('/'));
        } 
        elseif ($openid->mode == 'cancel') 
        {
            Msq::instance()->errors[] =  'User has cancelled Google login';

            url::redirect(site_url('/'));
        }
        else 
        {
            $openid->identity = 'https://www.google.com/accounts/o8/id';
            $openid->required = array('namePerson/friendly', 'contact/email');

            url::redirect($openid->authUrl());
        }

    }

    public  function yahoo()
    {
 
        $openid = new LightOpenID($_SERVER['HTTP_HOST']);

        if (!$openid->mode) 
        {
            $openid->identity = "https://me.yahoo.com";
            $openid->required = array('namePerson/friendly', 'contact/email');

            url::redirect($openid->authUrl());
        } 
        elseif($openid->validate()) 
        {
            $returnVariables = $openid->getAttributes();
            $email = $returnVariables['contact/email'];

            $_SESSION["ologin"] = array();
            $_SESSION["ologin"]['username']  = $email;
            $_SESSION["ologin"]['email']  = $email;
            $_SESSION["ologin"]['auth']  = 'yahoo';
            
            $_SESSION['ologin'] = array(
                'username' => $user['username'],
                'email' => isset($user['email']) ? $user['email'] : null,
                'auth' =>  'yahoo'
            );

            url::redirect(site_url('/'));
        } 
        else 
        {
            Msq::instance()->errors[] =  'User has cancelled yahoo login';
            url::redirect(site_url('/'));
        }
    }

    public  function facebook()
    {
        $facebook = new Facebook(array(
            'appId' => FB_APP_KEY, 
            'secret' => FB_APP_SECRET
            )
        );

        $user = $facebook->getUser();

        if ($user) 
        {            
            try 
            {
                // Proceed knowing you have a logged in user who's authenticated.
                $user = $facebook->api('/me');
            }
            catch (FacebookApiException $e) 
            {
                util::printr($e);
                $user = null;
            }

            $img = file_get_contents("http://graph.facebook.com/{$user['username']}/picture?type=large");
            
            $filename = null;
            if ((bool)$img) 
            {
                $filename = "uploads/{$user['username']}-fb-" ;
                file_put_contents($filename, $img);
            }

            $_SESSION ['ologin'] = array(
                'username' => $user['username'],
                'email' => isset($user['email']) ? $user['email'] : null,
                'auth' =>  'facebook',
                'img' =>  ($filename) ? $filename : null,
            );

            $_SESSION["ologin"] = array();
            $_SESSION["ologin"]['username']  = $user['username'];
            $_SESSION["ologin"]['email']  = isset($user['email']) ? $user['email'] : null;  
            $_SESSION["ologin"]['auth']  = 'facebook';
            $_SESSION["ologin"]['img'] = ($filename) ? $filename : null;

            url::redirect(site_url('/'));
        }
        else 
        {
            url::redirect($facebook->getLoginUrl());
        }

    }   

    public  function twitter()
    {
        $callback_url = TWITTER_CALLBACK_URL;
         
        $consumer_key = TWITTER_CONSUMER_KEY ;
        $consumer_secret =  TWITTER_CONSUMER_SECRET ;
        
        $oauth_token = (isset($_GET['oauth_token'])) ? $_GET['oauth_token'] : null;

        if (!$oauth_token) 
        {
            $twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
            $url = $twitterObj->getAuthorizationUrl();

            url::redirect($url);
        }

        $oauth_request_token = "http://twitter.com/oauth/request_token"; 
        $oauth_authorize = "http://twitter.com/oauth/authorize"; 
        $oauth_access_token = "http://twitter.com/oauth/access_token"; 
         
        $sig_method = new OAuthSignatureMethod_HMAC_SHA1(); 
        $test_consumer = new OAuthConsumer($consumer_key, $consumer_secret, $callback_url); 
         
        $req_req = OAuthRequest::from_consumer_and_token($test_consumer, NULL, "GET", $oauth_request_token);     
        $req_req->sign_request($sig_method, $test_consumer, NULL); 
         
        $oc = new OAuthCurl(); 
        $reqData = $oc->fetchData($req_req->to_url()); 
                         
        parse_str($reqData['content'], $reqOAuthData); 
                         
        $req_token = new OAuthConsumer($reqOAuthData['oauth_token'], $reqOAuthData['oauth_token_secret'], 1); 
                                         
        $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $req_token, "GET", $oauth_authorize); 
        $acc_req->sign_request($sig_method, $test_consumer, $req_token); 
                 
        $oauth_token = $reqOAuthData['oauth_token']; 
        $oauth_token_secret = $reqOAuthData['oauth_token_secret']; 

        url::redirect($acc_req);        
    }

    public function twitter_landing()
    {
        $consumer_key = TWITTER_CONSUMER_KEY ;
        $consumer_secret =  TWITTER_CONSUMER_SECRET ;

        $twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
        $twitterObj->setToken($_GET['oauth_token']);
        $token = $twitterObj->getAccessToken();
        $twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);
        $twitterInfo= $twitterObj->get_accountVerify_credentials();
        $response = $twitterInfo->response;

        $img_url = preg_replace("/\_normal/", "", $response['profile_image_url']);
    
        $img = file_get_contents($img_url);

        $filename = null;
        if ((bool)$img) 
        {
            $filename = "uploads/{$response['screen_name']}-twit" ;
            file_put_contents($filename, $img);
        }

        $_SESSION ['ologin'] = array(
            'username' => $response['screen_name'],
            'email' => isset($user['email']) ? $user['email'] : null,
            'auth' =>  'twitter',
            'img' =>  $filename
        );

        $_SESSION["ologin"] = array();
        $_SESSION["ologin"]['username']  = $response['screen_name'];
        $_SESSION["ologin"]['img']  = $filename;
        $_SESSION["ologin"]['auth']  = 'twitter';

        url::redirect(site_url('/'));
    }

}