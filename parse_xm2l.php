<?php

require_once 'testing-header.php';
require_once 'mysql_funcs.php';

define( 'DEFAULT_CUST_DISCOUNT',  "30" );	// default 30% commission to the customer
define( 'DEFAULT_SALES_COMM',     "100" );	// default 100% commission to the salesman

define( 'FLD_NAME', 0 );
define( 'EXPR',     1 );


function site_records( $sys_code, $oper_name )
// return an array of all records from table site_operators 
// that match the $sys_code and $oper_name.  return NULL if
// error.
{
GLOBAL $db_conn;
GLOBAL $msg_log;

        $qry = "SELECT * FROM site_operators WHERE Operator =  '$oper_name' " .
			"AND SysCode = '$sys_code'";
        if ($sql_result = mysql_query( $qry, $db_conn )) {
		$j = 0;
		$result = array();
                while ($record = mysql_fetch_array( $sql_result )) {
                	$result[ $j++ ] = $record;
		} // while
		if ($j == 0) {
			unset( $result );
			$result = NULL;
			message_log_append( $msg_log, "Error reading site_operators records " .
					"for Syscode $sys_code", MSG_LOG_ERROR );
		} // if
        } else {
		message_log_append( $msg_log, "MySQL query failed: " . $qry, MSG_LOG_ERROR );
		message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
                $result = NULL;
        } // if
	return( $result );

} // site_records


function operator_record( $oper_name )
{
GLOBAL $db_conn;
GLOBAL $msg_log;

        $qry = "SELECT * FROM operators WHERE ShortName =  '$oper_name'";
        if (($sql_result = mysql_query( $qry, $db_conn )) &&
                ($record = mysql_fetch_array( $sql_result ))) {
                $result = $record;
//// Kludge to add SalesComm value:
//		if (is_null( $result[ 'SalesComm' ]))
//			$result[ 'SalesComm' ] = DEFAULT_SALES_COMM;
        } else {
		message_log_append( $msg_log, "MySQL query failed: " . $qry, MSG_LOG_ERROR );
		message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
                $result = NULL;
        }
	return( $result );

} // operator_record


function salesman_record( $p_name, $oper_name )
{
GLOBAL $db_conn;
GLOBAL $msg_log;

        $qry = "SELECT * FROM salesman WHERE Name = '$p_name' AND Operator = '$oper_name'";
        if (($sql_result = mysql_query( $qry, $db_conn )) &&
                ($record = mysql_fetch_array( $sql_result ))) {
                $result = $record;
        } else {
		message_log_append( $msg_log, "MySQL query failed: " . $qry, MSG_LOG_ERROR );
		message_log_append( $msg_log, mysql_error( $db_conn ), MSG_LOG_ERROR );
                $result = NULL;
        }
	return( $result );

} // salesman_record


function xml_company_name( $p_campaign, $p_type )
// locate the company element with type $p_type and return the name value
{
GLOBAL $msg_log;

	$result = NULL;
	/* Search for <campaign>...<company type="$p_type"><name> */
//var_dump( $p_campaign );
//die();
	if ($match = $p_campaign->xpath( '/adx/campaign/company' )) {
		while (list( $foo, $node ) = each( $match )) {
			if ($p_type == $node[ "type" ]) {
				$result = '' . $node->name;
				break;
			} // if
		} // while
	} else {
if (DEBUG) echo "no $p_type name\n";
if (DEBUG) var_dump( $p_campaign );
		message_log_append( $msg_log, "Can't find campaign/company['$p_type']", MSG_LOG_ERROR );
	} // if
	return( $result );
} // xml_company_name


function xml_syscode( $p_sys_ord )
// locate the system element and return the syscode value
{
GLOBAL $msg_log;

	$result = trim( (string)$p_sys_ord->system->syscode );
	if ($result == NULL || strlen( $result ) != 4) {
if (DEBUG) echo "no syscode\n";
if (DEBUG) var_dump( $p_sys_ord );
		message_log_append( $msg_log, "Missing or invalid syscode $result", MSG_LOG_ERROR );
	} // if
	return( $result );
}


function count_xml_elements( $p_xml, $path )
// return the number of elements in $xml matching $path.
{
	return( count( $p_xml->xpath( $path ) ) );
}


