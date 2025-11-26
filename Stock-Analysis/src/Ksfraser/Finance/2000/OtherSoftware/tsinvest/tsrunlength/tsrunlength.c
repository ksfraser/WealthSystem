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

tsrunlength.c is for finding the run lengths of zero free intervals in
a time series, which is assumed to be a Brownian fractal.  The value
of each sample in the time series is stored, and the run length to a
like value in the time series is stored. A histogram of the number of
run lengths of each run length value is printed to stdout as tab
delimited columns of run length value, positive run lengths, negative
run lengths, and the sum of both positive and negative run lengths,
followed by the cumulative sum of the positive run lengths, the
cumulative sum of negative run lengths, and the cumulative sum of both
positive and negative run lengths.

The idea is to create a run length structure, that tallies how many
time intervals a run length was either positive or negative, for each
element in the time series. When a run length transition is made,
(ie., when the value of the time series has PASSED through the value
of the time series when the run length structure was created, from a
positive or negative direction,) then the run length is tallied into
histogram arrays, and the structure removed. See "Fractals, Chaos,
Power Laws," Manfred Schroeder, W. H. Freeman and Company, New York,
New York, 1991, ISBN 0-7167-2136-8, pp 160.

As approximations for the probability, p, of the run lengths, for t >>
1, p = 1 / (2 * x^(3/2)), which can be integrated for the cumulative
probability, P, for t >> 1, P = 1 / sqrt (t). For t ~ 1, P = erf (1 /
sqrt (t).

Note: there is an issue with this methodology-a run length is not
considered complete until the value is PASSED, so, for example, a
square wave function input will never be tallied, ie., a 1 to -1 to 1
to -1 to 2 sequence is a negative run length of 3 time units.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

$Revision: 0.0 $
$Date: 2006/01/05 19:36:00 $
$Id: tsrunlength.c,v 0.0 2006/01/05 19:36:00 john Exp $
$Log: tsrunlength.c,v $
Revision 0.0  2006/01/05 19:36:00  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>

#ifdef __STDC__

#include <float.h>

#else

#include <malloc.h>

#endif

static char rcsid[] = "$Id: tsrunlength.c,v 0.0 2006/01/05 19:36:00 john Exp $"; /* program version */
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
    "Find the run lengths of zero free intervals in a time series\n",
    "Usage: tsrunlength [-v] [filename]\n",
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
    "",
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

typedef struct run_length_struct /* structure for the the run length of zero free regions in the time series */
{
    struct run_length_struct *next; /* reference to the next run length structure in the list of run length structures */
    double start_value; /* current value when the run length started in the time series */
    int run_length; /* the count of elements in the time series that the value of the time series was above, or below start_value */
} RUN_LENGTH;

static int *positive_histogram = (int *) 0, /* reference to postive histogram array */
           *negative_histogram = (int *) 0; /* reference to negative histogram array */

static RUN_LENGTH *unused = (RUN_LENGTH *) 0, /* reference to the stack of unused run length structures, allocated run length structures are returned to this list for future usage */
                  *active = (RUN_LENGTH *) 0; /* reference to the stack of active run length structures, if a run length is active, ie., the length of zero free intervals in the time series is being counted, it is in this list */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static int tsgetopt (int argc, char *argv[], const char *opts); /* get an option letter from argument vector */
static int strtoken (char *string, char *parse_array, char **parse, const char *delim); /* parse a record based on sequential delimiters */
static RUN_LENGTH *get_run_length_struct (void); /* get a run length structure */
static int increment_histogram (int count); /* increment the size of the histogram arrays */

#else

static void print_message (); /* print any error messages */
static int tsgetopt (); /* get an option letter from argument vector */
static int strtoken ();  /* parse a record based on sequential delimiters */
static RUN_LENGTH *get_run_length_struct (); /* get a run length structure */
static int increment_histogram (); /* increment the size of the histogram arrays */

#endif

static char *optarg; /* reference to vector argument in tsgetopt () */

static int optind = 1; /* count of arguments in tsgetopt () */

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
        run_length, /* the count of elements in the time series that the value of the time series was above, or below start_value for an element in the stack of active run length structures */
        max_run_length = 0, /* the maximum count of elements in the time series that the value of the time series was above, or below start_value for an element in the stack of active run length structures */
        positive_run_lengths = 0, /* number of positive run lengths */
        negative_run_lengths = 0, /* number of negative run lengths */
        histogram_count; /* histogram element counter */

    double currentvalue = (double) 0.0, /* value of current sample in time series */
           start_value, /* current value when the run length started in the time series for an element in the stack of active run length structures */
           positive_sum = (double) 0.0, /* cumulative sum/distribution for positive run lengths */
           negative_sum = (double) 0.0, /* cumulative sum/distribution for negative run lengths */
           both_sum = (double) 0.0; /* cumulative sum/distribution for positive and negative run lengths */

    FILE *infile = stdin; /* reference to input file */

    RUN_LENGTH *element, /* reference to run length structure */
               *next, /* reference to next run length structure in the stack of active run length structures */
               *temp; /* temporary reference to next run length structure in the stack of active run length structures */

    while ((c = tsgetopt (argc, argv, "v")) != EOF) /* for each command line switch */
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

                if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the stock's record into fields, skip the record if there are no fields */
                {

                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                    {
                        currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */
                        count ++; /* increment the input file record counter */
                        retval = EALLOC; /* assume error allocating memory */

                        /*

                        increment the histogram arrays by one-this is
                        conservative, but it allows the arrays to
                        expanded and initialized dynamically, making
                        the elements in the array equal to the number
                        of records in the input file will accommodate
                        a worst case scenario of a run length spanning
                        the entire input file

                        */

                        if (increment_histogram (count) == 0)
                        {

                            /*

                            get a run length structure, and put it as
                            first in the list of run length
                            structures, this starts a run length
                            calculation for this element from the time
                            series-the run length is initialized to
                            zero, and will be determined as a positive
                            or negative run length in the next time
                            interval, (ie., if the movement from this
                            time interval to the next was "up", then
                            the run length will be positive, and
                            negative if the movement was "down",)

                            */

                            if ((element = get_run_length_struct ()) != (RUN_LENGTH *) 0) /* get a run length structure */
                            {
                                retval = NOERROR; /* assume no error */
                                element->next = active; /* save the reference to the next run length structure in the list of run length structures */
                                element->start_value = currentvalue; /* save the current value when the run length started in the time series */
                                element->run_length = 0; /* zero the count of elements in the time series that the value of the time series was above, or below start_value */
                                active = element; /* save the reference to the stack of active run length structures */
                                next = active->next; /* save the reference to last run length structure in the stack of active run length structures */

                                /*

                                for each element in the list of run
                                length structures, (excepting the
                                first, which was just added,) either
                                increment the positive or negative run
                                length values, or, if the run length
                                is finished, remove it from the list
                                of run length structures

                                */

                                while (next != (RUN_LENGTH *) 0) /* for each element in the stack of active run length structures */
                                {
                                    start_value = next->start_value; /* save the current value when the run length started in the time series for the element in the stack of active run length structures */
                                    run_length = next->run_length; /* save the count of elements in the time series that the value of the time series was above, or below start_value for the element in the stack of active run length structures */

                                    if (currentvalue > start_value) /* value of the current sample in the time series larger than the current value when the run length started in the time series? */
                                    {

                                        if (run_length > 0) /* count of elements in the time series that the value of the time series is greater than zero, ie., is the run length positive? */
                                        {
                                            next->run_length ++; /* yes, the run length is still positive, increment the count of elements in the time series that the value of the time series is greater than zero */
                                            element = next; /* the reference to run length structure references the next run length structure in the stack of active run length structures */
                                            next = next->next; /* the reference to next run length structure in the stack of active run length structures references the next run length structure in the stack of active run length structures */
                                        }

                                        else if (run_length < 0) /* count of elements in the time series that the value of the time series is less than zero, ie., is the run length negative? */
                                        {

                                            /*

                                            a zero transition occured
                                            from negative to positive;
                                            the run length is
                                            complete; remove the run
                                            length structure in the
                                            stack of active run length
                                            structures

                                            */

                                            negative_run_lengths ++; /* increment the number of negative run lengths */
                                            negative_histogram[-run_length] ++; /* increment the count of the negative run lengths in the negative histogram array */
                                            max_run_length = max_run_length > -run_length ? max_run_length : -run_length ; /* save the maximum count of elements in the time series that the value of the time series was above, or below start_value for an element in the stack of active run length structures */
                                            temp = next; /* save the temporary reference to next run length structure in the stack of active run length structures */
                                            element->next = next->next; /* reference to next run length structure in the stack of active run length structures */
                                            next = element->next; /* reference the next run length structure in the stack of active run length structures */
                                            temp->next = unused; /* "push" the element on stack of unused run length structures, allocated run length structures are returned to this list for future usage */
                                            unused = temp; /* reference the stack of unused run length structures, allocated run length structures are returned to this list for future usage */
                                        }

                                        else /* count of elements in the time series that the value of the time series is zero, ie., is the run length negative or positive? */
                                        {
                                            next->run_length ++; /* yes, the run length is starting positive, increment the count of elements in the time series that the value of the time series is greater than zero */
                                            element = next; /* the reference to run length structure references the next run length structure in the stack of active run length structures */
                                            next = next->next; /* the reference to next run length structure in the stack of active run length structures references the next run length structure in the stack of active run length structures */
                                        }

                                    }

                                    else if (currentvalue < start_value) /* value of the current sample in the time series smaller than the current value when the run length started in the time series? */
                                    {

                                        if (run_length > 0) /* count of elements in the time series that the value of the time series is greater than zero, ie., is the run length positive? */
                                        {

                                            /*

                                            a zero transition occured
                                            from positive to negative;
                                            the run length is
                                            complete; remove the run
                                            length structure in the
                                            stack of active run length
                                            structures

                                            */

                                            positive_run_lengths ++; /* increment the number of positive run lengths */
                                            positive_histogram[run_length] ++; /* increment the count of the positive run lengths in the positive histogram array */
                                            max_run_length = max_run_length > run_length ? max_run_length : run_length ; /* save the maximum count of elements in the time series that the value of the time series was above, or below start_value for an element in the stack of active run length structures */
                                            temp = next; /* save the temporary reference to next run length structure in the stack of active run length structures */
                                            element->next = next->next; /* reference to next run length structure in the stack of active run length structures */
                                            next = element->next; /* reference the next run length structure in the stack of active run length structures */
                                            temp->next = unused; /* "push" the element on stack of unused run length structures, allocated run length structures are returned to this list for future usage */
                                            unused = temp; /* reference the stack of unused run length structures, allocated run length structures are returned to this list for future usage */
                                        }

                                        else if (run_length < 0) /* count of elements in the time series that the value of the time series is less than zero, ie., is the run length negative? */
                                        {
                                            next->run_length --; /* yes, the run length is still negative, decrement the count of elements in the time series that the value of the time series is less than zero */
                                            element = next; /* the reference to run length structure references the next run length structure in the stack of active run length structures */
                                            next = next->next; /* the reference to next run length structure in the stack of active run length structures references the next run length structure in the stack of active run length structures */
                                        }

                                        else /* count of elements in the time series that the value of the time series is zero, ie., is the run length negative or positive? */
                                        {
                                            next->run_length --; /* yes, the run length is starting negative, decrement the count of elements in the time series that the value of the time series is less than zero */
                                            element = next; /* the reference to run length structure references the next run length structure in the stack of active run length structures */
                                            next = next->next; /* the reference to next run length structure in the stack of active run length structures references the next run length structure in the stack of active run length structures */
                                        }

                                    }

                                    else /* value of the current sample in the time series equal to the current value when the run length started in the time series? */
                                    {

                                        if (run_length > 0) /* count of elements in the time series that the value of the time series is greater than zero, ie., is the run length positive? */
                                        {
                                            next->run_length ++; /* yes, the run length is still positive, increment the count of elements in the time series that the value of the time series is greater than zero */
                                            element = next; /* the reference to run length structure references the next run length structure in the stack of active run length structures */
                                            next = next->next; /* the reference to next run length structure in the stack of active run length structures references the next run length structure in the stack of active run length structures */
                                        }

                                        else if (run_length < 0) /* count of elements in the time series that the value of the time series is less than zero, ie., is the run length negative? */
                                        {
                                            next->run_length --; /* yes, the run length is still negative, decrement the count of elements in the time series that the value of the time series is less than zero */
                                            element = next; /* the reference to run length structure references the next run length structure in the stack of active run length structures */
                                            next = next->next; /* the reference to next run length structure in the stack of active run length structures references the next run length structure in the stack of active run length structures */
                                        }

                                        else /* count of elements in the time series that the value of the time series is zero, ie., is the run length negative or positive? */
                                        {

                                            /*

                                            a zero transition occured
                                            from zero; the run length
                                            is complete; remove the
                                            run length structure in
                                            the stack of active run
                                            length structures

                                            note: what happened here
                                            is that the value of the
                                            time series in the last
                                            interval is equal to the
                                            value of the time series
                                            in this time
                                            interval-there are other
                                            ways of handling this, but
                                            since there was no
                                            movement, it seems
                                            reasonable that the run
                                            length be terminated

                                            */

                                            positive_histogram[0] ++; /* increment the count of the positive run lengths in the positive histogram array */
                                            negative_histogram[0] ++; /* increment the count of the negative run lengths in the negative histogram array */
                                            temp = next; /* save the temporary reference to next run length structure in the stack of active run length structures */
                                            element->next = next->next; /* reference to next run length structure in the stack of active run length structures */
                                            next = element->next; /* reference the next run length structure in the stack of active run length structures */
                                            temp->next = unused; /* "push" the element on stack of unused run length structures, allocated run length structures are returned to this list for future usage */
                                            unused = temp; /* reference the stack of unused run length structures, allocated run length structures are returned to this list for future usage */
                                        }

                                    }

                                }

                            }

                            else
                            {
                                break; /* couldn't get a run length structure, stop reading the records from the input file */
                            }

                        }

                        else
                        {
                            break; /* couldn't increment the histogram array, stop reading the records from the input file */
                        }

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

            if (retval == NOERROR) /* any errors? */
            {

                /*

                finished-print the histogram arrays and cumulative sum
                of the histogram arrays

                note: there are probably run length structures in the
                stack of active run length structures-these are run
                lengths that have not finished yet-ignore them

                */

                for (histogram_count = 1; histogram_count <= max_run_length; histogram_count ++) /* no, for each element in the histogram arrays that have run lengths */
                {
                    positive_sum = positive_sum + (double) ((double) positive_histogram[histogram_count] / (double) positive_run_lengths); /* sum to the cumulative sum/distribution for positive run lengths */
                    negative_sum = negative_sum + (double) ((double) negative_histogram[histogram_count] / (double) negative_run_lengths); /* sum to the cumulative sum/distribution for negative run lengths */
                    both_sum = both_sum + (double) ((double) (positive_histogram[histogram_count] + negative_histogram[histogram_count]) / (double) (positive_run_lengths + negative_run_lengths)); /* sum to the cumulative sum/distribution for positive and negative run lengths */
                    (void) printf ("%d\t%f\t%f\t%f\t%f\t%f\t%f\n", histogram_count, (double) ((double) positive_histogram[histogram_count] / (double) positive_run_lengths), (double) ((double) negative_histogram[histogram_count] / (double) negative_run_lengths), (double) ((double) (positive_histogram[histogram_count] + negative_histogram[histogram_count]) / (double) (positive_run_lengths + negative_run_lengths)), (double) 1.0 - positive_sum, (double) 1.0 - negative_sum, (double) 1.0 - both_sum); /* print the run lengths */
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

Get an option letter from argument vector.

int tsgetopt (int argc, char *argv[], const char *opts);

I) The compiler will warn "optopt not accessed" - optopt is left in
for compatability with system V.

II) The tsgetopt function returns the next option letter in argv that
matches a letter in opts to parse positional parameters and check for
options that.  are legal for the command

III) The variable opts must contain the option letters the command
using tsgetopt () will recognize; if a letter is followed by a colon,
the option is expected to have an argument, or group of arguments,
which must be separated from it by white space.

IV) The variable optarg is set to point to the start of the
option-argument on return from tsgetopt ().

V) The function tsgetopt () places in optind the argv index of the
next argument to be processed- optind is an external and is
initialized to 1 before the first call to tsgetopt ().

