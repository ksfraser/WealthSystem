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

    http://www.johncon.com/ntropix/
    http://www.johncon.com/

------------------------------------------------------------------------------

csv2tsinvest.c is a C source code template for programs that convert
different time series formats and structures to the tsinvest(1) time
series database(s) format.

The example application is the Yahoo! historical stock price database
spreadsheet format, csv, available from http://chart.yahoo.com/d by
specifying "Download Spreadsheet Format" at the bottom of the page
when requesting the time series for a stock. For example, the csv
format for ticker symbol GE looks like:

    Date,Open,High,Low,Close,Volume
    26-Oct-99,125.9375,127.25,124.9375,125.625,6226400
    25-Oct-99,124.625,125.9375,123.625,125.25,4565300
    22-Oct-99,123.50,126.1875,123.4375,125.625,5705400
    .
    .
    .
    6-Jan-70,0.8956,0.8956,0.8662,0.8706,35500
    5-Jan-70,0.9015,0.9059,0.8897,0.8971,42400
    2-Jan-70,0.9118,0.9133,0.90,0.9015,23200

Which needs to be converted to the tsinvest(1) database format.

The tsinvest(1) time series database file structure is a text file
consisting of records, in temporal order, one record per time series
sample of each equity.  Blank records are ignored, and comment records
are signified by a '#' character as the first non white space
character in the record. Each data record represents an equity
transaction, consisting of a minium of three fields, separated by
white space. The fields are ordered by time stamp, equity ticker
identifier, and closing price, for example:

    1      ABC     333.6
    2      DEF     7.2
    3      GHI     335.9

GENERAL COMMENTS

There are certain advantages to imposing additional structural
requirements on the tsinvest(1) time series database file
structure. For example, although tsinvest(1) places no constraints on
white space field delimiters, if the multiple consecutive white space
characters are required to be exactly a single tab character, the time
series database becomes a "Unix standard" tab delimited tabular text
relational database, and can be manipulated with the traditional Unix
commands, sed(1), awk(1), cut(1), paste(1), etc.

Additionally, although tsinvest(1) places no context or meaning on the
time stamp in the first field, if the time stamp values are required
to be lexical on the temporal order, databases of individual stocks
can be combined into a market by concatenating the files of individual
stocks, and then sorting on the first field with the traditional Unix
commands, cat(1), and sort(1). For example:

    cat stock1.file ... stockn.file | sort > market.file

There are, obviously, many alternatives for importing stock price time
series into the tsinvest(1) suite:

    1) The Unix standard tab delimited tabular text relational
    database is a very extensible universal file structure, and a
    significant infrastructure exists for translating and manipulating
    such files with programs like perl(1), sed(1), awk(1), cut(1),
    paste(1), etc., which can be used in shell script programs.

    2) The sources, in main (), of tsinvest(1) can be modified to
    accommodate some file structures. Frequently, all that has to be
    changed are the field number implicit addresses, and perhaps the
    field delimiter, since the field sequence, (time stamp, symbol
    name, price,) is fairly standard-although intermixed with other
    data, such as open, high, low, and volume.

    3) A separate program can be written, in a compiled language, to
    do the translation from one file format to another.

PROGRAM ARCHITECTURE

I) Data architecture:

    A) Each time stamp has a data structure, of type VALUE, that
    contains the day, month, year, and closing price of the stock.  An
    array is constructed, one per day, (because the sequence of time
    stamps has to be reversed,) for each day represented in the input
    file.

II) Program description:

    A) The function main serves to read the input file, loading the
    array of type VALUE, one element per each valid input file record.

        1) handle any command line arguments.

        2) Open the input file.

        3) For each record in the input file:

            a) Parse the record using the function int strtoken (),
            checking that the record has exactly 6 fields, and if it
            does, then check that the equity's value represented by
            this record is greater than zero.

            b) The time stamp field is parsed, using the function int
            strtoken (), checking that the field has exactly 3 fields,
            and if it does, it is converted to integers for the day,
            month, and year. The month is converted from 3 character
            abbreviations to an integer in an if/else if construct-if
            the month is not recognized, the entire record is ignored.

        4) After the input file has been read, the array of type VALUE
        is dumped to the stdout, in reverse order.

