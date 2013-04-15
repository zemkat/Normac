<!DOCTYPE html>
<html>
  <head>
    <title>MAC: MARC Access Checker</title>
  </head>
  <body>
    <form action="" method="POST" enctype="multipart/form-data">
      <fieldset>
        <legend>E-Book Batch Checker Submission Form</legend>
	<input name="batch_name" placeholder="Batch Name"/>
	<select name="platform_id">
          <option value="0">Select a vendor from the list</option>
	  <?php
		require_once "db.php";
		$query = "SELECT platform_id, platform_name FROM platforms";
		$result = Db::do_query( $query );
		while( $result and null !== ( $row = $result->fetch_row() ) ) {
			print "<option value='{$row[0]}'>" . htmlspecialchars($row[1]) . "</option>\n";
		}
	  ?>
	</select>
	<input type="file" name="batch_file" />
      </fieldset>
    </form>
    <pre>
<?php
if( isset( $_FILES['batch_file'] ) and isset( $_REQUEST['batch_name'] ) and isset( $_REQUEST['platform_id'] ) ) {
	require_once "db.php";

	$batch_name = $_REQUEST['batch_name'];
	$platform_id = intval( $_REQUEST['platform_id'] );
	$batch = new NewBatch( $batch_name, $platform_id );
	// Find URLs in file (either .mrc, .mrk, or .txt with lines matching the pattern /^([^ ]*)( *.*)?$/
	$batch->HandleMarcFile( $_FILES['batch_file']['tmp_name'] );
	// Report back on successful submission (how many new urls, how many already-queued urls, how many known-good urls)
	print "{$batch->new_marc} new MARC records, {$batch->old_marc} old MARC records, {$batch->new_url} new URLs, {$batch->old_url} old URLs.\n";
	// Link to status page
	print "Created <a href='batchStatus.php?batch_id={$batch->batch_id}'>batch #{$batch->batch_id} ({$batch->getBatchName()})</a>\n";

}
?>
    </pre>
  </body>
</html>
