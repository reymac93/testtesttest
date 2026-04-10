<?php

ob_start();
$Roger=0;
if($Roger>0) {echo ("<br/>File: " . dirname(__FILE__) . ", Line: " . __LINE__ . "<br/>");}

define( 'DEBUG', FALSE );

// why doesn't this work (FALSE OR ...)
$DEBUG = DEBUG || (isset( $_COOKIE[ 'TTVM_DEBUG' ] ) && ($_COOKIE[ 'TTVM_DEBUG' ] == 'TRUE'));
define( 'TEST_DB', $DEBUG );	// T = use test SQL database, F = use production database

define( 'CLI', FALSE );

define( 'MSG_LOG_NORMAL',  0 );
define( 'MSG_LOG_WARNING', 1 );
define( 'MSG_LOG_ERROR',   2 );

//echo phpinfo();

// Some error types that MSG_LOG can recognize (not very many yet)

define( 'ERR_UNSPEC',      0 );		// Type of error not specified
define( 'ERR_NO_NETWORK',  1 );		// Network not available
define( 'ERR_OTHER',       2 );		// Other error
define( 'ERR_UNKNOWN',     3 );		// Unknown error

// eventually we should support multiple operators, but
// for now, it's a constant:

define( 'OPERATOR_NAME', 'AdSystems' );
define( 'SALESMAN_NAME', 'Generic AdSystems' );
define( 'TELAMERICA',    'CADENT NETWORK' );



require_once( 'style.php' );
require_once( 'mysql_funcs.php' );
require_once( 'parse_xml.php' );
require_once( 'verify_xml.php' );
require_once( 'insert_sql.php' );


$msg_log = array();

function message_log_reset( &$p_msg_log )
{
	$p_msg_log = array();
} // message_log_reset


function message_log_format( $p_msg_log )
// return a string with the html-formatted message log
{
	$s = "";
	foreach ($p_msg_log as $log) 
	{
		$msg = $log[0];
		$lvl = $log[1];
		if (strlen( $msg ) > 0) 
		{
			switch (TRUE) 
			{
			case ($lvl == MSG_LOG_WARNING):
				$p_tag = '<p class="warning">';
				break;
			case ($lvl == MSG_LOG_ERROR):
				$p_tag = '<p class="error">';
				break;
			default: // default to normal
				$p_tag = '<p>';
			} // switch
			$s .= $p_tag . $msg . "</p>\n";
		} // if strlen
	} // foreach
	return( $s );
} // message_log_format


function message_log_table( $p_msg_log )
// return a string with the html table-formatted message log
{
	$s = '';
	foreach ($p_msg_log as $log) 
	{
		$msg = $log[0];
		$lvl = $log[1];
		if (strlen( $msg ) > 0) 
		{
			switch (TRUE) 
			{
			case ($lvl == MSG_LOG_WARNING):
				$p_tag = '<td class="warning">';
				break;
			case ($lvl == MSG_LOG_ERROR):
				$p_tag = '<td class="error">';
				break;
			default: // default to normal
				$p_tag = '<td>';
			} // switch
			$s .= "<tr>" . $p_tag . $msg . "</td></tr>\n";
		} // if strlen
	} // foreach
	$s = (strlen( $s ) ? "<table>$s</table>\n" : '');
	return( $s );
} // message_log_table


function message_log_append( &$p_msg_log, $msg, $level = MSG_LOG_NORMAL, $errno = ERR_UNSPEC )
{
	$p_msg_log[] = array( $msg, $level, $errno );
} // message_log_append


function current_state()
////////////////////////////////////////////
//
// Return the value of client's session 
// state variable.
//
////////////////////////////////////////////
{
	return( isset( $_SESSION[ 'state_value' ] ) ? $_SESSION[ 'state_value' ] : NULL );
} // current_state



function next_state( $p_state )
////////////////////////////////////////////
//
// Update the client's session so that the
// state variable is set to $p_state
//
////////////////////////////////////////////
{
GLOBAL $msg_log;
GLOBAL $DEBUG;


 //GLOBAL $DEBUG; ///reynan need to enable this
//
//$Reynan = "";
//	if($DEBUG == "ERROR1")
//	{
//		$Reynan = FALSE;
//	}
//	else
//	{
//		$Reynan = TRUE;
//	}





	$_SESSION[ "state_value" ] = $p_state;
if ($DEBUG) {message_log_append( $msg_log, "state changed to $p_state\n" );}
} // next_state



function prompt_for_xml_input()
////////////////////////////////////////////
//
// Display a page which prompts the user
// to browse the local web client's disk
// system and upload an XML file containing
// an NCC order.
//
////////////////////////////////////////////
{
	$xml_prompt_page = "upload-prompt.html";
	readfile( $xml_prompt_page );
} // prompt_for_xml_input


function nav_buttons( $p_keys = NULL, $p_import = FALSE, $p_headers = NULL, $p_cont_key = NULL )
// return a string containing HTML buttons which will allow the 
// client to navigate through the contract array, or cancel.
// if $p_cont_key is passed and if it points to a contract
// with ERROR-level log messages, a delete button will appear.
// If no errors are detected in any of the contract verification
// logs, and if $p_import is TRUE, then an Import button will
// be presented.  This should mean that the Import and Delete
// buttons will never both appear.

