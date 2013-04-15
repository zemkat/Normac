<?php

abstract class MacProfile {
	abstract public function verify_url( $url );

	const 	OK 		= 0,
		BAD_HTTP_CODE 	= 1,
		NO_ACCESS 	= 2,
		NO_PDF	 	= 3,
		BAD_DOI 	= 4;
}
