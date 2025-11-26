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
# "gnuplot plot".
#
# Note: the tests use 1500 samples, so, depending on machine resources,
# could take a very long time to execute.
#
# $Revision: 0.0 $
# $Date: 2006/01/07 02:07:51 $
# $Id: Makefile,v 0.0 2006/01/07 02:07:51 john Exp $
# $Log: Makefile,v $
# Revision 0.0  2006/01/07 02:07:51  john
# Initial version
#
#
DATA = tsbrownian \
       tsbrownian.tsnormal \
       tsbrownian.tsnormal-f \
       tsgaussian \
       tsgaussian.tsnormal \
       tsgaussian.tsnormal-f \
       tswhite \
       tswhite.tsnormal \
       tswhite.tsnormal-f \
       tslaplacian \
       tslaplacian.tsnormal \
       tslaplacian.tsnormal-f \
       tsfractional \
       tsfractional.tsnormal \
       tsfractional.tsnormal-f \
       tspink \
       tspink.tsnormal \
       tspink.tsnormal-f \
       tsblack \
       tsblack.tsnormal \
       tsblack.tsnormal-f \
       tscoin \
       tscoin.tsunfairbrownian \
       tsfBm-h0.0 \
       tsfBm-h0.0.tsnormal \
       tsfBm-h0.0.tsnormal-f \
       tsfBm-h0.1 \
       tsfBm-h0.1.tsnormal \
       tsfBm-h0.1.tsnormal-f \
       tsfBm-h0.3 \
       tsfBm-h0.3.tsnormal \
       tsfBm-h0.3.tsnormal-f \
       tsfBm-h0.5 \
       tsfBm-h0.5.tsnormal \
       tsfBm-h0.5.tsnormal-f \
       tsfBm-h0.7 \
       tsfBm-h0.7.tsnormal \
       tsfBm-h0.7.tsnormal-f \
       tsfBm-h0.9 \
       tsfBm-h0.9.tsnormal \
       tsfBm-h0.9.tsnormal-f \
       tsfBm-h1.0 \
       tsfBm-h1.0.tsnormal \
       tsfBm-h1.0.tsnormal-f \
       tscoins \
       tscoins.tsfraction \
       tscoins.tsfraction.tsnormal \
       tscoins.tsfraction.tsnormal-f \
       tslogistic \
       tsdlogistic \
       tsbinomial \
       tsbinomial.tsnormal \
       tsbinomial.tsnormal-f \
       tscoin.tsunfairbrownian.tsstockwager \
       tscoin.tsunfairbrownian.tsunfairfractional \
       tsintegers \
       tsmarket \
       tsmarket.tsshannonstock \
       tscauchy \
       tscauchy.tsnormal \
       tscauchy.tsnormal-f \
       tscoins.tslognormal \
       tscoins.tslognormal.tsnormal \
       tscoins.tslognormal.tsnormal-f

GRAPHICS = tsbrownian.tsnormal.eps \
	   tsgaussian.tsnormal.eps \
	   tswhite.tsnormal.eps \
	   tslaplacian.tsnormal.eps \
	   tsfractional.tsnormal.eps \
	   tspink.tsnormal.eps \
	   tsblack.tsnormal.eps \
	   tscoin.eps \
	   tscoin.tsunfairbrownian.eps \
	   tsfBm-h0.0.eps \
	   tsfBm-h0.1.eps \
	   tsfBm-h0.3.eps \
	   tsfBm-h0.5.eps \
	   tsfBm-h0.7.eps \
	   tsfBm-h0.9.eps \
	   tsfBm-h1.0.eps \
	   tscoins.eps \
	   tscoins.tsfraction.tsnormal.eps \
	   tslogistic.eps \
	   tsdlogistic.eps \
	   tsbinomial.tsnormal.eps \
	   tscoin.tsunfairbrownian.tsstockwager.eps \
	   tscoin.tsunfairbrownian.tsunfairfractional.eps \
	   tsintegers.eps \
	   tsmarket.eps \
	   tsmarket.tsshannonstock.eps \
	   tscoin.tsstock-n.tex \
	   tscauchy.tsnormal.eps \
	   tscoins.tslognormal.tsnormal.eps

all: ${GRAPHICS}

.PHONEY: ${GRAPHICS}

