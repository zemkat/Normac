<?php

require_once "ISO2709.php";
require_once "RecursiveDirectoryScan.php";

@mkdir("sanity",0777,true);
chdir("sanity");

$start = time();
$check = $start;
$totno = 0;

array_shift( $argv );
$dirs = empty( $argv ) ? array( "/home/kathryn/CTS" ) : $argv;
foreach( $dirs as $mrc_dir ) {
	foreach( new RecursiveDirectoryScan( $mrc_dir, ".mrc" ) as $path ) {
		$recno = 0;
		foreach( new ISO2709RecordSetIterator( $path ) as $record ) {
			if( count( $record->exceptions) > 0 ) {
				@mkdir("./$path",0777,true);
				file_put_contents( "./$path/$recno.mrc", $record->raw );
				file_put_contents( "./$path/$recno.mrk", $record->asmnemonicstring() );
			}
			$now = time();
			if( $now > $check ) { $check = $now; printf( "\r%s#%d %.2fHz         ", basename($path), $recno, $totno/($check-$start) ); }
			$recno++;
			$totno++;
		}
	}
}
printf("\rDone! $totno records at %.2f Hz                                    \n", $totno/max(1,($check-$start)) );