// $p_keys can be an array of contract keys to display, or NULL.
// Each contract key will be displayed as a clickable button
// to display that contract.  If NULL, we won't display any
// clickable contract buttons.

// $p_headers is optional.  If passed, we'll examine the ['MSG_LOG']
// element and treat it as an array of message_logs, where each 
// message_log is an array of log entries [0]=message, [1]=loglevel.
// We'll note the highest loglevel found, and style the nav button 
// for that contract 'warn_button' if the highest loglevel is 
// MSG_LOG_WARNING, or 'error_button' if it is MSG_LOG_ERROR.
{
	$s  = '<form action="form_handler.php" method="GET">';
	$s .= "\n<div>\n";

// condense the header logs to a single maximum loglevel per contract.
// also find the highest loglevel and total spots in the entire campaign.
// $camp_loglevel and $camp_spots will remain 0 if $p_headers is not
// passed.

	$camp_spots    = 0;	// total spots in the entire campaign
	$camp_loglevel = 0;	// highest loglevel in the entire campaign
	$p_loglevel = array();

	if (is_array( $p_headers )) 
	{
		foreach (array_keys( $p_headers ) as $key) 
		{
			$hdr = $p_headers[ $key ];
			$camp_spots += $hdr[ 'total_spots' ];
			$j = 0;
			foreach ($hdr['MSG_LOG'] as $log) 
			{
				foreach ($log as $msg) 
				{
					if ($msg[1] > $j) $j = $msg[1];
					if ($msg[1] > $camp_loglevel) $camp_loglevel = $msg[1];
				} // foreach
			} // foreach
			$p_loglevel[ $key ] = $j;
		} // foreach
	} // if is_array

	if (is_array( $p_keys )) 
	{
		$syscode_list = array();	// to build a drop-down list
		$buttons = "";
		$j = 0;
		foreach ($p_keys as $key) 
		{
			$syscode_list[ $key ] = $syscode = $p_headers[ $key ]['SysCode'];
//			$buttons .= '<label for="' . $key . '">' .
//					$key . $key . '</label>' . "\n";
			switch (TRUE) 
			{
			case (is_null( $p_headers[ $key ] )):
			case ($p_headers[ $key ][ 'detail_spots' ] == 0):
				$style = 'class="blank_button" '; // defined in style.php
				break;
			case ($p_loglevel[ $key ] == MSG_LOG_WARNING):
				$style = 'class="warn_button" '; // defined in style.php
				break;
			case ($p_loglevel[ $key ] == MSG_LOG_ERROR):
				$style = 'class="error_button" '; // defined in style.php
				break;
			default:
				$style = 'class="good_button" '; // defined in style.php
				break;
			} // switch
			$style .= 'style="height: 35px; width: 35px" ';
			$buttons .= '<input type="submit" name="action" ' .
					'title="SysCode ' . $syscode . '" ' .
					$style .
					'id="' . $key . '" ' .
					'value="' . $key . '" ' .
					'>';
			if (++$j % 25 == 0) {$buttons .= '<br>';}
		} // foreach

		$syscode_dropdown = ':';
		if (asort( $syscode_list )) 
		{
			$syscode_dropdown = " or select a SysCode: " .
						"<select name=\"syscode\">\n";
			foreach ($syscode_list as $key => $syscode) 
			{
				$selected = ($p_cont_key === $key)
						? ' selected="selected"' 
						: '';
				$syscode_dropdown .= "<option$selected value=$key>" .
					"$syscode</option>\n";
			}
			$syscode_dropdown .= "</select>&nbsp;";
			$syscode_dropdown .= "<input type=\"submit\" name=\"action\" " . 
						"value=\"Select\">\n";
		} // if

		$s .= "<br>Click a contract button$syscode_dropdown<br><hr>$buttons<br><hr>\n";

		$prevnext  = "<input type=\"submit\" name=\"action\" value=\"Prev\">\n";
		$prevnext .= "<input type=\"submit\" name=\"action\" value=\"Next\">\n";

		$p_delete = !is_null( $p_cont_key );
		if ($p_delete)
		{
			$p_delete = (isset( $p_loglevel[ $p_cont_key ] ) && ($p_loglevel[ $p_cont_key ] >= MSG_LOG_ERROR));
		}
		$p_delete = TRUE;	// always allow delete
		if ($p_delete || (isset( $_COOKIE[ 'TTVM_DEBUG' ] ) && ($_COOKIE[ 'TTVM_DEBUG' ] == 'TRUE'))) 
		{
			$prevnext .= "<input type=\"submit\" name=\"action\" " . "value=\"Delete Contract\">\n";
		}
		///$p_import = $reynan;  //reynan need to enable this
		$p_import = $p_import && ($camp_spots > 0);
		if ($p_import && ($camp_loglevel < MSG_LOG_ERROR)) 
		{
			$prevnext .= "<input type=\"submit\" name=\"action\" value=\"Import\">\n";
		}
		////reynan need to put code here

	} 
	else 
	{
		$prevnext = "";
	} // if is_array

	$s .= "<input type=\"submit\" name=\"action\" value=\"Cancel\">\n";
	$s .= $prevnext;

	$s .= "</div>\n</form>\n";
	return( $s );
} // nav_buttons


