<?php
if($Roger>0) {echo ("<br/>File: " . dirname(__FILE__) . ", Line: " . __LINE__ . "<br/>");}

// begin header of defines/declares only needed for testing
// well, maybe not just for testing.  Revisit this section
// later to see which of these are required as system-wide
// constant or global variable definitions.

//define( 'DEBUG', FALSE );

//$db_host="127.0.0.1";
//$db_user="james";
//$db_pwd="mysql";
//$db_name="james";

date_default_timezone_set( 'America/Los_Angeles' );

#define( "MAX_CONTRACTS",  50 );	// max. number of contracts in one XML file
define( "MAX_CONTRACTS", 100000 );	// max. number of contracts in one XML file

define( "MAX_WEEKS",    10000 );	// max. number of weeks on one order
define( "MAX_SPOTS", 1000000 );	// max. number of spots on one order

define( "MONDAY", 0 );	// used for day-of-week range checking
define( "SUNDAY", 6 );	// used for day-of-week range checking

//define( 'OPERATOR_NAME', 'AdSystems' );

//require_once( 'C:\xampp\xml_import3\include\telamerica.php' );
define( 'APEX_MEDIA',    'Apex Media' );
define( 'APEX_MEDIA_DR', 'Apex Media DR' );

//  Attribute bit values:

define( 'ATTRIB_AGENCY',        256 );
define( 'ATTRIB_AUTO_MAKEGOOD',  32 );
define( 'ATTRIB_COOP',           16 );
define( 'ATTRIB_EOF_BILLING',     8 );
define( 'ATTRIB_PENDING',         4 );
define( 'ATTRIB_FILLER',          2 );
define( 'ATTRIB_PI',              1 );

define( 'ATTRIBUTES', ATTRIB_AGENCY + ATTRIB_PENDING );

define( 'DEFAULT_AGENCY_RATE', 150 );

/*
function message_log_append( $s )
{
echo $s . "\n";
}
function message_log_reset()
{
}
function message_log()
{
}
*/

// end testing header
?>
