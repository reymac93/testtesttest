#!/bin/sh

if ./show-locks.sh | grep -q ^; then
	echo "The following files are checked out in RCS:"
	./show-locks.sh
	echo
fi

rsync ${DRY_RUN} -purlvt ~james/market-specific/ver2/ dl380:~www/apache22/data/xml_import_test/
rsync ${DRY_RUN} -purlvt ~james/market-specific/ver2 www:~james/market-specific/
