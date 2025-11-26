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

tsstockwager.c, stock capital investment simulation. The idea is to
simulate an optimal wagering strategy, dynamically determining the
Shannon probability by counting the up movements in a stock's value in
a window from the stock's value time series, and using this to compute
the fraction of the total capital to be invested in the stock for the
next iteration of the time series, which is 2 * P - 1, where P is the
Shannon probability. See, "Fractals, Chaos, Power Laws," Manfred
Schroeder, W. H. Freeman and Company, New York, New York, 1991, ISBN
0-7167-2136-8, pp 128, 151. The assumption is that a stock's price
time series could be modeled as a fixed increment fractal.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

$Revision: 0.0 $
$Date: 2006/01/10 07:18:52 $
$Id: tsstockwager.c,v 0.0 2006/01/10 07:18:52 john Exp $
$Log: tsstockwager.c,v $
Revision 0.0  2006/01/10 07:18:52  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

#ifndef __STDC__

#include <malloc.h>

#endif

static char rcsid[] = "$Id: tsstockwager.c,v 0.0 2006/01/10 07:18:52 john Exp $"; /* program version */
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
    "Simulate the portfolio gains time series of a stock\n",
    "Usage: tsstockwager [-c] [-d] [-f f] [-i i] [-p] [-s] [-t] [-u]\n",
    "                    [-w w] [-v] [filename]\n",
    "    -c, sample's value will be included in the output time series\n",
    "    -d, capital gains will be included in the output time series\n",
    "    -f f, fraction of capital invested will be included in the output time\n",
    "          series\n",
    "    -i i, initial value of capital\n",
    "    -p, current value of stock will be included in the output time series\n",
    "    -s, sample's Shannon probability will be included in the output time series\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -u, sequential elements of equal magnitude will be counted as up movement\n",
    "    -w w, window sample size for Shannon probability computation\n",
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
static double p (double *current_value, int window_size, double *window_array, int u);
static int strtoken (char *string, char *parse_array, char **parse, char *delim);

#else

static void print_message (); /* print any error messages */
static double p ();
static int strtoken ();

#endif

#ifdef __STDC__

int main (int argc,char *argv[])

#else