VI) When all options have been processed (i.e., up to the first
non-option argument), tsgetopt () returns a EOF. The special option
"--" may be used to delimit the end of the options; when it is
encountered, EOF will be returned, and "--" will be skipped.

VII) The following rules comprise the System V standard for
command-line syntax:

    1) Command names must be between two and nine characters.

    2) Command names must include lowercase letters and digits only.

    3) Option names must be a single character in length.

    4) All options must be delimited by the '-' character.

    5) Options with no arguments may be grouped behind one delimiter.

    6) The first option-argument following an option must be preceeded
    by white space.

    7) Option arguments cannot be optional.

    8) Groups of option arguments following an option must be
    separated by commas or separated by white space and quoted.

    9) All options must precede operands on the command line.

    10) The characters "--" may be used to delimit the end of the
    options.

    11) The order of options relative to one another should not
    matter.

    12) The order of operands may matter and position-related
    interpretations should be determined on a command-specific basis.

    13) The '-' character precded and followed by white space should
    be used only to mean standard input.

VIII) Changing the value of the variable optind or calling tsgetopt
with different values of argv may lead to unexpected results.

IX) The function tsgetopt () prints an error message on standard error
and returns a question mark (?) when it encounters an option letter
not included in opts or no option-argument after an option that
expects one; this error message may be disabled by setting opterr to
0.

