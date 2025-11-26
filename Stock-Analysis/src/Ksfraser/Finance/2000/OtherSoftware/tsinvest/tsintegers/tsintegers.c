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

tsintegers.c, integers function generator-generates a time series.

Note: these programs use the following functions from other
references:

    ran1, which returns a uniform random deviate between 0.0 and
    1.0. See "Numerical Recipes in C: The Art of Scientific
    Computing," William H. Press, Brian P. Flannery, Saul
    A. Teukolsky, William T. Vetterling, Cambridge University Press,
    New York, 1988, ISBN 0-521-35465-X, page 210, referencing Knuth.

    gasdev, which returns a normally distributed deviate with zero
    mean and unit variance, using ran1 () as the source of uniform
    deviates. See "Numerical Recipes in C: The Art of Scientific
    Computing," William H. Press, Brian P. Flannery, Saul
    A. Teukolsky, William T. Vetterling, Cambridge University Press,
    New York, 1988, ISBN 0-521-35465-X, page 217.

    gammln, which returns the log of the results of the gamma
    function.  See "Numerical Recipes in C: The Art of Scientific
    Computing," William H. Press, Brian P. Flannery, Saul
    A. Teukolsky, William T. Vetterling, Cambridge University Press,
    New York, 1988, ISBN 0-521-35465-X, page 168.

$Revision: 0.0 $
$Date: 2006/01/18 20:28:55 $
$Id: tsintegers.c,v 0.0 2006/01/18 20:28:55 john Exp $
$Log: tsintegers.c,v $
Revision 0.0  2006/01/18 20:28:55  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>

static char rcsid[] = "$Id: tsintegers.c,v 0.0 2006/01/18 20:28:55 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Generate integer time series made of integers\n",
    "Usage: tsintegers [-s value] [-t] [-v] number\n",
    "    -s value, first value in the time series\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -v, print the program's version information\n",
    "    number, the number of samples in the time series\n"
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

int main (int argc,char *argv[])

#else

int main (argc,argv)
int argc;
char *argv[];

#endif

{
    int number, /* number of records in time series */
        retval = EARGS, /* return value, assume not enough arguments */
        i, /* counter of number of records in time series */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        lastvalue = 0, /* last value in time series */
        c; /* command line switch */

    while ((c = getopt (argc, argv, "s:tv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 's': /* request for a start value? */

                lastvalue = atoi (optarg); /* yes, save the start value */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

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
        number = atoi (argv[optind]); /* number of records in time series */

        for (i = 0; i < number; i ++) /* for each record in the time series */
        {

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%d\t", i); /* yes, print the sample's time */
            }

            (void) printf ("%d\n", lastvalue); /* print the record */
            lastvalue = (lastvalue + 1); /* next value */
        }

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
