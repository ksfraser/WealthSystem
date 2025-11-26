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

tsunfairfractional.c, unfair returns of a time series. The idea is to
produce the returns of a time series which is weighted unfairly, by a
Shannon probability, p. The input time series is presumed to have a
Gaussian distribution. The main function of this program is regression
scenario verification-given an empirical time series, a Shannon
probability, and a "wager" fraction, (which were probably derived from
the program tsshannon,) speculative market pro forma performance can
be analyzed.

Note: Conceptually, this program is used to ``weight'' the returns of
a time series with a Gaussian distribution, ie., produce a fractional
Brownian motion time series, as opposed to a Brownian
distribution. The program should be regarded as experimental, and used
with caution.

The general outline of this program is:

    1) scan the input file, finding the incremental differences in the
    time series data, to remove the integration process of the time
    series, which is assumed have the characteristics of fractional
    Brownian motion

    2) compute the mean and standard deviation of the incremental
    differences in the time series, which will be a Gaussian
    distribution if, indeed, the time series has the characteristics
    of fractional Brownian motion

    3) given the Shannon probability, compute the abscissa value that
    divides the area under the normal curve (with parameters from 2),
    above,) into two sections, such that the area to the left of the
    value, divided by the total area under the normal curve is the
    Shannon probability-a Newton-Raphson iterated approach using
    Romberg integration to find the area is used for this

    4) normalize the mean, and the abscissa value from 3) to units of
    standard deviation from 2), above, as an expediency

    5) rescan the input file, finding the incremental differences in
    the time series, and for each time series value, divide the value
    by the standard deviation, subtract the normalized mean from 4)
    above, add the normalized abscissa value from 4), above, and add
    this value to the running sum of the revenues to reconstruct the
    integration process of the time series, which is assumed to have
    the characteristics of fractional Brownian motion

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

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

$Revision: 0.0 $
$Date: 2006/01/18 20:28:55 $
$Id: tsunfairfractional.c,v 0.0 2006/01/18 20:28:55 john Exp $
$Log: tsunfairfractional.c,v $
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

static char rcsid[] = "$Id: tsunfairfractional.c,v 0.0 2006/01/18 20:28:55 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b," /* file record field separators */

#define NREPS (double) DBL_EPSILON * (double) 10.0 /* epsilon accuracy for final iteration */

#ifndef PI /* make sure PI is defined */

#define PI 3.141592653589793 /* pi to 15 decimal places as per CRC handbook */

#endif

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Unfair returns of a time series\n",
    "Usage: tsunfairfractional [-d] [-f fraction] [-F] [-i value] [-l lower]\n",
    "                          [-p probability] [-t] [-u upper] [-v] filename\n",
    "    -d, the input file is a derivative instead of an integral\n",
    "    -f fraction, fraction of reserves to be wagered, (0 <= fraction <= 1)\n",
    "    -F, output time series is fraction of reserves wagered\n",
    "    -i value, initial value of output time series (ie., initial reserves)\n",
    "    -l lower, lower limit to interval losses\n",
    "    -p probability, Shannon probability, (0.5 <= probability <= 1.0)\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -u upper, upper limit to interval gains\n",
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

static int jmax = 20, /* default maximum number of iterate () iterations allowed */
           k = 5; /* default number of extrapolation points in romberg () integration */

static double eps = (double) 1e-12; /* default convergence error magnitude */

