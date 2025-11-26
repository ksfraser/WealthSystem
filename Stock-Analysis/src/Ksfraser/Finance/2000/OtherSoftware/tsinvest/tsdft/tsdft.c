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

Note: the algorithm used in this program is a modified version of the
program dft.c, written and Copyright 1985 Nicholas B. Tufillaro.

------------------------------------------------------------------------------

tsdft.c for taking the Discrete Fourier Transform (power spectrum) of
a time series.

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
$Id: tsdft.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsdft.c,v $
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

#ifndef PI /* make sure PI is defined */

#define PI 3.141592653589793 /* pi to 15 decimal places as per CRC handbook */

#endif

static char rcsid[] = "$Id: tsdft.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Discrete Fourier Transform (power spectrum) of a time series\n",
    "Usage: tsdft [-l] [-s] [-v] [filename]\n",
    "    -l, log-log output of spectrum\n",
    "    -s, square the output instead of producing power spectrum\n",
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
        c, /* command line switch */
        s = 0, /* square the output series flag, 0 = no, 1 = yes */
        l = 0, /* logarithmic output series flag, 0 = no, 1 = yes */
        k, /* loop counter */
        p, /* loop counter */
        N, /* half the number of data points in the time series */
        L; /* number of data points in the time series */

    double *ts = (double *) 0, /* reference to array of data values from file */
           *lastts = (double *) 0, /* last reference to array of data from file */
           *A, /* reference to array of average of cosine terms */
           *B, /* reference to array of average of sine terms */
           *P, /* reference to array power spectrum terms */
           avg, /* average or dc component in the time series */
           sum, /* sum of values */
           psmax, /* maximum of the power spectrum to normalize */
           temp1, /* frequency output temporary computation */
           temp2; /* magnitude output temporary computation */

    FILE *infile; /* reference to input file */

    while ((c = getopt (argc, argv, "lsv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'l': /* request for logarithmic output series? */

                l = 1; /* yes, set the logarithmic output series flag */
                break;

            case 's': /* request for square the output series? */

                s = 1; /* yes, set the square the output series flag */
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
                        lastts = ts; /* save the last reference to array of data from file */

                        if ((ts = (double *) realloc (ts, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of data values from the input file */
                        {
                            ts = lastts; /* couldn't allocate space for the array of data values from the input file, restore the last reference to array of data from file */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        ts[count] = atof (token[fields - 1]); /* save the sample's value */
                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            L = count; /* save the count of data points in the time series */

            if ((count % 2) == 0) /* get rid of last point and make sure number of data points is even, even number of data points? */
            {
                L -= 2; /* remove the last two data points */
            }

            else
            {
                L -= 1; /* remove the last data point */
            }

            retval = EALLOC;  /* assume error allocating memory */
            N = L / 2; /* save half the count of data points in the time series */

            if ((A = (double *) malloc ((N + 1) * sizeof (double))) != (double *) 0) /* allocate space for the array of data values from the input file */
            {

                if ((B = (double *) malloc ((N) * sizeof (double))) != (double *) 0) /* allocate space for the array of data values from the input file */
                {

                    if ((P = (double *) malloc ((N + 1) * sizeof (double))) != (double *) 0) /* allocate space for the array of data values from the input file */
                    {
                        retval = NOERROR; /* assume no error */

                        /* subtract out dc component from time series */

                        avg = 0; /* initialize the mean or dc component of the time series */

                        for (count = 0; count < L; ++ count) /* for each data point in the time series */
                        {
                            avg += ts[count]; /* sum the value of the data point */
                        }

                        avg = avg / (double) L; /* calculate the mean or dc component in the time series */

                        /* now subtract out the mean value from the time series */

                        for (count = 0; count < L; ++ count) /* for each data point in the time series */
                        {
                            ts[count] = ts[count] - avg; /* subtract the mean or dc component in the time series from the value of the data point */
                        }

                        /* Fourier transform */

                        /* cosine series */

                        for (k = 0; k <= N; ++ k) /* for each element in the lower half of the time series */
                        {
                            sum = (double) 0.0; /* initialize the sum of values */

                            for (p = 0; p < L; ++ p) /* for each value in the time series */
                            {
                                sum += ts[p] * cos ((double) PI * (double) k * (double) p / (double) N); /* add to the sum of values, the value of the data point multiplied by the cosine term */
                            }

                            A[k] = sum / (double) N; /* caculate the average of the cosine terms */
                        }

                        /* sine series */

                        for (k = 0; k < N; ++ k) /* for each element in the lower half of the time series */
                        {
                            sum = (double) 0.0; /* initialize the sum of values */

                            for(p = 0; p < L; ++ p) /* for each value in the time series */
                            {
                                sum += ts[p] * sin ((double) PI * (double) k * (double) p / (double) N); /* add to the sum of values, the value of the data point multiplied by the sine term */
                            }

                            B[k] = sum / (double) N; /* caculate the average of the sine terms */
                        }

                        /* calculate the power spectrum */

                        for (count = 0; count <= N; ++ count) /* for each element in the lower half of the time series */
                        {
                            P[count] = sqrt (A[count] * A[count] + B[count] * B[count]); /* calculate the power spectrum term */
                        }

                        /* find the maximum of the power spectrum to normalize */

                        psmax = 0; /* initialize the maximum of the power spectrum to normalize */

                        for (count = 0; count <= N; ++ count) /* for each element in the lower half of the time series */
                        {

                            if (P[count] > psmax) /* power spectrum term greater than the maximum of the power spectrum to normalize? */
                            {
                                psmax = P[count]; /* yes, save the power spectrum term */
                            }

                        }

                        for (count = 0; count <= N; ++ count) /* for each element in the lower half of the time series */
                        {
                            P[count] = P[count] / psmax; /* divide the power spectrum term by the maximum of the power spectrum to normalize */
                        }

                        for (k = 0; k <= N; ++ k) /* for each element in the lower half of the time series */
                        {
                            temp1 = (double) k; /* save the frequency */
                            temp2 = P[k]; /* save the magnitude */

                            if (s == 1)  /* square the output series flag set? */
                            {
                                temp2 = temp2 * temp2; /* yes, square the magnitude */
                            }

                            if (l == 1) /* logarithmic output series flag set? */
                            {

                                if (temp1 == (double) 0.0) /* if the frequency is zero, skip this term */
                                {
                                    continue;
                                }

                                temp1 = log (temp1); /* yes, take the logarithm of the frequency */
                                temp2 = log (temp2); /* take the logarithm of the magnitude */
                            }

                            (void) printf ("%f\t%f\n", temp1, temp2); /* print the spectrum term */
                        }

                        free (P);
                    }

                    free (B);
                }

                free (A);
            }

            if (ts != (double *) 0) /* allocated space for the array of data values from the input file? */
            {
                free (ts); /* yes, free the space for the array of data values from the input file */
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
