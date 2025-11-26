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

tsinvestsim.c, generate a time series for the tsinvest(1) program.
Generates a fractal time series, of many equities, concurrently.

The time series' increments for each equity is generated as a binomial
distribution, (which is a reasonably close approximation to a Gaussian
distribution, and is a reasonably close first order approximation to
an equity's value, over time.)

The input file is organized, one equity per record, with each record
having up to four fields, of which only the Shannon probability need
be specified. The fields are sequential, in any order, with the field
type specified by a single letter-P for the Shannon probability, H for
the short term, (Markov,) Hurst exponent, F for the wager fraction, I
for the initial value, and L if the distribution of the marginal
increments for a stock is to have exponential leptokurtosis. Any field
that is not one of these letters is assumed to be the equity's
name. For example:

    ABC, P = 0.51, F = 0.01, I = 31
    DEF, P = 0.52, F = 0.02, I = 4, L = 1
    GHI, P = 0.53, F = 0.03, I = 65

Naturally, single letter equity names should be avoided, (since P, H,
F, I, and L, are reserved tokens.) Any punctuation is for clarity, and
ignored. Upper or lower case characters may be used. The fields are
delimited by whitespace, or punctuation. Comment records are are
signified by a '#' character as the first non whitespace character in
a record. Blank records are ignored.

The output file structure is a text file consisting of records, in
temporal order, one record per time series sample of each equity.
Blank records are ignored, and comment records are signified by a '#'
character as the first non white space character in the record. Each
data record represents an equity transaction, consisting of a minium
of three fields, separated by white space. The fields are ordered by
time stamp, equity ticker identifier, and closing price, for example:

    0       GHI     62.270000
    0       DEF     3.967743
    0       ABC     30.752000
    1       GHI     64.885340
    1       DEF     3.951808
    1       ABC     29.890944

The index of various American equity markets, since 1950, can be
simulated with 10 equities, each having p = 0.505, and f = 0.03. For
the last few years, p = 0.51 to 0.52 should be used, with f = 0.03 for
each equity. In general, more than 10 equities will have to be
simulated, making the index volatility, ie., the root mean square of
the normalized increments of the index, too small.

DERIVATION

The equity value simulator produces a time series, with normal, or
Gaussian, distributed increments, approximated with a binomial
distribution, produced by sequential calls to a uniform deviate random
number generator, and using the formula:

    V(t) = V(t - 1)(1 + f * F(t))

where V(t - 1) and V(t) is the previous and next values of the equity
in the recursion, F(t) is the normal, or Gaussian, distributed
fluctuations in the equity's value, of unit variance, and, f, is a
scaling factor. If F(t) is not zero mean, the Shannon probability, P,
of an increase in the equity's price, (ie., the likelihood of an up
movement,) is:

        avg
        --- + 1
        rms
    P = -------
           2

where avg is the offset of the mean, and f = rms, ie., avg is the
average growth in the equity's value, (eg., the average of the
increments,) and rms the risk, (eg., the root mean square of the
increments.)

The Shannon probability, P, is simulated with an offset in the
summation of the uniform deviate random number generator to produce a
binomial distribution-ie., if the threshold, for increase, or
decrease, is 0.5, (where the uniform deviate values lie between 0 and
1,) then P = 0.5, and avg = 0. If the threshold is 0.49, then P =
0.51, (and rms always is equal to f,) and so on.

For more details on the equity price model used, see the manual page
for tsinvest(1) for a complete derivation.

The short term, (Markov,) Hurst exponent specifies persistence, from
one time increment to the next. If the Hurst exponent is 0.5, there is
no persistence, (ie., whether the next time increment will have an
increase, or decrease in value, is a 50%/50% proposition.)  If it is
larger than 0.5, then there is a propensity for what happened in the
last increment to happen in the next, (ie., a better than 50%/50%
proposition.) And, if the Hurst exponent is less than 0.5, there is a
propensity for the opposite of what happened in the last increment to
happen in the next.

For example, if the Hurst exponent is 0.6, then there is a 60% chance
of what happened in the last element of the time series, to happen in
the next.

