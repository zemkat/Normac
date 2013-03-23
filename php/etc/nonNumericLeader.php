<?php
require_once "RecursiveDirectoryScan.php";
$mrc_dir = $argv[1];
$log = fopen( "nonNumericLeader.log", "a" );
fwrite( $log, "Each line is:\npath#recordNumber@001'supposedly-numeric-portion-of-leader'\n" );
$start = time();
$check = $start;
$totno = 0;
foreach( new RecursiveDirectoryScan( $mrc_dir, ".mrc" ) as $path ) {
	$fh = fopen( $path, "r" );
	$recno = 0;
	while( !feof( $fh ) ) {
		$recno++;
		$totno++;
		$now = time();
		if( $now > $check ) {
			$check = $now;
			printf ( "\r%s#%d %.2f Hz            ", $path, $recno, $totno/($check-$start) );
		}
		$peek = stream_get_line( $fh, 99999, "\035" );
		if(feof($fh)) break;
		$numlead = substr( $peek, 0, 5 ) . substr( $peek, 10, 7 ) . substr( $peek, 20, 3 );
		if( !ctype_digit( $numlead ) ) {
			$peek .= stream_get_line( $fh, 99999-24, "\035" );
			$beg = strpos( $peek, "\036" );
			$end = strpos( $peek, "\036", $beg+1 );
			if( $beg === false or $end === false ) continue;
			$oo1 = substr( $peek, $beg+1, $end-$beg-1 );
			fwrite( $log, "$path#$recno@$oo1'$numlead'\n" );
		} 
	}
	fclose( $fh );
}
