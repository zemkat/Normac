<?php
function RemoveInvalidLinks ($record, $variables) {
	$good_urls = array();
	if(isset($variables['ValidUrlHosts']))
		$good_urls = explode(",",$variables['ValidUrlHosts']);
	$good_urls[] = "loc.gov";
	foreach ($good_urls as $k => $v) {
		$good_urls[$k] = ltrim(rtrim($v));
	}
	foreach ($record->getFields("856") as $field) {
		$keep = false;
		foreach( $field->getSubfields("u") as $subfield ) {
			$url = $subfield->data;
			foreach ($good_urls as $good) {
				if (preg_match("@$good@i",$url)) {
					$keep = true;
				}
			}
		}
		if (!$keep) {
			$field->delete();
		}
	}
}

function Add856Label ($record, $variables) {
	$good_urls = array();
	if(isset($variables['ValidUrlHosts']))
		$good_urls = explode(",",$variables['ValidUrlHosts']);
	foreach ($record->getFields("856") as $field) {
		$label = false;
		foreach( $field->getSubfields("u") as $subfield ) {
			$url = $subfield->data;
			foreach ($good_urls as $good) {
				if (preg_match("@$good@i",$url)) {
					$label = true;
				}
			}
		}
		foreach( $field->getSubfields("3") as $subfield ) {
			$label = false;
		}
		if ($label) {
			$field->appendSubfield("3", $variables['Label856']);
		}
	}
}

function Add856ProxyPrefix ($record, $variables) {
	foreach ($record->getFields("856") as $field) {
		foreach( $field->getSubfields("u") as $subfield ) {
			if (!preg_match("@loc.gov@i",$subfield->data)) {
				$subfield->data = "{$variables['ProxyPrefix']}{$subfield->data}";
			}
		}
	}
}

function Add856PublicNote ($record, $variables) {
	foreach ($record->getFields("856") as $field) {
		foreach( $field->getSubfields("u") as $subfield ) {
			$url = $subfield->data;
			if (!preg_match("@loc.gov@i",$url)) {
				$field->appendSubfield("z",$variables['PublicNote']);
			}
		}
	}
}

function Neutralize300 ($record, $variables) {
	foreach( $record->getFields("300") as $field ) {
		$ext = $field->getOneSubfield("a");
		if (preg_match("/^1 online resource/",$ext->data)) {
			return;
		}
		if ($subfield = $field->getOneSubfield("c")) {
			$subfield->delete();
			if ($phys = $field->getOneSubfield("b")) {
				$phys->data = preg_replace("/ *; *$/","",$phys->data);
			} else { # only subfield a
				$ext = $field->getOneSubfield("a");
				$ext->data = preg_replace("/ *; *$/","",$ext->data);
			}
		}
		$ext = $field->getOneSubfield("a");
		if (preg_match("/(.*)( [:;] *)$/",$ext->data,$m)) {
			$ext->data = "1 online resource(${m[1]})$m[2]";
		} else {
			$ext->data = "1 online resource($ext->data)";
		}
	}
}

function AddGMD ($record, $variables) {
	foreach( $record->getFields("245") as $field ) {
		if ($subfield = $field->getOneSubfield("h")) {
			$good = false;
			$matches = array();
			preg_match( "/[[](.*)[]]/", $subfield->data, $matches );
			$curgmd = $matches[1];
			foreach (explode( ",", $variables['GoodGMD'] ) as $gmd) {
				if ($curgmd == $gmd) {
					$good = true;
				}
			}
			if (!$good) {
				$record->appendField('=ERR  \\\\$aBAD GMD $h'.$subfield->data);
			}
		} else {
			$field->appendSubfield("h","[electronic resource]");
		}
	}
}

