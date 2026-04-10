#!/bin/sh

compare_one()
{
	php cli-output-header-data.php $a > header.$a
	php cli-output-header-data.php $b > header.$b
	diff -U 1000 header.$a header.$b > diff-header-$a-vs-$b
}


while read a b; do
	echo $a $b
	compare_one $a $b
done
