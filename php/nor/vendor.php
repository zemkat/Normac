<?php

class VendorProfile {
	public $description; // string
	public $variables; // associative array <string,string>
	public $initial, $middle, $final;// array of strings, each one the name of a function
	public $del; // array of regexes to be deleted
	public $add; // array of strings (or marc fields?) to be added
	function __construct($name) {
		if(!is_readable($name)) $name = "profiles/$name.ini";
		$phpname =preg_replace( "/\.ini/",".php", $name );
		if(is_readable($phpname)) require_once "$phpname";
		$ini = file_get_contents( $name );
		$ini = preg_replace("/\n\r/","\n",$ini);
		// split into sections
		$matches = array();
		preg_match_all("/^\[[a-zA-Z]*\]$/m",$ini,$matches,PREG_OFFSET_CAPTURE);
		$matches=$matches[0];
		$matches[] = array( "", strlen($ini) );
		$sections = array();
		for( $i = 0 ; $i+1 < count($matches) ; $i++ ) {
			$name = strtolower( substr( $matches[$i][0], 1, -1 ) );
			$sections[ $name ] = substr( $ini, $matches[$i][1]+strlen($matches[$i][0])+1,
				$matches[$i+1][1] - $matches[$i][1]-strlen($matches[$i][0])-2 );
		}
		$this -> description 	= $sections["description"];
		$this -> initial 	= explode( "\n", $sections["initial"], -1 );
		$this -> middle 	= explode( "\n", $sections["middle"], -1 );
		$this -> final 		= explode( "\n", $sections["final"], -1 );
		$this -> add 		= explode( "\n", $sections["add"], -1 );
		$this -> del 		= explode( "\n", $sections["delete"], -1 );
		$this -> variables	= parse_ini_string( $sections["variables"] );
	}
	function appendVendor( $vendor ) {
		$this -> description .= "\n" . $vendor -> description;
		$this -> variables = array_merge( $this->variables, $vendor->variables );
		$this -> initial = array_merge( $this->initial, $vendor->initial );
		$this -> middle = array_merge( $this->middle, $vendor->middle );
		$this -> final = array_merge( $this->final, $vendor->final );
		$this -> add = array_merge( $this->add, $vendor->add );
		$this -> del = array_merge( $this->del, $vendor->del );
		return $this;
	}
	function normalizeRecord( $record ) {
		foreach( $this -> initial as $f ) { $f( $record, $this->variables ); }
		foreach( $this -> del as $f ) { $record -> delFields( $f ); }
		foreach( $this -> middle as $f ) { $f( $record, $this->variables ); }
		foreach( $this -> add as $f ) { $record -> appendField( $f, true ); }
		foreach( $this -> final as $f ) { $f( $record, $this->variables ); }
		return $record;
	}
}