X) Example usage:

    int main (int argc,char *argv[])
        {
            int c;

            .
            .
            .

            while ((c = tsgetopt (argc,argv,"abo:")) != EOF)
            {

                switch (c)
                {

                    case 'a':

                        'a' switch processing

                        .
                        .
                        .
                        break;

                    case 'b':

                        'b' switch processing

                        .
                        .
                        .
                        break;

                    case 'o':

                        'o' switch processing

                        (this switch requires argument(s), separated by white space)

                        .
                        .
                        .
                        break;

                    case '?':

                        illegal switch processing

                        .
                        .
                        .
                        break;

                }

            }
            .
            .
            .

            for (;optind < argc;optind ++)
            {

                non-switch option processing

                .
                .
                .
            }

            .
            .
            .
        }

XI) Returns the next option letter in argv that matches a letter in
opts, or EOF on error or no more arguments.

*/

static int opterr = 1, /* print errors, 0 = no, 1 = yes */
           optopt;  /* next character in argument */

#ifdef __STDC__

static int tsgetopt (int argc, char *argv[], const char *opts)

#else

static int tsgetopt (argc, argv, opts)
int argc;
char *argv[];
char *opts;

#endif

{
    static int sp = 1; /* implicit index of argument in opts */

    char *cp;

    int c; /* argument option letter */

    if (sp == 1) /* first implicit index of argument in opts? */
    {

        if (optind >= argc || argv[optind][0] != '-' || argv[optind][1] == '\0') /* yes, argument? */
        {
            return (EOF); /* no, processing is through, return EOF */
        }

    }

    else if (strcmp (argv[optind], "--") == 0) /* request for end of arguments? */
    {
        optind ++; /* yes, next argument is not an option */
        return (EOF); /* processing is through, return EOF */
    }

    optopt = c = argv[optind][sp]; /* handle the next character in this argument */

    if (c == ':' || (cp = strchr (opts, c)) == 0) /* if an argument follows the option, or this is another option */
    {

        if (opterr) /* if error */
        {
           (void) fprintf (stderr, "%s: illegal option -- %c\n", argv[0], (char)(c)); /* print the error */
        }

        if (argv[optind][++ sp] == '\0') /* if end of procssing this argument */
        {
            optind ++; /* prepare for the next */
            sp = 1; /* at the first character of the next */
        }

        return ('?'); /* force a question, generally, a request for help */
    }

    if (*++cp == ':') /* next argument an argument to the option? */
    {

        if (argv[optind][sp + 1] != '\0') /* yes, is the argument there? */
        {
            optarg = &argv[optind ++][sp + 1]; /* yes, reference it */
        }

        else if (++ optind >= argc) /* no, too few arguments? */
        {

            if (opterr) /* yes, under error? */
            {
               (void) fprintf (stderr, "%s: option requires an argument -- %c\n", argv[0], (char)(c)); /* yes, print the error */
            }

            sp = 1; /* implicitly index the next character */
            return ('?'); /* force a question, generally, a request for help */
        }

        else
        {
            optarg = argv[optind ++]; /* else the next argument is the required argument, reference the next argument to the option */
        }

        sp = 1; /* implicitly index the next character */
    }

    else
    {

        if (argv[optind][++ sp] == '\0') /* single letter option, no argument? */
        {
            sp = 1; /* yes, first character of next argument */
            optind ++; /* reference next argument */
        }

        optarg = 0; /* no argument follows single character options */
    }

    return (c); /* return the argument option letter */
}

