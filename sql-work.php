// some basic MySQL transaction handling routines:

function temp_header_site_name( $db_conn )
{
	$result = mysql_query( "SELECT SiteName FROM temp_header", $db_conn );
	if ($result) {
		$record = mysql_fetch_array( $result );
		$value = ($record) ? $record[ 0 ] : NULL;
	} else $value = NULL;
	return( $value );
}


function temp_header_insert( $p_header_data, $db_conn )
// return TRUE if no errors, else FALSE
{

if (DEBUG) message_log_append( "header: insert" );

	$qry = "DROP TABLE temp_header";
	$success = mysql_query( $qry, $db_conn );

	$qry = "CREATE TABLE temp_header LIKE contract_header";
	$success = mysql_query( $qry, $db_conn );

	if ($success) {
		$seqn = last_insert_id( $db_conn );
if (DEBUG) message_log_append( "header: seqn = $seqn" );
		$success = (!is_null( $seqn ));
	} else {
		message_log_append( mysql_error(), LOG_ERROR );
	}

	if ($success) {
		$qry = "INSERT INTO temp_header " . $p_header_data;
if (DEBUG) message_log_append( "header: $qry" );
		if (!($success = mysql_query( $qry, $db_conn )))
			message_log_append( mysql_error(), LOG_ERROR );
if (DEBUG) message_log_append( "header info: " . mysql_error(), LOG_ERROR );
	}
if (DEBUG) $seqn = last_insert_id( $db_conn );
if (DEBUG) message_log_append( "header: new_seqn = $seqn" );

	return( $success );

} // temp_header_insert


function array_of_networks_ordered( $db_conn )

// build an array of the network aliases from the order detail lines.
// no duplicates, just one occurrence of each different alias.
// return NULL if error, otherwise an array 0..n-1 of n networks
// on the order.

{
	$network_array = NULL;
	$qry = "SELECT DISTINCT Network FROM temp_detail";
	$result = mysql_query( $qry, $db_conn );
	if ($result) {
		$n_nets = 0;
		$network_array = array();
		while ($row = mysql_fetch_array( $result )) {
			$network_array[ $n_nets++ ] = $row[ "Network" ];
		} // while
if (DEBUG) echo "<br>nets ordered<br>";
if (DEBUG) var_dump( $network_array );
	} else {
		message_log_append( mysql_error(), LOG_ERROR );
	} // if result

	return( $network_array );

} // array_of_networks_ordered


function array_of_network_aliases( $p_sitename, $db_conn )

// build an array of the network names aliases available at 
// $p_sitename.  No duplicates, just one occurrence of each.
// The alias is the array key, and the native network is
// the array element value, such that $a[ 'alias' ] => 'native'
// return NULL if error, otherwise an associative array of 
// networks at the site.

{
	$network_array = NULL;

	$qry = "SELECT Networks FROM registration WHERE SiteName = '$p_sitename'";
	$result = mysql_query( $qry, $db_conn );

	if ($result) {

		$net_string = "";
		while ($row = mysql_fetch_array( $result )) {
			$net_string .= $row[ 0 ] . " ";
		} // while

// Yuk!  That's a string similar to:
// DISC-E* ESPN    SPIK-E* TLC-E   TBS-E*  TNT-E*  USA-E   CNN*
// the asterisks are not meaningful for now, and should be stripped.
		$net_string = str_replace( "*", " ", $net_string );
		$net_string = trim( $net_string );
// split that string into an array, delimited wherever one or
// more spaces is found
		$string_array = preg_split( '/  */', $net_string );

// now step through that string_array, and look up each one in
// the networks table.  Create our final $network_array with 
// the array key being the alias tag, and the array element value
// being the native network tag.
		$network_array = array();
		$j = 0;
		while (!is_null( $native = $string_array[ $j ] )) {
			$alias = network_alias( $native, $db_conn );
			$network_array[ $alias ] = $native;
			$j++;
		} // while
if (DEBUG) echo "<br>nets available<br>";
if (DEBUG) var_dump( $network_array );

	} else { // result is false:

		message_log_append( mysql_error(), LOG_ERROR );

	} // if result

	return( $network_array );

} // array_of_network_aliases


