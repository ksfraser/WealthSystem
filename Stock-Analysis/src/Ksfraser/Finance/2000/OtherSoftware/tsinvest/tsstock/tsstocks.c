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

tsstocks.c is for simulating the optimal gains of multiple stock
investments. The program decides which of all available stocks to
invest in at any single time, by calculating the instantaneous Shannon
probability of all stocks, and using an approximation to statistical
estimation techniques to estimate the accuracy of the calculated
Shannon probability.

One of the implications of considering stock prices to have fractal
characteristics, ie., random walk or Brownian motion, is that future
prices can not be predicted from past stock price performance. The
Shannon probability of a stock price time series is the likelihood
that a stocks price will increase in the next time interval. It is
typically 0.51, on a day to day bases, (although, occasionally, it
will be as high as 0.6) What this means, for a typical stock, is that
51% of the time, a stock's price will increase, and 49% of the time it
will decrease-and there is no possibility of determining which will
occur-only the probability.

However, another implication of considering stock prices to have
fractal characteristics is that there are statistical optimizations to
maximize portfolio performance. The Shannon probability, P, is related
to the volatility of a stock's price, (measured as the root mean
square of the normalized increments of the stock's price time series,)
rms, by rms = 2P - 1. Also, the average of the normalized increments
is the growth in the stock's price, and is equal to the square of the
rms. Unfortunately, the measurements of avg and rms must be made over
a long period of time, to construct a very large data set for
analytical purposes do to the necessary accuracy
requirements. Statistical estimation techniques are usually employed
to quantitatively determine the size of the data set for a given
analytical accuracy.

There are several techniques used to optimize stock portfolio
performance. Since the volatility of an individual stock price, rms,
is considered to have a Gaussian distribution, the volatilities add
root mean square. What this means is that if the portfolio consists of
10 stocks, concurrently, with each stock representing 10% of the
portfolio, then the volatility of the portfolio will be decreased by a
factor of the square root of 10, (assuming all stocks are
statistically identical.)  Further, since it is assumed that the
stocks are statistically identical, the average growth of the stocks
adds linearly in the portfolio, ie., it would not make any difference,
from a portfolio growth standpoint, whether the portfolio consisted of
1 stock, or 10 stocks.  This indicates that control of stock portfolio
volatility can be an "engineered solution." (In reality, of course,
the stocks are not statistically identical, but the volatilities still
add root mean square. The growth of the portfolio would be less, since
it was not totally invested in the stock with the highest growth
rate-this would be the cost of managing the volatility risk.)

Now consider "timing the market." If a stock's price has fractal
characteristics, this is impossible, (at least more than 51% of the
time, on average, for most stocks.) Attempting to do so, say by
selling a stock for the speculative reason that the stocks price will
decrease in the future, will result in selling a stock that 51% of the
time would increase in value in the future, and 49% of the time would
decrease in value. Of course, holding a stock would have the same
probabilities, also.

If a stock's price is fractal, it will, over time, exhibit price
increases, and decreases, that have a range that is proportional to
the square root of time, and a probable duration that is proportional
to the reciprocal of the square root of time. In point of fact,
measurements on these characteristics in stock pro forma for the past
century offer compelling evidence that stock prices exhibit fractal
characteristics.  These increases and decreases in stock price over
time would lead to the intuitive presumption that a "buy low and sell
high" strategy could be implemented. Unfortunately, if stock prices
are indeed fractal in nature, that is not the case, because no matter
what time scale you use, the characteristics are invariant, (ie., on a
time scale-be it by the tick, by the day, by the month, or by the
year-the range and duration phenomena is still the same, ie., made up
of "long term" increases and decreases, that have no predictive
qualities, other than probabilistic.)

The issue with attempting to "time the market" is that if you sell a
stock to avoid an intuitively expected price decrease, (which will be
correct, 49% of the time, typically,) then you will, also, give up the
chance of the stock price increasing, (which will happen 51% of the
time.) However, there is an alternative, and that would be to sell the
stock, and invest in another stock, (which would also have a 51%
chance of increasing in price, on the average-a kind of "hedging"
strategy.)

To implement such a strategy, one would never sell a stock for a stock
with a smaller Shannon probability, without compelling reasons. In
point of fact, it would probably be, at least heuristically, the best
strategy to always be invested in the stocks with the most recent
largest Shannon probability, the assumption being that during the
periods when a stock's price is increasing, the short term
"instantaneous" average Shannon probability will be larger than the
long term average Shannon probability. (Not that this will always be
true-only 51% of the time, for an average stock, will it succeed in
the next time interval.) This will require specialized filtering, (to
"weight" the most recent instantaneous Shannon probability more than
the least recent,) and statistical estimation (to determine the
accuracy of the measurement of the Shannon probability, upon which the
decision will be made as to which stocks are in the portfolio at any
instant in time.)

