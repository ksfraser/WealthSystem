#
# -----------------------------------------------------------------------------
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
# -----------------------------------------------------------------------------
#
# Makefile for the noise generators.
#
# $Revision: 0.0 $
# $Date: 2006/01/07 20:28:55 $
# $Id: Makefile,v 0.0 2006/01/07 20:28:55 john Exp $
# $Log: Makefile,v $
# Revision 0.0  2006/01/07 20:28:55  john
# Initial version
#
#
CFLAGS = -DLINT -pedantic -W -Wall -Waggregate-return -Wcast-align -Wcast-qual -Wconversion -Winline -Wmissing-prototypes -Wmissing-declarations -Wnested-externs -Wno-import -Wpointer-arith -Wshadow -Wstrict-prototypes -Wwrite-strings -O

all: programs catman

programs: tsfractional \
	  tsbrownian \
	  tswhite \
	  tsgaussian \
	  tsdlogistic \
	  tspink \
	  tsblack \
	  tsfBm \
	  tsintegers \
	  tslogistic \
	  tsunfairfractional \
	  tsunfairbrownian \
	  tscoin \
	  tscoins \
	  tsstockwager \
	  tsbinomial \
	  tsshannonstock \
	  tsmarket \
	  tsstock \
	  tsstocks \
	  tstrade\
	  tstradesim \
	  tscauchy \
	  tslognormal \
	  tslaplacian

tswhite: tswhite.c
	cc $(CFLAGS) -o tswhite tswhite.c

tsbrownian: tsbrownian.c
	cc $(CFLAGS) -o tsbrownian tsbrownian.c

tsfractional: tsfractional.c
	cc $(CFLAGS) -o tsfractional tsfractional.c -lm

tsgaussian: tsgaussian.c
	cc $(CFLAGS) -o tsgaussian tsgaussian.c -lm

tsdlogistic: tsdlogistic.c
	cc $(CFLAGS) -o tsdlogistic tsdlogistic.c

tspink: tspink.c
	cc $(CFLAGS) -o tspink tspink.c -lm

tsblack: tsblack.c
	cc $(CFLAGS) -o tsblack tsblack.c -lm

tsfBm: tsfBm.c
	cc $(CFLAGS) -o tsfBm tsfBm.c -lm

tsintegers: tsintegers.c
	cc $(CFLAGS) -o tsintegers tsintegers.c

tslogistic: tslogistic.c
	cc $(CFLAGS) -o tslogistic tslogistic.c -lm

tsunfairfractional: tsunfairfractional.c
	cc $(CFLAGS) -o tsunfairfractional tsunfairfractional.c -lm

tsunfairbrownian: tsunfairbrownian.c
	cc $(CFLAGS) -o tsunfairbrownian tsunfairbrownian.c

tscoin: tscoin.c
	cc $(CFLAGS) -o tscoin tscoin.c

tscoins: tscoins.c
	cc $(CFLAGS) -o tscoins tscoins.c -lm

tsstockwager: tsstockwager.c
	cc $(CFLAGS) -o tsstockwager tsstockwager.c

tsbinomial: tsbinomial.c
	cc $(CFLAGS) -o tsbinomial tsbinomial.c -lm

tsshannonstock: tsshannonstock.c
	cc $(CFLAGS) -o tsshannonstock tsshannonstock.c -lm

tsmarket: tsmarket.c
	cc $(CFLAGS) -o tsmarket tsmarket.c -lm

tsstock: tsstock.c
	cc $(CFLAGS) -o tsstock tsstock.c -lm

tsstocks: tsstocks.c
	cc $(CFLAGS) -o tsstocks tsstocks.c -lm

tstrade: tstrade.c
	cc $(CFLAGS) -o tstrade tstrade.c -lm

tstradesim: tstradesim.c
	cc $(CFLAGS) -o tstradesim tstradesim.c -lm

tscauchy: tscauchy.c
	cc $(CFLAGS) -o tscauchy tscauchy.c -lm

tslognormal: tslognormal.c
	cc $(CFLAGS) -o tslognormal tslognormal.c -lm

tslaplacian: tslaplacian.c
	cc $(CFLAGS) -o tslaplacian tslaplacian.c -lm

catman: tsblack.catman \
	tsbrownian.catman \
	tscoin.catman \
	tscoins.catman \
	tsdlogistic.catman \
	tsfBm.catman \
	tsfractional.catman \
	tsgaussian.catman \
	tsintegers.catman \
	tslogistic.catman \
	tspink.catman \
	tsunfairbrownian.catman \
	tsunfairfractional.catman \
	tswhite.catman \
	tsstockwager.catman \
	tsbinomial.catman \
	tsshannonstock.catman \
	tsmarket.catman \
	tsstock.catman \
	tsstocks.catman \
	tstrade.catman \
	tstradesim.catman \
	tscauchy.catman \
	tslognormal.catman \
	tslaplacian.catman