${GRAPHICS}: ${DATA}
	gnuplot gnuplot
	echo "{\\footnotesize\\begin{verbatim}" > tscoin.tsstock-n.tex; ../tsstock -n tscoin | head -1000 | tail -10 >> tscoin.tsstock-n.tex; echo "\\end{verbatim}}" >> tscoin.tsstock-n.tex

tsbrownian:
	../tsbrownian 1500 > tsbrownian

tsbrownian.tsnormal: tsbrownian
	../../utilities/tsnormal -t tsbrownian > tsbrownian.tsnormal

tsbrownian.tsnormal-f: tsbrownian
	../../utilities/tsnormal -t -f tsbrownian > tsbrownian.tsnormal-f

tsgaussian:
	../tsgaussian 1500 > tsgaussian

tsgaussian.tsnormal: tsgaussian
	../../utilities/tsnormal -t tsgaussian > tsgaussian.tsnormal

tsgaussian.tsnormal-f: tsgaussian
	../../utilities/tsnormal -t -f tsgaussian > tsgaussian.tsnormal-f

tswhite:
	../tswhite 1500 > tswhite

tswhite.tsnormal: tswhite
	../../utilities/tsnormal -t tswhite > tswhite.tsnormal

tswhite.tsnormal-f: tswhite
	../../utilities/tsnormal -t -f tswhite > tswhite.tsnormal-f

tslaplacian:
	../tslaplacian 1500 > tslaplacian

tslaplacian.tsnormal: tslaplacian
	../../utilities/tsnormal -t tslaplacian > tslaplacian.tsnormal

tslaplacian.tsnormal-f: tslaplacian
	../../utilities/tsnormal -t -f tslaplacian > tslaplacian.tsnormal-f

tsfractional:
	../tsfractional 1500 > tsfractional

tsfractional.tsnormal: tsfractional
	../../utilities/tsnormal -t tsfractional > tsfractional.tsnormal

tsfractional.tsnormal-f: tsfractional
	../../utilities/tsnormal -t -f tsfractional > tsfractional.tsnormal-f

tspink:
	../tspink 1500 > tspink

tspink.tsnormal: tspink
	../../utilities/tsnormal -t tspink > tspink.tsnormal

tspink.tsnormal-f: tspink
	../../utilities/tsnormal -t -f tspink > tspink.tsnormal-f

tsblack:
	../tsblack 1500 > tsblack

tsblack.tsnormal: tsblack
	../../utilities/tsnormal -t tsblack > tsblack.tsnormal

tsblack.tsnormal-f: tsblack
	../../utilities/tsnormal -t -f tsblack > tsblack.tsnormal-f

tscoin:
	../tscoin -p 0.6 1500 > tscoin

tscoin.tsunfairbrownian: tscoin
	../tsunfairbrownian -f 0.1 tscoin > tscoin.tsunfairbrownian

tsfBm-h0.0:
	../tsfBm -m 1500 -h 0.0 1500 > tsfBm-h0.0

tsfBm-h0.0.tsnormal: tsfBm-h0.0
	../../utilities/tsnormal -t tsfBm-h0.0 > tsfBm-h0.0.tsnormal

tsfBm-h0.0.tsnormal-f: tsfBm-h0.0
	../../utilities/tsnormal -t -f tsfBm-h0.0 > tsfBm-h0.0.tsnormal-f

tsfBm-h0.1:
	../tsfBm -m 1500 -h 0.1 1500 > tsfBm-h0.1

tsfBm-h0.1.tsnormal: tsfBm-h0.1
	../../utilities/tsnormal -t tsfBm-h0.1 > tsfBm-h0.1.tsnormal

tsfBm-h0.1.tsnormal-f: tsfBm-h0.1
	../../utilities/tsnormal -t -f tsfBm-h0.1 > tsfBm-h0.1.tsnormal-f

tsfBm-h0.3:
	../tsfBm -m 1500 -h 0.3 1500 > tsfBm-h0.3

tsfBm-h0.3.tsnormal: tsfBm-h0.3
	../../utilities/tsnormal -t tsfBm-h0.3 > tsfBm-h0.3.tsnormal

tsfBm-h0.3.tsnormal-f: tsfBm-h0.3
	../../utilities/tsnormal -t -f tsfBm-h0.3 > tsfBm-h0.3.tsnormal-f

tsfBm-h0.5:
	../tsfBm -m 1500 -h 0.5 1500 > tsfBm-h0.5

tsfBm-h0.5.tsnormal: tsfBm-h0.5
	../../utilities/tsnormal -t tsfBm-h0.5 > tsfBm-h0.5.tsnormal

