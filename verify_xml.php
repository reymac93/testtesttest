<?php
if($Roger>0) {echo ("<br/>File: " . dirname(__FILE__) . ", Line: " . __LINE__ . "<br/>");}
//function array_of_networks_ordered( $p_detail_array )
//
//// build an array of the network aliases from the order detail 
//// line passed.  $p_detail_array holds just one contract.
//// we want no duplicates, just one occurrence of each different
//// alias.
//// return NULL if error, otherwise an array 0..n-1 of n networks
//// on the order.
//
//{
//
//        $network_array = array();
//
//// build the array using the network ID as the key.
//// then return the array of keys, and we'll have no dupes.
//
//	foreach ($p_detail_array as $line)
//		$network_array[ $line[ 'XMLNetwork' ] ] = 1;	// any value will do
//
//	return( array_keys( $network_array ) );
//
//} // array_of_networks_ordered

function array_of_native_networks( $p_sitename, $db_conn, $p_msg_log )
// build an array of the network name aliases available at 
// $p_sitename.  No duplicates, just one occurrence of each.
// The alias is the array key, and the native network is
// the array element value, such that $a[ 'alias' ] => 'native'
// return NULL if error, otherwise an associative array of 
// networks at the site.
{
GLOBAL $DEBUG;

	$network_array = NULL;

	$qry = "SELECT Networks FROM registration WHERE SiteName = '$p_sitename'";
	$result = mysqli_query($db_conn,$qry);
	if ($result) 
	{

		$net_string = "";
		while ($row = mysqli_fetch_array( $result )) 
		{
			$net_string .= $row[ 0 ] . " ";
		} // while

// That's a string similar to:
// DISC-E* ESPN    SPIK-E* TLC-E   TBS-E*  TNT-E*  USA-E   CNN*

// convert the asterisks to whitespace
		$net_string = trim( str_replace( "*", " ", $net_string ));

// split string into an array, delimited wherever one or more spaces is found
		$string_array = preg_split( '/  */', $net_string );

// now step through that string_array, and look up each one in
// the network database table.  Create our final $network_array with 
// the array key being the alias tag, and the array element value
// being the native network tag.
		$network_array = array();
		foreach ($string_array as $native) 
		{
			$alias = network_alias( $native, $db_conn );
			$network_array[ $alias ] = $native;
		} // foreach

//if ($DEBUG) {
//echo "<br>nets available<br>";
//var_dump( $network_array ); }

	} 
	else 
	{ // result is false:
		message_log_append( $p_msg_log, "Can't find SiteName $p_site_name: " . mysql_error(), MSG_LOG_ERROR );
	} // if result
	return( $network_array );
} // array_of_native_networks

function header_version_check( $p_head_array, &$p_msg_log )
{
//GLOBAL $DEBUG;
//if ($DEBUG)
//echo "<pre>begin header_version_check systemOrder " . $p_head_array['SystemOrder'] . "\n</pre>";

	$done    = FALSE;
	$success = TRUE;
	$j       = 1;

	while ($success && !$done) 
	{
		$fld = "version" . $j++;
		$ver = isset( $p_head_array[ $fld ] ) ? $p_head_array[ $fld ] : NULL;
		switch (TRUE) 
		{
		case (is_null( $ver )):
			$done = TRUE; // no more to check
//if ($DEBUG)
//echo "<pre>HVC: $fld ver is null\n</pre>";
			break;
		case ($ver == "1"):
		case (strlen( $ver ) == 0):
//if ($DEBUG)
//echo "<pre>HVC: $fld ver is okay\n</pre>";
			break;
		default:
//if ($DEBUG)
//echo "<pre>HVC: $fld ver fails ($ver)\n</pre>";
			$msg     = 'XML System Order version number must be 1, ' .
					'value found is ' . 
					(is_null( $ver ) ? 'NULL' : $ver);
                        message_log_append( $p_msg_log, $msg, MSG_LOG_ERROR );
			$success = FALSE;
		} // switch
	} // while
	return( $success );
} // header_version_check