function temp_detail_insert( $p_detail_data, $db_conn, $p_cindex )
// return TRUE if no errors, else FALSE
{

if (DEBUG) message_log_append( "detail insert" );

	$success = TRUE;

	$qry = "DROP TABLE temp_detail";
	$success = mysql_query( $qry, $db_conn );

	$qry = "CREATE TABLE temp_detail LIKE contract_detail";
	if (!($success = mysql_query( $qry, $db_conn )))
		message_log_append( mysql_error(), LOG_ERROR );

	if ($success) {
		$seqn = last_insert_id( $db_conn );
if (DEBUG) message_log_append( "detail: seqn = $seqn" );
		$success = (!is_null( $seqn ));
	}

        if ($success) {
		$qry = "INSERT INTO temp_detail " . $p_detail_data;
if (DEBUG) message_log_append( "detail: $qry" );
		$success = mysql_query( $qry, $db_conn );
		if (!$success) message_log_append( mysql_error(), LOG_ERROR );
if (DEBUG) message_log_append( "detail info: " . mysql_error(), LOG_ERROR );
	}

        if ($success) {
		$new_seqn = last_insert_id( $db_conn );
if (DEBUG) message_log_append( "detail: new_seqn = $new_seqn" );
		$success = (!is_null( $new_seqn ));
	}

        if ($success) {
// Update the detail records to reference the $p_cindex we were passed
		$qry = "UPDATE temp_detail SET Contract = $p_cindex";
		$success = mysql_query( $qry, $db_conn );
	}

	return( $success );

} // temp_detail_insert


function insert_temp_data( $p_header_file_name, 
			   $p_detail_file_name )

// insert the header and detail data into temporary tables
// to facilitate review of the data prior to importation

{
GLOBAL $db_conn;

	$header_insert_string = file_get_contents( $p_header_file_name );
	$detail_insert_string = file_get_contents( $p_detail_file_name );

//message_log_append( "Header:<br>$header_insert_string" );
//message_log_append( "Detail:<br>$detail_insert_string" );

	$success = TRUE;

// The header insert brings data into a temporary table.
// It returns TRUE on success or FALSE on failure.

	$cindex = temp_header_insert( $header_insert_string, $db_conn );
	$site_name = temp_header_site_name( $db_conn );

	$success = (!is_null( $cindex ));
	if (!$success)
		message_log_append( "temp_header_insert failed", LOG_ERROR );

//message_log_append( "CIndex: $cindex" );

// Pass the CIndex value to the detail insert routine.

	if ($success) {
		$success = temp_detail_insert( $detail_insert_string, $db_conn, $cindex );
		if (!$success)
			message_log_append( "temp_detail_insert failed", LOG_ERROR );
	}

// Sum the total spots and total value of this contract
	if ($success) {
		$qry = "SELECT SUM( nSched ) as N, SUM( nSched * UnitPrice ) as Value " .
			"FROM temp_detail";
		if (($result = mysql_query( $qry, $db_conn )) && 
			($row = mysql_fetch_array( $result ))) {
//	UnitPrice is in pennies
			$n = $row[ "N" ];
			$v = $row[ "Value" ] / 100;
			message_log_append( "Total spots: " . $n );
			message_log_append( "Total value: " . sprintf( "%.02f", $v ) );
		} else {
			message_log_append( mysql_error(), LOG_ERROR );
			$success = FALSE;
		}
	}

	if ($success) {
//		$qry = "UPDATE temp_header SET TotalValue = " . $v;
	}

	if ($success) {
//  $order_nets is a numeric-indexed array of customer network aliases
		$order_nets = array_of_networks_ordered( $db_conn );
//  $alias_nets is a text-indexed array that maps an alias to an on-site network
		$alias_nets = array_of_network_aliases( $site_name, $db_conn );
		$j = 0;
		while (!is_null( $net = $order_nets[ $j++ ] )) {
//			echo "order calls for " . $net . " / ";
			switch (TRUE) {
			case (is_null( $alias_nets[ $net ] )):
// this is a problem
				message_log_append( "Network '$net' is not available " .
					"at site $site_name", LOG_ERROR );
				$qry = "DELETE FROM temp_detail WHERE Network = '" .
					$net . "'";
				if (!mysql_query( $qry, $db_conn )) {
					message_log_append( mysql_error(), LOG_ERROR );
					$success = FALSE;
				}
				break;

			case ($net != $alias_nets[ $net ]):
				message_log_append( "Network '$net' mapped to '" . 
					$alias_nets[ $net ] . "'", LOG_WARNING );
				$qry = "UPDATE temp_detail SET Network = '" .
					$alias_nets[ $net ] . "' WHERE Network = '" .
					$net . "'";
				if (!mysql_query( $qry, $db_conn )) {
					message_log_append( mysql_error(), LOG_ERROR );
					$success = FALSE;
				}
			} // switch
		} // while
	}

	return( NULL );

} // insert_temp_data


