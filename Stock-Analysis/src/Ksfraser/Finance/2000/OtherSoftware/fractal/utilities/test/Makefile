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
# Makefile for various tests for the utilities and simulation programs
#
# The tests may be viewed using gnuplot, with the command load
# "gnuplot plot"
#
# Note: the tests use 1500 samples, so, depending on machine resources,
# could take a very long time to execute.
#
# $Revision: 0.0 $
# $Date: 2006/01/25 20:21:29 $
# $Id: Makefile,v 0.0 2006/01/25 20:21:29 john Exp $
# $Log: Makefile,v $
# Revision 0.0  2006/01/25 20:21:29  john
# Initial version
#
#
DATA = tsfBm-h0.0 \
       tsfBm-h0.1 \
       tsfBm-h0.3 \
       tsfBm-h0.5 \
       tsfBm-h0.7 \
       tsfBm-h0.9 \
       tsfBm-h1.0 \
       tsfBm-h0.0.tshurst \
       tsfBm-h0.1.tshurst \
       tsfBm-h0.3.tshurst \
       tsfBm-h0.5.tshurst \
       tsfBm-h0.7.tshurst \
       tsfBm-h0.9.tshurst \
       tsfBm-h1.0.tshurst \
       tsfBm-h0.0.tshcalc \
       tsfBm-h0.1.tshcalc \
       tsfBm-h0.3.tshcalc \
       tsfBm-h0.5.tshcalc \
       tsfBm-h0.7.tshcalc \
       tsfBm-h0.9.tshcalc \
       tsfBm-h1.0.tshcalc \
       tsfBm-h0.5.tsnormal \
       tsfBm-h0.5.tsnormal-f \
       tsbrownian \
       tsbrownian.tsderivative \
       tsbrownian.tsderivative.tsnormal \
       tsbrownian.tsderivative.tsnormal-f \
       tswhite \
       tswhite.tsintegrate \
       tswhite.tsintegrate.tsnormal \
       tswhite.tsintegrate.tsnormal-f \
       tscoin \
       tscoin.tsfraction \
       tscoin.tsfraction.tsrms \
       tscoin.tsfraction.tslsq \
       tscoin.tschangewager \
       tscoin.tsshannonmax \
       tscoin.tsfraction.tsavg \
       tscoin.tsfraction.tskalman \
       tscoin.tssample \
       tscoin.tsfraction.tsavgwindow \
       tscoin.tsfraction.tsrmswindow \
       tscoin.tspole-p0.01 \
       tscoin.tsdft-s \
       tscoin.tsmath-l \
       tscoin.tsroot-l \
       tsdlogistic \
       tsdlogistic.tsdeterministic \
       tscoin.tsfraction.tsunfraction \
       tscoin.tsinstant \
       tsfractional.tsrunlength.cut-f1.4 \
       tsfractional.tsrunlength.cut-f1.7 \
       tsfBm-h0.0.tsrootmean \
       tsfBm-h0.1.tsrootmean \
       tsfBm-h0.3.tsrootmean \
       tsfBm-h0.5.tsrootmean \
       tsfBm-h0.7.tsrootmean \
       tsfBm-h0.9.tsrootmean \
       tsfBm-h1.0.tsrootmean \
       tsfBm-h0.0.tsrunmagnitude \
       tsfBm-h0.1.tsrunmagnitude \
       tsfBm-h0.3.tsrunmagnitude \
       tsfBm-h0.5.tsrunmagnitude \
       tsfBm-h0.7.tsrunmagnitude \
       tsfBm-h0.9.tsrunmagnitude \
       tsfBm-h1.0.tsrunmagnitude \
       tsfBm-h0.0.tsrootmeanscale \
       tsfBm-h0.1.tsrootmeanscale \
       tsfBm-h0.3.tsrootmeanscale \
       tsfBm-h0.5.tsrootmeanscale \
       tsfBm-h0.7.tsrootmeanscale \
       tsfBm-h0.9.tsrootmeanscale \
       tsfBm-h1.0.tsrootmeanscale \
       tscoin.tsgain \
       tscoin.tsgainwindow \
       tsfBm-h0.0.tshurst.tsscalederivative \
       tsfBm-h0.1.tshurst.tsscalederivative \
       tsfBm-h0.3.tshurst.tsscalederivative \
       tsfBm-h0.5.tshurst.tsscalederivative \
       tsfBm-h0.7.tshurst.tsscalederivative \
       tsfBm-h0.9.tshurst.tsscalederivative \
       tsfBm-h1.0.tshurst.tsscalederivative \
       tsfBm-h0.0.tskurtosis \
       tsfBm-h0.1.tskurtosis \
       tsfBm-h0.3.tskurtosis \
       tsfBm-h0.5.tskurtosis \
       tsfBm-h0.7.tskurtosis \
       tsfBm-h0.9.tskurtosis \
       tsfBm-h1.0.tskurtosis \
       tsfBm-h0.0.tskurtosiswindow \
       tsfBm-h0.1.tskurtosiswindow \
       tsfBm-h0.3.tskurtosiswindow \
       tsfBm-h0.5.tskurtosiswindow \
       tsfBm-h0.7.tskurtosiswindow \
       tsfBm-h0.9.tskurtosiswindow \
       tsfBm-h1.0.tskurtosiswindow

