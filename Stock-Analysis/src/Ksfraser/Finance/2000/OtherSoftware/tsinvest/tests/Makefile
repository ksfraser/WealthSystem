#
# ------------------------------------------------------------------------------
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
#    http://www.johncon.com/ntropics/
#    http://www.johncon.com/
#
#------------------------------------------------------------------------------
#
# $Revision: 1.7 $
# $Date: 2006/01/07 09:55:07 $
# $Id: Makefile,v 1.7 2006/01/07 09:55:07 john Exp $
# $Log: Makefile,v $
# Revision 1.7	2006/01/07 09:55:07  john
# Initial revision
#
#
FILES = stocks-d1 \
	stocks-d1-m0 \
	stocks-d1-u \
	stocks-d1-u-m0 \
	stocks-d2 \
	stocks-d2-m0 \
	stocks-d2-u \
	stocks-d2-u-m0 \
	stocks-d3 \
	stocks-d3-m0 \
	stocks-d3-u \
	stocks-d3-u-m0 \
	stocks-d4 \
	stocks-d4-m0 \
	stocks-d4-u \
	stocks-d4-u-m0 \
	stocks-d5 \
	stocks-d5-m0 \
	stocks-d5-u \
	stocks-d5-u-m0 \
	stocks-d6 \
	stocks-d6-m0 \
	stocks-d6-u \
	stocks-d6-u-m0 \
	stocks.data-d1 \
	stocks.data-d1-m0 \
	stocks.data-d1-u \
	stocks.data-d1-u-m0 \
	stocks.data-d2 \
	stocks.data-d2-m0 \
	stocks.data-d2-u \
	stocks.data-d2-u-m0 \
	stocks.data-d3 \
	stocks.data-d3-m0 \
	stocks.data-d3-u \
	stocks.data-d3-u-m0 \
	stocks.data-d4 \
	stocks.data-d4-m0 \
	stocks.data-d4-u \
	stocks.data-d4-u-m0 \
	stocks.data-d5 \
	stocks.data-d5-m0 \
	stocks.data-d5-u \
	stocks.data-d5-u-m0 \
	stocks.data-d6 \
	stocks.data-d6-m0 \
	stocks.data-d6-u \
	stocks.data-d6-u-m0 \
	non-volatile.data-d1 \
	non-volatile.data-d2 \
	non-volatile.data-d3 \
	non-volatile.data-d4 \
	non-volatile.data-d5 \
	non-volatile.equal.antipersistent.data-d1 \
	non-volatile.equal.antipersistent.data-d2 \
	non-volatile.equal.antipersistent.data-d3 \
	non-volatile.equal.antipersistent.data-d4 \
	non-volatile.equal.antipersistent.data-d5 \
	non-volatile.equal.data-d1 \
	non-volatile.equal.data-d2 \
	non-volatile.equal.data-d3 \
	non-volatile.equal.data-d4 \
	non-volatile.equal.data-d5 \
	non-volatile.equal.persistent.data-d1 \
	non-volatile.equal.persistent.data-d2 \
	non-volatile.equal.persistent.data-d3 \
	non-volatile.equal.persistent.data-d4 \
	non-volatile.equal.persistent.data-d5 \
	optimal.data-d1 \
	optimal.data-d2 \
	optimal.data-d3 \
	optimal.data-d4 \
	optimal.data-d5 \
	optimal.equal.antipersistent.data-d1 \
	optimal.equal.antipersistent.data-d2 \
	optimal.equal.antipersistent.data-d3 \
	optimal.equal.antipersistent.data-d4 \
	optimal.equal.antipersistent.data-d5 \
	optimal.equal.data-d1 \
	optimal.equal.data-d2 \
	optimal.equal.data-d3 \
	optimal.equal.data-d4 \
	optimal.equal.data-d5 \
	optimal.equal.persistent.data-d1 \
	optimal.equal.persistent.data-d2 \
	optimal.equal.persistent.data-d3 \
	optimal.equal.persistent.data-d4 \
	optimal.equal.persistent.data-d5 \
	volatile.data-d1 \
	volatile.data-d2 \
	volatile.data-d3 \
	volatile.data-d4 \
	volatile.data-d5 \
	volatile.equal.antipersistent.data-d1 \
	volatile.equal.antipersistent.data-d2 \
	volatile.equal.antipersistent.data-d3 \
	volatile.equal.antipersistent.data-d4 \
	volatile.equal.antipersistent.data-d5 \
	volatile.equal.data-d1 \
	volatile.equal.data-d2 \
	volatile.equal.data-d3 \
	volatile.equal.data-d4 \
	volatile.equal.data-d5 \
	volatile.equal.persistent.data-d1 \
	volatile.equal.persistent.data-d2 \
	volatile.equal.persistent.data-d3 \
	volatile.equal.persistent.data-d4 \
	volatile.equal.persistent.data-d5 \
	crash.data-d1 \
	crash.data-d2 \
	crash.data-d3 \
	crash.data-d4 \
	crash.data-d5 \
	crash-down.data \
	crash.data \
	losers.data-p-P-D0-j-d1 \
	losers.data-p-P-D0-j-d1-m0 \
	losers.data-p-P-D0-j-d2 \
	losers.data-p-P-D0-j-d2-m0 \
	losers.data-p-P-D0-j-d3 \
	losers.data-p-P-D0-j-d3-m0 \
	losers.data-p-P-D0-j-d4 \
	losers.data-p-P-D0-j-d4-m0 \
	losers.data-p-P-D0-j-d5 \
	losers.data-p-P-D0-j-d5-m0

