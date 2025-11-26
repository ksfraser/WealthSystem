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

tsroot.c is for finding the root of a time series. The range, as a
function of time, is summed, for each and every point in the time
series. For example, the output should be proportional to the sqrt (t)
for a Brownian motion fractal.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

$Revision: 0.0 $
$Date: 2006/01/25 20:54:36 $
$Id: tsroot.c,v 0.0 2006/01/25 20:54:36 john Exp $
$Log: tsroot.c,v $
Revision 0.0  2006/01/25 20:54:36  john
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

static char rcsid[] = "$Id: tsroot.c,v 0.0 2006/01/25 20:54:36 john Exp $"; /* program version */
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
    "Find the root of a time series\n",
    "Usage: tsroot [-l] [filename]\n",
    "    -l, output log-log values\n",
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
        l = 0, /* output log-log values flag, 0 = no, 1 = yes */
        c, /* command line switch */
        *samples = (int *) 0, /* reference to number of samples in element */
        *lastsamples = (int *) 0, /* last reference to number of samples in element */
        element, /* number of elements */
        elements; /* number of elements */

    double *data = (double *) 0, /* reference to array of data from file */
           *lastdata = (double *) 0, /* last reference to array of data from file */
           *sum = (double *) 0, /* reference to sum of elements */
           *lastsum = (double *) 0; /* last reference to sum of elements */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "lv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'l': /* request for outputing log-log values? */

                l = 1; /* yes, set the output log-log values flag */
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
                        lastsum = sum; /* save the last reference to sum of elements */

                        if ((sum = (double *) realloc (sum, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of sum of elements */
                        {
                            sum = lastsum; /* couldn't allocate space for the array of sum of elements, restore the last reference to array of sum of elements  */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        sum[count] = (double) 0.0; /* initialize the sum for the element to zero */
                        lastsamples = samples; /* save the last reference to number of samples in element */

                        if ((samples = (int *) realloc (samples, (count + 1) * sizeof (int))) == (int *) 0) /* allocate space for the array of samples in element */
                        {
                            samples = lastsamples; /* couldn't allocate space for the array of samples in element, restore the last reference to array of samples in element */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        samples[count] = 0; /* initialize the sample count to zero */
                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            if (retval == NOERROR) /* no errors? */
            {

                for (elements = 0; elements < count; elements ++) /* for each element */
                {

                    for (element = elements; element < count; element ++) /* for each element past the element */
                    {
                        sum[element - elements] = sum[element - elements] + fabs (data[element] - data[elements]); /* sum the absolute value of the range */
                        samples[element - elements] ++; /* one more range added */
                    }

                }

                for (elements = 0; elements < count; elements ++) /* for each element */
                {

                    if (l == 0) /* outputing log-log values flag set ? */
                    {
                        (void) printf ("%d\t%f\n", elements, sum[elements] / (double) samples[elements]); /* no, print the average range */
                    }

                    else
                    {

                        if ((elements > 0) && (sum[elements] / (double) samples[elements] > (double) 0.0)) /* element output representable as a logarithm? */
                        {
                            (void) printf ("%f\t%f\n", log ((double) elements), log (sum[elements] / (double) samples[elements])); /* no, print the average range */
                        }

                    }

                }

            }

            if (data != (double *) 0) /* allocated space for the array of data from the input file? */
            {
                free (data); /* yes, free the array of data from the file */
            }

            if (sum != (double *) 0) /* allocated space for the array of sum of elements? */
            {
                free (sum); /* yes, free the array of sum of elements */
            }

            if (samples != (int *) 0) /* allocated space for the array of samples in element? */
            {
                free (samples); /* yes, free the array of samples in elements */
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