GRAPHICS = tsfBm.tshcalc.eps \
	   tsfBm.tshurst.eps \
	   tsfBm.tsnormal.eps \
	   tsbrownian.tsderivative.tsnormal.eps \
	   tswhite.tsintegrate.tsnormal.eps \
	   tscoin.eps \
	   tscoin.tsfraction.eps \
	   tscoin.tsfraction.tsrms.eps \
	   tscoin.tsfraction.tslsq.eps \
	   tscoin.tschangewager.eps \
	   tscoin.tsshannonmax.eps \
	   tscoin.tslogreturns-p.tex \
	   tscoin.tslogreturns-p.tsshannon.tex \
	   tscoin.tsfraction.tsavg.eps \
	   tscoin.tsfraction.tskalman.eps \
	   tscoin.tssample.eps \
	   tsbrownian.tsderivative.tsnormal.tsXsquared.tex \
	   tscoin.tsfraction.tsavgwindow.eps \
	   tscoin.tsfraction.tsrmswindow.eps \
	   tscoin.tsshannonwindow-a-b-c-d-e-f-g-h.tex \
	   tscoin.tspole-p0.01.eps \
	   tscoin.tsdft-s.eps \
	   tscoin.tsmath-l.eps \
	   tscoin.tsroot-l.eps \
	   tsdlogistic.tsdeterministic.eps \
	   tsfBm-h0.5.tsstatest-f0.1-c0.9-i.tex \
	   tsshannonaggregate-p_0.55_0.65.tex \
	   tscoin.tsfraction.tsunfraction.eps \
	   tscoin.tsinstant.eps \
	   tsfractional.tsrunlength.cut-f1.4.eps \
	   tsfractional.tsrunlength.cut-f1.7.eps \
	   tsfBm.tsrootmean.eps \
	   tsfBm.tsrunmagnitude.eps \
	   tsfBm.tsrootmeanscale.eps \
	   tscoin.tsgain.eps \
	   tscoin.tsgainwindow.eps \
	   tsfBm.tshurst.tsscalederivative.eps \
	   tsfBm.tskurtosis.eps \
	   tsfBm.tskurtosiswindow.eps

all: ${GRAPHICS}

.PHONEY: ${GRAPHICS}

