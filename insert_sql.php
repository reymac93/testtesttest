<?php

    ini_set('memory_limit', '2048M');
    ini_set('max_execution_time', '1800000');
	set_time_limit(1800000);
	ini_set("display_errors", "on");

if($Roger>0) {echo ("<br/>File: " . dirname(__FILE__) . ", Line: " . __LINE__ . "<br/>");}

define( 'I_DEBUG', FALSE );

function pre_echo( $s )
{
//	echo "<pre>$s</pre>";
}

function string_field( $s )
// return $s with a quote mark at both ends.  Eventually this
// should have smarts to do all necessary escaping of special
// characters in string $s
{
	$result = '"' . $s . '"';
	return( $result );
}

function build_header_field_array()
// Return an array of rows where [n][0] is a field name,
// and [n][1] is a boolean indicating when TRUE that the
// field needs to be enclosed in quotes.
{
// what is the sequence of field names?
	$p_fld_array = array();

	$p_fld_array[] = array( 'CIndex',        FALSE );
	$p_fld_array[] = array( 'ContractName',  TRUE  );
	$p_fld_array[] = array( 'SiteName',      TRUE  );
	$p_fld_array[] = array( 'StartDate',     TRUE  );
	$p_fld_array[] = array( 'EndDate',       TRUE  );
	$p_fld_array[] = array( 'AgencyComm',    FALSE );
	$p_fld_array[] = array( 'Discount',      FALSE );
	$p_fld_array[] = array( 'AIndex',        FALSE );
	$p_fld_array[] = array( 'TotalValue',    FALSE );
	$p_fld_array[] = array( 'Attributes',    FALSE );
	$p_fld_array[] = array( 'CustOrder',     TRUE  );
	$p_fld_array[] = array( 'SIndex',        FALSE );
	$p_fld_array[] = array( 'SalesComm',     FALSE );
    $p_fld_array[] = array( 'MinSeparation', FALSE );
	return( $p_fld_array );
} // build_header_field_array

function build_detail_field_array()
// Return an array of rows where [n][0] is a field name,
// and [n][1] is a boolean indicating when TRUE that the
// field needs to be enclosed in quotes.
{
// what is the sequence of field names?
	$p_fld_array = array();

//	$p_fld_array[] = array( 'Line',         FALSE );
	$p_fld_array[] = array( 'Contract',     FALSE );
	$p_fld_array[] = array( 'Network',      TRUE  );
	$p_fld_array[] = array( 'StartDate',    TRUE  );
	$p_fld_array[] = array( 'EndDate',      TRUE  );
	$p_fld_array[] = array( 'TimeOn',       TRUE  );
	$p_fld_array[] = array( 'TimeOff',      TRUE  );
	$p_fld_array[] = array( 'Distribution', TRUE  );
	$p_fld_array[] = array( 'Bonus',        FALSE );
	$p_fld_array[] = array( 'Priority',     FALSE );
	$p_fld_array[] = array( 'UnitPrice',    FALSE );
	$p_fld_array[] = array( 'nWeeks',       FALSE );
	$p_fld_array[] = array( 'Value',        TRUE  );
	$p_fld_array[] = array( 'nSched',       FALSE );
	$p_fld_array[] = array( 'nPlaced',      FALSE );
	$p_fld_array[] = array( 'nPlayed',      FALSE );
	$p_fld_array[] = array( 'ActualValue',  FALSE );
	$p_fld_array[] = array( 'ProgramName',  TRUE  );
	$p_fld_array[] = array( 'StartDay',     FALSE );
	$p_fld_array[] = array( 'EndDay',       FALSE );
	$p_fld_array[] = array( 'MakeGoods',    FALSE );
	$p_fld_array[] = array( 'MakeGoodDays', FALSE );
	$p_fld_array[] = array( 'nOrdered',     FALSE );
	$p_fld_array[] = array( 'LineID',       TRUE  );
	$p_fld_array[] = array( 'UseStartDate', TRUE  );
	return( $p_fld_array );
} // build_detail_field_array

function data_values( $p_fld_array, $p_array )
// return the data row values in the sequence
// given by p_fld_array, in the format an INSERT statement
// requires.
{
	$s = '';
	foreach ($p_fld_array as $fld) 
	{
		switch (TRUE) 
		{
		case ($fld[ 1 ]):
			$val = string_field( $p_array[ $fld[0] ] );
			break;
		default:
			$val = $p_array[ $fld[0] ];
		} // switch
		$s .= ', ' . $val;
	} // foreach fld
	$s = substr( $s, 2 );	// remove leading comma-space
	return( $s );
} // data_values

