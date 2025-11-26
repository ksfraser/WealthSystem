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

tsunfairbrownian.c, unfair returns of a time series. The idea is to
produce the returns of a time series which is weighted unfairly, by a
Shannon probability, p, or alternately, a fraction of reserves to be
wagered on each time increment. The input time series is presumed to
have a Brownian distribution. The main function of this program is
regression scenario verification-given an empirical time series, a
Shannon probability, or a "wager" fraction, (which were probably
derived from the program tsshannon,) speculative market pro forma
performance can be analyzed. The cumulative sum process is Brownian in
nature.

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
$Id: tsunfairbrownian.c,v 0.0 2006/01/18 20:28:55 john Exp $
$Log: tsunfairbrownian.c,v $
Revision 0.0  2006/01/18 20:28:55  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

static char rcsid[] = "$Id: tsunfairbrownian.c,v 0.0 2006/01/18 20:28:55 john Exp $"; /* program version */
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
    "Unfair returns of a time series\n",
    "Usage: tsunfairbrownian [-d] [-f fraction] [-i value] [-p probability] [-t]\n",
    "                        [-v] [filename]\n",
    "    -d, the input file is a derivative instead of an integral\n",
    "    -f fraction, fraction of reserves to be wagered, (0 <= fraction <= 1)\n",
    "    -i value, initial value of output time series (ie., initial reserves)\n",
    "    -p probability, Shannon probability, (0.5 <= probability <= 1.0)\n",
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
        c, /* command line switch */
        d = 0, /* input file contains differences flag, 1 = yes, 0 = no */
        t = 0; /* print time of samples flag, 0 = no, 1 = yes */

    double temp, /* temporary double storage */
           currentvalue = (double) 0.0, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           i = (double) 0.0, /* initial value of output time series, null value = use value of first non-comment record in time series */
           f = (double) 0.0, /* fraction of reserves to be wagered, null value = use (2 * p) - 1 */
           p = (double) 0.5; /* Shannon probability */

    FILE *infile = stdin; /* reference to input file */

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

            case 'i': /* request for initial value in output time series? */

                i = atof (optarg); /* yes, set the initial value of the output time series */
                break;

            case 'p': /* request for Shannon probability */

                p = atof (optarg); /* yes, set the Shannon probability */
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

    if (f == (double) 0.0) /* fraction of reserves to be wagered = null value? */
    {
        f = ((double) 2.0 * p) - (double) 1.0; /* yes, fraction of reserves to be wagered, null value = use (2 * p) - 1 */
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
                                temp = currentvalue - lastvalue; /* yes, subtract the last value in the time series from the current value */

                                if (temp < (double) 0.0) /* value of the current increment in the time series negative? */
                                {
                                    i = i - (i * f); /* yes, subtract the amount wagered from the cumulative sum */
                                }

                                else
                                {
                                    i = i + (i * f); /* yes, add the amount wagered to the cumulative sum */
                                }

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

                                (void) printf ("%f\n", i); /* print the new value of reserves */
                            }

                            lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                        }

                        else
                        {

                            if (currentvalue < (double) 0.0) /* value of the current sample in the time series negative? */
                            {
                                i = i - (i * f); /* yes, subtract the amount wagered from the cumulative sum */
                            }

                            else
                            {
                                i = i + (i * f); /* yes, add the amount wagered to the cumulative sum */
                            }

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

                            (void) printf ("%f\n", i); /* print the new value of reserves */
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