all: ${FILES}

stocks-d1: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 1 ../stocks > stocks-d1

stocks-d1-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 1 -m0 ../stocks > stocks-d1-m0

stocks-d1-u: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 1 -u ../stocks > stocks-d1-u

stocks-d1-u-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 1 -u -m0 ../stocks > stocks-d1-u-m0

stocks-d2: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 2 ../stocks > stocks-d2

stocks-d2-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 2 -m0 ../stocks > stocks-d2-m0

stocks-d2-u: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 2 -u ../stocks > stocks-d2-u

stocks-d2-u-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 2 -u -m0 ../stocks > stocks-d2-u-m0

stocks-d3: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 3 ../stocks > stocks-d3

stocks-d3-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 3 -m0 ../stocks > stocks-d3-m0

stocks-d3-u: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 3 -u ../stocks > stocks-d3-u

stocks-d3-u-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 3 -u -m0 ../stocks > stocks-d3-u-m0

stocks-d4: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 4 ../stocks > stocks-d4

stocks-d4-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 4 -m0 ../stocks > stocks-d4-m0

stocks-d4-u: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 4 -u ../stocks > stocks-d4-u

stocks-d4-u-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 4 -u -m0 ../stocks > stocks-d4-u-m0

stocks-d5: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 5 ../stocks > stocks-d5

stocks-d5-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 5 -m0 ../stocks > stocks-d5-m0

stocks-d5-u: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 5 -u ../stocks > stocks-d5-u

stocks-d5-u-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 5 -u -m0 ../stocks > stocks-d5-u-m0

stocks-d6: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 6 ../stocks > stocks-d6

stocks-d6-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 6 -m0 ../stocks > stocks-d6-m0

stocks-d6-u: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 6 -u ../stocks > stocks-d6-u

stocks-d6-u-m0: ../stocks
	nice ../tsinvest -i -t -s -p -P -d 6 -u -m0 ../stocks > stocks-d6-u-m0

stocks.data-d1: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 1 > stocks.data-d1

stocks.data-d1-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 1 -m0 > stocks.data-d1-m0

stocks.data-d1-u: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 1 -u > stocks.data-d1-u

stocks.data-d1-u-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 1 -u -m0 > stocks.data-d1-u-m0

stocks.data-d2: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 2 > stocks.data-d2

stocks.data-d2-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 2 -m0 > stocks.data-d2-m0

stocks.data-d2-u: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 2 -u > stocks.data-d2-u

stocks.data-d2-u-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 2 -u -m0 > stocks.data-d2-u-m0

stocks.data-d3: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 3 > stocks.data-d3

stocks.data-d3-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 3 -m0 > stocks.data-d3-m0

stocks.data-d3-u: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 3 -u > stocks.data-d3-u

