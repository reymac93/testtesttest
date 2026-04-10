<?

// Routine to output old and newly-entered detail data
// to facilitate comparison of data for validation purposes.


function field_list( $table, $db_conn )
// return a string with the comma-separated list of fields in
// $table.  return NULL on error.
{
        $result = mysql_query( "SHOW COLUMNS FROM $table", $db_conn );
        if ($result) {
                $value = "";
                while ($record = mysql_fetch_array( $result )) {
                        $value .= "," . $record[ "Field" ];
                }
                $value = substr( $value, 1 ); // remove leading comma
        } else $value = NULL;
        return( $value );
} // field_list

// the user must pass CONTRACT sequence number on the command line.
// We'll look at all detail lines for that contract.
$contract_seq = $argv[1];

//echo $contract_seq . "\n";

$db_host = "localhost";
$db_user = "james";
$db_pwd  = "mysql";
$db_name = "james";
$db_host = "127.0.0.1";
$db_user = "msm";
$db_pwd  = "EetGiOj6";
$db_name = "test_msm";


$tab = CHR( 9 );
$newline = CHR( 10 );

if (($db_conn = @mysql_connect( $db_host, $db_user, $db_pwd )) && mysql_select_db( $db_name, $db_conn )) ;
else die("mysql_connect\n");

$fld_list = field_list( "contract_detail", $db_conn );
$fld_array = explode( ",", $fld_list );

// rebuild $fld_list with some modifications to re-format dates

var_dump( $fld_array );

$fld_list = "";
$j = 0;
while (!is_null( $fld = $fld_array[ $j++ ] )) {
	switch (TRUE) {
	case ($fld == 'StartDate'):
	case ($fld == 'EndDate'):
		$fld = "date_format( $fld, \"%Y-%m-%d\" ) as $fld";
	} // switch
	$fld_list .= "," . $fld;
} // while
$fld_list = substr( $fld_list, 1 ); // remove leading comma

echo $fld_list . "\n";

$qry = "select $fld_list from contract_detail where contract = $contract_seq order by line";
echo $qry . $newline;
if (!($result = mysql_query( $qry, $db_conn ))) {
	die( "mysql_query\n" );
}

$i = 0;
while ($row = mysql_fetch_array( $result )) {
	$j = 0;
	echo "_item" . $tab . $tab . ++$i . $newline;
	while (!is_null( $fld = $fld_array[ $j++ ] )) {
		echo $fld . (strlen( $fld ) < 8 ? $tab : '' ) . 
			$tab . $row[ $fld ] . $newline;
	} // while
	echo "---" . $newline;
} // while

?>
