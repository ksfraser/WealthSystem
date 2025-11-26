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

tshurst.c for calculating the Hurst coefficient for a time series.  The
method used is from "Complexification," John L. Casti, HarperCollins,
New York, New York, 1994, ISBN 0-06-016888-9, pp 253, or "Chaos and
Order in the Capital Markets," Edgar E. Peters, John Wiley & Sons, New
York, New York, 1991, ISBN 0-471-53372-6, pp 63, or "Fractals, Chaos,
Power Laws," Manfred Schroeder, W. H. Freeman and Company, New York,
New York, 1991, ISBN 0-7167-2136-8, pp 129, or "Applied Chaos Theory:
A Paradigm for Complexity," A. B. Cambel.  Academic Press, San Diego,
California, 1993, ISBN 0-12-155940-8, pp 172.  The time series is
broken into variable length intervals, which are assumed to be
independent of each other, and the R/S value is computed for each
interval based on the deviation from the average over the
interval. These R/S values are then averaged for all of the intervals,
then printed to stdout. The -r flag sets operation as described in
"Chaos and Order in the Capital Markets," by Edgar E. Peters, pp 81,
and should only be used for time series from market data since
logarithmic returns sum to cumulative return-negative numbers in the
time series file are not permitted with this option. The log (R/S) vs
log (time) plot is printed to stdout.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

$Revision: 0.0 $
$Date: 2006/01/18 20:54:36 $
$Id: tshurst.c,v 0.0 2006/01/18 20:54:36 john Exp $
$Log: tshurst.c,v $
Revision 0.0  2006/01/18 20:54:36  john
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

#ifndef DBL_MAX

#define DBL_MAX 1.7976931348623157E+308

#endif

