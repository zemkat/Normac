<?php

class URL {
	function httpCode() { return $this->getInfo('http_code'); } 
	function httpStatus() { return $this->getInfo('http_code'); }
	function contentType() { return $this->getInfo('content_type'); }
	function contains($literal) { return false !== strpos( $this->getContents(), $literal ); }
	function preg_match( $regex, &$matches = array() ) {
		return preg_match( $regex, $this->getContents(), $matches );
	}
	function isPDF() { return $this->contentType() === 'application/pdf'; }


	protected $url, $contents, $info, $error;

	function __construct( $url ) {
		$this->url = $url;
	}

	function getUrl() {
		return $this->url;
	}

	function getContents() {
		$this->fetch();
		return $this->contents;
	}

	function getInfo($field = null) {
		$this->fetch();
		return ( $field === null ) ? $this->info : $this->info[ $field ];
	}


	function fetch($retries = 0) {
		if( $this->error === "" ) return;
        	$ch = curl_init();
        	curl_setopt($ch, CURLOPT_URL, $this->url);
        	curl_setopt($ch, CURLOPT_HEADER, TRUE);
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        	curl_setopt($ch, CURLOPT_NOBODY, FALSE);
        	curl_setopt($ch, CURLOPT_FAILONERROR, true);
        	curl_setopt($ch, CURLOPT_USERAGENT, 'Curly McGee');
        	curl_setopt($ch, CURLOPT_COOKIEJAR, "/dev/null" );
		do {
			$this->contents = curl_exec($ch);
			$this->error = curl_error($ch);
			$this->info = curl_getinfo($ch);
		} while( $retries-- > 0 and $this->error );
        	curl_close($ch);
	}

	function getHTMLDocument() {
		return new HTMLDocument( $this->getContents() );
	}

};

class HTMLDocument {
	protected $string, $DOMDocument, $SimpleXML;
	function __construct( $string ) {
		$this->string = $string;
	}
	static function zero($x) { return isset($x[0]) ? $x[0] : FALSE; }
	function getDOMDocument() {
		if( $this->DOMDocument === null ) {
  			$this->DOMDocument = new DOMDocument();
			$this->DOMDocument->strictErrorChecking = FALSE;
			@$this->DOMDocument->loadHTML( $this->string );
		}
		return $this->DOMDocument;
	}
	function getSimpleXML() {
		if( $this->SimpleXML === null ) {
			$this->SimpleXML = simplexml_import_dom( $this->getDOMDocument() );
		}
		return $this->SimpleXML;
	}
	function getXPath( $xpath ) {
		return self::zero( $this->getSimpleXML()->xpath( $xpath ) );
	}
	function getLinks() {
		$ret = array();
		foreach($this->getDOMDocument()->getElementsByTagName("a") as $a )
			$ret[] = new URL( trim($a->getAttribute("href") ) );
		return $ret;
	}
}

