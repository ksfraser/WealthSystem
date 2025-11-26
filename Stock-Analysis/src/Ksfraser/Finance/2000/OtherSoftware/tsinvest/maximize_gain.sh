#!/bin/sh

if [ -e "$1" ]
then
	./tsinvest -d 2 -a 1 -m 1 -M 20 -i -s -t $1
else
	echo "Need to write a usage";
	usage;
fi

exit 0;

