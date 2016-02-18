<?php // (C) Copyright Bobbing Wide 2014
/**
 * an OO version of foobar to test oik-shortcodes for OO code
 */
class oof { 

  function fighters() {
  }
}

/**
 * Example OO class called rab.
 *
 * Long description.
 *
 * @since x.x.x
 * @access (for functions: only use if private)
 *
 * @see Function/method/class relied on
 * @link URL
 * @global type $varname Short description.
 *
 * @param  type $var Description.
 * @param  type $var Optional. Description.
 * @return type Description.
 */
class rab {

  /**
   * short description for rab's fighters()
   *
   * Another fighters API
   * @param string $cnesbitt - married to mary hen  
   * @return string - the passed parameter
   */
  function fighters( $cnesbitt ) {
    return $cnesbitt;  
  }

  
  function roombrawl() {
  }
}

class raboof extends oof {
  function raboof_fighters() {
  }
}

class rabfoo extends foo {
  
  function raboof_fighters() {
    
  }
  
  function __construct() {
    $foolen = strlen(  "foo" );
    parent::__construct();
    WP_CLI::log( "this" );
    raboof::raboof_fighters();
    $this->raboof_fighters();
    $foo = "foo";
    add_action( "rabfoo" , "oobar_foobar" );
    add_action( "rab$foo" , array( $this, "raboof_fighters" ) );
    do_action( "rab$foo" );
    do_action( "rab" , $foo );
    do_action( 'baz' . $qux );
    $bar = apply_filters( "rab", "foo" );
  }
}




