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

tsnormal.c for making a histogram or frequency plot of a time series.

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
$Id: tsnormal.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsnormal.c,v $
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

static char rcsid[] = "$Id: tsnormal.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b," /* file record field separators */

#ifndef PI /* make sure PI is defined */

#define PI 3.141592653589793 /* pi to 15 decimal places as per CRC handbook */

#endif

#define SCALE 0.41664 /* scaling factor for area under normal curve */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Frequency distribution of a time series\n",
    "Usage: tsnormal [-f] [-p] [-s number] [-t] [-v] [filename]\n",
    "    -f, output frequency histogram\n",
    "    -p, don't output the time series, only the mean and standard deviation\n",
    "    -s number, number of steps in the output\n",
    "    -t, x axis values will be included in the output file\n",
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
        *frequency = (int *) 0, /* reference to array of frequency counts */
        length = 0, /* total length of step elements in histogram */
        c, /* command line switch */
        i, /* array element counter */
        f = 0, /* output frequency histogram flag, 0 = no, 1 = yes */
        p = 0, /* only output mean and standard deviation, 0 = no, 1 = yes */
        s = 100, /* number of steps in output file, default 100 */
        t = 0, /* x axis values will be included in output file flag, 0 = no, 1 = yes */
        nindex; /* computed index into array of frequency counts */

    double *value = (double *) 0, /* reference to array of data values from file */
           *lastdata = (double *) 0, /* last reference to array of data from file */
           temp, /* temporary double storage */
           sumx = (double) 0.0, /* linear sum of numbers in file */
           sumsq = (double) 0.0, /* squared sum of numbers in file */
           mean, /* mean of numbers in file */
           stddev, /* standard deviation of numbers in file */
           xscale, /* x scaling factor for normal curve */
           yscale, /* y scaling factor for normal curve */
           sigma3, /* 3 sigma limits for normal curve */
           del; /* x distance between steps of output normal curve */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "fps:tv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'f': /* output frequency histogram? */

                f = 1; /* yes, set the output frequency histogram flag */
                break;

            case 'p': /* only output mean and standard deviation? */

                p = 1; /* yes, set the only output mean and standard deviation flag */
                break;

            case 's': /* number of steps in output file? */

                s = atoi (optarg); /* save the number of steps in the output file */
                break;

            case 't': /* x axis values will be included in output file flag? */

                t = 1; /* set the x axis values will be included in output file flag */
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
                        temp = atof (token[fields - 1]); /* save the sample's value in the temporary double storage */
                        sumx = sumx + temp; /* add the sample's value to the linear sum of numbers in file */
                        sumsq = sumsq + temp * temp; /* add the square of the sample's value to the squared sum of numbers in file */

                        if (f == 1) /* output frequency histogram flag set? */
                        {
                            lastdata = value; /* save the last reference to array of data from file */

                            if ((value = (double *) realloc (value, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of data values from the input file */
                            {
                                value = lastdata; /* couldn't allocate space for the array of data values from the input file, restore the last reference to array of data from file */
                                retval = EALLOC;  /* assume error allocating memory */
                                break; /* and stop */
                            }

                            value[count] = atof (token[fields - 1]); /* save the sample's value in the array of data values from the input file */
                        }

                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            mean = sumx / ((double) count); /* compute the mean of the numbers in the file */
            temp = (sumsq - sumx * sumx / (double) count) / (double) (count - 1); /* save the stddev squared */

            if (temp < (double) 0.0) /* stddev squared a negative number? */
            {
                stddev = (double) 0.0; /* yes, assume it is zero */
            }

            else
            {
                stddev = sqrt (temp); /* compute the standard deviation of the numbers in the file */
            }

            if (p == 1) /* only output mean and standard deviation flag set? */
            {
                (void) printf ("%f %f\n", mean, stddev); /* yes, print the mean and standard deviation */
            }

            else
            {

                if (f == 1) /* output frequency histogram flag set? */
                {

                    if (retval == NOERROR) /* no errors? */
                    {
                        retval = EALLOC;  /* assume error allocating memory */

                        if ((frequency = (int *) malloc (s * sizeof (int))) != (int *) 0) /* allocate space for the array of frequency counts */
                        {
                            retval = NOERROR; /* assume no error */

                            for (i = 0; i < s; i++) /* for each element in the array of frequency counts */
                            {
                                frequency[i] = 0; /* set the element's count to zero */
                            }

                            sigma3 = stddev * (double) 3.0; /* calculate the 3 sigma limits for normal curve */
                            del = ((double) 2.0 * sigma3) / (double) s; /* calculate the x distance between steps of output normal curve */

                            for (i = 0; i < count; i++) /* for each element array of data values from the input file */
                            {
                                nindex = (int) floor (((value[i] - mean)  / del) + (double) ((double) s / (double) 2.0)); /* compute the index into array of frequency counts */

                                if (nindex < 0) /* index less than 3 sigma limit? */
                                {
                                    nindex = 0; /* yes, index is 3 sigma limit */
                                }

                                if (nindex >= s) /* index greater than 3 sigma limit? */
                                {
                                    nindex = s - 1; /* yes, index is 3 sigma limit */
                                }

                                frequency[nindex]++; /* increment the element indexed */
                                length ++; /* increment the total length of step elements in histogram */
                            }

                            yscale = (double) (((double) s *  (double) SCALE) / (double) length); /* calculate the y scaling factor for normal curve */
                            temp = -sigma3; /* start at left side of graph */

                            for (i = 0; i < s; i++) /* for each element in the histogram */
                            {

                                if (t == 1) /* x axis values will be included in output file flag set? */
                                {
                                    (void) printf ("%f\t", temp); /* yes, print the x axis value */
                                }

                                (void) printf ("%f\n", (double) (((double) frequency[i]) * yscale)); /* print the frequency array element's value */
                                temp = temp + del; /* next interval */
                            }

                        }

                        free (frequency); /* free the space for the array of frequency counts */
                    }

                    if (value != (double *) 0) /* allocated space for the array of data values from the input file? */
                    {
                        free (value); /* yes, free the space for the array of data values from the input file */
                    }

                }

                else
                {
                    xscale = stddev * sqrt ((double) 2.0 * (double) PI); /* calculate the x scaling factor for normal curve */
                    yscale = xscale; /* calculate the y scaling factor for normal curve */
                    sigma3 = stddev * (double) 3.0; /* calculate the 3 sigma limits for normal curve */
                    del = ((double) 2.0 * sigma3) / (double) s; /* calculate the x distance between steps of output normal curve */
                    temp = -sigma3; /* start at left side of graph */

                    for (i = 0; i < s; i++) /* for each record in the normal gaussian curve time series */
                    {

                        if (t == 1) /* x axis values will be included in output file flag set? */
                        {
                            (void) printf ("%f\t", temp); /* yes, print the x axis value */
                        }

                        (void) printf ("%f\n", (yscale * ((1.0 / xscale) * (exp ((- (temp * temp)) / (2.0 * stddev * stddev)))))); /* print the record for this interval in the normal gaussian curve time series */
                        temp = temp + del; /* next interval */
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
