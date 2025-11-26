#
# ------------------------------------------------------------------------------
#
# A license is hereby granted to reproduce this software source code and
# to create executable versions from this source code for personal,
# non-commercial use.  The copyright notice included with the software
# must be maintained in all copies produced.
#
# THIS PROGRAM IS PROVIDED "AS IS". THE AUTHOR PROVIDES NO WARRANTIES
# WHATSOEVER, EXPRESSED OR IMPLIED, INCLUDING WARRANTIES OF
# MERCHANTABILITY, TITLE, OR FITNESS FOR ANY PARTICULAR PURPOSE.  THE
# AUTHOR DOES NOT WARRANT THAT USE OF THIS PROGRAM DOES NOT INFRINGE THE
# INTELLECTUAL PROPERTY RIGHTS OF ANY THIRD PARTY IN ANY COUNTRY.
#
# Copyright (c) 1994-2006, John Conover, All Rights Reserved.
#
# Comments and/or bug reports should be addressed to:
#
#     john@email.johncon.com (John Conover)
#
#    http://www.johncon.com/ntropix/
#    http://www.johncon.com/
#
#------------------------------------------------------------------------------
#
# $Revision: 1.7 $
# $Date: 2006/01/07 09:55:07 $
# $Id: Makefile,v 1.7 2006/01/07 09:55:07 john Exp $
# $Log: Makefile,v $
# Revision 1.7  2006/01/07 09:55:07  john
# Initial revision
#
#
# CFLAGS = -O
CFLAGS = -DLINT -pedantic -W -Wall -Waggregate-return -Wcast-align -Wcast-qual -Wconversion -Winline -Wmissing-prototypes -Wmissing-declarations -Wnested-externs -Wno-import -Wpointer-arith -Wshadow -Wstrict-prototypes -Wwrite-strings
#
CC = gcc $(CFLAGS)
#
PROGRAMS = tsinvest \
	   tsinvestsim \
	   tsshannoneffective \
	   tsinvestdb \
	   csv2tsinvest

CATMAN = tsinvest.catman \
	 tsinvestsim.catman \
	 tsshannoneffective.catman \
	 tsinvestdb.catman \
	 csv2tsinvest.catman

all: ${PROGRAMS} ${CATMAN}

tsinvest: tsinvest.c
	$(CC) -o tsinvest tsinvest.c -lm

tsinvestsim: tsinvestsim.c
	$(CC) -o tsinvestsim tsinvestsim.c -lm

tsshannoneffective: tsshannoneffective.c
	$(CC) -o tsshannoneffective tsshannoneffective.c -lm

tsinvestdb: tsinvestdb.c
	$(CC) -o tsinvestdb tsinvestdb.c -lm

csv2tsinvest: csv2tsinvest.c
	$(CC) -o csv2tsinvest csv2tsinvest.c -lm

tsinvest.catman: tsinvest.1
	nroff -man tsinvest.1 > tsinvest.catman

tsinvestsim.catman: tsinvestsim.1
	nroff -man tsinvestsim.1 > tsinvestsim.catman

tsshannoneffective.catman: tsshannoneffective.1
	nroff -man tsshannoneffective.1 > tsshannoneffective.catman

tsinvestdb.catman: tsinvestdb.1
	nroff -man tsinvestdb.1 > tsinvestdb.catman

csv2tsinvest.catman: csv2tsinvest.1
	nroff -man csv2tsinvest.1 > csv2tsinvest.catman

realclean:
	rm -f ${PROGRAMS} ${CATMAN}