function detail_time_validation( &$det_array, $p_cust_name )
// $det_array is one contract's detail array.  we might modify the
// caller's data, such as to convert 00:00:00 to 24:00:00.
// Also, we will decrement the minute of the TimeOff so that x:00
// becomes (x-1):59 and x:30 becomes x:29.
// Finally, we will figure out the Priority field value and
// update the det_array row with that value.
{
GLOBAL $msg_log;
GLOBAL $DEBUG;

// Dates must be YYYY-MM-DD so that "<" comparison will work correctly.

	$success = TRUE;	// assume no problems
//if ($DEBUG)
//echo "<pre>";

// In the XML file, the startTime and endTime must be on 30-minute boundaries:
	$time_boundary = 30;

//if ($DEBUG)
//{ echo "det_array:\n"; var_dump( $det_array ); }
	$det_keys = array_keys( $det_array );
	foreach ($det_keys as $key) 
	{ // each line item's array key
//if ($DEBUG)
//echo "det line $key:\n";
		$line = $det_array[ $key ];	// one detail line
//if ($DEBUG)
//var_dump( $line );
		$s1  = $line[ 'StartDate' ] . ' ' . $line[ 'XMLstartTime'  ];
		$s2  = $line[ 'EndDate'   ] . ' ' . $line[ 'XMLendTime' ];
//if ($DEBUG)
//echo "Raw Start/End dates: $s1/$s2\n";
		$dt1 = date_parse( $s1 );
		$dt2 = date_parse( $s2 );

// validate means no errors, no warnings
// check that neither date has minutes, seconds or fraction of seconds
		$success = ($dt1[ 'error_count'   ] === 0) && 
			   ($dt2[ 'error_count'   ] === 0) &&
			   ($dt1[ 'warning_count' ] === 0) &&
			   ($dt2[ 'warning_count' ] === 0) &&
			   ($dt1[ 'second'        ] === 0) &&
			   ($dt2[ 'second'        ] === 0) &&
			   ($dt1[ 'fraction'      ] == 0) &&
			   ($dt2[ 'fraction'      ] == 0);

		if ($success && ($dt2[ 'hour' ] === 0) && ($dt2[ 'minute' ] === 0)) 
		{
// modify TimeOff == 00:00 to be 24:00
			$msg = "changing endTime='00:00' to '24:00'";
//if ($DEBUG)
//echo "$msg\n";
			$dt2[ 'hour'   ] = 24;
			$dt2[ 'minute' ] =  0;
			$det_array[ $key ][ 'TimeOff' ] = '24:00:00';
		} // if
		if ($success & ($dt2[ 'hour' ] === 23) && ($dt2[ 'minute' ] === 59)) 
		{
//// modify TimeOff == 23:59 to be 24:00
			$msg = "changing endTime='23:59' to '24:00'";
//if ($DEBUG)
//echo "$msg\n";
			$dt2[ 'hour'   ] = 24;
			$dt2[ 'minute' ] =  0;
			$det_array[ $key ][ 'TimeOff' ] = '24:00:00';
		}
		$success = $success && 
				($dt1[ 'minute'        ] % $time_boundary === 0) &&
				($dt2[ 'minute'        ] % $time_boundary === 0);

		if ($success) 
		{
// Check that StartDate <= EndDate
			$s1 = $line[ 'StartDate' ];
			$s2 = $line[ 'EndDate'   ];
			$success = ($s1 <= $s2);	// equal date is okay
			if (!$success) 
			{
				$msg = "EndDate earlier than StartDate";
//if ($DEBUG)
//echo "$msg\n";
// For now, we'll log this error at the global level, but with a null string.
// This will cause the contract to flag red.
				message_log_append( $msg_log, '', MSG_LOG_ERROR );
// log error to the specific detail line
               	message_log_append( $det_array[ $key ][ 'MSG_LOG' ], $msg, MSG_LOG_ERROR );
			}
		} 
		else 
		{
			$msg = "Invalid run dates/times: $s1 / $s2";

			////$DEBUG = "ERROR1";  ///reynan need to enable this
//if ($DEBUG)
//echo "$msg\n";
// For now, we'll log this error at the global level, but with a null string.
// This will cause the contract to flag red.
			message_log_append( $msg_log, '', MSG_LOG_ERROR );
// log error to the specific detail line
            message_log_append( $det_array[ $key ][ 'MSG_LOG' ], $msg, MSG_LOG_ERROR );
		}

		if ($success) 
		{
// mktime appears to be slow....  will this work
// as accurately, but faster?
//			$timeon  = mktime( $dt1[ 'hour' ], $dt1[ 'minute' ], $dt1[ 'second' ],
//						0, 0, 0 ); // month, day, year
			$timeon = $dt1['second'] + $dt1['minute']*60 + $dt1['hour']*60*60;
//			$timeoff = mktime( $dt2[ 'hour' ], $dt2[ 'minute' ], $dt2[ 'second' ],
//						0, 0, 0 ); // month, day, year
			$timeoff = $dt2['second'] + $dt2['minute']*60 + $dt2['hour']*60*60;

            $window_length = ($timeoff - $timeon) / 60;	// in minutes
			$unit_price = $line[ 'UnitPrice' ];

//if ($DEBUG)
//echo "On  " . $det_array[ $key ][ 'TimeOn'  ] . ' ' . $timeon  . "\n";
//if ($DEBUG)
//echo "Off " . $det_array[ $key ][ 'TimeOff' ] . ' ' . $timeoff . "\n";
//if ($DEBUG)
//echo "Len " . $window_length . "\n";
//if ($DEBUG)
//echo "Cus '" . $p_cust_name . "'\n";

			$s = sprintf( '%02d:%02d', $dt1[ 'hour' ], $dt1[ 'minute' ] );
			$det_array[ $key ][ 'TimeOn' ] = $s;
			$s1 = $s;

			if ($dt2[ 'minute' ] == 0)
			{
			    $s = sprintf( '%02d:%02d', $dt2[ 'hour' ] - 1, 59 );
			}
			else
			{
			    $s = sprintf( '%02d:%02d', $dt2[ 'hour' ], $dt2[ 'minute' ] - 1 );
			}
			$det_array[ $key ][ 'TimeOff' ] = $s;
			$s2 = $s;

// check that TimeOn < TimeOff
//			$s1 = $det_array[ $key ][ 'TimeOn'  ];
//			$s2 = $det_array[ $key ][ 'TimeOff' ];
			$success = ($s1 < $s2);		// equal time is not okay

			if (!$success) 
			{
				$msg = "TimeOn must be earlier than TimeOff";
//if ($DEBUG)
//echo "$msg\n";
// For now, we'll log this error at the global level, but with a null string.
// This will cause the contract to flag red.
				message_log_append( $msg_log, '', MSG_LOG_ERROR );
// log error to the specific detail line
               	message_log_append( $det_array[ $key ][ 'MSG_LOG' ], $msg, MSG_LOG_ERROR );
			}
		}

// check that StartDate is a Monday, EndDate is Sunday
// re-use $dt1 and $dt2 arrays from above
		$t1 = mktime( 0, 0, 0, // hour, min, sec
			$dt1['month'], $dt1['day'], $dt1['year'] );
		$t2 = mktime( 0, 0, 0, // hour, min, sec
			$dt2['month'], $dt2['day'], $dt2['year'] );
		$t1 = date( 'w', $t1 );		// 0 = Sun, 6 = Sat
		$t2 = date( 'w', $t2 );		// 0 = Sun, 6 = Sat
		$t1 = ($t1 - 1 + 7) % 7;	// 0 = Mon, 6 = Sun
		$t2 = ($t2 - 1 + 7) % 7;	// 0 = Mon, 6 = Sun
		if ($t1 != 0 || $t2 != 6) 
		{
			$success = TRUE;
			$msg = "StartDate must be Monday, EndDate must be Sunday";
//if ($DEBUG)
//echo "$msg\n";
// For now, we'll log this error at the global level, but with a null string.
// This will cause the contract to flag red.
			//message_log_append( $msg_log, '', MSG_LOG_ERROR );
// log error to the specific detail line
            //message_log_append( $det_array[ $key ][ 'MSG_LOG' ], $msg, MSG_LOG_ERROR );
		} // if

		if ($success) 
		{
            $priority = "NULL";
            switch (TRUE) 
			{
// 0 price is priority 4
                case ($unit_price == 0):
                    $priority = 4;
                    break;
// 3 hours or less means priority 1
                case ($window_length <= 180):
                    $priority = 1;
                    break;
// 7 hours or less means priority 2
                case ($window_length <= 420):
                    $priority = 2;
                    break;
// Customer TelAmerica is priority 5
                case ($p_cust_name == TELAMERICA):
                    $priority = 5;
                    break;
// Anything else is priority 3
                default:
                    $priority = 3;
            } // switch
			$det_array[ $key ][ 'Priority' ] = $priority;
//echo "<pre>priority: $priority\n"; var_dump( $det_array[ $key ] ); echo "</pre><br>";
		} // if
	} // foreach

//if ($DEBUG)
//echo "</pre>";
	return( $success );
} // detail_time_validation

