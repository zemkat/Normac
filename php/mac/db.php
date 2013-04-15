<?php

require_once "db_options.php";
require_once "../lib/MARC21.php";
require_once "../lib/URL.php";

class PreparedStatement {
	private $query, $prepared, $signature, $bind_method;
	function __construct( $query, $signature ) {
		$this->query = $query;
		$this->signature = $signature;
		$this->prepared = Db::$mysqli->prepare( $query );
		if( $this->prepared === false ) trigger_error( Db::$mysqli->error );
		else $this->bind_method = new ReflectionMethod( get_class( $this->prepared ), 'bind_param' );
	}
	function execute() {
		$args = func_get_args();
		array_unshift( $args, $this->signature );
		$this->bind_method->invokeArgs( $this->prepared, $args );
		$this->prepared->execute();
		return $this->prepared->get_result();
	}
	function get_warnings() { return $this->prepared->get_warnings(); }
	function affected_rows() { return $this->prepared->affected_rows; }
}

class Db {
	static $mysqli;
	static $InsertBatchSQL;
	static $InsertMarcRecordSQL;
	static $InsertLinkSQL;
	static $InsertBatchLinkSQL;
	static $SelectMarcByBibIdSQL;
	static $SelectBatchByIdSQL;
	static $SelectLinksByBatchIdSQL;
	static $SelectUncheckedLinksByBatchIdSQL;
	static $UpdateLinkSQL;
	static function connect( $host, $username,  $password, $database ) {
		self::$mysqli = new mysqli( $host, $username, $password, $database );
		self::$InsertBatchSQL = new PreparedStatement("INSERT INTO batches ( batch_name, platform_id ) VALUES(?,?)","si");
		self::$InsertMarcRecordSQL = new PreparedStatement("INSERT INTO marc_records ( bib_id, title, marc ) VALUES(?,?,?)","sss");
		self::$InsertLinkSQL = new PreparedStatement("INSERT IGNORE INTO links ( url ) VALUES (?)","s");
		self::$InsertBatchLinkSQL = new PreparedStatement("INSERT INTO batch_link ( batch_id, url, marc_id ) VALUES(?,?,?)","isi");
		self::$SelectMarcByBibIdSQL = new PreparedStatement("SELECT marc_id, marc FROM marc_records WHERE bib_id = ?","s");
		self::$SelectBatchByIdSQL = new PreparedStatement("SELECT batch_name, platform_id FROM batches WHERE batch_id = ?","i");
		self::$SelectLinksByBatchIdSQL = new PreparedStatement("SELECT url,bib_id,title,checked,error_code,marc FROM links NATURAL JOIN batch_link NATURAL JOIN marc_records WHERE batch_id = ? ORDER BY checked, error_code", "i");
		self::$SelectUncheckedLinksByBatchIdSQL = new PreparedStatement("SELECT url,bib_id,title,checked,error_code,marc FROM links NATURAL JOIN batch_link NATURAL JOIN marc_records WHERE batch_id = ? AND NOT checked ORDER BY checked, error_code", "i");
		self::$UpdateLinkSQL = new PreparedStatement("UPDATE links SET checked = 1, error_code = ? WHERE url = ?","is");
	}
	static function do_query( $query ) {
		$result = self::$mysqli->query( $query );
		if(!$result) { 
			trigger_error( "mysqli->query($query)" );
			trigger_error(" returned " . (self::$mysqli->error) . "\n");
		}
		return $result;
	}
	static function lastInsertId() {
		return self::$mysqli->insert_id;
	}
}

class Batch {
	public $batch_id, $batch_name, $platform_id, $platform; // read only

	static function getBatches( ) {
		$ret = array();
		$result = Db::do_query( "SELECT batch_id, COUNT(url) as c FROM batches NATURAL JOIN batch_link NATURAL JOIN links GROUP BY batch_id HAVING c > 0 ORDER BY batch_id" );
		foreach( $result as $row ) {
			$ret[ $row['batch_id'] ] = new Batch( $row['batch_id'] );
		}
		return $ret;
	}

