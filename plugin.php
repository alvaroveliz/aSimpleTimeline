<?php
/*
Plugin Name: aSimpleTimeline
Plugin URI: https://github.com/alvaroveliz/aSimpleTimeline
Description: A plugin that helps you to get a Twitter User Timeline
Version: 1.0
Author: Alvaro Véliz
Author URI: http://alvaroveliz.cl
License: MIT
*/
require_once 'includes/TwitterAPIExchange.php';

/** aSimpleTimeline **/
class aSimpleTimeline
{

  private $twitter;

  public function __construct()
  {
    $ast_consumer_key         = get_option('ast_consumer_key');
    $ast_consumer_secret      = get_option('ast_consumer_secret');
    $ast_access_token         = get_option('ast_access_token');
    $ast_access_token_secret  = get_option('ast_access_token_secret');

    $settings = array(
      'consumer_key'              => $ast_consumer_key,
      'consumer_secret'           => $ast_consumer_secret,
      'oauth_access_token'        => $ast_access_token,
      'oauth_access_token_secret' => $ast_access_token_secret,
    );

    $this->twitter = new TwitterAPIExchange($settings);
  }

  public function getUserTimeline($screen_name, $limit = 10)
  {
    $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    $getfield = '?screen_name='.$screen_name.'&count='.$limit;
    $requestMethod = 'GET';

    $response = $this->twitter->setGetfield($getfield)
             ->buildOauth($url, $requestMethod)
             ->performRequest();

    return json_decode($response);
  }

  public function parseTweetText($tweet)
  {
    $text = $tweet->text;
    $hashtag_link_pattern = '<a href="http://twitter.com/search?q=%%23%s&src=hash" rel="nofollow" target="_blank">#%s</a>';
    $url_link_pattern = '<a href="%s" rel="nofollow" target="_blank" title="%s">%s</a>';
    $user_mention_link_pattern = '<a href="http://twitter.com/%s" rel="nofollow" target="_blank" title="%s">@%s</a>';
    $media_link_pattern = '<a href="%s" rel="nofollow" target="_blank" title="%s">%s</a>';

    $entity_holder = array();

    foreach($tweet->entities->hashtags as $hashtag)
    {
      $entity = new stdclass();
      $entity->start = $hashtag->indices[0];
      $entity->end = $hashtag->indices[1];
      $entity->length = $hashtag->indices[1] - $hashtag->indices[0];
      $entity->replace = sprintf($hashtag_link_pattern, strtolower($hashtag->text), $hashtag->text);
      $entity_holder[$entity->start] = $entity;
    }

    foreach($tweet->entities->urls as $url)
    {
      $entity = new stdclass();
      $entity->start = $url->indices[0];
      $entity->end = $url->indices[1];
      $entity->length = $url->indices[1] - $url->indices[0];
      $entity->replace = sprintf($url_link_pattern, $url->url, $url->expanded_url, $url->display_url);
      $entity_holder[$entity->start] = $entity;
    }

    foreach($tweet->entities->user_mentions as $user_mention)
    {
      $entity = new stdclass();
      $entity->start = $user_mention->indices[0];
      $entity->end = $user_mention->indices[1];
      $entity->length = $user_mention->indices[1] - $user_mention->indices[0];
      $entity->replace = sprintf($user_mention_link_pattern, strtolower($user_mention->screen_name), $user_mention->name, $user_mention->screen_name);
      $entity_holder[$entity->start] = $entity;
    }

    if (isset($tweet->entities->media)) {
      foreach($tweet->entities->media as $media)
      {
        $entity = new stdclass();
        $entity->start = $media->indices[0];
        $entity->end = $media->indices[1];
        $entity->length = $media->indices[1] - $media->indices[0];
        $entity->replace = sprintf($media_link_pattern, $media->url, $media->expanded_url, $media->display_url);
        $entity_holder[$entity->start] = $entity;
      }
    }
    

    krsort($entity_holder);

    foreach($entity_holder as $entity)
    {
      $text = substr_replace($text, $entity->replace, $entity->start, $entity->length);
    }

    return $text;
  }
}

/** ADMIN **/
function ast_admin_options()
{
  add_menu_page( 'aSimpleTimeline', 'aSimpleTimeline', 'administrator', 'a_simple_timeline', 'ast_admin_settings');
}

function ast_admin_settings()
{
  $ast_consumer_key         = get_option('ast_consumer_key');
  $ast_consumer_secret      = get_option('ast_consumer_secret');
  $ast_access_token         = get_option('ast_access_token');
  $ast_access_token_secret  = get_option('ast_access_token_secret');

  $html = '</pre>
  <div class="wrap">
    <form action="options.php" method="post" name="options">
      <h2>Please configure the plugin with this 3 steps</h2>' . wp_nonce_field('update-options') . '
      <h3>1. First of all, you have to create your Twitter App <a href="https://dev.twitter.com/apps/new">here</a></h3>
      <h3>2. Second, configure your application. Be sure to <strong>recreate your access token</strong> first.</h3>
      <table class="form-table" width="100%" cellpadding="10">
        <tbody>
          <tr>
            <td>
              <label>Consumer Key</label>
            </td>
            <td>
              <input type="text" name="ast_consumer_key" value="'.$ast_consumer_key.'" placeholder="" size="80">
            </td> 
          </tr>
          <tr>
            <td>
              <label>Consumer Secret</label>
            </td>
            <td>
              <input type="text" name="ast_consumer_secret" value="'.$ast_consumer_secret.'" placeholder="" size="80">
            </td> 
          </tr>
          <tr>
            <td>
              <label>Access Token</label>
            </td>
            <td>
              <input type="text" name="ast_access_token" value="'.$ast_access_token.'" placeholder="" size="80">
            </td> 
          </tr>
          <tr>
            <td>
              <label>Access Token Secret</label>
            </td>
            <td>
              <input type="text" name="ast_access_token_secret" value="'.$ast_access_token_secret.'" placeholder="" size="80">
            </td> 
          </tr>
          <tr>
            <td></td>
            <td>
              <input type="hidden" name="action" value="update" />
              <input type="hidden" name="page_options" value="ast_consumer_key,ast_consumer_secret,ast_access_token,ast_access_token_secret" />
              <input type="submit" name="Submit" value="Save Settings" />
            </td>
          </tr>
        </tbody>
      </table>
      <h3>3. Finally, use the widget or the shortcode whenever you want. That\'s all!</h3>
      <p>Shortcode Example: <em>[asimpletimeline username="alvaroveliz" limit="10"]</em></p>
    </form>
      
  </div>
  <pre>
  ';

  echo $html;
}

add_action('admin_menu', 'ast_admin_options');

/** SHORTCODE **/
function ast_shortcode($attributes)
{
  $ast = new aSimpleTimeline();
  $tweets = $ast->getUserTimeline($attributes['username']);

  echo '<ul>';
  foreach ($tweets as $tweet)
  {
    $text = $ast->parseTweetText($tweet);
    echo '<li>'.$text.'</li>';
  }
  echo '</ul>';
}

add_shortcode( 'asimpletimeline', 'ast_shortcode' );