This decision would be based on the normalized increments,

    V(t) - V(t - 1)
    ---------------
       V(t - 1)

of the time series, which, when averaged over a "sufficiently large"
number of increments, is the mean of the normalized increments,
avg. The term "sufficiently large" must be analyzed
quantitatively. For example, the following table is the statistical
estimate for a Shannon probability, P, of a time series, vs, the
number of records required, based on a mean of the normalized
increments = 0.04:

     P      avg         e       c     n
    0.51   0.0004    0.0396  0.7000  27
    0.52   0.0016    0.0384  0.7333  33
    0.53   0.0036    0.0364  0.7667  42
    0.54   0.0064    0.0336  0.8000  57
    0.55   0.0100    0.0300  0.8333  84
    0.56   0.0144    0.0256  0.8667  135
    0.57   0.0196    0.0204  0.9000  255
    0.58   0.0256    0.0144  0.9333  635
    0.59   0.0324    0.0076  0.9667  3067
    0.60   0.0400    0.0000  1.0000  infinity

where avg is the average of the normalized increments, e is the error
estimate in avg, c is the confidence level of the error estimate, and
n is the number of records required for that confidence level in that
error estimate.  What this table means is that if a step function,
from zero to 0.04, (corresponding to a Shannon probability of 0.6,) is
applied to the system, then after 27 records, we would be 70%
confident that the error level was not greater than 0.0396, or avg was
not lower than 0.0004, which corresponds to an effective Shannon
probability of 0.51. Note that if many iterations of this example of
27 records were performed, then 30% of the time, the average of the
time series, avg, would be less than 0.0004, and 70% greater than
0.0004. This means that the the Shannon probability, 0.6, would have
to be reduced by a factor of 0.85 to accommodate the error created by
an insufficient data set size to get the effective Shannon probability
of 0.51. Since half the time the error would be greater than 0.0004,
and half less, the confidence level would be 1 - ((1 - 0.85) * 2) =
0.7, meaning that if we measured a Shannon probability of 0.6 on only
27 records, we would have to use an effective Shannon probability of
0.51, corresponding to an avg of 0.0004. For 33 records, we would use
an avg of 0.0016, corresponding to a Shannon probability of 0.52, and
so on.

The table above was made by iterating the tsstatest(1) program, and
can be approximated by a single pole low pass recursive discreet time
filter[1], with the pole frequency at 0.00045 times the time series
sampling frequency. The accuracy of the approximation is about +/- 10%
for the first 260 samples, with the approximation accuracy prediction
becoming optimistic thereafter, ie., about +30%.

A pole frequency of 0.033 seems a good approximation for working with
the root mean square of the normalized increments, with a reasonable
approximation to about 5-10 time units.

The "instantaneous," weighted, and statistically estimated Shannon
probability, P, can be determined by dividing the filtered rms by the
filtered avg, adding unity, and dividing by two.

(Note: there is some possibility of operating on the absolute value of
the normalized increments, which is a close approximation to the root
mean square of the normalized increments. Another possibility is to
use trading volumes to calculate the instantaneous value for the
average and root mean square of the increments as in the
tsshannonvolume(1) program.  Also, another reasonable statistical
estimate approximation is Pest = 0.5 + (1 - 1 / sqrt(n)) * ((2 *
Pmeas) - 1) * 0.5, where Pmeas is the measured Shannon probability
over n many records, and Pest is the Shannon probability that should
be used do to the uncertainty created by an inadequate data set size.)

The advantage of the discreet time recursive single pole filter
approximation is that it requires only 3 lines of code in the
implementation-two for initialization, and one in the calculation
construct.

The single pole low pass filter is implemented from the following
discrete time equation:

    v      = I * k2 + v  * k1
     n + 1             n

where I is the value of the current sample in the time series, v are
the value of the output time series, and k1 and k2 are constants
determined from the following equations:

          -2 * p * pi
    k1 = e

and

    k2 = 1 - k1

where p is a constant that determines the frequency of the pole-a
value of unity places the pole at the sample frequency of the time
series.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

[1] This program is based on "An Analog, Discrete Time, Single Pole
Filter," John Conover, Fairchild Journal of Semiconductor Progress,
July/August, 1978, Volume 6, Number 4, pp. 11.

$Revision: 0.0 $
$Date: 2006/01/18 19:36:00 $
$Id: tsstocks.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsstocks.c,v $
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