	function __construct( $batch_id ) {
		$result = Db::$SelectBatchByIdSQL->execute( $batch_id );
		$this->batch_id = $batch_id;
		while( $result and null !== ( $row = $result->fetch_assoc() ) ) {
			$this->batch_name = $row['batch_name'];
			$this->platform_id = $row['platform_id'];
		}
	}

	function getLinks( $checked = null ) {
		$ret = array();
		$result = $checked ? (Db::$SelectUncheckedLinksByBatchIdSQL->execute( $this->batch_id ) ) : ( Db::$SelectLinksByBatchIdSQL->execute( $this->batch_id ) );
		foreach( $result as $row ) {
			$row['url'] = new URL( $row['url'] );
			$ret[] = $row;
		}
		return $ret;
	}

	function getPlatform( ) {
		if( null === $this->platform ) {
			$this->platform = new Platform( $this->platform_id );
		}
		return $this->platform;
	}
}

class NewBatch extends Batch {
	public $new_marc, $old_marc, $new_url, $old_url;

	function __construct( $batch_name, $platform_id ) {
		$this->batch_name = $batch_name;
		$this->platform_id = $platform_id;
		Db::$InsertBatchSQL->execute( $batch_name, $platform_id );
		$this->batch_id = Db::lastInsertId();
		$this->new_marc = 0;
		$this->old_marc = 0;
		$this->new_url = 0;
		$this->old_url = 0;
	}

	function InsertMarcRecord( \MARC21Record $record ) {
		$bib_id = $record->getFields("001")[0]->data;
		$binfmt = $record->AsBinaryString();
		$result = Db::$SelectMarcByBibIdSQL->execute( $bib_id );
		while( $result and null !== ( $row = $result->fetch_assoc() ) ) {
			if ( $row[ 'marc' ] == $binfmt ) {
				$this->old_marc++;
				return $row['marc_id'];
			}
		}
		$bib_id = $record->getFields("001")[0]->data;
		$title = $record->getFields("245")[0]->getSubfields("a")[0]->data;
		$this->new_marc++;
		Db::$InsertMarcRecordSQL->execute( $bib_id, $title, $binfmt );
		return Db::lastInsertId();
	}

	function HandleMarcRecord( \MARC21Record $record ) {
		$marc_id = $this->InsertMarcRecord( $record );
		foreach( $record->getFields("856") as $field ) {
			foreach( $field->getSubfields("u") as $subfield ) {
				$url = $subfield->data;
				Db::$InsertBatchLinkSQL->execute($this->batch_id, $url, $marc_id );
				Db::$InsertLinkSQL->execute( $url );
				if( Db::$InsertLinkSQL->affected_rows() )
					$this->new_url++;
				else $this->old_url++;
			}
		}
	}

	function HandleMarcFile( $filename ) {
		$marcfile = new MARC21RecordSetIterator( $filename );
		foreach( $marcfile as $record ) {
			$this->HandleMarcRecord( $record );
		}
	}

}

class Platform {
	public $platform_id;
	public $profile;
	public $profile_name;

	function __construct( $platform_id ) {
		$platform_id = intval( $platform_id );
		$result = Db::do_query("SELECT short_name FROM platforms WHERE platform_id = $platform_id" );
		foreach( $result as $row ) {
			$this->profile_name = $row['short_name'];
		}
		$this->platform_id = $platform_id;
		if( is_readable( "profiles/{$this->profile_name}.php" ) ) {
			require_once "profiles/{$this->profile_name}.php";
			$this->profile = new $this->profile_name();
		} 
	}

	function check_url( $url ) {
		$res = $this->profile->verify_url( $url );
		Db::$UpdateLinkSQL->execute( $res, $url -> getUrl() );
		return $res;
	}

}
