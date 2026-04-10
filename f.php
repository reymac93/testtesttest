<?
//$fil = finfo_open(FILEINFO_MIME_TYPE);
$fil = finfo_open(FILEINFO_MIME);
if ($fil) {
	echo finfo_file($fil,'../../whysumoisbetterthankarate1.mpg') . "\n";
	echo finfo_file($fil,'order-xml-61469250-9145.scx') . "\n";
	echo finfo_file($fil,'../test-orders.tgz') . "\n";
} else {
	echo "finfo_open failed\n";
}
?>
