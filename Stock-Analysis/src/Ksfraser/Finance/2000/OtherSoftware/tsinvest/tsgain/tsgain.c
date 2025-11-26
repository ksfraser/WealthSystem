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

tsgain.c for finding the gain of a time series.  The value of a sample
in the time series added to the cumulative sum of the samples, and is
squared and added to the cumulative sum of squares, the Shannon probability, P,
calculated using:

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
$Id: tsgain.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsgain.c,v $
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

static char rcsid[] = "$Id: tsgain.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Find the gain of a time series\n",
    "Usage: tsgain [-p] [-t] [-v] [filename]\n",
    "    -p, don't output the time series, only the gain value\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -v, print the program's version information\n",
    "    filename, input filename\n",
    "Usage: tsgain -a avg -r rms\n",
    "    -a avg, average value of marginal returns\n",
    "    -r rms, root mean square value of marginal returns\n",
    "Usage: tsgain -P P -r rms\n",
    "    -P P, Shannon probability\n",
    "    -r rms, root mean square value of marginal returns\n"
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
    "Error closing file\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1
#define EOPEN 2
#define ECLOSE 3

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
        p = 0, /* only output the gain, 0 = no, 1 = yes */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        a_flag = 0, /* avg specified from the command line flag, 0 = no, 1 = yes */
        r_flag = 0, /* rms specified from the command line flag, 0 = no, 1 = yes */
        P_flag = 0, /* P specified from the command line flag, 0 = no, 1 = yes */
        com_flag = 0, /* a_flag or r_flag or P_flag set, 0 = no, 1 = yes */
        c; /* command line switch */

    double currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           fraction, /* fraction, marginal return */
           sum = (double) 0.0, /* running value of cumulative sum of squares */
           sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           avg = (double) 0.0, /* average of the marginal returns */
           rms = (double) 0.0, /* root mean square of the marginal returns */
           P = (double) 0.0, /* Shannon probability */
           G; /* the gain */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "a:P:pr:tv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'a': /* avg specified from the command line? */

                avg = atof (optarg); /* yes, save the average of the marginal returns */
                a_flag = 1; /* set the avg specified from the command line flag, 0 = no, 1 = yes */
                com_flag = 1; /* set the a_flag or r_flag or P_flag set, 0 = no, 1 = yes */
                retval = EARGS; /* return value, assume argument error */

                if (r_flag == 1) /* rms specified from the command line flag, 0 = no, 1 = yes, set? */
                {
                    retval = NOERROR; /* return value, assume no error */
                    P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
                    G = pow (((double) 1.0 + rms), P) * pow (((double) 1.0 - rms), (double) 1.0 - P); /* calculate the gain */
                    (void) printf ("%f\n", G); /* yes, print the last value of the gain of the time series */
                }

                break;

            case 'P': /* Shannon probability specified from the command line */

                P = atof (optarg); /* yes, save the Shannon probability */
                P_flag = 1; /* set the P specified from the command line flag, 0 = no, 1 = yes */
                com_flag = 1; /* set the a_flag or r_flag or P_flag set, 0 = no, 1 = yes */
                retval = EARGS; /* return value, assume argument error */

                if (r_flag == 1) /* rms specified from the command line flag, 0 = no, 1 = yes, set? */
                {
                    retval = NOERROR; /* return value, assume no error */
                    G = pow (((double) 1.0 + rms), P) * pow (((double) 1.0 - rms), (double) 1.0 - P); /* calculate the gain */
                    (void) printf ("%f\n", G); /* yes, print the last value of the gain of the time series */
                }

                break;

            case 'p': /* only output the gain? */

                p = 1; /* yes, set the only output the gain flag */
                break;

            case 'r': /* rms specified from the command line? */

                rms = atof (optarg); /* yes, save the root mean square of the marginal returns */
                r_flag = 1; /* set the rms specified from the command line flag, 0 = no, 1 = yes */
                com_flag = 1; /* set the a_flag or r_flag or P_flag set, 0 = no, 1 = yes */
                retval = EARGS; /* return value, assume argument error */

                if (a_flag == 1) /* avg specified from the command line flag, 0 = no, 1 = yes, set? */
                {
                    retval = NOERROR; /* return value, assume no error */
                    P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
                    G = pow (((double) 1.0 + rms), P) * pow (((double) 1.0 - rms), (double) 1.0 - P); /* calculate the gain */
                    (void) printf ("%f\n", G); /* yes, print the last value of the gain of the time series */
                }

                if (P_flag == 1) /* P specified from the command line flag, 0 = no, 1 = yes, set? */
                {
                    retval = NOERROR; /* return value, assume no error */
                    G = pow (((double) 1.0 + rms), P) * pow (((double) 1.0 - rms), (double) 1.0 - P); /* calculate the gain */
                    (void) printf ("%f\n", G); /* yes, print the last value of the gain of the time series */
                }

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

        if (com_flag != 1) /* a_flag or r_flag or P_flag set, 0 = no, 1 = yes, set? */
        {
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
                            currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                            if (count != 0) /* not first record? */
                            {
                                fraction = ((currentvalue - lastvalue) / lastvalue); /* save the fraction, marginal return */

                                if (t == 1 && p == 0) /* print time of samples? */
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

                                sum = sum + fraction; /* add the marginal return of the current sample in the time series to the cumulative sum of the time series */
                                sumsquared = sumsquared + (fraction * fraction); /* add the square of the marginal return of the current sample in the time series to the running value of cumulative sum of squares */

                                if (p == 0) /* only output the gain flag set? */
                                {
                                    avg = sum / (double) count; /* save the average of the marginal returns */
                                    rms = sqrt (sumsquared / (double) count); /* save the root mean square of the marginal returns */

                                    if (rms > (double) 0.0) /* rms can be zero if there is no change in the first few records have no change in value */
                                    {
                                        P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
                                        G = pow (((double) 1.0 + rms), P) * pow (((double) 1.0 - rms), (double) 1.0 - P); /* calculate the gain */
                                        (void) printf ("%f\n", G); /* print the current value of the gain of the time series, so far */
                                    }

                                    else
                                    {
                                        (void) printf ("%f\n", (double) 1.0); /* print the current value of the gain of the time series, so far */
                                    }

                                }

                            }

                            lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                            count ++; /* increment the count of records from the input file */
                        }

                    }

                }

                if (p == 1) /* only output the gain flag set? */
                {
                    avg = sum / (double) count; /* save the average of the marginal returns */
                    rms = sqrt (sumsquared / (double) count); /* save the root mean square of the marginal returns */
                    P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
                    G = pow (((double) 1.0 + rms), P) * pow (((double) 1.0 - rms), (double) 1.0 - P); /* calculate the gain */
                    (void) printf ("%f\n", G); /* yes, print the last value of the gain of the time series */
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
