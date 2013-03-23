<?php

require_once "RecursiveDirectoryScan.php";

# check whether fseek or stream_get_line is faster to count/index records
# result: they are the same speed, stream_get_line possibly a little faster

function count_records_using_reclen( $dir ) {
	$recno = 0;
	foreach( new RecursiveDirectoryScan( $dir, ".mrc" ) as $path ) {
		$fh = fopen( $path, "r" );
		while( true ) {
			$peek = fread( $fh, 5 );
			if( feof( $fh ) ) break;
			fseek( $fh, intval( $peek ) - 5, SEEK_CUR );
			$recno++;
		}
		fclose( $fh );
	}
	return $recno;
}

function count_records_using_recterm( $dir ) {
	$recno = 0;
	foreach( new RecursiveDirectoryScan( $dir, ".mrc" ) as $path ) {
		$fh = fopen( $path, "r" );
		while( true ) {
			stream_get_line( $fh, 99999, "\035" );
			if( feof( $fh ) ) break;
			$recno++;
		}
		fclose( $fh );
	}
	return $recno;
}

function compare( $dir ) {
	$start = time();
	$recno1 = count_records_using_reclen( $dir );
	$again = time();
	$recno2 = count_records_using_recterm( $dir );
	$finish = time();
	printf( "%d vs %d records, %d vs %d seconds\n", $recno1, $recno2, $again-$start, $finish-$again );
}

compare( $argv[1] );
