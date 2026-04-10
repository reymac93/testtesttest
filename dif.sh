#!/bin/sh


[ -f $1.safe ] && {
	echo error: $1.safe already exists
	exit 1;
}

mv -n $1 $1.safe
co -l $1
diff -u $1 $1.safe
