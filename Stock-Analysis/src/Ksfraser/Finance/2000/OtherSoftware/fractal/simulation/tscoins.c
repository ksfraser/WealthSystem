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

tscoins.c, fractional brownian noise generator, with unfair bias, and
cumulative sum-generates a time series.  The idea is to produce a 1/f
squared power spectrum distribution by running a cumulative sum on a
Gaussian power spectrum distribution. The program accepts an unfair
bias and a wager factor.  See "Fractals", Jens Feder, Plenum Press,
New York, New York, 1988, ISBN 0-306-42851-2, pp 172.

The name, tscoins, was chosen since pitching many coins, at once, and
counting the number of heads, many times, will approach a Gaussian
distribution, if the number of coins is large, and the number of times
is large. See "Fractals", Jens Feder, Plenum Press, New York, New
York, 1988, ISBN 0-306-42851-2, pp 154.

The discreet time formula is:

    x[t] = x[t - 1] + f * R * x[t - 1]

where f is the fraction of the capital to be wagered, and R is a
Gaussian function, with the mean offset appropriately to provide a
Shannon probability, P. For the logistic function, the discreet time
formula is:

    x[t] = x[t - 1] + f * R * x[t - 1] + n * x[t - 1]^2

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

The general outline of this program is:

    1) given the Shannon probability, compute the abscissa value that
    divides the area under the normal curve, into two sections, such
    that the area to the left of the value, divided by the total area
    under the normal curve is the Shannon probability-a Newton-Raphson
    iterated approach using Romberg integration to find the area is
    used for this

    2) for each record:

        a) compute a gaussian distributed random number

        b) add the computed abscissa value from 1) above to the
        gaussian distributed number

        c) multiply this number by the fraction of cumulative sum
        to be wagered

        d) multiply this number by the cumulative sum

        e) add this number to the cumulative sum

This program will require finding the value of the normal function,
given the standard deviation. The method used is to use
Romberg/trapezoid integration to numerically solve for the value.

This program will require finding the functional inverse of the normal,
ie., Gaussian, function. The method used is to use Romberg/trapezoid
integration to numerically solve the equation:

                    x                2
                    |   1        - t   / 2
    F(x) = integral | ------ * e          dt + 0.5
                    | 2 * pi
                    0

which has the derivative:

                          2
             1        - x   / 2
    f(x) = ------ * e
           2 * pi

Since F(x) is known, and it is desired to find x,

                    x                2
                    |   1        - t   / 2
    F(x) - integral | ------ * e          dt + 0.5 = P(x) = 0
                    | 2 * pi
                    0

and the Newton-Raphson method of finding roots would be:

                  P(x)
    P      = P  - ----
     n + 1    n   f(x)

As a reference on Newton-Raphson Method of root finding, see
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, pp
270.

As a reference on Romberg integration, see "Numerical Recipes in C:
The Art of Scientific Computing," William H. Press, Brian P. Flannery,
Saul A. Teukolsky, William T. Vetterling, Cambridge University Press,
New York, 1988, ISBN 0-521-35465-X, page 124.

As a reference on trapezoid iteration, see "Numerical Recipes in C:
The Art of Scientific Computing," William H. Press, Brian P. Flannery,
Saul A. Teukolsky, William T. Vetterling, Cambridge University Press,
New York, 1988, ISBN 0-521-35465-X, page 120.

As a reference on polynomial interpolation, see "Numerical Recipes in
C: The Art of Scientific Computing," William H. Press, Brian
P. Flannery, Saul A. Teukolsky, William T. Vetterling, Cambridge
University Press, New York, 1988, ISBN 0-521-35465-X, page 90.

$Revision: 0.0 $
$Date: 2006/01/18 20:28:55 $
$Id: tscoins.c,v 0.0 2006/01/18 20:28:55 john Exp $
$Log: tscoins.c,v $
Revision 0.0  2006/01/18 20:28:55  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <unistd.h>

#ifdef __STDC__

#include <float.h>

#else

#include <malloc.h>

#endif

#ifndef DBL_EPSILON

#define DBL_EPSILON 2.2204460492503131E-16

#endif

#ifndef DBL_MAX

#define DBL_MAX 1.7976931348623157E+308

#endif

static char rcsid[] = "$Id: tscoins.c,v 0.0 2006/01/18 20:28:55 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Generate an unfair coins toss time series\n",
    "Usage: tscoins [-b n] [-f fraction] [-i value] [-p probability] [-t] [-v]\n",
    "               number\n",
    "    -b n, logistic nonlinear term, x(t) = x(t - 1) * (m - n * x(t - 1))\n",
    "    -f fraction,  fraction of reserves to be wagered, (0 <= fraction <= 1)\n",
    "    -i value, initial value of cash reserves\n",
    "    -p probability, Shannon probability, (0.5 <= probability <= 1.0)\n",
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
    "Error in program argument(s)\n",
    "Error allocating memory\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1
