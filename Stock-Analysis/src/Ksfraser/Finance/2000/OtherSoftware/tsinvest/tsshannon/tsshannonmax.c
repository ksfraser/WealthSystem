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

Tsshannonmax is for calculating unfair returns of a time series, as a
function of Shannon probability. The input time series is presumed to
have a Brownian distribution. The main function of this program is
regression scenario verification-given an empirical time series,
speculative market pro forma performance can be analyzed, as a
function of Shannon probability. The cumulative sum process is
Brownian in nature.

To find the maximum returns, the "golden" method of minimization is
used.  As a reference on the "golden" method of minimization, see
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, pp
298.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

$Revision: 0.0 $
$Date: 2006/01/18 19:36:00 $
$Id: tsshannonmax.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsshannonmax.c,v $
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

static char rcsid[] = "$Id: tsshannonmax.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Returns of a time series with unfair weights\n",
    "Usage: tsshannonmax [-d] [-i value] [-m minimum] [-M maximum] [-p]\n",
    "                    [-s step] [-v] [filename]\n",
    "    -d, the input file is a derivative instead of an integral\n",
    "    -i value, initial value of output time series (ie., initial reserves)\n",
    "    -m minimum, minimum Shannon probability, (0.5 <= probability <= 1.0)\n",
    "    -M maximum, maximum Shannon probability, (0.5 <= probability <= 1.0)\n",
    "    -p, output only the Shannon probability followed by maximum return value\n",
    "    -s step, step size of output time series, (0.0 < size < 1.0)\n",
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
    "Error allocating memory\n",
    "Error opening file\n",
    "Error closing file\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1
#define EALLOC 2
#define EOPEN 3
#define ECLOSE 4

#define TOL DBL_EPSILON * (double) 10.0 /* tolerance accuracy for final iteration in golden () minimization */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static double unfairbrownian (double p, double initial, double *series, int elements);
static int strtoken (char *string, char *parse_array, char **parse, char *delim);
static double golden (double ax, double bx, double cx, double tol);

#else

static void print_message (); /* print any error messages */
static double unfairbrownian ();
static int strtoken ();
static double golden ();

#endif

static int count = 0; /* input file record counter, static global for visability in golden () */