static char rcsid[] = "$Id: tsstocks.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Simulate the optimal gains of multiple stock investments\n",
    "Usage: tsstocks [-f] [-p n] [-P m] [-t] [-v] filename ...\n",
    "    -f, alternate output format\n",
    "    -p n, average pole frequency\n",
    "    -P m, root mean square pole frequency\n",
    "    -t, sample's time will be included in the output time series\n",
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

Note there are elements in struct STOCK that are not utilized by this
program, for example, currentvalue-these are useful for analytical
purposes and quick "hacks," so I left them in ...

*/

typedef struct stock_struct /* structure for each stock/file */
{
    char *filename; /* reference to stock's file name */
    double currentvalue, /* value of current sample in time series */
           lastvalue, /* last value of output time series */
           fraction, /* fractional increment */
           avgfilter, /* filtered value of average of the fractional increment */
           rmsfilter; /* filtered value of root mean square of the fractional increment */
    FILE *infile; /* reference to input file */
} STOCK;

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
         stocks, /* number of stocks/files */
         token_separators[] = TOKEN_SEPARATORS;

    int count = 0, /* input file record counter */
        f = 0, /* alternate output format */
        retval = NOERROR, /* return value, assume no error */
        fields = 0, /* number of fields in a record */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        file_counter, /* file/stock counter */
        j, /* counter */
        anyeof = 0, /* eof in any file flag, 0 = no, 1 = yes */
        maxstock = 0, /* the implicit index of element in stock[] of the stock that has the maximum average of all stock's filtered value of the average of the fractional increment in a time unit */
        lastmaxstock = 0, /* the implicit index of element in stock[] of the last stock that had the maximum average of all stock's filtered value of the average of the fractional increment in a time unit */
        c; /* command line switch */

    double pa = (double) 0.00045, /* avg pole frequency */
           pb = (double) 0.033, /* rms pole frequency */
           k1, /* coefficient k1 in the avg recursive filter */
           k2, /* coefficient k2 in the avg recursive filter */
           k3, /* coefficient k1 in the rms recursive filter */
           k4, /* coefficient k2 in the rms recursive filter */
           P, /* Shannon probability */
           maxP, /* the maximum Shannon probability of all stock's */
           capital = (double) 0.0; /* the value of the capital */

    STOCK *stock = (STOCK *) 0; /* reference to array of stock structures, one per file/stock */

    while ((c = getopt (argc, argv, "fp:P:tv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'p': /* request for avg pole frequency? */

                pa = atof (optarg); /* yes, save the avg pole frequency */
                break;

            case 'P': /* request for rms pole frequency? */

                pb = atof (optarg); /* yes, save the rms pole frequency */
                break;

            case 'f': /* request for alternate output format? */

                f = 1; /* yes, set the alternate output format flag */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
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

    k1 = exp (- ((double) 2.0 * (double) PI * pa)); /* calculate the coefficient k1 in the avg recursive filter */
    k2 = (double) 1.0 - k1; /* calculate the coefficient k2 in the avg recursive filter */
    k3 = exp (- ((double) 2.0 * (double) PI * pb)); /* calculate the coefficient k1 in the rms recursive filter */
    k4 = (double) 1.0 - k3; /* calculate the coefficient k2 in the rms recursive filter */

    if (retval == NOERROR)  /* any errors? */
    {
        retval = EARGS; /* assume not enough arguments */

        if (argc > optind) /* enough arguments? */
        {
            stocks = argc - optind; /* save the number of stocks/files */
            retval = EALLOC; /* assume error allocating memory */

            if ((stock = (STOCK *) malloc (stocks * sizeof (STOCK))) != (STOCK *) 0)
            {
                retval = NOERROR; /* assume no error */
                file_counter = 0; /* implicit index into array of stock structures, one per file/stock */
                j = optind; /* filename counter */

                while (j < argc) /* for each filename */
                {
                    stock[file_counter].filename = argv[j]; /* save this stocks file name */
                    stock[file_counter].currentvalue = (double) 0.0; /* initialize the value of current sample in time series */
                    stock[file_counter].lastvalue = (double) 0.0; /* initialize the last value of output time series */
                    stock[file_counter].fraction = (double) 0.0; /* initialize the fractional increment */
                    stock[file_counter].avgfilter = (double) 0.0; /* initialize the filtered value of average of the fractional increment */
                    stock[file_counter].rmsfilter = (double) 0.0; /* initialize the filtered value of root mean square of the fractional increment */

                    if ((stock[file_counter].infile = fopen (argv[j], "r")) == (FILE *) 0) /* yes, open the stock's input file */
                    {
                        retval = EOPEN; /* assume error opening file */
                        break;
                    }

                    file_counter ++; /* next file, and, next array element of stock structures, one per file/stock */
                    j ++; /* next filename */
                }

                if (retval == NOERROR)  /* any errors? */
                {

                    while (!anyeof) /* while no files are at eof */
                    {
                        maxP = (double) 0.0; /* the maximum Shannon probability of all stocks at this time, Probability must be greater than zero */

                        for (j = 0; j < file_counter && !anyeof; j ++) /* for each file/stock, while no file is at eof */
                        {
                            anyeof = 1; /* assume eof in any file */

                            while (fgets (buffer, BUFLEN, stock[j].infile) != (char *) 0) /* read the stock's next record from the stock's input file */
                            {

                                if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the stock's record into fields, skip the record if there are no fields */
                                {

                                    if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                                    {
                                        anyeof = 0; /* assume eof not in this file */
                                        stock[j].currentvalue = atof (token[fields - 1]); /* save the stock's value of the current sample in the time series */

                                        if (count != 0) /* not first record? */
                                        {
                                            stock[j].fraction = (stock[j].currentvalue - stock[j].lastvalue) / stock[j].lastvalue; /* save the stock's fractional increment */
                                            stock[j].avgfilter = stock[j].fraction * k2 + stock[j].avgfilter * k1; /* compute the stock's filtered value of the average of the fractional increment */
                                            stock[j].rmsfilter = stock[j].fraction * stock[j].fraction * k4 + stock[j].rmsfilter * k3; /* compute the stock's filtered value of the root mean square of the fractional increment */

                                            if (stock[j].rmsfilter == (double) 0.0) /* a root mean square of the increments that is zero means the Shannon probability is 0.5 */
                                            {
                                                P = (double) 0.5; /* assume a Shannon probability of 0.5 */
                                            }

                                            else
                                            {
                                                P = ((stock[j].avgfilter / sqrt (stock[j].rmsfilter)) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
                                            }

                                            if (maxP < P) /* this stock/file have the maximum Shannon probability, so far? */
                                            {
                                                maxP = P; /* save the maximum Shannon probability of all stocks */
                                                maxstock = j; /* save the implicit index of element in stock[] of the stock that has the maximum average of all stock's filtered value of average of the fractional increment in a time unit */
                                            }

                                        }

                                        stock[j].lastvalue = stock[j].currentvalue; /* save the stock's current value of the sample in the time series as the last value */
                                        break; /* next stock */
                                    }

                                }

                            }

                        }

                        if (anyeof == 0 && count > 0) /* no files at eof? */
                        {

                            if (count == 1) /* second record? */
                            {
                                capital = stock[maxstock].lastvalue; /* initialize the beginning capital */
                                lastmaxstock = maxstock; /* save the implicit index of element in stock[] of the last stock that had the maximum average of all stock's filtered value of the average of the fractional increment in a time unit */
                            }

                            capital = capital * ((double) 1.0 + stock[lastmaxstock].fraction); /* yes, calculate the current value of the capital */

                            if (t == 1) /* print time of samples? */
                            {

                                if (fields > 1) /* yes, more that one field? */
                                {
                                    (void) printf ("%s\t", token[0]); /* yes, print the sample's time */
                                }

                                else
                                {
                                    (void) printf ("%d\t", count); /* no, print the sample's time which is assumed to be the record count */
                                }

                            }

                            if (f == 1) /* alternate output format flag set? */
                            {

                                for (j = 0; j < stocks; j ++) /* for each stock/file */
                                {

                                    if (j == maxstock) /* if this stock/file is the one just picked */
                                    {
                                        (void) printf ("%f", capital); /* print the capital invested in it */
                                    }

                                    else
                                    {
                                        (void) printf ("%f", (double) 0.0); /* there was no capital invested in it */
                                    }

                                    if (j < stocks - 1) /* another stock/file follows this one? */
                                    {
                                        (void) printf ("\t"); /* yes, separator is a tab character */
                                    }

                                }

                                (void) printf ("\n"); /* terminate the record */
                            }

                            else
                            {
                                (void) printf ("%f\t%s->%s\n", capital, stock[lastmaxstock].filename, stock[maxstock].filename); /* print the capital and names of stock */
                            }

                            lastmaxstock = maxstock; /* save the implicit index of element in stock[] of the last stock that had the maximum average of all stock's filtered value of the average of the fractional increment in a time unit */
                        }

                        count ++; /* increment the count of records from the input file(s) */
                    }

                }

                for (j = 0; j < file_counter; j ++) /* for each open file/stock */
                {

                    if (fclose (stock[j].infile) == EOF) /* no, close the stock's input file */
                    {
                        retval = ECLOSE; /* error closing file/stock */
                    }

                }

            }

            if (stock != (STOCK *) 0) /* array of stock structures, one per file/stock allocated? */
            {
                free (stock); /* yes, free the array of stock structures, one per file/stock */
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
