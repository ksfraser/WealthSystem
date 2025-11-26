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

tsrunmagnitude.c is for finding the magnitude of the run lengths in a
time series. The value of each sample in the time series is stored,
and subtracted from all other values in the time series, each point
being tallied root mean square. The magnitude deviation is printed to
stdout.

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
$Id: tsrunmagnitude.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsrunmagnitude.c,v $
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

static char rcsid[] = "$Id: tsrunmagnitude.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Find the magnitude of the run lengths in a time series\n",
    "Usage: tsrunmagnitude [-r root] [-v] [filename]\n",
    "    -r root, the root to be used for the root mean, (0.5)\n",
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

/*

The n'th element of type RUN_MAGNITUDE in the data array consists of
the n'th value from the time series, and the running mean squared
value of all possible differences in magnitude that are n many
intervals away from each other in the time series-there would be count
many mean squared values summed in the sumsquared element.

*/

typedef struct run_magnitude_struct  /* run magnitude structure */
{
    double value; /* value of sample in time series */
    double sumsquared; /* the running mean squared value of differences of samples */
    int count; /* the number of elements in sumsquared */
} RUN_MAGNITUDE;

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
        i, /* implicit index counter */
        j, /* implicit index counter */
        k, /* implicit index counter */
        c; /* command line switch */

    double currentvalue = (double) 0.0, /* value of current sample in time series */
           e = (double) 2.0, /*  exponent value, 2.0 for random walk */
           r = (double) 0.5; /*  root value, 0.5 for random walk */

    FILE *infile = stdin; /* reference to input file */

    RUN_MAGNITUDE *data = (RUN_MAGNITUDE *) 0, /* indirect reference to the data array, one element per time series sample */
            *lastdata = (RUN_MAGNITUDE *) 0; /* last indirect reference to the data array, one element per time series sample */

    while ((c = getopt (argc, argv, "r:v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'r': /* request for the root to be used for the root mean? */

                r = atof (optarg); /* yes, get the root to be used for the root mean? */
                e = (double) 1.0 / r; /* and, its reciprocal is the exponent used */
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
                        lastdata = data; /* save the last indirect reference to the data array, one element per time series sample */

                        if ((data = (RUN_MAGNITUDE *) realloc (data, (count + 1) * sizeof (RUN_MAGNITUDE))) == (RUN_MAGNITUDE *) 0) /* allocate space for the data array, one element per time series sample */
                        {
                            data = lastdata; /* couldn't allocate space for the data array, one element per time series sample, restore the last indirect reference to the data array, one element per time series sample */
                            retval = EALLOC; /* assume error allocating memory */
                            break; /* and stop */
                        }

                        data[count].value = currentvalue; /* save the value of the sample in time series */
                        data[count].sumsquared = (double) 0.0; /* reset the running mean squared value of differences of samples */
                        data[count].count = 0; /* reset the number of elements in sumsquared */
                        j = 0; /* implicit address to first element in data array, ie., the first sample in the time series, and the cummulative mean square of deviations for one time unit */
                        k = count - 1; /* implicit address to last element in data array, ie., the last sample in the time series, and the cummulative mean square of deviations for count many time units */

                        /*

                        starting with the most recent sample from the
                        time series, ie., the last element in the data
                        array, subtract the previous element, and add
                        it root mean to the first sumsquared element
                        in the data array-incrementing the count of
                        sumsquared additions-then subtract the next
                        previous element and add it to the second
                        sumsquared element, and so on

                        */

                        for (i = k; i > 0; i --, j ++) /* for each element in the data array, moving backwards */
                        {
                            data[j].sumsquared = data[j].sumsquared + pow (fabs (currentvalue - data[i].value), e); /* add the root mean difference in value between this element and the last element to the sumsquared */
                            data[j].count ++; /* increment the number of elements in sumsquared for this data element */
                        }

                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            if (retval == NOERROR) /* any errors? */
            {

                k = count - 2; /* the last element has not root mean values in it, skip it */

                for (i = 0; i < k; i ++) /* for each element in the data array */
                {
                    (void) printf ("%d\t%f\n", i + 1, pow (data[i].sumsquared / (double) data[i].count, r)); /* print the root mean of magnitude */
                }

            }

            if (data != (RUN_MAGNITUDE *) 0) /* data array, one element per time series sample, allocated? */
            {
                free (data); /* yes, free the data array, one element per time series sample */
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