${GRAPHICS}: ${DATA}
	gnuplot gnuplot
	../tslogreturns -p tscoin | sed 's/(/{(/g'  | sed 's/)/)}/g' > tscoin.tslogreturns-p.tex
	../tsshannon `sed 's/.*2\^{(//' tscoin.tslogreturns-p.tex | sed 's/t)}//'` > tscoin.tslogreturns-p.tsshannon.tex
	../tsXsquared tsbrownian.tsderivative.tsnormal-f tsbrownian.tsderivative.tsnormal > tsbrownian.tsderivative.tsnormal.tsXsquared.tex
	../tsshannonwindow -a -b -c -d -e -f -g -h tscoin > tscoin.tsshannonwindow-a-b-c-d-e-f-g-h.tex
	echo "{\\footnotesize\\begin{verbatim}" > tsfBm-h0.5.tsstatest-f0.1-c0.9-i.tex; ../tsstatest -f 0.1 -c 0.9 -i tsfBm-h0.5 >> tsfBm-h0.5.tsstatest-f0.1-c0.9-i.tex; echo "\\end{verbatim}}" >> tsfBm-h0.5.tsstatest-f0.1-c0.9-i.tex
	echo "{\\footnotesize\\begin{verbatim}" > tsshannonaggregate-p_0.55_0.65.tex; ../tsshannonaggregate -p 0.55 0.65 >> tsshannonaggregate-p_0.55_0.65.tex; echo "\\end{verbatim}}" >> tsshannonaggregate-p_0.55_0.65.tex

tsfBm-h0.0:
	../../simulation/tsfBm -m 1500 -h 0.0 1500 > tsfBm-h0.0

tsfBm-h0.0.tshurst: tsfBm-h0.0
	../tshurst tsfBm-h0.0 > tsfBm-h0.0.tshurst

tsfBm-h0.0.tshcalc: tsfBm-h0.0
	../tshcalc tsfBm-h0.0 > tsfBm-h0.0.tshcalc

tsfBm-h0.1:
	../../simulation/tsfBm -m 1500 -h 0.1 1500 > tsfBm-h0.1

tsfBm-h0.1.tshurst: tsfBm-h0.1
	../tshurst tsfBm-h0.1 > tsfBm-h0.1.tshurst

tsfBm-h0.1.tshcalc: tsfBm-h0.1
	../tshcalc tsfBm-h0.1 > tsfBm-h0.1.tshcalc

tsfBm-h0.3:
	../../simulation/tsfBm -m 1500 -h 0.3 1500 > tsfBm-h0.3

tsfBm-h0.3.tshurst: tsfBm-h0.3
	../tshurst tsfBm-h0.3 > tsfBm-h0.3.tshurst

tsfBm-h0.3.tshcalc: tsfBm-h0.3
	../tshcalc tsfBm-h0.3 > tsfBm-h0.3.tshcalc

tsfBm-h0.5:
	../../simulation/tsfBm -m 1500 -h 0.5 1500 > tsfBm-h0.5

tsfBm-h0.5.tshurst: tsfBm-h0.5
	../tshurst tsfBm-h0.5 > tsfBm-h0.5.tshurst

tsfBm-h0.5.tshcalc: tsfBm-h0.5
	../tshcalc tsfBm-h0.5 > tsfBm-h0.5.tshcalc

tsfBm-h0.7:
	../../simulation/tsfBm -m 1500 -h 0.7 1500 > tsfBm-h0.7

tsfBm-h0.7.tshurst: tsfBm-h0.7
	../tshurst tsfBm-h0.7 > tsfBm-h0.7.tshurst

tsfBm-h0.7.tshcalc: tsfBm-h0.7
	../tshcalc tsfBm-h0.7 > tsfBm-h0.7.tshcalc

tsfBm-h0.9:
	../../simulation/tsfBm -m 1500 -h 0.9 1500 > tsfBm-h0.9

tsfBm-h0.9.tshurst: tsfBm-h0.9
	../tshurst tsfBm-h0.9 > tsfBm-h0.9.tshurst

tsfBm-h0.9.tshcalc: tsfBm-h0.9
	../tshcalc tsfBm-h0.9 > tsfBm-h0.9.tshcalc

tsfBm-h1.0:
	../../simulation/tsfBm -m 1500 -h 1.0 1500 > tsfBm-h1.0

tsfBm-h1.0.tshurst: tsfBm-h1.0
	../tshurst tsfBm-h1.0 > tsfBm-h1.0.tshurst

tsfBm-h1.0.tshcalc: tsfBm-h1.0
	../tshcalc tsfBm-h1.0 > tsfBm-h1.0.tshcalc

tsfBm-h0.5.tsnormal: tsfBm-h0.5
	../tsnormal -t tsfBm-h0.5 > tsfBm-h0.5.tsnormal

