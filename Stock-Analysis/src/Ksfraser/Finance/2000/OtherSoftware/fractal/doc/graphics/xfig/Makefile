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
# Makefile for xfig generated files
#
# $Revision: 0.0 $
# $Date: 2006/01/19 19:38:03 $
# $Id: Makefile,v 0.0 2006/01/19 19:38:03 john Exp $
# $Log: Makefile,v $
# Revision 0.0	2006/01/19 19:38:03  john
# Initial version
#
#
GRAPHICS=market1.eps \
	 market2.eps

all: ${GRAPHICS}

market1.eps: market1.fig
	fig2dev -L eps -m 0.5 -p x market1.fig > market1.eps

market2.eps: market2.fig
	fig2dev -L eps -m 0.5 -p x market2.fig > market2.eps

clean:
	echo "Nothing to do for clean"

realclean:
	rm -f ${GRAPHICS}
