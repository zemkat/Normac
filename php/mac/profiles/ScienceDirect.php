<?php

require_once "MacProfile.php";

class ScienceDirect extends MacProfile {

	function verify_url( $url ) {
		if( is_string( $url ) ) $url = new URL( $url );
		if( $url->httpCode() != "200" ) return self::BAD_HTTP_CODE;
		if( ! $this->verify_url_access_string( $url ) ) return self::NO_ACCESS;
		if( ! $this->verify_url_has_working_pdf( $url ) ) return self::NO_PDF;
		return self::OK;
	}	

	private function verify_url_access_string( $url ) {
		return true;
		return $url->contains("You are entitled to access the full text of this document" );
	}

	private function verify_url_has_working_pdf( $url ) {
		foreach( $url -> getHTMLDocument() -> getLinks() as $link ) {
			if( substr( $link->getUrl(), -9 ) === "-main.pdf" )
				return $link->isPDF();
		}
		return false;
	}
}

