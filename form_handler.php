<?php
if($Roger>0) {echo ("<br/>File: " . dirname(__FILE__) . ", Line: " . __LINE__ . "<br/>");}

define( 'DOWNLOAD_LABEL', 'Download SQL' );
define( 'IMPORT_LABEL',   'Import' );
define( 'SELECT_LABEL',   'Select' );
define( 'RETURN_LABEL',   'Cancel' );
define( 'NEXT_LABEL',     'Next' );
define( 'PREV_LABEL',     'Prev' );
define( 'DEL_LN_LABEL',   'DelLn' );
define( 'DEL_NET_LABEL',  'DelNet' );
define( 'DEL_CONT_LABEL', 'Delete Contract' );

$return_url = $_SERVER['HTTP_REFERER'];

if (session_start()) 
{

//echo "GET<br>\n";
//var_dump( $_GET );
//echo "<br>";

	$action = $_GET[ 'action' ];

	if ($action == DOWNLOAD_LABEL) 
	{ // SQL code download is no longer supported
		$filnam = $_SESSION[ 'sql_file' ];
		unset( $_SESSION[ 'sql_file' ] );
		header( 'Content-type: application/force-download' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-length: ' . filesize( $filnam ) );
		header( 'Content-disposition: attachment; filename="' . basename( $filnam ) . '"' );
		readfile( $filnam );

	} 
	elseif ($action == IMPORT_LABEL) 
	{
		$_SESSION[ 'state_value' ] = 'IMPORT';
		header( 'Location: ' . $return_url );
	} 
	elseif ($action == SELECT_LABEL) 
	{
		$value = $_GET[ 'syscode' ];	// value is actually a p_cont_key index
		if (strlen( $value ) <= 5 && preg_match( '/^[0-9]*/', $value ) > 0) 
		{ // treat as an array key
			$_SESSION[ 'state_value' ] = 'DISPLAY_CONTRACT';
			$_SESSION[ 'cont_key'    ] = intval( $value );
			header( 'Location: ' . $return_url );
		} // if

	} 
	elseif ($action == RETURN_LABEL) 
	{
		$_SESSION[ 'state_value' ] = 'BEGIN';
		header( 'Location: ' . $return_url );
	} 
	elseif ($action == NEXT_LABEL) 
	{
		$_SESSION[ 'state_value' ] = 'DISPLAY_NEXT';
		header( 'Location: ' . $return_url );
	} 
	elseif ($action == PREV_LABEL) 
	{
		$_SESSION[ 'state_value' ] = 'DISPLAY_PREV';
		header( 'Location: ' . $return_url );
	} 
	elseif ($action == DEL_LN_LABEL) 
	{
		$_SESSION[ 'state_value' ] = 'DELETE_LINE';
		$_SESSION[ 'LineID' ] = $_GET[ 'LineID' ];
		header( 'Location: ' . $return_url );
	} 
	elseif ($action == DEL_NET_LABEL) 
	{
		// delete all line items that have network matching this line's network
		$_SESSION[ 'state_value' ] = 'DELETE_NETWORK';
		$_SESSION[ 'LineID' ] = $_GET[ 'LineID' ];
		header( 'Location: ' . $return_url );
	} 
	elseif ($action == DEL_CONT_LABEL) 
	{
		$_SESSION[ 'state_value' ] = 'DELETE_CONTRACT';
		header( 'Location: ' . $return_url );
	} 
	elseif (strlen( $action ) <= 5 && preg_match( '/^[0-9]*/', $action ) > 0) 
	{ // treat as an array key
		$_SESSION[ 'state_value' ] = 'DISPLAY_CONTRACT';
		$_SESSION[ 'cont_key'    ] = $action;
		header( 'Location: ' . $return_url );
	} // if action

} // if session

?>