function parse_campaign( $campaign,
			 &$camp_array )

// we will parse one campaign passed in XML form in $campaign.
// parsed values will be returned in associative array $camp_array.
// the function will return TRUE if successful, else FALSE.

{

//echo "pc: on entry\n";
//var_dump( $campaign );

	$success    = TRUE;
	$xml_fields = array();
	$j	    = 0;

/*
Here are the definitions of the XML values we will parse that relate
to the campaign.

The FLD_NAME is the name of the field.  There is no campaign
database table per se, so all FLD_NAMEs do not correspond directly
to  table fields.

These definitions get processed in numerical array order, so lay
them out such that prerequisite values appear and thus get defined
before later values which require that they be set.
*/

	$xml_fields[ $j ][ FLD_NAME ] = "CampKeyID";
	$xml_fields[ $j ][ EXPR     ] = "(string)('' . \$campaign->key->id)";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "CampKeyVer";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->key->version";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "CampOrderKeyID";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->order->key->id";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "CampOrderKeyVer";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->order->key->version";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Customer Name";
	$xml_fields[ $j ][ EXPR     ] = "(string)xml_company_name( \$campaign, 'Rep' )";
	$j++;

// Is this campaign from TelAmerica?  Boolean TRUE/FALSE
	$xml_fields[ $j ][ FLD_NAME ] = "TELAMERICA";
//	$xml_fields[ $j ][ EXPR     ] = "(\$camp_array['Customer Name'] == '" . TELAMERICA . "')";
	$xml_fields[ $j ][ EXPR     ] = "((\$camp_array['Customer Name'] == '" . TELAMERICA . "')" .
		" OR (\$camp_array['Customer Name'] == '" . APEX_MEDIA . "')" .
		" OR (\$camp_array['Customer Name'] == '" . APEX_MEDIA_DR . "'))";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Customer Record";
	$xml_fields[ $j ][ EXPR     ] = "cust_record( xml_company_name( \$campaign, 'Rep' ), '" . OPERATOR_NAME . "' )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Agency Name";
	$xml_fields[ $j ][ EXPR     ] = "(string)xml_company_name( \$campaign, 'Agency' )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Agency Record";
	$xml_fields[ $j ][ EXPR     ] = "agency_record( xml_company_name( \$campaign, 'Agency' ), '" . OPERATOR_NAME . "' )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "CustOrder";
	$xml_fields[ $j ][ EXPR     ] = "(string)('' . \$campaign->order->key->id)";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "UpdateDate";
	$xml_fields[ $j ][ EXPR     ] = "(string)('' . \$campaign->order->key->updateDate)";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "contract_name";
	$xml_fields[ $j ][ EXPR     ] = "(\$camp_array['TELAMERICA']) ? ((string)('' . \$campaign->product->name)) : ((string)('' . \$campaign->advertiser->name))";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "ContractName";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['TELAMERICA'] " .
					"? (substr( \$camp_array['contract_name'], 0, 26 ) " .
					" . ' ' . substr( \$camp_array['CustOrder'], 0, 5 )) " .
					": (substr( \$camp_array['contract_name'], 0, 27 ) " .
					" . ' ' . substr( \$camp_array['CustOrder'], -4 ))";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "StartDate";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->dateRange->startDate";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "EndDate";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->dateRange->endDate";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "MakeGoodPolicy";
	$xml_fields[ $j ][ EXPR     ] = "\$campaign->makeGoodPolicy->code . ':' . " . 
				"('' . \$campaign->makeGoodPolicy->code['codeDescription'])";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "EstimateCode";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->estimate->ID->code";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "buyType";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->buyType";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "total_spots";
	$xml_fields[ $j ][ EXPR     ] = "(int)\$campaign->order->totals->spots";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "total_cost";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$campaign->order->totals->cost";
	$j++;

// Now evaluate the fields defined in the array:

//echo "pc: xml_fields\n";
//var_dump( $xml_fields );

	$N_FLDS = $j;

//echo "pc: on eval\n";
//var_dump( $campaign );
//echo "pc: after var_dump\n";

//echo "<pre>\n";
	$j = 0;
	while ($j < $N_FLDS) {
//echo "inside while\n";
		if (!is_null( $xml_fields[ $j ][ EXPR ] )) {
//echo "inside if\n";
			$fld_name = $xml_fields[ $j ][ FLD_NAME ];
//echo $fld_name . "\n";
			$expr = $xml_fields[ $j ][ EXPR ];
//echo $expr . "\n";
			$expr = "RETURN( " . $expr . " );";
			$fld_value = eval( $expr );
			$camp_array[ $fld_name ] = $fld_value;
//echo $camp_array[ $fld_name ] . "\n";
//var_dump( $camp_array[ $fld_name ] );
//echo "---\n";
		}
		$j++;
	} // while

//echo "parse_campaign returns:<br>\n";
//var_dump( $camp_array );
//echo "</pre>\n";
	return( $success );

} // parse_campaign


