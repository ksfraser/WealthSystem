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

tsshannonwindow.c for finding the windowed Shannon probability of a
time series.  The Shannon probability is calculated by the following
method:

    1) For each sample in the time series:

        a) Find the value of the sample's normalized increment by
        subtracting the previous value of the time series from the
        current value of the time series, and then dividing this value
        of the increment by the previous value in the time series,
        (note that this is similar to the procedure used by the
        program tsfraction(1)).

        b) Find the running value of the root mean square of a window
        of the normalized increments, (note that this is similar to
        the procedure used by the program tsrmswindow(1)).

        c) Find the running value of the average of a window of the
        normalized increments, (note that this is similar to the
        procedure used by the program tsavgwindow(1)).

    2) Compute the Shannon probability of the windows by eight
    methods:

        a) using the formula:

                avg
                --- + 1
                rms
            P = -------
                   2

        b) using the formula:

                rms + 1
            P = -------
                   2

        c) using the formula:

                sqrt (avg) + 1
            P = --------------
                      2

        d) by taking the absolute value of the normalized increments
           and using the formula:

                abs + 1
            P = -------
                   2


            (Note that the absolute value of the
            normalized increments, when averaged, is
            related to the root mean square of the
            increments by a constant. If the normalized
            increments are a fixed increment, the constant
            is unity. If the normalized increments have a
            Gaussian distribution, the constant is ~0.8
            depending on the accuracy of of "fit" to a
            Gaussian distribution. This formula assumes a
            fixed increment fractal.)

        e) counting the up movements in the window of the time series,
           and considering adjacent elements from the time series with
           equal magnitude as an up movement.

        f) counting the up movements in the window of the time series,
           and considering adjacent elements from the time series with
           equal magnitude as a down movement.

        g) finding an exponential least squares fit of the values of
           the time series in a window, and iteratively calculating
           the Shannon probability from the least squares fit variable
           using Newton-Raphson method for finding the roots of a
           function.

        h) finding the logarithmic returns of the values of the time
           series in a window, and iteratively calculating the Shannon
           probability from the least squares fit variable using
           Newton-Raphson method for finding the roots of a function.

Where P is the Shannon probability, avg is the running average of a
window of the normalized increments, rms is the running root mean
square of a window of the increments, and abs is the absolute value of
a window of the increments.  The Shannon probability of the windows of
the increments is a time series that is printed to stdout.

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

where fit (t) is the least squares exponential fit.

Note: The derivation for exponential least squares fit is:

    1) y[t] = exp (k1 + k2t)

    2) s[t] = log (exp (k1 + k2t) / exp (k1 + k2 (t - 1)))
            = log (exp (k1 + k2t - k1 - k2t + k2))
            = log (exp (k2))
            = k2

    And for the binary least squares fit, letting k = k2:

    1) compute the least squares fit, as above

    2) exp (xt) = pow (2, kt)

    3) pow (a, t) = pow (2, kt)

    4) a = pow (2, k)

    5) k * log (2) = log (a)

    6) k = log (a) / log (2)

Note: The derivation for calculating the Shannon probability, given
the Shannon information capacity, where the information capacity is
the exponent derived from the least squares fit to the values of the
time series, divided by the natural logarithm of two. See "Fractals,
Chaos, Power Laws," Manfred Schroeder, W. H. Freeman and Company, New
York, New York, 1991, ISBN 0-7167-2136-8, pp 128 and pp 151. Uses
Newton-Raphson method for an iterative solution for the probability,
p.

As a reference on Newton-Raphson Method of root finding, see
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, pp
270.

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
$Id: tsshannonwindow.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsshannonwindow.c,v $
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

