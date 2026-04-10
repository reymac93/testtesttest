<?php


function init_tam_aliases( $file )
// return an array mapped $result[ TAMalias ] = NCCalias;
{
	$result = array();
	$handle = fopen( $file, "r" );
	if ($handle) {
        	$line = fgets( $handle, 1024 );	// 1k is plenty!
    		while (!feof($handle)) {
// strip trailing newline
			$line = str_replace( "\n", "", $line );
			list( $tam, $ncc ) = explode( ' ', $line );
			$result[ $tam ] = $ncc;
        		$line = fgets( $handle, 1024 );
    		}
    		fclose($handle);
	}
	return( $result );
}

$tam_alias = init_tam_aliases( 'TAM-aliases.txt' );


?>