The way this is implemented in tsinvestsim(1), is to find what value
the preceeding value must be multiplied by, such that when added to
the current value will increase the probability that the current value
will do the same thing as the preceeding value-ie., it is a recursive
algorithm:

    V(t)  = (G  + (Hvalue * V(t - 1)))

where G is normally, or Gaussian, distributed random variable of unit
variance, (approximated by a binomial distribution,) Hvalue is the
multiplying value, and V the value of an equity at time t, and t - 1,
(which will be corrected to unit variance.)

The value, Hvalue, is calculated by finding x, in the normal, or
Gaussian, distribution, F(x), where F(x) is the Hurst exponent.

For example, if it is desired that the current value of a stock's
value have a persistence of H, meaning that the value of F(x) = H,
find x, which the previous stock's value will be multiplied by, and
added to the current value. For example, if H = F(x) = 0.6, then x
would be about 0.25, meaning that the previous value would be
multiplied by 0.25, and added to the current value.

V, of course, does not have unit variance, and must be compensated by
a correction factor:

    V(t)  = (G  + (Hvalue * V(t - 1))) * correction

such that both the current and previous values of V have unit
variance, or working with distributions:

        2     2                      2              2
    V(t)  = (G  + (Hvalue * V(t - 1)) ) * correction

where V = G = 1:

     2     2              2               2
    1  = (1  + (Hvalue * 1 )) * correction

or:

                   2              2
    1 = (1 + Hvalue ) * correction

and, finally:

                                      2
    correction = sqrt (1 / (1 + Hvalue ))

Obviously, the maximum Hurst exponent, H, that can be accommodated is
one standard deviation, (ie., 0.84,) since the recursion will diverge
for values greater.

PROGRAM ARCHITECTURE

I) Data architecture:

    A) Each equity has a data structure, of type STOCK, that contains
    the statistical information on the fluctuations in the equity's
    value. The structure is maintained in a linked list, referenced by
    stock_list.

II) Program description:

    A) The function main serves to handle any command line arguments,
    dispatch to the function to read the input file, static int
    read_infile (), and generate the simulation output:

        1) For each element in the time series:

            a) "Spin" through the list of equities, of type STOCK,
            referenced by stock_list, generating the normal, or
            Gaussian, distributed increment for each equity, using
            sequential calls to a uniform deviate random number
            generator to generate a binomial distribution to
            approximate the normal distribution.

    B) The function static int read_infile (), reads the input file:

        1) The cumulative normal distribution table is created by
            the cumulative_normal () function.

        2) The input file is opened.

        3) For each record in the input file:

            a) Parse the record into fields using the function static
            int strtoken ().

            b) Create a data structure, of type STOCK, for the equity
            represented by the record, populating the elements with
            default values.

            c) Each variable specified in the record is verified for
            appropriate values, and included in the included in the
            data structure, of type STOCK.

            d) If a Hurst exponent is specified, the corresponding
            value is looked up in the cumulative_normal_table by
            static double findHvalue (), and the correction element
            set appropriately.

II) Notes and asides:

    A) The program flow follows the derivation, and many of the
    computational formulas were transcribed from the text. Although
    this may enhance clarity, it is probably not in the best interest
    of expeditious computation.

    B) The programming stylistics used were to encourage modifications
    to the program without an in depth understanding of
    programming. Specifically, if efficiency is an issue, using
    indirect referencing on doubles that are passed as arguments to
    functions, and implementing implicit addresses of arrays with
    pointers would be recommended.

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
$Id: tsinvestsim.c,v 1.7 2006/01/07 10:05:09 john Exp $
$Log: tsinvestsim.c,v $
Revision 1.7  2006/01/07 10:05:09  john
Initial revision


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <ctype.h> /* for toupper () */

#ifdef __STDC__

#include <float.h>

#else

#include <malloc.h>

#endif

#ifndef PI /* make sure PI is defined */

#define PI 3.14159265358979323846 /* pi to 20 decimal places */

#endif

static char rcsid[] = "$Id: tsinvestsim.c,v 1.7 2006/01/07 10:05:09 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Generate a time series for the tsinvest(1) program\n",
    "Usage: tsinvestsim [-n n] [-v] infile number\n",
    "    -n n, n = number of elements in the binomial distribution, (100)\n",
    "    -v, print the program's version information\n",
    "    infile, input file name\n",
    "    number, number of samples in the time series\n"
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
    "Error allocating memory\n",
    "Error in input file syntax\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1 /* inadaquate number arguments */