function parse_contract_header( $campaign,
				$system_order,
				$camp_array,
				&$hdr_array )

// we will parse one system order passed in $system_order.
// some variables from $campaign will be used.
// parsed values will be returned in associative array $hdr_array.
// the function will return TRUE if successful, else FALSE.

{
//GLOBAL $DEBUG;

	$success    = TRUE;
	$xml_fields = array();
	$j	    = 0;

/*
Here are the definitions of the XML values we will parse that relate
to the contract header.

The FLD_NAME is the name of the field.  Not all FLD_NAMEs correspond
directly to contract_header table fields, but all table fields must
exist here, and the table field name must match the FLD_NAME value
in the array.  This means we can store additional values which will
not get imported into the table, but which facilitate lookup or
computation of values that do get imported into the contract_header
table fields, such as customer and agency records, etc.  In order
to avoid getting imported, these 'extra' values must have FLD_NAMEs
which do not appear in the database table.

These definitions get processed in numerical array order, so lay
them out such that prerequisite values appear and thus get defined
before later values which require that they be set.
*/

// parse as many version nodes as we can find.  Later we'll
// validate them to all be equal to 1.

// 2012-11-01 we find that $campaign->key->version and $campaign->order->key->version
// can sometimes be <> 1, cf TelAmerica order 208000791.  However, 
// on that order, the individual systemOrder->key->version values 
// are still 1.  Let's change our assumption and only validate 
// $campaign->order->systemOrder->key->version (for each instance
// of systemOrder).

// version fields need to be first (to use $j+1)
	$xml_fields[ $j ][ FLD_NAME ] = "version" . ($j+1);
	$xml_fields[ $j ][ EXPR     ] = "(string)\$system_order->key->version";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Seq";
	$xml_fields[ $j ][ EXPR     ] = "'NULL'";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "SysCode";
	$xml_fields[ $j ][ EXPR     ] = "xml_syscode( \$system_order )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Site Records";
	$xml_fields[ $j ][ EXPR     ] = "site_records( xml_syscode( \$system_order ), '" . OPERATOR_NAME . "' )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Operator Record";
	$xml_fields[ $j ][ EXPR     ] = "operator_record( '" . OPERATOR_NAME . "' )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Salesman Record";
	$xml_fields[ $j ][ EXPR     ] = "salesman_record( '" . 
					SALESMAN_NAME . "', '" . OPERATOR_NAME . "' )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "CIndex";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['Customer Record']['Seq']";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Discount";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['Customer Record']['Discount'] * 10";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "AIndex";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['Agency Record']['Seq']";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "AgencyComm";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['Agency Record']['Rate'] * 10";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "CustOrder";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['CustOrder']";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "SystemOrder";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$system_order->key->id";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "ContractName";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['ContractName']";
	$j++;

// SiteName is a placeholder that will be filled in later.
	$xml_fields[ $j ][ FLD_NAME ] = "SiteName";
	$xml_fields[ $j ][ EXPR     ] = "NULL";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "StartDate";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['StartDate']";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "EndDate";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['EndDate']";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "TotalValue";	// stored in pennies
	$xml_fields[ $j ][ EXPR     ] = "bcmul( (string)\$system_order->totals->cost, 100 )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Attributes";
	$xml_fields[ $j ][ EXPR     ] = ATTRIBUTES;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "SIndex";
	$xml_fields[ $j ][ EXPR     ] = "\$hdr_array['Salesman Record']['Seq']";
	$j++;
	
	$xml_fields[ $j ][ FLD_NAME ] = "SalesComm";
	$xml_fields[ $j ][ EXPR     ] = "\$hdr_array['Salesman Record']['Rate'] * 10";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "MakeGoodPolicy";
	$xml_fields[ $j ][ EXPR     ] = "\$camp_array['MakeGoodPolicy']";
	$j++;

        $xml_fields[ $j ][ FLD_NAME ] = "MinSeparation";
        $xml_fields[ $j ][ EXPR     ] = "0";
        $j++;

	$xml_fields[ $j ][ FLD_NAME ] = "week_count";
	$xml_fields[ $j ][ EXPR     ] = "(int)\$system_order->weeks['count']";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "total_spots";
	$xml_fields[ $j ][ EXPR     ] = "(int)\$system_order->totals->spots";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "total_cost";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$system_order->totals->cost";
	$j++;

// Now evaluate the fields defined in the array:

	$N_FLDS = $j;

	$j = 0;
	while ($j < $N_FLDS) {
		if (!is_null( $xml_fields[ $j ][ EXPR ] )) {
			$fld_name = $xml_fields[ $j ][ FLD_NAME ];
//if ($DEBUG) {echo "PCH fld_name '" . $fld_name . "'\n";}
			$expr = $xml_fields[ $j ][ EXPR ];
//if ($DEBUG) {echo "PCH expr " . $expr . "\n";}
			$expr = "RETURN( " . $expr . " );";
			$fld_value = eval( $expr );
			$hdr_array[ $fld_name ] = $fld_value;
//if ($DEBUG) {
//echo "PCH hdr_array[fld_name] " . $hdr_array[ $fld_name ] . "\n";
//var_dump( $hdr_array[ $fld_name ] );
//echo "---\n";
//}
		}
		$j++;
	} // while

	return( $success );

} // parse_contract_header