function detail_network_validation( &$p_msg_log, &$p_detail, $p_site_name, $p_telamerica )
// $p_detail is one contract's detail lines.
// $p_site_name is the sitename for this contract.
{
GLOBAL $db_conn;
GLOBAL $DEBUG;

	$success = TRUE;

////  $order_nets is a numeric-indexed array of customer network aliases
//	$order_nets = array_of_networks_ordered( $p_detail );

//  $native_nets is an associative array that maps an alias net (index)
//  to a native (element value) on-site network

//  pass $p_msg_log to array_of_native_networks for potential error logging
	$native_nets = array_of_native_networks( $p_site_name, $db_conn, $p_msg_log );

	$j = 0;
	$keys =  array_keys( $p_detail );
	foreach ($keys as $key) 
	{
		$alias = $p_detail[ $key ][ 'XMLNetwork' ];
		if (isset( $native_nets[ $alias ] )) 
		{
			$p_detail[ $key ][ 'Network' ] = $native = $native_nets[ $alias ];
			if ($alias != $native) 
			{
				$msg = "Network '$alias' mapped to '" .  $native . "'";
// log to $p_msg_log
				message_log_append( $p_msg_log, '', MSG_LOG_WARNING );
// and log again to the specific detail line
				message_log_append( $p_detail[ $key ]['MSG_LOG'], $msg, MSG_LOG_WARNING );
			}
		} 
		else 
		{
			$success = FALSE;	// darn!
			$p_detail[ $key ][ 'Network' ] = $alias;
			$msg = "Network '$alias' is not available at site $p_site_name";
// Log the specific error message at the detail level.
			message_log_append( $p_detail[ $key ][ 'MSG_LOG' ],	$msg, MSG_LOG_ERROR, ERR_NO_NETWORK );
// log this error at the header level, but with a null string.
// This will cause the contract to flag red.
			message_log_append( $p_msg_log, '', MSG_LOG_ERROR, ERR_NO_NETWORK );
		} // if

	} // foreach
	return( $success );
} // detail_network_validation