stocks.data-d3-u-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 3 -u -m0 > stocks.data-d3-u-m0

stocks.data-d4: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 4 > stocks.data-d4

stocks.data-d4-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 4 -m0 > stocks.data-d4-m0

stocks.data-d4-u: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 4 -u > stocks.data-d4-u

stocks.data-d4-u-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 4 -u -m0 > stocks.data-d4-u-m0

stocks.data-d5: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 5 > stocks.data-d5

stocks.data-d5-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 5 -m0 > stocks.data-d5-m0

stocks.data-d5-u: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 5 -u > stocks.data-d5-u

stocks.data-d5-u-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 5 -u -m0 > stocks.data-d5-u-m0

stocks.data-d6: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 6 > stocks.data-d6

stocks.data-d6-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 6 -m0 > stocks.data-d6-m0

stocks.data-d6-u: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 6 -u > stocks.data-d6-u

stocks.data-d6-u-m0: stocks.data
	nice ../tsinvestsim stocks.data 671 | ../tsinvest -i -t -s -d 6 -u -m0 > stocks.data-d6-u-m0

non-volatile.data-d1: non-volatile.data
	nice ../tsinvestsim non-volatile.data 100000 | ../tsinvest -d 1 -i -t -s > non-volatile.data-d1

non-volatile.data-d2: non-volatile.data
	nice ../tsinvestsim non-volatile.data 100000 | ../tsinvest -d 2 -i -t -s > non-volatile.data-d2

non-volatile.data-d3: non-volatile.data
	nice ../tsinvestsim non-volatile.data 100000 | ../tsinvest -d 3 -i -t -s > non-volatile.data-d3

non-volatile.data-d4: non-volatile.data
	nice ../tsinvestsim non-volatile.data 100000 | ../tsinvest -d 4 -i -t -s > non-volatile.data-d4

non-volatile.data-d5: non-volatile.data
	nice ../tsinvestsim non-volatile.data 100000 | ../tsinvest -d 5 -i -t -s > non-volatile.data-d5

non-volatile.equal.antipersistent.data-d1: non-volatile.equal.antipersistent.data
	nice ../tsinvestsim non-volatile.equal.antipersistent.data 100000 | ../tsinvest -d 1 -i -t -s > non-volatile.equal.antipersistent.data-d1

non-volatile.equal.antipersistent.data-d2: non-volatile.equal.antipersistent.data
	nice ../tsinvestsim non-volatile.equal.antipersistent.data 100000 | ../tsinvest -d 2 -i -t -s > non-volatile.equal.antipersistent.data-d2

non-volatile.equal.antipersistent.data-d3: non-volatile.equal.antipersistent.data
	nice ../tsinvestsim non-volatile.equal.antipersistent.data 100000 | ../tsinvest -d 3 -i -t -s > non-volatile.equal.antipersistent.data-d3

non-volatile.equal.antipersistent.data-d4: non-volatile.equal.antipersistent.data
	nice ../tsinvestsim non-volatile.equal.antipersistent.data 100000 | ../tsinvest -d 4 -i -t -s > non-volatile.equal.antipersistent.data-d4

non-volatile.equal.antipersistent.data-d5: non-volatile.equal.antipersistent.data
	nice ../tsinvestsim non-volatile.equal.antipersistent.data 100000 | ../tsinvest -d 5 -i -t -s > non-volatile.equal.antipersistent.data-d5

non-volatile.equal.data-d1: non-volatile.equal.data
	nice ../tsinvestsim non-volatile.equal.data 100000 | ../tsinvest -d 1 -i -t -s > non-volatile.equal.data-d1

non-volatile.equal.data-d2: non-volatile.equal.data
	nice ../tsinvestsim non-volatile.equal.data 100000 | ../tsinvest -d 2 -i -t -s > non-volatile.equal.data-d2

non-volatile.equal.data-d3: non-volatile.equal.data
	nice ../tsinvestsim non-volatile.equal.data 100000 | ../tsinvest -d 3 -i -t -s > non-volatile.equal.data-d3