function parse_contract_detail( $campaign,
				$system_order,
				&$det_array )

// we will parse one system order's details passed in $system_order[0].
// some variables from $campaign will be used.
// parsed values will be returned in associative array $det_array[0..N-1]
// to represent N detail lines.
// the function will return TRUE if successful, else FALSE.

{
GLOBAL $msg_log;
//GLOBAL $DEBUG;

//if ($DEBUG) echo "<pre>";
//if ($DEBUG) var_dump( $system_order );

	$success = TRUE;
	$N_lines = 0;	// count the number of detail lines parsed

/*
Here are the definitions of the XML values we will parse that relate
to the contract detail.

The FLD_NAME is the name of the field.  Not all FLD_NAMEs correspond
directly to contract_detail table fields, but all table fields must
exist here, and the table field name must match the FLD_NAME value
in the array.  This means we can store additional values which will
not get imported into the table, but which facilitate lookup or
computation of values that do get imported into the contract_detail
table fields, such as customer and agency records, etc.  In order
to avoid getting imported, these 'extra' values must have FLD_NAMEs
which do not appear in the database table.

These definitions get processed in numerical array order, so lay
them out such that prerequisite values appear and thus get defined
before later values which require that they be set.
*/

// The only values we can compute here are values which are 
// constant over the entire contract.  Anything that changes
// based on detailLine or spot must be calculated below in 
// the appropriate WHILE loop.

	$week_table = array();	// needs to be $week_count elements, but
				// indices are not necessarily consecutive
	$week_count = (int)$system_order->weeks['count'];

	$week = 0;
	foreach ($system_order->weeks->week as $x) {
		$week = (int)$x['number'];
		$week_table[ $week ] = (string)$x['startDate'];
	}
//if ($DEBUG) echo "week_count: ";
//if ($DEBUG) var_dump( $week_count );
//if ($DEBUG) echo "week_table: ";
//if ($DEBUG) var_dump( $week_table );

	$xml_fields = array();
	$j	    = 0;

	$xml_fields[ $j ][ FLD_NAME ] = "Line";
	$xml_fields[ $j ][ EXPR     ] = "'NULL'";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Contract";
	$xml_fields[ $j ][ EXPR     ] = "'NULL'";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "XMLNetwork";
	$xml_fields[ $j ][ EXPR     ] = "(string)('' . \$detail_line->network->ID->code)";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "StartDate";
	$xml_fields[ $j ][ EXPR     ] = "\$week_table[(int)\$spot->weekNumber]";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "EndDate";
	$xml_fields[ $j ][ EXPR     ] = <<< __EOF__
date_format( date_modify( date_create( \$one_line['StartDate'], new DateTimeZone( "GMT" ) ), '+6 days' ), 'Y-m-d' )
__EOF__;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "XMLstartTime";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$detail_line->startTime";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "XMLendTime";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$detail_line->endTime";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Distribution";
	$xml_fields[ $j ][ EXPR     ] = "\$distrib";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Bonus";
	$xml_fields[ $j ][ EXPR     ] = 0;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Priority";
	$xml_fields[ $j ][ EXPR     ] = NULL;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "UnitPrice";	// in pennies
	$xml_fields[ $j ][ EXPR     ] = "bcmul( (string)\$detail_line->spotCost, 100, 0 )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "nOrdered";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$spot->quantity";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "LineID";
	$xml_fields[ $j ][ EXPR     ] = "(string)(\$detail_line['detailLineID'])";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "Value";
	$xml_fields[ $j ][ EXPR     ] = "bcmul( bcmul( \$one_line['UnitPrice'], \$one_line['nOrdered'], 2 ), '0.01', 2 )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "nWeeks";
	$xml_fields[ $j ][ EXPR     ] = 1;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "nSched";
	$xml_fields[ $j ][ EXPR     ] = 0;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "nPlaced";
	$xml_fields[ $j ][ EXPR     ] = 0;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "nPlayed";
	$xml_fields[ $j ][ EXPR     ] = 0;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "ActualValue";
	$xml_fields[ $j ][ EXPR     ] = 0;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "ProgramName";
	$xml_fields[ $j ][ EXPR     ] = "substr( (string)('' . \$detail_line->program), 0, 32 )";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "StartDay";
	$xml_fields[ $j ][ EXPR     ] = "\$start_day";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "EndDay";
	$xml_fields[ $j ][ EXPR     ] = "\$end_day";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "MakeGoods";
	$xml_fields[ $j ][ EXPR     ] = 0;
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "MakeGoodDays";
	$xml_fields[ $j ][ EXPR     ] = "\$make_good_days";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "RunDays";
	$xml_fields[ $j ][ EXPR     ] = "\$run_days";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "DayMask";
	$xml_fields[ $j ][ EXPR     ] = "\$day_mask";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "SpotID";	// 1..N
//	$xml_fields[ $j ][ EXPR     ] = "substr( (string)\$spot['id'], -4 )";
	$xml_fields[ $j ][ EXPR     ] = "(string)\$spot['id']";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "week_count";
	$xml_fields[ $j ][ EXPR     ] = "\$week_count";
	$j++;

	$xml_fields[ $j ][ FLD_NAME ] = "week_table";
	$xml_fields[ $j ][ EXPR     ] = "\$week_table";
	$j++;

	$N_FLDS = $j;

	$detail_lines = $system_order->detailLine;
	foreach ($detail_lines as $detail_line) {

//if ($DEBUG) echo "XML system_order->detail_line: ";
//if ($DEBUG) var_dump( $detail_line );

		$one_line = array();	// temp. array to hold one detail line

// calculate detail constants here

// Calculate the make good day bitmask, and count the number
// of days this spot will run in the week.

// It would be nice to throw an error if we find anything 
// other than Yes/No run indications.  The spec. permits 
// the XML file to stipulate day-by-day run distributions,
// but we don't support that.

		$dow            = MONDAY;		// day of week 0-6 = Mon-Sun
		$days           = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 
					'Friday', 'Saturday', 'Sunday' );
		$make_good_days = 0;	// binary bit mask of days
		$day_mask	= "";	// string mask of days Y/N
		$run_days       = "";	// string
		$n_run_days     = 0;	// count
		foreach ($days as $day) {
			$run_day = (string)$detail_line->dayOfWeek->$day;
			$run_days .= ' ' . $run_day;
			switch ($run_day) {
			case 'Y':
				$day_mask .= 'Y';
				$n_run_days++;
				$make_good_days += pow( 2, $dow );
				break;
// other run_day cases would go here
			case 'N':
			case '0':
				$day_mask .= 'N';
				break;
			default:
				$success = FALSE;
				message_log_append( $msg_log, "Invalid run day format: '$run_day'",
							MSG_LOG_ERROR );
			} // switch
			$dow++;
		}

// take the "startDay" field from the XML spec
		$start_day_code = (string)$detail_line->startDay;

// day codes stipulated by the XML spec:
		$days = array( 'M', 'Tu', 'W', 'Th', 'F', 'Sa', 'Su' );

		$start_day = array_search( $start_day_code, $days );
		if ($start_day === FALSE) {
			$success = FALSE;
			message_log_append( $msg_log, "Invalid start day in detailLineID "
				. $detail_line['detailLineID'] . ": '$start_day_code'", MSG_LOG_ERROR );
//		} else {
//			message_log_append( $msg_log, "start day '$start_day_code' is $start_day" );
		}

// there is no "endDay" field in the XML spec
		$end_day = SUNDAY;

		if ($n_run_days < 1) {
			$success = FALSE;
			message_log_append( $msg_log, "No run days found", MSG_LOG_ERROR );
			break;
		}

		$run_days = substr( $run_days, 1 );	// remove leading space

//echo $detail_line['detailLineID'] . "\n";
		$spots = $detail_line->spot;
		foreach ($spots as $spot) {
//echo "  " . $spot['id'] . "\n";

// using the number of spots for this week, and the run days for the
// detail line, figure the distribution for this spot and this week.

			$n_spots = $spot->quantity;

//	Given the number $n_spots and the $n_run_days, we can figure
//	the distribution.  If the number of spots does not divide
//	evenly by $spot_day_count, then the remainder R will cause
//	the first R days to be increased by one.  11 spots in 3 days
//	means the remainder is 2, so the first 2 days get an extra spot:
//	4 4 and 3 to total 11.  19 spots in seven days means the remainder
//	is 5, so 3 3 3 3 3 2 2.

//	Format the seven daily spot counts with a hyphen delimiter, like
//	0-4-0-4-0-0-3 for 11 spots and a day mask of NYNYNNY (TuThSun)

			$remain = $n_spots % $n_run_days;	// remainder
			$each   = ($n_spots - $remain) / $n_run_days;

                	$j = MONDAY;
                	$distrib = "";
                	while ($j <= SUNDAY) {
                        	$distrib .= '-';
                        	if (substr( $day_mask, $j++, 1 ) == 'Y') {
                                	$distrib .=
                                	( $remain-- > 0 ? 1 : 0 ) + $each;
                        	} else {
                                	$distrib .= "0";
                        	}
                	} // while
			$distrib = substr( $distrib, 1 ); // remove leading hyphen

			$j = 0;
			while ($j < $N_FLDS) {
				if (!@is_null( $xml_fields[ $j ][ EXPR ] )) {
					$fld_name = $xml_fields[ $j ][ FLD_NAME ];
//if ($DEBUG) echo "setting " . $fld_name . " to ";
					$expr = $xml_fields[ $j ][ EXPR ];
//if ($DEBUG) echo $expr . "\n";
					$expr = "RETURN( " . $expr . " );";
					$fld_value = eval( $expr );
					$one_line[ $fld_name ] = $fld_value;
//if ($DEBUG) var_dump( $one_line[ $fld_name ] );
//if ($DEBUG) echo "---\n";
				}
				$j++;
			} // while

//	increment the number of lines parsed, and add a new row
//	to the result array
			$det_array[ $N_lines ] = $one_line;
//if ($DEBUG) echo "det_array[ $N_lines ] array parsed as: ";
//if ($DEBUG) var_dump( $det_array[ $N_lines ] );
			$N_lines++;

		} // foreach spot

	} // foreach detail_line