function verify_one_contract( $campaign, &$header, &$detail )
// check one contract's $campaign, $header and $detail arrays
// the header array is passed by reference because
// we will add detail_spots and detail_cost fields to the
// header.
// the detail array is passed by reference because 
// detail_time_validation will add the Priority element to
// each row, and MSG_LOG entries could be added.
// return TRUE if no errors, else FALSE
{
//GLOBAL $DEBUG;

	$success     = TRUE;
	$done        = FALSE;
	$test_number = 0;

// total_spots is the XML reference field in the contract header:
	$total_spots = $header['total_spots'];
// total_cost is the XML reference field in the contract header:
	$total_cost  = $header['total_cost'];

	message_log_reset( $h_msg_log ); // local $h_msg_log specific to this header

//  sum spots and cost across all the detail lines passed to us
	$det_spots = 0;
	$det_cost  = 0;

	foreach ($detail as $det) 
	{
		$det_spots += $det[ 'nOrdered' ];
// calculate values with binary precision arithmetic routines
		$v = bcmul( bcmul( $det[ 'nOrdered' ], $det[ 'UnitPrice' ], 2 ), '0.01', 2 );
		$det_cost = bcadd( $det_cost, $v, 2 );
	} // foreach

// Add 'detail_spots' and 'detail_cost' fields to header array.
// These fields will hold the total spots and costs as summed from
// individual detail lines.  These should agree with 'total_spots'
// and 'total_cost' (which are parsed directly from the XML) but we 
// will check that (elsewhere).

    $header[ 'detail_spots' ] = $det_spots;
    $header[ 'detail_cost'  ] = $det_cost;

//echo "test number " . ($test_number + 1) . "\n";

	while (!$done) 
	{

		switch (++$test_number) 
		{
		case 1:
			break;
// per 11-06-2012 email from Carolyn Boyer to Dustin Carlson,
// we no longer care about header version numbers.
		case 2:
			break;
// all contract headers must be version 1
//if ($DEBUG)
//echo "<pre>VOC: calling header_version_check\n</pre>\n";
			if (header_version_check( $header, $h_msg_log )) ;
			else {
				$success = FALSE;
			}
//			$done = (!$success);	// don't continue if this fails
			break;
		case 3:
// $header totals must match sum of detail
			if ($header[ 'detail_spots' ] != $header[ 'total_spots' ]) 
			{
				$msg = "Total number of spots doesn't match";
				message_log_append( $h_msg_log, $msg, MSG_LOG_ERROR );
//if ($DEBUG)
//echo "<pre>$msg\n</pre>\n";
			}
			if ($header[ 'detail_cost' ] != $header[ 'total_cost' ]) 
			{
				$msg = "Total value of spots doesn't match";
				message_log_append( $h_msg_log, $msg, MSG_LOG_ERROR );
//if ($DEBUG)
//echo "<pre>$msg\n</pre>\n";
			}
			break;
		case 4:
			break;
		case 5:
// validate all detail lines' StartDate/TimeOn against EndDate/TimeOff
// To figure the priority, we must know the customer name 
// for this contract.  The customer record is stored in 
// the header array.  Pass the Name field as a parameter.
			$cust_name = $campaign[ 'Customer Name' ];
			if (!detail_time_validation( $detail, $cust_name )) 
			{
//echo "Received FALSE from DTV<br>";
				$success = FALSE;
// make the contract flag as red
               	message_log_append( $h_msg_log, '', MSG_LOG_ERROR );
			} // if
			break;
// Let's look at the networks on these orders.
// All networks on the order have to map to
// networks available at the sitename/syscode.
		case 6:
			if (!detail_network_validation( $h_msg_log, $detail, $header[ 'SiteName' ], $campaign[ 'TELAMERICA' ] )) 
			{
				$success = FALSE;
			} // if
			break;
// total number of spots and weeks on each order must be in range
		case 7:
			$weeks = $header[ 'week_count'  ];
			$spots = $header[ 'total_spots' ];
//if ($DEBUG)
//echo "week_count check, header ($weeks)\n";
			if ($weeks > 0 && $weeks <= MAX_WEEKS) ;
			else {
				$success = FALSE;
				$msg     = "Number of weeks ($weeks) is out of range";
                        	message_log_append( $h_msg_log, $msg, MSG_LOG_ERROR );
			} // if
//if ($DEBUG)
//echo "spot count check, header ($spots)\n";
			if ($spots > 0 && $spots <= MAX_SPOTS) ;
			else if ($spots == 0) {
				$msg     = "ZERO spots on this contract";
                        	message_log_append( $h_msg_log, $msg, MSG_LOG_WARNING );
			} else {
				$success = FALSE;
				$msg     = "Number of spots ($spots) is out of range";
                        	message_log_append( $h_msg_log, $msg, MSG_LOG_ERROR );
			} // if
			break;	
		case 8:
			break;
		case 9:
			break;
		case 10:
			break;
		default:
			$done = TRUE;
		} // switch
	} // while

	if (isset( $header[ 'MSG_LOG' ] ))
	{
		die( 'verify_one_contract: header already has a message log' );
	}
	else 
	{
//if ($DEBUG)
//echo "<pre>Setting header[MSG_LOG]\n</pre>\n";
		$header[ 'MSG_LOG' ] = $h_msg_log;
	}

//if ($DEBUG) {
//echo "Returning " . ( $success ? 'TRUE' : 'FALSE' ) . " from VOC<br>";
//echo message_log_format( $header[ 'MSG_LOG' ] ); }
	return( $success );
} // verify_one_contract