int main (argc,argv)
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
        d = 0, /* print capital gains, 0 = no, 1 = yes */
        f = 0, /* print fraction of capital that should be invested, 0 = no, 1 = yes */
        b = 0, /* print current value of investment in stock, 0 = no, 1 = yes */
        s = 0, /* print the Shannon probability of samples flag, 0 = no, 1 = yes */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        u = 0, /* sequential elements with the same magnitude will be classed as an up movement in stock price, 0 = no, 1 = yes */
        v = 0, /* print the current value of samples flag, 0 = no, 1 = yes */
        w = 3, /* window size flag, how many time samples are to be included in the calculation of the Shannon probability */
        c; /* command line switch */

    double *window_array, /* reference to window array */
           i = (double) 100000.0, /* initial value of capital, capital that is not invested in stock */
           initial = (double) 100000.0, /* initial value of capital */
           value = (double) 0.0, /* capital invested in stock */
           currentvalue = (double) 0.0, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of the last sample in the time series */
           fraction = (double) 0.0, /* fraction of capital that should be invested */
           shannon = (double) 0.0, /* Shannon probability of elements of time series in window array */
           wager = (double) 0.0; /* amount that should be wagered for this time series period */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "cdfi:pstuw:v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'c': /* request printing the current values of samples? */

                v = 1; /* yes, set the print the current values of samples flag */
                break;

            case 'd': /* request printing capital gains? */

                d = 1; /* yes, set the print capital gains flag */
                break;

            case 'f': /* request printing fraction of capital that should be invested of samples? */

                f = 1; /* yes, set the print fraction of capital that should be invested of samples flag */
                break;

            case 'i': /* request for initial value of capital? */

                i = atof (optarg); /* yes, set the initial value of capital */
                initial = i; /* save the initial value of capital */
                break;

            case 'p': /* request for printing current value of investment in stock? */

                b = 1; /* yes, set the print current value of investment in stock flag */
                break;

            case 's': /* request printing Shannon probability of samples? */

                s = 1; /* yes, set the print Shannon probability of samples flag */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

            case 'u': /* request for sequential elements with the same magnitude will be classed as an up movement in stock price? */

                u = 1; /* yes, set the sequential elements with the same magnitude will be classed as an up movement in stock price flag */
                break;

            case 'w': /* request for window size? */

                w = atoi (optarg); /* yes, set the window size flag */
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
            retval = EALLOC;  /* assume error allocating memory */

            if ((window_array = (double *) malloc ((w + 1) * sizeof (double))) != (double *) 0) /* allocate space for the window array */
            {
                retval = NOERROR; /* assume no error */

                while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* for each record in the input file */
                {

                    if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                    {

                        if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                        {
                            currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                            if (currentvalue == (double) 0.0) /* a value of zero is probably a data error, is this one of those? */
                            {
                                continue; /* yes, skip the record */
                            }

                            if (count != 0) /* any records? */
                            {

                                value = value * (currentvalue / lastvalue); /* yes, calculate the current value of the capital that is invested in stock */
                            }

                            shannon = p (&currentvalue, w, window_array, u); /* get the Shannon probability for this window */

                            if (shannon <= (double) 0.5) /* Shannon probability less than or equal to 0.5? */
                            {
                                fraction = (double) 0.0; /* yes, make no wager */
                            }

                            else
                            {
                                fraction = ((double) 2.0 * shannon) - (double) 1.0; /* no, calculate the fraction of capital that should be invested */
                            }

                            if (t == 1) /* no, print time of samples? */
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

                            if (v == 1) /* print current value of samples? */
                            {
                                (void) printf ("%f\t", currentvalue); /* yes, print the current value of the sample */
                            }

                            if (b == 1) /* print current value of capital invested in stock? */
                            {
                                (void) printf ("%f\t", value); /* yes, print the current value of capial invested in stock */
                            }

                            if (s == 1) /* print Shannon probability of samples? */
                            {

                                if (shannon == (double) -1.0) /* yes, Shannon probability set yet? */
                                {
                                    (void) printf ("%f\t", (double) 0.0); /* no, print the Shannon probability of zero */
                                }

                                else
                                {
                                    (void) printf ("%f\t", shannon); /* yes, print the Shannon probability of the sample */
                                }

                            }

                            if (f == 1) /* print fraction of capital that should be invested? */
                            {
                                (void) printf ("%f\t", fraction); /* yes, print the fraction of capital that should be invested */
                            }

                            if (d == 1) /* print capital gains? */
                            {
                                (void) printf ("%f\t", i + value - initial); /* yes, print the capital gains */
                            }

                            (void) printf ("%f\n", i + value); /* print the current value of the capital */

                            wager = (i + value) * fraction; /* calculate the amount of money that should be invested in the stock */

                            if (wager > value) /* amount of money that should be invested in the stock is less than what is invested in the stock */
                            {
                                i = i - (wager - value); /* yes, decrement the capital that is not invested in stock by the amount of the difference between what is should be wagered and what is invested in stock */
                            }

                            else
                            {
                                i = i + (value - wager); /* no, increment the capital that is not invested in stock by the amount of the difference between what is invested in stock and what should be wagered */
                            }

                            value = wager; /* and add it to the capital that should be invested in stock */
                            lastvalue = currentvalue; /* new value of the last sample in the time series */
                            count ++; /* increment the count of records from the input file */
                        }

                    }

                }

                free (window_array); /* free the window array */
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

Returns the Shannon probability of the last window_size elements from
the time series. The Shannon probability is calculated by the number
of "up" movements in the last window_size elements of the time series,
divided by window_size.

*/

#ifdef __STDC__

static double p (double *current_value, int window_size, double *window_array, int u)

#else

static double p (current_value, window_size, window_array, u)
double *current_value;
int window_size;
double *window_array;
int u;

#endif

{
    static int count = 0, /* count of elements in time series */
               idx = 0; /* implicit address of the next element in window array */

    int current, /* implicit address of an element in the window array */
        next, /* implicit address of the next element in the window array */
        element, /* element counter in window array */
        up; /* number of positive movements in window array */

    double retval = (double) -1.0; /* return value, assume not enough elements from time series in window array */

    window_array[idx] = *current_value; /* save the value of the current element in the time series */
    idx ++; /* increment the implicit address of the next element in window array */

    if (idx > window_size) /* implicit address of the next element in window array beyond last element in window array? */
    {
        idx = 0; /* yes, implicit address of the next element in window array is the first element in the array */
    }

    if (count >= window_size)
    {
        up = 0; /* no number of positive movements in window array, yet */
        current = idx; /* reference the oldest element from the time series in the window array */

        for (element = 0; element < window_size; element ++)
        {
            next = current + 1; /* implicitly address the next element in the window array */

            if (next > window_size) /* implicit address of the next element in the window array beyond last element in window array? */
            {
                next = 0; /* yes, the next implicit address of the next element in the window array is the first element in the window array */
            }

            if (u == 1) /* sequential elements with the same magnitude will be classed as an up movement in stock price flag set */
            {

                if (window_array[next] >= window_array[current]) /* yes, up movement between current and next element in the window array? */
                {
                    up ++; /* yes, include the up movement in the count the up movements */
                }

            }

            else
            {

                if (window_array[next] > window_array[current]) /* up movement between current and next element in the window array? */
                {
                    up ++; /* yes, include the up movement in the count the up movements */
                }

            }

            current ++; /* increment the implicit address of the next element in the window array */

            if (current > window_size) /* implicit address of the next element in the window array beyond last element in window array? */
            {
                current = 0; /* yes, the next implicit address of the next element in the window array is the first element in the window array */
            }

        }

        retval = (double) ((double) up / (double) window_size); /* calculate the Shannon probability of the window array */
    }

    count ++; /* increment the count of elements in time series */
    return (retval);
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
