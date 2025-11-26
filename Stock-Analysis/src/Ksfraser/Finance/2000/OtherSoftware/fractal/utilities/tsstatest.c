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

tsstatest.c for making a statistical estimation of a time series. The
number of samples, given the maximum error estimate, and the
confidence level required is computed for both the standard deviation,
and the mean.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

Consider the following formula for determination of the Shannon
Probability, P, of an equity market time series, using the average and
root mean square of the normalized increments, avg, and, rms,
respectively:

        avg
        --- + 1
        rms
    P = -------
           2

which is useful in the determination of the optimal fraction of
capital, f, to invest in a stock, by:

    f = 2P - 1

The objective is to estimate how large the data set has to be for
determining P to a given accuracy, possibly using statistical
estimates of how many data points are required for a given confidence
level that the error is less than a specific value.

Suppose we have a confidence level, 0 < c < 1, that a value is within,
plus or minus, an error level, e. What this means, for example if c =
0.9, and e = 0.1, is that for 90% of the cases, the value will be
within the limits of +/- e, or, 5% of the time, on the average, it
will be less than -e, and 5% of the time more than +e.

The error level for avg, for a given confidence level, will
be:

    e    = k (rms / sqrt (n))
     avg

where n is the number of records in the data set, and k is a function
involving a normal distribution. The error level for rms, for the same
given confidence level, will be:

    e    = k (rms / sqrt (2n))
     rms

where k is identical in both cases. Also, the number of records
required for a given error level would be:

                             2
    n    = ((rms * k) / e   )
     avg                 rms

and
           1                   2
    n    = - ((rms * k) / e   )
     rms   2               rms

where k is the same as above.

For equity market indices, a typical value for rms would be 0.01, and
0.0003 for avg. This is probably typical for many stocks, however,
high gain stocks, in a "bull" market can have an rms of 0.04, and an
avg of 0.005.

The value of k can be determined from standard statistical tables:

    c       sigma level
    -------------------
    50          0.67
    68.27       1.00
    80          1.28
    90          1.64
    95          1.96
    95.45       2.00
    99          2.58
    99.73       3.00

where k = sigma level, for a confidence level, c. Note that for a
given confidence level:

    avg   avg +/- k (rms / sqrt (n))
    --- = ---------------------------
    rms   rms +/- k (rms / sqrt (2n))

          avg          1
          --- +/- k --------
          rms       sqrt (n)
        = ---------------------
                        1
           1 +/- k ------------
                   4 * sqrt (n)

Now, consider the specific example of avg and rms for an exponential
function. In this specific case, avg = rms, and avg / rms = 1. Since k
is assumed to be a function of a normally distributed random variable,
the error in the ratio avg / rms as a function of the data set size,
n, can be found by superposition, and adding the contributing error
values as a function of n for both rms and avg root mean square, or:

           2          2
    sqrt (1  + (1 / 4) ) = 1.030776406

or:

    avg   avg               1
    --- ~ --- +/- 1.03 * -------- * k
    rms   rms            sqrt (n)

          avg       1
        ~ --- +/ -------- * k
          rms    sqrt (n)

where k is determined from the table, above. In this specific case,
where avg = rms:

    avg   avg             1
    --- ~ --- * (1 +/- -------- * k)
    rms   rms          sqrt (n)

An interpretation of what this means is that, given a data set size,
n, and a confidence level of, say 90%, then 90% of the time, our
measurements of avg / rms, would fall within an error level of +/-
1.64 * 1 / sqrt (n), ie., 5% of the time it would be greater than the
error value, and 5% of the time, it would be lower than the error
value. In general, the concern is with the lower error value since
from the equation:

        avg
        --- + 1
        rms
    P = -------
           2

(at least in this specific case where avg = rms,) that a 90%
confidence level would imply that there is a 5% chance of the real
value avg / rms being zero is where:

        k
    -------- = 1
    sqrt (n)