function fld_list( $p_fld_array )
// take the field array and return a comma-separated
// list of field names.
{
	$s = '';
	foreach ($p_fld_array as $fld)
	{
		$s .= ', ' . $fld[0];
	}
	$s = substr( $s, 2 );	// remove leading comma-space
	return( $s );
}


function agency_insert( $db_conn, $p_name, $p_rate, &$p_aindex )
{
GLOBAL $msg_log;
// create a new, minimal agency record with a name and a rate.
// if successful return TRUE and update $p_aindex, else FALSE.

	$old_seq = last_insert_id( $db_conn ); // note this value
	$success = !is_null( $old_seq );
	$qry = "INSERT INTO agencies (Name,Rate) VALUES ('$p_name',$p_rate)";
//if ($success) pre_echo( $qry );
	$success = ($success && mysqli_query($db_conn,$qry));
	if ($success) 
	{
		$agent_seq = last_insert_id( $db_conn );
		if ($success = (($agent_seq <> $old_seq) && !is_null( $agent_seq )))
		{
			$p_aindex = $agent_seq;
		}
	}
	return( $success );
} // agency_insert

function customer_insert( $db_conn, $p_name, $p_discount, &$p_cindex )
{
GLOBAL $msg_log;
// create a new, minimal customer record with a name and a discount rate.
// if successful return TRUE and update $p_cindex, else FALSE.
	$old_seq = last_insert_id( $db_conn ); // note this value
	$success = !is_null( $old_seq );
	$qry = "INSERT INTO customers (Name,Discount) VALUES ('$p_name',$p_discount)";
//if ($success) pre_echo( $qry );
	$success = ($success && mysqli_query($db_conn,$qry));
	if ($success) 
	{
		$cust_seq = last_insert_id( $db_conn );
		if ($success = (($cust_seq <> $old_seq) && !is_null( $cust_seq )))
		{
			$p_cindex = $cust_seq;
		}
	}
	return( $success );
} // customer_insert

function do_insert( $db_conn, $header_insert_stmt, $detail_insert_stmt )
// Perform the inserts contained in $header_insert_stmt and
// $detail_insert_stmt.  That will move data into temporary
// tables (because the INSERTs are formatted that way by
// the caller).

// After the data is in the temp tables, try to insert the temp_header
// table into the production header table.  If successful, not the
// new Seq assigned, and update the temp_detail table to reference
// that value in each record's Contract field.

