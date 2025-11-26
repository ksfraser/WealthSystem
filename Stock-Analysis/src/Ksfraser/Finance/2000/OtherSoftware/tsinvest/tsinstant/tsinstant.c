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

tsinstant.c for finding the instantaneous fraction of change in a time
series. The value of a sample in the time series is subtracted from
the previous sample in the time series, and divided by the value of
the previous sample. For Brownian motion, random walk fractals, the
absolute value of the instantaneous fraction of change is also the
root mean square of the instantaneous fraction of change, (the
absolute value of the normalized increments, when averaged, is related
to the root mean square of the increments by a constant. If the
normalized increments are a fixed increment, the constant is unity. If
the normalized increments have a Gaussian distribution, the constant
is ~0.8 depending on the accuracy of of "fit" to a Gaussian
distribution). Squaring this value is the average of the instantaneous
fraction of change, and adding unity to the absolute value of the
instantaneous fraction of change, and dividing by two, is the Shannon
probability of the instantaneous fraction of change. The values are
printed to stdout.

For fractional Brownian motion time series, substantial filtering will
be required of the output time series. The programs tspole(1) and
tsavgwindow(1) may be used, perhaps in a cascade fashion, to implement
a filtering technique, which potentially could be used in an adaptive
computational system. Markov techniques may also be applicable. Note
that in fractal time series, the short term correlation, say less than
three time units as a typical value, is quite high-this can be
verified by the tshurst(1) program, eg., filtering, to find the
average value, over a few time units, may be an advantageous strategy
in adaptive computational control systems.

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
$Id: tsinstant.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsinstant.c,v $
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

static char rcsid[] = "$Id: tsinstant.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Take the instantaneous fraction of change of a time series\n",
    "Usage: tsinstant [-a] [-r] [-s] [-t] [-u] [-v] [filename]\n",
    "    -a, print the instantaneous average of the increment\n",
    "    -r, print the instantaneous root mean square of the increment\n",
    "    -s, print the instantaneous Shannon probability of the increment\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -u, print the instantaneous sign of the unity of the increment\n",
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
        preceeding, /* preceeding field printed in a record, 1 = yes, 0 = no */
        sign, /* the sign of the normalized increment */
        a = 0, /* print the average of the normalized increment */
        x = 0, /* any print flags set, 0 = no, 1 = yes */
        r = 0, /* print absolute value of normalized increment */
        s = 0, /* print the Shannon probability of the normalized increment */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        u = 0, /* print the sign of the normalized increment */
        c; /* command line switch */

    double currentvalue = (double) 0.0, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           fraction, /* current value of normalized increment */
           absolute_value, /* absolute value of normalized increment */
           average, /* average value of normalized increment */
           shannon; /* Shannon probability of normalized increment */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "arsutv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'a': /* request to print the average of the normalized increment? */

                a = 1; /* yes, set print the average of the normalized increment flag */
                x = 1; /* set any print flags set */
                break;

            case 'r': /* request to print absolute value of normalized increment? */

                r = 1; /* yes, set print absolute value of normalized increment flag */
                x = 1; /* set any print flags set */
                break;

            case 's': /* request to print the Shannon probability of the normalized increment? */

                s = 1; /* yes, set the print the Shannon probability of the normalized increment flag */
                x = 1; /* set any print flags set */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

            case 'u': /* request to print the sign of the normalized increment? */

                u = 1; /* yes, set the print the sign of the normalized increment flag */
                x = 1; /* set any print flags set */
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

    if (x == 0) /* any print flags set? */
    {
        r = 1; /* no, default is to print only the absolute value, set the flag */
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

                        if (count != 0) /* not first record? */
                        {
                            preceeding = 0; /* reset the preceeding field printed in a record flag */

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

                            fraction = (currentvalue - lastvalue) / lastvalue; /* save the current value of normalized increment */
                            absolute_value = fraction < (double) 0.0 ? -fraction : fraction; /* save the absolute value of normalized increment */
                            shannon = (absolute_value + (double) 1.0) / (double) 2.0; /* Shannon probability of normalized increment */
                            average = absolute_value * absolute_value; /* average value of normalized increment */

                            if (fraction == (double) 0.0) /* no change in increment? */
                            {
                                sign = 0; /* yes, no change */
                            }

                            else if (fraction > (double) 0.0) /* change in increment positive? */
                            {
                                sign = 1; /* yes, sign is unity */
                            }

                            else
                            {
                                sign = -1; /* else, the change in increment is negative, sign is negative unity */
                            }

                            if (r == 1) /* print absolute value of normalized increment flag set? */
                            {

                                if (preceeding == 1) /* yes, any other preceeding fields in this record? */
                                {
                                    (void) printf ("\t%f", absolute_value); /* yes, at least on preceeding field, print the absolute value of the normalized increment */
                                }

                                else
                                {
                                    (void) printf ("%f", absolute_value); /* no, no preceeding field, print the absolute value of the normalized increment */
                                    preceeding = 1; /* at least one preceeding field printed in this record */
                                }

                            }

                            if (a == 1) /* print the average of the normalized increment flag set? */
                            {

                                if (preceeding == 1) /* yes, any other preceeding fields in this record? */
                                {
                                    (void) printf ("\t%f", average); /* yes, at least on preceeding field, print the average of the normalized increment */
                                }

                                else
                                {
                                    (void) printf ("%f", average); /* no, no preceeding field, print the average of the normalized increment */
                                    preceeding = 1; /* at least one preceeding field printed in this record */
                                }

                            }

                            if (s == 1) /* print the Shannon probability of the normalized increment flag set? */
                            {

                                if (preceeding == 1) /* yes, any other preceeding fields in this record? */
                                {
                                    (void) printf ("\t%f", shannon); /* yes, at least on preceeding field, print the Shannon probability of the normalized increment */
                                }

                                else
                                {
                                    (void) printf ("%f", shannon); /* no, no preceeding field, print the Shannon probability of the normalized increment */
                                    preceeding = 1; /* at least one preceeding field printed in this record */
                                }

                            }

                            if (u == 1) /* print the sign of the normalized increment flag set? */
                            {

                                if (preceeding == 1) /* yes, any other preceeding fields in this record? */
                                {
                                    (void) printf ("\t%d", sign); /* yes, at least on preceeding field, print the sign of the normalized increment */
                                }

                                else
                                {
                                    (void) printf ("%d", sign); /* no, no preceeding field, print the sign of the normalized increment */
                                    preceeding = 1; /* at least one preceeding field printed in this record */
                                }

                            }

                            if (preceeding == 1) /* any preceeding fields in this record? */
                            {
                                (void) printf ("\n"); /* yes, terminate the record with an EOL */
                            }

                        }

                        lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
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
