#!/bin/sh

compare_one()
{
	php cli-output-detail-data.php $a > detail.$a
	php cli-output-detail-data.php $b > detail.$b
	diff -U 1000 detail.$a detail.$b > diff-detail-$a-vs-$b
}


while read a b; do
	echo $a $b
	compare_one $a $b
done
