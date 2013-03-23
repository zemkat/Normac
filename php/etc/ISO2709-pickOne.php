<?php

require_once "ISO2709.php";

$input = $argv[1];
$recno = intval( $argv[2] );
$output = $argv[3];

$rsa = new ISO2709RecordSetArray( $input );
file_put_contents( $output.".mrk", $rsa[$recno]->asMnemonicString(), FILE_APPEND );
file_put_contents( $output.".mrc", $rsa[$recno]->asBinaryString(), FILE_APPEND );
file_put_contents( $output.".raw", $rsa[$recno]->raw, FILE_APPEND );
#$rsa[$recno]->DataNotInDirectory();
print "Appended Mnemonic format of $input#$recno to $output\n";

