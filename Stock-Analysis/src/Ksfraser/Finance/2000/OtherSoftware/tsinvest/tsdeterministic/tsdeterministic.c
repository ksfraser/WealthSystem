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

tsdeterministic.c for determining if a time series was created by a
deterministic mechanism. The idea is place each element of a time
series in an array structure that contains the element and the next
element in the time series, and then sort the array. The array is
output and may be plotted. For example, using the program
tsdlogistic(1) to make a discrete time series of the logistic,
(quadratic function,) with the following command:

    tsdlogistic -a 4 -b -4 1000 > XXX

and then using this program on the output file, XXX, will result in a
plot of a parabola.  See "Chaos and Fractals: New Frontiers of
Science," Heinz-Otto Peitgen and Hartmut Jurgens and Dietmar Saupe,
Springer-Verlag, 1992, pp. 745.

$Revision: 0.0 $
$Date: 2006/01/18 19:36:00 $
$Id: tsdeterministic.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsdeterministic.c,v $
Revision 0.0  2006/01/18 19:36:00  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

static char rcsid[] = "$Id: tsdeterministic.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Determine if a time series is deterministic\n",
    "Usage: tsdeterministic [-v] [filename]\n",
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

#define MIDWAY(a,b) (int) (((unsigned int) (a) + (unsigned int) (b)) / (unsigned int) 2)

typedef struct element /* structure for two consecutive elements from the time series */
{
    double current; /* current value of element in time series */
    double next; /* value of next element in time series */
} DATA;

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static int strtoken (char *string, char *parse_array, char **parse, char *delim);
static void str_sort (DATA *array, int bottom, int top);

#else

static void print_message (); /* print any error messages */
static int strtoken ();
static void str_sort ();

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
        i, /* loop counter */
        retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        c; /* command line switch */

    double currentvalue; /* value of current element from the time series */

    FILE *infile = stdin; /* reference to input file */

    DATA *data = (DATA *) 0, /* reference to array of time values of type DATA from file */
         *lastdata = (DATA *) 0; /* last reference to array of data from file */

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

                        if ((data = (DATA *) realloc (data, (count + 1) * sizeof (DATA))) == (DATA *) 0) /* allocate space for the array of data values from the input file */
                        {
                            data = lastdata; /* couldn't allocate space for the array of data values from the input file, restore the last reference to array of data from file */
                            retval = EALLOC;  /* assume error allocating memory */
                            break; /* and stop */
                        }

                        currentvalue = atof (token[fields - 1]); /* no, save the sample's value at that time */
                        data[count].current = currentvalue; /* include the sample in the data array */

                        if (count > 0) /* if not the first record of the file */
                        {
                            data[count - 1].next = currentvalue; /* include the sample in the data array */
                        }

                        count ++; /* increment the file record counter */
                    }

                }

            }

            if (retval == NOERROR) /* no errors? */
            {
                str_sort (data, 0, count - 2); /* sort the DATA array */
                count --; /* remove the last element from the DATA array which has no next element */

                for (i = 0; i < count; i ++) /* for each element in the DATA array */
                {
                    (void) printf ("%f\t%f\n", data[i].current, data[i].next); /* print the value of the element and the value of the next element */
                }

            }

            if (data != (DATA *) 0) /* allocated space for the array of data values from the input file? */
            {
                free (data); /* yes, free the array of data from the file */
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

strsort.c - binary sort routine

static void str_sort (DATA *array, int bottom, int top);

str_sort (), quick sorts an array of type DATA, and returns nothing.

note: this is a recursive proceedure, requiring three references, and
4 integers to be allocated on the stack for each recursion

note: on entry, argument "bottom" should be zero, and argument "top"
should be the index of the last array element, ie., if the array
contains n many elements, then the call would be:

    str_sort (array, 0, n - 1);

*/

#ifdef __STDC__

static void str_sort (DATA *array, int bottom, int top)

#else

static void str_sort (array, bottom, top)
    DATA *array;
    int bottom;
    int top;

#endif

{
    int i = bottom, /* bottom index of the array */
        j = top; /* top index of the array */

    DATA x,
         y;

    x = array[MIDWAY(bottom, top)]; /* starting in the middle of the array */

    do /* while the bottom moving index of the array is less than or equal to the top moving index of the array */
    {

        while (array[i].current < x.current && i < top) /* increment the bottom moving index of the array while the elements referenced are lexically "less" */
        {
            i ++;
        }

        while (array[j].current > x.current && j > bottom) /* decrement the top moving index of the array while the elements referenced are lexically "greater" */
        {
            j --;
        }

        if (i <= j) /* if the bottom moving index is less than or equal to the top moving index of the array */
        {
            y = array[i]; /* swap the element references, increment the bottom moving index, decrement the top moving index */
            array[i] = array[j];
            array[j] = y;
            i ++;
            j --;
        }

    }
    while (i <= j);

    if (bottom < j) /* if the top moving index is not at the bottom of the array elements, sort the remaining elements */
    {
        str_sort (array, bottom, j);
    }

    if (i < top) /* if the bottom moving index is not at the top of the array elements, sort the remaining elements */
    {
        str_sort (array, i, top);
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
