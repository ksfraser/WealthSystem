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
# Makefile for the time series utilities.
#
# $Revision: 0.0 $
# $Date: 2006/01/25 19:55:40 $
# $Id: Makefile,v 0.0 2006/01/25 19:55:40 john Exp $
# $Log: Makefile,v $
# Revision 0.0  2006/01/25 19:55:40  john
# Initial version
#
#
CFLAGS = -DLINT -pedantic -W -Wall -Waggregate-return -Wcast-align -Wcast-qual -Wconversion -Winline -Wmissing-prototypes -Wmissing-declarations -Wnested-externs -Wno-import -Wpointer-arith -Wshadow -Wstrict-prototypes -Wwrite-strings -O

all: programs catman

programs: tshurst \
	  tsderivative \
	  tslsq \
	  tsnormal \
	  tsshannon \
	  tsintegrate \
	  tslogreturns \
	  tshcalc \
	  tsfraction \
	  tsshannonmax \
	  tschangewager \
	  tssample \
	  tsrms \
	  tsavg \
	  tsXsquared \
	  tsshannonwindow \
	  tsmath \
	  tsavgwindow \
	  tspole \
	  tsdft \
	  tsdeterministic \
	  tsnumber \
	  tsrmswindow \
	  tsstatest \
	  tsunfraction \
	  tsshannonaggregate \
	  tsinstant \
	  tsshannonvolume \
	  tsshannonfundamental \
	  tsrunlength \
	  tsunshannon \
	  tsrootmean \
	  tsrunmagnitude \
	  tskurtosis \
	  tskurtosiswindow \
	  tsrootmeanscale \
	  tsscalederivative \
	  tsgain \
	  tsgainwindow \
	  tskalman \
	  tsroot

tshurst: tshurst.c
	cc $(CFLAGS) -o tshurst tshurst.c -lm

tsderivative: tsderivative.c
	cc $(CFLAGS) -o tsderivative tsderivative.c -lm

tslsq: tslsq.c
	cc $(CFLAGS) -o tslsq tslsq.c -lm

tsnormal: tsnormal.c
	cc $(CFLAGS) -o tsnormal tsnormal.c -lm

tsshannon: tsshannon.c
	cc $(CFLAGS) -o tsshannon tsshannon.c -lm

tsintegrate: tsintegrate.c
	cc $(CFLAGS) -o tsintegrate tsintegrate.c -lm

tslogreturns: tslogreturns.c
	cc $(CFLAGS) -o tslogreturns tslogreturns.c -lm

tshcalc: tshcalc.c
	cc $(CFLAGS) -o tshcalc tshcalc.c -lm

tsfraction: tsfraction.c
	cc $(CFLAGS) -o tsfraction tsfraction.c

tsshannonmax: tsshannonmax.c
	cc $(CFLAGS) -o tsshannonmax tsshannonmax.c -lm

tschangewager: tschangewager.c
	cc $(CFLAGS) -o tschangewager tschangewager.c

tssample: tssample.c
	cc $(CFLAGS) -o tssample tssample.c

tsrms: tsrms.c
	cc $(CFLAGS) -o tsrms tsrms.c -lm

tsavg: tsavg.c
	cc $(CFLAGS) -o tsavg tsavg.c -lm

tsXsquared: tsXsquared.c
	cc $(CFLAGS) -o tsXsquared tsXsquared.c -lm

tsshannonwindow: tsshannonwindow.c
	cc $(CFLAGS) -o tsshannonwindow tsshannonwindow.c -lm

tsmath: tsmath.c
	cc $(CFLAGS) -o tsmath tsmath.c -lm

tsavgwindow: tsavgwindow.c
	cc $(CFLAGS) -o tsavgwindow tsavgwindow.c

tspole: tspole.c
	cc $(CFLAGS) -o tspole tspole.c -lm

tsdft: tsdft.c
	cc $(CFLAGS) -o tsdft tsdft.c -lm

tsdeterministic: tsdeterministic.c
	cc $(CFLAGS) -o tsdeterministic tsdeterministic.c

tsnumber: tsnumber.c
	cc $(CFLAGS) -o tsnumber tsnumber.c

tsrmswindow: tsrmswindow.c
	cc $(CFLAGS) -o tsrmswindow tsrmswindow.c -lm

tsstatest: tsstatest.c
	cc $(CFLAGS) -o tsstatest tsstatest.c -lm

tsunfraction: tsunfraction.c
	cc $(CFLAGS) -o tsunfraction tsunfraction.c

tsshannonaggregate: tsshannonaggregate.c
	cc $(CFLAGS) -o tsshannonaggregate tsshannonaggregate.c -lm

tsinstant: tsinstant.c
	cc $(CFLAGS) -o tsinstant tsinstant.c

tsshannonvolume: tsshannonvolume.c
	cc $(CFLAGS) -o tsshannonvolume tsshannonvolume.c -lm

tsshannonfundamental: tsshannonfundamental.c
	cc $(CFLAGS) -o tsshannonfundamental tsshannonfundamental.c -lm

tsrunlength: tsrunlength.c
	cc $(CFLAGS) -o tsrunlength tsrunlength.c

