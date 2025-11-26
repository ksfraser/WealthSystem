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
# Makefile for the documentation
#
# $Revision: 0.0 $
# $Date: 2006/01/20 04:43:16 $
# $Id: Makefile,v 0.0 2006/01/20 04:43:16 john Exp $
# $Log: Makefile,v $
# Revision 0.0	2006/01/20 04:43:16  john
# Initial version
#
#
SOURCES = fractal.tex \
	  title.tex \
	  preface.tex \
	  abstract.tex \
	  chap1.tex \
	  chap2.tex \
	  chap3.tex \
	  chap4.tex \
	  appa.tex \
	  appb.tex \
	  appc.tex \
	  appd.tex \
	  tables.tex \
	  fraction.tex \
	  hurst.tex \
	  fiscal.tex \
	  operations.tex \
	  simulation.tex \
	  maximum.tex \
	  companies.tex \
	  instant.tex \
	  logistic.tex \
	  verification.tex \
	  colophon.tex \
	  bibliography.bib \
	  newreport.sty

all: fractal.dvi TAGS

fractal.ps: fractal.dvi
	dvips -o fractal.ps fractal.dvi

fractal.pdf: fractal.ps
	ps2pdf fractal.ps fractal.pdf

fractal.dvi: ${SOURCES}
	touch fractal.idxs
	latex fractal
	texindex fractal.idx
	bibtex fractal
	latex fractal
	texindex fractal.idx
	bibtex fractal
	latex fractal
	texindex fractal.idx
	bibtex fractal
	latex fractal

check:
	lacheck fractal.tex

TAGS: ${SOURCES}
	etags ${SOURCES}

clean:
	rm -f \
	TAGS \
	fractal.aux \
	fractal.bbl \
	fractal.blg \
	fractal.idx \
	fractal.idxs \
	fractal.lof \
	fractal.log \
	fractal.lot \
	fractal.toc

realclean:
	rm -f \
	TAGS \
	fractal.aux \
	fractal.bbl \
	fractal.blg \
	fractal.dvi \
	fractal.idx \
	fractal.idxs \
	fractal.lof \
	fractal.log \
	fractal.lot \
	fractal.toc \
	fractal.dvi \
	fractal.ps