//if ($DEBUG) echo "no more detail_lines, N_lines = $N_lines\n";

//if ($DEBUG) echo "</pre>";
	return( $success );

} // parse_contract_detail


function parse_xml(
	$xmlfile,		// input file to parse
	&$camp_header,		// campaign output array returned to caller
	&$cont_header,		// header   output array returned to caller
	&$cont_detail		// detail   output array returned to caller
)

// return TRUE if no errors, else FALSE

{
GLOBAL $msg_log;
//GLOBAL $DEBUG;

//if ($DEBUG) echo "<pre>";

	$n_contract = 0;		// index of contracts parsed

	$camp_header = array();		// array, but limited to one element, [0]
	$cont_header = array();		// $cont_header[N] is header for index N
	$cont_detail = array();		// $cont_detail[N] is detail array for index N

	$success = TRUE;

	if ($success) {
//if ($DEBUG) echo "loading XML\n";
		$success = ($xml = simplexml_load_file( $xmlfile ));
		if (!$success)
			echo "Can't load XML file.\n";
	}

	if ($success) {
		$success = ($xml->document->documentType == 'Order');
		if (!$success)
			echo "XML file is the wrong documentType.\n";
	}

	if ($success) {
		$success = (count_xml_elements( $xml, '/adx/campaign' ) == 1);
		if (!$success)
			echo "Invalid number of campaigns in XML file.\n";
	} // if

	if ($success) {
		$success = ($campaign = $xml->xpath( '/adx/campaign' ));
		if ($success) {
//if ($DEBUG) echo "parsing campaign\n";
			$success = parse_campaign( $campaign[0], $camp_header[0] );
//if ($DEBUG) {
//echo "done parsing campaign, ID='" . $camp_header[0]['CampKeyID'] . "'<br>\n";
////echo "<pre>";
////var_dump( $camp_header[0] );
////echo "</pre>";
//}
			if (!$success) {
				message_log_append( $msg_log, "Can't parse campaign from XML file",
					MSG_LOG_ERROR );
			} // if
		} else message_log_append( $msg_log, "Can't locate 'campaign' node in XML file",
				MSG_LOG_ERROR );
	} // if

	if ($success) {

		$sys_ords = $campaign[0]->xpath('/adx/campaign/order/systemOrder');

		$n_sys_ord = 0;
		while ($success && 
			isset( $sys_ords[ $n_sys_ord ] ) &&
			!is_null( $sys_ords[ $n_sys_ord ] )) {

			if ($n_contract < MAX_CONTRACTS) {

				$one_header = array();
				$one_detail = array();
				$one_copy   = array();

//if ($DEBUG) echo "parsing header $contract_seq\n";
				$success = parse_contract_header( 
						$campaign[ 0 ], // XML object
						$sys_ords[ $n_sys_ord ], // XML object
						$camp_header[ 0 ], // array
					 	$one_header // array
						);
				if ($success)
//if ($DEBUG) echo "parsing detail $contract_seq\n";
					$success = parse_contract_detail( 
						$campaign[0], // XML object
						$sys_ords[ $n_sys_ord ], // XML object
						$one_detail // array
						);

//				if ($success)
//					$success = parse_contract_copy(
//						$campaign[0], // XML object
//						$sys_ords[ $n_sys_ord ], // XML object
//						$one_copy // array
//						);

				if ($success) {
//if ($DEBUG) { echo "one_detail: "; var_dump( $one_detail ); }
foreach ($one_header['Site Records'] as $site_record) {
$one_header['Site Records'] = array( $site_record );
$one_header['SiteName'] = $site_record['SiteName'];
					$contract_seq = $n_contract + 1;
					$cont_header[ $contract_seq ] = $one_header;
					$cont_detail[ $contract_seq ] = $one_detail;
					$cont_copy[   $contract_seq ] = $one_copy;
					$n_contract++;
} // foreach site_record
				}

				$n_sys_ord++;

			} else {
				$success = FALSE;
				message_log_append( $msg_log, "XML file contains too many contracts",
					MSG_LOG_ERROR );
			} // if

		} // while

		if (!$success)
			message_log_append( $msg_log, "Error while parsing XML file", MSG_LOG_ERROR );

	} // if

	if ($success) {
//if ($DEBUG) echo "$n_contract contract" . ($n_contract == 1 ? '' : 's') . " parsed successfully.\n";
//if ($DEBUG) echo "cont_header: ";
//if ($DEBUG) var_dump( $cont_header );
//if ($DEBUG) echo "detail: ";
//if ($DEBUG) var_dump( $cont_detail );
	} // if

//if ($DEBUG) echo "</pre>\n";
	return( $success );

} // parse_xml


?>