tsunshannon: tsunshannon.c
	cc $(CFLAGS) -o tsunshannon tsunshannon.c -lm

tsrootmean: tsrootmean.c
	cc $(CFLAGS) -o tsrootmean tsrootmean.c -lm

tsrunmagnitude: tsrunmagnitude.c
	cc $(CFLAGS) -o tsrunmagnitude tsrunmagnitude.c -lm

tskurtosis: tskurtosis.c
	cc $(CFLAGS) -o tskurtosis tskurtosis.c

tskurtosiswindow: tskurtosiswindow.c
	cc $(CFLAGS) -o tskurtosiswindow tskurtosiswindow.c

tsrootmeanscale: tsrootmeanscale.c
	cc $(CFLAGS) -o tsrootmeanscale tsrootmeanscale.c

tsscalederivative: tsscalederivative.c
	cc $(CFLAGS) -o tsscalederivative tsscalederivative.c

tsgain: tsgain.c
	cc $(CFLAGS) -o tsgain tsgain.c -lm

tsgainwindow: tsgainwindow.c
	cc $(CFLAGS) -o tsgainwindow tsgainwindow.c -lm

tskalman: tskalman.c
	cc $(CFLAGS) -o tskalman tskalman.c -lm

tsroot: tsroot.c
	cc $(CFLAGS) -o tsroot tsroot.c -lm

catman: tshurst.catman \
	tsderivative.catman \
	tslsq.catman \
	tsnormal.catman \
	tsshannon.catman \
	tsintegrate.catman \
	tslogreturns.catman \
	tshcalc.catman \
	tsfraction.catman \
	tsshannonmax.catman \
	tschangewager.catman \
	tssample.catman \
	tsrms.catman \
	tsavg.catman \
	tsXsquared.catman \
	tsshannonwindow.catman \
	tsmath.catman \
	tsavgwindow.catman \
	tspole.catman \
	tsdft.catman \
	tsdeterministic.catman \
	tsnumber.catman \
	tsrmswindow.catman \
	tsstatest.catman \
	tsunfraction.catman \
	tsshannonaggregate.catman \
	tsinstant.catman \
	tsshannonvolume.catman \
	tsshannonfundamental.catman \
	tsrunlength.catman \
	tsunshannon.catman \
	tsrootmean.catman \
	tsrunmagnitude.catman \
	tskurtosis.catman \
	tskurtosiswindow.catman \
	tsrootmeanscale.catman \
	tsscalederivative.catman \
	tsgain.catman \
	tsgainwindow.catman \
	tskalman.catman \
	tsroot.catman

tsshannonwindow.catman: tsshannonwindow.1
	nroff -man tsshannonwindow.1 > tsshannonwindow.catman

tsXsquared.catman: tsXsquared.1
	nroff -man tsXsquared.1 > tsXsquared.catman

tsavg.catman: tsavg.1
	nroff -man tsavg.1 > tsavg.catman

tschangewager.catman: tschangewager.1
	nroff -man tschangewager.1 > tschangewager.catman

tsderivative.catman: tsderivative.1
	nroff -man tsderivative.1 > tsderivative.catman

tsfraction.catman: tsfraction.1
	nroff -man tsfraction.1 > tsfraction.catman

tshcalc.catman: tshcalc.1
	nroff -man tshcalc.1 > tshcalc.catman

tshurst.catman: tshurst.1
	nroff -man tshurst.1 > tshurst.catman

tsintegrate.catman: tsintegrate.1
	nroff -man tsintegrate.1 > tsintegrate.catman

tslogreturns.catman: tslogreturns.1
	nroff -man tslogreturns.1 > tslogreturns.catman

tslsq.catman: tslsq.1
	nroff -man tslsq.1 > tslsq.catman

tsnormal.catman: tsnormal.1
	nroff -man tsnormal.1 > tsnormal.catman

tsrms.catman: tsrms.1
	nroff -man tsrms.1 > tsrms.catman

tssample.catman: tssample.1
	nroff -man tssample.1 > tssample.catman

tsshannon.catman: tsshannon.1
	nroff -man tsshannon.1 > tsshannon.catman

tsshannonmax.catman: tsshannonmax.1
	nroff -man tsshannonmax.1 > tsshannonmax.catman

tsmath.catman: tsmath.1
	nroff -man tsmath.1 > tsmath.catman

tsavgwindow.catman: tsavgwindow.1
	nroff -man tsavgwindow.1 > tsavgwindow.catman

tspole.catman: tspole.1
	nroff -man tspole.1 > tspole.catman

tsdft.catman: tsdft.1
	nroff -man tsdft.1 > tsdft.catman

tsdeterministic.catman: tsdeterministic.1
	nroff -man tsdeterministic.1 > tsdeterministic.catman

tsnumber.catman: tsnumber.1
	nroff -man tsnumber.1 > tsnumber.catman

tsrmswindow.catman: tsrmswindow.1
	nroff -man tsrmswindow.1 > tsrmswindow.catman

tsstatest.catman: tsstatest.1
	nroff -man tsstatest.1 > tsstatest.catman

