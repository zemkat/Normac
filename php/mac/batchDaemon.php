<?php

// look through the list of urls that are not checked
// find the oldest (or most recent?) (or most frequently queued?) and check it
// record results in database
// pause between checks on a given platform

require_once "db.php";

foreach( Batch::getBatches() as $batch ) {
	$platform = $batch -> getPlatform();
	if( $platform->profile === null ) {
		print "Platform '{$platform->profile_name}' link checker not available\n";
		continue;
	}
	foreach( $batch->getLinks(true) as $link ) {
		$verified = $platform -> check_url( $link['url'] );
		sleep(10);
	}
}
