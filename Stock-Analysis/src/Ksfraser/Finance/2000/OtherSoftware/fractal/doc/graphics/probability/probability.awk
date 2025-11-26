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
# Compute the Shannon probability by counting the number of records in
# the file "data.tsfraction" with negative signs, and the total number
# of records in the file "data.tsfraction", dividing these two numbers,
# and subtracting the quotient from 1
#
# Input must be successive records that contain:
#
#     0) the count of records with negative signes in data.tsfraction, from
#        the file "data.tsfraction.pmaxnumerator"
#     1) the count of records in data.tsfraction, from the file
#        "data.tsfraction.pmaxdenominator"
#
# $Revision: 0.0 $
# $Date: 2006/01/08 23:53:45 $
# $Id: probability.awk,v 0.0 2006/01/08 23:53:45 john Exp $
# $Log: probability.awk,v $
# Revision 0.0  2006/01/08 23:53:45  john
# Initial version
#
#
{

    if (linectr == 0)
    {
        pmaxnumerator = $0
    }

    if (linectr == 1)
    {
        pmaxdenominator = $0
        pmax = (pmaxdenominator - pmaxnumerator) / pmaxdenominator
        printf ("%f\n", pmax)
    }

    linectr++
}