static char rcsid[] = "$Id: tsshannonwindow.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#define EPS (double) DBL_EPSILON * (double) 1000000.0 /* epsilon accuracy for final iteration */
#define P_START (double) 0.75 /* since p must be between 0.5 and 1.0, start with initial iteration of mid way */

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b," /* file record field separators */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Find the windowed Shannon probability of a time series\n",
    "Usage: tsshannonwindow [-a] [-b] [-c] [-d] [-e] [-f] [-g] [-h] [-t]\n",
    "                       [-v] [-w size] [filename]\n",
    "    -a, Shannon probability = ((avg / rms) + 1) / 2\n",
    "    -b, Shannon probability = (rms + 1) / 2\n",
    "    -c, Shannon probability = (sqrt (avg) + 1) / 2\n",
    "    -d, Shannon probability = (abs + 1) / 2\n",
    "    -e, Shannon probability = number of up movements (equal = up)\n",
    "    -f, Shannon probability = number of up movements (equal = down)\n",
    "    -g, Shannon probability = iterated exponential least squares fit\n",
    "    -h, Shannon probability = iterated mean of logarithmic returns\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -v, print the program's version information\n",
    "    -w size, specifies the window size for the running average\n",
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

typedef struct element /* structure for the root mean square and average of the normalized increments between two consecutive elements from the time series */
{
    double avg; /* current value of the root mean square of a normalized increment in time series */
    double rms; /* current value of the average of a normalized increment in time series */
    double absolute; /* current value of the absolute value of a normalized increment in time series */
    int up; /* current movement of adjacent elements in the time series, 1 = up, 0 = down, with adjacent elements from the time series with equal magnitutes considered an up movement */
    int down; /* current movement of adjacent elements in the time series, 1 = up, 0 = down, with adjacent elements from the time series with equal magnitutes considered a down movement */
    double value; /* current value in time series */
    double position; /* current value of the time time series */
    double lastvalue; /* last value in time series */
} DATA;

static double log_2 = (double) 0.0, /* 1 / log (2), for computations */
       capacity; /* Shannon information capacity */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static double function (double p);
static double derivative (double p);
static int strtoken (char *string, char *parse_array, char **parse, char *delim);
static void print_shannons (int t, int count, char *token[BUFLEN / 2], int fields, int w, double sum, double sumsquared, double absolutevalue, DATA *window, int a, int b, int c, int d, int e, int f, int g, int h);
static int windowed (FILE *infile, int w, int t, int a, int b, int c, int d, int e, int f, int g, int h);
static int nonwindowed (FILE *infile, int t, int a, int b, int c, int d, int e, int f, int g, int h);

#else

