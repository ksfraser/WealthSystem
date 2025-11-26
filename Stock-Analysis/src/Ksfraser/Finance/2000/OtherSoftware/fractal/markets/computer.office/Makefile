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
# Makefile for various files containing market and industry data
#
# $Revision: 0.0 $
# $Date: 2006/01/19 19:38:03 $
# $Id: Makefile,v 0.0 2006/01/19 19:38:03 john Exp $
# $Log: Makefile,v $
# Revision 0.0	2006/01/19 19:38:03  john
# Initial version
#
#
ECHO = /bin/echo -n
#
DATA = data \
       data.tsfraction \
       data.tsfraction.tslsq-p \
       data.tsfraction.tsnormal-p \
       data.tsfraction.tsnormal-p.gnuplot \
       data.tsfraction.tsrms \
       data.tsfraction.tsrms-p \
       data.tsfraction.tsavg \
       data.tsfraction.tsavg-p \
       data.tsfraction.abs \
       data.tsfraction.abs.tslsq-p \
       data.tsfraction.abs.tsnormal-p \
       data.tsfraction.abs.tsnormal-p.gnuplot \
       data.tsfraction.tsnormal-s30 \
       data.tsfraction.tsnormal-s30-f \
       data.tsfraction.tshurst-d \
       data.tsfraction.tshcalc-d \
       data.tsfraction.tshurst-d.tslsq-p \
       data.tsfraction.tshurst-d.tslsq-p.low \
       data.tsfraction.tshurst-d.tslsq-p.gnuplot \
       data.tsfraction.tshcalc-d.tslsq-p \
       data.tsfraction.tshcalc-d.tslsq-p.low \
       data.tsfraction.tshcalc-d.tslsq-p.gnuplot \
       tsunfairbrownian-f \
       tsunfairbrownian-f.tsfraction \
       tsunfairbrownian-f.tsfraction.tsnormal-p \
       tsunfairbrownian-f.tsfraction.tsnormal-s30 \
       tsunfairbrownian-f.tsfraction.tsnormal-s30-f \
       data.tsshannonmax \
       data.tsshannonmax-p \
       data.tsshannonmax-p.tsunfairbrownian-p \
       data.tslogreturns-p \
       data.tslogreturns-p.tsshannon \
       data.tslsq-e-p \
       data.tslsq-e-p.bits \
       data.tslsq-e-p.bitsq \
       data.tslsq-e-p.tsshannon \
       data.tsfraction.tsnormal-p.mean \
       data.tsfraction.tsnormal-p.stddev \
       data.tsfraction.abs.tsnormal-p.mean \
       data.tsfraction.abs.tsnormal-p.stddev \
       data.tsfraction.tslsq-p.constant \
       data.tsfraction.tslsq-p.slope \
       data.tsfraction.abs.tslsq-p.constant \
       data.tsfraction.abs.tslsq-p.slope \
       tsunfairbrownian-f.fraction.mean \
       tsunfairbrownian-f.fraction.stddev \
       data.tsfraction.tshurst-d.tslsq-p.hurstall \
       data.tsfraction.tshurst-d.tslsq-p.low.hurstlow \
       hurstlownear.gnuplot \
       hurstlowfar.gnuplot \
       data.tsfraction.tshcalc-d.tslsq-p.hcalcall \
       data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow \
       data.tslogreturns-p.tsshannon.probability \
       data.tslogreturns-p.tsshannon.probabilityq \
       data.tslogreturns-p.tsshannon.returns \
       data.tslogreturns-p.tsshannon.returnsq \
       data.tsfraction.tsrms-pq \
       data.tsshannonmax-p.max \
       data.tsfraction.pmaxnumerator \
       data.tsfraction.pmaxdenominator \
       data.tshurst \
       data.tshurst.tslsq-p \
       data.tshurst.tslsq-p.low \
       data.tshurst.tslsq-p.gnuplot \
       data.tshcalc \
       data.tshcalc.tslsq-p \
       data.tshcalc.tslsq-p.low \
       data.tshcalc.tslsq-p.gnuplot \
       data.tshurst.tslsq-p.hurstall \
       data.tshurst.tslsq-p.low.hurstlow \
       datahurstlownear.gnuplot \
       datahurstlowfar.gnuplot \
       data.tshcalc.tslsq-p.hcalcall \
       data.tshcalc.tslsq-p.low.hcalclow \
       data.tsfraction.tsnormal-s30.tsXsquared \
       chisquared \
       critical \
       tschangewager-f \
       data.tsfraction.tsderivative \
       data.tsfraction.tsderivative.tsnormal-s30 \
       data.tsfraction.tsderivative.tsnormal-s30-f \
       data.tsfraction.tsderivatives \
       data.tsfraction.tsderivatives.tsnormal-s30 \
       data.tsfraction.tsderivatives.tsnormal-s30-f \
       data.tshurst-f \
       data.tsfraction.tsdeterministic \
       data.tsfraction.tslsq-p.tsdlogistic \
       data.tsfraction.tslsq-p.tsdlogistic2 \
       data.tsinstant-r \
       data.tsinstant-s

