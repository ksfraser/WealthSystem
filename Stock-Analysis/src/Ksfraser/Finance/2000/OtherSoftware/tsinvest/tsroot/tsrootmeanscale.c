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

tsrootmeanscale.c for finding the root mean of a time series, at
different scales. The number of consecutive samples of like movements
in the time series is tallied, at different scales, and the resultant
value of the distribution, as calculated by using the first value in
the distribution, the running mean of the distribution, and the least
squares fit of the distribution, is printed to stdout-a simple random
walk fractal with a Gaussian/normal distributed increments would be
the combinatorial probabilities, 0.5, 0.25, 0.125, 0.625 ...

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
$Id: tsrootmeanscale.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsrootmeanscale.c,v $
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

static char rcsid[] = "$Id: tsrootmeanscale.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Find the root mean of a time series\n",
    "Usage: tsrootmeanscale [-n n] [-p] [-v] [filename]\n",
    "    -n n, minimum consecutive like movements for running average and LSQ\n",
    "    -p, don't output the time series, only the root mean value by:\n",
    "       first element in distribution\n",
    "       running average of distribution\n",
    "       LSQ formula of best fit to distribution\n",
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
        current_movement = 0, /* current movement, +1 = up, -1 = down */
        last_movement = 0, /* last movement, +1 = up, -1 = down */
        p = 0, /* only output root mean, 0 = no, 1 = yes */
        c, /* command line switch */
        i, /* implicit address counter */
        j = 0, /* element counter in LSQ fit approximation */
        l = 0, /* starting point in the data to scan from by different scales */
        m = 0, /* element scan by scale */
        n = 1, /* the count of consecutive like movements in the movments distribution must be greater than this number to be included in the running average and LSQ fit of persistence probability */
        sample, /* the scale, starting at one, skipping one value, skipping two values, etc. */
        transitions = 0, /* count of total transitions */
        likemovements = 0, /* number of consecutive like movements */
        movements_size = 0, /* size of array of the count of consecutive like movements */
        *movements = (int *) 0, /* array of the count of consecutive like movements */
        *lastmovements; /* last array of the count of consecutive like movements */

    double *data = (double *) 0, /* reference to array of data from file */
           *lastdata = (double *) 0, /* last reference to array of data from file */
           currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           sx = (double) 0.0, /* sum of the time values */
           sy = (double) 0.0, /* sum of the data values */
           sxx = (double) 0.0, /* sum of the time values squared */
           sxy = (double) 0.0, /* sum of the data values * the time values */
           det, /* determinate in best fit calculations */
           a, /* slope of best fit line */
           b, /* offset of best fit line */
           temp1, /* temporary double variable */
           temp2, /* temporary double variable */
           running_avg = (double) 0.0; /* running average of persistence probability */

    FILE *infile = stdin; /* reference to input file */

    while ((c = getopt (argc, argv, "n:pv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'n': /* the count of consecutive like movements in the movments distribution must be greater than this number to be included in the running average and LSQ fit of persistence probability? */

                n = atoi (optarg); /* yes, set the count of consecutive like movements in the movments distribution must be greater than this number to be included in the running average and LSQ fit of persistence probability */
                break;

            case 'p': /* only output root mean square? */

                p = 1; /* yes, set the only output root mean square flag */
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
                        lastdata = data; /* save the last reference to array of data from file */

                        if ((data = (double *) realloc (data, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of data from the input file */
                        {
                            data = lastdata; /* couldn't allocate space for the array of data from the input file, restore the last reference to array of data from file */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        data[count] = atof (token[fields - 1]); /* save the value of the current sample in the time series */
                        count ++; /* increment the count of records from the input file */
                    }

                }

            }

            for (sample = 1; sample < count / 2; sample ++) /* for each time element, increment the scale, starting at one, skipping one value, skipping two values, etc., ie., the sample will be by one, by two, by three, etc. */
            {

                for (l = 0; l < sample; l ++) /* starting at each scale point in the data to scan from by different scales, ie., starting at each element < sample size, move forward,  0 <= l <= sample */
                {
                    current_movement = 0; /* current movement, +1 = up, -1 = down */
                    last_movement = 0; /* last movement, +1 = up, -1 = down */
                    likemovements = 0; /* number of consecutive like movements */
                    movements_size = 0; /* size of array of the count of consecutive like movements */
                    transitions = 0; /* count of total transitions */

                    for (m = l; m < count; m = m + sample) /* for each element in the scan by scale, ie., starting at l, move forward by sample size */
                    {
                        currentvalue = data[m]; /* save the value of the current sample in the time series */
                        current_movement = ((currentvalue - lastvalue) > (double) 0.0) ? 1 : -1; /* save the current movement, +1 = up, -1 = down */

                        if (current_movement == last_movement) /* current movement same as last movement? */
                        {
                            likemovements ++; /* increment the number of consecutive like movements */
                        }

                        else
                        {
                            likemovements = 0; /* reset the number of consecutive like movements */
                        }

                        if (likemovements == movements_size) /* number of consecutive like movements greater than the size of array of the count of consecutive like movements? */
                        {
                            movements_size ++; /* yes, increment the size of array of the count of consecutive like movements */
                            lastmovements = movements; /* save the last array of the count of consecutive like movements */

                            if ((movements = (int *) realloc (movements, (size_t) (movements_size) * sizeof (int))) == (int *) 0) /* allocate space for the array of the count of consecutive like movements */
                            {
                                movements = lastmovements; /* restore the array of the count of consecutive like movements */
                                retval = EALLOC; /* assume error allocating memory */
                                break; /* and stop */
                            }

                            movements[movements_size - 1] = 0; /* zero the element of the array of the count of consecutive like movements */
                        }

                        movements[likemovements] ++; /* increment the element of the array of the count of consecutive like movements */
                        lastvalue = currentvalue; /* save the value of last sample in time series */
                        last_movement = current_movement; /* save the last movement, +1 = up, -1 = down */
                        transitions ++; /* increment the count of total transitions */
                    }

                }

                if (retval == NOERROR) /* any errors? */
                {

                    if (p == 0) /* only output root mean flag set? */
                    {

                        for (i = 0; i < movements_size; i ++) /* for each element in the array of the count of consecutive like movements */
                        {
                            (void) printf ("%d\t%d\t%f\n", sample, i, (double) ((double) movements[i] / (double) transitions)); /* print the fraction of the total consecutive movements that were this long */
                        }

                    }

                    else
                    {

                        if (movements_size > 2)
                        {

                            /* least squares fit to (root mean value^t) */

                            j = 0; /* element counter in LSQ fit approximation */
                            sx = (double) 0.0; /* sum of the time values */
                            sy = (double) 0.0; /* sum of the data values */
                            sxx = (double) 0.0; /* sum of the time values squared */
                            sxy = (double) 0.0; /* sum of the data values * the time values */

                            /* average  fit to (root mean value^t) */

                            running_avg = (double) 0.0; /* running average of persistence probability */

                            for (i = movements_size - 2; i >= 0;i --) /* for each element in the array of the count of consecutive like movements, in reverse order */
                            {

                                if (movements[i + 1] > n) /* only include if movements[i + 1] has more than n many counts in it */
                                {

                                    /* least squares fit to (root mean value^t) */

                                    temp1 = (double) (((double) movements[i + 1]) / ((double) movements[i])); /* save the element value */
                                    temp2 = (double) (j); /* save the time of the element */
                                    sx += temp2; /* add the time value to the sum of the time values */
                                    sy += temp1; /* add the data value to the sum of the data values */
                                    sxx += temp2 * temp2; /* add the square of the time value to the sum of the time values squared */
                                    sxy += temp2 * temp1; /* add the product of the time value and data value to the sum of the data values * the time values */

                                    /* average  fit to (root mean value^t) */

                                    running_avg = running_avg + (double) (((double) movements[i + 1]) / ((double) movements[i])); /* divide adjacent values in the cumulative distribution for the running average of persistence probability */

                                    j ++; /* increment the element counter in LSQ fit approximation */
                                }

                            }

                            det = ((double) j) * sxx - sx * sx;

                            if (det != (double) 0.0) /* protect from the "if (movements[i + 1] > n)" removing too much data */
                            {
                                a = (((double) j) * sxy - sx * sy) / det;
                                b = (-sx * sxy + sxx * sy) / det;

                                (void) printf ("%d\t%f\t%f\t%f\t%+ft\n", sample, (double) ((double) movements[1] / (double) movements[0]), running_avg / (double) (j), b, a); /* print the values of the distribution */
                            }

                        }

                    }

                }

                if (movements != (int *) 0) /* array of the count of consecutive like movements allocated? */
                {
                    free (movements); /* yes, free the array of the count of consecutive like movements allocated? */
                    movements = (int *) 0; /* array of the count of consecutive like movements */
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
