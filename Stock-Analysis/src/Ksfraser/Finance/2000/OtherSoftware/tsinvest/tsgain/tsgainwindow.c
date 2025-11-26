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

tsgainwindow.c for finding the windowed gain of a time series.  The
value of a sample in the time series added to the cumulative sum of
the samples, and is squared and added to the cumulative sum of
squares, the Shannon probability, P, calculated using:

        avg
        --- + 1
        rms
    P = -------
           2

where rms is the root mean square of the marginal returns, and avg is
the average of the marginal returns, and the gain, G, calculated
using:

                 P            P -1
    G = (1 + rms)  * (1 - rms)

to make a new time series. The new time series is printed to stdout.

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
$Id: tsgainwindow.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsgainwindow.c,v $
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

static char rcsid[] = "$Id: tsgainwindow.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Find the windowed gain of a time series\n",
    "Usage: tsgainwindow [-t] [-w size] [-v] [filename]\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -w size, specifies the window size for the running average\n",
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
        w = 10, /* window size for the running average */
        element = 0, /* element counter in the array of the last w many elements from the time series */
        retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           fraction, /* fraction, marginal return */
           sum = (double) 0.0, /* running value of cumulative sum of squares */
           sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           avg, /* average of the marginal returns */
           rms, /* root mean square of the marginal returns */
           P, /* Shannon probability */
           G, /* the gain */
           temp, /* temporary double storage */
           *avgwindow, /* reference to the array of the last w many elements of avg from the time series */
           *rmswindow; /* reference to the array of the last w many elements of rms from the time series */

    FILE *infile; /* reference to input file */

    while ((c = getopt (argc, argv, "tvw:")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

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

    if (retval == NOERROR) /* enough arguments? */
    {
        retval = EOPEN; /* assume error opening file */

        if ((infile = argc <= optind ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the input file */
        {
            retval = EALLOC;  /* assume error allocating memory */

            if ((avgwindow = (double *) malloc ((w) * sizeof (double))) != (double *) 0) /* allocate space for the array of the last w many avg elements from the time series */
            {

                if ((rmswindow = (double *) malloc ((w) * sizeof (double))) != (double *) 0) /* allocate space for the array of the last w many rms elements from the time series */
                {
                    retval = NOERROR; /* assume no error */

                    for (element = 0; element < w; element ++) /* for each element in the array of the last w many elements from the time series */
                    {
                        avgwindow[element] = (double) 0.0; /* initialize each avg element to zero */
                        rmswindow[element] = (double) 0.0; /* initialize each rms element to zero */
                    }

                    element = 0; /* reset the element counter in the array of the last w many elements from the time series */

                    while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the records from the input file */
                    {

                        if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                        {

                            if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                            {
                                currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                                if (count != 0) /* not first record? */
                                {
                                    fraction = ((currentvalue - lastvalue) / lastvalue); /* save the fraction, marginal return */

                                    if (count >= w) /* w many records so far? */
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

                                        avg = sum / (double) w; /* save the average of the marginal returns */
                                        rms = sqrt (sumsquared / (double) w); /* save the root mean square of the marginal returns */
                                        P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
                                        G = pow (((double) 1.0 + rms), P) * pow (((double) 1.0 - rms), (double) 1.0 - P); /* calculate the gain */

                                        (void) printf ("%f\n", G); /* print the window value of the gain of the time series */
                                    }

                                    sum = sum - avgwindow[element]; /* subtract the value  of the oldest avg sample in the time series from the running value of cumulative sum */
                                    sum = sum + fraction; /* add the marginal return of the current sample in the time series to the cumulative sum of the time series */
                                    avgwindow[element] = fraction; /* replace the oldest avg value sample of in the time series with the current value from the time series */

                                    temp = fraction * fraction; /* save the sqare of the current value */
                                    sumsquared = sumsquared - rmswindow[element]; /* subtract the value  of the oldest rms sample in the time series from the running value of the square of the cumulative sum */
                                    sumsquared = sumsquared + temp; /* add the square of the value of the current sample in the time series to the running value of cumulative sum of squares */
                                    rmswindow[element] = temp; /* replace the oldest rms value sample of in the time series with the current value from the time series */

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