or:

      1.64
    -------- = 1
    sqrt (n)

or n = 2.6896 ~ 3.

What this means is that, if we repeat the experiment of finding 3
records in a row that have rms = avg, with neither equal to zero, many
times, that we would loose money in 5% of the cases, making the
measured Shannon probability, P, unity, and the estimated Shannon
probability, 0.95, eg., we should consider the Shannon probability as
0.95 in this specific case-ie., it would be ill advised to invest all
of the capital in such a scenario, since, sooner or later, all of the
capital would be lost, (on average, by the 20'th game.)

This implies a simple methodology. Measure avg and rms, and compute
the Shannon probability. Decease that probability by a factor-ie., one
minus the confidence level, divided by two-that the wager could be a
loosing proposition, based on the estimates that avg could be zero,
(which is a function of the confidence level, and the number of
records in the data set.) This, conceivably, could provide a
quantitative estimate on the number of records required in a data set.

Note that if avg / rms is measured at 0.9, then:

      1.64
    -------- = 0.9
    sqrt (n)

for the same confidence level of 0.9, or

    n = 3.32

and:

    avg
    ---      n   p          p
    rms           measured
    --------------------------
    1.0     2.7   1.00    0.95
    0.9     3.3   0.95    0.90
    0.8     4.2   0.90    0.86
    0.7     5.5   0.85    0.81
    0.6     7.5   0.80    0.76
    0.5    10.8   0.75    0.71
    0.4    16.8   0.70    0.67
    0.3    29.9   0.65    0.62
    0.2    67.2   0.60    0.57
    0.1   268.9   0.55    0.52
    0.05 1075.8   0.53    0.50

for the same confidence level 0.9. What the table means is that if you
have a stock price time series of 67 records, then the minimum
measured Shannon probability must be at least 0.6-and the wagering
strategy should use the Shannon probability of 0.57-and the minimum
number of records used to measure avg and rms is 67. Additionally, a
stock time series with a Shannon probability of 0.53 should be
measured using not less than 1076 records, and no wager should be
made, unless the measurements involve substantially more than 1076
records. In general, the Shannon probability of almost all stock time
series fall, inclusively, in this range. 67 business days is,
approximately, 13.4 weeks, or little more than a calendar quarter.
1076 business days is slightly longer than four calendar years.

Note that in "Chaos and Order in the Capital Markets," Edgar
E. Peters, John Wiley & Sons, New York, New York, 1991, pp. 83,
referencing "Fractals," Jens Feder, Plenum Press, New York, New York,
1988, pp. 179, makes the claim that 2500 records is the minimum size
of the data set for using fractal analytical methodologies. Note that
a data set of this size would have, with an avg /rms of 0.5-which is
"typical" for a stock time series, a Shannon probability error level
that is approximately 1%, since it lies between 2 and 3 sigma, and c
would be approximately 0.99. This would seem to be consistent with the
empirical arguments of both Feder and Peters, although Peters implies
that less could be used if the system being analyzed is "chaotic" in
nature, and one "cycle" of the system's, apparently, "strange
attractor" is less than 2500 time units. This analysis would seem to
be consistent with the observations of these authors, provided that it
is a requirement that the measured Shannon probability be used to
calculate the optimum wager fraction.

What this analysis would tend to suggest is that, although Feder's and
Peter's arguments seem to be confirmed, that there may, also, be other
viable solutions for data sets, (or fragments thereof,) that are very
much smaller, provided that the measured Shannon probability of the
data set, or segment, is sufficiently large-for example, a stock that
has a time series fragment that has 5 out of 6 upward movements may
prove to be a viable investment opportunity at a measured Shannon
probability that is greater than 0.85, (5 / 6 = a Shannon probability
of 0.833 ~ 0.85,) if played at a Shannon probability as high as 0.8,
but no higher.

For example, using a Shannon probability, P, of 0.51 for the
tscoins(1) and tsfraction(1) programs, to provide an input fractal
time series for the tsstatest(1) program, and iterating, indicates
that for a standard deviation of 0.020000, with a confidence level of
0.960784 that the error did not exceed 0.020000, 3 samples would be
required.

Since the Shannon probability is calculated directly from the standard
deviation, (ie., rms = root mean square of the normalized increments,)
the maximum error can be calculated:

    0.5
    ---- = 0.980392157
    0.51

which means that a confidence level of 0.960784314 that the error
level in the standard deviation is less than 0.02 because standard
deviation = rms = 0.02 - 0.02 = 0, which would correspond to a Shannon
probability, P, of 0.5, and since half the errors outside the range of
0.02 would be negative, (and the other half positive,) the confidence
level required would be 1 - ((1 - 0.980392157) * 2).

What this means is that ((1 - 0.960784314) / 2) * 100 percent of the
time, the actual rms value will be sufficiently small to make P equal
to, or less than 0.5. This means that P must be decreased by
1.960784300 percent. The reasoning is that after many iterations, the
measured P would be too small by 1.90784300% of the time, on average,
making the measured P, over all of the iterations, 0.5.

This suggests a dynamic rule: do not wager unless the Shannon
probability, P, is strictly greater than 0.51, as measured on strictly
more than 3 time units. Interestingly, the Hurst Coefficient, as
measured by the tshurst(1) program, graph of a random walk, Brownian
motion, or fractional Brownian motion fractals indicates that there is
significant near term correlations for 4 or less time units. This
suggests a dynamic trading methodology for equities.

Similar reasoning would indicate that using a value of P = 0.6 for the
tscoins(1) and tsfraction(1) programs to provide input to the
tsstatest(1) program with a confidence level of 0.8, and an error of
0.12, (ie., 10% of the time the value of P would be less than 0.9 *
0.6 = 0.54, where 0.2 - 0.12 = 0.08, and 0.54 = (0.08 + 1) / 2) would
require a minimum of 3 records. The fraction of capital wagered
should be 2 * 0.54 - 1 = 0.08.

To review what has been presented so far, we really are not confident
that we know the value of the Shannon probability, P, until we have
sufficiently many records, n. One way of addressing this issue is to
wait to make a wager until we do. But this strategy has an
"opportunity cost," since, approximately 50% of the time, we would not
have made an investment when we should have. Note that since investing
in equities is not a 100% assured proposition, we only invest a
fraction of our capital, f, where f = 2P - 1. Since investing with a
data set size that is insufficient, ie., n is too small, lowers the
probability of the wins, the Shannon probability, P, will have to be
lowered to maintain the optimum wager fraction of the capital. We can
compute the value that the Shannon probability, P, must be lowered to
account for this.

The relationship between the Shannon probability, P, and the root mean
square of the normalized increments of a time series, rms, is:

        rms + 1
    P = -------
           2

Let the error, e, in rms created by an insufficient data set size be:

    e = rms - rms'

where 0 < rms' < rms. This means that although rms was measured it
could be as low as rms'. The confidence level that rms is not less
than rms' can be found by statistical estimate. The Shannon
probability, P', associated with rms' is:

         rms' + 1
    P' = --------
            2

P' is the Shannon probability if the root mean square value of the
normalized increments of the time series is rms'.

Since we want to alter the measured Shannon probability, P, to
accommodate the error created by a insufficient data set size, we
multiply P by the confidence level that the real value of P is not
less than P', or the confidence level, C, is:

        P'
    C = --
        P

The reasoning is that a value of C, say 0.9, means that the root mean
square value of the increments could be below the measured value, rms,
by an amount e for 5% of the time, and above rms by an amount e for 5%
of the time, so that:

    P' = CP

Substituting:

         rms' + 1
    CP = --------
            2

and solving for rms':

    rms' = 2CP - 1

or:

    e = rms - (2CP - 1) = rms - 2CP + 1

and substituting for rms, where rms = 2P - 1:

    e  = 2P - 1 - 2CP + 1 = 2P - 2CP = 2P(1 - C)

and substituting P' = CP:

    e = 2P - 2P' = 2(P - P')

