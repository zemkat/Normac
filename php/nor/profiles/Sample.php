<?php

function import938( $record, $variables ) {
	$url = $record->getOneField( '938' )->getOneSubfield( 'u' )->data;
	$record -> appendField( '=856  \\$u' . $url . ' $3' . $variables['vendorName'], true );
	$record -> delFields( '938' );
}
