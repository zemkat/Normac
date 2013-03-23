<?php

require_once "vendor.php";
require_once "../lib/MARC21.php";

if( count($argv) < 4 ) {
	print "Usage: {$argv[0]} InputMarcFile OutputMarcFileBasename Vendor1 [ Vendor2 ... ]\n";
	exit(-1);
}

array_shift( $argv );
$marcfile = new MARC21RecordSetIterator( array_shift( $argv ) );
$outfile = array_shift( $argv );
$mrk = fopen( "$outfile.mrk", "w" );
$mrc = fopen( "$outfile.mrc", "w" );
$vendor = new VendorProfile( array_shift( $argv ) );
foreach( $argv as $vendorName ) {
	$vendor->appendVendor( new VendorProfile( $vendorName ) );
}

foreach( $marcfile as $record ) {
	$vendor->normalizeRecord( $record );
	fwrite( $mrk, $record->AsMnemonicString() );
	fwrite( $mrc, $record->AsBinaryString() );
}

fclose( $mrk );
fclose( $mrc );