C now has to be adjusted because we are only concerned with the values
of rms' that are less than rms, where:

    c = 1 - 2(1 - C) = 1 - 2 + 2C = 2C - 1

but since C = P' / P:

        2P'
    c = -- - 1
        P

or we have:

    e = 2(P - P')

and:

        2P'
    c = -- - 1
        P

which are the two general equations for use of this program for
trading equities.

Making a plot of these equations, of P' vs. n for various P presents
an interesting conjecture. The graph can be crudely approximated by a
single pole filter, with a pole at 0.033, ie., using the program
tscoins(1) with a -p 0.6 argument to simulate an equity value time
series, and the program tsinstant(1), with the -s option, to calculate
the instantaneous Shannon probability of the time series, followed by
the program tspole(1) with a -p 0.033 argument, would output,
approximately, P'. The P' tends to under wager for t < 7, and over
wager for t > 0.7. The approximation is simple, but
crude. Interestingly, using the program tshurst(1) on the same time
series indicates that there is good correlation for t < 5, and if this
temporal range is of interest, this simple solution may prove adequate
for non-rigorous requirements. Additionally, perhaps using the
tsmath(1) program, the output of the tspole(1) program could have 0.5
subtracted, multiplied by, say, 0.85, and then the 0.5 re-added to
extend the usefulness to approximately 100 business days. The accuracy
over this range is approximately +/- 0.01 out of 0.55. Naturally,
after very many days, for example, if P = 0.6, P' would still be
0.585, creating a long term error in rms of 0.2 - 0.17 = 0.03. Note
that the error created in the exponential growth of the capital would
be 0.04 - 0.0289. A substantial long term error. Alternately, perhaps
a recursive feed-forward technique could be implemented that would
allow the pole frequency to be selected for far term compatibility
with the statistical estimate, while at the same time approximating
the near term. Naturally, this, also, should not be considered a
substitute for statistical estimates, but using statistical estimates
would probably require a recursive procedure, and that is a formidable
proposition.

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
    F(x) - integral | ------ * e          dt + 0.5 = P(x)
                    | 2 * pi
                    0

                                                    = 0

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
$Date: 2006/01/18 19:36:00 $
$Id: tsstatest.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsstatest.c,v $
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

#else

#include <malloc.h>

#endif

#ifndef DBL_EPSILON

#define DBL_EPSILON 2.2204460492503131E-16

#endif

#ifndef DBL_MAX

#define DBL_MAX 1.7976931348623157E+308

#endif

static char rcsid[] = "$Id: tsstatest.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b," /* file record field separators */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Make a statistical estimation of a time series\n",
    "Usage: tsstatest [-c n] [-d] [-D j] [-e m] [-f o] [-i] [-p] [-P k]\n",
    "                 [-v] [filename]\n",
    "    -c n, confidence level, 0.0 < n < 1.0\n",
    "    -d, print number of samples required as a float\n",
    "    -D j, step size between 0.5 and P (requires -P)\n",
    "    -e m, maximum absolute error estimate, 0.0 < m\n",
    "    -f o, maximum fraction error estimate in standard deviation and mean\n",
    "    -i, input is the integration of a Gaussian variable\n",
    "    -p, only print number of samples required for mean and standard deviation\n",
    "    -P k, Shannon probability (requires -D)\n",
    "    -v, print the program's version information\n",
    "    filename, input filename\n"
};

