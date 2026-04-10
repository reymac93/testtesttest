<?php

//echo phpinfo();

//$xmlfile = "example.xml";
//
//$xml = simplexml_load_file( $xmlfile );
//
//var_dump( $xml->campaign->makeGoodPolicy->code['codeDescription'] );

//var_dump( $xml );


//$obj_name = "document->documentType";

//var_dump( $xml->document->documentType );
//$doc_type = (string)eval( "return( \$xml->$obj_name );" );
//var_dump( $doc_type );

//echo date_default_timezone_get() . "\n";
//$startdate = '12-8-1961';
//
//$six_days = DateInterval::__construct( 'P6D' ); // Period of 6 Days
//
//$enddate = strtotime( $startdate );
//var_dump( $enddate );



//var_dump( $a );
//
//$a = array( 'g' => 'foo', 'bar', 'farkle' );
//var_dump( $a );
//
//unset( $a );
//var_dump( $a );


//$dt = "8-12-1961 24:00";
//
//$a = date_parse( $dt );
//var_dump( $a );


//$j = 0;
//while ($j < 7) {
//	echo $j . ' ' . ($j - 1 + 7) % 7 . "\n";
//	$j++;
//} // while

//$a[ 0 ] = '5';
//$a[ 5 ] = '0';
//
//var_dump( array_keys( $a ) );
//unset( $a[ 0 ] );
//var_dump( array_keys( $a ) );
//unset( $a[ 5 ] );
//var_dump( array_keys( $a ) );
//var_dump( $a[0] );


// $s = "\$foo = 'farkle'; return( 'bar' );";
// echo $s . "\n";
// echo eval( "return(" . $s . ");" ) . "\n";

function simplexml2array($xml) {
   if (get_class($xml) == 'SimpleXMLElement') {
       $attributes = $xml->attributes();
       foreach($attributes as $k=>$v) {
           if ($v) $a[$k] = (string) $v;
       }
       $x = $xml;
       $xml = get_object_vars($xml);
   }
   if (is_array($xml)) {
       if (count($xml) == 0) return (string) $x; // for CDATA
       foreach($xml as $key=>$value) {
           $r[$key] = simplexml2array($value);
       }
       if (isset($a)) $r['@'] = $a;    // Attributes
       return $r;
   }
   return (string) $xml;
}

$xml = simplexml_load_file( "example.xml" );

//var_dump( $xml->campaign->company[0]->name );
//var_dump( simplexml2array( $xml->campaign->company[0]->name ) );
//echo "---\n";
//var_dump( $xml->campaign->company[1]->name );
//var_dump( simplexml2array( $xml->campaign->company[1]->name ) );
//echo "---\n";
//var_dump( $xml->campaign->product->name );
//var_dump( simplexml2array( $xml->campaign->product->name ) );
//echo "---\n";
var_dump( '' . $xml->campaign->advertiser->name );
var_dump( (string)('' . $xml->campaign->advertiser->name) );
var_dump( simplexml2array( $xml->campaign->advertiser->name ) );
echo "---\n";


?>