GRAPHICS = data.eps \
	   data.tsfraction.eps \
	   data.tsfraction.abs.eps \
	   data.tsfraction.tsnormal-s30.eps \
	   data.tsfraction.tshurst-d.eps \
	   data.tsfraction.tshcalc-d.eps \
	   tsunfairbrownian-f.eps \
	   tsunfairbrownian-f.tsfraction.tsnormal-s30.eps \
	   data.tsshannonmax.eps \
	   data.tsshannonmax-p.tsunfairbrownian-p.eps \
	   hurstlownear.eps \
	   hurstlowfar.eps \
	   data.tshurst.eps \
	   data.tshcalc.eps \
	   datahurstlownear.eps \
	   datahurstlowfar.eps \
	   max.comparison.eps \
	   data.tsfraction.tsderivative.tsnormal-s30.eps \
	   data.tsfraction.2tsderivative.tsnormal-s30.eps \
	   data.tshurst-f.eps \
	   data.tsfraction.tsdeterministic.eps \
	   data.tsfraction.tslsq-p.tsdlogistic.eps \
	   data.tsfraction.tslsq-p.tsdlogistic2.eps \
	   data.tsstatest-f0.1-c0.9-i.tex \
	   data.tsinstant-r.eps \
	   data.tsinstant-s.eps

LATEX = parameters.tex \
	qparameters.tex

all: ${GRAPHICS} ${LATEX}

.PHONEY: ${GRAPHICS}

${GRAPHICS}: ${DATA}
	gnuplot gnuplot
	echo "{\\footnotesize\\begin{verbatim}" > data.tsstatest-f0.1-c0.9-i.tex; ../../utilities/tsstatest -f 0.1 -c 0.9 -i data >> data.tsstatest-f0.1-c0.9-i.tex; echo "\\end{verbatim}}" >> data.tsstatest-f0.1-c0.9-i.tex

data: NOTES
	sh NOTES

data.tsfraction: data
	../../utilities/tsfraction data > data.tsfraction

data.tsfraction.tslsq-p: data.tsfraction
	../../utilities/tslsq -p data.tsfraction > data.tsfraction.tslsq-p

data.tsfraction.tsnormal-p: data.tsfraction
	../../utilities/tsnormal -p data.tsfraction > data.tsfraction.tsnormal-p

data.tsfraction.tsnormal-p.gnuplot: data.tsfraction data.tsfraction.tslsq-p data.tsfraction.tsnormal-p data.tsfraction.tsrms data.tsfraction.tsrms-p data.tsfraction.tsavg
	${ECHO} "plot [t=:] \"data.tsfraction\" with lines, " "`sed 's/t/ * t with lines, /' data.tsfraction.tslsq-p`" "`sed 's/ .*/ with lines, /' data.tsfraction.tsnormal-p`" "\"data.tsfraction.tsrms\" with lines, \"data.tsfraction.tsavg\" with lines, `cat data.tsfraction.tsrms-p` with lines" > data.tsfraction.tsnormal-p.gnuplot

data.tsfraction.tsrms: data.tsfraction
	../../utilities/tsrms data.tsfraction > data.tsfraction.tsrms

data.tsfraction.tsrms-p: data.tsfraction
	../../utilities/tsrms -p data.tsfraction > data.tsfraction.tsrms-p

data.tsfraction.tsavg: data.tsfraction
	../../utilities/tsavg data.tsfraction > data.tsfraction.tsavg

data.tsfraction.tsavg-p: data.tsfraction
	../../utilities/tsavg -p data.tsfraction > data.tsfraction.tsavg-p

data.tsfraction.abs: data.tsfraction
	sed 's/^-//' data.tsfraction > data.tsfraction.abs

data.tsfraction.abs.tslsq-p: data.tsfraction.abs
	../../utilities/tslsq -p data.tsfraction.abs > data.tsfraction.abs.tslsq-p

data.tsfraction.abs.tsnormal-p: data.tsfraction.abs
	../../utilities/tsnormal -p data.tsfraction.abs > data.tsfraction.abs.tsnormal-p