#ifdef __STDC__

static const char *error_message[] = /* error message index array */

#else

static char *error_message[] = /* error message index array */

#endif

{
    "No error\n",
    "Error in program argument(s)\n",
    "Error opening file\n",
    "Error closing file\n",
    "Error allocating memory\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1
#define EOPEN 2
#define ECLOSE 3
#define EALLOC 4

#define NREPS (double) DBL_EPSILON * (double) 10000.0 /* epsilon accuracy for final iteration */

#ifndef PI /* make sure PI is defined */

#define PI 3.141592653589793 /* pi to 15 decimal places as per CRC handbook */

#endif

static int jmax = 20, /* default maximum number of iterate () iterations allowed */
           k = 5; /* default number of extrapolation points in romberg () integration */

static double eps = (double) 1e-12; /* default convergence error magnitude */

#ifdef __STDC__

static int standard (double con, int d, double e, double f, int i, int p, double offset, double l, int argc, char *argv[]); /* the "standard" way of statistical estimate */
static int records (double D, double P, int d); /* given P, and D, calculate n by statistical estimate */

static void print_message (int retval); /* print any error messages */
static int strtoken (char *string, char *parse_array, char **parse, char *delim);

typedef double (*FUNCTION) (double x); /* typedef of the function to be integrated */

static double function (double p); /* compute the integral from negative infinity to p */
static double derivative (double p); /* compute the derivative of the function at p */
static double romberg (FUNCTION func, double a, double b); /* function executing romberg's integration rule  */
static double normal (double x); /* the normal probability function */
static double iterate (FUNCTION func, double a, double b, int n); /* function executing trapazoid integration */
static void interpolate (double *xa, double *ya, int n, double x, double *y, double *dy); /* polynomial interpolation function */

#else

static int standard (); /* the "standard" way of statistical estimate */
static int records (); /* given P, and D, calculate n by statistical estimate */

static void print_message (); /* print any error messages */
static int strtoken ();

typedef double (*FUNCTION) (); /* typedef of the function to be integrated */

static double function (); /* compute the integral from negative infinity to p */
static double derivative (); /* compute the derivative of the function at p */
static double romberg (); /* function executing romberg's integration rule  */
static double normal (); /* the normal probability function */
static double iterate (); /* function executing trapazoid integration */
static void interpolate (); /* polynomial interpolation function */

#endif

#ifdef __STDC__

int main (int argc, char *argv[])

#else

int main (argc, argv)
int argc;
char *argv[];

#endif

{
    int retval = NOERROR, /* return value, assume no error */
        d = 0, /* print the number of samples required as a float, 0 = no, 1 = yes */
        i = 0, /* input is an integration of a normal distribution, 0 = no, 1 = yes */
        p = 0, /* print only the number of samples required for the mean and standard deviation */
        nflag = 0, /* given P, and D, calculate n flag, 2 = yes, otherwise no */
        c; /* command line switch */

    double con = (double) 0.99, /* confidence level */
           l = (double) 0.99, /* confidence level */
           offset = (double) 0.0, /* value to find standard deviation */
           e = (double) 0.0, /* maximum absolute error estimate */
           f = (double) 0.0, /* maximum fraction error estimate in standard deviation and mean */
           D = (double) 0.0, /* step size between 0.5 and P (requires -P) */
           P = (double) 0.0;/* Shannon probability (requires -D) */

    while ((c = getopt (argc, argv, "c:dD:e:f:ipP:v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'c': /* request for confidence level? */

                con = offset = l = atof (optarg); /* yes, set the confidence level */
                break;

            case 'd': /* request for print the number of samples required as a float? */

                d = 1; /* yes, set the print the number of samples required as a float flag */
                break;

            case 'D': /* request for step size between 0.5 and P (requires -P)? */

                D = atof (optarg); /* yes, set the steps size between 0.5 and P (requires -P) */
                nflag ++; /* increment the given P, and D, calculate n flag */
                break;

            case 'e': /* request for maximum absolute error estimate? */

                e = atof (optarg); /* yes, set the maximum absolute error estimate */
                break;

            case 'f': /* request for maximum fraction error estimate in standard deviation and mean? */

                f = atof (optarg); /* yes, set the maximum fraction error estimate in standard deviation and mean? */
                break;

            case 'i': /* request for input is an integration of a normal distribution? */

                i = 1; /* yes, set the input is an integration of a normal distribution flag */
                break;

            case 'p': /* request for print only the number of samples required for the mean and standard deviation */

                p = 1; /* yes, set the print only the number of samples required for the mean and standard deviation */
                break;

            case 'P': /* request Shannon probability (requires -D)? */

                P = atof (optarg); /* yes, set the Shannon probability (requires -D) */
                nflag ++; /* increment the given P, and D, calculate n flag */
                break;

            case 'v':

                (void) printf ("%s\n", rcsid); /* print the version */
                (void) printf ("%s\n", copyright); /* print the copyright */
                optind = argc; /* force argument error */
                retval = EARGS; /* assume not enough arguments */

            default: /* illegal switch? */

                optind = argc; /* force argument error */
                retval = EARGS; /* assume not enough arguments */
                break;
        }

    }

    if (retval == NOERROR) /* enough arguments? */
    {

        if (nflag == 2) /* given P, and D, calculate n flag equal to two? */
        {
            retval = records (D, P, d); /* yes, given P, and D, calculate n by statistical estimate */
        }

        else
        {
            retval = standard (con, d, e, f, i, p, offset, l, argc, argv); /* the "standard" way of statistical estimate */
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

/*

the "standard" way of statistical estimate

int standard (double con, int d, double e, double f, int i, int p, int argc, char *argv[]);

calculate the standard deviation offset, then compute the inverse
function of the normal distribution, open the input file, read the
records from the input file to calculate the average and the unbiased
root mean square of the time series, and, lastly, calculate the
maximum absolute error estimate for the mean

*/

#ifdef __STDC__

static int standard (double con, int d, double e, double f, int i, int p, double offset, double l, int argc, char *argv[])

#else

static int standard (con, d, e, f, i, p, offset, l, argc, argv)
double con;
int d;
double e;
double f;
int i;
int p;
double offset;
double l;
int argc;
char *argv[];

#endif

{
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         token_separators[] = TOKEN_SEPARATORS;

    int count = 0, /* input file record counter */
        retval = NOERROR, /* return value, assume no error */
        fields; /* number of fields in a record */

    double sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           sum = (double) 0.0, /* running value of cumulative sum */
           currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           nreps = NREPS, /* epsilon accuracy for final iteration */
           value = (double) DBL_MAX, /* return value from call to function (), less than eps will exit */
           avg, /* average of the time series */
           rms, /* root mean square of the time series */
           temp, /* temporary double storage */
           temp1; /* temporary double storage */

    FILE *infile = stdin; /* reference to input file */

    l = offset = (l + (double) 1.0) / (double) 2.0; /* calculate the standard deviation offset */

    while (fabs (value) > nreps) /* compute the inverse function of the normal distribution, while the return value from a call to function () is greater than eps */
    {
        offset = offset - (value = ((function (offset) - l) / derivative (offset))); /* iterate the newton loop */
    }

    retval = EOPEN; /* assume error opening file */

    if ((infile = argc <= optind ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the input file */
    {
        retval = NOERROR; /* assume no error */

        while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the records from the input file */
        {

            if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
            {

                if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                {

                    if (i == 0) /* input is an integration of a normal distribution flag set? */
                    {
                        currentvalue = atof (token[fields - 1]); /* no, save the value of the current sample in the time series */
                        sum = sum + currentvalue; /* add the value  of the current sample in the time series to the cumulative sum of the time series */
                        sumsquared = sumsquared + (currentvalue * currentvalue); /* add the square of the value of the current sample in the time series to the running value of cumulative sum of squares */
                        count ++; /* increment the count of records from the input file */
                    }

                    else
                    {
                        currentvalue = atof (token[fields - 1]); /* yes, save the value of the current sample in the time series */

                        if (count != 0) /* not first record? */
                        {
                            temp = ((currentvalue - lastvalue) / lastvalue); /* save the current sample value minus the last sample value */
                            sum = sum + temp; /* add the value  of the current sample in the time series to the cumulative sum of the time series */
                            sumsquared = sumsquared + (temp * temp); /* add the square of the value of the current sample in the time series to the running value of cumulative sum of squares */
                        }

                        lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

        }

        avg = sum / (double) count; /* calculate the average of the time series */
        rms = sqrt (sumsquared / ((double) (count - 1))); /* calculate the unbiased root mean square of the time series */

        if (f == (double) 0.0) /* request for maximum fraction error estimate in standard deviation and mean set? */
        {

            if (e == (double) 0.0) /* maximum absolute error estimate assigned? */
            {
                e = avg * (double) 0.01; /* no, set it to 1% of the average of the time series */
            }

            temp = ((rms * offset) / e); /* no, calculate the value for the mean */
            temp = temp * temp; /* and square it */

            if (p == 1) /* print only the number of samples required for the mean and standard deviation flag set? */
            {

                if (d == 0) /* print the number of samples required as a float flag set? */
                {
                    (void) printf ("%d\t%d\n", (int) floor (temp + (double) 1.0), (int) floor ((temp / (double) 2.0) + (double) 1.0)); /* no, print the value for the mean and standard deviation */
                }

                else
                {
                    (void) printf ("%f\t%f\n", temp, (temp / (double) 2.0)); /* yes, print the value for the mean and standard deviation */
                }

            }

            else
            {
                temp1 = ((rms * offset) / sqrt ((double) count));
                (void) printf ("For a mean of %f, with a confidence level of %f\n    that the error did not exceed %f, %d samples would be required.\n    (With %d samples, the estimated error is %f = %f percent.)\n", avg, con, e, (int) floor (temp + (double) 1.0), count, temp1, (temp1 / avg) * (double) 100.0); /* no, print the values for the mean */
                temp1 = ((rms * offset) / sqrt ((double) (count * 2)));
                (void) printf ("For a standard deviation of %f, with a confidence level of %f\n    that the error did not exceed %f, %d samples would be required.\n    (With %d samples, the estimated error is %f = %f percent.)\n", rms, con, e, (int) floor ((temp / (double) 2.0) + (double) 1.0), count, temp1, (temp1 / rms) * (double) 100.0); /* print the values for the mean */
            }

        }

        else
        {
            e = f * avg; /* calculate the maximum absolute error estimate for the mean */
            temp = ((rms * offset) / e); /* no, calculate the value for the mean */
            temp = temp * temp; /* and square it */

            if (p == 1) /* print only the number of samples required for the mean and standard deviation flag set? */
            {

                if (d == 0) /* print the number of samples required as a float flag set? */
                {
                    (void) printf ("%d\t", (int) floor (temp + (double) 1.0)); /* no, print the value for the mean */
                }

                else
                {
                    (void) printf ("%f\t", temp); /* yes, print the value for the mean */
                }

            }

            else
            {
                temp1 = ((rms * offset) / sqrt ((double) count));
                (void) printf ("For a mean of %f, with a confidence level of %f\n    that the error did not exceed %f, %d samples would be required.\n    (With %d samples, the estimated error is %f = %f percent.)\n", avg, con, e, (int) floor (temp + (double) 1.0), count, temp1, (temp1 / avg) * (double) 100.0); /* print the values for the mean */
            }

            e = f * rms; /* calculate the maximum absolute error estimate for the standard deviation */
            temp = ((rms * offset) / e); /* no, calculate the value for the mean */
            temp = temp * temp; /* and square it */

            if (p == 1) /* print only the number of samples required for the mean and standard deviation flag set? */
            {

                if (d == 0) /* print the number of samples required as a float flag set? */
                {
                    (void) printf ("%d\n", (int) floor ((temp / (double) 2.0) + (double) 1.0)); /* no, print the value for the standard deviation */
                }

                else
                {
                    (void) printf ("%f\n", (temp / (double) 2.0)); /* no, print the value for the standard deviation */
                }

            }

            else
            {
                temp1 = ((rms * offset) / sqrt ((double) (count * 2)));
                (void) printf ("For a standard deviation of %f, with a confidence level of %f\n    that the error did not exceed %f, %d samples would be required.\n    (With %d samples, the estimated error is %f = %f percent.)\n", rms, con, e, (int) floor ((temp / (double) 2.0) + (double) 1.0), count, temp1, (temp1 / rms) * (double) 100.0); /* print the values for the mean */
            }

        }

        if (argc > optind) /* using stdin as input? */
        {

            if (fclose (infile) == EOF) /* no, close the input file */
            {
                retval = ECLOSE; /* error closing file */
            }

        }

    }

    return (retval); /* return any errors */
}

/*

given P, and D, calculate n by statistical estimate

int records (double D, double P, int d);

*/

#ifdef __STDC__

static int records (double D, double P, int d)

#else

static int records (D, P, d)
double D;
double P;
int d;

#endif

{
    double Pprime = (double) 0.5, /* starting point of n calculations is when the Shannon probability = 0.5 */
           e, /* maximum absolute error estimate */
           rms, /* root mean square of normalized increments */
           c, /* confidence level */
           l = (double) 0.99, /* confidence level */
           offset = (double) 0.0, /* value to find standard deviation */
           nreps = NREPS, /* epsilon accuracy for final iteration */
           value = (double) DBL_MAX, /* return value from call to function (), less than eps will exit */
           temp; /* temporary double storage */

    rms = (double) 2.0 * P - (double) 1.0; /* caclulate the root mean square of normalized increments */

    while (Pprime < P - (double) 2.2204460492503131E-16) /* using a step size of D, calculate n for each step */
    {
        c = (((double) 2.0 * Pprime) / P) - (double) 1.0; /* caclulate the confidence level */
        offset = l = c; /* set the confidence level */

        l = offset = (l + (double) 1.0) / (double) 2.0; /* calculate the standard deviation offset */

        while (fabs (value) > nreps) /* compute the inverse function of the normal distribution, while the return value from a call to function () is greater than eps */
        {
            offset = offset - (value = ((function (offset) - l) / derivative (offset))); /* iterate the newton loop */
        }

        e = (double) 2.0 * (P - Pprime); /* calculate the maximum absolute error estimate */

        temp = ((rms * offset) / e); /* calculate the value for the mean */
        temp = temp * temp; /* and square it */

        if (d == 0) /* print the number of samples required as a float flag set? */
        {
            (void) printf ("%d\t%f\n", (int) floor (temp + (double) 1.0), Pprime); /* print the value for the mean */
        }

        else
        {
            (void) printf ("%f\t%f\n", temp, Pprime); /* print the value for the mean */
        }

        Pprime = Pprime + D; /* next Pprime */
    }

    return (0);
}


/*

parse a record based on sequential delimiters

int strtoken (char *string, char *parse_array, char **parse, char *delim);

parse a character array, string, into an array, parse_array, using
consecutive characters from delim as field delimiters, point the
character pointers, token, to the beginning of each field, return the
number of fields parsed

*/

#ifdef __STDC__

static int strtoken (char *string, char *parse_array, char **parse, char *delim)

#else

static int strtoken (string, parse_array, parse, delim)
char *string,
*parse_array,
**parse,
*delim;

#endif

{
    int tokens = 0;

    (void) strcpy (parse_array, string); /* copy the string */

    parse[tokens] = strtok (parse_array, delim); /* get the 1st field */

    while (parse[tokens] != 0) /* get the remaining fields */
    {
        parse[++ tokens] = strtok ((char *) 0, delim);
    }

    return (tokens); /* return the number of tokens parsed */
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
