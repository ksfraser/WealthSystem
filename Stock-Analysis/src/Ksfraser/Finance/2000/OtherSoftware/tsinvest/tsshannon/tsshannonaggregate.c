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

tsshannonaggregate.c, aggregate Shannon probability of many concurrent
Shannon probabilities.

Consider gambling on two unfair coin tossing games, at the same time,
one game having a Shannon probability of 0.55, and the other having a
Shannon probability of 0.65. Assuming that the coins in both games are
tossed concurrently for each iteration of the game, the combinatorics
of the possible outcomes of wins and losses in each iteration are:

    outcomes probability fraction    average

    ll:      0.157500  * -0.400000 = -0.063000
    wl:      0.192500  * -0.200000 = -0.038500
    lw:      0.292500  *  0.200000 =  0.058500
    ww:      0.357500  *  0.400000 =  0.143000

where 'l' is a loss, and 'w' is a win, and the probability is
calculated by multiplying the individual probabilities of a loss or
win for the respective coins, ie., for both coins to win, the
probability would be 0.55 * 0.65 = 0.3575.  (1 - P is used for the
probability of a loss for each coin.) The fraction is the fraction of
capital waged on an individual game, and is computed as optimal, from
the equation 2P - 1, where P is the Shannon probability of the
individual unfair coin and is either 0.55 or 0.65. The average is
computed as the product of the probability and the fraction.

What this means is that 35.75% of the time, a win-win outcome will be
observed in the iterated games, and 15.75% of the time, a lose-lose
outcome will be observed. The amount won in the win-win scenario will
be the sum of the fractions wagered on each coin, which is (2 * 0.55 -
1) + (2 * 0.65 - 1) = 0.1 + 0.3 = 0.4. The product of this fraction
and probability is the contribution over many plays to the capital do
to this outcome. Summing these averages for the different outcomes is
the average over many plays of the capital growth by playing both
games, and is numerically identical to the sum of the average of the
normalized increments of both games.

Since the average and root mean square of the normalized increments
are related by:

    rms = sqrt (average)

squaring the average will be the root mean square of the normalized
increments, or:

    Average  rms      Shannon probability

    0.100000 0.316228 0.658114

where the Shannon probability, P, is computed by:

        rms + 1   1.316228
    P = ------- = -------- = 0.658114
           2         2

The implication is that the two concurrent unfair coin tossing games
could be "modeled" as a single game with a Shannon probability of
0.658114.

Although it is generally more expedient just to sum, root mean square,
the individual root mean square of the normalized increments of each
game, (where f = rms = 2P - 1,) and then compute the Shannon
probability by:

                              2                   2
        sqrt (((2 * 0.55) - 1)  + ((2 * 0.65) - 1))  + 1
    P = ------------------------------------------------
                              2

                 2      2
        sqrt (0.1  + 0.3 )  + 1     sqrt (0.01 + 0.09) + 1
      = -----------------------   = ----------------------
                   2                           2

        sqrt (0.1) + 1   0.316227766 + 1   1.316227766
      = -------------- = --------------- = -----------
              2                 2               2

      = 0.658113883

this program does it with combinatorics.

$Revision: 0.0 $
$Date: 2006/01/18 20:28:55 $
$Id: tsshannonaggregate.c,v 0.0 2006/01/18 20:28:55 john Exp $
$Log: tsshannonaggregate.c,v $
Revision 0.0  2006/01/18 20:28:55  john
Initial version

*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <unistd.h>

#define BUFLEN BUFSIZ /* array size-the maximum number of command line arguments */

static char rcsid[] = "$Id: tsshannonaggregate.c,v 0.0 2006/01/18 20:28:55 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{
    "\n",
    "Calculate the aggregate Shannon probability\n",
    "Usage: tsshannonaggregate [-p] [-v] Probability_1 Probability_2 ...\n",
    "    Probability_1, first Shannon probability\n",
    "    Probability_2, second Shannon probability\n",
    "    -p, verbose print\n",
    "    -v, print the program's version information\n"
};

#ifdef __STDC__

static const char *error_message[] = /* error message index array */

#else

static char *error_message[] = /* error message index array */

#endif