function customer_warn( $p_campaign, &$p_msg_log )
{
        if (is_null( $p_campaign[ 'Customer Record' ] )) 
		{
                $cu_name = $p_campaign[ 'Customer Name' ];
                message_log_append( $p_msg_log, "Customer name '$cu_name' not found -- " . 
			"A NEW CUSTOMER will be created", MSG_LOG_WARNING );
        }
} // customer_warn


function agency_warn( $p_campaign, &$p_msg_log )
{
        if (is_null( $p_campaign[ 'Agency Record' ] )) 
		{
                $ag_name = $p_campaign[ 'Agency Name' ];
                message_log_append( $p_msg_log, "Agency name '$ag_name' not found -- " . 
			"A NEW AGENCY will be created", MSG_LOG_WARNING );
        }
} // agency_warn


function verify_campaign( &$campaigns, $headers, $details )
//  $campaigns could be modified, but $headers and $details will not be
//  we'll log any errors/warnings found into $campaigns[0]['MSG_LOG']
{
	$success = TRUE;
	message_log_reset( $msg_log );	// use a local $msg_log

	customer_warn( $campaigns[0], $msg_log ); // warn if customer will be auto-created
	agency_warn(   $campaigns[0], $msg_log ); // warn if agency   will be auto-created

	if ($campaigns[0]['StartDate'] > $campaigns[0]['EndDate']) 
	{
		$success = FALSE;
		message_log_append( $msg_log, 
			"Campaign start date is beyond the end date", 
			MSG_LOG_ERROR );
	}
// Verify total spots and total cost on the campaign.

// For TelAmerica, all detail sums to the contract totals, and all
// contracts sum to the campaign totals.

// For NCC, the detail sums to the contract totals, but the contracts
// do not necessarily sum to the campaign totals.

	$cam_spots = 0;	// across entire campaign
	$cam_cost  = 0;	// across entire campaign

	$keys = array_keys( $details );

	foreach ($keys as $key) 
	{
	    foreach ($details[ $key ] as $ln) 
		{
			$cam_spots += $ln[ 'nOrdered' ];
// calculate values with binary precision arithmetic routines
			$v = bcmul( bcmul( $ln[ 'nOrdered' ], $ln[ 'UnitPrice' ], 2 ), '0.01', 2 );
			$cam_cost = bcadd( $cam_cost, $v, 2 );
	    } // foreach line item in the contract detail
	} // foreach detail key (contract) in the campaign

	$cust_name = $campaigns[0]['Customer Name'];

// Add 'detail_spots' and 'detail_cost' fields to campaign array.
// These fields will hold the total spots and costs as summed from
// individual contract headers.  These should agree with 'total_spots'
// and 'total_cost' but we will check that.

	$campaigns[ 0 ][ 'detail_spots' ] = $cam_spots;
	$campaigns[ 0 ][ 'detail_cost' ]  = $cam_cost;

// If NCC, we have to fudge the campaign totals or they won't match.
	if ($cust_name === 'NCC') 
	{
		$campaigns[ 0 ][ 'total_spots' ] = $cam_spots;
		$campaigns[ 0 ][ 'total_cost'  ] = $cam_cost;
	} // if NCC
	$campaigns[0]['MSG_LOG'] = $msg_log;
} // verify_campaign


function html_campaign( $p_camp )
// format a campaign header $p_camp[0] for HTML display
{
  $html = <<<EOF
  <table width="750">
  <tr>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
  </tr>
EOF;

  $cust_html_class = is_null( $p_camp[0]['Customer Record'] ) ? "class='warning' " : '';
  $agcy_html_class = is_null( $p_camp[0]['Agency Record'] ) ? "class='warning' " : '';

  $html .= "<tr>" .
		"<td colspan=5 $cust_html_class>Customer: " . $p_camp[0]['Customer Name'] . 
		"</td>" .
		"<td colspan=5 $agcy_html_class>Agency: " . $p_camp[0]['Agency Name'] . 
		"</td>" .
	   "</tr>\n";

  $html .= "<tr><td colspan=5>Campaign ID " . $p_camp[0]['CampKeyID'] . 
//		" (ver " . $p_camp[0]['CampKeyVer'] . ")" .
		"</td>" . 
		"<td colspan=5>" . $p_camp[0]['contract_name'] . "</td>" .
	   "</tr>\n";

  $html .= "<tr><td colspan=5>Order # " . $p_camp[0]['CustOrder'] . 
//		" (ver " . $p_camp[0]['CampOrderKeyVer'] . ")" .
		"</td>" . 
		"<td colspan=5>Order Date " . $p_camp[0]['UpdateDate'] . "</td>" .
	   "</tr>\n";

  $html .= "<tr><td colspan=5>Flight Dates: " .
		$p_camp[0]['StartDate'] . ' -> ' . $p_camp[0]['EndDate'] . '</td>' . 
		"<td colspan=5>MakeGood Policy: " . $p_camp[0]['MakeGoodPolicy'] . '</td>' .
	   "</tr>\n";

  $total_html_class = '';
  if (bccomp( $p_camp[0][ 'total_spots' ], $p_camp[0][ 'detail_spots' ], 2 ) != 0)
  {	  
		$total_html_class = " class='error'";
  }
  if (bccomp( $p_camp[0][ 'total_cost' ], $p_camp[0][ 'detail_cost' ], 2 ) != 0) 
  {
		$total_html_class = " class='error'";
  }
  $html .= "<tr><td colspan=5 $total_html_class>Campaign totals: " .
		$p_camp[0]['total_spots'] . ' spots, $' . $p_camp[0]['total_cost'] . 
		'</td>' . "<td colspan=5 $total_html_class>Detail totals: " . 
		$p_camp[0]['detail_spots'] . ' spots, $' . $p_camp[0]['detail_cost'] . 
		'</td>' . 
	   "</tr>\n";

  $html .= "</table>\n";

  $html .= message_log_format( $p_camp[0]['MSG_LOG'] );
  return( $html );
} // html_campaign


