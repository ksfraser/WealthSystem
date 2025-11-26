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

tsbinomial.c, generate binomial distribution noise, with unfair bias,
and cumulative sum-generates a time series.  The idea is to produce a
1/f squared power spectrum distribution by running a cumulative sum on
a binomial distribution. The program accepts a an unfair bias and a
wager factor. See "Fractals," Jens Feder, Plenum Press, New York, New
York, 1988, ISBN 0-306-42851-2, pp. 154, as suggested by Hurst.

This program is a modification of the program tscoin. The wager
fraction is computed by first calculating the optimal wager fraction,
f = 2P - 1, where P is the Shannon probability, and f is the optimal
wager fraction, (which is the root mean square = standard deviation of
the normalized increments of the time series,) and then reducing this
value by the standard deviation of the binomial distribution, which is
the square root of the number of elements in the distribution, ie.,
the root mean square of the normalized increments of the cumulative
sum is the same as the standard deviation of the binomial
distribution.  See "Fractals," Jens Feder, Plenum Press, New York, New
York, 1988, ISBN 0-306-42851-2, pp. 155.

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
$Id: tsbinomial.c,v 0.0 2006/01/18 20:28:55 john Exp $
$Log: tsbinomial.c,v $
Revision 0.0  2006/01/18 20:28:55  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include <unistd.h>

#ifdef __STDC__

#include <float.h>

#endif

static char rcsid[] = "$Id: tsbinomial.c,v 0.0 2006/01/18 20:28:55 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "generate a binomial distribution time series\n",
    "Usage: tsbinomial [-f fraction] [-i value] [-n n] [-p probability]\n",
    "                  [-r] [-s] [-t] [-v] number\n",
    "    -f fraction,  fraction of reserves to be wagered, (0 <= fraction <= 1)\n",
    "    -i value, initial value of cash reserves\n",
    "    -n n, number of elements in the binomial distribution\n",
    "    -p probability, Shannon probability, (0.5 <= probability <= 1.0)\n",
    "    -r, do not normalize the standard deviation = fraction\n",
    "    -s, print the cumulative sum of the binomial distribution time series\n",
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
static double ran1 (int *idum);

#else