III) Constructional and stylistic issues follow, generally, a
compromise agreement with the following references:

    A) "C A Reference Manual", Samuel P.  Harbison, Guy L.  Steele
    Jr. Prentice-Hall, 1984.

    B) "C A Reference Manual, Second Edition", Samuel P.  Harbison,
    Guy L. Steele Jr.  Prentice-Hall, 1987.

    C) "C Programming Guidelines", Thomas Plum.  Plum Hall, 1984.

    D) "C Programming Guidelines, Second Edition", Thomas Plum.  Plum
    Hall, 1989.

    E) "Efficient C", Thomas Plum, Jim Brodie.  Plum Hall, 1985.

    F) "Fundamental Recommendations on C Programming Style", Greg
    Comeau. Microsoft Systems Journal, vol 5, number 3, May, 1990.

    G) "Notes on the Draft C Standard", Thomas Plum.  Plum Hall, 1987.

    H) "Portable C Software", Mark R.  Horton.  Printice Hall, 1990.

    I) "Programming Language - C", ANSI X3.159-1989.  American
    National Standards Institute, 1989.

    J) "Reliable Data Structures", Thomas Plum.  Plum Hall, 1985.

    K) "The C Programming Language", Brian W.  Kernighan and Dennis
    M. Ritchie.  Printice-Hall, 1978.

    Each "c" source file has an "rcsid" static character array that
    contains the revision control system "signatures" for that
    file. This information is included in the "c" source file and in
    all object modules for audit and maintenence.

    If the stylistics listed below are annoying, the indent program
    from the gnu foundation, (anonymous ftp to prep.ai.mit in
    /pub/gnu,) is available to convert from these stylistics to any
    desirable.

    Both ANSI X3.159-1989 and Kernighan and Ritchie standard
    declarations are supported, with a typical construct:

        #ifdef __STDC__

            ANSI declarations.

        #else

            K&R declarations.

        #endif

    Brace/block declarations and constructs use the stylistic, for
    example:

        for (this < that; this < those; this ++)
        {
            that --;
        }

        as opposed to:

        for (this < that; this < those; this ++) {
            that --;
        }

    Nested if constructs use the stylistic, for example:

        if (this)
        {

            if (that)
             {
                 .
                 .
                 .
             }

        }

        as opposed to:

        if (this)
            if (that)
                 .
                 .
                 .

    The comments in the source code are verbose, and beyond the
    necessity of commenting the program operation, and the one liberty
    taken was to write the code on a 132 column display. Many of the
    comments in the source code occupy the full 132 columns, (but do
    not break up the code's flow with interline comments,) and are
    incompatible with text editors like vi(1). The rationale was that
    it is easier to remove them with something like:

        sed "s/\/\*.*\*\//" sourcefile.c > ../new/sourcefile.c

    than to add them. Unfortunately, in the standard distribution of
    Unix, there is no inverse command.

$Revision: 1.7 $
$Date: 2006/01/07 10:05:09 $
$Id: csv2tsinvest.c,v 1.7 2006/01/07 10:05:09 john Exp $
$Log: csv2tsinvest.c,v $
Revision 1.7  2006/01/07 10:05:09  john
Initial revision


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#ifndef __STDC__

#include <malloc.h>

#endif

static char rcsid[] = "$Id: csv2tsinvest.c,v 1.7 2006/01/07 10:05:09 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Convert a csv spreadsheet time series to tsinvest time series database\n",
    "Usage: csv2tsinvest symbol [-v] [filename]\n",
    "    symbol, symbol name of stock\n",
    "    -v, print the version and copyright banner of this program\n",
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
#define EARGS 1 /* command line argument error */
#define EOPEN 2 /* error opening file */
#define ECLOSE 3 /* error closing file */
#define EALLOC 4 /* error allocating memory */

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b," /* file record field separators */

typedef struct value_struct /* structure for each stock's close value */
{
    int sday, /* the integer value of the time stamp day, ie., 1 to 31 */
        smonth, /* the integer value of the time stamp month, ie., 1 to 12 */
        syear; /* the integer value of the time stamp year */
    double value; /* the closing value of the stock at the time stamp */
} VALUE;

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static int strtoken (char *string, char *parse_array, char **parse, const char *delim); /* parse a record based on sequential delimiters */
static int tsgetopt (int argc, char *argv[], const char *opts); /* get an option letter from argument vector */

#else

static void print_message (); /* print any error messages */
static int strtoken ();  /* parse a record based on sequential delimiters */
static int tsgetopt (); /* get an option letter from argument vector */

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
         *stock_name, /* reference to argv[] for stock name */
         *temp; /* reference to token[1] in month integer determination */

    int retval = EARGS, /* return value, assume not enough arguments */
        fields, /* number of fields in a record */
        count = 0, /* the count of time stamps */
        currentday, /* the integer value of the time stamp day, ie., 1 to 31 */
        currentmonth, /* the integer value of the time stamp month, ie., 1 to 12 */
        currentyear, /* the integer value of the time stamp year */
        c; /* command line switch */

    double currentvalue; /* current value of stock */

    VALUE *series = (VALUE *) 0, /* reference to the array containing closing data for the stock */
          *last_series = (VALUE *) 0; /* reference to the last array containing closing data for the stock */

    FILE *infile = stdin; /* reference to input file */

    while ((c = tsgetopt (argc, argv, "hv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'v':

                (void) printf ("%s\n", rcsid); /* print the version */
                (void) printf ("%s\n", copyright); /* print the copyright */
                optind = argc; /* force argument error */
                break;

            case '?':

                break;

            case 'h': /* request for help? */

                optind = argc; /* force argument error */
                break;

            default: /* illegal switch? */

                optind = argc; /* force argument error */
                break;
        }

    }

    if (argc - optind > 0) /* enough arguments? */
    {
        stock_name = argv[optind]; /* save the reference to argv[] for stock name */
        optind ++; /* increment the count of arguments in tsgetopt () */
        retval = EOPEN; /* assume error opening file */

        if ((infile = (argc <= optind) ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the stock's input file */
        {
            retval = NOERROR; /* assume no errors */

            while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the next record from the stock's input file */
            {

                if ((fields = strtoken (buffer, parsebuffer, token, TOKEN_SEPARATORS)) != 0) /* parse the stock's record into fields, skip the record if there are no fields */
                {

                    if (fields == 7) /* 6 fields are required */ /*Yahoo changes their format */
                    {
                        currentvalue = atof (token[4]); /* save the current value of the stock */

                        if (currentvalue > (double) 0.0) /* a negative or zero value(s) makes no sense, add protection */
                        {

                            if ((fields = strtoken (token[0], parsebuffer, token, "-")) != 0) /* parse the stock's first field into the date */
                            {

                                if (fields == 3) /* 3 fields are required */
                                {
                                    currentday = atoi (token[0]); /* save the integer value of the time stamp day, ie., 1 to 31 */
                                    temp = token[1]; /* save the reference to token[2] in month integer determination */
                                    if (strcmp (temp, "Jan") == 0)
                                        currentmonth = 1; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Feb") == 0)
                                        currentmonth = 2; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Mar") == 0)
                                        currentmonth = 3; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Apr") == 0)
                                        currentmonth = 4; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "May") == 0)
                                        currentmonth = 5; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Jun") == 0)
                                        currentmonth = 6; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Jul") == 0)
                                        currentmonth = 7; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Aug") == 0)
                                        currentmonth = 8; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Sep") == 0)
                                        currentmonth = 9; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Oct") == 0)
                                        currentmonth = 10; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Nov") == 0)
                                        currentmonth = 11; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "Dec") == 0)
                                        currentmonth = 12; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "01") == 0)
                                        currentmonth = 1; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "02") == 0)
                                        currentmonth = 2; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "03") == 0)
                                        currentmonth = 3; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "04") == 0)
                                        currentmonth = 4; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "05") == 0)
                                        currentmonth = 5; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "06") == 0)
                                        currentmonth = 6; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "07") == 0)
                                        currentmonth = 7; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "08") == 0)
                                        currentmonth = 8; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "09") == 0)
                                        currentmonth = 9; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "10") == 0)
                                        currentmonth = 10; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "11") == 0)
                                        currentmonth = 11; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else if (strcmp (temp, "12") == 0)
                                        currentmonth = 12; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                    else
                                        currentmonth = 0; /* error, none of the above, save the integer value of the time stamp month, ie., 1 to 12, as zero to signify the error */
                                    if (currentmonth > 0) /* integer value of the time stamp year signified an error? */
                                    {
                                        currentyear = atoi (token[2]); /* save the integer value of the time stamp year */

                                        if ((series = (VALUE *) realloc (series, (size_t) (count + 1) * sizeof (VALUE))) != (VALUE *) 0) /* allocate space for the array containing closing data for the stock */
                                        {
                                            series[count].sday = currentday; /* save the integer value of the time stamp day, ie., 1 to 31 */
                                            series[count].smonth = currentmonth; /* save the integer value of the time stamp month, ie., 1 to 12 */
                                            series[count].syear = currentyear; /* save the integer value of the time stamp year */
                                            series[count].value = currentvalue; /* save the closing value of the stock at the time stamp */
                                            count ++; /* increment the count of time stamps */
                                        }

                                        else
                                        {
                                            retval = EALLOC; /* assume error allocating memory */
                                            series = last_series; /* restore the reference to the array containing closing data for the stock */
                                            break;
                                        }

                                    }

                                    else
                                    {
                                        (void) fprintf (stderr, "Illegal month name: %s", buffer); /* illegal month name, print the error and continue */
                                    }

                                }

                                else
                                {
                                    (void) fprintf (stderr, "Illegal number of date fields: %s", buffer); /* illegal number of date fields, print the error and continue */
                                }

                            }

                        }

                        else
                        {
                            (void) fprintf (stderr, "Illegal stock value: %s", buffer); /* illegal stock value, print the error and continue */
                        }

                    }

                    else
                    {
                        (void) fprintf (stderr, "Illegal number of fields: %s", buffer); /* illegal number of fields, print the error and continue */
                    }

                }

            }

            if (retval == NOERROR) /* any errors? */
            {

                if (count > 0) /* any time stamps? */
                {

                    do  /* moving backwards through the array containing closing data for the stock */
                    {
                        count --; /* decrement the count of time stamps */
                        (void) printf ("%02d%02d%02d\t%s\t%f\n", series[count].syear, series[count].smonth, series[count].sday, stock_name, series[count].value); /* print the stock's time stamp and closing value */
                    }
                    while (count > 0);

                }

            }

            if (argc > optind) /* using stdin as input? */
            {

                if (fclose (infile) == EOF) /* no, close the input file */
                {
                    retval = ECLOSE; /* error closing file */
                }

            }

            if (series != (VALUE *) 0) /* array containing closing data for the stock allocated? */
            {
                free (series); /* yes, free the array containing closing data for the stock allocated? */
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

Parse a record based on sequential delimiters.

int strtoken (char *string, char *parse_array, char **parse, const char *delim);

I) Parse a character array, string, into an array, parse_array, using
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
