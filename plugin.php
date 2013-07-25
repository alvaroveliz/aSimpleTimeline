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
require_once 'includes/aSimpleTimeline.php';

$ast = new aSimpleTimeline();

/** DO THE ADMIN **/
add_action('admin_menu', array($ast, 'getAdminOptions'));

/** DO THE SHORTCODE **/
add_shortcode( 'asimpletimeline', array($ast, 'getShortCode') );

/** TO-DO: THE WIDGET **/