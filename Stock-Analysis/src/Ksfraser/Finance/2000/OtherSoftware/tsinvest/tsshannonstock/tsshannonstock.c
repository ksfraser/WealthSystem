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

tsshannonstock.c is for simulating the gains of a stock investment
using Shannon probability. See "Fractals, Chaos, Power Laws," Manfred
Schroeder, W. H. Freeman and Company, New York, New York, 1991, ISBN
0-7167-2136-8, pp 128, 151.

The derivation of the algorithm used is:

    Let I(t) be the amount of capital at time t.

    Let W(t) be the amount of the capital wagered at time t.

    Let V(t) be the price of the stock at time t.

    Let F be the fraction of the captial wagered at any time, and is
    assumed not to be a function of time.

        W(t) = F * I(t - 1)

                              V(t) - V(t - 1)
        I(t) = I(t - 1) + W * ---------------
                                 V(t - 1)

                                         V(t) - V(t - 1)
        I(t) = I(t - 1) + F * I(t - 1) * ---------------
                                            V(t - 1)

          I(t)             V(t) - V(t - 1)
        -------- = 1 + F * ---------------
        I(t - 1)              V(t - 1)

    If it is assumed that the stock's price time series can be represented
    as a Brownian noise factal, then the optimum value of F would be:

        F = (2 * P) - 1

    where P is the Shannon probability of the time series, found by:

            avg
            --- + 1
            rms
        P = -------
               2

    where avg is the average, and rms is the root mean square, of the
    normalized increments of the stock's price time series, which can
    be calculated by

        V(t) - V(t - 1)
        ---------------
           V(t - 1)

    for each data point in the time series.

    Represented in pseudo code:

        1) for each data point in the stock's price time series,
        find the normalized increment from the following equation:

            V(t) - V(t - 1)
            ---------------
               V(t - 1)

        2) calculate the average of all normalized increments in the
        stock's price time series by the following equation:

                      n
                    -----
                  1 \     V(t) - V(t - 1)
            avg = -  >    ---------------
                  n /        V(t - 1)
                    -----
                    i = 0

        3) calculate the root mean square of all normalized increments
        in the stock's price time series by the following equation:

                       n
                     -----                     2
               2   1 \      [ V(t) - V(t - 1) ]
            rms  = -  >     [ --------------- ]
                   n /      [   V(t - 1)      ]
                     -----
                     i = 0

        4) calculate the Shannon probability by the following
        equation:

                      avg
                      --- + 1
                      rms
            shannon = -------
                         2

        5) calculate the optimal fraction of the capital to be wagered
        by the following equation:

            fraction = (2 * shannon) - 1

        6) since the stock's price time series already has a value
        rms as the root mean square of the normalized increments, for
        the optimal wagering strategy, the fraction should be divided
        by rms to provide a multiplier:

            multiplier = fraction / rms

        so that:

              I(t)                      V(t) - V(t - 1)
            -------- = 1 + multiplier * ---------------
            I(t - 1)                       V(t - 1)

    What this means is that if you have capital, (ie, a portfolio,)
    I(t), the fraction of I(t) that should be wagered with each
    iteration of the game, (ie., time unit,) would be twice the
    Shannon probability minus unity, where the capital, (or
    portfolio,) is the sum total of cash on hand, C(t), and the
    current value of stocks held, V(t) * N, where N is the number of
    stocks held, or:

        I(t) = C(t) + V(t) * N

    It would be convenient, from a comparative standpoint, to let
    I(0), the beginning capital, be the same as V(0), the price of the
    stock at the beginning of the simulation, so that the wagering
    strategy can be compared to the price of the stock over time.

    N will be adjusted for the next game, (time unit,) such that:

                   I(t) * F
        N(t + 1) = --------
                     V(t)

    where, as above, F is the fraction of capital, (portfolio,) to
    be wagered:

            F = fraction = (2 * P) - 1 = (2 * shannon) -1

    It would, additionally, for the simulation, be convenient, from an
    information-theoretic standpoint, to let F be a fraction, (either
    larger or smaller,) of the root mean square value of the
    normalized increments of the stock's price time series, ie., let F
    = f * rms, where f is a constant value, (usually around unity,)
    and rms is the average of the root mean square value of the
    normalized increments of the stock's price time series. This would
    allow a comparison of the stock's price, over time, to the
    capital, over time, with a wagering strategy that is optimal for a
    stock price that is characterized as a Brownian motion fractal
    over time.

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
$Id: tsshannonstock.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsshannonstock.c,v $
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