data.tsfraction.abs.tsnormal-p.gnuplot: data.tsfraction.abs data.tsfraction.abs.tslsq-p data.tsfraction.abs.tsnormal-p data.tsfraction.tsrms data.tsfraction.tsrms-p data.tsfraction.tsavg
	${ECHO} "plot [t=:] \"data.tsfraction.abs\" with lines, " "`sed 's/t/ * t with lines, /' data.tsfraction.abs.tslsq-p` " "`sed 's/ .*/ with lines, /' data.tsfraction.abs.tsnormal-p` " "\"data.tsfraction.tsrms\" with lines, \"data.tsfraction.tsavg\" with lines, `cat data.tsfraction.tsrms-p` with lines" > data.tsfraction.abs.tsnormal-p.gnuplot

data.tsfraction.tsnormal-s30: data.tsfraction
	../../utilities/tsnormal -t -s 30 data.tsfraction > data.tsfraction.tsnormal-s30

data.tsfraction.tsnormal-s30-f: data.tsfraction
	../../utilities/tsnormal -t -s 30 -f data.tsfraction > data.tsfraction.tsnormal-s30-f

data.tsfraction.tshurst-d: data.tsfraction
	../../utilities/tshurst -d data.tsfraction > data.tsfraction.tshurst-d

data.tsfraction.tshurst-d.tslsq-p: data.tsfraction.tshurst-d
	../../utilities/tslsq -p data.tsfraction.tshurst-d > data.tsfraction.tshurst-d.tslsq-p

data.tsfraction.tshurst-d.tslsq-p.low: data.tsfraction.tshurst-d
	head -10 data.tsfraction.tshurst-d > data.tsfraction.tshurst-d.tslsq-p.low.tmp
	../../utilities/tslsq -p data.tsfraction.tshurst-d.tslsq-p.low.tmp > data.tsfraction.tshurst-d.tslsq-p.low
	rm -f data.tsfraction.tshurst-d.tslsq-p.low.tmp

data.tsfraction.tshurst-d.tslsq-p.gnuplot: data.tsfraction.tshurst-d.tslsq-p data.tsfraction.tshurst-d data.tsfraction.tshurst-d.tslsq-p.low
	sed 's/t/ * t with lines,/' data.tsfraction.tshurst-d.tslsq-p | sed 's/^/plot [t=:] "data.tsfraction.tshurst-d" with lines, /' > data.tsfraction.tshurst-d.tslsq-p.tmp
	sed 's/t/ * t with lines/' data.tsfraction.tshurst-d.tslsq-p.low > data.tsfraction.tshurst-d.tslsq-p.low.tmp
	${ECHO} "`cat 'data.tsfraction.tshurst-d.tslsq-p.tmp'`" "`cat 'data.tsfraction.tshurst-d.tslsq-p.low.tmp'`" > data.tsfraction.tshurst-d.tslsq-p.gnuplot
	rm -f data.tsfraction.tshurst-d.tslsq-p.tmp
	rm -f data.tsfraction.tshurst-d.tslsq-p.low.tmp

data.tsfraction.tshcalc-d: data.tsfraction
	../../utilities/tshcalc -d data.tsfraction > data.tsfraction.tshcalc-d

data.tsfraction.tshcalc-d.tslsq-p: data.tsfraction.tshcalc-d
	../../utilities/tslsq -p data.tsfraction.tshcalc-d > data.tsfraction.tshcalc-d.tslsq-p

data.tsfraction.tshcalc-d.tslsq-p.low: data.tsfraction.tshcalc-d
	head -3 data.tsfraction.tshcalc-d > data.tsfraction.tshcalc-d.tslsq-p.low.tmp
	../../utilities/tslsq -p data.tsfraction.tshcalc-d.tslsq-p.low.tmp > data.tsfraction.tshcalc-d.tslsq-p.low
	rm -f data.tsfraction.tshcalc-d.tslsq-p.low.tmp

data.tsfraction.tshcalc-d.tslsq-p.gnuplot: data.tsfraction.tshcalc-d.tslsq-p data.tsfraction.tshcalc-d data.tsfraction.tshcalc-d.tslsq-p.low
	sed 's/t/ * t with lines,/' data.tsfraction.tshcalc-d.tslsq-p | sed 's/^/plot [t=:] "data.tsfraction.tshcalc-d" with lines, /' > data.tsfraction.tshcalc-d.tslsq-p.tmp
	sed 's/t/ * t with lines/' data.tsfraction.tshcalc-d.tslsq-p.low > data.tsfraction.tshcalc-d.tslsq-p.low.tmp
	${ECHO} "`cat 'data.tsfraction.tshcalc-d.tslsq-p.tmp'`" "`cat 'data.tsfraction.tshcalc-d.tslsq-p.low.tmp'`" > data.tsfraction.tshcalc-d.tslsq-p.gnuplot
	rm -f data.tsfraction.tshcalc-d.tslsq-p.tmp
	rm -f data.tsfraction.tshcalc-d.tslsq-p.low.tmp

