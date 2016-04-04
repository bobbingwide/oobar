<?php
add_filter( "dummy", "oiku_field_validation_userref", 10 );
add_filter( "bw_field_validation_userref", "bw_trace4", 10 );

add_shortcode( '0', 'oobar_0' );
// add_shortcode( '00', "oobar_0" );

function oobar_0() {
  return( '0' );
}

/* 
 * Return 1
 * 
 * Just a test
 */             
function oobar_1() {
  return( 1 );
} 