#ifdef __STDC__

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
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         token_separators[] = TOKEN_SEPARATORS;

    int count = 0, /* input file record counter */
        retval = EARGS, /* return value, assume not enough arguments */
        fields, /* number of fields in a record */
        c, /* command line switch */
        d = 0, /* input file contains differences flag, 1 = yes, 0 = no */
        F = 0, /* output time series is fraction of reserves wagered */
        t = 0; /* print time of samples flag, 0 = no, 1 = yes */

    double temp, /* temporary double storage */
           currentvalue = (double) 0.0, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           f = (double) 0.0, /* fraction of reserves to be wagered, null value = use (2 * p) - 1 */
           i = (double) 0.0, /* initial value of output time series, null value = use value of first non-comment record in time series */
           p = (double) 0.5, /* Shannon probability */
           sumx = (double) 0.0, /* linear sum of numbers in file */
           sumsq = (double) 0.0, /* squared sum of numbers in file */
           mean, /* mean of numbers in file */
           stddev, /* standard deviation of numbers in file */
           x = (double) 0.0, /* value to find standard deviation, null means use mean scaled by standard deviation */
           nreps = NREPS, /* epsilon accuracy for final iteration */
           value = (double) DBL_MAX, /* return value from call to function (), less than eps will exit */
           l = (double) - DBL_MAX, /* interval losses can not be less than this value */
           u = (double) DBL_MAX; /* interval gains can not be greater than this value */

    FILE *infile; /* reference to input file */

    while ((c = getopt (argc, argv, "df:Fi:l:np:tu:v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'd': /* request for input file contains differences? */

                d = 1; /* yes, set the input file contains differences flag */
                break;

            case 'f': /* request for fraction of reserves to be wagered */

                f = atof (optarg); /* yes, set the fraction of reserves to be wagered */
                break;

            case 'F': /* request for output time series is fraction of reserves wagered? */
                F = 1; /* set output time series is fraction of reserves wagered */
                break;

            case 'i': /* request for initial value in output time series? */

                i = atof (optarg); /* yes, set the initial value of the output time series */
                break;

            case 'l': /* request for interval losses can not be less than this value */

                l = atof (optarg); /* yes, set the interval losses can not be less than this value */
                break;

            case 'p': /* request for Shannon probability */

                x = p = atof (optarg); /* yes, set the Shannon probability */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

            case 'u': /* request for interval gains can not be greater than this value */

                u = atof (optarg); /* yes, set the interval gains can not be greater than this value */
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
        retval = EOPEN; /* assume error opening file */

        if ((infile = fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the input file */
        {
            retval = NOERROR; /* assume no error */

            while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* count the records in the input file */
            {

                if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                {

                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                    {
                        currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                        if (count == 0) /* first non-comment record? */
                        {

                            if (i == (double) 0.0) /* yes, initial value of output time series a null value? */
                            {
                                i = currentvalue; /* yes, initial value of output time series, null value = use value of first non-comment record in time series */
                            }

                        }

                        if (d == 0) /* input file contains differences flag not set? */
                        {

                            if (count != 0) /* yes, not first record? */
                            {
                                temp = currentvalue - lastvalue; /* yes, subtract the last value in the time series from the current value */
                                sumx = sumx + temp; /* add the sample's value to the linear sum of numbers in file */
                                sumsq = sumsq + temp * temp; /* add the square of the sample's value to the squared sum of numbers in file */
                            }

                            lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                        }

                        else
                        {
                            temp = currentvalue; /* no, save athe current value of the time series */
                            sumx = sumx + temp; /* add the sample's value to the linear sum of numbers in file */
                            sumsq = sumsq + temp * temp; /* add the square of the sample's value to the squared sum of numbers in file */
                        }

                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            if (d == 0) /* input file contains differences flag not set? */
            {
                count --; /* decrement the count of the first record */
            }

            mean = sumx / ((double) count); /* compute the mean of the numbers in the file */
            stddev = (sqrt ((sumsq - sumx * sumx / count) / (count - 1))); /* compute the standard deviation of the numbers in the file */
            rewind (infile); /* rewind the input file */
            count = 0; /* reset the input file record counter */
            currentvalue = (double) 0.0; /* reset the value of current sample in time series */
            lastvalue = (double) 0.0; /* reset the value of last sample in time series */
            mean = mean / stddev; /* scale the mean by the standard deviation */

            if (x == (double) 0.0) /* Shannon probability, null value = use mean scaled by standard deviation */
            {
                p = romberg (normal,(double) 0.0, mean); /* integrate the normal probablility function */
                p = (double) 0.5 + (p / (sqrt ((double) 2.0 * (double) PI))); /* compute the normal probability function's value */
                x = p; /* x is used in calculation of f */
            }

            else
            {

                while (fabs (value) > nreps) /* compute the inverse function of the normal distribution, while the return value from a call to function () is greater than eps */
                {
                    p = p - (value = ((function (p) - x) / derivative (p))); /* iterate the newton loop */
                }

            }

            if (f == (double) 0.0) /* yes, fraction of reserves to be wagered = null value? */
            {
                f = ((double) 2.0 * x) - (double) 1.0; /* yes, fraction of reserves to be wagered, null value = use (2 * p) - 1 */
            }

            while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the records from the input file */
            {

                if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                {

                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                    {
                        currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                        if (d == 0) /* input file contains differences flag not set? */
                        {

                            if (count != 0) /* not first record? */
                            {

                                if (t == 1) /* print time of samples? */
                                {

                                    if (fields > 1) /* yes, more that one field? */
                                    {
                                        (void) printf ("%s\t", token[0]); /* yes, print the sample's time */
                                    }

                                    else
                                    {
                                        (void) printf ("%d\t", count); /* no, print the sample's time which is assumed to be the record count */
                                    }

                                }

                                temp = currentvalue - lastvalue; /* subtract the last value in the time series from the current value */
                                temp = temp / stddev; /* scale the time series value by the standard deviation */
                                temp = temp - mean; /* subtract the mean from the scaled time series value */
                                temp = temp + p; /* add the offset to the scaled and mean corrected time series value */

                                if (temp < l) /* offset value less than minimum? */
                                {
                                    temp = l; /* yes, set the offset value to the minimum */
                                }

                                if (temp > u) /* offset value greater than maximum? */
                                {
                                    temp = u; /* yes, set the offset value to the maximum */
                                }

                                i = (i + (f * i * temp)); /* calculate the new value of reserves */

                                if (F == 0) /* output time series is fraction of reserves wagered? */
                                {
                                    (void) printf ("%f\n", i); /* no, print the new value of reserves */
                                }

                                else
                                {

                                    if (count != 0) /* not first record? */
                                    {
                                        (void) printf ("%f\n", ((currentvalue - lastvalue) / currentvalue) / temp); /* print the fraction of reserves wagered */
                                    }

                                }

                            }

                            lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                        }

                        else
                        {

                            if (t == 1) /* print time of samples? */
                            {

                                if (fields > 1) /* yes, more that one field? */
                                {
                                    (void) printf ("%s\t", token[0]); /* yes, print the sample's time */
                                }

                                else
                                {
                                    (void) printf ("%d\t", count); /* no, print the sample's time which is assumed to be the record count */
                                }

                            }

                            temp = currentvalue; /* save athe current value of the time series */
                            temp = temp / stddev; /* scale the time series value by the standard deviation */
                            temp = temp - mean; /* subtract the mean from the scaled time series value */
                            temp = temp + p; /* add the offset to the scaled and mean corrected time series value */

                            if (temp < l) /* offset value less than minimum? */
                            {
                                temp = l; /* yes, set the offset value to the minimum */
                            }

                            if (temp > u) /* offset value greater than maximum? */
                            {
                                temp = u; /* yes, set the offset value to the maximum */
                            }

                            i = (i + (f * i * temp)); /* calculate the new value of reserves */

                            if (F == 0) /* output time series is fraction of reserves wagered? */
                            {
                                (void) printf ("%f\n", i); /* no, print the new value of reserves */
                            }

                            else
                            {

                                if (count != 0) /* not first record? */
                                {
                                    (void) printf ("%f\n", ((currentvalue - lastvalue) / currentvalue) / temp); /* print the fraction of reserves wagered */
                                }

                            }

                        }

                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            if (fclose (infile) == EOF) /* close the input file */
            {
                retval = ECLOSE; /* error closing file */
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
