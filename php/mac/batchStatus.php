<!DOCTYPE html>
<html>
<head>
<title>MAC: Batch Status</title>
<style type='text/css'> 
	.marc { display: none; font-family: monospace; white-space: pre-wrap; } 
	.marc_holder { cursor: pointer; }
</style>
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
</head>
<body>
<?php

require_once "db.php";

$batch_id = isset($_REQUEST['batch_id'])?intval($_REQUEST['batch_id']):0;

// Get name of batch
$result = Db::$SelectBatchByIdSQL->execute($batch_id);
foreach( $result as $row ) { $batch_name = htmlspecialchars($row['batch_name']); }
if(!isset($batch_name)) {
	print "No such batch id. Please choose from the following list:<ul>\n";
	$query = "SELECT batch_id, batch_name FROM batches ORDER BY batch_start DESC";
	$result = Db::do_query( $query );
	while( $result and null !== ($row = $result->fetch_row() ) ) {
		print "<li><a href='?batch_id={$row[0]}'>" . htmlspecialchars( $row[1] ) . "</a></li>\n";
	}
	print "</ul>\n";
	exit();
}

// Count total number of URLs in batch
$query = "SELECT COUNT(url) FROM links NATURAL JOIN batch_link WHERE batch_id = $batch_id";
$result = Db::do_query( $query );
while( $result and null !== ($row = $result->fetch_row() ) ) { $total = $row[0]; }

// Count number of queued URLs in batch
$query = "SELECT COUNT(url) FROM links NATURAL JOIN batch_link WHERE batch_id = $batch_id AND NOT checked";
$result = Db::do_query( $query );
while( $result and null !== ($row = $result->fetch_row() ) ) { $queued = $row[0]; }
// Count number of bad URLs in batch
$query = "SELECT COUNT(url) FROM links NATURAL JOIN batch_link WHERE batch_id = $batch_id AND error_code";
$result = Db::do_query( $query );
while( $result and null !== ($row = $result->fetch_row() ) ) { $badones = $row[0]; }

// Display status

printf( "<h2>%s: %.0f%% done, %.2f%% bad</h2>", $batch_name, 1-$queued/$total, $badones/$total );

// Allow displaying the bad URLs (or top 20)
// Allow downloading the MARC records with the good urls? How do we find those marc records?

require_once "../norphp/MARC21.php";

$result = Db::$SelectLinksByBatchIdSQL->execute($batch_id);
$marc = new MARC21Record();
print "<ul>\n";
while( $result and null !== ( $row = $result->fetch_assoc() ) ) {
	$row['url'] = htmlspecialchars( $row['url'] );
	$row['title'] = htmlspecialchars( $row['title'] );
	$marc->loadFromString( $row['marc'] );
	$row['marc'] = htmlspecialchars( $marc->AsMnemonicString() );
	$row['checked'] = $row['checked'] ? "checked, error code {$row['error_code']}" : "not checked";
	print "<li><a href='checkUrl.php?url={$row['url']}'>recheck</a> " .
		"(currently {$row['checked']}) <a href='{$row['url']}'>{$row['url']}</a> " .
		"<span class='marc_holder'><span class='title'>{$row['title']}</span>" . 
		" <span class='bib_id'>({$row['bib_id']})</span>" .
		"<div class='marc'>{$row['marc']}</div></span></li>\n";
}
print "</ul>\n";
?>
<script type='text/javascript'>//<![CDATA[
$(".marc").hide(0);
$(".marc_holder").on("click",function(e) { $(this).children(".marc").toggle(100); });
//]]></script>