tsunfairbrownian-f: data.tsfraction.tsrms-p
	../../simulation/tsunfairbrownian -f `cat data.tsfraction.tsrms-p` data > tsunfairbrownian-f

tsunfairbrownian-f.tsfraction: tsunfairbrownian-f
	../../utilities/tsfraction tsunfairbrownian-f > tsunfairbrownian-f.tsfraction

tsunfairbrownian-f.tsfraction.tsnormal-p: tsunfairbrownian-f.tsfraction
	../../utilities/tsnormal -p tsunfairbrownian-f.tsfraction > tsunfairbrownian-f.tsfraction.tsnormal-p

tsunfairbrownian-f.tsfraction.tsnormal-s30: tsunfairbrownian-f.tsfraction
	../../utilities/tsnormal -t -s 30 tsunfairbrownian-f.tsfraction > tsunfairbrownian-f.tsfraction.tsnormal-s30

tsunfairbrownian-f.tsfraction.tsnormal-s30-f: tsunfairbrownian-f.tsfraction
	../../utilities/tsnormal -t -s 30 -f tsunfairbrownian-f.tsfraction > tsunfairbrownian-f.tsfraction.tsnormal-s30-f

data.tsshannonmax: data
	../../utilities/tsshannonmax data > data.tsshannonmax

data.tsshannonmax-p: data
	../../utilities/tsshannonmax -p data > data.tsshannonmax-p

data.tsshannonmax-p.tsunfairbrownian-p: data.tsshannonmax-p data
	../../simulation/tsunfairbrownian -p `sed 's/ .*//' data.tsshannonmax-p` data > data.tsshannonmax-p.tsunfairbrownian-p

data.tslogreturns-p: data
	../../utilities/tslogreturns -p data > data.tslogreturns-p

data.tslogreturns-p.tsshannon: data.tslogreturns-p
	../../utilities/tsshannon `sed "s/^.*2\^(//" data.tslogreturns-p | sed "s/t)//"` > data.tslogreturns-p.tsshannon

data.tslsq-e-p: data
	../../utilities/tslsq -e -p data > data.tslsq-e-p

data.tslsq-e-p.bits: data.tslsq-e-p
	sed 's/^.*2\^(.* + //' data.tslsq-e-p | sed 's/t)//' > data.tslsq-e-p.bits

data.tslsq-e-p.bitsq: data.tslsq-e-p.bits
	cat data.tslsq-e-p.bits | awk -f ../Master/3divide.awk > data.tslsq-e-p.bitsq

data.tslsq-e-p.tsshannon: data.tslsq-e-p
	../../utilities/tsshannon `sed 's/^.*2\^(.* + //' data.tslsq-e-p | sed 's/t)//'` > data.tslsq-e-p.tsshannon

data.tsfraction.tsnormal-p.mean: data.tsfraction.tsnormal-p
	sed 's/ .*//' data.tsfraction.tsnormal-p > data.tsfraction.tsnormal-p.mean

data.tsfraction.tsnormal-p.stddev: data.tsfraction.tsnormal-p
	sed 's/.* //' data.tsfraction.tsnormal-p > data.tsfraction.tsnormal-p.stddev

data.tsfraction.abs.tsnormal-p.mean: data.tsfraction.abs.tsnormal-p
	sed 's/ .*//' data.tsfraction.abs.tsnormal-p > data.tsfraction.abs.tsnormal-p.mean

data.tsfraction.abs.tsnormal-p.stddev: data.tsfraction.abs.tsnormal-p
	sed 's/^.* //' data.tsfraction.abs.tsnormal-p > data.tsfraction.abs.tsnormal-p.stddev

data.tsfraction.tslsq-p.constant: data.tsfraction.tslsq-p
	sed 's/ + .*//' data.tsfraction.tslsq-p > data.tsfraction.tslsq-p.constant

data.tsfraction.tslsq-p.slope: data.tsfraction.tslsq-p
	sed 's/^.* + //' data.tsfraction.tslsq-p | sed 's/t//' > data.tsfraction.tslsq-p.slope

