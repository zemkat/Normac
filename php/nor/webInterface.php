<?php

if( isset($_REQUEST['download']) ) {
	header("Content-type: application/marc");
	header("Content-disposition: attachment; filename=\"{$_FILES['marc']['name']}-{$_REQUEST['vendor']}.mrc\"");
} else {

?> 
<!DOCTYPE html>
<html>
<head>
  <title>Norm, MARC Normalizer</title>
  <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css" />
  <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
  <script src="http://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
  <style>
  .sortable{ list-style-type: none; margin: 0; padding: 0 0 2.5em; float: left; margin-right: 10px; }
  #input { width: auto; float: left; }
  table tr td { vertical-align: top; }
  .sortable li { margin: 0 5px 5px 5px; padding: 5px; font-size: 1.2em; width: 120px; cursor: pointer; }
  #active li.ui-state-default { font-weight: bold; background-color: #ccf; background-image: none; }
  #active { min-height: 60ex; min-width: 10em; }
  #normalized { min-width: 20em; min-height: 40ex; width: 100%; font-family: monospace; }
  .placeholder { min-height: 3ex; border: 3px dashed gray; }
  </style>
  <script>
  $(function() { 
	$( ".sortable" ).sortable({ placeholder: "placeholder", cursor: "pointer", connectWith: ".sortable" }).disableSelection(); 
	if(false) $(".sortable" ).on("click", "li", function(e) { 
		var i = $("#inactive")[0], a = $("#active")[0], t;
		t = $(this)[0];
		if(t.parentNode == i ) a.appendChild( t ); 
		else if(t.parentNode == a && a.children.length > 1 ) i.insertBefore( t, i.firstChild ); 
	} );
	$("input[name='download']").on( "click", function(e) { $("form")[0].downloady = true; } );
	$( "form" ).on( "submit", function(e) {
		var vendor;
		vendor = [];
		$("#active li").each( function(k) { vendor.push( $(this).text() ); } );
		$("form input[name='vendor']")[0].value = vendor.join("|");
		if( this.downloady ) {
			this.downloady = false;
			return true;
		}
		$("#normalized")[0].value = "Normalizing...";
		$.ajax({
			url: "webInterface.php",
			type: "POST",
			data: new FormData(this),
			processData: false,
			contentType: false,
			success: displayRecord,
			error: displayError,
		});
		e.preventDefault();
		return false;
	} );
	$( "#normalized" )[0].select();
  });
function displayError() {
	$("#normalized" )[0].value += "Error";
}
  function displayRecord( data ) {
		var pat = "textarea";
		var pos1 = data.indexOf( "<" + pat );
		var pos2 = data.indexOf( "</" + pat );
		data = data.substring( pos1, pos2 );
		pos1 = data.indexOf( ">" );
		data = data.substr( pos1+1 );
		$("#normalized")[0].value = data;
	}
  </script>
</head>
<body>

<form method="POST" action="" enctype="multipart/form-data">
<div id="input">
<fieldset><legend>MARC file in Mnemonic format</legend>
<div id="file">
<input name="marc" type="file" class="ui-statedefault" />
<input type="submit" value="Normalize" class="ui-state-default"/>
</div>
</fieldset>
<fieldset><legend>Vendor profiles</legend>
<table>
<tr><th>Active</th><th>Inactive</th></tr>
<tr><td> 
<ul id="active" class="sortable">
  <li class="ui-state-default">UK</li>
</ul>
<input type="hidden" name="vendor" value="UK"/>
</td>
<td>
<ul id="inactive" class="sortable"><?php 
	$profile_dir = "../profiles";
	foreach( scandir( $profile_dir ) as $file ) {
		$path = $profile_dir . $file;
		if(is_dir($path)) continue;
		$matches= array();
		if( preg_match('/(.*)\.ini$/',$file,$matches)) print "<li class='ui-state-default'>{$matches[1]}</li>\n";
	}
?></ul>
</td>
</tr>
</table>
</fieldset>
</div>
<fieldset><legend>Normalized MARC</legend>
<input type="submit" name="download" value="Download"/>
<textarea id='normalized' class='ui-state-default'>
<?php

}

require_once "vendor.php";
require_once "../MARC21.php";

if(isset($_FILES['marc']) && isset( $_REQUEST['vendor']) ) {

if( !isset($_REQUEST['download']) ) {
print "Normalizing '{$_FILES['marc']['name']}' using vendor profiles '{$_REQUEST['vendor']}'\n";
}

$argv = array_merge( array( "Web", $_FILES['marc']['tmp_name'] ), explode('|', $_REQUEST['vendor']) );

array_shift($argv);
$marcfile = new MARC21RecordSetIterator( array_shift( $argv ) );
$vendor = new VendorProfile( array_shift( $argv ) );
foreach( $argv as $vendor_name ) {
	$vendor->appendVendor( new VendorProfile( $vendor_name ) );
}
foreach( $marcfile as $record ) {
	#print "Normalizing: " . $record->leader . "\t=001" . $record->getFields("001")[0]->data . "\n";
	#$record->loadFromBinaryString( $record->AsBinaryString() );
	$vendor->normalizeRecord( $record );
	#print "Normalized: " . $record->leader . "\t=001" . $record->getFields("001")[0]->data . "\n";
	$record->loadFromBinaryString( $record->AsBinaryString() );
if( isset($_REQUEST['download']) ) {
	print ($record->AsBinaryString());
} else {
	print htmlspecialchars($record->AsMnemonicString());
}
}

if( isset($_REQUEST['download']) ) { exit(0); }


print "</textarea></fieldset>\n";

}
?></textarea></fieldset></form></body></html>