static char rcsid[] = "$Id: tshurst.c,v 0.0 2006/01/18 20:54:36 john Exp $"; /* program version */
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
    "Hurst coefficient calculation of a time series\n",
    "Usage: tshurst [-a] [-d] [-f] [-m] [-p] [-r] [-v] [filename]\n",
    "    -a, do not subtract mean of intervals from values in intervals\n",
    "    -d, the input file is a derivative instead of an integral\n",
    "    -f, output linear range and standard deviation values\n",
    "    -m, precision mode, (computationally inefficient)\n",
    "    -p, don't output the time series, only the Hurst coefficients\n",
    "    -r, specifies that logarithmic returns will be used\n",
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
        i, /* number of samples in an interval */
        j, /* interval counter */
        k, /* implicit index of a sample in data */
        l, /* implicit index of the end of intervals in data */
        m, /* implicit index of end of an interval in data */
        n, /* interval counter */
        o, /* sample counter in an interval of data */
        inc, /* value of increment in intervals */
        c, /* command line switch */
        a = 0, /* do not subtract mean of intervals from values in intervals, 0 = subtract mean from values in intervals, 1 = do not subtract mean from values in intervals */
        f = 0, /* output linear range and standard deviation values, 1 = yes, 0 = no */
        d = 0, /* input file contains differences flag, 1 = yes, 0 = no */
        p = 0, /* logarithmic returns flag, 0 = no, 1 = yes */
        q = 0, /* precision mode, 0 = no, 1 = yes */
        P = 0, /* output only Hurst coefficient, 0 = no, 1 = yes */
        cnt; /* number of counts in range and standard deviation average */

    double *data = (double *) 0, /* reference to array of data from file */
           *lastdata = (double *) 0, /* last reference to array of data from file */
           sumx, /* running sum for mean */
           sumsq, /* running sum of squares for standard deviation */
           min, /* minimum data value in an interval */
           max, /* maximum data value in an interval */
           r, /* range of data values in an interval, ie., max - min */
           s, /* standard deviation of data values in an interval */
           avgrs, /* cumulative sum of R/S for a set of intervals */
           avg, /* average of data values over an interval */
           range, /* running range of time series values */
           std, /* running standard deviation of time series values */
           temp; /* temporary storage for a data value */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "adfmprv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'a': /* request for do not subtract mean of intervals from values in intervals? */

                a = 1; /* yes, set the do not subtract mean of intervals from values in intervals flag */
                break;

            case 'd': /* request for input file contains differences? */

                d = 1; /* yes, set the input file contains differences flag */
                break;

            case 'f': /* request for output linear range and standard deviation values? */

                f = 1; /* yes, set the output linear range and standard deviation values flag */
                break;

            case 'm': /* request for precision mode? */

                q = 1; /* yes, set the precision mode flag */
                break;

            case 'p': /* request for output only Hurst coefficient? */

                P = 1; /* yes, set the output only Hurst coefficient flag */
                break;

            case 'r': /* request for logarithmic returns? */

                p = 1; /* yes, set the logarithmic returns flag */
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
                        lastdata = data; /* save the last reference to array of data from file */

                        if ((data = (double *) realloc (data, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of data from the input file */
                        {
                            data = lastdata; /* couldn't allocate space for the array of data from the input file, restore the last reference to array of data from file */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        data[count] = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                        if (p != 0) /* logarithmic returns flag set? */
                        {

                            if (count != 0) /* yes, not first record? */
                            {
                                data[count - 1] = log (data[count] / data[count - 1]); /* yes, compute the logrithmic return of the sample */
                            }

                        }

                        if (d == 0) /* input file contains differences flag not set? */
                        {

                            if (count != 0) /* yes, not first record? */
                            {
                                data[count - 1] = (data[count] - data[count - 1]); /* no, compute the difference  return of the sample */
                            }

                        }

                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            if (retval == NOERROR) /* no errors? */
            {

                if (p != 0 || d == 0) /* no errors, logarithmic returns flag set or input file contains differences flag not set? */
                {
                    count --; /* yes, one less since data is differences */
                }

                for (i = 2; i <= count; i ++) /* number of samples in an interval, for each interval, could be <= count / 2 */
                {
                    l = count - i + 1; /* calculate the implicit index of the end of intervals in data */
                    avgrs = (double) 0.0; /* reset the cumulative sum of R/S for a set of intervals */
                    n = 0; /* reset the interval counter */
                    range = (double) 0.0; /* reset the running range of time series values */
                    std = (double) 0.0; /* reset the running standard deviation of time series values */
                    cnt = 0; /* reset the number of counts in range and standard deviation average */
                    inc = ((q == 1) ? 1 : i); /* calculate the value of increment in intervals, m = 1 means single step */

                    for (j = 0; j < l; j = j + inc) /* interval counter, for each interval */
                    {
                        m = j + i; /* calculate the implicit index of end of an interval in data */
                        avg = (double) 0.0; /* reset the average of data values over an interval */

                        if (a == 0) /* do not subtract mean of intervals from values in intervals flag not set? */
                        {
                            temp = (double) 0.0; /* reset temp, which is used as a cumulative value for the data values over the interval */
                            o = 0; /* reset the sample counter in an interval of data */

                            for (k = j; k < m; k ++) /* yes, flag not set, implicit index of a sample in data, for each sample */
                            {
                                temp = temp + data[k]; /* save the data value */
                                o ++; /* increment the sample counter in an interval of data */
                            }

                            avg = temp / (double) o; /* calculate the average of data values over an interval */
                        }

                        sumx = (double) 0.0; /* reset the running sum for mean */
                        sumsq = (double) 0.0; /* reset the running sum of squares for standard deviation */
                        min = (double) DBL_MAX; /* reset the minimum data value in an interval */
                        max = (double) - DBL_MAX; /* reset the maximum data value in an interval */

                        for (k = j; k < m; k ++) /* implicit index of a sample in data, for each sample */
                        {
                            temp = data[k] - avg; /* save the data value, after the average data value over the interval is subtracted */
                            sumx = sumx + temp; /* add the data value to the running sum for mean */
                            sumsq = sumsq + (temp * temp); /* add the square of the data value to the running sum of squares for standard deviation */

                            if (sumx > max) /* maximum data value in interval so far? */
                            {
                                max = sumx; /* yes, save the maximum data value in interval so far */
                            }

                            if (sumx < min) /* minimum data value in interval so far? */
                            {
                                min = sumx; /* yes, save the minimum data value in interval so far */
                            }

                        }

                        r = max - min; /* calculate the range of data values in an interval, ie., max - min */
                        s = (sqrt ((sumsq - sumx * sumx / (double) i) / (double) (i - 1))); /* compute the standard deviation of the difference values */
                        range = range + r; /* add the range of data values in an interval to the running range of time series values */
                        std = std + s; /* add the range of data values in an interval to the running standard deviation of time series values */
                        cnt ++; /* increment the number of counts in range and standard deviation average */

                        if (s != (double) 0.0) /* s being zero is somewhat expected, if it is, discard this iteration from the cumulative sum of R/S for a set of intervals */
                        {
                            avgrs = avgrs + (r / s); /* add this r / s to the cumulative sum of R/S for a set of intervals */
                            n ++; /* increment the interval counter */
                        }

                    }

                    if (P == 1) /* output only Hurst coefficient flag set? */
                    {
                        (void) printf ("%d\t%f\n", i, log (avgrs / (double) n) / log ((double) i)); /* yes, the Hurst coefficient is defined as ln (r / s) / ln (t) */
                    }

                    else
                    {

                        if (f == 1) /* no, output linear range and standard deviation values flag set */
                        {
                            (void) printf ("%d\t%f\t%f\n", i, (double) (range / (double) cnt), (double) (std / (double) cnt)); /* yes, print the average linear range and standard deviation values */
                        }

                        else
                        {
                            (void) printf ("%f\t%f\n", log ((double) i), log (avgrs / (double) (n))); /* print the average r / s for the intervals */
                        }

                    }

                }

            }

            if (data != (double *) 0) /* allocated space for the array of data from the input file? */
            {
                free (data); /* yes, free the array of data from the file */
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
