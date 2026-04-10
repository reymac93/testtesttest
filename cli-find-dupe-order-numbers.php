<?

// Routine to find header records with the same order number
// and cindex value.  These records are assumed to be in
// pairs, indicating an old (low seq) record entered manually,
// and a new (high seq) record imported electronically.

// We'll output these sequence numbers two to a line, with
// the lower seq number first.

//echo $contract_seq . "\n";

// this is the highest hand-entered seq #.  All numbers
// > than this are electronically imported.
$max_seq = 16804;

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

if ($db_conn = @mysql_connect( $db_host, $db_user, $db_pwd ))
	mysql_select_db( $db_name, $db_conn );
else die("mysql_connect\n");

$qry = <<< __EOF__
select a.seq as a_seq, b.seq as b_seq, a.CustOrder as CustOrder, a.cindex as cindex
from contract_header a, contract_header b
where a.CustOrder = b.CustOrder and a.SiteName = b.SiteName
 and  a.Cindex = b.Cindex 
 and  a.seq < b.seq and b.seq > $max_seq
order by a_seq, b_seq
__EOF__;

//echo $qry . $newline;
$result = mysql_query( $qry, $db_conn );

while ($row = mysql_fetch_array( $result )) {
	$j = 0;
// output the pairs of sequence numbers
	echo $row[0] . " " . $row[1] . $newline;
//	while (!is_null( $fld = $fld_array[ $j++ ] )) {
//		echo $fld . (strlen( $fld ) < 8 ? $tab : '' ) . 
//			$tab . $row[ $fld ] . $newline;
//	} // while
} // while


?>