function html_order( $p_head, $p_num )
// format an order header $p_head for HTML display
{
  $html = <<<EOF
  <table width="750">
  <tr>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
    <td width="10%"></td>
  </tr>
EOF;

  $html .= "<tr><td colspan=2>Contract #" . $p_num . "</td>" .
		"<td colspan=3>System Order " . $p_head['SystemOrder'] . "</td>" .
		"<td colspan=5>Syscode/Sitename: " .
		$p_head['SysCode'] . ' / ' . $p_head['SiteName'] . "</td>" .
	   "</tr>\n";

  $total_html_class = '';
  if (bccomp( $p_head[ 'total_spots' ], $p_head[ 'detail_spots' ], 2 ) != 0)
  {
		$total_html_class = " class='error'";
  }
  if (bccomp(  $p_head[ 'total_cost' ], $p_head[ 'detail_cost' ], 2 ) != 0) 
  {
		$total_html_class = " class='error'";
  }
  $html .= "<tr><td colspan=5 $total_html_class>Contract totals: " .
		$p_head['total_spots'] . ' spots, $' . $p_head['total_cost'] . 
		'</td>' . "<td colspan=5 $total_html_class>Detail totals: " . 
		$p_head['detail_spots'] . ' spots, $' . $p_head['detail_cost'] . 
		'</td>' . 
	   "</tr>\n";

  $html .= "</table>\n";

  $j = 0;
  foreach ($p_head['MSG_LOG'] as $log) 
  {
//$html .= "<p>Log group " . ++$j . ":</p>\n";
	$html .= message_log_table( $log );
  }
  return( $html );
} // html_order


function html_detail( $p_detail, $p_num )
// format an order's detail array $p_detail for HTML display
{
  $html = "<table width=\"750\" columns=\"10\">
";

  $html .= "<tr>";
  $html .= "  <th class=\"left\">Line #</th>";	// 1
  $html .= "  <th class=\"left\">Time</th>";	// 2
  $html .= "  <th class=\"left\">WkDays</th>";	// 3
  $html .= "  <th class=\"left\">Net</th>";	// 4
  $html .= "  <th class=\"left\">Distrib</th>";	// 5
  $html .= "  <th class=\"right\">Price</th>";	// 6
  $html .= "  <th class=\"right\">Value</th>";	// 7
  $html .= "</tr>
";

  foreach ($p_detail as $p_det) 
  {
    $ln  = "  <tr>\n";
    $ln .= "    <td>" . $p_det[ 'LineID' ] . "</td>\n";
    $ln .= "    <td>" . 
	substr( $p_det[ 'StartDate' ], 5 ) . ' - ' . 
	substr( $p_det[ 'EndDate' ],   5 ) . ' / ' .
	$p_det[ 'TimeOn' ] . ' - ' . $p_det[ 'TimeOff' ] . 
	"</td>\n";
// Here we might translate DayMask 'YYYYNYY' or similar to 
// MoTuWeTh--SaSu or similar
    $ln .= "    <td>" . $p_det[ 'DayMask' ] . "</td>\n";
    $ln .= "    <td>" . $p_det[ 'Network' ] . "</td>\n";
    $ln .= "    <td>" . $p_det[ 'Distribution' ] . "</td>\n";
    $price = bcmul( $p_det['UnitPrice'], '0.01', 2 );
    $ln .= "    <td class=\"right\">" . $price . "</td>\n";
    $ln .= "    <td class=\"right\">" . $p_det[ 'Value' ] . "</td>\n";
    $ln .= "  </tr>\n";
    if (isset( $p_det[ 'MSG_LOG' ] )) 
	{
      $button = '';
      foreach ($p_det['MSG_LOG'] as $p_log) 
	  {
		if ($p_log[2] == ERR_NO_NETWORK) 
		{
			$style  = 'class="error_button" ';
			$button = '<a href="form_handler.php?' . 
				'action=DelNet' . '&' .
			'LineID=' . $p_det['LineID'] . '" ' .
			'class="error" ' . 
			'title="Delete Network ' . $p_det['Network'] . '" ' .
			'>&nbsp;Delete&nbsp;</a>';
			break;
		} // if
      } // foreach
      $ln .= "  <tr><td>$button</td>\n";
      $ln .= "    <td colspan=6>" . message_log_table( $p_det['MSG_LOG'] ) . "</td>\n";
      $ln .= "  </tr>\n";
    }
    $html .= $ln;
  }
  $html .= "</table>\n";
  return( $html );
} // html_detail