tsfBm-h0.5.tsnormal-f: tsfBm-h0.5
	../tsnormal -t -f tsfBm-h0.5 > tsfBm-h0.5.tsnormal-f

tsbrownian:
	../../simulation/tsbrownian 1500 > tsbrownian

tsbrownian.tsderivative: tsbrownian
	../tsderivative tsbrownian > tsbrownian.tsderivative

tsbrownian.tsderivative.tsnormal: tsbrownian.tsderivative
	../tsnormal -t tsbrownian.tsderivative > tsbrownian.tsderivative.tsnormal

tsbrownian.tsderivative.tsnormal-f: tsbrownian.tsderivative
	../tsnormal -t -f tsbrownian.tsderivative > tsbrownian.tsderivative.tsnormal-f

tswhite:
	../../simulation/tswhite 1500 > tswhite

tswhite.tsintegrate: tswhite
	../tsintegrate tswhite > tswhite.tsintegrate

tswhite.tsintegrate.tsnormal: tswhite.tsintegrate
	../tsnormal -t tswhite.tsintegrate > tswhite.tsintegrate.tsnormal

tswhite.tsintegrate.tsnormal-f: tswhite.tsintegrate
	../tsnormal -t -f tswhite.tsintegrate > tswhite.tsintegrate.tsnormal-f

tscoin:
	../../simulation/tscoin -p 0.6 1500 > tscoin

tscoin.tsfraction: tscoin
	../tsfraction tscoin > tscoin.tsfraction

tscoin.tsfraction.tsrms: tscoin.tsfraction
	../tsrms tscoin.tsfraction > tscoin.tsfraction.tsrms

tscoin.tsfraction.tslsq: tscoin.tsfraction
	../tslsq tscoin.tsfraction > tscoin.tsfraction.tslsq

tscoin.tschangewager: tscoin
	../tschangewager -f 0.5 tscoin > tscoin.tschangewager

tscoin.tsshannonmax: tscoin
	../tsshannonmax tscoin > tscoin.tsshannonmax

tscoin.tsfraction.tsavg: tscoin.tsfraction
	../tsavg tscoin.tsfraction > tscoin.tsfraction.tsavg

tscoin.tsfraction.tskalman: tscoin.tsfraction
	../tskalman tscoin.tsfraction > tscoin.tsfraction.tskalman

tscoin.tssample: tscoin
	../tssample -i 2 tscoin > tscoin.tssample

tscoin.tsfraction.tsavgwindow: tscoin.tsfraction
	../tsavgwindow tscoin.tsfraction > tscoin.tsfraction.tsavgwindow

tscoin.tsfraction.tsrmswindow: tscoin.tsfraction
	../tsrmswindow tscoin.tsfraction > tscoin.tsfraction.tsrmswindow

tscoin.tspole-p0.01: tscoin
	../tspole -p 0.01 tscoin > tscoin.tspole-p0.01

tscoin.tsdft-s: tscoin
	../tsdft -s tscoin > tscoin.tsdft-s

tscoin.tsmath-l: tscoin
	../tsmath -l tscoin > tscoin.tsmath-l

tscoin.tsroot-l: tscoin
	../tsroot -l tscoin > tscoin.tsroot-l

tsdlogistic:
	../../simulation/tsdlogistic -a 4 -b -1 1000 > tsdlogistic

tsdlogistic.tsdeterministic: tsdlogistic
	../tsdeterministic tsdlogistic > tsdlogistic.tsdeterministic

tscoin.tsfraction.tsunfraction: tscoin.tsfraction
	../tsunfraction tscoin.tsfraction > tscoin.tsfraction.tsunfraction

tscoin.tsinstant: tscoin
	../tsinstant tscoin > tscoin.tsinstant

tsfractional.tsrunlength.cut-f1.4:
	../../simulation/tsfractional 100000 | ../tsrunlength | cut -f1,4 > tsfractional.tsrunlength.cut-f1.4

tsfractional.tsrunlength.cut-f1.7:
	../../simulation/tsfractional 100000 | ../tsrunlength | cut -f1,7 > tsfractional.tsrunlength.cut-f1.7