#define EALLOC 2

#define NREPS (double) DBL_EPSILON * (double) 10.0 /* epsilon accuracy for final iteration */

#ifndef PI /* make sure PI is defined */

#define PI 3.141592653589793 /* pi to 15 decimal places as per CRC handbook */

#endif

static int jmax = 20, /* default maximum number of iterate () iterations allowed */
           k = 5; /* default number of extrapolation points in romberg () integration */

static double eps = (double) 1e-12; /* default convergence error magnitude */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static double ran1 (int *idum);
static double gasdev (int *idum);

typedef double (*FUNCTION) (double x); /* typedef of the function to be integrated */

static double function (double p); /* compute the integral from negative infinity to p */
static double derivative (double p); /* compute the derivative of the function at p */
static double romberg (FUNCTION func, double a, double b); /* function executing romberg's integration rule  */
static double normal (double x); /* the normal probability function */
static double iterate (FUNCTION func, double a, double b, int n); /* function executing trapazoid integration */
static void interpolate (double *xa, double *ya, int n, double x, double *y, double *dy); /* polynomial interpolation function */

#else

static void print_message (); /* print any error messages */
static double ran1 ();
static double gasdev ();

typedef double (*FUNCTION) (); /* typedef of the function to be integrated */

static double function (); /* compute the integral from negative infinity to p */
static double derivative (); /* compute the derivative of the function at p */
static double romberg (); /* function executing romberg's integration rule  */
static double normal (); /* the normal probability function */
static double iterate (); /* function executing trapazoid integration */
static void interpolate (); /* polynomial interpolation function */

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
        idem = -1, /* random number initialize flag */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double p = (double) 0.5, /* Shannon probability */
           f = (double) 0.0, /* wager, (0 <= wage <= 1) */
           i = (double) 1.0, /* initial value of cash reserves */
           b = (double) 0.0, /* value of logistic nonlinearity term */
           temp, /* temporary float storage */
           sum, /* cumulative sum of random numbers generated by ran1 () */
           nreps = NREPS, /* epsilon accuracy for final iteration */
           value = (double) DBL_MAX, /* return value from call to function (), less than eps will exit */
           offset = (double) 0.0; /* value to find standard deviation, null means use mean scaled by standard deviation */

    while ((c = getopt (argc, argv, "b:i:p:f:tv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'b': /* request for value of logistic nonlinearity term */

                b = atof (optarg); /* yes, set the value of logistic nonlinearity term */
                break;

            case 'f': /* request for fraction of reserves to be wagered */

                f = atof (optarg); /* yes, set the fraction of reserves to be wagered */
                break;

            case 'i': /* request for initial value of cash reserves? */

                i = atof (optarg); /* yes, set the initial value of cash reserves */
                break;

            case 'p': /* request for Shannon probability? */

                offset = p = atof (optarg); /* yes, set the Shannon probability */
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

        while (fabs (value) > nreps) /* compute the inverse function of the normal distribution, while the return value from a call to function () is greater than eps */
        {
            offset = offset - (value = ((function (offset) - p) / derivative (offset))); /* iterate the newton loop */
        }

        retval = NOERROR; /* assume no error */
        number = atoi (argv[optind]); /* number of records in time series */
        sum = i; /* initialize cumulative sum */

        if (f == (double) 0.0) /* wager, (0 <= wage <= 1) still zero? */
        {
            f = ((double) 2.0 * p) - (double) 1.0; /* yes, set w to 2 * the Shannon probability - 1 */
        }

        for (n = 0; n < number; n ++) /* for each record in the time series */
        {
            temp = gasdev (&idem); /* compute a gaussian distributed random number */
            temp = temp + offset; /* add the offset to the computed gaussian destributed random number */

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%d\t", n); /* yes, print the sample's time */
            }

            sum = sum + (sum * f * temp) - (b * sum * sum); /* the sum is the sum plus the sum times the wager fraction time the random variable */
            (void) printf ("%f\n", sum); /* print the record */
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

/*

Returns a normally distributed deviate with zero mean and unit
variance, using ran1 () as the source of uniform deviates. Set idum to
any negative value to initialize or reinitialize the sequence. See
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, page
217.

*/

#ifdef __STDC__

static double gasdev (int *idum)

#else

static double gasdev (idum)
int *idum;

#endif

{
    static int iset = 0;

    static double gset;

    double fac,
           r,
           v1,
           v2;

    if (iset == 0)
    {

        do /* no deviate */
        {
            v1 = 2.0 * ran1 (idum) - 1.0; /* get two uniform numbers in the square extending from -1 to +1 in each direction */
            v2 = 2.0 * ran1 (idum) - 1.0;
            r = v1 * v1 + v2 * v2; /* see if they are in the unit circle */
        }
        while (r >= 1.0); /* if not, try again */

        fac = sqrt (-2.0 * log (r) / r); /* make the Box-Muller transformation to get two normal deviates, return one, save the other for next call */
        gset = v1 * fac;
        iset = 1; /* set flag */
        return (v2 * fac);
    }

    else
    {
        iset = 0; /* extra deviat from last time, unset the flag an return it */
        return (gset);
    }

}

#ifdef __STDC__

static double function (double p)

#else

static double function (p)
double p;

#endif

{
    double s;
    s = romberg (normal, (double) 0.0, p); /* integrate the normal probablility function */
    return ((double) 0.5 + (s / (sqrt ((double) 2.0 * (double) PI)))); /* compute the normal probability function's value */
}

#ifdef __STDC__

static double derivative (double p)

#else

static double derivative (p)
double p;

#endif

{
    double s;
    s = normal (p);
    return ((s / (sqrt ((double) 2.0 * (double) PI)))); /* compute the normal probability function's value */
}

/*

the normal probability function, compute the exponential part of the
normal probability function, e^(-(x^2 / 2)).

returns the value of the exponential part of the function

*/

#ifdef __STDC__

static double normal (double x)

#else

static double normal (x)
double x;

#endif

{
    return (exp (-(pow (x, (double) 2.0) / ((double) 2.0))));
}

/*

romberg's integration rule, returns the integral of the function,
func, from a to b; the parameters eps can be set to the desired
fractional accuracy, jmax so that 2^(jmax - 1) is the maximum allowed
number of iterations of iterate (), and k the number of points in the
extrapolation, (k = 2 is simpson's rule). See "Numerical Recipes in C:
The Art of Scientific Computing," William H. Press, Brian P. Flannery,
Saul A. Teukolsky, William T. Vetterling, Cambridge University Press,
New York, 1988, ISBN 0-521-35465-X, page 124.

returns the value of the integration, exits on too many iterations, or
inadequate memory to allocate the successive trapezoidal
approximations and relative step-sizes

*/

#ifdef __STDC__

static double romberg (FUNCTION func, double a, double b)

#else

static double romberg (func, a, b)
FUNCTION func;
double a;
double b;

#endif

{
    int j; /* iterate () iteration counter */

    double ss, /* iterative value of integration of func */
           dss,
           *s, /* successive trapezoidal approximations */
           *h; /* successive trapezoidal approximation relative step sizes */

    if ((s = (double *) malloc ((size_t) (jmax + 2) * sizeof (double))) == (double *) 0) /* allocate space for successive trapezoidal approximations */
    {
        (void) fprintf (stderr, "Error allocating memory\n"); /* inadaquate memory, print the error and exit */
        exit (1);
    }

    if ((h = (double *) malloc ((size_t) (jmax + 2) * sizeof (double))) == (double *) 0) /* allocate space for successive trapezoidal approximation relative step sizes */
    {
        (void) fprintf (stderr, "Error allocating memory\n"); /* inadaquate memory, print the error and exit */
        free ((void *) s); /* free s */
        exit (1);
    }

    h[1] = (double) 1.0;

    for (j = 1; j <= jmax; j++)  /* limit iterations to jmax, for each iteration of iterate () */
    {
        s[j] = iterate (func, a, b, j);  /* execute iterate () to get the result of the integration iteration */

        if (j >= k)
        {
            interpolate (&h[j - k], &s[j - k], k, (double) 0.0, &ss, &dss);

            if (fabs (dss) < eps * fabs (ss))
            {
                free ((void *) h); /* free h */
                free ((void *) s); /* free s */
                return (ss); /* yes, the accuracy has been attained, return the value */
            }

        }

        s[j + 1] = s[j];
        h[j + 1] = (double) 0.25 *h[j];  /* important, factor is 1/4, even though step-size is decreased by 1/2-makes extrapolation a polynomial in h^2, not just a polynomal in h */
    }

    (void) fprintf (stderr, "\nMaximum number of iterations exceeded\n"); /* too many iterations, print the error and exit */
    free ((void *) h); /* free h */
    free ((void *) s); /* free s */
    exit (1);
    return ((double) 0.0); /* for formality */
}

/*

trapezoid iteration, compute the n'th stage of refinement of an
extended iterate rule; func is input as a pointer to the function to
be integrated between limits a and b, also input-when called with n =
1, the routine returns the crudest estimate of the integral-subsequent
calls with n = 2, 3 ... (in that sequential order) will improve the
accuracy of adding 2^(n - 2) additional interior points. See
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, page
120.

returns the value of the integration

*/

#ifdef __STDC__

static double iterate (FUNCTION func, double a, double b, int n)

#else

static double iterate (func, a, b, n)
FUNCTION func;
double a;
double b;
int n;

#endif

{
    static int it; /* number of points to be added on the NEXT call */

    static double s; /* refined value of integration for the iteration */

    int j; /* interior point counter */

    double x, /* argument of func */
           tnm,
           sum, /* running sum of func values */
           del; /* spacing of the points to be added */

    if (n == 1)  /* first iteration? */
    {
        it = 1;  /* yes, make a best guess */
        return (s = (double) 0.5 * (b - a) * (((*func) (a)) + ((*func) (b))));
    }

    else
    {
        tnm = (double) it; /* no, save the current number of points to be added on the NEXT call */
        del = (b - a) / tnm; /* compute the spacing of the points to be added */
        x = a + ((double) 0.5 * del); /* x's are offset by 1/2 the spacing of the points */

        for (sum = (double) 0.0, j = 1; j <= it; j++, x = x + del) /* for each interior point */
        {
            sum = sum + (*func) (x); /* sum the value's of the function */
        }

        it = it * 2; /* the next iteration will have twice as many interior points */
        s = (double) 0.5 *(s + (((b - a) * sum) / tnm)); /* compute the average value of the sum of the function's values, add it to the value of the previous iteration, and divide by 2 */
        return (s); /* replace s with its refined value */
    }

}

/*

polynomial interpolation, interpolates the y value for point x, given
the x and y data points in arrays xa, and ya, respectively and are of
type double which is defined as a double or float in interpol.h-there
are n many x and y points, and the result is returned via indirection
to y, with dy containing an error estimate. See "Numerical Recipes in
C: The Art of Scientific Computing," William H. Press, Brian
P. Flannery, Saul A. Teukolsky, William T. Vetterling, Cambridge
University Press, New York, 1988, ISBN 0-521-35465-X, page 90.

returns nothing, exits if inadequate memory to allocate the working
arrays, or if two or more x's have the same value, within roundoff

*/

#ifdef __STDC__

static void interpolate (double *xa, double *ya, int n, double x, double *y, double *dy)

#else

static void interpolate (xa, ya, n, x, y, dy)
double *xa;
double *ya;
int n;
double x;
double *y;
double *dy;

#endif

{
    int i,
        m,
        ns = 1;

    double den,
           dif,
           dift,
           ho,
           hp,
           w,
           *c,
           *d;

    dif = fabs (x - xa[1]);

    if ((c = (double *) malloc ((size_t) (n + 1) * sizeof (double))) == (double *) 0) /* allocate the c array */
    {
        (void) fprintf (stderr, "Error allocating memory\n"); /* inadaquate memory, print the error and exit */
        exit (1);
    }

    if ((d = (double *) malloc ((size_t) (n + 1) * sizeof (double))) == (double *) 0) /* allocate the d array */
    {
        (void) fprintf (stderr, "Error allocating memory\n"); /* inadaquate memory, print the error and exit */
        free ((void *) c);
        exit (1);
    }

    for (i = 1; i <= n; i++) /* find index, ns, of closest table entry, for each element in xa */
    {

        if ((dift = fabs (x - xa[i])) < dif)
        {
            ns = i;
            dif = dift;
        }

        c[i] = ya[i]; /* initialize c */
        d[i] = ya[i]; /* initialize d */
    }

    *y = ya[ns--]; /* initial approximation */

    for (m = 1; m < n; m++) /* for each column in the tableau of c's and d's */
    {

        /*

        after each column in the table is completed, decide which
        correction, c or d, is necessary to add to the accumulating
        value of y, i.e. which path to take through the tableau-
        forking up or down-in such a way to take the most "straight
        line" route through the table to its apex, updating ns
        accordingly to keep track of the current location; this route
        keeps the partial approximations centered (insofar as
        possible) on the target x-the last dy added is thus the error
        indication

        */

        for (i = 1; i <= n - m; i++)
        {
            ho = xa[i] - x;
            hp = xa[i + m] - x;
            w = c[i + 1] - d[i];

            if ((den = ho - hp) == (double) 0.0) /* two xa values identical? */
            {
                (void) fprintf (stderr, "Multiple identical x values\n");
                free ((void *) d);
                free ((void *) c);
                exit (1);
            }

            den = w / den;
            d[i] = hp * den;
            c[i] = ho * den;
        }

        *y = *y + (*dy = (2 * ns < (n - m) ? c[ns + 1] : d[ns--]));
    }

    free ((void *) d);
    free ((void *) c);
}
