<?php 
/**
Plugin Name: oobar
Plugin URI: http://www.oik-plugins.com/oik-plugins/oobar
Description: a sample OO plugin 
Version: 1.0
Author: bobbingwide
Author URI: http://www.bobbingwide.com
License: GPL2
*/

class foo {

	function doer() { 
		do_action( 'before_oobar_foobar' );
		oobar_foobar();
		do_action( "after_oobar_foobar" );
	}
} 

class bar extends foo {
	function __construct() {
		parent::__construct;
	}
	
	function init() {
		/**
		 * Fires before the Site Activation page is loaded.
		 *
		 * @since 3.0.0
		 */
		do_action( 'activate_header' );
		self::doer();


	}
  /**
   * Sample method - barfoo
   *
   * Used to demonstrate dynamic API documentation for a method
   *
   * 
   * @param string $bar - e.g. "bar"
   * @param string $foo - e.g. "foo"
   * @return string foo concatenated to bar
   */
  function barfoo( $bar, $foo=null ){
    return( $bar . $foo );
   }
   
   
  function roombrawl() {
  }
 

/* line whatever */ }

/**
 * Function invoked when file loaded
 * 
 * oobar_foobar() is the function that is invoked when the plugin (this file) is first loaded.
 * If we deactivate the plugin then this function will not be present, so will have to be loaded
 * in order for us to produce output using Reflection functions.
 */
function oobar_foobar() { 
//gobang();
  if ( false ) {
	echo '<div class="hidden" id="inline_  ">
	<div class="post_title">'  .  '</div>
	<div class="post_name">' . apply_filters( 'editable_slug', $post->post_name ) . '</div>';
  }
}
