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

tslsq.c for making a least squares fit time series from a time series.

The form of the best fit is b + at, for linear least squares fit, e^(b
+ at), x^(y + t), or 2^(z + t) for exponential least squares fit, c /
(1 + e^(-(b + at))) for the logistic least squares fit, sqrt (b + at)
for the square root fit, ln (b + at) for the logarithmic fit, and (b +
at)^2 for the square law fit.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

Note: The derivation for exponential least squares fit is:

        1) input the value of the time series for each time interval,
        value(t), and store the log of the value, ie.:

            y[t] = log (value(t));

        2) compute the least squares fit to y[t], a + bt, then:

            log (y[t]) = b + at

        3) exponentiate the values in y[t]:

            fit (t) = exp (b) * exp (at)
                    = exp (b + at)

        where fit (t) is the least squares exponential fit

    Note: The derivation for power least squares fit is:

        1) compute the least squares fit, as above

        2) since exp (b + at) = pow (exp (a), (b / a) + t),

            fit (t) = pow (x, y + t)

        where x = exp (a), and y = b / a, and fit is the least squares
        power fit, and is identical to the least squares exponential
        fit, above, but is expedient in financial calculations

    Note: The derivation for the binary least squares fit is:

        1) compute the least squares fit, as above

        2) since exp (b + at) = pow (2, x), where

            b + at = x log (2)

            fit (t) = pow (2, x)

        where (b / log (2)) + ((a / log (2)) * t), and fit is the
        least squares binary fit, and is identical to the least
        squares exponential fit, above, but is expedient in financial
        calculations

Note: The derivation for logistic least squares fit is:

    As references on the logistic function, see, "The Art of Modeling
    Dynamic Systems", Foster Morrison, John Wiley & Sons, New York,
    New York, 1991, pp 100, or, "Predictions", Theodore Modis, Simon &
    Schuster, New York, New York, 1992, pp 229.

    As a reference on Newton-Raphson Method of root finding, see
    "Numerical Recipes in C: The Art of Scientific Computing," William
    H. Press, Brian P. Flannery, Saul A. Teukolsky, William
    T. Vetterling, Cambridge University Press, New York, 1988, ISBN
    0-521-35465-X, pp 270,

                      c
    1) n(t) = -------------------
              1 + exp (-(b + at))

                               c
    2) 1 + exp (-(b + at)) = ------
                              n(t)

                          c
    3) exp (-(b + at)) = ---- - 1
                         n(t)

                          c - n(t)
    4) exp (-(b + at)) = ---------
                           n(t)

                        n(t)
    4) exp (b + at) = --------
                      c - n(t)

    5) ln (n(t)) - ln (c - n(t)) = b + at

    which can be used for a least squares approximation of b + at, and
    for Newton-Raphson's Method of finding the roots:

    6) F(a) = ln (n(t)) - ln (c - n(t)) - b - at = 0

    7) F(b) = ln (n(t)) - ln (c - n(t)) - b - at = 0

    8) F(c) = ln (n(t)) - ln (c - n(t)) - b - at = 0

       dF(a)
    9) ----- = - t
        da

        dF(b)
    10) ----- = - 1
         da

        dF(c)        1
    11) ----- = - --------
         dc       c - n(t)

    12) a    = a  + [ln (n(t)) - ln (c  - n(t)) - b - at][t]
         n+1    n                     n

    13) b    = b  + [ln (n(t)) - ln (c  - n(t)) - b - at]
         n+1    n                     n

    or

    14) b   - b  = [ln (n(t)) - ln (c  - n(t)) - b - at]
         n+1   n                     n

                   F(c)
    15) c   = c  - ----- = c  + [ln (n(t)) - ln (c  - n(t)) - b - at][c  - n(t)]
         n+1   n   dF(c)    n                     n                    n
                   -----
                    dc

    therefore, by combining equations 14) and 15)

    16) c    = c  + [b    - b ][c  - n(t)]
         n+1    n     n+1    n   n

    which is a statement of how c changes for changes in b in the
    process of convergence for the Newton-Raphson's Method of finding
    the roots, which leads to the strategy for the iterative loop

        use

        1) ln (n(t)) - ln (c  - n(t)) = b + at
                            n

        to find the least squares fit for b and at, using the original
        data for n(t), and the iteration value for c , and then use
                                                    n

        2) c    = c  + [b    - b ][c  - n(t)]
            n+1    n     n+1    n   n

        to find the next value of c    for the next iteration of
                                   n+1

        equation 1), using the differences in b, and the original data
        for n(t)-if b    - b  is sufficiently small, convergence has
                     n+1    n

        has been attained, and the iterations are finished, with the
        values of a, b, and c

    note that the average of the term c  - n(t) is
                                       n

                1
    c  - n(t) = - [c  - n(t ) + c  - n(t ) + c  - n(t ) + ... + c  - n(t )]
     n          t   n      0     n      2     n      3           n      n

                        k
                     1 ---
              = c  + - \   (n(t))
                 n   k /
                       ---
                       t = 0

    where k is the number of samples in the time series

    note that an initial estimate of c must be made-an appropriate
    choice is that maximum value of n(t), plus a sufficiently large
    amount to protect the log() function in equation 1) for the
    initial iteration, as a reasonable choice, since

         c             (-(b + at))
        ---- = 1 + exp
        n(t)

        and both b and a are zero,

         c             0
        ---- = 1 + exp
        n(t)

        or

        c = 2n(t)

        where n(t) is taken as the maximum n(t) in the time series, or

                 |
        c = 2n(t)|
                 | max

        unfortunately, if the time series data set constitutes the
        rising exponential section of the logistic function, this
        assumption can cause the iterations to converge to a larger
        value of c

    note, additionally, that since the derivative of a time series is
    straight forward, ie., subtract the nth value from the n plus one
    value, that additional information is available

