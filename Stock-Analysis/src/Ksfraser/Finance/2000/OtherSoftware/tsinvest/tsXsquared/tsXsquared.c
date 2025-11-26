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

tsXsquared is for taking the Chi-Square of two time series, the first
file contains the observed values, the second contains the expected
values.

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
$Id: tsXsquared.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsXsquared.c,v $
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

static char rcsid[] = "$Id: tsXsquared.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Take the Chi-Square of two time series\n",
    "Usage: tsXsquared [-v] [observed] expected\n",
    "    observed, observed values filename\n",
    "    expected, expected values filename\n",
    "    -v, print the program's version information\n",
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

double lookup[] = /* lookup table for 5% confidence levels for less than 31 degrees of freedom */
{
    (double) 0.0000, /* no records */
    (double) 1.0000, /* 1 record */
    (double) 3.8415, /* 2 records */
    (double) 5.9915, /* 3 records */
    (double) 7.8147, /* 4 records */
    (double) 9.4877, /* 5 records */
    (double) 11.071, /* 6 records */
    (double) 12.592, /* 7 records */
    (double) 14.067, /* 8 records */
    (double) 15.507, /* 9 records */
    (double) 16.919, /* 10 records */
    (double) 18.307, /* 11 records */
    (double) 19.675, /* 12 records */
    (double) 21.026, /* 13 records */
    (double) 22.362, /* 14 records */
    (double) 23.685, /* 15 records */
    (double) 24.996, /* 16 records */
    (double) 26.296, /* 17 records */
    (double) 27.587, /* 18 records */
    (double) 28.869, /* 19 records */
    (double) 30.140, /* 20 records */
    (double) 31.410, /* 21 records */
    (double) 32.671, /* 22 records */
    (double) 33.924, /* 23 records */
    (double) 35.173, /* 24 records */
    (double) 36.415, /* 25 records */
    (double) 37.653, /* 26 records */
    (double) 38.885, /* 27 records */
    (double) 40.113, /* 28 records */
    (double) 41.337, /* 29 records */
    (double) 42.557, /* 30 records */
    (double) 43.773  /* 31 records */
};

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
        c; /* command line switch */

    double observedvalue, /* value of current sample in the observed input file*/
           expectedvalue, /* value of current sample in the expected input file*/
           X = (double) 0.0, /* chi-squared value */
           C, /* used in confidence calculation */
           temp; /* temporary float storage */

    FILE *infileo = stdin, /* reference to observed input file */
         *infilee; /* reference to expected input file */

    while ((c = getopt (argc, argv, "v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

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

    if (argc - optind > 0) /* enough arguments? */
    {
        retval = EOPEN; /* assume error opening file */

        if ((infileo = argc - optind == 1 ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the observed input file */
        {

            if ((infilee = argc - optind == 1 ? fopen (argv[optind], "r") : fopen (argv[optind + 1], "r")) != (FILE *) 0) /* yes, open the expected input file */
            {
                retval = NOERROR; /* assume no error */

                while (fgets (buffer, BUFLEN, infileo) != (char *) 0) /* read the records from the input file */
                {

                    if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                    {

                        if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                        {
                            observedvalue = atof (token[fields - 1]); /* save the value of the current observed value from the input file */

                            while (fgets (buffer, BUFLEN, infilee) != (char *) 0) /* read the records from the input file */
                            {

                                if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                                {

                                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                                    {
                                        expectedvalue = atof (token[fields - 1]); /* save the value of the current expected value from the input file */
                                        temp = observedvalue - expectedvalue; /* save the difference between the observed value and expected value */

                                        if (temp < (double) 0.0) /* difference between the observed value and expected value negative */
                                        {
                                            temp = -temp; /* yes, make it postive */
                                        }

                                        X = X + ((temp * temp) / expectedvalue); /* calculate the chi-squared value */
                                        break; /* files are aligned, next record from observed input file */
                                    }

                                }

                            }

                            count ++; /* increment the count of records from the input file */
                        }

                    }

                }

            }

            if (count > 101) /* number of degrees of freedom greater than 101? */
            {
                temp = (double) 1.6449 + sqrt ((double) 2.0 * ((double) count - (double) 1.0) - (double) 1.0);
                C = (double) 0.5 * (temp * temp);
            }

            else if (count == 101) /* number of degrees of freedom equal to 101 */
            {
                C = (double) 124.342;
            }

            else if (count > 31) /* number of degrees of freedom greater than 31, but less than 101? */
            {
                temp = sqrt ((double) 2.0 / ((double) 9.0 * ((double) count - (double) 1.0)));
                temp = (double) 1.6449 * (temp * temp * temp);
                C = (double) ((double) count - (double) 1.0) * ((double) 1.0 - (double) 2.0 / ((double) 9.0 * ((double) count - (double) 1.0))) + temp;
            }

            else /* number of degrees of freedom less than 31, use lookup table */
            {
                C = lookup[count];
            }

            (void) printf ("chi-squared value = %.3f, 5 percent critical value = %.3f, for %d samples\n", X, C, count); /* print the chi-squared statistics */

            if (fclose (infileo) == EOF) /* close the observed input file */
            {
                retval = ECLOSE; /* error closing file */
            }

            if (fclose (infilee) == EOF) /* close the expected input file */
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