{
    "No error\n",
    "Error in program argument(s)\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static int binary_increment (char *binary_table);

#else

static void print_message (); /* print any error messages */
static binary_increment ();

#endif

#ifdef __STDC__

int main (int argc,char *argv[])

#else

int main (argc,argv)
int argc;
char *argv[];

#endif

{
    char winlose[BUFLEN]; /* win/lose table to hold last binary value of each Shannon probability, 'w' = win, 'l' = lose */

    int retval = EARGS, /* return value, assume not enough arguments */
        i, /* command line argument Shannon probability counter */
        j, /* array element counter */
        n, /* number of elements in the win/lose table */
        p = 0, /* verbose print flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double shannon_probability[BUFLEN], /* Shannon probabilities from command line */
           probability, /* probability of combinatoric from win/lose table */
           sumprobability = (double) 0.0, /* sum of probability of combinatoric from win/lose table */
           win; /* fraction of win/loss for table elements */

    while ((c = getopt (argc, argv, "pv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'p': /* request for verbose print? */

                p = 1; /* yes, set the verbose print flag */
                break;

            case 'v':

                (void) printf ("%s\n", rcsid); /* print the version */
                (void) printf ("%s\n", copyright); /* print the copyright */
                optind = argc; /* force argument error */

            default: /* illegal switch? */

                optind = argc; /* force argument error */
                break;

        }

    }

    if (argc - optind > 0) /* enough arguments? */
    {
        retval = NOERROR; /* assume no error */

        j = 0; /* start with first element in arrays */

        for (i = optind; i < argc; i ++) /* for each Shannon probability on the command line */
        {
            winlose[j] = 'l'; /* initialize the win/lose table element to 'l' = lose */
            shannon_probability[j] = atof (argv[i]); /* Shannon probability from command line */
            j ++; /* next element in arrays */
            winlose[j] = '\0'; /* terminate the last element in the win/lose table */
        }

        n = j; /* save the number of elements in the win/lose table */

        do /* for all possible combinations of wins and losses */
        {
            j = 0; /* start with first element in win/lose array */
            win = (double) 0.0; /* reset the fraction of win/loss for table elements */
            probability = (double) 1.0; /* initialize probability of combinatoric from win/lose table */

            while (winlose[j] != '\0') /* for each element in the win/lose table */
            {

                if (winlose[j] == 'w') /* a win? */
                {
                    win = win + (((double) 2.0 * shannon_probability[j]) - (double) 1.0); /* add the amount wagered for this game */
                    probability = probability * shannon_probability[j]; /* multiply the probability of combinatoric by the Shannon probability from command line */
                }

                else
                {
                    win = win - (((double) 2.0 * shannon_probability[j]) - (double) 1.0); /* subtract the amount wagered for this game */
                    probability = probability * ((double) 1.0 - shannon_probability[j]); /* multiply the probability of combinatoric by 1 - the Shannon probability from command line */
                }

                j ++; /* next element in the win/lose table */
            }

            sumprobability = sumprobability + (probability * win); /* sum of probability of combinatoric from win/lose table */

            if (p == 1) /* verbose print flag set? */
            {
                (void) printf ("%s: probability of %f * fraction of %f = average of %f\n", winlose, probability, win, probability * win); /* print the win/loss table element */
            }

        }
        while (binary_increment (winlose));

        (void) printf ("\nAverage = %f, rms = %f, Shannon Probability = %f\n", sumprobability, sqrt (sumprobability), (sqrt (sumprobability) + (double) 1.0) / (double) 2); /* print the aggregate */
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

static int binary_increment (char *binary_table);

Increment a binary table. The table is a character string of 'w', for
win, and 'l' for lose charcters. The table should be initialized to
all 'l' and will list all possible combinations of 'w' and 'l' with
repeated calls.

Returns 1 if the increment was successful, 0 if overflow.

*/

#ifdef __STDC__

static int binary_increment (char *binary_table)

#else

static int binary_increment (binary_table)
char *binary_table;

#endif

{
    int carry = 1, /* carry from one element to the next */
        i = 0; /* table element counter */

    while (carry == 1)
    {

        if (binary_table[i] == 'l')
        {
            binary_table[i] = 'w';
            carry = 0;
            break;
        }

        else if (binary_table[i] == 'w')
        {
            binary_table[i] = 'l';
        }

        else
        {
            break;
        }

        i ++; /* next table element */
    }

    return (! carry);
}