////////////////////////////////////////////
//
// Given one contract's detail lines, and a
// LineID, copy all detail lines that do not
// match that LineID.  Return the copied array
// (which will not contain the detail lines
// which matched the LineID given).  Array
// indices begin at 0.
//
// We must set $p_spots to the total number
// of spots deleted and $p_value to their
// total value.
//
////////////////////////////////////////////
function delete_detail_lineid( $p_details, $p_lineID, &$p_spots, &$p_value )
{
	$kept = array();  // the array rows we will keep
	$p_spots = 0;
	$p_value = 0;
	foreach ($p_details as $p_row) 
	{
		if ($p_row[ 'LineID' ] == $p_lineID) 
		{
			$p_spots += $p_row[ 'nOrdered' ];
			$p_value  = bcadd( $p_value, $p_row[ 'Value' ], 2 );
		} 
		else 
		{
			$kept[] = $p_row;
		} // if
	} // foreach
	return( $kept );
} // delete_detail_lineid


////////////////////////////////////////////
//
// Given one contract's detail lines, and a
// Network, copy all detail lines that do not
// match that Network.  Return the copied array
// (which will not contain the detail lines
// which matched the Network given).  Array
// indices begin at 0.
//
// We will set $p_spots to the total number
// of spots deleted and $p_value to their
// total value.
//
////////////////////////////////////////////
function delete_detail_network( $p_details, $p_network, &$p_spots, &$p_value )
{
	$kept = array();  // the array rows we will keep
	$p_spots = 0;
	$p_value = 0;
	foreach ($p_details as $p_row) 
	{
		if ($p_row[ 'Network' ] == $p_network) 
		{
			$p_spots += $p_row[ 'nOrdered' ];
			$p_value  = bcadd( $p_value, $p_row[ 'Value' ], 2 );
		} 
		else 
		{
			$kept[] = $p_row;
		} // if
	} // foreach
	return( $kept );
} // delete_detail_network


function next_cont_key( $p_cont_key, $p_keys)
{
	if (is_null( $p_cont_key ))
	{
		$p_cont_key = $p_keys[0];
	}
	else 
	{
		$i = array_search( $p_cont_key, $p_keys );
		$next = $i+1;
		if (isset( $p_keys[ $next ] )) 
		{
			$p_cont_key = $p_keys[ $next ];
		}
	} // if
	return( $p_cont_key );
} // next_cont_key


function prev_cont_key( $p_cont_key, $p_keys)
{
	if (is_null( $p_cont_key ))
	{
		$p_cont_key = $p_keys[0];
	}
	else 
	{
		$i = array_search( $p_cont_key, $p_keys );
		$prev = $i-1;
		if (isset( $p_keys[ $prev ] ))
		{
			$p_cont_key = $p_keys[ $prev ];
		}
	} // if
	return( $p_cont_key );
} // prev_cont_key


