#!/bin/sh
if [ $# -lt 2 ] ; then echo "$0 path-to-marc-file vendor1 [ vendor2 ... ]" ; exit -1 ; fi
FIL="$1"
shift
php commandLine.php "$FIL" "`basename $FIL`-fixed" "$@"
