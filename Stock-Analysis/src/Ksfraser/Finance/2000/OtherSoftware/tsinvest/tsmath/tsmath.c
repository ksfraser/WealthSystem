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

tsmath.c for for performing arithmetic operations on each element in a
time series. The resultant time series is printed to stdio.

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
$Id: tsmath.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsmath.c,v $
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

#ifndef DBL_MAX

#define DBL_MAX 1.7976931348623157e+308

#endif

#ifndef DBL_MIN

#define DBL_MIN 2.2250738585072014e-308

#endif

static char rcsid[] = "$Id: tsmath.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b," /* file record field separators */

enum operation /* type of math operation */
{
    ADDITION, /* addition */
    ABSOLUTE, /* absolute value */
    DIVISION, /* division */
    EXPONENT, /* exponentiation */
    LN, /* natural logarithm */
    MAXIMUM, /* maximum */
    MINIMUM, /* minimum */
    MULTIPLICATION, /* multiplication */
    POWER, /* power */
    SQUARE, /* square */
    SQRT, /* square root */
    SUBTRACTION, /* subtraction */
    NONE /* no operation */
};

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Math operations on a time series\n",
    "Usage: tsmath [-a n] [-b] [-d n] [-e] [-l] [-L] [-M] [-m n] [-p n]\n",
    "              [-R] [-S] [-s n] [-t] [-v] [filename]\n",
    "    -a n, add the number n to each element in the time series\n",
    "    -b, take the absolute value of each element in the time series\n",
    "    -d n, divide each element in the time series by the number n\n",
    "    -e, exponentiate each element in the time series\n",
    "    -l, take the logarithm of each element in the time series\n",
    "    -L, find the minumum value in the time series\n",
    "    -M, find the maximum value in the time series\n",
    "    -m n, multiply each element in the time series by the number n\n",
    "    -p n, raise each element in the time series to the power n\n",
    "    -R, take the square root of each element in the time series\n",
    "    -S, square each element in the time series\n",
    "    -s n, subtract the number n from each element in the time series\n",
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
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double currentvalue, /* value of current sample in time series */
           adder = (double) 0.0, /* value of adder */
           divisor = (double) 0.0, /* value of divisor */
           multiplier = (double) 0.0, /* value of multiplier */
           power = (double) 0.0, /* value of power */
           subtracter = (double) 0.0, /* value of subtracter */
           maximum = (double) DBL_MIN, /* no double is smaller than this */
           minimum = (double) DBL_MAX; /* no double is larger than this */

    enum operation op = NONE;

    FILE *infile; /* reference to input file */

    while ((c = getopt (argc, argv, "RSa:bd:elLMm:p:s:tv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'a': /* addition? */

                op = ADDITION; /* yes, set addition operation */
                adder = atof (optarg); /* save the value of the adder */
                break;

            case 'b': /* absolute value? */

                op = ABSOLUTE; /* yes, set absolute value operation */
                break;

            case 'd': /* division? */

                op = DIVISION; /* yes, set division operation */
                divisor = atof (optarg); /* save the value of the divisor */
                break;

            case 'e': /* division? */

                op = EXPONENT; /* yes, set exponentiation operation */
                break;

            case 'l': /* logarithm? */

                op = LN; /* yes, set logaritmic operation */
                break;

            case 'M': /* maximum? */

                op = MAXIMUM; /* yes, set maximum operation */
                break;

            case 'L': /* minimum? */

                op = MINIMUM; /* yes, set minimum operation */
                break;

            case 'm': /* multiplication? */

                op = MULTIPLICATION; /* yes, set multiplication operation */
                multiplier = atof (optarg); /* save the value of the multiplier */
                break;

            case 'p': /* power? */

                op = POWER; /* yes, set power operation */
                power = atof (optarg); /* save the value of the power */
                break;

            case 'R': /* square root? */

                op = SQRT; /* yes, set square root operation */
                break;

            case 'S': /* square? */

                op = SQUARE; /* yes, set square operation */
                break;

            case 's': /* subtraction? */

                op = SUBTRACTION; /* yes, set subtraction operation */
                subtracter = atof (optarg); /* save the value of the subtracter */
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

            while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the records from the input file */
            {

                if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
                {

                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                    {
                        currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

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

                        switch (op)
                        {

                            case ADDITION: /* addition? */

                                (void) printf ("%f\n", currentvalue + adder); /* yes, print the addition */
                                break;

                            case ABSOLUTE: /* absolute value? */

                                (void) printf ("%f\n", fabs (currentvalue)); /* yes, print absolute value */
                                break;

                            case DIVISION: /* division? */

                                (void) printf ("%f\n", currentvalue / divisor); /* yes, print the division */
                                break;

                            case EXPONENT: /* exponentiation? */

                                (void) printf ("%f\n", exp (currentvalue)); /* yes, print the exponentiation */
                                break;

                            case LN: /* natural logarithm? */

                                (void) printf ("%f\n", log (currentvalue)); /* yes, print the logarithm */
                                break;

                            case MAXIMUM: /* maximum? */

                                if (currentvalue > maximum) /* largest value seen? */
                                {
                                    maximum = currentvalue; /* yes, save the largest value seen */
                                }

                                (void) printf ("%f\n", maximum); /* print the maximum */
                                break;

                            case MINIMUM: /* minimum? */

                                if (currentvalue < minimum) /* smallest value seen? */
                                {
                                    minimum = currentvalue; /* yes, save the smallest value seen */
                                }

                                (void) printf ("%f\n", minimum); /* print the minumum */
                                break;

                            case MULTIPLICATION: /* multiplication? */

                                (void) printf ("%f\n", currentvalue * multiplier); /* yes, print the multiplication */
                                break;

                            case POWER: /* power? */

                                (void) printf ("%f\n", pow (currentvalue, power)); /* yes, print the power */
                                break;

                            case SQRT: /* square root? */

                                (void) printf ("%f\n", sqrt (currentvalue)); /* yes, print the square root */
                                break;

                            case SQUARE: /* square? */

                                (void) printf ("%f\n", currentvalue * currentvalue); /* yes, print the square */
                                break;

                            case SUBTRACTION: /* subtraction? */

                                (void) printf ("%f\n", currentvalue - subtracter); /* yes, print the subtraction */
                                break;

                            case NONE: /* no operation? */

                                (void) printf ("%f\n", currentvalue); /* yes, print the original record */
                                break;

                            default:

                                break;

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