non-volatile.equal.data-d4: non-volatile.equal.data
	nice ../tsinvestsim non-volatile.equal.data 100000 | ../tsinvest -d 4 -i -t -s > non-volatile.equal.data-d4

non-volatile.equal.data-d5: non-volatile.equal.data
	nice ../tsinvestsim non-volatile.equal.data 100000 | ../tsinvest -d 5 -i -t -s > non-volatile.equal.data-d5

non-volatile.equal.persistent.data-d1: non-volatile.equal.persistent.data
	nice ../tsinvestsim non-volatile.equal.persistent.data 100000 | ../tsinvest -d 1 -i -t -s > non-volatile.equal.persistent.data-d1

non-volatile.equal.persistent.data-d2: non-volatile.equal.persistent.data
	nice ../tsinvestsim non-volatile.equal.persistent.data 100000 | ../tsinvest -d 2 -i -t -s > non-volatile.equal.persistent.data-d2

non-volatile.equal.persistent.data-d3: non-volatile.equal.persistent.data
	nice ../tsinvestsim non-volatile.equal.persistent.data 100000 | ../tsinvest -d 3 -i -t -s > non-volatile.equal.persistent.data-d3

non-volatile.equal.persistent.data-d4: non-volatile.equal.persistent.data
	nice ../tsinvestsim non-volatile.equal.persistent.data 100000 | ../tsinvest -d 4 -i -t -s > non-volatile.equal.persistent.data-d4

non-volatile.equal.persistent.data-d5: non-volatile.equal.persistent.data
	nice ../tsinvestsim non-volatile.equal.persistent.data 100000 | ../tsinvest -d 5 -i -t -s > non-volatile.equal.persistent.data-d5

optimal.data-d1: optimal.data
	nice ../tsinvestsim optimal.data 100000 | ../tsinvest -d 1 -i -t -s > optimal.data-d1

optimal.data-d2: optimal.data
	nice ../tsinvestsim optimal.data 100000 | ../tsinvest -d 2 -i -t -s > optimal.data-d2

optimal.data-d3: optimal.data
	nice ../tsinvestsim optimal.data 100000 | ../tsinvest -d 3 -i -t -s > optimal.data-d3

optimal.data-d4: optimal.data
	nice ../tsinvestsim optimal.data 100000 | ../tsinvest -d 4 -i -t -s > optimal.data-d4

optimal.data-d5: optimal.data
	nice ../tsinvestsim optimal.data 100000 | ../tsinvest -d 5 -i -t -s > optimal.data-d5

optimal.equal.antipersistent.data-d1: optimal.equal.antipersistent.data
	nice ../tsinvestsim optimal.equal.antipersistent.data 100000 | ../tsinvest -d 1 -i -t -s > optimal.equal.antipersistent.data-d1

optimal.equal.antipersistent.data-d2: optimal.equal.antipersistent.data
	nice ../tsinvestsim optimal.equal.antipersistent.data 100000 | ../tsinvest -d 2 -i -t -s > optimal.equal.antipersistent.data-d2

optimal.equal.antipersistent.data-d3: optimal.equal.antipersistent.data
	nice ../tsinvestsim optimal.equal.antipersistent.data 100000 | ../tsinvest -d 3 -i -t -s > optimal.equal.antipersistent.data-d3

optimal.equal.antipersistent.data-d4: optimal.equal.antipersistent.data
	nice ../tsinvestsim optimal.equal.antipersistent.data 100000 | ../tsinvest -d 4 -i -t -s > optimal.equal.antipersistent.data-d4

optimal.equal.antipersistent.data-d5: optimal.equal.antipersistent.data
	nice ../tsinvestsim optimal.equal.antipersistent.data 100000 | ../tsinvest -d 5 -i -t -s > optimal.equal.antipersistent.data-d5

optimal.equal.data-d1: optimal.equal.data
	nice ../tsinvestsim optimal.equal.data 100000 | ../tsinvest -d 1 -i -t -s > optimal.equal.data-d1

optimal.equal.data-d2: optimal.equal.data
	nice ../tsinvestsim optimal.equal.data 100000 | ../tsinvest -d 2 -i -t -s > optimal.equal.data-d2