data.tsfraction.abs.tslsq-p.constant: data.tsfraction.abs.tslsq-p
	sed 's/ + .*//' data.tsfraction.abs.tslsq-p > data.tsfraction.abs.tslsq-p.constant

data.tsfraction.abs.tslsq-p.slope: data.tsfraction.abs.tslsq-p
	sed 's/^.* + //' data.tsfraction.abs.tslsq-p | sed 's/t//' > data.tsfraction.abs.tslsq-p.slope

tsunfairbrownian-f.fraction.mean: tsunfairbrownian-f.tsfraction.tsnormal-p
	sed 's/ .*//' tsunfairbrownian-f.tsfraction.tsnormal-p > tsunfairbrownian-f.fraction.mean

tsunfairbrownian-f.fraction.stddev: tsunfairbrownian-f.tsfraction.tsnormal-p
	sed 's/.* //' tsunfairbrownian-f.tsfraction.tsnormal-p > tsunfairbrownian-f.fraction.stddev

data.tsfraction.tshurst-d.tslsq-p.hurstall: data.tsfraction.tshurst-d.tslsq-p
	sed 's/^.* + //' data.tsfraction.tshurst-d.tslsq-p | sed 's/t//' > data.tsfraction.tshurst-d.tslsq-p.hurstall

data.tsfraction.tshurst-d.tslsq-p.low.hurstlow: data.tsfraction.tshurst-d.tslsq-p.low
	sed 's/^.* + //' data.tsfraction.tshurst-d.tslsq-p.low | sed 's/t//' > data.tsfraction.tshurst-d.tslsq-p.low.hurstlow

hurstlownear.gnuplot: data.tsfraction.tshurst-d.tslsq-p.low.hurstlow
	${ECHO} "plot [t=0:12] `cat data.tsfraction.tshurst-d.tslsq-p.low.hurstlow` ** t with lines, erf (1 / sqrt (2 * t)) with lines, 1 / sqrt (t) with lines" > hurstlownear.gnuplot

hurstlowfar.gnuplot: data.tsfraction.tshurst-d.tslsq-p.low.hurstlow
	${ECHO} "plot [t=24:60] `cat data.tsfraction.tshurst-d.tslsq-p.low.hurstlow` ** t with lines, erf (1 / sqrt (2 * t)) with lines, 1 / sqrt (t) with lines" > hurstlowfar.gnuplot

data.tsfraction.tshcalc-d.tslsq-p.hcalcall: data.tsfraction.tshcalc-d.tslsq-p
	sed 's/^.* + //' data.tsfraction.tshcalc-d.tslsq-p | sed 's/t//' > data.tsfraction.tshcalc-d.tslsq-p.hcalcall

data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow: data.tsfraction.tshcalc-d.tslsq-p.low
	sed 's/^.* + //' data.tsfraction.tshcalc-d.tslsq-p.low | sed 's/t//' > data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow

data.tslogreturns-p.tsshannon.probability: data.tslogreturns-p.tsshannon
	sed 's/^C(//' data.tslogreturns-p.tsshannon | sed 's/).*//' > data.tslogreturns-p.tsshannon.probability

data.tslogreturns-p.tsshannon.probabilityq: data.tslogreturns-p.tsshannon.returnsq
	../../utilities/tsshannon `cat data.tslogreturns-p.tsshannon.returnsq` | sed 's/^C(//' | sed 's/).*//' > data.tslogreturns-p.tsshannon.probabilityq

data.tslogreturns-p.tsshannon.returns: data.tslogreturns-p.tsshannon
	sed 's/^.*= //' data.tslogreturns-p.tsshannon > data.tslogreturns-p.tsshannon.returns

data.tslogreturns-p.tsshannon.returnsq: data.tslogreturns-p.tsshannon.returns
	cat data.tslogreturns-p.tsshannon.returns | awk -f ../Master/3divide.awk > data.tslogreturns-p.tsshannon.returnsq

data.tsfraction.tsrms-pq: data.tsfraction.tsrms-p
	cat data.tsfraction.tsrms-p | awk -f ../Master/3divide.awk > data.tsfraction.tsrms-pq

data.tsshannonmax-p.max: data.tsshannonmax-p
	sed 's/ .*//' data.tsshannonmax-p > data.tsshannonmax-p.max

data.tsfraction.pmaxnumerator: data.tsfraction
	egrep ^- data.tsfraction | wc | sed 's/ *//' | sed 's/ .*//' > data.tsfraction.pmaxnumerator

data.tsfraction.pmaxdenominator: data.tsfraction
	wc data.tsfraction | sed 's/ *//' | sed 's/ .*//' > data.tsfraction.pmaxdenominator

