#!/bin/sh

#grep -l "locks$" RCS/* | \
#sed -e 's/^RCS\///' -e 's/,v$//'

rlog -R -L RCS/*
