<?php
if($Roger>0) {echo ("<br/>File: " . dirname(__FILE__) . ", Line: " . __LINE__ . "<br/>");}

$test_db_host = "localhost";
$test_db_user = "msm";
$test_db_pwd  = "EetGiOj6";
$test_db_pwd  = "claypigeon";
$test_db_name = "test_msm";

$db_host = "192.168.1.254";	// resolvable in /etc/hosts... keng office area ini
///$db_host = "210.4.97.115:30306";	// Para keng bale ini ... hehehehe
$db_user = "reynan";
$db_name = "dev_reporting";
$db_pwd  = "Adsys2024!";

if (TEST_DB) 
{
	$db_host = $test_db_host;
	$db_user = $test_db_user;
	$db_name = $test_db_name;
	$db_pwd  = $test_db_pwd;
} // if

$db_conn = mysqli_connect( $db_host, $db_user, $db_pwd );
mysqli_select_db($db_conn, $db_name);

// some basic MySQL transaction handling routines:

function open_mysql()
{
GLOBAL $db_conn, $db_host, $db_user, $db_pwd, $db_name;
GLOBAL $msg_log;

	$db_conn = mysqli_connect( $db_host, $db_user, $db_pwd );
	if ($db_conn && mysqli_select_db($db_conn,$db_name)) 
	{
		$result = TRUE;
		$qry = "set autocommit = 1";
		$result = mysqli_query($db_conn,$qry);
	} 
	else 
	{
		message_log_append( $msg_log, mysqli_error( $db_conn ), MSG_LOG_ERROR );
		$result = FALSE;
	}
	return( $result );
} // open_mysql

function begin( $db_conn )
{
GLOBAL $msg_log;

	message_log_append( $msg_log, "START TRANSACTION" );
	return( mysqli_query($db_conn,"START TRANSACTION" ) );
}

function commit( $db_conn )
{
GLOBAL $msg_log;

	message_log_append( $msg_log, "COMMIT" );
	return( mysqli_query($db_conn, "COMMIT"));
}

function rollback( $db_conn )
{
GLOBAL $msg_log;

	message_log_append( $msg_log, "ROLLBACK" );
	return( mysqli_query($db_conn,"ROLLBACK"));
}

function last_insert_id( $db_conn )
{
	$result = mysqli_query($db_conn, "SELECT LAST_INSERT_ID()");
	if ($result) 
	{
		$record = mysqli_fetch_array( $result );
		$value = ($record ? $record[ 0 ] : NULL);
	} 
	else 
	{
		$value = NULL;
	}
	return( $value );
}

function field_list( $table, $db_conn )
// return a string with the comma-separated list of fields in
// $table.  return NULL on error.
{
	$result = mysqli_query($db_conn,"SHOW COLUMNS FROM $table");
	if ($result) 
	{
		$value = "";
		while ($record = mysqli_fetch_array( $result )) 
		{
			$value .= "," . $record[ "Field" ];
		}
		$value = substr( $value, 1 ); // remove leading comma
	} 
	else
	{
		$value = NULL;
	}
	return( $value );
} // field_list

function site_name( $db_conn )
{
	$result = mysqli_query($db_conn,"SELECT SiteName FROM temp_header");
	if ($result) 
	{
		$record = mysqli_fetch_array( $result );
		$value = ($record) ? $record[ 0 ] : NULL;
	} 
	else 
	{
		$value = NULL;
	}
	return( $value );
}

function network_alias( $p_network, $db_conn )
// Look up the NCC alias for the given network tag
// return NULL if error.
{
	$qry = "SELECT NCCAlias from network WHERE Name = '$p_network'";
	$result = mysqli_query($db_conn,$qry);
	if ($result) 
	{
		$record = mysqli_fetch_array( $result );
		if ($record)
		{
			$value = $record[ 0 ];
		}
		else
		{
			$value = NULL;
		}
	} 
	else 
	{
		$value = NULL;
	}
	return( $value );
}

function agency_record( $agency_name, $oper_name )
{
GLOBAL $db_conn;
GLOBAL $msg_log;

	$qry = "SELECT * FROM agencies WHERE Operator =  '$oper_name' " . "AND Name = '$agency_name'";
	if (($sql_result = mysqli_query($db_conn,$qry)) &&
			($record = mysqli_fetch_array( $sql_result ))) 
	{
		$result = $record;
	} 
	else 
	{
//		message_log_append( $msg_log, "MySQL query failed: " . $qry, MSG_LOG_ERROR );
//		message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
		$result = NULL;
	}
	return( $result );
} // agency_record

function cust_record( $cust_name, $oper_name )
// fetch a record from the customer table and return
// it as an array.  return NULL on error.
{
GLOBAL $db_conn;
GLOBAL $msg_log;

	$qry = "SELECT * FROM customers WHERE Operator =  '$oper_name' " . 	"AND Name = '$cust_name'";
	if (($sql_result = mysqli_query($db_conn,$qry)) &&
			($record = mysqli_fetch_array( $sql_result ))) 
	{
		$result = $record;
	} 
	else 
	{
//		message_log_append( $msg_log, "MySQL query failed: " . $qry, MSG_LOG_ERROR );
//		message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
		$result = NULL;
	}
	return( $result );
} // cust_record

?>