data.tshurst: data
	../../utilities/tshurst	 data > data.tshurst

data.tshurst.tslsq-p: data.tshurst
	../../utilities/tslsq -p data.tshurst > data.tshurst.tslsq-p

data.tshurst.tslsq-p.low: data.tshurst
	head -10 data.tshurst > data.tshurst.tslsq-p.low.tmp
	../../utilities/tslsq -p data.tshurst.tslsq-p.low.tmp > data.tshurst.tslsq-p.low
	rm -f data.tshurst.tslsq-p.low.tmp

data.tshurst.tslsq-p.gnuplot: data.tshurst.tslsq-p data.tshurst data.tshurst.tslsq-p.low
	sed 's/t/ * t with lines,/' data.tshurst.tslsq-p | sed 's/^/plot [t=:] "data.tshurst" with lines, /' > data.tshurst.tslsq-p.tmp
	sed 's/t/ * t with lines/' data.tshurst.tslsq-p.low > data.tshurst.tslsq-p.low.tmp
	${ECHO} "`cat 'data.tshurst.tslsq-p.tmp'`" "`cat 'data.tshurst.tslsq-p.low.tmp'`" > data.tshurst.tslsq-p.gnuplot
	rm -f data.tshurst.tslsq-p.tmp
	rm -f data.tshurst.tslsq-p.low.tmp

data.tshcalc: data
	../../utilities/tshcalc	 data > data.tshcalc

data.tshcalc.tslsq-p: data.tshcalc
	../../utilities/tslsq -p data.tshcalc > data.tshcalc.tslsq-p

data.tshcalc.tslsq-p.low: data.tshcalc
	head -3 data.tshcalc > data.tshcalc.tslsq-p.low.tmp
	../../utilities/tslsq -p data.tshcalc.tslsq-p.low.tmp > data.tshcalc.tslsq-p.low
	rm -f data.tshcalc.tslsq-p.low.tmp

data.tshcalc.tslsq-p.gnuplot: data.tshcalc.tslsq-p data.tshcalc data.tshcalc.tslsq-p.low
	sed 's/t/ * t with lines,/' data.tshcalc.tslsq-p | sed 's/^/plot [t=:] "data.tshcalc" with lines, /' > data.tshcalc.tslsq-p.tmp
	sed 's/t/ * t with lines/' data.tshcalc.tslsq-p.low > data.tshcalc.tslsq-p.low.tmp
	${ECHO} "`cat 'data.tshcalc.tslsq-p.tmp'`" "`cat 'data.tshcalc.tslsq-p.low.tmp'`" > data.tshcalc.tslsq-p.gnuplot
	rm -f data.tshcalc.tslsq-p.tmp
	rm -f data.tshcalc.tslsq-p.low.tmp

data.tshurst.tslsq-p.hurstall: data.tshurst.tslsq-p
	sed 's/^.* + //' data.tshurst.tslsq-p | sed 's/t//' > data.tshurst.tslsq-p.hurstall

data.tshurst.tslsq-p.low.hurstlow: data.tshurst.tslsq-p.low
	sed 's/^.* + //' data.tshurst.tslsq-p.low | sed 's/t//' > data.tshurst.tslsq-p.low.hurstlow

datahurstlownear.gnuplot: data.tshurst.tslsq-p.low.hurstlow
	${ECHO} "plot [t=0:12] `cat data.tshurst.tslsq-p.low.hurstlow` ** t with lines, erf (1 / sqrt (2 * t)) with lines, 1 / sqrt (t) with lines" > datahurstlownear.gnuplot

datahurstlowfar.gnuplot: data.tshurst.tslsq-p.low.hurstlow
	${ECHO} "plot [t=24:60] `cat data.tshurst.tslsq-p.low.hurstlow` ** t with lines, erf (1 / sqrt (2 * t)) with lines, 1 / sqrt (t) with lines" > datahurstlowfar.gnuplot

data.tshcalc.tslsq-p.hcalcall: data.tshcalc.tslsq-p
	sed 's/^.* + //' data.tshcalc.tslsq-p | sed 's/t//' > data.tshcalc.tslsq-p.hcalcall

data.tshcalc.tslsq-p.low.hcalclow: data.tshcalc.tslsq-p.low
	sed 's/^.* + //' data.tshcalc.tslsq-p.low | sed 's/t//' > data.tshcalc.tslsq-p.low.hcalclow

data.tsfraction.tsnormal-s30.tsXsquared: data.tsfraction.tsnormal-s30-f data.tsfraction.tsnormal-s30
	../../utilities/tsXsquared data.tsfraction.tsnormal-s30-f data.tsfraction.tsnormal-s30 > data.tsfraction.tsnormal-s30.tsXsquared