static void print_message (); /* print any error messages */
static double ran1 ();

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
        n, /* counter of number of records in time series */
        b = 10, /* number of elements in the binomial distribution */
        k, /* number of elements in the binomial distribution counter */
        count, /* count of elements in the binomial distribution that are greater than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p */
        idem = -1, /* random number initialize flag */
        r = 0, /* do not normalize the standard deviation vs the number of elements in the binomial distribution */
        s = 0, /* print cumulative sum of binomial distribution, 0 = no, 1 = yes */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double p = (double) 0.5, /* Shannon probability */
           f = (double) 0.0, /* wager, (0 <= wage <= 1) */
           i = (double) 1.0, /* initial value of cash reserves */
           sum = (double) 0.0, /* cumulative sum of binomial distribution time series */
           temp; /* temporary float storage */

    while ((c = getopt (argc, argv, "f:i:n:p:rstv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'f': /* request for fraction of reserves to be wagered */

                f = atof (optarg); /* yes, set the fraction of reserves to be wagered */
                break;

            case 'i': /* request for initial value of cash reserves? */

                i = atof (optarg); /* yes, set the initial value of cash reserves */
                break;

            case 'n': /* request for number of elements in the binomial distribution? */

                b = atoi (optarg); /* yes, set the number of elements in the binomial distribution */
                break;

            case 'p': /* request for Shannon probability? */

                p = atof (optarg); /* yes, set the Shannon probability */
                break;

            case 'r': /* request for do not normalize the standard deviation vs the number of elements in the binomial distribution */

                r = 1; /* yes, set the do not normalize the standard deviation vs the number of elements in the binomial distribution flag */
                break;

            case 's': /* request printing sum of binomial distribution? */

                s = 1; /* yes, set the print sum of binomial distribution flag */
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
        sum = i; /* initialize the cumulative sum of binomial distribution time series to the initial value of cash reserves */

        if (f == (double) 0.0) /* wager, (0 <= wage <= 1) still zero? */
        {
            f = ((double) 2.0 * p) - (double) 1.0; /* yes, set w to 2 * the Shannon probability - 1 */
        }

        if (r == 0) /* request for do not normalize the standard deviation vs the number of elements in the binomial distribution flag not set? */
        {
            f = f / sqrt ((double) b); /* yes, divide the wager by the square root of b, the number of elements in the binomial distribution-the standard deviation of the binomial distribution */
        }

        for (n = 0; n < number; n ++) /* for each record in the time series */
        {
            count = 0; /* reset the count of elements in the binomial distribution that are greater than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p */

            for (k = 0; k < b; k ++) /* for each element in the binomial distribution */
            {
                temp = ran1 (&idem); /* compute a random number */

                if (temp < p) /* random number less than probability? */
                {
                    count ++; /* increment the count of elements in the binomial distribution that are greater than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p */
                }

                else
                {
                    count --; /* decrement the count of elements in the binomial distribution that are greater than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p */
                }

            }

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%d\t", n); /* yes, print the sample's time */
            }

            if (s == 1) /* print cumulative sum of binomial distribution flag set? */
            {
                sum = sum + (sum * (double) count * f); /* add the count of elements in the binomial distribution that are greater than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p to the cumulative sum of binomial distribution time series */
                (void) printf ("%f\n", sum); /* yes, print the cumulative sum */
            }

            else
            {
                (void) printf ("%d\n", count); /* no, print the count of elements in the binomial distribution that are greager than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p */
            }

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

#define M1 259200
#define IA1 7141
#define IC1 54773
#define RM1 (1.0/M1)
#define M2 134456
#define IA2 8121
#define IC2 28411
#define RM2 (1.0/M2)
#define M3 243000
#define IA3 4561
#define IC3 51349

/*

Returns a uniform random deviate between 0.0 and 1.0. Set idum to any
negative value to initialize or reinitialize the sequence. See
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, page
210, referencing Knuth.

*/

#ifdef __STDC__

static double ran1 (int *idum)

#else

static double ran1 (idum)
int *idum;

#endif

{
    static int iff = 0;

    static long ix1,
                ix2,
                ix3;

    static double r[98];

    int j;

    double temp;

    if (*idum < 0 || iff == 0) /* initialize on first call even if idum is not negative */
    {
        iff = 1;
        ix1 = (IC1 - (*idum)) % M1; /* seed first routine */
        ix1 = (IA1 * ix1 + IC1) % M1;
        ix2 = ix1 % M2; /* use first to seed second routine */
        ix1 = (IA1 * ix1 +IC1) % M1;
        ix3 = ix1 % M3; /* use first to seed third routine */

        for (j = 1; j <= 97; j++) /* fill table with sequential uniform deviates generated by first two routines */
        {
            ix1 = (IA1 * ix1 + IC1) % M1;
            ix2 = (IA2 * ix2 + IC2) % M2;
            r[j] = (ix1 + ix2 * RM2) * RM1; /* low and high order pieces combined here */
        }

        *idum = 1;
    }

    ix1 = (IA1 * ix1 + IC1) % M1; /* except when initializing, this is the start-generate the next number for each sequence */
    ix2 = (IA2 * ix2 + IC2) % M2;
    ix3 = (IA3 * ix3 + IC3) % M3;
    j = 1 + ((97 * ix3)/M3); /* use the third sequence to get an integer between 1 and 97 */

    if (j > 97 || j < 1)
    {
        (void) fprintf (stderr, "RAN1: This can not happen.\n");
        exit (1);
    }

    temp = r[j]; /* return that table entry */
    r[j] = (ix1 + ix2 * RM2) * RM1; /* refill the table's entry */
    return (temp);
}

#ifdef TEST_RAN1

/*

Calculates PI statistically using volume of unit n-sphere.  Test
driver for ran1 (). See "Numerical Recipes: Example Book (C),"
William T. Vetterling, Saul A. Teukolsky, William H. Press, Brian
P. Flannery, Cambridge University Press, New York, 1988, ISBN
0-521-35746-2, page 82.

*/

#include <stdio.h>
#include <math.h>

#ifndef PI

#define PI 3.141592653589793 /* pi to 15 decimal places as per CRC handbook */

#endif

#ifdef __STDC__

static int twotoj (int j);
static double fnc (double x1, double x2, double x3, double x4);
static double ran1 (int *idum);

#else

static int twotoj ();
static double fnc ();
static double ran1 ();

#endif

#ifdef __STDC__

void main (void)

#else

void main ()

#endif

{
    int i,
        idum = -1,
        j,
        k,
        jpower;

    double x1,
           x2,
           x3,
           x4,
           iy[4],
           yprob[4];

    /* Calculates PI statistically using volume of unit n-sphere */

    for (i = 1; i <= 3; i ++)
    {
        iy[i] = (double) 0.0;
    }

    (void) printf ("\nvolume of unit n-sphere, n = 2, 3, 4\n");
    (void) printf ("points\t\tPI\t\t(4/3)*PI\t(1/2)*PI^2\n\n");

    for (j = 1; j <= 14; j ++)
    {

        for (k = twotoj (j - 1); k <= twotoj (j); k ++)
        {
            x1 = ran1 (&idum);
            x2 = ran1 (&idum);
            x3 = ran1 (&idum);
            x4 = ran1 (&idum);

            if (fnc (x1, x2, (double) 0.0, (double) 0.0) < (double) 1.0)
            {
                ++ iy[1];
            }

            if (fnc (x1, x2, x3, (double) 0.0) < (double) 1.0)
            {
                ++ iy[2];
            }

            if (fnc (x1, x2, x3, x4) < (double) 1.0)
            {
                ++ iy[3];
            }

        }

        jpower=twotoj (j);
        yprob[1] = (double) 4.0 * iy[1] / jpower;
        yprob[2] = (double) 8.0 * iy[2] / jpower;
        yprob[3] = (double) 16.0 * iy[3] / jpower;
        (void) printf ("%6d\t%12.6f\t%12.6f\t%12.6f\n", jpower, yprob[1], yprob[2], yprob[3]);
    }

    (void) printf ("\nactual\t%12.6f\t%12.6f\t%12.6f\n", (double) PI, 4.0 * (double) PI / (double) 3.0, (double) 0.5 * (double) PI * (double) PI);
}

#endif