function process_state( $ps_state )
////////////////////////////////////////////
//
// A basic state machine.
//
// Given the $ps_state value passed, perform
// the action dictated by that state.
//
////////////////////////////////////////////
{
GLOBAL $argv;
GLOBAL $msg_log;
GLOBAL $db_host, $db_user, $db_pwd, $db_name;
GLOBAL $DEBUG;

	$done = FALSE;

// $cont_key is an integer 1 .. N of the contract number 
// we'll display.  It indexes the arrays $headers and $details

	$cont_key  = isset( $_SESSION[ 'cont_key'  ] ) ? $_SESSION[ 'cont_key'  ] : NULL;
	$campaigns = isset( $_SESSION[ 'campaigns' ] ) ? $_SESSION[ 'campaigns' ] : NULL;
	$headers   = isset( $_SESSION[ 'headers'   ] ) ? $_SESSION[ 'headers'   ] : NULL;
	$details   = isset( $_SESSION[ 'details'   ] ) ? $_SESSION[ 'details'   ] : NULL;

// loop until we reach a state where the user (web client)
// must perform an action to determine what the next state
// will be.

	output_style_header();
	do {

		message_log_reset( $msg_log );
		if ($DEBUG)
		{
			message_log_append( $msg_log, "database name is $db_name\n" );
		}
		if ($DEBUG)
		{
			message_log_append( $msg_log, "state is $ps_state\n" );
		}
		switch (TRUE) 
		{
/////////////////////////////////
//
//  Initial state is BEGIN
//
/////////////////////////////////
			case ($ps_state == 'BEGIN'):
//  Clear all keys in array $_SESSION
				foreach (array_keys( $_SESSION ) as $sess_key) 
				{
					unset( $_SESSION[ $sess_key ] );
				} // foreach
//  Proceed to a prompting state
				next_state( 'PROMPT_FOR_XML_INPUT' );
				break;
/////////////////////////////////
//
//  Prompt the user to provide an
//  XML file.
//
/////////////////////////////////
			case ($ps_state == 'PROMPT_FOR_XML_INPUT'):
// We are ready to prompt for an input file
				if (CLI) 
				{
					;
// In CLI mode, the input file name will be on the command line.
				} 
				else 
				{
					prompt_for_xml_input();
				}
//  The next step is to check the XML file for basic sanity checks
				next_state( 'CHECK_XML_FILENAME' );
//  But first we have to wait for the web client to upload the file
				$done = TRUE;
				if (CLI) 
				{
					$done = FALSE;
				}
				break;
/////////////////////////////////
//
//  Do some basic checking on the
//  XML file the user provided.
//
/////////////////////////////////
			case ($ps_state == 'CHECK_XML_FILENAME'):
				$input_nam = 'xmlfile'; // per the POST form
				$filnam = isset( $_FILES[ $input_nam ] ) ? 
						 $_FILES[ $input_nam ][ 'name' ] : NULL;
// The full path to where the file was uploaded on the server
				$filtmp = isset( $_FILES[ $input_nam ] ) ? 
						 $_FILES[ $input_nam ][ 'tmp_name' ] : NULL;
				if (is_null( $filnam ) || is_null( $filtmp ) ||
						(strlen( $filnam ) == 0) ||
						(strlen( $filtmp ) == 0)) 
				{
					next_state( 'BEGIN' );
				} 
				else 
				{
					next_state( 'CHECK_XML_UPLOAD' );
				}
				break;
			case ($ps_state == 'CHECK_XML_UPLOAD'):
				if (CLI) 
				{
					$filtmp = $argv[1];
					echo "input file is $filtmp\n";
				}
// The size of the file in bytes
				$filsiz = $_FILES[ $input_nam ][ 'size' ];
// The error code of the upload process
				$filerr = $_FILES[ $input_nam ][ 'error' ];
				if (CLI || $filerr == UPLOAD_ERR_OK) 
				{
					next_state( 'PARSE_XML_UPLOAD' );
				} 
				else 
				{
					message_log_append( $msg_log, "Upload error $filerr", MSG_LOG_ERROR );
					$done = TRUE;
					next_state( 'BEGIN' );	// start over
				}
				break;
/////////////////////////////////
//
//  Do a full parsing of the
//  XML file the user provided.
//
/////////////////////////////////

			case ($ps_state == 'PARSE_XML_UPLOAD'):
				open_mysql();
//  What is the path portion of the $filtmp filename?
				$_SESSION[ 'xmlfile' ] = $filtmp;
				if ($DEBUG) 
				{
					echo "calling parse_xml<br>\n";
					echo "parsing file: $filtmp<br>\n";
				}
//  Set state to start over, in case we fail before parse_xml
// returns.
				next_state( 'BEGIN' );
// parse the XML file, returning an array of contract headers,
// and an array of arrays of detail lines.  First-order indices of 
// headers and details arrays will be integers starting from 1.
				if (parse_xml( $filtmp, $campaigns, $headers, $details )) 
				{
					next_state( 'VERIFY_CAMPAIGN' );
				} 
				else 
				{
					message_log_append( $msg_log, "XML order parsing failed", MSG_LOG_ERROR );
					unset( $_SESSION[ 'campaigns' ] );
					unset( $_SESSION[ 'headers'   ] );
					unset( $_SESSION[ 'details'   ] );
					next_state( 'TRY_AGAIN' );
				}
				break;
/////////////////////////////////
//
//  Verify some basic integrity
//  checks on the data, and create
//  human-readable log messages for
//  problems that are identified.
//
/////////////////////////////////
			case ($ps_state == 'RE-VERIFY_CAMPAIGN'):
// unset all header row MSG_LOG values
				foreach (array_keys( $headers ) as $key)
				{
				    unset( $headers[ $key ][ 'MSG_LOG' ] );
				}
// unset all detail line MSG_LOG values
				foreach (array_keys( $details ) as $dkey)
				{
				    foreach (array_keys( $details[ $dkey ] ) as $ln)
					{
						unset( $details[ $dkey ][ $ln ][ 'MSG_LOG' ] );
					}
				}
// and fall through to VERIFY_CAMPAIGN:
			case ($ps_state == 'VERIFY_CAMPAIGN'):
				verify_campaign( $campaigns, $headers, $details );
				next_state( 'BEGIN' );	// as precaution
//echo "calling verify_all_contracts<br>";
				if (verify_all_contracts( $campaigns, $headers, $details )) 
				{
//echo "vac succeeded<br>";
				} 
				else 
				{
//echo "vac failed<br>";
				}
				next_state( ($ps_state == 'VERIFY_CAMPAIGN')
					? 'DISPLAY_CAMPAIGN'
					: 'DISPLAY_CONTRACT' );
				break;
			case ($ps_state == 'DISPLAY_CAMPAIGN'):
				unset( $cont_key );	// contract array key
				next_state( 'DISPLAY_CONTRACT' );
				break;
			case ($ps_state == 'DISPLAY_CONTRACT'):
//echo "$ps_state<br>";
				if (is_null( $campaigns ) ||
				    is_null( $headers   ) || is_null( $details   )) 
				{
				    next_state( 'BEGIN' );
				} 
				else 
				{
				    if (!isset( $cont_key ) || is_null( $cont_key )) 
					{
						$keys = array_keys( $headers );
						$cont_key = $keys[0];
				    } // if
				    if (is_null( $cont_key )) 
					{
						next_state( 'BEGIN' );
				    } 
					else 
					{
						echo html_campaign( $campaigns );
						echo nav_buttons( array_keys( $headers ), 
							  TRUE,
							  $headers,
							  $cont_key );
						echo html_order( $headers[$cont_key], $cont_key );
						echo html_detail( $details[$cont_key], $cont_key );
// set a safe next state.  form_handler ought to override, unless error.
						next_state( 'BEGIN' );
						$done = TRUE;
				    } //if
				} // if
				break;
			case ($ps_state == 'DISPLAY_NEXT'):
				$keys = array_keys( $headers );
				$cont_key = next_cont_key( $cont_key, $keys );
// we might someday use a better state than BEGIN here.  A NULL
// return from next_cont_key would mean that no contracts remain.
				next_state( is_null( $cont_key ) ? 'BEGIN' : 'DISPLAY_CONTRACT' );
				break;
			case ($ps_state == 'DISPLAY_PREV'):
				$keys = array_keys( $headers );
				$cont_key = prev_cont_key( $cont_key, $keys );
				next_state( is_null( $cont_key ) ? 'BEGIN' : 'DISPLAY_CONTRACT' );
				break;
/////////////////////////////////
//
//  Push SQL file out to
//  the web client for download.
//
/////////////////////////////////
			case ($ps_state == 'PUSH_SQL_FILE'):
				next_state( 'BEGIN' );
				if (CLI) 
				{
					echo "SQL output is in file $sql_file\n";
				} 
				else 
				{
					header( 'Content-type: application/force-download' );
    				header( 'Content-Transfer-Encoding: Binary' );
    				header( 'Content-length: ' . filesize( $sql_file ) );
    				header( 'Content-disposition: attachment; filename="' . basename( $sql_file ) . '"' );
    				readfile( $sql_file );
					unlink( $sql_file );   // delete
				}
				$done = TRUE;
				break;
/////////////////////////////////
//
//  Connect to the SQL server and
//  move the data into the database.
//
/////////////////////////////////
			case ($ps_state == 'IMPORT'):
				next_state( 'BEGIN' );
				open_mysql();
				insert_sql( $campaigns, $headers, $details );
				echo message_log_format( $msg_log );
				message_log_reset( $msg_log );
				break;
/////////////////////////////////
//
//  Delete a LineID from a contract
//
/////////////////////////////////
			case ($ps_state == 'DELETE_LINE'):
				$lineID	   = $_SESSION[ 'LineID'    ];  // XML LineID number
//message_log_append( $msg_log, 'You requested to delete LineID ' . $lineID . ' from contract key ' . $cont_key );

// Deleting detail lines will change the header spot and value totals.
// delete_detail_lineid is responsible for telling us how many spots
// were deleted, and what their total value was.
				$spots = 0;
				$value = 0;
				$details[ $cont_key ] = delete_detail_lineid( 
					$details[ $cont_key ], $lineID, $spots, $value );

// subtract deleted spots and value from header 'detail_*' totals
				$headers[ $cont_key ][ 'detail_spots' ] -= $spots;
				$v = $headers[ $cont_key ][ 'detail_cost' ];
				$v = bcsub( $v, $value, 2 );
				$headers[ $cont_key ][ 'detail_cost' ] = $v;

// subtract deleted spots and value from header 'total_*' totals
				$headers[ $cont_key ][ 'total_spots' ] -= $spots;
				$v = $headers[ $cont_key ][ 'total_cost' ];
				$v = bcsub( $v, $value, 2 );
				$headers[ $cont_key ][ 'total_cost' ] = $v;

// subtract deleted spots and value from campaign 'detail_*' totals
				$campaigns[0][ 'detail_spots' ] -= $spots;
				$v = $campaigns[0][ 'detail_cost' ];
				$v = bcsub( $v, $value, 2 );
				$campaigns[0][ 'detail_cost' ] = $v;

// subtract deleted spots and value from campaign 'total_*' totals
				$campaigns[0][ 'total_spots' ] -= $spots;
				$v = $campaigns[0][ 'total_cost' ];
				$v = bcsub( $v, $value, 2 );
				$campaigns[0][ 'total_cost' ] = $v;

// Now re-validate everything, and re-display this
// specific contract.
				next_state( 'RE-VERIFY_CAMPAIGN' );
				break;
/////////////////////////////////
//
//  Delete a Network from a contract
//
/////////////////////////////////
			case ($ps_state == 'DELETE_NETWORK'):
next_state( 'BEGIN' );
				$lineID	   = $_SESSION[ 'LineID'    ];  // XML LineID number
				$network   = NULL;
				foreach ($details[ $cont_key ] as $det) 
				{
					if ($det[ 'LineID' ] === $lineID) 
					{
						$network = $det[ 'Network' ];
//echo "<pre>"; var_dump( $det ); echo "</pre>";
						break;
					}
				}

				next_state( 'RE-VERIFY_CAMPAIGN' );
				if (is_null( $network)) 
				{
message_log_append( $msg_log, 'Invalid LineID: ' . $lineID . ' not found in contract key ' . $cont_key );
					break;
				}

// Deleting detail lines will change the header spot and value totals.
// delete_detail_network is responsible for telling us how many spots
// were deleted, and what their total value was.
				$spots = 0;
				$value = 0;
				$details[ $cont_key ] = delete_detail_network( 
					$details[ $cont_key ], $network, $spots, $value );

// subtract deleted spots and value from header 'detail_*' totals
				$headers[ $cont_key ][ 'detail_spots' ] -= $spots;
				$v = $headers[ $cont_key ][ 'detail_cost' ];
				$v = bcsub( $v, $value, 2 );
				$headers[ $cont_key ][ 'detail_cost' ] = $v;

// subtract deleted spots and value from header 'total_*' totals
				$headers[ $cont_key ][ 'total_spots' ] -= $spots;
				$v = $headers[ $cont_key ][ 'total_cost' ];
				$v = bcsub( $v, $value, 2 );
				$headers[ $cont_key ][ 'total_cost' ] = $v;

// subtract deleted spots and value from campaign 'detail_*' totals
				$campaigns[0][ 'detail_spots' ] -= $spots;
				$v = $campaigns[0][ 'detail_cost' ];
				$v = bcsub( $v, $value, 2 );
				$campaigns[0][ 'detail_cost' ] = $v;

// subtract deleted spots and value from campaign 'total_*' totals
				$campaigns[0][ 'total_spots' ] -= $spots;
				$v = $campaigns[0][ 'total_cost' ];
				$v = bcsub( $v, $value, 2 );
				$campaigns[0][ 'total_cost' ] = $v;

				message_log_append( $msg_log, 
					'Network ' . $network . 
					' deleted from contract ' . $cont_key );

// Now re-validate everything, and re-display this
// specific contract.
				next_state( 'RE-VERIFY_CAMPAIGN' );
				break;
/////////////////////////////////
//
//  Delete an entire contract
//
/////////////////////////////////
			case ($ps_state == 'DELETE_CONTRACT'):
//message_log_append( $msg_log, 'You requested to delete contract ' . $cont_key );

// Unset the individual detail lines for this contract.
				$dkeys = array_keys( $details[ $cont_key ] );
				foreach ($dkeys as $key) 
				{
					unset( $details[ $cont_key ][ $key ] );
				}
// subtract contract spots and value from campaign 'detail_*' totals
				$spots = $headers[ $cont_key ][ 'detail_spots' ];
				$value = $headers[ $cont_key ][ 'detail_cost'  ];
				$campaigns[0][ 'detail_spots' ] -= $spots;
				$v = $campaigns[0][ 'detail_cost' ];
				$v = bcsub( $v, $value, 2 );
				$campaigns[0][ 'detail_cost' ] = $v;

// subtract contract spots and value from campaign 'total_*' totals
				$spots = $headers[ $cont_key ][ 'total_spots' ];
				$value = $headers[ $cont_key ][ 'total_cost'  ];
				$campaigns[0][ 'total_spots' ] -= $spots;
				$v = $campaigns[0][ 'total_cost' ];
				$v = bcsub( $v, $value, 2 );
				$campaigns[0][ 'total_cost' ] = $v;

// Set the contract header to show 0 spots, 0 value.
				$spots = 0;
				$value = 0;
				$headers[ $cont_key ][ 'detail_spots' ] = $spots;
				$headers[ $cont_key ][ 'detail_cost'  ] = $value;
				$headers[ $cont_key ][ 'total_spots'  ] = $spots;
				$headers[ $cont_key ][ 'total_cost'   ] = $value;

// Unset the 'versionN' fields of deleted contracts so that version
// problems don't prevent the remaining contracts from being imported.
				$j = 0;
				$done = FALSE;
				while (!$done) 
				{
					$fld = 'version' . (++$j);
					$done = !isset( $headers[ $cont_key ][ $fld ] );
					unset( $headers[ $cont_key ][ $fld ] );
				} // while
// auto-advance to next contract
				$done = FALSE;
				$keys = array_keys( $headers );
				$cont_key = next_cont_key( $cont_key, $keys );
// we might someday use a better state than BEGIN here.  A NULL
// return from next_cont_key would mean that no contracts remain.
				next_state( is_null( $cont_key ) ? 'BEGIN' : 'RE-VERIFY_CAMPAIGN' );
				break;
/////////////////////////////////
//
//  Dummy stub.
//
/////////////////////////////////
			case ($ps_state == 'DEAD_END'):
			case ($ps_state == 'STOP'):
				echo $ps_state;
				next_state( 'BEGIN' );
				$done = TRUE;
				break;
/////////////////////////////////
//
//  Screen has error messages.
//  Force user to go back and retry.
//
/////////////////////////////////
			case ($ps_state == 'TRY_AGAIN'):
				next_state( 'BEGIN' );
				echo nav_buttons( NULL, FALSE );	// Cancel only
				$done = TRUE;
				break;
/////////////////////////////////
//
//  State is unknown or invalid.
//  Go back to BEGIN state.
//
/////////////////////////////////
			default:
				message_log_append( $msg_log, 'Invalid state label: ' . $ps_state, MSG_LOG_ERROR );
				next_state( 'BEGIN' );
				$done = TRUE;

		} // switch
		$ps_state = current_state();
//		echo message_log_format( $msg_log );
		echo message_log_table( $msg_log );
	} while (!$done);
	$_SESSION[ 'campaigns' ] = $campaigns;
	$_SESSION[ 'headers'   ] = $headers;
	$_SESSION[ 'details'   ] = $details;
	$_SESSION[ 'cont_key'  ] = $cont_key;
} // process_state


function output_html_head()
// can we put up a 'loading' indicator?
{
echo '<html>
<head>
<title></title>
<script language="javascript">
function toggle(e) {
if (e.style.display == "none") {
e.style.display = "";
} else {
e.style.display = "none";
}
}
</script>
</head>
<body onload="toggle(progress)">
<div id="progress">Animation/Text/What Ever Here</div>
';
} // output_html_head


function main()
{
GLOBAL $argv;

	if (CLI || session_start()) 
	{
//output_html_head();
// what state are we in with the current session?

		if (is_null( current_state() )) 
		{
			next_state( "BEGIN" );
		}
		process_state( current_state() );

//echo '</body>
//</html>
//';
	} 
	else 
	{
		echo "SESSION_START has failed -- are cookies enabled in your browser?<br>\n";
	} // if session_start
} // main



main();
ob_end_flush();
?>