optimal.equal.data-d3: optimal.equal.data
	nice ../tsinvestsim optimal.equal.data 100000 | ../tsinvest -d 3 -i -t -s > optimal.equal.data-d3

optimal.equal.data-d4: optimal.equal.data
	nice ../tsinvestsim optimal.equal.data 100000 | ../tsinvest -d 4 -i -t -s > optimal.equal.data-d4

optimal.equal.data-d5: optimal.equal.data
	nice ../tsinvestsim optimal.equal.data 100000 | ../tsinvest -d 5 -i -t -s > optimal.equal.data-d5

optimal.equal.persistent.data-d1: optimal.equal.persistent.data
	nice ../tsinvestsim optimal.equal.persistent.data 100000 | ../tsinvest -d 1 -i -t -s > optimal.equal.persistent.data-d1

optimal.equal.persistent.data-d2: optimal.equal.persistent.data
	nice ../tsinvestsim optimal.equal.persistent.data 100000 | ../tsinvest -d 2 -i -t -s > optimal.equal.persistent.data-d2

optimal.equal.persistent.data-d3: optimal.equal.persistent.data
	nice ../tsinvestsim optimal.equal.persistent.data 100000 | ../tsinvest -d 3 -i -t -s > optimal.equal.persistent.data-d3

optimal.equal.persistent.data-d4: optimal.equal.persistent.data
	nice ../tsinvestsim optimal.equal.persistent.data 100000 | ../tsinvest -d 4 -i -t -s > optimal.equal.persistent.data-d4

optimal.equal.persistent.data-d5: optimal.equal.persistent.data
	nice ../tsinvestsim optimal.equal.persistent.data 100000 | ../tsinvest -d 5 -i -t -s > optimal.equal.persistent.data-d5

volatile.data-d1: volatile.data
	nice ../tsinvestsim volatile.data 100000 | ../tsinvest -d 1 -i -t -s > volatile.data-d1

volatile.data-d2: volatile.data
	nice ../tsinvestsim volatile.data 100000 | ../tsinvest -d 2 -i -t -s > volatile.data-d2

volatile.data-d3: volatile.data
	nice ../tsinvestsim volatile.data 100000 | ../tsinvest -d 3 -i -t -s > volatile.data-d3

volatile.data-d4: volatile.data
	nice ../tsinvestsim volatile.data 100000 | ../tsinvest -d 4 -i -t -s > volatile.data-d4

volatile.data-d5: volatile.data
	nice ../tsinvestsim volatile.data 100000 | ../tsinvest -d 5 -i -t -s > volatile.data-d5

volatile.equal.antipersistent.data-d1: volatile.equal.antipersistent.data
	nice ../tsinvestsim volatile.equal.antipersistent.data 100000 | ../tsinvest -d 1 -i -t -s > volatile.equal.antipersistent.data-d1

volatile.equal.antipersistent.data-d2: volatile.equal.antipersistent.data
	nice ../tsinvestsim volatile.equal.antipersistent.data 100000 | ../tsinvest -d 2 -i -t -s > volatile.equal.antipersistent.data-d2

volatile.equal.antipersistent.data-d3: volatile.equal.antipersistent.data
	nice ../tsinvestsim volatile.equal.antipersistent.data 100000 | ../tsinvest -d 3 -i -t -s > volatile.equal.antipersistent.data-d3

volatile.equal.antipersistent.data-d4: volatile.equal.antipersistent.data
	nice ../tsinvestsim volatile.equal.antipersistent.data 100000 | ../tsinvest -d 4 -i -t -s > volatile.equal.antipersistent.data-d4

volatile.equal.antipersistent.data-d5: volatile.equal.antipersistent.data
	nice ../tsinvestsim volatile.equal.antipersistent.data 100000 | ../tsinvest -d 5 -i -t -s > volatile.equal.antipersistent.data-d5

volatile.equal.data-d1: volatile.equal.data
	nice ../tsinvestsim volatile.equal.data 100000 | ../tsinvest -d 1 -i -t -s > volatile.equal.data-d1

volatile.equal.data-d2: volatile.equal.data
	nice ../tsinvestsim volatile.equal.data 100000 | ../tsinvest -d 2 -i -t -s > volatile.equal.data-d2