// Then, attempt to insert the temp_detail records into the
// production detail table.
{
GLOBAL $msg_log;
	if (I_DEBUG) {message_log_append( $msg_log, " begin do_insert" );}
	$success = TRUE;
// Delete all temp_header and temp_detail records
	if ($success) 
	{
		$qry = "DELETE FROM temp_header";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);	// true if success
		if (!$success)
		{
			message_log_append( $msg_log, "do_insert: delete from temp_header failed: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{
		$qry = "DELETE FROM temp_detail";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);	// true if success
		if (!$success)
		{
			message_log_append( $msg_log, "do_insert: delete from temp_detail failed: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

// Attempt the temp header insert
	if ($success) 
	{
		$qry = $header_insert_stmt;
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);	// true if success
		if (!$success)
		{
			message_log_append( $msg_log, "do_insert: temp header insert failed: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

// Attempt the temp detail insert
	if ($success) 
	{
		$qry = $detail_insert_stmt;
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);	// true if success
		if (!$success)
		{
			message_log_append( $msg_log, "temp detail insert failed: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

// Attempt to insert temp_header into contract_header
	if ($success) 
	{ // Set $fld_list to the list of fields in table temp_header
		$fld_list = field_list( "temp_header", $db_conn );
		$success = (!is_null( $fld_list ));
		if (!$success)
		{
			message_log_append( $msg_log, "field_list of temp_header failed", MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{
		$success = !is_null( $old_seq = last_insert_id( $db_conn ) );
		if (!$success)
		{
			message_log_append( $msg_log, "do_insert: last_insert_id failed(1)", MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{
//if (I_DEBUG) message_log_append( $msg_log, "do_insert: old_seq = $old_seq" );
		$qry = "INSERT INTO contract_header ( $fld_list ) " .
				"SELECT * FROM temp_header";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);	// true if success
		if (!$success)
		{
			message_log_append( $msg_log, "contract header insert failed: " . mysql_errori( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{
		$new_seq = last_insert_id( $db_conn );
		$success = (!is_null( $new_seq ) && ($new_seq <> $old_seq));
		if ($success) 
		{
			$seq = $new_seq;
		}
		else 
		{
			message_log_append( $msg_log, "temp detail insert failed: " . 	mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

// Attempt to update the temp_detail records with the new $seq number
	if ($success) 
	{
		if (I_DEBUG) 
		{
			message_log_append( $msg_log, "do_insert: new contract_header seq = $seq" );
		}
		$qry = "UPDATE temp_detail SET Contract = $seq";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);
		if (!$success)
		{
			message_log_append( $msg_log, mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{ // Set $fld_list to the list of fields in table temp_detail
		$fld_list = field_list( "temp_detail", $db_conn );
		$success = ($fld_list != NULL);
		if (!$success)
		{
			message_log_append( $msg_log, "field_list of temp_detail failed", MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{ // Set $seqn to the value of LAST_INSERT_ID()
		$old_seq = last_insert_id( $db_conn );
//if (I_DEBUG) message_log_append( $msg_log, "detail: old_seq = $old_seq" );
		$success = (!is_null( $old_seq ));
		if (!$success)
		{
			message_log_append( $msg_log, "do_insert: last_insert_id failed(2)", MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{ // insert into contract_detail from temp_detail
		$qry = "INSERT INTO contract_detail ( $fld_list ) " . "SELECT * FROM temp_detail";
		if (I_DEBUG) 
		{
			message_log_append( $msg_log, "detail: $qry" );
		}
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);
		if (!$success)
		{
			message_log_append( $msg_log, mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success

	if ($success) 
	{ // check to see if LAST_INSERT_ID() changed
		$new_seq = last_insert_id( $db_conn );
		$success = (($new_seq <> $old_seq) && !is_null( $new_seq ));
		if ($success) 
		{
			if (I_DEBUG) 
			{
				message_log_append( $msg_log, "do_insert: success" );
			}
		} 
		else 
		{
			message_log_append( $msg_log, "contract_detail insert failed", MSG_LOG_ERROR );
		}
	} // if success
	return( $success );
} // do_insert

function insert_sql_data( $db_conn, $p_campaigns, $p_headers, $p_details )
// insert the header and detail data into the file
// with appropriate safeguards to ensure full 
// completion or rollback of the transaction.
{
GLOBAL $msg_log;

	$success = TRUE;
// Wrap all this in a transaction
// First, turn off autocommit
	$qry = "set autocommit=0";
//pre_echo( $qry );
	$success = mysqli_query($db_conn,$qry);

// Second, take care of all ALTER TABLE queries.  Due to a (documented)
// glitch in MySQL, these commands force a transaction to commit, 
// which sucks.

	if ($success) 
	{ // Create the temp_header table
		$qry = "CREATE TEMPORARY TABLE temp_header LIKE contract_header";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);
		if (!$success) 
		{
			message_log_append( $msg_log, mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	}

	if ($success) 
	{ // Create the temp_detail table
		$qry = "CREATE TEMPORARY TABLE temp_detail LIKE contract_detail";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);
		if (!$success)
		{
			message_log_append( $msg_log, mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	}

	if ($success) 
	{ // Delete the Seq field from table temp_header
		$qry = "ALTER TABLE temp_header DROP COLUMN Seq";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);
		if (!$success)
		{
			message_log_append( $msg_log, mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	}

    if ($success) 
	{ // Delete the Line column from table temp_detail
		$qry = "ALTER TABLE temp_detail DROP COLUMN Line";
//pre_echo( $qry );
		$success = mysqli_query($db_conn,$qry);
		if (!$success)
		{
			message_log_append( $msg_log, mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	}

// loop through the campaigns, headers, and details to insert the
// data into the SQL database.  Keep solid track of all error
// results so that we can ROLLBACK on any error.
	if ($success) 
	{
//echo "<pre>";
//var_dump( $p_campaigns );  echo "</pre><br>";
		$success = begin( $db_conn );
		if (!$success)
		{
			message_log_append( $msg_log, "Error in START TRANSACTION: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	}

// do the work here, and keep track of $success
// If we need to create a new agency record, do that here.
	$new_agency = FALSE;
	if ($success && is_null( $p_campaigns[0][ 'Agency Record' ])) 
	{
		$agent_name = $p_campaigns[0][ 'Agency Name' ];
		$rate = DEFAULT_AGENCY_RATE / 10;
		if ($success = agency_insert( $db_conn, $agent_name, $rate, $aindex )) 
		{
			$p_campaigns[0][ 'Agency Record' ] = agency_record( $agent_name, OPERATOR_NAME );
			$success = !is_null( $p_campaigns[0][ 'Agency Record' ]);
		} // if agency_insert
		if ($success) 
		{
			$new_agency = TRUE;
			message_log_append( $msg_log, "Agency created: " .
			"Seq = $aindex, Name = '$agent_name'", MSG_LOG_WARNING );
		} 
		else 
		{
			message_log_append( $msg_log, "Error while creating " . "Agency '$agent_name': " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if null agency record

// If we need to create a new customer record, do that here.

	$new_customer = FALSE;
	if ($success && is_null( $p_campaigns[0][ 'Customer Record' ])) 
	{
		$cust_name = $p_campaigns[0][ 'Customer Name' ];
		$rate = DEFAULT_CUST_DISCOUNT;
		if ($success = customer_insert( $db_conn, $cust_name, $rate, $cindex )) 
		{
			$p_campaigns[0][ 'Customer Record' ] = cust_record( $cust_name, OPERATOR_NAME );
			$success = !is_null( $p_campaigns[0][ 'Customer Record' ]);
		} // if customer_insert
		if ($success) 
		{
			$new_customer = TRUE;
			message_log_append( $msg_log, "Customer created: " . "Seq = $cindex, Name = '$cust_name'", MSG_LOG_WARNING );
		} 
		else 
		{
			message_log_append( $msg_log, "Error while creating " . "Customer '$cust_name' " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if null customer record

	if ($success) 
	{
// build the list of header fields, in order with 'quote required' flag
//  [n][0] is field name, [n][1] is boolean T=quote required, F=not
		$hdr_flds = build_header_field_array();
// A SQL INSERT statement lead-in
		$hdr_sql  = "INSERT INTO temp_header ( ";
		$hdr_sql .= fld_list( $hdr_flds ) . ") VALUES\n";

// build the list of detail fields, in order with 'quote required' flag
//  [n][0] is field name, [n][1] is boolean T=quote required, F=not

		$det_flds = build_detail_field_array();

// A SQL INSERT statement lead-in
		$det_sql  = "INSERT INTO temp_detail ( ";
		$det_sql .= fld_list( $det_flds ) . ") VALUES ";

// Here we go.  We'll loop through each contract header record,
// and its accompanying detail records.

		$n_inserted = 0;

		while ($success && (list( $key ) = each( $p_headers ))) 
		{
		    if (count( $p_details[ $key ] ) > 0) 
			{
//	If we created a new agency or customer above, update 
//	the respective header fields.
				if ($new_customer) 
				{
					$p_headers[ $key ][ 'CIndex' ] = $cindex;
					$p_headers[ $key ][ 'Discount' ] = $p_campaigns[0][ 'Customer Record' ][ 'Discount' ];
				}
				if ($new_agency) 
				{
					$p_headers[ $key ][ 'AIndex' ] = $aindex;
					$p_headers[ $key ][ 'AgencyComm' ] = $p_campaigns[0][ 'Agency Record' ][ 'Rate' ];
				}
				$row = data_values( $hdr_flds, $p_headers[ $key ] );
				$sql_header  = $hdr_sql;	// INSERT INTO ... VALUES
				$sql_header .= "(" . $row . ");";

				$rows = "";
				foreach ($p_details[ $key ] as $line)
				{
					$rows .= ",\n( " . data_values( $det_flds, $line ) . " )";
				}
				$rows = substr( $rows, 1 );	// remove comma-newline
				$sql_detail  = $det_sql;	// INSERT INTO ... VALUES
				$sql_detail .= $rows;

				if ($success = do_insert( $db_conn, $sql_header, $sql_detail ))
				{
					$n_inserted++;
				}
			} // if detail count > 0
		} // while success and each key
	} // if success

	if ($success) 
	{
		$success = commit( $db_conn );
		if ($success)
		{
			message_log_append( $msg_log, "$n_inserted contract" . ($n_inserted == 1 ? '' : 's') . " imported" );
		}
		else 
		{
			message_log_append( $msg_log, "Error in COMMIT TRANSACTION: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
			if (!rollback( $db_conn ))
			{
				message_log_append( $msg_log, "Error in ROLLBACK TRANSACTION: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
			}
		}
	} 
	else 
	{
		if (!rollback( $db_conn ))
		{
			message_log_append( $msg_log, "Error in ROLLBACK TRANSACTION: " . mysqli_error( $db_conn ), MSG_LOG_ERROR );
		}
	} // if success
	return( $success );
} // insert_sql_data

function insert_sql( $p_campaigns, $p_headers, $p_details )
// take the arrays and organize a structured INSERT process
// with the necessary steps to ensure referential integrity
// of the various indices.  protect against errors with
// transaction commit/rollback.
{
GLOBAL $db_conn;
	insert_sql_data( $db_conn, $p_campaigns, $p_headers, $p_details );
} // insert_sql

?>