tsfBm-h0.0.tsrootmean: tsfBm-h0.0
	../tsrootmean tsfBm-h0.0 > tsfBm-h0.0.tsrootmean

tsfBm-h0.1.tsrootmean: tsfBm-h0.1
	../tsrootmean tsfBm-h0.1 > tsfBm-h0.1.tsrootmean

tsfBm-h0.3.tsrootmean: tsfBm-h0.3
	../tsrootmean tsfBm-h0.3 > tsfBm-h0.3.tsrootmean

tsfBm-h0.5.tsrootmean: tsfBm-h0.5
	../tsrootmean tsfBm-h0.5 > tsfBm-h0.5.tsrootmean

tsfBm-h0.7.tsrootmean: tsfBm-h0.7
	../tsrootmean tsfBm-h0.7 > tsfBm-h0.7.tsrootmean

tsfBm-h0.9.tsrootmean: tsfBm-h0.9
	../tsrootmean tsfBm-h0.9 > tsfBm-h0.9.tsrootmean

tsfBm-h1.0.tsrootmean: tsfBm-h1.0
	../tsrootmean tsfBm-h1.0 > tsfBm-h1.0.tsrootmean

tsfBm-h0.0.tsrunmagnitude: tsfBm-h0.0
	../tsrunmagnitude tsfBm-h0.0 > tsfBm-h0.0.tsrunmagnitude

tsfBm-h0.1.tsrunmagnitude: tsfBm-h0.1
	../tsrunmagnitude tsfBm-h0.1 > tsfBm-h0.1.tsrunmagnitude

tsfBm-h0.3.tsrunmagnitude: tsfBm-h0.3
	../tsrunmagnitude tsfBm-h0.3 > tsfBm-h0.3.tsrunmagnitude

tsfBm-h0.5.tsrunmagnitude: tsfBm-h0.5
	../tsrunmagnitude tsfBm-h0.5 > tsfBm-h0.5.tsrunmagnitude

tsfBm-h0.7.tsrunmagnitude: tsfBm-h0.7
	../tsrunmagnitude tsfBm-h0.7 > tsfBm-h0.7.tsrunmagnitude

tsfBm-h0.9.tsrunmagnitude: tsfBm-h0.9
	../tsrunmagnitude tsfBm-h0.9 > tsfBm-h0.9.tsrunmagnitude

tsfBm-h1.0.tsrunmagnitude: tsfBm-h1.0
	../tsrunmagnitude tsfBm-h1.0 > tsfBm-h1.0.tsrunmagnitude

tsfBm-h0.0.tsrootmeanscale: tsfBm-h0.0
	../tsrootmeanscale -p tsfBm-h0.0 > tsfBm-h0.0.tsrootmeanscale

tsfBm-h0.1.tsrootmeanscale: tsfBm-h0.1
	../tsrootmeanscale -p tsfBm-h0.1 > tsfBm-h0.1.tsrootmeanscale

tsfBm-h0.3.tsrootmeanscale: tsfBm-h0.3
	../tsrootmeanscale -p tsfBm-h0.3 > tsfBm-h0.3.tsrootmeanscale

tsfBm-h0.5.tsrootmeanscale: tsfBm-h0.5
	../tsrootmeanscale -p tsfBm-h0.5 > tsfBm-h0.5.tsrootmeanscale

tsfBm-h0.7.tsrootmeanscale: tsfBm-h0.7
	../tsrootmeanscale -p tsfBm-h0.7 > tsfBm-h0.7.tsrootmeanscale

tsfBm-h0.9.tsrootmeanscale: tsfBm-h0.9
	../tsrootmeanscale -p tsfBm-h0.9 > tsfBm-h0.9.tsrootmeanscale

tsfBm-h1.0.tsrootmeanscale: tsfBm-h1.0
	../tsrootmeanscale -p tsfBm-h1.0 > tsfBm-h1.0.tsrootmeanscale

tscoin.tsgain: tscoin
	../tsgain tscoin > tscoin.tsgain

tscoin.tsgainwindow: tscoin
	../tsgainwindow tscoin > tscoin.tsgainwindow

