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
# Makefile for the document "Notes on the Fractal Analysis of Various
# Market Segments in the North American Electronics Industry".
#
# $Revision: 0.0 $
# $Date: 2006/01/15 03:05:12 $
# $Id: Makefile,v 0.0 2006/01/15 03:05:12 john Exp $
# $Log: Makefile,v $
# Revision 0.0	2006/01/15 03:05:12  john
# Initial version
#
#
all:
	(cd simulation;make;cd ../)
	(cd utilities;make;cd ../)
	(cd simulation/test;make;cd ../../)
	(cd utilities/test;make;cd ../../)
	(cd markets;make;cd ../)
	(cd doc/graphics;make;cd ../../)
	(cd doc;make;cd ../)
clean:
	(cd simulation;make clean;cd ../)
	(cd utilities;make clean;cd ../)
	(cd simulation/test;make clean;cd ../../)
	(cd utilities/test;make clean;cd ../../)
	(cd markets;make clean;cd ../)
	(cd doc/graphics;make clean;cd ../../)
	(cd doc;make clean;cd ../)
realclean:
	(cd simulation;make realclean;cd ../)
	(cd utilities;make realclean;cd ../)
	(cd simulation/test;make realclean;cd ../../)
	(cd utilities/test;make realclean;cd ../../)
	(cd markets;make realclean;cd ../)
	(cd doc/graphics;make realclean;cd ../../)
	(cd doc;make realclean;cd ../)