static double i = (double) 0.0, /* initial value of output time series, null value = use value of first non-comment record in time series, static global for visability in golden () */
       *array; /* array of the time series data set, static global for visability in golden () */

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

    int retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        c, /* command line switch */
        d = 0, /* input file contains differences flag, 1 = yes, 0 = no */
        p = 0; /* print the value of the maximum, not the graph */

    double currentvalue = (double) 0.0, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           ps, /* Shannon probability */
           m = (double) 0.5, /* minimum Shannon probability */
           M = (double) 1.0, /* maximum Shannon probability */
           s = (double) 0.01, /* step size of shannon probability */
           temp; /* temporary double storage */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "di:m:M:ps:v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'd': /* request for input file contains differences? */

                d = 1; /* yes, set the input file contains differences flag */
                break;

            case 'm': /* request for minimum Shannon probability */
                m = atof (optarg); /* yes, set the minimum Shannon probability */
                break;

            case 'M': /* request for maximum Shannon probability */

                M = atof (optarg); /* yes, set the maximum Shannon probability */
                break;

            case 'i': /* request for initial value in output time series? */

                i = atof (optarg); /* yes, set the initial value of the output time series */
                break;

            case 'p': /* request for print the value of the maximum, not the graph */
                p = 1; /* yes, set the print the value of the maximum, not the graph */
                break;

            case 's': /* request for step size of shannon probability */

                s = atof (optarg); /* yes, set the step size of shannon probability */
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

                                if (count == 0) /* size of the array of the time series data set = zero? */
                                {

                                    if ((array = (double *) malloc (sizeof (double))) == (double *) 0) /* yes, allocate space for the array of the time series data set's first element */
                                    {
                                        retval = EALLOC;  /* couldn't allocate space for the array of the time series data set's first element, assume error allocating memory */
                                        break;
                                    }

                                }

                                else
                                {

                                    if ((array = (double *) realloc (array, ((size_t) (count + 1)) * sizeof (double))) == (double *) 0) /* no, reallocate space for the array of the time series data set's next element */
                                    {
                                        retval = EALLOC;  /* assume error allocating memory */
                                        break;
                                    }

                                }

                                array[count] = currentvalue - lastvalue; /* save the difference between this element's value and the last element's value */
                            }

                            lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                        }

                        else
                        {

                            if (count == 0) /* size of the array of the time series data set = zero? */
                            {

                                if ((array = (double *) malloc (sizeof (double))) == (double *) 0) /* yes, allocate space for the array of the time series data set's first element */
                                {
                                    retval = EALLOC;  /* couldn't allocate space for the array of the time series data set's first element, assume error allocating memory */
                                    break;
                                }

                            }

                            else
                            {

                                if ((array = (double *) realloc (array, ((size_t) (count + 1)) * sizeof (double))) == (double *) 0) /* no, reallocate space for the array of the time series data set's next element */
                                {
                                    retval = EALLOC;  /* assume error allocating memory */
                                    break;
                                }

                            }

                            array[count] = currentvalue; /* save the element's value */
                        }

                        count ++; /* increment the count of records from the input file */
                    }

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

        if (retval == NOERROR) /* if no errors, continue */
        {

            if (p == 0)
            {

                ps = m; /* start at the lowest value of Shannon probability */

                while (ps < M) /* while the Shannon probability is less than the maximum value of Shannon probability */
                {
                    (void) printf ("%f\t%f\n", ps, unfairbrownian (ps, i, array, count)); /* compute and print the Shannon probability and last value of reserves */
                    ps = ps + s; /* next step in Shannon probability */
                }

            }

            else
            {
                temp = golden (m, (M - m) / (double) 2.0 + m, M, (double) TOL); /* compute and save the Shannon probability of the maximum */
                (void) printf ("%f %f\n", temp, unfairbrownian (temp, i, array, count)); /* print the Shannon probability of the maximum with the final reserves */
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

double unfairbrownian (double p, double initial, double *series, int elements);

unfair returns of a time series-the idea is to produce the returns of
a time series which is weighted unfairly, by "wagering" a fraction of
reserves to be bet for each time increment. The input time series is
presumed to have a Brownian distribution; p is the shannon probability,
starting with a value of initial, and series is an array on elements
many elements that contain the value of the time series set

returns the final cumulative sum after making elements many wagers

*/

#ifdef __STDC__

static double unfairbrownian (double p, double initial, double *series, int elements)

#else

static double unfairbrownian (p, initial, series, elements)

double p;
double initial;
double *series;
int elements;

#endif

{
    int j; /* element counter */

    double in = initial, /* cumulative sum of cash reserves */
           f; /* fraction of reserves to be wagered in each interval */

    f = ((double) 2.0 * p) - (double) 1.0; /* compute the fraction of reserves to be wagered */

    for (j = 0; j < elements; j++)
    {

        if (series[j] < (double) 0.0) /* value of the current sample in the time series negative? */
        {
            in = in - (in * f); /* yes, subtract the amount wagered from the cumulative sum */
        }

        else
        {
            in = in + (in * f); /* yes, add the amount wagered to the cumulative sum */
        }

    }

    return (in); /* return the final value of cash reserves */
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

/*

double golden (double ax, double bx, double cx, double f, double tol)

given a bracketing triplet of abscissas ax, bx, cx, (such that bx is
between ax and cx, and f(bx) is less than both f(ax) and f(cx), this
routine performs a golden section search for the minimum, isolating it
to a fractional precision of about tol. The abscissa of the minimum is
returned.  See "Numerical Recipes in C: The Art of Scientific
Computing," William H. Press, Brian P. Flannery, Saul A. Teukolsky,
William T. Vetterling, Cambridge University Press, New York, 1988,
ISBN 0-521-35465-X, page 298.

Modified to accommodate the multiple arguments of tsunfairbrownian (),
and return the abscissa of the minimum-the reciprocal of the function
tsunfairbrownian () is used so that golden () can find the minimum

*/

#define R 0.61803399
#define C (1.0 - R)
#define SHFT(a,b,c,d) (a)=(b);(b)=(c);(c)=(d);

#ifdef __STDC__

static double golden (double ax, double bx, double cx, double tol)

#else

static double golden (ax, bx, cx, tol)
double ax;
double bx;
double cx;
double tol;

#endif

{
    double f0,
           f1,
           f2,
           f3,
           x0,
           x1,
           x2,
           x3;

    x0 = ax; /* at any given time we will keep track of four points, x0, x1, x2, x3 */
    x3 = cx;

    if (fabs (cx - bx) > fabs (bx - ax)) /* make x0 to x1 the smaller segment, and fill in the new point to be tried */
    {
        x1 = bx;
        x2 = bx + C * (cx - bx);
    }

    else
    {
        x2 = bx;
        x1 = bx - C * (bx - ax);
    }

    f1 = (double) 1.0 / unfairbrownian (x1, i, array, count); /* the initial function evaluations, note that we never need to evaluate the function at the original endpoints */
    f2 = (double) 1.0 / unfairbrownian (x2, i, array, count); /* the initial function evaluations, note that we never need to evaluate the function at the original endpoints */

    while (fabs (x3 - x0) > tol * (fabs (x1) + fabs (x2)))
    {

        if (f2 < f1) /* one possible outcome, its housekeeping, and a new function evaluation */
        {
            SHFT(x0, x1, x2, R * x1 + C * x3)
            SHFT(f0, f1, f2, (double) 1.0 / unfairbrownian (x2, i, array, count))
        }

        else /* the other outcome, and its new function evaluation */
        {
            SHFT(x3, x2, x1, R * x2 + C * x0)
            SHFT(f3, f2, f1, (double) 1.0 / unfairbrownian (x1, i, array, count))
        }

    }

    if (f1 < f2) /* back to see if we are done, if we are, output the best of the two current values */
    {
        return (x1);
    }

    else
    {
        return (x2);
    }

#ifdef LINT

    return (0); /* for lint formalities */

#endif

}