tsfBm-h0.5.tsnormal-f: tsfBm-h0.5
	../../utilities/tsnormal -t -f tsfBm-h0.5 > tsfBm-h0.5.tsnormal-f

tsfBm-h0.7:
	../tsfBm -m 1500 -h 0.7 1500 > tsfBm-h0.7

tsfBm-h0.7.tsnormal: tsfBm-h0.7
	../../utilities/tsnormal -t tsfBm-h0.7 > tsfBm-h0.7.tsnormal

tsfBm-h0.7.tsnormal-f: tsfBm-h0.7
	../../utilities/tsnormal -t -f tsfBm-h0.7 > tsfBm-h0.7.tsnormal-f

tsfBm-h0.9:
	../tsfBm -m 1500 -h 0.9 1500 > tsfBm-h0.9

tsfBm-h0.9.tsnormal: tsfBm-h0.9
	../../utilities/tsnormal -t tsfBm-h0.9 > tsfBm-h0.9.tsnormal

tsfBm-h0.9.tsnormal-f: tsfBm-h0.9
	../../utilities/tsnormal -t -f tsfBm-h0.9 > tsfBm-h0.9.tsnormal-f

tsfBm-h1.0:
	../tsfBm -m 1500 -h 1.0 1500 > tsfBm-h1.0

tsfBm-h1.0.tsnormal: tsfBm-h1.0
	../../utilities/tsnormal -t tsfBm-h1.0 > tsfBm-h1.0.tsnormal

tsfBm-h1.0.tsnormal-f: tsfBm-h1.0
	../../utilities/tsnormal -t -f tsfBm-h1.0 > tsfBm-h1.0.tsnormal-f

tscoins:
	../tscoins -p 0.6 1500 > tscoins

tscoins.tsfraction: tscoins
	../../utilities/tsfraction tscoins > tscoins.tsfraction

tscoins.tsfraction.tsnormal: tscoins.tsfraction
	../../utilities/tsnormal tscoins.tsfraction > tscoins.tsfraction.tsnormal

tscoins.tsfraction.tsnormal-f: tscoins.tsfraction
	../../utilities/tsnormal -f tscoins.tsfraction > tscoins.tsfraction.tsnormal-f

tslogistic:
	../tslogistic 1 1 1 100 > tslogistic

tsdlogistic:
	../tsdlogistic -a 4 -b -1 1000 > tsdlogistic

tsbinomial:
	../tsbinomial 1500 > tsbinomial

tsbinomial.tsnormal: tsbinomial
	../../utilities/tsnormal -t tsbinomial > tsbinomial.tsnormal

tsbinomial.tsnormal-f: tsbinomial
	../../utilities/tsnormal -t -f tsbinomial > tsbinomial.tsnormal-f

tscoin.tsunfairbrownian.tsstockwager: tscoin.tsunfairbrownian
	../tsstockwager tscoin.tsunfairbrownian > tscoin.tsunfairbrownian.tsstockwager

tscoin.tsunfairbrownian.tsunfairfractional: tscoin.tsunfairbrownian
	../tsunfairfractional tscoin.tsunfairbrownian > tscoin.tsunfairbrownian.tsunfairfractional

tsintegers:
	../tsintegers 1500 > tsintegers

tsmarket:
	../tsmarket -p 0.6 -c 5 500 > tsmarket

tsmarket.tsshannonstock: tsmarket
	../tsshannonstock tsmarket > tsmarket.tsshannonstock

tscauchy:
	../tscauchy 1500 > tscauchy

tscauchy.tsnormal: tscauchy
	../../utilities/tsnormal -t tscauchy > tscauchy.tsnormal

tscauchy.tsnormal-f: tscauchy
	../../utilities/tsnormal -t -f tscauchy > tscauchy.tsnormal-f

tscoins.tslognormal: tscoins
	../tslognormal tscoins > tscoins.tslognormal

tscoins.tslognormal.tsnormal: tscoins.tslognormal
	../../utilities/tsnormal tscoins.tslognormal > tscoins.tslognormal.tsnormal

tscoins.tslognormal.tsnormal-f: tscoins.tslognormal
	../../utilities/tsnormal -f tscoins.tslognormal > tscoins.tslognormal.tsnormal-f

tscoins.tslognormal.tsnormal-f: tscoins.tslognormal

clean:
	rm -f ${DATA}

realclean:

	rm -f ${DATA} ${GRAPHICS}