$Revision: 0.0 $
$Date: 2006/01/18 19:36:00 $
$Id: tslsq.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tslsq.c,v $
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

#ifndef DBL_MIN

#define DBL_MIN 2.2250738585072014E-308

#endif

static char rcsid[] = "$Id: tslsq.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b," /* file record field separators */

#define EPS (double) DBL_EPSILON * (double) 1000.0 /* epsilon accuracy for final iteration in logistic least squares fit */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Least squares fit to a time series\n",
    "Usage: tslsq [-c start] [-e] [-f n] [-i] [-L] [-l] [-m n] [-o] [-p]\n",
    "             [-R] [-S] [-s] [-t] [-v] [filename]\n",
    "    -c start, start value in logistic fit\n",
    "    -e, use exponential fit of the form, e^(b + at) = x^(y + t) = 2^(p + qt)\n",
    "    -f n, increase c by n under floating point exception in the logistic fit\n",
    "    -i, print convergence information to stderr in logistic fit\n",
    "    -L, use natural logarithm fit of the form, ln (b + at)\n",
    "    -l, use logistic fit of the form, c / (1 + e^(-(b + at)))\n",
    "    -m n, n is 0, 1, 2, 3, 4, or 5 = convergence  method in logistic fit\n",
    "    -o, subtract the least squares fit from the output time series\n",
    "    -p, output only the formula for the least square fit\n",
    "    -R, use square root fit of the form, sqrt (b + at)\n",
    "    -S, use square law fit of the form, (b + at)^2\n",
    "    -s, scale the output to the magnitude of the least squares fit  (implies -o)\n",
    "    -t, sample's time will be included in the output time series\n",
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

#ifdef __STDC__

typedef int (*PTF) (double *value, double *position, int count, int t, int o, int s, int p); /* how function algorithms are called */

static void print_message (int retval); /* print any error messages */
static int linear (double *value, double *position, int count, int t, int o, int s, int p);
static int logarithmic (double *value, double *position, int count, int t, int o, int s, int p);
static int logistic (double *value, double *position, int count, int t, int o, int s, int p);
static int exponential (double *value, double *position, int count, int t, int o, int s, int p);
static int square (double *value, double *position, int count, int t, int o, int s, int p);
static int squareroot (double *value, double *position, int count, int t, int o, int s, int p);
static int strtoken (char *string, char *parse_array, char **parse, char *delim);

#else

typedef int (*PTF) (); /* how function algorithms are called */

static void print_message (); /* print any error messages */
static int linear ();
static int logarithmic ();
static int logistic ();
static int exponential ();
static int square ();
static int squareroot ();
static int strtoken ();

#endif

static int converge = 0; /* print convergence information in logistic function, 0 = no, 1 = yes */

static int method = 0; /* convergence method used in logistic function, 0 is most simple, 1 is next most simple, 2 is most complex, 3 is like 1, but with reverse direction convergence, 4 is like 2, but with reverse direction convergence, 5 is like 0, but with reverse direction convergence */

