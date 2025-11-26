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

tsshannon.c for calculating the probability, given the Shannon
information capacity. See "Fractals, Chaos, Power Laws," Manfred
Schroeder, W. H. Freeman and Company, New York, New York, 1991, ISBN
0-7167-2136-8, pp 128 and pp 151. Uses Newton-Raphson method for an
iterative solution for the probability, p.

As a reference on Newton-Raphson Method of root finding, see
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, pp
270.

From Schroeder, pp 151:

    p = 0.55
    2^(C(0.55)) = 0.005, (probably a typo, meaning 1.005)
    by calculator, C(0.55) = 0.0072,
        (this program gives C(0.549912) = 0.0072)

For electronic components shipments:

    tslogreturns -p ../electronic.components.shipments/data gives:
        2^(0.012810t)
    therefore:
        C(p) = 0.012810
    and , tsshannon 0.012810 gives:
        C(0566532) = 0.012810
    therefore:
        2^(C(0566532)) = 1.0089
    and:
        2p - 1 = 0.1331 = 13.31% / month

Derivation, starting with Schroeder, pp 151:

    C(p) = 1 + p ln (p) + (1 - p) ln (1 - p)
                   2                2

    C(p) = 1 + p (ln (p) / ln (2)) + (1 - p) (ln (1 - p) / ln (2))

    C(p) = [1 / ln (2)] [ln (2) + p ln (p) + (1 - p) ln (1 - p)]

    C(p) = [1 / ln (2)] [ ln (2) + p ln (p) + ln (1 - p) - p ln (1 - p)]

    dC(p)
    ---- = [1 / ln (2)] [1 + ln (p) - (1 / (1 - p)) - {ln (1 - p) - (p / (1 - p))}]
    dp

         = [1 / ln (2)] [1 + ln (p) - (1 / (1 - p)) - ln (1 - p) + (p / (1 - p))]

         = [1 / ln (2)] [ln (p) - ln (1 - p) + (p / (1 - p)) - (1 / (1 - p))]

         = [1 / ln (2)] [1 + ln (p) - ln (1 - p) + ((p - 1) / (1 - p))]

         = [1 / ln (2)] [1 + ln (p) - ln (1 - p) - 1]

         = [1 / ln (2)] [ln (p) - ln (1 - p)]

$Revision: 0.0 $
$Date: 2006/01/18 19:36:00 $
$Id: tsshannon.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsshannon.c,v $
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

#ifndef DBL_EPSILON

#define DBL_EPSILON 2.2204460492503131E-16

#endif

#ifndef DBL_MAX

#define DBL_MAX 1.7976931348623157E+308

#endif

static char rcsid[] = "$Id: tsshannon.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#define EPS (double) DBL_EPSILON * (double) 100.0 /* epsilon accuracy for final iteration */
#define P_START (double) 0.75 /* since p must be between 0.5 and 1.0, start with initial iteration of mid way */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Shannon calculation for probability, given the information capacity\n",
    "Usage: tsshannon [-v] C(p)\n",
    "    -v, print the program's version information\n",
    "    C(p), Shannon information capacity\n"
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

static double log_2 = (double) 0.0, /* 1 / log (2), for computations */
       capacity; /* Shannon information capacity */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static double function (double p);
static double derivative (double p);

#else

static void print_message (); /* print any error messages */
static double function ();
static double derivative ();

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

    double eps = EPS, /* epsilon accuracy for final iteration */
           p = P_START, /* since p must be between 0.5 and 1.0, start with initial iteration of mid way */
           value = DBL_MAX; /* return value from call to function (), less than eps will exit */

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
        log_2 = (1 / log ((double) 2.0)); /* 1 / log (2), for computations */
        capacity = atof (argv[optind]) ; /* Shannon information capacity */

        while (fabs (value) > eps) /* while the return value from a call to function () is greater than eps */
        {
            p = p - (value = (function (p) / derivative (p))); /* iterate the newton loop */
        }

        (void) printf ("C(%f) = %f\n", p, capacity); /* print the Shannon information capacity probability */
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

/*

compute the value of the Shannon information capacity for a probability
of p

static double function (double p);

returns the value of the Shannon information capacity for a probability
of p, where:

C(p) = 1 + (p * log (p) / log (2)) + ((1 - p) * log (1 - p) / log (2))

*/

#ifdef __STDC__

static double function (double p)

#else

static double function (p)
double p;

#endif

{
    return ((1 + (log_2 * ((p * log (p)) + ((1 - p) * log (1 - p))))) - capacity);
}

/*

compute the value of the derivative of the Shannon information
capacity for a probability of p

static double derivative (double p);

returns the value of the derivative of the Shannon information
capacity for a probability of p, where:

(d C(p) / dp) = (1 / ln (2)) (log (p) - log (1 - p))


*/

#ifdef __STDC__

static double derivative (double p)

#else

static double derivative (p)
double p;

#endif

{
    return ((log_2 * (log (p) - log (1 - p))));
}