tsunfraction.catman: tsunfraction.1
	nroff -man tsunfraction.1 > tsunfraction.catman

tsshannonaggregate.catman: tsshannonaggregate.1
	nroff -man tsshannonaggregate.1 > tsshannonaggregate.catman

tsinstant.catman: tsinstant.1
	nroff -man tsinstant.1 > tsinstant.catman

tsshannonvolume.catman: tsshannonvolume.1
	nroff -man tsshannonvolume.1 > tsshannonvolume.catman

tsshannonfundamental.catman: tsshannonfundamental.1
	nroff -man tsshannonfundamental.1 > tsshannonfundamental.catman

tsrunlength.catman: tsrunlength.1
	nroff -man tsrunlength.1 > tsrunlength.catman

tsunshannon.catman: tsunshannon.1
	nroff -man tsunshannon.1 > tsunshannon.catman

tsrootmean.catman: tsrootmean.1
	nroff -man tsrootmean.1 > tsrootmean.catman

tsrunmagnitude.catman: tsrunmagnitude.1
	nroff -man tsrunmagnitude.1 > tsrunmagnitude.catman

tskurtosis.catman: tskurtosis.1
	nroff -man tskurtosis.1 > tskurtosis.catman

tskurtosiswindow.catman: tskurtosiswindow.1
	nroff -man tskurtosiswindow.1 > tskurtosiswindow.catman

tsrootmeanscale.catman: tsrootmeanscale.1
	nroff -man tsrootmeanscale.1 > tsrootmeanscale.catman

tsscalederivative.catman: tsscalederivative.1
	nroff -man tsscalederivative.1 > tsscalederivative.catman

tsgain.catman: tsgain.1
	nroff -man tsgain.1 > tsgain.catman

tsgainwindow.catman: tsgainwindow.1
	nroff -man tsgainwindow.1 > tsgainwindow.catman

tskalman.catman: tskalman.1
	nroff -man tskalman.1 > tskalman.catman

tsroot.catman: tsroot.1
	nroff -man tsroot.1 > tsroot.catman

clean:
	rm -f \
	tshurst \
	tsderivative \
	tslsq \
	tsnormal \
	tsshannon \
	tsintegrate \
	tslogreturns \
	tshcalc \
	tsfraction \
	tsshannonmax \
	tschangewager \
	tssample \
	tsrms \
	tsavg \
	tsXsquared \
	tsshannonwindow \
	tsmath \
	tsavgwindow \
	tspole \
	tsdft \
	tsdeterministic \
	tsnumber \
	tsrmswindow \
	tsstatest \
	tsunfraction \
	tsshannonaggregate \
	tsinstant \
	tsshannonvolume \
	tsshannonfundamental \
	tsrunlength \
	tsunshannon \
	tsrootmean \
	tsrunmagnitude \
	tskurtosis \
	tskurtosiswindow \
	tsrootmeanscale \
	tsscalederivative \
	tsgain \
	tsgainwindow \
	tskalman \
	tsroot

realclean:
	rm -f \
	tshurst \
	tsderivative \
	tslsq \
	tsnormal \
	tsshannon \
	tsintegrate \
	tslogreturns \
	tshcalc \
	tsfraction \
	tsshannonmax \
	tschangewager \
	tssample \
	tsrms \
	tsavg \
	tsXsquared \
	tsshannonwindow \
	tsmath \
	tsavgwindow \
	tspole \
	tsdft \
	tsdeterministic \
	tsnumber \
	tsrmswindow \
	tsstatest \
	tsunfraction \
	tsshannonaggregate \
	tsinstant \
	tsshannonvolume \
	tsshannonfundamental \
	tsrunlength \
	tsunshannon \
	tsrootmean \
	tsrunmagnitude \
	tskurtosis \
	tskurtosiswindow \
	tsrootmeanscale \
	tsscalederivative \
	tsgain \
	tsgainwindow \
	tskalman \
	tsroot \
	tshurst.catman \
	tsderivative.catman \
	tslsq.catman \
	tsnormal.catman \
	tsshannon.catman \
	tsintegrate.catman \
	tslogreturns.catman \
	tshcalc.catman \
	tsfraction.catman \
	tsshannonmax.catman \
	tschangewager.catman \
	tssample.catman \
	tsrms.catman \
	tsavg.catman \
	tsXsquared.catman \
	tsshannonwindow.catman \
	tsmath.catman \
	tsavgwindow.catman \
	tspole.catman \
	tsdft.catman \
	tsdeterministic.catman \
	tsnumber.catman \
	tsrmswindow.catman \
	tsstatest.catman \
	tsunfraction.catman \
	tsshannonaggregate.catman \
	tsinstant.catman \
	tsshannonvolume.catman \
	tsshannonfundamental.catman \
	tsrunlength.catman \
	tsunshannon.catman \
	tsrootmean.catman \
	tsrunmagnitude.catman \
	tskurtosis.catman \
	tskurtosiswindow.catman \
	tsrootmeanscale.catman \
	tsscalederivative.catman \
	tsgain.catman \
	tsgainwindow.catman \
	tskalman.catman \
	tsroot.catman
