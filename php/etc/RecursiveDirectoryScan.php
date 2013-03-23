<?php

class RecursiveDirectoryScan implements Iterator {
	private $endw, $dirq, $dirp, $curd, $curk, $curv;

	function __construct( $path, $endsWith = "" ) {
		$this->dirq = array();
		$this->dirq[] = $path;
		$this->dirp = false;
		$this->endw = $endsWith;
		$this->curk = 0;
	}

	function valid() {
		return $this->curv !== false;
	}

	function next() {
		$done = false;
		while( !( $this->dirp === false and empty($this->dirq) ) ) {
			if( $this->dirp === false ) {
				$this->curv = false;
				$this->curd = array_shift( $this->dirq );
				$this->dirp = opendir( $this->curd );
				if( $this->dirp === false ) var_dump( $this );
				continue;
			}
			$filename = readdir( $this->dirp );
			if( $filename === false ) {
				closedir( $this->dirp );
				$this->dirp = false;
				$this->curv = false;
				$this->curd = false;
				$done = empty( $this->dirq );
				continue;
			}
			if( $filename === "." or $filename === ".." ) continue;
			$path = $this->curd . DIRECTORY_SEPARATOR . $filename;
			if( is_dir( $path ) ) {
				$this->dirq[] = $path;
				continue;
			}
			$this->curv = $path;
			if( substr( $filename, -strlen( $this->endw ) ) === $this->endw ) break;
		}
		$this->curk++;
	}

	function key() {
		return $this->curk;
	}

	function current() {
		return $this->curv;
	}

	function rewind() {
		$this->next();
	}

}