static void print_message (); /* print any error messages */
static double function ();
static double derivative ();
static int strtoken ();
static void print_shannons ();
static int windowed ();
static int nonwindowed ();

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
        a = 0, /* calculate the Shannon probability using method a flag, 0 = no, 1 = yes */
        b = 0, /* calculate the Shannon probability using method b flag, 0 = no, 1 = yes */
        c = 0, /* calculate the Shannon probability using method c flag, 0 = no, 1 = yes */
        d = 0, /* calculate the Shannon probability using method d flag, 0 = no, 1 = yes */
        e = 0, /* calculate the Shannon probability using method e flag, 0 = no, 1 = yes */
        f = 0, /* calculate the Shannon probability using method f flag, 0 = no, 1 = yes */
        g = 0, /* calculate the Shannon probability using method g flag, 0 = no, 1 = yes */
        h = 0, /* calculate the Shannon probability using method h flag, 0 = no, 1 = yes */
        w = 0, /* window size for the running average, 0 means to use entire time series */
        x = 0, /* any calculate the Shannon probability method flags, 0 = no, 1 = yes */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        z; /* command line switch */

    FILE *infile = stdin; /* reference to input file */

    log_2 = (1 / log ((double) 2.0)); /* 1 / log (2), for computations */

    while ((z = getopt (argc, argv, "abcdefghtvw:")) != EOF) /* for each command line switch */
    {

        switch (z) /* which switch? */
        {

            case 'a': /* request for Shannon probability using method a? */

                a = 1; /* yes, set the calculate the Shannon probability using method a flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 'b': /* request for Shannon probability using method b? */

                b = 1; /* yes, set the calculate the Shannon probability using method b flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 'c': /* request for Shannon probability using method c? */

                c = 1; /* yes, set the calculate the Shannon probability using method c flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 'd': /* request for Shannon probability using method d? */

                d = 1; /* yes, set the calculate the Shannon probability using method d flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 'e': /* request for Shannon probability using method e? */

                e = 1; /* yes, set the calculate the Shannon probability using method e flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 'f': /* request for Shannon probability using method f? */

                f = 1; /* yes, set the calculate the Shannon probability using method f flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 'g': /* request for Shannon probability using method g? */

                g = 1; /* yes, set the calculate the Shannon probability using method g flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 'h': /* request for Shannon probability using method h? */

                h = 1; /* yes, set the calculate the Shannon probability using method h flag */
                x = 1; /* set the any calculate the Shannon probability method flags */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

            case 'w': /* request for window size for the running average */

                w = atoi (optarg); /* yes, set the window size for the running average */
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

    if (x == 0) /* calculate the Shannon probability method flags set? */
    {
        a = 1; /* no, default is to calculate the Shannon probability using method a, set the flag */
    }

    if (retval == NOERROR) /* enough arguments? */
    {
        retval = EOPEN; /* assume error opening file */

        if ((infile = argc <= optind ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the input file */
        {
            retval = NOERROR; /* assume no error */

            if (w == 0) /* window size flag not set yet? */
            {
                retval = nonwindowed (infile, t, a, b, c, d, e, f, g, h); /* a window size has been specified, pass all arguments to nonwindowed () */
            }

            else
            {
                retval = windowed (infile, w, t, a, b, c, d, e, f, g, h); /* a window size has been specified, pass all arguments to windowed () */
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

print driver for output of various Shannon probabilities

static void print_shannons (int t, int count, char *token[BUFLEN / 2], int fields, int w, double sum, double sumsquared, double absolutevalue, DATA *window, int a, int b, int c, int d, int e, int f, int g, int h)

the variables from main (), and either windowed (), or nonwindowed ()
are passed to this routine for formatting and printing-returns nothing

*/

#ifdef __STDC__

static void print_shannons (int t, int count, char *token[BUFLEN / 2], int fields, int w, double sum, double sumsquared, double absolutevalue, DATA *window, int a, int b, int c, int d, int e, int f, int g, int h)

#else

static void print_shannons (t, count, token, fields, w, sum, sumsquared, absolutevalue, window, a, b, c, d, e, f, g, h)
int t;
int count;
char *token[BUFLEN / 2];
int fields;
int w;
double sum;
double sumsquared;
double absolutevalue;
DATA *window;
int a;
int b;
int c;
int d;
int e;
int f;
int g;
int h;

#endif
{
    int preceeding = 0, /* preceeding field printed in a record, 1 = yes, 0 = no */
        upcount, /* count of up movements in sequential elements from the time series, with adjacent elements from the time series with equal magnitude considered an up movement */
        downcount, /* count of up movements in sequential elements from the time series, with adjacent elements from the time series with equal magnitude considered a down movement */
        i; /* loop counter */

    double avg, /* average value of a window of the normalized increments of the time series */
           rms, /* root mean square value of a window of the normalized increments of the time series */
           Pa, /* Shannon probability calculated by method a */
           Pb, /* Shannon probability calculated by method b */
           Pc, /* Shannon probability calculated by method c */
           Pd, /* Shannon probability calculated by method d */
           Pe, /* Shannon probability calculated by method e */
           Pf, /* Shannon probability calculated by method f */
           Pg, /* Shannon probability calculated by method g */
           Ph, /* Shannon probability calculated by method h */
           sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           slope, /* slope of best fit line */
           value = DBL_MAX, /* return value from call to function (), less than eps will exit */
           eps = EPS, /* epsilon accuracy for final iteration */
           temp1, /* temporary double storage */
           temp2; /* temporary double storage */

    if (t == 1) /* print time of samples? */
    {

        if (fields > 1) /* yes, more that one field? */
        {
            (void) printf ("%s", token[0]); /* yes, print the sample's time */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

        else
        {
            (void) printf ("%d", count); /* no, print the sample's time which is assumed to be the record count */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    avg = sum / (double) w; /* calculate the average value of a window of the normalized increments of the time series */

    if (sumsquared < (double) 0.0) /* sumsquared can be negative do to numerical stability issues, sumsquard less than zero? */
    {
        rms = (double) 0.0; /* yes, fault condition on the square root involved in the calculation of rms, consider it zero */
    }

    else
    {
        rms = sqrt (sumsquared / (double) w); /* no, calculate the root mean squared value of a window of the normalized increments of the time series */
    }

    if (a == 1) /* calculate the Shannon probability using method a flag set? */
    {

        if (rms == (double) 0.0) /* rms equal to zero? */
        {
            Pa = (double) 0.0; /* yes, fault condition on the division involved in the calculation of Pa, consider rms as zero, and the Shannon probability as zero */
        }

        else
        {
            Pa = ((avg / rms) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability calculated by method a */
        }

        if (preceeding == 1) /* yes, any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Pa); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method a */
        }

        else
        {
            (void) printf ("%f", Pa); /* no, no preceeding field has been printed in this record, print the Shannon probability for method a */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (b == 1) /* calculate the Shannon probability using method b flag set? */
    {
        Pb = (rms + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability calculated by method b */

        if (preceeding == 1) /* yes, any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Pb); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method b */
        }

        else
        {
            (void) printf ("%f", Pb); /* no, no preceeding field has been printed in this record, print the Shannon probability for method b */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (c == 1) /* calculate the Shannon probability using method c flag set? */
    {

        if (avg < (double) 0.0) /* average of the normalized increments of a window less than zero? */
        {
            Pc = (double) 0.0; /* fault condition on the square root involved in the calculation of Pc, consider the Shannon probability 0.0 instead of imaginary since most programs reading the output file will probably not understand complex arithmetic */
        }

        else
        {
            Pc = (sqrt (avg) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability calculated by method c */
        }

        if (preceeding == 1) /* yes, any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Pc); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method c */
        }

        else
        {
            (void) printf ("%f", Pc); /* no, no preceeding field has been printed in this record, print the Shannon probability for method c */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (d == 1) /* calculate the Shannon probability using method d flag set? */
    {
        Pd = ((absolutevalue / (double) w) + (double) 1.0) / (double) 2.0 ; /* calculate the absolute value of a window of the normalized increments of the time series */

        if (preceeding == 1) /* yes, any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Pd); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method d */
        }

        else
        {
            (void) printf ("%f", Pd); /* no, no preceeding field has been printed in this record, print the Shannon probability for method d */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (e == 1) /* calculate the Shannon probability using method e flag set? */
    {
        upcount = 0; /* reset the count of up movements in sequential elements from the time series, with adjacent elements from the time series with equal magnitude considered an up movement  */

        for (i = 0; i < w; i ++) /* for each element in the window */
        {
            upcount = upcount + window[i].up; /* add the up movement to the count of up movements in sequential elements from the time series  with equal magnitute considered an up movement */
        }

        Pe = (double) ((double) upcount / (double) w); /* calculate the average number of up movements in the window with equal magnitute considered a down movement */

        if (preceeding == 1) /* yes, any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Pe); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method e */
        }

        else
        {
            (void) printf ("%f", Pe); /* no, no preceeding field has been printed in this record, print the Shannon probability for method e */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (f == 1) /* calculate the Shannon probability using method f flag set? */
    {
        downcount = 0; /* reset the count of up movements in sequential elements from the time series, with adjacent elements from the time series with equal magnitude considered a down movement */

        for (i = 0; i < w; i ++) /* for each element in the window */
        {
            downcount = downcount + window[i].down; /* add the up movement to the count of up movements in sequential elements from the time series  with equal magnitute considered a down movement */
        }

        Pf = (double) ((double) downcount / (double) w); /* calculate the average number of up movements in the window with adjacent elements from the time series with equal magnitute considered a down movement */

        if (preceeding == 1) /* yes, any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Pf); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method f */
        }

        else
        {
            (void) printf ("%f", Pf); /* no, no preceeding field has been printed in this record, print the Shannon probability for method f */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (g == 1) /* calculate the Shannon probability using method g flag set? */
    {
        sx = (double) 0.0; /* yes, reset the sum of the time values */
        sy = (double) 0.0; /* reset the sum of the data values */
        sxx = (double) 0.0; /* reset the sum of the time values squared */
        sxy = (double) 0.0; /* reset the sum of the data values * the time values */

        for (i = 0; i < w; i ++) /* for each element in the window */
        {
            temp1 = log (window[i].value); /* save the logarithm of the element value */
            temp2 = window[i].position; /* save the time of the element */
            sx += temp2; /* add the time value to the sum of the time values */
            sy += temp1; /* add the data value to the sum of the data values */
            sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
            sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */
        }

        det = (double) w * sxx - sx * sx; /* calculate the determinate in best fit calculations */

        if (det <= (double) 0.0) /* determinate less than or equal to zero? */
        {
            Pg = (double) 0.0; /* fault condition, divison by zero in calculation of slope, consider the Shannon probability zero */
        }

        else
        {
           slope = ((double) w * sxy - sx * sy) / det; /* calculate the slope of best fit line */

           if (slope <= EPS) /* slope less than epsilon accuracy for final iteration? */
           {
               Pg = (double) 0.0; /* fault condition, possibly numerical stability issues resulting in a hung program, or a numerical exception in the Newton iteration functions, consider the Shannon probability zero */
           }

           else
           {
               capacity = slope / log ((double) 2.0); /* Shannon information capacity */
               Pg = P_START; /* since p must be between 0.5 and 1.0, start with initial iteration of mid way */
               eps = EPS; /* epsilon accuracy for final iteration */
               value = DBL_MAX; /* return value from call to function (), less than eps will exit */

               while (fabs (value) > eps) /* while the return value from a call to function () is greater than eps */
               {
                   Pg = Pg - (value = (function (Pg) / derivative (Pg))); /* iterate the Newton loop */
               }

           }

        }

        if (preceeding == 1) /* any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Pg); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method g */
        }

        else
        {
            (void) printf ("%f", Pg); /* no, no preceeding field has been printed in this record, print the Shannon probability for method g */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (h == 1) /* calculate the Shannon probability using method h flag set? */
    {
        sy = (double) 0.0; /* yes, reset the sum of the data values */

        for (i = 0; i < w; i ++) /* for each element in the window */
        {

            if (window[i].lastvalue <= (double) 0.0 || window[i].value < (double) 0.0) /* can the logarithm of the quotient of the current value and last value of elements from the time series be taken? */
            {
                sy = -1; /* no, fault condition, set the slope to a negative value, and break this loop-this will set the Shannon probability zero */
                break;
            }

            sy = sy + log (window[i].value / window[i].lastvalue); /* add the sample's value to the sum of the data values for this window */
        }

        slope = sy / ((double) w); /* yes, compute the mean of the slope of the data values for this window */

        if (slope <= EPS) /* slope less than epsilon accuracy for final iteration? */
        {
            Ph = (double) 0.0; /* fault condition, possibly numerical stability issues resulting in a hung program, or a numerical exception in the Newton iteration functions, consider the Shannon probability zero */
        }

        else
        {
            capacity = slope / log ((double) 2.0); /* Shannon information capacity */
            Ph = P_START; /* since p must be between 0.5 and 1.0, start with initial iteration of mid way */
            eps = EPS; /* epsilon accuracy for final iteration */
            value = DBL_MAX; /* return value from call to function (), less than eps will exit */

            while (fabs (value) > eps) /* while the return value from a call to function () is greater than eps */
            {
                Ph = Ph - (value = (function (Ph) / derivative (Ph))); /* iterate the Newton loop */
            }

        }

        if (preceeding == 1) /* any other preceeding fields in this record? */
        {
            (void) printf ("\t%f", Ph); /* yes, at least one preceeding field has been printed in this record, add white space separation, and the Shannon probability for method h */
        }

        else
        {
            (void) printf ("%f", Ph); /* no, no preceeding field has been printed in this record, print the Shannon probability for method h */
            preceeding = 1; /* at least one preceeding field printed in this record */
        }

    }

    if (preceeding == 1) /* any preceeding fields in this record? */
    {
        (void) printf ("\n"); /* yes, terminate the record with an EOL */
    }

    return;
}

/*

construct the data set for the Shannon probabilities

static int windowed (FILE *infile, int w, int t, int a, int b, int c, int d, int e, int f, int g, int h)

the variables from main () are passed to this routine for construction
of the data structures for computation of all Shannon
probabilities-this routine is called if a window size was specified on
the command line-returns EALLOC if sufficient space could for the data
structure could not be allocated, NOERROR on success

*/

#ifdef __STDC__

static int windowed (FILE *infile, int w, int t, int a, int b, int c, int d, int e, int f, int g, int h)

#else

static int windowed (infile, w, t, a, b, c, d, e, f, g, h)
FILE *infile;
int w;
int t;
int a;
int b;
int c;
int d;
int e;
int f;
int g;
int h;

#endif

{
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         token_separators[] = TOKEN_SEPARATORS;

    int count = 0, /* input file record counter */
        element = 0, /* element counter in the array of the last w many elements from the time series */
        retval = EALLOC,  /* return value, assume error allocating memory */
        fields = 0; /* number of fields in a record */

    double sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           sum = (double) 0.0, /* running value of cumulative sum of squares */
           absolutevalue = (double) 0.0, /* running value of cumulative sum of absolute values */
           currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           increment = (double) 0.0, /* value of a normalized increment from the time series */
           temp; /* temporary double storage */

    DATA *window = (DATA *) 0; /* reference to the array of the last w many normalized increments from the time series */

    if ((window = (DATA *) malloc ((w) * sizeof (DATA))) != (DATA *) 0) /* allocate space for the array of the last w many normalized increments from the time series */
    {
        retval = NOERROR; /* assume no error */

        for (element = 0; element < w; element ++) /* for each element in the array of the last w many normalized increments from the time series */
        {
            window[element].rms = (double) 0.0; /* initialize each rms element to zero */
            window[element].avg = (double) 0.0; /* initialize each avg element to zero */
            window[element].absolute = (double) 0.0; /* initialize each absolute element to zero */
            window[element].up = 0; /*initialize each up element to zero */
            window[element].down = 0; /*initialize each down element to zero */
            window[element].value = (double) 0.0; /* initialize each value element to zero */
            window[element].position = (double) 0.0; /* initialize each position element to zero */
            window[element].lastvalue = (double) 0.0; /* initialize each lastvalue element to zero */
        }

        element = 0; /* reset the element counter in the array of the last w many elements from the time series */

        while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the records from the input file */
        {

            if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
            {

                if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                {
                    currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                    if (count > 0) /* not first record? */
                    {
                        increment = (currentvalue - lastvalue) / lastvalue; /* save the normalized increment of the element in the time series */

                        sum = sum - window[element].avg; /* subtract the value of the oldest average normalized increment in the time series from the cumulative sum of the normalized increments of the time series */
                        sum = sum + increment; /* add the value of the normalized increment of the current sample in the time series to the cumulative sum of the time series */
                        window[element].avg = increment; /* replace the oldest average value of the normalized increment in the time series with the current value of the normalized increment from the time series */

                        temp = increment * increment; /* save the square of the normalized increment of the current value in the time series */
                        sumsquared = sumsquared - window[element].rms; /* subtract the oldest square of the normalized increment in the time series from the cumulative sum of squares of the normalized increments of the time series */
                        sumsquared = sumsquared + temp; /* add the square of the normalized increment of the current sample in the time series to the cumulative sum of squares of the time series */
                        window[element].rms = temp; /* replace the oldest square value of the normalized increment in the time series with the current value of the normalized increment from the time series */

                        temp = fabs (increment); /* save the absolute value of the normalized increment of the current value in the time series */
                        absolutevalue = absolutevalue - window[element].absolute; /* subtract the value of the oldest absolute value normalized increment in the time series from the cumulative sum of the normalzed increments of the time series */
                        absolutevalue = absolutevalue + temp; /* add the value of the normalized increment of the current sample in the time series to the cumulative absolute value of the time series */
                        window[element].absolute = temp; /* replace the oldest absolute value of the normalized increment in the time series with the current value of the absolute value of the normalized increment from the time series */

                        if (currentvalue >= lastvalue) /* is the current value of the normalize increment greater than or equal to zero? */
                        {
                            window[element].up = 1; /* yes, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                        }

                        else
                        {
                            window[element].up = 0; /* no, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                        }

                        if (currentvalue > lastvalue) /* is the current value of the normailzed increment greater than zero? */
                        {
                            window[element].down = 1; /* yes, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                        }

                        else
                        {
                            window[element].down = 0; /* no, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                        }

                        window[element].value = currentvalue; /* save the current value in time series */

                        if (fields > 1) /* yes, more that one field? */
                        {
                            window[element].position = atof (token[0]); /* yes, save the current value of the time in time series */
                        }

                        else
                        {
                            window[element].position = (double) count; /* no, save the current value of the time in time series */
                        }

                        window[element].lastvalue = lastvalue; /* save the last value in the time series */

                        if (count >= w) /* yes, greater than w many records so far? */
                        {
                            print_shannons (t, count, token, fields, w, sum, sumsquared, absolutevalue, window, a, b, c, d, e, f, g, h); /* print the Shannon probabalities */
                        }

                        element ++; /* next element in the array of the last w many elements from the time series */

                        if (element >= w) /* next element in the array of the last w many elements from the time series greater than the array size? */
                        {
                            element = 0; /* yes, next element in the array of the last w many elements from the time series is the first element in the array */
                        }

                    }

                    lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                    count ++; /* increment the count of records from the input file */
                }

            }

        }

        if (window != (DATA *) 0) /* allocated space for the array of the last w many normalized increments from the time series? */
        {
            free (window); /*yes, free the space for the array of the last w many normalized increments from the time series */
        }

    }

    return (retval); /* return any errors */
}

/*

construct the data set for the Shannon probabilities

static int nonwindowed (FILE *infile, int t, int a, int b, int c, int d, int e, int f, int g, int h)

the variables from main () are passed to this routine for construction
of the data structures for computation of all Shannon
probabilities-this routine is called if a window size was not
specified on the command line-returns EALLOC if sufficient space could
for the data structure could not be allocated, NOERROR on success

*/

#ifdef __STDC__

static int nonwindowed (FILE *infile, int t, int a, int b, int c, int d, int e, int f, int g, int h)

#else

static int nonwindowed (infile, t, a, b, c, d, e, f, g, h)
FILE *infile;
int t;
int a;
int b;
int c;
int d;
int e;
int f;
int g;
int h;

#endif

{
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         token_separators[] = TOKEN_SEPARATORS;

    int count = 0, /* input file record counter */
        element = 0, /* element counter in the array of the last w many elements from the time series */
        retval = NOERROR,  /* return value, assume no error */
        fields = 0; /* number of fields in a record */

    double sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           sum = (double) 0.0, /* running value of cumulative sum of squares */
           absolutevalue = (double) 0.0, /* running value of cumulative sum of absolute values */
           currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           increment = (double) 0.0, /* value of a normalized increment from the time series */
           temp; /* temporary double storage */

    DATA *window = (DATA *) 0, /* reference to the array of the last w many normalized increments from the time series */
         *lastwindow; /* reference to the last array of the last w many normalized increments from the time series */

    while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the records from the input file */
    {

        if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
        {

            if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
            {
                currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                if (count > 0) /* not first record? */
                {
                    lastwindow = window; /* save the reference to the last array of the last w many normalized increments from the time series */

                    if ((window = (DATA *) realloc (window, (element + 1) * sizeof (DATA))) == (DATA *) 0) /* allocate space for the array of the last w many normalized increments from the time series */
                    {
                        window = lastwindow; /* restore the reference to the array of the last w many normalized increments from the time series */
                        retval = EALLOC;  /* assume error allocating memory */
                        break; /* and stop */
                    }

                    window[element].avg = (double) 0.0; /* initialize the last window's current value of the root mean square of a normalized increment in time series */
                    window[element].rms = (double) 0.0; /* initialize the last window's current value of the average of a normalized increment in time series */
                    window[element].absolute = (double) 0.0; /* initialize the last window's current value of the absolute value of a normalized increment in time series */
                    window[element].up = (int) 0; /* initialize the last window's current movement of adjacent elements in the time series, 1 = up, 0 = down, with adjacent elements from the time series with equal magnitutes considered an up movement */
                    window[element].down = (int) 0; /* initialize the last window's current movement of adjacent elements in the time series, 1 = up, 0 = down, with adjacent elements from the time series with equal magnitutes considered a down movement */
                    window[element].value = (double) 0.0; /* initialize the last window's current value in time series */
                    window[element].position = (double) 0.0; /* initialize the last window's current value of the time time series */
                    window[element].lastvalue = (double) 0.0; /* initialize the last window's last value in time series */

                    increment = (currentvalue - lastvalue) / lastvalue; /* save the normalized increment of the element in the time series */

                    sum = sum - window[element].avg; /* subtract the value of the oldest average normalized increment in the time series from the cumulative sum of the normalized increments of the time series */
                    sum = sum + increment; /* add the value of the normalized increment of the current sample in the time series to the cumulative sum of the time series */
                    window[element].avg = increment; /* replace the oldest average value of the normalized increment in the time series with the current value of the normalized increment from the time series */

                    temp = increment * increment; /* save the square of the normalized increment of the current value in the time series */
                    sumsquared = sumsquared - window[element].rms; /* subtract the oldest square of the normalized increment in the time series from the cumulative sum of squares of the normalized increments of the time series */
                    sumsquared = sumsquared + temp; /* add the square of the normalized increment of the current sample in the time series to the cumulative sum of squares of the time series */
                    window[element].rms = temp; /* replace the oldest square value of the normalized increment in the time series with the current value of the normalized increment from the time series */

                    temp = fabs (increment); /* save the absolute value of the normalized increment of the current value in the time series */
                    absolutevalue = absolutevalue - window[element].absolute; /* subtract the value of the oldest absolute value normalized increment in the time series from the cumulative sum of the normalzed increments of the time series */
                    absolutevalue = absolutevalue + temp; /* add the value of the normalized increment of the current sample in the time series to the cumulative absolute value of the time series */
                    window[element].absolute = temp; /* replace the oldest absolute value of the normalized increment in the time series with the current value of the absolute value of the normalized increment from the time series */

                    if (currentvalue >= lastvalue) /* is the current value of the normalize increment greater than or equal to zero? */
                    {
                        window[element].up = 1; /* yes, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                    }

                    else
                    {
                        window[element].up = 0; /* no, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                    }

                    if (currentvalue > lastvalue) /* is the current value of the normailzed increment greater than zero? */
                    {
                        window[element].down = 1; /* yes, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                    }

                    else
                    {
                        window[element].down = 0; /* no, save the current movement of adjacent elements in the time series, 1 = up, 0 = down */
                    }

                    window[element].value = currentvalue; /* save the current value in time series */

                    if (fields > 1) /* yes, more that one field? */
                    {
                        window[element].position = atof (token[0]); /* yes, save the current value of the time in time series */
                    }

                    else
                    {
                        window[element].position = (double) count; /* no, save the current value of the time in time series */
                    }

                    window[element].lastvalue = lastvalue; /* save the last value in the time series */

                    element ++; /* next element in the array of the last w many elements from the time series */
                }

                lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                count ++; /* increment the count of records from the input file */
            }

        }

    }

    if (retval == NOERROR) /* any errors? */
    {
        print_shannons (t, element, token, fields, element, sum, sumsquared, absolutevalue, window, a, b, c, d, e, f, g, h); /* no, print the Shannon probabalities */
    }

    if (window != (DATA *) 0) /* allocated space for the array of the last w many normalized increments from the time series? */
    {
        free (window); /*yes, free the space for the array of the last w many normalized increments from the time series */
    }

    return (retval); /* return any errors */
}
