<?

$a = array(); 

$a[] = ( 'L3' );
$a[] = ( 'L2' );
$a[] = ( 'L1' );
$a[] = ( 'L4' );

asort( $a );
$s = implode( '/', $a );

var_dump( $s );
var_dump( array_keys ( $a ));

?>