tsfBm-h0.0.tshurst.tsscalederivative: tsfBm-h0.0.tshurst
	../tsscalederivative tsfBm-h0.0.tshurst > tsfBm-h0.0.tshurst.tsscalederivative

tsfBm-h0.1.tshurst.tsscalederivative: tsfBm-h0.1.tshurst
	../tsscalederivative tsfBm-h0.1.tshurst > tsfBm-h0.1.tshurst.tsscalederivative

tsfBm-h0.3.tshurst.tsscalederivative: tsfBm-h0.3.tshurst
	../tsscalederivative tsfBm-h0.3.tshurst > tsfBm-h0.3.tshurst.tsscalederivative

tsfBm-h0.5.tshurst.tsscalederivative: tsfBm-h0.5.tshurst
	../tsscalederivative tsfBm-h0.5.tshurst > tsfBm-h0.5.tshurst.tsscalederivative

tsfBm-h0.7.tshurst.tsscalederivative: tsfBm-h0.7.tshurst
	../tsscalederivative tsfBm-h0.7.tshurst > tsfBm-h0.7.tshurst.tsscalederivative

tsfBm-h0.9.tshurst.tsscalederivative: tsfBm-h0.9.tshurst
	../tsscalederivative tsfBm-h0.9.tshurst > tsfBm-h0.9.tshurst.tsscalederivative

tsfBm-h1.0.tshurst.tsscalederivative: tsfBm-h1.0.tshurst
	../tsscalederivative tsfBm-h1.0.tshurst > tsfBm-h1.0.tshurst.tsscalederivative

tsfBm-h0.0.tskurtosis: tsfBm-h0.0
	../tskurtosis tsfBm-h0.0 > tsfBm-h0.0.tskurtosis

tsfBm-h0.1.tskurtosis: tsfBm-h0.1
	../tskurtosis tsfBm-h0.1 > tsfBm-h0.1.tskurtosis

tsfBm-h0.3.tskurtosis: tsfBm-h0.3
	../tskurtosis tsfBm-h0.3 > tsfBm-h0.3.tskurtosis

tsfBm-h0.5.tskurtosis: tsfBm-h0.5
	../tskurtosis tsfBm-h0.5 > tsfBm-h0.5.tskurtosis

tsfBm-h0.7.tskurtosis: tsfBm-h0.7
	../tskurtosis tsfBm-h0.7 > tsfBm-h0.7.tskurtosis

tsfBm-h0.9.tskurtosis: tsfBm-h0.9
	../tskurtosis tsfBm-h0.9 > tsfBm-h0.9.tskurtosis

tsfBm-h1.0.tskurtosis: tsfBm-h1.0
	../tskurtosis tsfBm-h1.0 > tsfBm-h1.0.tskurtosis

tsfBm-h0.0.tskurtosiswindow: tsfBm-h0.0
	../tskurtosiswindow tsfBm-h0.0 > tsfBm-h0.0.tskurtosiswindow

tsfBm-h0.1.tskurtosiswindow: tsfBm-h0.1
	../tskurtosiswindow tsfBm-h0.1 > tsfBm-h0.1.tskurtosiswindow

tsfBm-h0.3.tskurtosiswindow: tsfBm-h0.3
	../tskurtosiswindow tsfBm-h0.3 > tsfBm-h0.3.tskurtosiswindow

tsfBm-h0.5.tskurtosiswindow: tsfBm-h0.5
	../tskurtosiswindow tsfBm-h0.5 > tsfBm-h0.5.tskurtosiswindow

tsfBm-h0.7.tskurtosiswindow: tsfBm-h0.7
	../tskurtosiswindow tsfBm-h0.7 > tsfBm-h0.7.tskurtosiswindow

tsfBm-h0.9.tskurtosiswindow: tsfBm-h0.9
	../tskurtosiswindow tsfBm-h0.9 > tsfBm-h0.9.tskurtosiswindow

tsfBm-h1.0.tskurtosiswindow: tsfBm-h1.0
	../tskurtosiswindow tsfBm-h1.0 > tsfBm-h1.0.tskurtosiswindow

clean:
	rm -f ${DATA}

realclean:

	rm -f ${DATA} ${GRAPHICS}