static char rcsid[] = "$Id: tsshannonstock.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Simulate the optimal gains of a stock investment using Shannon probability\n",
    "Usage: tsshannonstock [-f fraction] [-i value] [-n] [-p] [-T] [-t] [-v]\n",
    "                      [filename]\n",
    "    -f fraction, optimal incremental changes are multiplied by fraction\n",
    "    -i value, initial value of capital\n",
    "    -n, print the (number held @ price = value of stocks) + cash = capital\n",
    "    -p, print the fraction of capital to be wagered and the Shannon probability\n",
    "    -T, print the theoretical capability of the stock, instead of the\n",
    "        simulation\n",
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

static void print_message (int retval); /* print any error messages */
static int strtoken (char *string, char *parse_array, char **parse, char *delim);

#else

static void print_message (); /* print any error messages */
static int strtoken ();

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
        retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        i, /* loop counter */
        n = 0, /* print the number of stocks held? */
        p = 0, /* print only the fraction of capital to be wagered and the Shannon probability */
        T = 0, /* print the theoretical capability of the stock, instead of the simulation */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           increment, /* value of the normalized increment of a sample in the time series */
           *value = (double *) 0, /* reference to array of data values from file */
           *position = (double *) 0, /* reference to array of time/position values from the file */
           *ref = (double *) 0, /* last reference to array of data from file */
           f = (double) 1.0, /* fraction of change in incremental changes */
           sum = (double) 0.0, /* running value of cumulative sum */
           sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           avg, /* value of the average of the increments of the time series */
           rms, /* value of root mean square of the increments of the time series */
           shannon, /* the Shannon probability, as calculated by (((avg / rms) + 1) / 2) */
           fraction, /* the optimal fraction to be wagered, as calculated by fraction = twice the Shannon probability minus one */
           multiplier, /* the amount the root mean squared value of the normalized increments is to be increased/decreased by */
           capital = (double) 0.0, /* running value of the capital */
           lastcapital = (double) 0.0; /* the last value of the capital */

    FILE *infile; /* reference to input file */

    while ((c = getopt (argc, argv, "f:i:npTtv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'f': /* request for fraction of change in incremental changes */

                f = atof (optarg); /* yes, set the fraction of change in incremental changes */
                break;

            case 'i': /* request for initial value of capital? */

                capital = atof (optarg); /* yes, set the initial value of capital */
                break;

            case 'n': /* request for print only the number of stocks held? */

                n = 1; /* yes, set the print the number of stocks held flag */
                break;

            case 'p': /* request for print only the fraction of capital to be wagered and the Shannon probability? */

                p = 1; /* yes, set the print only the fraction of capital to be wagered and the Shannon probability flag */
                break;

            case 'T': /* request print the theoretical capability of the stock, instead of the simulation? */

                T = 1; /* yes, set the print the theoretical capability of the stock, instead of the simulation flag */
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
                        currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                        if (count != 0) /* not first record? */
                        {
                            increment = ((currentvalue - lastvalue) / lastvalue); /* save the value of the normalized increment of a sample in the time series */
                            sum = sum + increment; /* add the value of the normalized increment of a sample in the time series to the running value of cumulative sum */
                            sumsquared = sumsquared + (increment * increment); /* add the square of the value of the normalized increment of a sample in the time series to the running value of cumulative sum of squares */
                        }

                        ref = value; /* save the last reference to array of data from file */

                        if ((value = (double *) realloc (value, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of data values from the input file */
                        {
                            value = ref; /* couldn't allocate space for the array of data values from the input file, restore the last reference to array of data from file */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        value[count] = currentvalue; /* save the sample's value */

                        if (t == 1) /* print time of samples? */
                        {
                            ref = position; /* save the last reference to array of data from file */

                            if ((position = (double *) realloc (position, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of time/position values from the input file */
                            {
                                position = ref; /* couldn't allocate space for the array of time/position values from the input file, restore the last reference to array of time/position from file */
                                retval = EALLOC;  /* assume error allocating memory */
                                break; /* and stop */
                            }

                            if (fields > 1) /* yes, more that one field? */
                            {
                                position[count] = atof (token[0]); /* yes, save the sample's time/position */
                            }

                            else
                            {
                                position[count] = (double) count; /* no, save the sample's time/position which is assumed to be the record count */
                            }

                        }

                        lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            avg = sum / (double) count; /* save the value of the average of the increments of the time series */
            rms = sqrt (sumsquared / (double) count); /* save the value of root mean square of the time series */
            shannon = (((avg / rms) + (double) 1.0) / (double) 2.0); /* calculate the shannon probability, as calculated by (((avg / rms) + 1) / 2) */
            fraction = ((double) 2.0 * shannon) - (double) 1.0; /* calculate the optimal fraction to be wagered, as calculated by fraction = twice the Shannon probability minus one */
            multiplier = fraction / rms; /* the amount the root mean squared value of the normalized increments is to be increased/decreased by */

            if (p == 1) /* print only the fraction of capital to be wagered and the Shannon probability flag set? */
            {
                (void) printf ("%f = (2 * %f) - 1\n", fraction, shannon); /* yes, print only the fraction of capital to be wagered and the Shannon probability */
            }

            else
            {

                if (capital == (double) 0.0) /* initial value of capital set? */
                {
                    capital = value[0]; /* no, save the beginning capital, assumed to be the value of the stock at time zero */
                }

                if (T == 1) /* print the theortical capability of the stock, instead of the simulation flag set? */
                {

                    for (i = 0; i < count; i ++) /* for each record in the input file */
                    {

                        if (i != 0) /* if not the first record in the input file */
                        {

                            if (value[i] > value[i - 1]) /* stock value increase? */
                            {
                                capital = capital * (value[i] / value[i - 1]); /* yes, the capital raised by the amount the stock raised, since there is a hundred percent of the capital invested in the stock, else it remains constant, ie., it was sold at the last high, and repurchased in the interval preceeding the raise in value */
                            }

                        }

                        if (t == 1) /* print time of samples? */
                        {
                            (void) printf ("%f\t", position[i]); /* print the time/position of the record in the input file */
                        }

                        (void) printf ("%f\n", capital); /* print the capital */
                        lastcapital = capital; /* save the last value of the capital */
                    }

                }

                else
                {

                    for (i = 0; i < count; i ++) /* for each record in the input file */
                    {

                        if (i != 0) /* if not the first record in the input file */
                        {
                            capital = lastcapital * (1 + (f * multiplier * ((value[i] - value[i - 1]) / value[i - 1]))); /* calculate the current value of the capital */
                        }

                        if (t == 1) /* print time of samples? */
                        {
                            (void) printf ("%f\t", position[i]); /* print the time/position of the record in the input file */
                        }

                        if (n == 1) /* print number of stocks held? */
                        {
                            (void) printf ("(%f @ %f = %f) + %f = ", (capital * f * multiplier * rms) / value[i], value[i], capital * f * multiplier * rms, capital * (1 - (f * multiplier * rms))); /* yes, print the number of stocks held, which is the root means square value of the normalized increments multiplied by the multiplier, and divided by the current value of the stock */
                        }

                        (void) printf ("%f\n", capital); /* print the capital */
                        lastcapital = capital; /* save the last value of the capital */
                    }

                }

            }

            if (value != (double *) 0) /* allocated space for the array of data values from the input file? */
            {
                free (value); /* yes, free the space for the array of data values from the input file */
            }

            if (position != (double *) 0) /* allocated space for the array of time/position values from the input file? */
            {
                free (position); /* yes, free the space for the array of time/position values from the input file */
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
