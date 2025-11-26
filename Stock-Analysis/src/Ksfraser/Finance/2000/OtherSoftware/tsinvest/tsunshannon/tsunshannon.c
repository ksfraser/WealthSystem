/*

------------------------------------------------------------------------------

A license is hereby granted to reproduce this software source code and
to create executable versions from this source code for personal,
non-commercial use.  The copyright notice included with the software
must be maintained in all copies produced.

THIS PROGRAM IS PROVIDED "AS IS". THE AUTHOR PROVIDES NO WARRANTIES
WHATSOEVER, EXPRESSED OR IMPLIED, INCLUDING WARRANTIES OF
MERCHANTABILITY, TITLE, OR FITNESS FOR ANY PARTICULAR PURPOSE.  THE
AUTHOR DOES NOT WARRANT THAT USE OF THIS PROGRAM DOES NOT INFRINGE THE
INTELLECTUAL PROPERTY RIGHTS OF ANY THIRD PARTY IN ANY COUNTRY.

Copyright (c) 1994-2006, John Conover, All Rights Reserved.

Comments and/or bug reports should be addressed to:

    john@email.johncon.com (John Conover)

------------------------------------------------------------------------------

tsunshannon.c for calculating the Shannon information capacity, (and
optimal gain,) given the Shannon probability. See "Fractals, Chaos,
Power Laws," Manfred Schroeder, W. H. Freeman and Company, New York,
New York, 1991, ISBN 0-7167-2136-8, pp 128 and pp 151.

This program is the inverse of the tsshannon program, and solves the
equation:

    C(p) = 1 + p ln (p) + (1 - p) ln (1 - p)
                   2                2

where the optimal gain is calculated as 2^(C(p)), and f, the fraction
of capital wagered, is 2p - 1.

From Schroeder, pp 151:

    p = 0.55
    2^(C(0.55)) = 0.005, (probably a typo, meaning 1.005)
    by this program, 2^C(0.550000) = 2^0.007226 = 1.005021

$Revision: 0.0 $
$Date: 2006/01/18 19:36:00 $
$Id: tsunshannon.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsunshannon.c,v $
Revision 0.0  2006/01/18 19:36:00  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <unistd.h>

#ifdef __STDC__

#include <float.h>

#endif

static char rcsid[] = "$Id: tsunshannon.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Shannon information capacity calculation, given the Shannon probability\n",
    "Usage: tsunshannon [-v] p\n",
    "    -v, print the program's version information\n",
    "    p, Shannon probability\n"
};

#ifdef __STDC__

static const char *error_message[] = /* error message index array */

#else

static char *error_message[] = /* error message index array */

#endif

{
    "No error\n",
    "Error in program argument(s)\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */

#else

static void print_message (); /* print any error messages */

#endif

#ifdef __STDC__

int main (int argc, char *argv[])

#else

int main (argc, argv)
int argc;
char *argv[];

#endif

{
    int retval = EARGS, /* return value, assume not enough arguments */
        c; /* command line switch */

    double log_2 = (double) 0.0, /* log (2), for computations */
           probability, /* Shannon probabililty */
           capacity; /* Shannon information capacity */

    while ((c = getopt (argc, argv, "v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'v':

                (void) printf ("%s\n", rcsid); /* print the version */
                (void) printf ("%s\n", copyright); /* print the copyright */
                optind = argc; /* force argument error */

            default: /* illegal switch? */

                optind = argc; /* force argument error */
                break;
        }

    }

    if (argc - optind > 0) /* enough arguments? */
    {
        retval = NOERROR; /* assume no error */
        log_2 = (log ((double) 2.0)); /* log (2), for computations */
        probability = atof (argv[optind]) ; /* Shannon probability */
        capacity = (double) 1.0 + (probability * (log (probability) / log_2)) + (((double) 1.0 - probability) * (log ((double) 1.0 - probability) / log_2)); /* Shannon information capacity */
        (void) printf ("2^C(%f) = 2^%f = %f\n", probability, capacity, pow ((double) 2.0, capacity)); /* print the Shannon probability */
    }

    print_message (retval); /* print any error messages */
    exit (retval); /* exit with the error value */

#ifdef LINT

    return (0); /* for lint formalities */

#endif

}

/*

Print any error messages.

static void print_message (int retval);

I) Data structures:

    A) The help_message array is an array of character stings, one per
       line to be printed for help.

    B) The error_message array is an array of character strings, one
       line per error; the error_message array is implicitly addressed
       by the value integer retval, which specifies the error to be
       printed.

II) Function execution:

    A) Depending on the value of the integer retval:

        1) If retval is zero, print nothing-normal/successful program
           exit.

        2) Else if retval is unity, print help.

        3) Else retval is not zero or unity, it is an error code, print
           the corresponding error message.

Returns nothing.

*/

#ifdef __STDC__

static void print_message (int retval)

#else

static void print_message (retval)
int retval;

#endif

{
    size_t help_ctr; /* help_message line counter */

    switch (retval) /* which return value */
    {

        case 0: /* program ended without errors, print nothing */

            break;

        case 1: /* program ended with a request for help, or argument error, print help */

            for (help_ctr = 0; help_ctr < (sizeof (help_message) / sizeof (char *)); help_ctr ++) /* for each line of help */
            {
                (void) printf ("%s", help_message[help_ctr]); /* print the line */
            }

            break;

        default: /* an error that was not a request for help, print the error */

            (void) fprintf (stderr, "%s", error_message[retval]);
            break;

    }

}