/*

Parse a record based on sequential delimiters.

int strtoken (char *string, char *parse_array, char **parse, const char *delim);

Parse a character array, string, into an array, parse_array, using
consecutive characters from delim as field delimiters, point the
character pointers, token, to the beginning of each field.

Returns the number of fields parsed.

*/

#ifdef __STDC__

static int strtoken (char *string, char *parse_array, char **parse, const char *delim)

#else

static int strtoken (string, parse_array, parse, delim)
char *string;
char *parse_array;
char **parse;
char *delim;

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

/*

Get a run length structure.

static RUN_LENGTH *get_run_length_struct (void);

If a run length structure has already been allocated, but is not
currently used, "pop" if from the stack of unused run length
structures, if not, allocate the structure.

Returns a reference to the structure, zero if error.

*/

#ifdef __STDC__

static RUN_LENGTH *get_run_length_struct (void)

#else

static RUN_LENGTH *get_run_length_struct ()

#endif

{
    RUN_LENGTH *element; /* reference to run length structure to be returned */

    if (unused != (RUN_LENGTH *) 0) /* reference to the stack of unused run length structures have any structures? */
    {
        element = unused; /* yes, save the reference to run length structure to be returned */
        unused = unused->next; /* save the reference to the stack of unused run length structures */
        element->next = (RUN_LENGTH *) 0; /* zero the reference to the next run length structure in the list of run length structures */
        element->start_value = (double) 0.0; /* zero the current value when the run length started in the time series */
        element->run_length = 0; /* zero the count of elements in the time series that the value of the time series was above, or below start_value */
    }

    else
    {

        if ((element = (RUN_LENGTH *) malloc (sizeof (RUN_LENGTH))) != (RUN_LENGTH *) 0) /* no, allocate the run length structure */
        {
            element->next = (RUN_LENGTH *) 0; /* zero the reference to the next run length structure in the list of run length structures */
            element->start_value = (double) 0.0; /* zero the current value when the run length started in the time series */
            element->run_length = 0; /* zero the count of elements in the time series that the value of the time series was above, or below start_value */
        }

    }

    return (element); /* return the reference to run length structure */
}