volatile.equal.data-d3: volatile.equal.data
	nice ../tsinvestsim volatile.equal.data 100000 | ../tsinvest -d 3 -i -t -s > volatile.equal.data-d3

volatile.equal.data-d4: volatile.equal.data
	nice ../tsinvestsim volatile.equal.data 100000 | ../tsinvest -d 4 -i -t -s > volatile.equal.data-d4

volatile.equal.data-d5: volatile.equal.data
	nice ../tsinvestsim volatile.equal.data 100000 | ../tsinvest -d 5 -i -t -s > volatile.equal.data-d5

volatile.equal.persistent.data-d1: volatile.equal.persistent.data
	nice ../tsinvestsim volatile.equal.persistent.data 100000 | ../tsinvest -d 1 -i -t -s > volatile.equal.persistent.data-d1

volatile.equal.persistent.data-d2: volatile.equal.persistent.data
	nice ../tsinvestsim volatile.equal.persistent.data 100000 | ../tsinvest -d 2 -i -t -s > volatile.equal.persistent.data-d2

volatile.equal.persistent.data-d3: volatile.equal.persistent.data
	nice ../tsinvestsim volatile.equal.persistent.data 100000 | ../tsinvest -d 3 -i -t -s > volatile.equal.persistent.data-d3

volatile.equal.persistent.data-d4: volatile.equal.persistent.data
	nice ../tsinvestsim volatile.equal.persistent.data 100000 | ../tsinvest -d 4 -i -t -s > volatile.equal.persistent.data-d4

volatile.equal.persistent.data-d5: volatile.equal.persistent.data
	nice ../tsinvestsim volatile.equal.persistent.data 100000 | ../tsinvest -d 5 -i -t -s > volatile.equal.persistent.data-d5

crash-down.data: crash-up.data
	nice ../tsinvestsim crash-up.data 2401 | sed 's/^/UP/' > crash.data
	egrep '^UP2400' crash.data | cut -f2,3 | sed 's/	/, p = 0.44, f = 0.02, h = 0.55, i = /' > crash-down.data

crash.data: crash-down.data
	nice ../tsinvestsim crash-down.data 853 | sed 's/^/DOWN/' >> crash.data

crash.data-d1: crash.data
	nice ../tsinvest -d 1 -i -t -s -c crash.data > crash.data-d1

crash.data-d2: crash.data
	nice ../tsinvest -d 2 -i -t -s -c crash.data > crash.data-d2

crash.data-d3: crash.data
	nice ../tsinvest -d 3 -i -t -s -c crash.data > crash.data-d3

crash.data-d4: crash.data
	nice ../tsinvest -d 4 -i -t -s -c crash.data > crash.data-d4

crash.data-d5: crash.data
	nice ../tsinvest -d 5 -i -t -s -c crash.data > crash.data-d5

losers.data-p-P-D0-j-d1: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 1 losers.data > losers.data-p-P-D0-j-d1

losers.data-p-P-D0-j-d1-m0: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 1 -m0 losers.data > losers.data-p-P-D0-j-d1-m0

losers.data-p-P-D0-j-d2: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 2 losers.data > losers.data-p-P-D0-j-d2

losers.data-p-P-D0-j-d2-m0: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 2 -m0 losers.data > losers.data-p-P-D0-j-d2-m0

losers.data-p-P-D0-j-d3: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 3 losers.data > losers.data-p-P-D0-j-d3

losers.data-p-P-D0-j-d3-m0: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 3 -m0 losers.data > losers.data-p-P-D0-j-d3-m0

losers.data-p-P-D0-j-d4: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 4 losers.data > losers.data-p-P-D0-j-d4

losers.data-p-P-D0-j-d4-m0: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 4 -m0 losers.data > losers.data-p-P-D0-j-d4-m0

losers.data-p-P-D0-j-d5: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 5 losers.data > losers.data-p-P-D0-j-d5

losers.data-p-P-D0-j-d5-m0: losers.data
	nice ../tsinvest -D0 -j -i -t -s -p -P -d 5 -m0 losers.data > losers.data-p-P-D0-j-d5-m0

realclean:
	rm -f ${FILES}