#define EOPEN 2 /* error opening file */
#define ECLOSE 3 /* error closing file */
#define EALLOC 4 /* error allocating memory */
#define ESYNTAX 5 /* error in input file syntax */

typedef struct stock_struct /* structure for each stock */
{
    struct stock_struct *next; /* reference to next element in stock list */
    char *name; /* reference to name of stock */
    int L; /* exponential leptokurtosis, 0 = no, 1 = yes */
    double currentvalue, /* stock's current value */
           f, /* fraction of currentvalue to be wagered */
           P, /* Shannon probability */
           Hvalue, /* the x value of the normal distribution that gives the Hurst exponent */
           correction, /* gamma correction to rms value for H */
           sum; /* running power law sum of increments */
} STOCK;

#define BUFLEN BUFSIZ /* i/o buffer size */

#define TOKEN_SEPARATORS " \t\n\r\b,~!@$%^&*()_+|`={}[]:;'<>,?/" /* file record field separators */

#define PUSH(x,y) (y)->next=(x);(x)=(y) /* method to push a STOCK element on the list of stocks, a list of STOCK structures */

#define POP(x) (x);(x)=(x)->next /* method to pop a STOCK element from the list of stocks, a list of STOCK structures */

#define SIGMAS 1 /* 1 sigma limit, ie., 0 to 1 sigma */

#define STEPS_PER_SIGMA 10000 /* each sigma has 10000 steps of granularity */

#define MIDWAY(a,b) (((a) + (b)) / 2) /* bisect a segment of the cumulative_normal_table lookup array */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static int read_infile (int argc, char *argv[]); /* read the input file */
static int strtoken (char *string, char *parse_array, char **parse, const char *delim); /* parse a record based on sequential delimiters */
static int cumulative_normal (void); /* construct/destroy the cumlative of a normal, or Gaussian, distribution lookup table */
static double findHvalue (double H); /* find the Hvalue */
static int tsgetopt (int argc, char *argv[], const char *opts); /* get an option letter from argument vector */

#else

static void print_message (); /* print any error messages */
static int read_infile (); /* read the input file */
static int strtoken (); /* parse a record based on sequential delimiters */
static int cumulative_normal (); /* construct/destroy the cumlative of a normal, or Gaussian, distribution lookup table */
static double findHvalue (double H); /* find the Hvalue */
static int tsgetopt (); /* get an option letter from argument vector */

#endif

static STOCK *stock_list = (STOCK *) 0; /* reference to list of stocks, a list of STOCK structures */

static double *cumulative_normal_table = (double *) 0; /* reference to cumulative normal, or Gaussian, lookup table */

static int sigma_limit = SIGMAS * STEPS_PER_SIGMA; /* the array size of the cumulative normal, or Gaussian, lookup table, ie., SIGMAS * STEPS_PER_SIGMA, for calculation expediency */

static double Hmax = (double) 1.0; /* maximum Hurst exponent */

static double Hmin = (double) 0.0; /* minimum Hurst exponent */

static char *optarg; /* reference to vector argument in tsgetopt () */

static int optind = 1; /* count of arguments in tsgetopt () */

#ifdef __STDC__

int main (int argc,char *argv[])

#else

int main (argc,argv)
int argc;
char *argv[];

#endif