function verify_all_contracts( &$campaign_array, &$header_array, &$detail_array )
// Examine the header and detail arrays and attempt to find
// all possible flaws with the data.
// Return TRUE if no flaws can be found, else FALSE.
{
GLOBAL $msg_log;
//GLOBAL $DEBUG;

// for starters, the two arrays must be indexed the same.
// $header_array is an array of rows.  $detail_array is an
// $array of arrays.  Each row in $detail_array is in fact 
// an array of rows, such that the rows in $detail_array[x]
// correspond to the header row in $header_array[x].

	$success = TRUE;
//if ($DEBUG) { echo "<pre>VAC: detail_array:\n"; var_dump( $detail_array ); echo "</pre>\n"; die(); }

// Before we do anything, the header array and detail array keys must match
	$header_keys = array_keys( $header_array );
	$detail_keys = array_keys( $detail_array );
	if ($header_keys != $detail_keys) 
	{
		$success = FALSE;
		$msg     = "Internal error: header and detail array indexes differ";
		message_log_append( $msg_log, $msg, MSG_LOG_ERROR );
		if ($DEBUG){ echo "<pre>VAC keys don't match: header_keys:\n"; var_dump( $header_keys ); echo "</pre>\n"; }
		if ($DEBUG){ echo "<pre>VAC: detail_keys:\n"; var_dump( $detail_keys ); echo "</pre>\n"; }
	} // if
	$done        = (!$success);
	if (!$done) 
	{
//if ($DEBUG)
//{ echo "<pre>VAC: header_keys: "; var_dump( $header_keys ); echo "</pre>\n"; }
//		message_log_reset( $msg_log );
		foreach ($header_keys as $key) 
		{
			$hdr = $header_array[ $key ];
			$det = $detail_array[ $key ];
// The code below that thinks one header record can have multiple
// site records is really cumbersome and confuses some assumptions
// made elsewhere.

// The header array could have multiple Site Records.
// We'll store one Log for each Site Record, in an array
// that is indexed to match the $hdr['Site Records'] array.
			$header_array[ $key ][ 'MSG_LOG' ] = array();
			foreach ($hdr[ 'Site Records' ] as $site_record) 
			{
// kludge!
				$hdr[ 'SiteName' ] = $site_record[ 'SiteName' ];
				if (verify_one_contract( $campaign_array[ 0 ], $hdr, $detail_array[ $key ] )) 
				{
					;
				} 
				else 
				{
					$success = FALSE;
				} // if
				$header_array[ $key ][ 'MSG_LOG' ][] = $hdr[ 'MSG_LOG' ];
//				message_log_reset( $msg_log );
			} // foreach site_record
			$header_array[ $key ][ 'detail_spots' ] = $hdr[ 'detail_spots' ];
			$header_array[ $key ][ 'detail_cost'  ] = $hdr[ 'detail_cost' ];
		} // foreach header
	} // if
	return( $success );
} // verify_all_contracts

?>