chisquared: data.tsfraction.tsnormal-s30.tsXsquared
	sed 's/^chi-squared value = //' data.tsfraction.tsnormal-s30.tsXsquared | sed 's/, .*//' > chisquared

critical: data.tsfraction.tsnormal-s30.tsXsquared
	sed 's/^.*critical value = //' data.tsfraction.tsnormal-s30.tsXsquared | sed 's/, .*//' > critical

parameters.tex: data.tsfraction.tsnormal-p.mean data.tsfraction.tsnormal-p.stddev data.tsfraction.tsrms-p data.tsfraction.abs.tsnormal-p.mean data.tsfraction.abs.tsnormal-p.stddev data.tsfraction.tslsq-p.constant data.tsfraction.tslsq-p.slope data.tsfraction.abs.tslsq-p.constant data.tsfraction.abs.tslsq-p.slope data.tsfraction.tshurst-d.tslsq-p.hurstall data.tsfraction.tshurst-d.tslsq-p.low.hurstlow data.tsfraction.tshcalc-d.tslsq-p.hcalcall data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow data.tsshannonmax-p.max data.tslogreturns-p.tsshannon.returns data.tsfraction.pmaxnumerator data.tsfraction.pmaxdenominator tsunfairbrownian-f.fraction.mean tsunfairbrownian-f.fraction.stddev data.tslogreturns-p.tsshannon.probability data.tslsq-e-p.bits data.tshurst.tslsq-p.hurstall data.tshurst.tslsq-p.low.hurstlow data.tshcalc.tslsq-p.hcalcall data.tshcalc.tslsq-p.low.hcalclow chisquared critical
	cat data.tsfraction.tsnormal-p.mean data.tsfraction.tsnormal-p.stddev data.tsfraction.tsrms-p data.tsfraction.abs.tsnormal-p.mean data.tsfraction.abs.tsnormal-p.stddev data.tsfraction.tslsq-p.constant data.tsfraction.tslsq-p.slope data.tsfraction.abs.tslsq-p.constant data.tsfraction.abs.tslsq-p.slope data.tsfraction.tshurst-d.tslsq-p.hurstall data.tsfraction.tshurst-d.tslsq-p.low.hurstlow data.tsfraction.tshcalc-d.tslsq-p.hcalcall data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow data.tsshannonmax-p.max data.tslogreturns-p.tsshannon.returns data.tsfraction.pmaxnumerator data.tsfraction.pmaxdenominator tsunfairbrownian-f.fraction.mean tsunfairbrownian-f.fraction.stddev data.tslogreturns-p.tsshannon.probability data.tslsq-e-p.bits data.tshurst.tslsq-p.hurstall data.tshurst.tslsq-p.low.hurstlow data.tshcalc.tslsq-p.hcalcall data.tshcalc.tslsq-p.low.hcalclow chisquared critical | awk -f ../Master/maketex.awk > parameters.tex

qparameters.tex: data.tsfraction.tsnormal-p.mean data.tsfraction.tsnormal-p.stddev data.tsfraction.tsrms-pq data.tsfraction.abs.tsnormal-p.mean data.tsfraction.abs.tsnormal-p.stddev data.tsfraction.tslsq-p.constant data.tsfraction.tslsq-p.slope data.tsfraction.abs.tslsq-p.constant data.tsfraction.abs.tslsq-p.slope data.tsfraction.tshurst-d.tslsq-p.hurstall data.tsfraction.tshurst-d.tslsq-p.low.hurstlow data.tsfraction.tshcalc-d.tslsq-p.hcalcall data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow data.tsshannonmax-p.max data.tslogreturns-p.tsshannon.returnsq data.tsfraction.pmaxnumerator data.tsfraction.pmaxdenominator tsunfairbrownian-f.fraction.mean tsunfairbrownian-f.fraction.stddev data.tslogreturns-p.tsshannon.probabilityq data.tslsq-e-p.bitsq data.tshurst.tslsq-p.hurstall data.tshurst.tslsq-p.low.hurstlow data.tshcalc.tslsq-p.hcalcall data.tshcalc.tslsq-p.low.hcalclow  chisquared critical
	cat data.tsfraction.tsnormal-p.mean data.tsfraction.tsnormal-p.stddev data.tsfraction.tsrms-pq data.tsfraction.abs.tsnormal-p.mean data.tsfraction.abs.tsnormal-p.stddev data.tsfraction.tslsq-p.constant data.tsfraction.tslsq-p.slope data.tsfraction.abs.tslsq-p.constant data.tsfraction.abs.tslsq-p.slope data.tsfraction.tshurst-d.tslsq-p.hurstall data.tsfraction.tshurst-d.tslsq-p.low.hurstlow data.tsfraction.tshcalc-d.tslsq-p.hcalcall data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow data.tsshannonmax-p.max data.tslogreturns-p.tsshannon.returnsq data.tsfraction.pmaxnumerator data.tsfraction.pmaxdenominator tsunfairbrownian-f.fraction.mean tsunfairbrownian-f.fraction.stddev data.tslogreturns-p.tsshannon.probabilityq data.tslsq-e-p.bitsq data.tshurst.tslsq-p.hurstall data.tshurst.tslsq-p.low.hurstlow data.tshcalc.tslsq-p.hcalcall data.tshcalc.tslsq-p.low.hcalclow chisquared critical | awk -f ../Master/maketex.awk > qparameters.tex