{
    int retval = EARGS, /* return value, assume not enough arguments */
        number, /* number of records in the time series */
        n = 100, /* number of elements in the binomial distribution */
        i, /* counter */
        k, /* counter */
        c, /* command line switch */
        count, /* binomial distribution counter */
        threshold; /* threshold for stock movement, above this gain, below this, loss */

    double sum, /* cumulative sum of stock's value */
           sqrtn = sqrt ((double) n), /* square root of the number of elements in the binomial distribution, for math expediency */
           uniform, /* an element from the uniform distribution */
           sqrtofonehalf = sqrt ((double) 0.5), /* square root of 1 / 2 */
           laplace_val; /* variant from the Laplacian distribution */

    STOCK *stock; /* reference to STOCK structure */

    while ((c = tsgetopt (argc, argv, "hn:v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'n': /* request for number of elements in the binomial distribution? */

                n = atoi (optarg); /* yes, save the number of elements in the binomial distribution */
                sqrtn = sqrt ((double) n); /* save the square root of the number of elements in the binomial distribution, for math expediency */
                break;

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

    if (argc - optind == 2) /* enough arguments? */
    {

        if ((retval = read_infile (optind, argv)) == NOERROR)
        {
            number = atoi (argv[optind + 1]); /* number of records in time series */

            for (i = 0; i < number; i ++) /* for each record in the time series */
            {
                stock = stock_list; /* reference the first stock in the list of stocks */

                while (stock != (STOCK *) 0) /* for each stock */
                {
                    sum = stock->currentvalue; /* get the stock's current value */

                    if (stock->L == 0) /* exponential leptokurtosis for this stock, 0 = no, 1 = yes? */
                    {
                        threshold = (int) floor (((((stock->P - (double) 0.5) / sqrtn) + (double) 0.5) * (double) RAND_MAX) + (double) 0.5); /* calculate the threshold for stock movement, above this gain, below this, loss */
                        count = 0; /* reset the binomial distribution counter */

                        for (k = 0; k < n; k ++) /* for each element in the binomial distribution */
                        {

                            if (rand () <= threshold) /* random number less than probability? */
                            {
                                count ++; /* increment the count of elements in the binomial distribution that are greater than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p */
                            }

                            else
                            {
                                count --; /* decrement the count of elements in the binomial distribution that are greater than p, the Shannon probability, minus the number of elements in the binomial distribution that are less than p */
                            }

                        }

                        stock->sum = (((stock->Hvalue * stock->sum) + ((double) count / sqrtn)) * stock->correction); /* save the running power law sum of increments */
                    }

                    else
                    {
                        uniform = (double) rand () / (double) RAND_MAX; /* get an element from the uniform distribution on 0, 1 */

                        if (uniform < (double) 0.5) /* element < 0.5? */
                        {
                            laplace_val = sqrtofonehalf * log (((double) 2.0) * uniform); /* save the variant from the Laplacian distribution */
                        }

                        else /* element >= 0.5 */
                        {
                            laplace_val = -sqrtofonehalf * log (((double) 2.0) * (((double) 1.0 - uniform))); /* save the variant from the Laplacian distribution */
                        }

                        laplace_val = laplace_val + (((double) 2.0 * stock->P) - (double) 1.0); /* add the avg to the variant, (2P - 1) * rms = avg, rms = 1 */
                        stock->sum = (((stock->Hvalue * stock->sum) + (laplace_val)) * stock->correction); /* save the running power law sum of increments */
                    }

                    sum = sum + (sum * stock->f * stock->sum); /* the sum is the sum plus the sum times the wager fraction times the random variable */
                    stock->currentvalue = sum; /* save the stock's current value */
                    (void) printf ("%d\t%s\t%f\n", i, stock->name, sum); /* print the values for this stock in the time series */
                    stock = stock->next; /* next stock in the stock list */
                }

            }

            while (stock_list != (STOCK *) 0) /* for each element in the list of stocks */
            {
                stock = POP(stock_list); /* reference the stock strcuture */

                if (stock->name != (char *) 0) /* a stock name been allocated? */
                {
                    free (stock->name); /* yes, free the stock name */
                }

                free (stock); /* free the element in the list of stocks */
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

read the input file

static int read_infile (int argc, char *argv[]);

I) Read, and parse, the input file, constructing a data structure for
each data record, which corresponds to a stock, and filling the data
structure with the information from each record-it all of the
information is not supplied, use defaults for that information (the
Shannon probability is the only required information).

returns NOERROR if successful, an error if not

*/

#ifdef __STDC__

static int read_infile (int argc, char *argv[])

#else

static int read_infile (argc, argv)
int argc;
char *argv[];

#endif

{
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         *char_ptr; /* reference to a character */

    int retval, /* return value */
        fields, /* number of fields in a record */
        field, /* implicit index of a token in the token array */
        stock_counter = 0; /* number of stocks */

    double temp; /* temporary double storage */

    FILE *infile; /* reference to input file */

    STOCK *stock; /* reference to STOCK structure */

    if ((retval = cumulative_normal ()) == NOERROR) /* construct the cumlative of a cumulative normal, or Gaussian, distribution lookup table */
    {
        retval = EOPEN; /* assume error opening file */

        if ((infile = fopen (argv[argc], "r")) != (FILE *) 0) /* yes, open the input file */
        {
            retval = NOERROR; /* assume no error */

            while (fgets (buffer, BUFLEN, infile) != (char *) 0 && retval == NOERROR) /* read the records from the input file */
            {

                if ((fields = strtoken (buffer, parsebuffer, token, TOKEN_SEPARATORS)) != 0) /* parse the record into fields, skip the record if there are no fields */
                {

                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                    {
                        stock_counter ++; /* increment the number of stocks */
                        retval = EALLOC; /* assume error allocating memory */

                        if ((stock = (STOCK *) malloc (sizeof (STOCK))) != (STOCK *) 0) /* allocate the stock structure */
                        {
                            retval = NOERROR; /* assume no error */
                            stock->next = stock; /* reference to next element in stock list, initialize to reference itself */
                            PUSH(stock_list,stock); /* push the stock on the list of stocks */
                            stock->name = (char *) 0; /* reference to name of stock, initilize to null */
                            stock->L = 0; /* exponential leptokurtosis, 0 = no, 1 = yes */
                            stock->currentvalue = (double) 1.0, /* stock's current value, initilize to unity */
                            stock->f = (double) -1.0; /* fraction of currentvalue to be wagered, initialize to a negative number */
                            stock->P = (double) 0.5; /* Shannon probability, initilize to 1 / 2 */
                            stock->Hvalue = (double) 0.0; /* the x value of the cumulative normal distribution that gives the Hurst exponent */
                            stock->correction = (double) 1.0; /* correction to rms value for H */
                            stock->sum = (double) 0.0; /* running power law sum of increments */

                            for (field = 0; field < fields && retval == NOERROR; field ++) /* for each field in the record */
                            {
                                retval = ESYNTAX; /* assume error in input file syntax */
                                char_ptr = token[field]; /* reference the first character in the field */

                                while (*char_ptr != '\0') /* for each character in the field */
                                {
                                    *char_ptr = toupper (*char_ptr); /* capitalize the character */
                                    char_ptr ++; /* next character in the field */
                                }

                                if (strcmp (token[field], "P") == 0) /* field a Shannon probability? */
                                {

                                    if (field < fields) /* another field? */
                                    {
                                        field ++; /* yes, reference the next field */
                                        stock->P = atof (token[field]); /* get the Shannon probability */

                                        if (stock->P >= (double) 0.0 && stock->P <= (double) 1.0) /* sanity check on the Shannon probability? */
                                        {
                                            retval = NOERROR; /* OK, assume no error */
                                        }

                                    }

                                }

                                else if (strcmp (token[field], "H") == 0) /* field a Hurst exponent? */
                                {

                                    if (field < fields) /* another field? */
                                    {
                                        field ++; /* yes, reference the next field */
                                        temp = atof (token[field]); /* save the Hurst exponent */

                                        if (temp >= Hmin && temp <= Hmax) /* sanity check on the Hurst exponent? */
                                        {
                                            stock->Hvalue = findHvalue (temp); /* save the x value of the cumulative normal distribution that gives the Hurst exponent */
                                            stock->correction = sqrt ((double) 1.0 / ((double) 1.0 + (stock->Hvalue * stock->Hvalue))); /* save the correction to rms value for H */
                                            retval = NOERROR; /* OK, assume no error */
                                        }

                                    }

                                }

                                else if (strcmp (token[field], "F") == 0) /* field a fraction of currentvalue to be wagered */
                                {

                                    if (field < fields) /* another field? */
                                    {
                                        field ++; /* yes, reference the next field */
                                        stock->f = atof (token[field]); /* get the fraction of currentvalue to be wagered */

                                        if (stock->f >= (double) 0.0 && stock->f <= (double) 1.0) /* sanity check on the fraction of currentvalue to be wagered? */
                                        {
                                            retval = NOERROR; /* OK, assume no error */
                                        }

                                    }

                                }

                                else if (strcmp (token[field], "I") == 0) /* field a stock's current value */
                                {

                                    if (field < fields) /* another field? */
                                    {
                                        field ++; /* yes, reference the next field */
                                        stock->currentvalue = atof (token[field]); /* get the stock's current value */

                                        if (stock->currentvalue >= (double) 0.0) /* sanity check on the stock's current value? */
                                        {
                                            retval = NOERROR; /* OK, assume no error */
                                        }

                                    }

                                }

                                else if (strcmp (token[field], "L") == 0) /* field a stock's exponential leptokurtosis */
                                {

                                    if (field < fields) /* another field? */
                                    {
                                        field ++; /* yes, reference the next field */
                                        stock->L = atoi (token[field]); /* get the stock's exponential leptokurtosis */

                                        if (stock->L == 0 || stock->L == 1) /* sanity check on the stock's exponential leptokurtosis? */
                                        {
                                            retval = NOERROR; /* OK, assume no error */
                                        }

                                    }

                                }

                                else /* else, assume it to be the stock's name */
                                {
                                    retval = EALLOC; /* assume error allocating memory */

                                    if ((stock->name = (char *) malloc (strlen (token[field]) + 1)) != (char *) 0) /* allocate the stock's name */
                                    {
                                        (void) strcpy (stock->name, token[field]); /* get the name of the stock */
                                        retval = NOERROR; /* assume no error */
                                    }

                                }

                            }

                        }

                        if (retval == NOERROR) /* any errors? */
                        {

                            if (stock->name == (char *) 0) /* no, name of stock specified? */
                            {
                                (void) sprintf (buffer, "%d", stock_counter); /* no, through with the buffer, use it to construct the name of the stock, which was not specified, using the data record number */
                                retval = EALLOC; /* assume error allocating memory */

                                if ((stock->name = (char *) malloc (sizeof (strlen (buffer) + 1))) != (char *) 0) /* allocate the stock's name */
                                {
                                    (void) strcpy (stock->name, buffer); /* get the name of the stock */
                                    retval = NOERROR; /* assume no error */
                                }

                            }

                            if (retval == NOERROR) /* any errors? */
                            {

                                if (stock->f < (double) 0.0 ) /* fraction of currentvalue to be wagered specified? */
                                {
                                    stock->f = ((double) 2.0 * stock->P) - (double) 1.0; /* no, assume optimal at f = 2P - 1 */
                                }

                            }

                        }

                    }

                }

            }

            if (fclose (infile) == EOF) /* no, close the input file */
            {
                retval = ECLOSE; /* error closing file */
            }

        }

        (void) cumulative_normal (); /* destroy the cumlative of a cumulative normal, or Gaussian, distribution lookup table */
    }

    return (retval); /* return any errors */
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

/*

Construct/destroy the cumulative of a normal, or Gaussian,
distribution lookup table.

int cumulative_normal ();

I) Construct a cumulative normal, or Gaussian, distribution lookup
table by integrating the probability density of a standardized random
variable to get the cumulative sum, ie., by integrating:

                 1        -(x^2)/2
    f(x) = ------------- e
           sqrt (2 * PI)

II) The table is compatible with a binary search for reverse lookup of
values, eg., given F(x), find x-specifically, in this case, find x,
given H, the Hurst exponent. It is desired that the current value of a
stock's value have a persistence of H, meaning that the value of F(x)
= H, find x, which the previous stock's value will be multiplied by,
and added to the current value. For example, if H = F(x) = 0.6, then x
would be about 0.25, meaning that the previous value would be
multiplied by 0.25, and added to the current value.

III) Calling the function the first time allocates memory for the
table, and populates the table.

IV) Calling the function the second time de-allocates memory for the
table.

Returns NOERROR if successful, an error if not.

*/

#ifdef __STDC__

static int cumulative_normal ()

#else

static int cumulative_normal ()

#endif

{
    int retval = NOERROR, /* return value, assume no error */
        i; /* step counter through cumulative of normal distribution */

    double scale = (double) 1.0 / sqrt ((double) 2.0 * (double) PI), /* scaling factor for cumulative of normal distribution, for math expediency */
           del = (double) 1.0 / (double) STEPS_PER_SIGMA, /* increment of granularity of x axis of cumulative of normal distribution */
           x = (double) 0.0, /* x variable in cumulative of normal distribution */
           cumulativesum = (double) 0.5; /* cumulative of normal distribution, begins at half of the distribution, ie., x = 0 */

    if (cumulative_normal_table == (double *) 0) /* cumulative normal, or Gaussian, lookup table allocated? */
    {
        retval = EALLOC; /* assume error allocating memory */

        if ((cumulative_normal_table = malloc ((size_t) sigma_limit * sizeof (double))) != (double *) 0) /*no, allocate the cumulative normal, or Gaussian, lookup table */
        {
            retval = NOERROR; /* assume no error */

            for (i = 0; i < sigma_limit; i ++) /* for each step in the cumulative of the normal distribution */
            {
                cumulativesum = cumulativesum + ((scale * (exp ((- (x * x)) / ((double) 2.0)))) / (double) STEPS_PER_SIGMA); /* add the value of the normal distribution for this x to the cumulative of the normal distribution */
                cumulative_normal_table[i] = cumulativesum;  /* save the cumulative of normal distribution, begins at half the distribution, ie., x = 0, element value */
                x = x + del; /* next increment of x in the cumulative of the normal distribution */
            }

            Hmax = cumulative_normal_table[sigma_limit - 1]; /* save the maximum Hurst exponent */
            Hmin = (double) 1.0 - Hmax; /* save the minimum Hurst exponent */
        }

    }

    else
    {
        free (cumulative_normal_table); /* yes, free the cumulative normal, or Gaussian, lookup table */
    }

    return (retval); /* return any errors */
}

/*

Find the Hvalue.

static double findHvalue (double H);

1) Given F(x), find x-specifically, in this case, find x, given H, the
Hurst exponent, using a binary search. It is desired that the current
value of a stock's value have a persistence of H, meaning that the
value of F(x) = H, find x, which the previous stock's value will be
multiplied by, and added to the current value. For example, if H =
F(x) = 0.6, then x would be about 0.25, meaning that the previous
value would be multiplied by 0.25, and added to the current value.

Returns x for the given F(x).

*/

#ifdef __STDC__

static double findHvalue (double H)

#else

static double findHvalue (H)
double H;

#endif

{
    int bottom = 0, /* bottom index of segment of cumulative_normal_table array, initialize to bottom index of cumulative_normal_table array */
        middle = 0, /* middle index of segment of cumulative_normal_table array */
        top = sigma_limit - 1, /* top index of segment of the cumulative_normal_table array, (cumulative_normal_table array of size n elements is indexed 0 to n - 1,) initialize to top index of the cumulative_normal_table array */
        antipersistence = 0; /* anti-persistence flag, 0 = no, 1 = yes */

    double decision; /* decision variable on whether to move up, or down, the cumulative_normal_table array, ie., the stearing variable */

    if (H < (double) 0.5) /* H specify anti-persistence? */
    {
        H = (double) 1.0 - H; /* yes, lookup the positive value, then change the sign */
        antipersistence = 1; /* set the anti-persistence flag, 0 = no, 1 = yes */
    }

    while (top > bottom) /* while the top index of the segment of the cumulative_normal_table array is greater than the bottom index of the segment of the cumulative_normal_table array, if the top is ever equal to the bottom, the search is finished */
    {
        middle = MIDWAY(bottom, top); /* starting in the middle of the segment of the cumulative_normal_table array */

        decision = H - cumulative_normal_table[middle]; /* calcluate the decision variable on whether to move up, or down, the cumulative_normal_table array, ie., the stearing variable */

        if (decision < 0) /* if the decision variable is negative, move down the cumulative_normal_table array */
        {
            top = middle - 1; /* the next segement of the cumulative_normal_table array will begin just below the current middle of the current segment of the cumulative_normal_table array */
        }

        else /* if the decision variable is positive, (or zero,) move up the cumulative_normal_table array */
        {
            bottom = middle + 1; /* the next segement of the cumulative_normal_table array will end just below the current middle of the current segment of the cumulative_normal_table array */
        }

    }

    return ((antipersistence == 0) ? (double) middle / (double) STEPS_PER_SIGMA : (double) -middle / (double) STEPS_PER_SIGMA); /* return the index H */
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
