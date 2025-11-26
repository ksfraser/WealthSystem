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
# Divide the number, on stdin, by 3.0, and print the output to stdout
#
# This script used is for converting data that is given in quarters to
# months, for comparitive purposes. It is only used by the
# strategy.tex, where the logarithmic returns are used to find the
# Shannon probability-dividing this number by 3 will convert from
# quarters to months, since:
#
# x = the power that 2 is raised to to produce the next quarter's
# cumulative returns from the current quarter's cumulative returns in
# the time series, or:
#
# R3    x
# -- = 2
# R1
#
# y = power that 2 is raised to to produce the next month's
# cumulative returns from the current month's cumulative returns in
# the time series, or:
#
#  y  y  y    x
# 2  2  2  - 2
#
#  3y    x
# 2   = 2
#
# or:
#
#     x
# y = -
#     3
#
# $Revision: 0.0 $
# $Date: 2006/01/08 23:46:39 $
# $Id: 3divide.awk,v 0.0 2006/01/08 23:46:39 john Exp $
# $Log: 3divide.awk,v $
# Revision 0.0  2006/01/08 23:46:39  john
# Initial version
#
#
{
    number = $0
    printf ("%f\n", number / 3.0)
}