static double start = (double) 0.0; /* start c in logistic function */

static double step = (double) 1.0; /* value by which value(t) is multiplied to prevent floating point exceptions in logistic function */

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
        retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        c, /* command line switch */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        o = 0, /* subtract least squares fit from output time series flag, 0 = no, 1 = yes */
        s = 0, /* scale output on magnitude of least squares fit flag, 0 = no, 1 = yes */
        p = 0; /* only output formula for least sqares fit flag, 0 = no, 1 = yes */

    double *value = (double *) 0, /* reference to array of data values from file */
           *position = (double *) 0, /* reference to array of time values from file */
           *lastdata = (double *) 0; /* last reference to array of data from file */

    FILE *infile = stdin; /* reference to input file */

    PTF function = linear; /* reference to least squares fitting function */

    while ((c = getopt (argc, argv, "c:ef:iLlm:opRSstv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'c': /* start c in logistic function? */

                start = atof (optarg); /* yes, save the start c in logistic function */
                break;

            case 'e': /* use exponential fit instead of linear? */

                function = exponential; /* yes, reference to least squares fitting function for exponential */
                break;

            case 'f': /* value by which value(t) is multiplied to prevent floating point exceptions in logistic function */

                step = atof (optarg); /* yes, save the value by which value(t) is multiplied to prevent floating point exceptions in logistic function */
                break;

            case 'i': /* print convergence information in logistic function? */

                converge = 1; /* yes, set the print convergence information in logistic function flag */
                break;

            case 'L': /* use logarithmic fit instead of linear? */

                function = logarithmic; /* yes, reference to least squares fitting function for logarithmic */
                break;

            case 'l': /* use logistic fit instead of linear? */

                function = logistic; /* yes, reference to least squares fitting function for logistic */
                break;

            case 'm': /* convergence method used in logistic function, 0 is most simple, 1 is next most simple, 2 is most complex, 3 is like 1, but with reverse direction convergence, 4 is like 2, but with reverse direction convergence, 5 is like 0, but with reverse direction convergence? */

                method = atoi (optarg); /* yes, set the convergence method used in logistic function, 0 is most simple, 1 is next most simple, 2 is most complex, 3 is like 1, but with reverse direction convergence, 4 is like 2, but with reverse direction convergence, 5 is like 0, but with reverse direction convergence */
                break;

            case 'o': /* subtract least squares fit from output time series? */

                o = 1; /* yes, set the subtract least squares fit from output time series flag */
                break;

            case 'p': /* only output formula for least sqares fit? */

                p = 1; /* yes, set the only output formula for least sqares fit flag */
                break;

            case 'S': /* use square law fit instead of linear? */

                function = square; /* yes, reference to least squares fitting function for square law */
                break;

            case 'R': /* use square root fit instead of linear? */

                function = squareroot; /* yes, reference to least squares fitting function for square root */
                break;

            case 's': /* scale output on magnitude of least squares fit? */

                s = 1; /* yes, set the scale output on magnitude of least squares fit flag */
                o = 1; /* and, set the subtract least squares fit from output time series flag */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
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
        retval = EOPEN; /* assume error opening file */

        if ((infile = argc <= optind ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the input file */
        {
            retval = NOERROR; /* assume no error */

            while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* count the records in the input file */
            {

                if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                {

                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                    {
                        lastdata = value; /* save the last reference to array of data from file */

                        if ((value = (double *) realloc (value, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of values from the input file */
                        {
                            value = lastdata; /* couldn't allocate space for the array of values from the input file, restore the last reference to array of data from file */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        lastdata = position; /* save the last reference to array of data from file */

                        if ((position = (double *) realloc (position, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of time values from the input file */
                        {
                            position = lastdata; /* couldn't allocate space for the array of time values from the input file, restore the last reference to array of data from file */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        value[count] = atof (token[fields - 1]); /* no, save the sample's value at that time */

                        if (fields > 1) /* yes, more that one field? */

                        {
                            position[count] = atof (token[0]); /* yes, save the sample's time */
                        }

                        else
                        {
                            position[count] = (double) count; /* no, use the record count as the time of the sample */
                        }

                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            if (retval == NOERROR) /* no errors? */
            {
                retval = (*function) (value, position, count, t, o, s, p); /* call the least squares function function */
            }

            if (value != (double *) 0) /* allocated space for the array of values from the input file? */
            {
                free (value); /* yes, free the space for the array of values from the input file */
            }

            if (position != (double *) 0) /* allocated space for the array of time values from the input file? */
            {
                free (position); /* yes, free the space for the array of time values from the input file*/
            }

            if (argc > optind) /* using stdin as input? */
            {

                if (fclose (infile) == EOF) /* no, close the input file */
                {
                    retval = ECLOSE; /* error closing file */
                }

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

construct a linear least squares best fit to time series data

static int linear (double *value, double *position, int count, int t, int o, int s, int p)

the time sample's data, and time, are in each element of the array
value and position, respectively-the arrays contain count many
elements, each. If t == 1, then the times will be printed, and if o ==
1, the best fit will be subtracted from the original data, and output.
If s == 1, then the best fit will be subtracted from the original data
and scaled to the magnitude of the best fit-this option implies that o
== 1. If p == 1, then the formula for the best fit will be output, but
no data.

NOERROR is returned.

*/

#ifdef __STDC__

static int linear (double *value, double *position, int count, int t, int o, int s, int p)

#else

static int linear (value, position, count, t, o, s, p)
double *value;
double *position;
int count;
int t;
int o;
int s;
int p;

#endif

{
    int i, /* element counter */
        retval = NOERROR; /* return value, assume no error */

    double sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           a, /* slope of best fit line */
           b, /* offset of best fit line */
           temp1, /* temporary double variable */
           temp2; /* temporary double variable */

    for (i = 0; i < count; i++) /* for each time sample */
    {
        temp1 = value[i]; /* save the element value */
        temp2 = position[i]; /* save the time of the element */
        sx += temp2; /* add the time value to the sum of the time values */
        sy += temp1; /* add the data value to the sum of the data values */
        sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
        sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
    }

    det = (double) count * sxx - sx * sx;
    a = ((double) count * sxy - sx * sy) / det;
    b = (-sx * sxy + sxx * sy) / det;

    if (p == 1) /* only output formula for least sqares fit flag set? */
    {
        (void) printf ("%f + %ft\n", b, a); /* no, print only output formula for the linear least sqares fit */
    }

    else
    {

        for (i = 0; i < count; i++) /* for each time sample */
        {

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* yes, print the sample's time */
            }

            if (o == 1) /* subtract least squares fit from output time series flag set? */
            {

                if (s == 1) /* scale output on magnitude of least squares fit flag set? */
                {
                    (void) printf ("%f\n", ((value[i] / (a * position[i] + b)) - 1)); /* no, print the value minus the best fit linear sample for that time */
                }

                else
                {
                    (void) printf ("%f\n", (value[i] - (a * position[i] + b))); /* no, print the value minus the best fit linear sample for that time */
                }

            }

            else
            {
                (void) printf ("%f\n", (a * position[i] + b)); /* no, print the best fit linear sample for that time */
            }

        }

    }

    return (retval); /* return any errors */
}

/*

construct an exponential least squares best fit to time series data

static int exponential (double *value, double *position, int count, int t, int o, int s, int p)

the time sample's data, and time, are in each element of the array
value and position, respectively-the arrays contain count many
elements, each. If t == 1, then the times will be printed, and if o ==
1, the best fit will be subtracted from the original data, and output.
If s == 1, then the best fit will be subtracted from the original data
and scaled to the magnitude of the best fit-this option implies that o
== 1. If p == 1, then the formula for the best fit will be output, but
no data.

NOERROR is returned.

*/

#ifdef __STDC__

static int exponential (double *value, double *position, int count, int t, int o, int s, int p)

#else

static int exponential (value, position, count, t, o, s, p)
double *value;
double *position;
int count;
int t;
int o;
int s;
int p;

#endif

{
    int i, /* element counter */
        retval = NOERROR; /* return value, assume no error */

    double sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           a, /* slope of best fit line */
           b, /* offset of best fit line */
           k, /* exp (b) */
           temp1, /* temporary double variable */
           temp2; /* temporary double variable */

    for (i = 0; i < count; i++) /* for each time sample */
    {
        temp1 = log (value[i]); /* save the logarithm of the element value */
        temp2 = position[i]; /* save the time of the element */
        sx += temp2; /* add the time value to the sum of the time values */
        sy += temp1; /* add the data value to the sum of the data values */
        sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
        sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
    }

    det = (double) count * sxx - sx * sx;
    a = ((double) count * sxy - sx * sy) / det;
    b = (-sx * sxy + sxx * sy) / det;
    k = exp (b); /* calculate exp (b) */

    if (p == 1) /* only output formula for least sqares fit flag set? */
    {
        (void) printf ("e^(%f + %ft) = %f^(%f + t) = 2^(%f + %ft)\n", b, a, (exp (a)), (b / a), (b / log ((double) 2.0)), ((a / log ((double) 2.0)))); /* yes, print only output formula for the exponential least sqares fit */
    }

    else
    {

        for (i = 0; i < count; i++) /* for each time sample */
        {

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* yes, print the sample's time */
            }

            if (o == 1) /* subtract least squares fit from output time series flag set? */
            {

                if (s == 1) /* scale output on magnitude of least squares fit flag set? */
                {
                    (void) printf ("%f\n", (((value[i] / (exp (a * position[i]) * k))) - 1)); /* yes, print the value minus the best fit exponential sample for that time */
                }

                else
                {
                    (void) printf ("%f\n", (value[i] - (exp (a * position[i]) * k))); /* yes, print the value minus the best fit exponential sample for that time */
                }

            }

            else
            {
                (void) printf ("%f\n", exp (a * position[i]) * k); /* yes, print the best fit exponential sample for that time */
            }

        }

    }

    return (retval); /* return any errors */
}

/*

construct a square root least squares best fit to time series data

static int squareroot (double *value, double *position, int count, int t, int o, int s, int p)

the time sample's data, and time, are in each element of the array
value and position, respectively-the arrays contain count many
elements, each. If t == 1, then the times will be printed, and if o ==
1, the best fit will be subtracted from the original data, and output.
If s == 1, then the best fit will be subtracted from the original data
and scaled to the magnitude of the best fit-this option implies that o
== 1. If p == 1, then the formula for the best fit will be output, but
no data.

NOERROR is returned.

*/

#ifdef __STDC__

static int squareroot (double *value, double *position, int count, int t, int o, int s, int p)

#else

static int squareroot (value, position, count, t, o, s, p)
double *value;
double *position;
int count;
int t;
int o;
int s;
int p;

#endif

{
    int i, /* element counter */
        retval = NOERROR; /* return value, assume no error */

    double sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           a, /* slope of best fit line */
           b, /* offset of best fit line */
           temp1, /* temporary double variable */
           temp2; /* temporary double variable */

    for (i = 0; i < count; i++) /* for each time sample */
    {
        temp1 = value[i] * value[i]; /* save the square of the element value */
        temp2 = position[i]; /* save the time of the element */
        sx += temp2; /* add the time value to the sum of the time values */
        sy += temp1; /* add the data value to the sum of the data values */
        sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
        sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
    }

    det = (double) count * sxx - sx * sx;
    a = ((double) count * sxy - sx * sy) / det;
    b = (-sx * sxy + sxx * sy) / det;

    if (p == 1) /* only output formula for least sqares fit flag set? */
    {
        (void) printf ("sqrt (%f + %ft)\n", b, a); /* yes, print only output formula for the square root least sqares fit */
    }

    else
    {

        for (i = 0; i < count; i++) /* for each time sample */
        {

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* yes, print the sample's time */
            }

            if (o == 1) /* subtract least squares fit from output time series flag set? */
            {

                if (s == 1) /* scale output on magnitude of least squares fit flag set? */
                {
                    (void) printf ("%f\n", ((value[i] / sqrt (a * position[i] + b)) - 1)); /* yes, print the value minus the best fit square root sample for that time */
                }

                else
                {
                    (void) printf ("%f\n", (value[i] - sqrt (a * position[i] + b))); /* yes, print the value minus the best fit square root sample for that time */
                }

            }

            else
            {
                (void) printf ("%f\n", sqrt (b + (a * position[i]))); /* yes, print the best fit square root sample for that time */
            }

        }

    }

    return (retval); /* return any errors */
}

/*

construct a logarithmic least squares best fit to time series data

static int logarithmic (double *value, double *position, int count, int t, int o, int s, int p)

the time sample's data, and time, are in each element of the array
value and position, respectively-the arrays contain count many
elements, each. If t == 1, then the times will be printed, and if o ==
1, the best fit will be subtracted from the original data, and output.
If s == 1, then the best fit will be subtracted from the original data
and scaled to the magnitude of the best fit-this option implies that o
== 1. If p == 1, then the formula for the best fit will be output, but
no data.

NOERROR is returned.

*/

#ifdef __STDC__

static int logarithmic (double *value, double *position, int count, int t, int o, int s, int p)

#else

static int logarithmic (value, position, count, t, o, s, p)
double *value;
double *position;
int count;
int t;
int o;
int s;
int p;

#endif

{
    int i, /* element counter */
        retval = NOERROR; /* return value, assume no error */

    double sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           a, /* slope of best fit line */
           b, /* offset of best fit line */
           temp1, /* temporary double variable */
           temp2; /* temporary double variable */

    for (i = 0; i < count; i++) /* for each time sample */
    {
        temp1 = exp (value[i]); /* save the square of the element value */
        temp2 = position[i]; /* save the time of the element */
        sx += temp2; /* add the time value to the sum of the time values */
        sy += temp1; /* add the data value to the sum of the data values */
        sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
        sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
    }

    det = (double) count * sxx - sx * sx;
    a = ((double) count * sxy - sx * sy) / det;
    b = (-sx * sxy + sxx * sy) / det;

    if (p == 1) /* only output formula for least sqares fit flag set? */
    {
        (void) printf ("log (%f + %ft)\n", b, a); /* yes, print only output formula for the squre root least sqares fit */
    }

    else
    {

        for (i = 0; i < count; i++) /* for each time sample */
        {

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* yes, print the sample's time */
            }

            if (o == 1) /* subtract least squares fit from output time series flag set? */
            {

                if (s == 1) /* scale output on magnitude of least squares fit flag set? */
                {
                    (void) printf ("%f\n", ((value[i] / log (a * position[i] + b)) - 1)); /* yes, print the value minus the best fit square root sample for that time */
                }

                else
                {
                    (void) printf ("%f\n", (value[i] - log (a * position[i] + b))); /* yes, print the value minus the best fit square root sample for that time */
                }

            }

            else
            {
                (void) printf ("%f\n", log (b + (a * position[i]))); /* yes, print the best fit square root sample for that time */
            }

        }

    }

    return (retval); /* return any errors */
}

/*

construct a square law least squares best fit to time series data

static int square (double *value, double *position, int count, int t, int o, int s, int p)

the time sample's data, and time, are in each element of the array
value and position, respectively-the arrays contain count many
elements, each. If t == 1, then the times will be printed, and if o ==
1, the best fit will be subtracted from the original data, and output.
If s == 1, then the best fit will be subtracted from the original data
and scaled to the magnitude of the best fit-this option implies that o
== 1. If p == 1, then the formula for the best fit will be output, but
no data.

NOERROR is returned.

*/

#ifdef __STDC__

static int square (double *value, double *position, int count, int t, int o, int s, int p)

#else

static int square (value, position, count, t, o, s, p)
double *value;
double *position;
int count;
int t;
int o;
int s;
int p;

#endif

{
    int i, /* element counter */
        retval = NOERROR; /* return value, assume no error */

    double sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           a, /* slope of best fit line */
           b, /* offset of best fit line */
           temp1, /* temporary double variable */
           temp2; /* temporary double variable */

    for (i = 0; i < count; i++) /* for each time sample */
    {
        temp1 = sqrt (value[i]); /* save the square of the element value */
        temp2 = position[i]; /* save the time of the element */
        sx += temp2; /* add the time value to the sum of the time values */
        sy += temp1; /* add the data value to the sum of the data values */
        sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
        sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
    }

    det = (double) count * sxx - sx * sx;
    a = ((double) count * sxy - sx * sy) / det;
    b = (-sx * sxy + sxx * sy) / det;

    if (p == 1) /* only output formula for least sqares fit flag set? */
    {
        (void) printf ("(%f + %ft)^2\n", b, a); /* yes, print only output formula for the square law least sqares fit */
    }

    else
    {

        for (i = 0; i < count; i++) /* for each time sample */
        {

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* yes, print the sample's time */
            }

            if (o == 1) /* subtract least squares fit from output time series flag set? */
            {

                if (s == 1) /* scale output on magnitude of least squares fit flag set? */
                {
                    (void) printf ("%f\n", ((value[i] / ((a * position[i] + b) * (a * position[i] + b))) - 1)); /* yes, print the value minus the best fit square sample for that time */
                }

                else
                {
                    (void) printf ("%f\n", (value[i] - ((a * position[i] + b) * (a * position[i] + b)))); /* yes, print the value minus the best fit square law sample for that time */
                }

            }

            else
            {
                (void) printf ("%f\n", (b + (a * position[i])) * (b + (a * position[i]))); /* yes, print the best fit square law sample for that time */
            }

        }

    }

    return (retval); /* return any errors */
}

/*

construct a logistic least squares best fit to time series data

static int logistic (double *value, double *position, int count, int t, int o, int s, int p)

the time sample's data, and time, are in each element of the array
value and position, respectively-the arrays contain count many
elements, each. If t == 1, then the times will be printed, and if o ==
1, the best fit will be subtracted from the original data, and output.
If s == 1, then the best fit will be subtracted from the original data
and scaled to the magnitude of the best fit-this option implies that o
== 1. If p == 1, then the formula for the best fit will be output, but
no data.

NOERROR is returned.

*/

#ifdef __STDC__

static int logistic (double *value, double *position, int count, int t, int o, int s, int p)

#else

static int logistic (value, position, count, t, o, s, p)
double *value;
double *position;
int count;
int t;
int o;
int s;
int p;

#endif

{
    int i, /* element counter */
        c_count = 1, /* number of times c has been increased */
        retval = NOERROR; /* return value, assume no error */

    double sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           eps = EPS, /* epsilon accuracy for final iteration */
           a = (double) 0.0, /* slope of best fit line */
           b = (double) 0.0, /* offset of best fit line */
           c = start, /* scaling factor */
           del_b, /* next b - previous b */
           max = (double) DBL_MIN, /* maximum value of any value[i] */
           n_t = (double) 0.0, /* average of all value[i] */
           error_value = DBL_MAX, /* return value from call to function (), less than eps will exit */
           temp1, /* temporary double variable */
           temp2; /* temporary double variable */

    for (i = 0; i < count; i++) /* for each time sample */
    {

        if (value[i] > max) /* value greater than any maximum value of any value[i] so far?? */
        {
            max = value[i]; /* yes, save the maximum, so far */
        }

        n_t = n_t + value[i]; /* sum the value to the average of all value[i] */
    }

    n_t = n_t / (double) count; /* calculate the average of all value[i] */
    error_value = DBL_MAX; /* return value from call to function (), less than eps will exit */

    while (fabs (error_value) > eps) /* while the error value is greater than eps */
    {
        sx = (double) 0.0; /* sum of the time values */
        sy = (double) 0.0; /* sum of the data values */
        sxx = (double) 0.0; /* sum of the time values squared */
        sxy = (double) 0.0; /* sum of the data values * the time values */

        while (c <= max) /* while c less than max? */
        {
            c_count++; /* yes, increment the number of times c has been increased */
            c = max * (step * (double) c_count); /* increase c to protect the log() function, below */

            if (converge == 1) /* print convergence information in logistic function flag set? */
            {
                (void) fprintf (stderr, "c = %f\n", c); /* yes, print the value of c */
            }

        }

        for (i = 0; i < count; i++) /* for each time sample */
        {
            temp1 = log (value[i] / (c - value[i])); /* save the logarithm of the element value minus the logarithm of the maximum minus the element value */
            temp2 = position[i]; /* save the time of the element */
            sx += temp2; /* add the time value to the sum of the time values */
            sy += temp1; /* add the data value to the sum of the data values */
            sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
            sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
        }

        det = (double) count * sxx - sx * sx;
        a = ((double) count * sxy - sx * sy) / det;
        del_b = b; /* save the previous value of b */
        b = (-sx * sxy + sxx * sy) / det;
        del_b = b - del_b; /* calculate the next b - previous b */

        switch (method) /* which convergence method used in logistic function, 0 is most simple, 1 is next most simple, 2 is most complex, 3 is like 1, but with reverse direction convergence, 4 is like 2, but with reverse direction convergence, 5 is like 0, but with reverse direction convergence */
        {

            case 1: /* moderate complex */

                temp1 = (double) 0.0;

                for (i = 0; i < count; i++) /* for each time sample */
                {
                    temp1 = temp1 + ((log (value[i] / (c - value[i])) - (a * position[i]) - b) * (c - value[i])); /* calculate and sum the values of c */
                }

                error_value = c; /* save the last value of c */
                c = c + (temp1 / (double) count); /* calculate the next value of c, which is the average value */
                error_value = (error_value / c) - (double) 1.0; /* calculate the error value */
                break;

            case 2: /* most complex */

                sx = (double) 0.0; /* sum of the time values */
                sy = (double) 0.0; /* sum of the data values */
                sxx = (double) 0.0; /* sum of the time values squared */
                sxy = (double) 0.0; /* sum of the data values * the time values */

                for (i = 0; i < count; i++) /* for each time sample */
                {
                    temp1 = ((log (value[i] / (c - value[i])) - (a * position[i]) - b) * (c - value[i])); /* calculate the value of c */
                    temp2 = position[i]; /* save the time of the element */
                    sx += temp2; /* add the time value to the sum of the time values */
                    sy += temp1; /* add the data value to the sum of the data values */
                    sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
                    sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
                }

                error_value = c; /* save the last value of c */
                det = (double) count * sxx - sx * sx;
                c = (-sx * sxy + sxx * sy) / det;
                c = c + ((((double) count * sxy - sx * sy) / det) * position[count / 2]);
                c = error_value + c; /* calculate the next value of c, which is the least squares value */
                error_value = (error_value / c) - (double) 1.0; /* calculate the error value */
                break;

            case 3: /* moderate complex, reverse direction convergence */

                temp1 = (double) 0.0;

                for (i = 0; i < count; i++) /* for each time sample */
                {
                    temp1 = temp1 + ((log (value[i] / (c - value[i])) - (a * position[i]) - b) * (c - value[i])); /* calculate and sum the values of c */
                }

                error_value = c; /* save the last value of c */
                c = c - (temp1 / (double) count); /* calculate the next value of c, which is the average value */
                error_value = (error_value / c) - (double) 1.0; /* calculate the error value */
                break;

            case 4: /* most complex, reverse direction convergence */

                sx = (double) 0.0; /* sum of the time values */
                sy = (double) 0.0; /* sum of the data values */
                sxx = (double) 0.0; /* sum of the time values squared */
                sxy = (double) 0.0; /* sum of the data values * the time values */

                for (i = 0; i < count; i++) /* for each time sample */
                {
                    temp1 = ((log (value[i] / (c - value[i])) - (a * position[i]) - b) * (c - value[i])); /* calculate the value of c */
                    temp2 = position[i]; /* save the time of the element */
                    sx += temp2; /* add the time value to the sum of the time values */
                    sy += temp1; /* add the data value to the sum of the data values */
                    sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
                    sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
                }

                error_value = c; /* save the last value of c */
                det = (double) count * sxx - sx * sx;
                c = (-sx * sxy + sxx * sy) / det;
                c = c + ((((double) count * sxy - sx * sy) / det) * position[count / 2]);
                c = error_value - c; /* calculate the next value of c, which is the least squares value */
                error_value = (error_value / c) - (double) 1.0; /* calculate the error value */
                break;

            case 5: /* simplest, reverse direction convergence */

                error_value = c; /* save the last value of c */
                c = c - (del_b * (c - n_t)); /* calculate the next value of c */
                error_value = (error_value / c) - (double) 1.0; /* calculate the error value */
                break;

            case 0: /* simplest, which is default */

            default:

                error_value = c; /* save the last value of c */
                c = c + (del_b * (c - n_t)); /* calculate the next value of c */
                error_value = (error_value / c) - (double) 1.0; /* calculate the error value */
                break;

        }

        if (converge == 1) /* print convergence information in logistic function flag set? */
        {
            (void) fprintf (stderr, "a = %f, b = %f, c = %f\n", a, b, c); /* yes, print the convergence informaiton in logistic function */
        }

    }

    if (p == 1) /* only output formula for least sqares fit flag set? */
    {
        (void) printf ("%f / (1 + e^(-(%f + %ft)))\n", c, b, a); /* yes, print only output formula for the logistic least sqares fit */
    }

    else
    {

        for (i = 0; i < count; i++) /* for each time sample */
        {

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* yes, print the sample's time */
            }

            if (o == 1) /* subtract least squares fit from output time series flag set? */
            {

                if (s == 1) /* scale output on magnitude of least squares fit flag set? */
                {
                    (void) printf ("%f\n", ((value[i] / (c / ((double) 1.0 + exp (-(b + (a * position[i])))))) - (double) 1.0)); /* yes, print the value minus the best fit logistic sample for that time */
                }

                else
                {
                    (void) printf ("%f\n", (value[i] - (c / ((double) 1.0 + exp (-(b + (a * position[i]))))))); /* yes, print the value minus the best fit logistic sample for that time */
                }

            }

            else
            {
                (void) printf ("%f\n", (c / ((double) 1.0 + exp (-(b + (a * position[i])))))); /* yes, print the best fit logistic sample for that time */
            }

        }

    }

    return (retval); /* return any errors */
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