tsblack.catman: tsblack.1
	nroff -man tsblack.1 > tsblack.catman

tsbrownian.catman: tsbrownian.1
	nroff -man tsbrownian.1 > tsbrownian.catman

tscoin.catman: tscoin.1
	nroff -man tscoin.1 > tscoin.catman

tscoins.catman: tscoins.1
	nroff -man tscoins.1 > tscoins.catman

tsdlogistic.catman: tsdlogistic.1
	nroff -man tsdlogistic.1 > tsdlogistic.catman

tsfBm.catman: tsfBm.1
	nroff -man tsfBm.1 > tsfBm.catman

tsfractional.catman: tsfractional.1
	nroff -man tsfractional.1 > tsfractional.catman

tsgaussian.catman: tsgaussian.1
	nroff -man tsgaussian.1 > tsgaussian.catman

tsintegers.catman: tsintegers.1
	nroff -man tsintegers.1 > tsintegers.catman

tslogistic.catman: tslogistic.1
	nroff -man tslogistic.1 > tslogistic.catman

tspink.catman: tspink.1
	nroff -man tspink.1 > tspink.catman

tsunfairbrownian.catman: tsunfairbrownian.1
	nroff -man tsunfairbrownian.1 > tsunfairbrownian.catman

tsunfairfractional.catman: tsunfairfractional.1
	nroff -man tsunfairfractional.1 > tsunfairfractional.catman

tswhite.catman: tswhite.1
	nroff -man tswhite.1 > tswhite.catman

tsstockwager.catman: tsstockwager.1
	nroff -man tsstockwager.1 > tsstockwager.catman

tsbinomial.catman: tsbinomial.1
	nroff -man tsbinomial.1 > tsbinomial.catman

tsshannonstock.catman: tsshannonstock.1
	nroff -man tsshannonstock.1 > tsshannonstock.catman

tsmarket.catman: tsmarket.1
	nroff -man tsmarket.1 > tsmarket.catman

tsstock.catman: tsstock.1
	nroff -man tsstock.1 > tsstock.catman

tsstocks.catman: tsstocks.1
	nroff -man tsstocks.1 > tsstocks.catman

tstrade.catman: tstrade.1
	nroff -man tstrade.1 > tstrade.catman

tstradesim.catman: tstradesim.1
	nroff -man tstradesim.1 > tstradesim.catman

tscauchy.catman: tscauchy.1
	nroff -man tscauchy.1 > tscauchy.catman

tslognormal.catman: tslognormal.1
	nroff -man tslognormal.1 > tslognormal.catman

tslaplacian.catman: tslaplacian.1
	nroff -man tslaplacian.1 > tslaplacian.catman

clean:
	rm -f \
	tsfractional \
	tsbrownian \
	tswhite \
	tsgaussian \
	tsdlogistic \
	tspink \
	tsblack \
	tsfBm \
	tsintegers \
	tslogistic \
	tsunfairfractional \
	tsunfairbrownian \
	tscoin \
	tscoins \
	tsstockwager \
	tsbinomial \
	tsshannonstock \
	tsmarket \
	tsstock \
	tsstocks \
	tstrade \
	tstradesim \
	tscauchy \
	tslognormal \
	tslaplacian

realclean:
	rm -f \
	tsfractional \
	tsbrownian \
	tswhite \
	tsgaussian \
	tsdlogistic \
	tspink \
	tsblack \
	tsfBm \
	tsintegers \
	tslogistic \
	tsunfairfractional \
	tsunfairbrownian \
	tscoin \
	tscoins \
	tsstockwager \
	tsbinomial \
	tsshannonstock \
	tsmarket \
	tsstock \
	tsstocks \
	tstrade \
	tstradesim \
	tscauchy \
	tslognormal \
	tslaplacian \
	tsblack.catman \
	tsbrownian.catman \
	tscoin.catman \
	tscoins.catman \
	tsdlogistic.catman \
	tsfBm.catman \
	tsfractional.catman \
	tsgaussian.catman \
	tsintegers.catman \
	tslogistic.catman \
	tspink.catman \
	tsunfairbrownian.catman \
	tsunfairfractional.catman \
	tswhite.catman \
	tsstockwager.catman \
	tsbinomial.catman \
	tsshannonstock.catman \
	tsmarket.catman \
	tsstock.catman \
	tsstocks.catman \
	tstrade.catman \
	tstradesim.catman \
	tscauchy.catman \
	tslognormal.catman \
	tslaplacian.catman