/*

Increment the size of the histogram arrays.

static int increment_histogram (int count);

If the postive and negative histogram arrays have not been allocated,
then allocate them, else, reallocate them to size count, which should
always be the number of elements in the time series.

Returns zero, non-zero if error.

*/

#ifdef __STDC__

static int increment_histogram (int count)

#else

static int increment_histogram (count)
int count;

#endif

{
    static int current_size = 0; /* current size of histogram arrays */

    int retval = EALLOC; /* return value, assume error allocating memory */

    if (current_size == 0) /* histogram arrays been allocated? */
    {

        if (count == 1) /* no, increment for first element in time series? */
        {

            if ((positive_histogram = (int *) malloc (2 * sizeof (int))) != (int *) 0) /* allocate the postive histogram array */
            {

                if ((negative_histogram = (int *) malloc (2 * sizeof (int))) != (int *) 0) /* allocate the negative histogram array */
                {
                    retval = NOERROR; /* assume no error */
                }

            }

        }

    }

    else
    {

        if (count == current_size + 1) /* yes, request to increment the size of the histogram arrays? */
        {

            if ((positive_histogram = (int *) realloc (positive_histogram, (size_t) (count + 1) * sizeof (int))) != (int *) 0) /* allocate the postive histogram array */
            {

                if ((negative_histogram = (int *) realloc (negative_histogram, (size_t) (count + 1) * sizeof (int))) != (int *) 0) /* allocate the negative histogram array */
                {
                    retval = NOERROR; /* assume no error */
                }

            }

        }

    }

    positive_histogram[current_size] = 0; /* null the positive histogram count for the added element */
    negative_histogram[current_size] = 0; /* null the negative histogram count for the added element */
    current_size ++; /* increment the current size of the histogram arrays */
    return (retval); /* return any error */
}