tschangewager-f: data data.tsfraction.pmaxdenominator data.tsfraction.pmaxnumerator data.tsfraction.tsrms-p
	../../utilities/tschangewager -f `cat data.tsfraction.tsavg-p data.tsfraction.tsrms-p | awk '{if(l==0){a=$$0}if(l==1){printf("%f\n",(a/($$0*$$0)))}l++}'` data > tschangewager-f

data.tsfraction.tsderivative: data.tsfraction
	../../utilities/tsderivative data.tsfraction > data.tsfraction.tsderivative

data.tsfraction.tsderivative.tsnormal-s30: data.tsfraction.tsderivative
	../../utilities/tsnormal -t -s 30 data.tsfraction.tsderivative > data.tsfraction.tsderivative.tsnormal-s30

data.tsfraction.tsderivative.tsnormal-s30-f: data.tsfraction.tsderivative
	../../utilities/tsnormal -t -s 30 -f data.tsfraction.tsderivative > data.tsfraction.tsderivative.tsnormal-s30-f

data.tsfraction.tsderivatives: data.tsfraction.tsderivative
	../../utilities/tsderivative data.tsfraction.tsderivative > data.tsfraction.tsderivatives

data.tsfraction.tsderivatives.tsnormal-s30: data.tsfraction.tsderivatives
	../../utilities/tsnormal -t -s 30 data.tsfraction.tsderivatives > data.tsfraction.tsderivatives.tsnormal-s30

data.tsfraction.tsderivatives.tsnormal-s30-f: data.tsfraction.tsderivatives
	../../utilities/tsnormal -t -s 30 -f data.tsfraction.tsderivatives > data.tsfraction.tsderivatives.tsnormal-s30-f

data.tshurst-f: data
	../../utilities/tshurst -f data | cut -f1,2 > data.tshurst-f

data.tsfraction.tsdeterministic: data.tsfraction
	../../utilities/tsdeterministic data.tsfraction > data.tsfraction.tsdeterministic

data.tsfraction.tslsq-p.tsdlogistic: data.tsfraction.tslsq-p.constant data.tsfraction.tslsq-p.slope
	../../simulation/tsdlogistic -a `cat data.tsfraction.tslsq-p.constant | awk '{printf("%f\n",$$0+1.0)}'` -b `cat data.tsfraction.tslsq-p.slope | awk '{if ($$0 > 0.0) {printf("%f\n",0.0)} else {printf("%f\n",$$0)}}'` -s `head -1 data` `wc data | sed -e 's/^ *//' -e 's/ .*//'` > data.tsfraction.tslsq-p.tsdlogistic

data.tsfraction.tslsq-p.tsdlogistic2: data.tsfraction.tslsq-p.constant data.tsfraction.tslsq-p.slope
	../../simulation/tsdlogistic -a `cat data.tsfraction.tslsq-p.constant | awk '{printf("%f\n",$$0+1.0)}'` -b `cat data.tsfraction.tslsq-p.slope | awk '{if ($$0 > 0.0) {printf("%f\n",0.0)} else {printf("%f\n",$$0)}}'` -s `head -1 data` `wc data | sed -e 's/^ *//' -e 's/ .*//' | awk '{printf("%f\n",$$0*2.0)}'` > data.tsfraction.tslsq-p.tsdlogistic2

data.tsinstant-r: data
	../../utilities/tsinstant -r data > data.tsinstant-r

data.tsinstant-s: data
	../../utilities/tsinstant -s data > data.tsinstant-s

clean:
	rm -f ${DATA}

realclean:
	rm -f ${DATA} ${GRAPHICS} ${LATEX}
