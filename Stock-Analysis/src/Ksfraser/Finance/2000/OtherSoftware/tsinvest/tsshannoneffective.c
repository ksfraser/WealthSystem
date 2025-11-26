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

tsshannoneffective.c for calculating the effective Shannon
probability, given the average, root mean square, data set size, and
data set duration, of the normalized increments of a time series.

DATA SET SIZE CONSIDERATIONS

This program addresses the question "is there reasonable evidence to
justify investment in an equity based on data set size?"

The Shannon probability of a time series is the likelihood that the
value of the time series will increase in the next time interval. The
Shannon probability is measured using the average, avg, and root mean
square, rms, of the normalized increments of the time series. Using
the rms to compute the Shannon probability, P:

        rms + 1
    P = ------- ....................................(1.1)
           2

However, there is an error associated with the measurement of rms do
to the size of the data set, N, (ie., the number of records in the
time series,) used in the calculation of rms. The confidence level, c,
is the likelihood that this error is less than some error level, e.

Over the many time intervals represented in the time series, the error
will be greater than the error level, e, (1 - c) * 100 percent of the
time-requiring that the Shannon probability, P, be reduced by a factor
of c to accommodate the measurement error:

         rms - e + 1
    Pc = ----------- ...............................(1.2)
              2

where the error level, e, and the confidence level, c, are calculated
using statistical estimates, and the product P times c is the
effective Shannon probability that should be used in the calculation
of optimal wagering strategies.

The error, e, expressed in terms of the standard deviation of the
measurement error do to an insufficient data set size, esigma, is:

              e
    esigma = --- sqrt (2N) .........................(1.3)
             rms

where N is the data set size = number of records. From this, the
confidence level can be calculated from the cumulative sum, (ie.,
integration) of the normal distribution, ie.:

    c     esigma
    -------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

Note that the equation:

         rms - e + 1
    Pc = ----------- ...............................(1.4)
              2

will require an iterated solution since the cumulative normal
distribution is transcendental. For convenience, let F(esigma) be the
function that given esigma, returns c, (ie., performs the table
operation, above,) then:

                    rms - e + 1
    P * F(esigma) = -----------
                         2

                          rms * esigma
                    rms - ------------ + 1
                           sqrt (2N)
                  = ---------------------- .........(1.5)
                              2

Then:

                                rms * esigma
                          rms - ------------ + 1
    rms + 1                      sqrt (2N)
    ------- * F(esigma) = ---------------------- ...(1.6)
       2                            2

or:

                                  rms * esigma
    (rms + 1) * F(esigma) = rms - ------------ + 1 .(1.7)
                                   sqrt (2N)

Letting a decision variable, decision, be the iteration error created
by this equation not being balanced:

                     rms * esigma
    decision = rms - ------------ + 1
                       sqrt (2N)

                - (rms + 1) * F(esigma) ............(1.8)

which can be iterated to find F(esigma), which is the confidence
level, c.

Note that from the equation:

         rms - e + 1
    Pc = -----------
              2

and solving for rms - e, the effective value of rms compensated for
accuracy of measurement by statistical estimation:

    rms - e = (2 * P * c) - 1 ......................(1.9)

and substituting into the equation:

        rms + 1
    P = -------
           2

    rms - e = ((rms + 1) * c) - 1 .................(1.10)

and defining the effective value of rms as rmseff:

    rmseff = rms - e ..............................(1.11)

It can be seen that if optimality exists, ie., f = 2P - 1,
or:

             2
    avg = rms  ....................................(1.12)

or:
                   2
    avgeff = rmseff  ..............................(1.13)

As an example of this algorithm, if the Shannon probability, P, is
0.51, corresponding to an rms of 0.02, then the confidence level, c,
would be 0.996298, or the error level, e, would be 0.003776, for a
data set size, N, of 100.

Likewise, if P is 0.6, corresponding to an rms of 0.2 then the
confidence level, c, would be 0.941584, or the error level, e, would
be 0.070100, for a data set size of 10.

Robustness is an issue in algorithms that, potentially, operate real
time. The traditional means of implementation of statistical estimates
is to use an integration process inside of a loop that calculates the
cumulative of the normal distribution, controlled by, perhaps, a
Newton Method approximation using the derivative of cumulative of the
normal distribution, ie., the formula for the normal distribution:

                                 2
                 1           - x   / 2
    f(x) = ------------- * e           ............(1.14)
           sqrt (2 * PI)

Numerical stability and convergence issues are an issue in such
processes.

The Shannon probability of a time series is the likelihood that the
value of the time series will increase in the next time interval. The
Shannon probability is measured using the average, avg, and root mean
square, rms, of the normalized increments of the time series. Using
the avg to compute the Shannon probability, P:

        sqrt (avg) + 1
    P = -------------- ............................(1.15)
              2

However, there is an error associated with the measurement of avg do
to the size of the data set, N, (ie., the number of records in the
time series,) used in the calculation of avg. The confidence level, c,
is the likelihood that this error is less than some error level, e.

Over the many time intervals represented in the time series, the error
will be greater than the error level, e, (1 - c) * 100 percent of the
time-requiring that the Shannon probability, P, be reduced by a factor
of c to accommodate the measurement error:

         sqrt (avg - e) + 1
    Pc = ------------------ .......................(1.16)
                 2

where the error level, e, and the confidence level, c, are calculated
using statistical estimates, and the product P times c is the
effective Shannon probability that should be used in the calculation
of optimal wagering strategies.

The error, e, expressed in terms of the standard deviation of the
measurement error do to an insufficient data set size, esigma, is:

              e
    esigma = --- sqrt (N) .........................(1.17)
             rms

where N is the data set size = number of records. From this, the
confidence level can be calculated from the cumulative sum, (ie.,
integration) of the normal distribution, ie.:

    c     esigma
    -------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

Note that the equation:

         sqrt (avg - e) + 1
    Pc = ------------------ .......................(1.18)
                 2

will require an iterated solution since the cumulative normal
distribution is transcendental. For convenience, let F(esigma) be the
function that given esigma, returns c, (ie., performs the table
operation, above,) then:

                    sqrt (avg - e) + 1
    P * F(esigma) = ------------------
                            2

                                rms * esigma
                    sqrt [avg - ------------] + 1
                                  sqrt (N)
                  = ----------------------------- .(1.19)
                                 2

Then:

    sqrt (avg)  + 1
    --------------- * F(esigma) =
           2

                    rms * esigma
        sqrt [avg - ------------] + 1
                      sqrt (N)
        ----------------------------- .............(1.20)
                     2

or:

    (sqrt (avg) + 1) * F(esigma) =

                    rms * esigma
        sqrt [avg - ------------] + 1 .............(1.21)
                      sqrt (N)

Letting a decision variable, decision, be the iteration error created
by this equation not being balanced:

                            rms * esigma
    decision = sqrt [avg - ------------] + 1
                              sqrt (N)

               - (sqrt (avg) + 1) * F(esigma) .....(1.22)

which can be iterated to find F(esigma), which is the confidence
level, c.

There are two radicals that have to be protected from numerical
floating point exceptions. The sqrt (avg) can be protected by
requiring that avg >= 0, (and returning a confidence level of 0.5, or
possibly zero, in this instance-a negative avg is not an interesting
solution for the case at hand.)  The other radical:

                rms * esigma
    sqrt [avg - ------------] .....................(1.23)
                  sqrt (N)

and substituting:

              e
    esigma = --- sqrt (N) .........................(1.24)
             rms

which is:

                       e
                rms * --- sqrt (N)
                      rms
    sqrt [avg - ------------------] ...............(1.25)
                  sqrt (N)

and reducing:

    sqrt [avg - e] ................................(1.26)

requiring that:

    avg >= e ......................................(1.27)

Note that if e > avg, then Pc < 0.5, which is not an interesting
solution for the case at hand. This would require:

              avg
    esigma <= --- sqrt (N) ........................(1.28)
              rms

Obviously, the search algorithm must be prohibited from searching for
a solution in this space. (ie., testing for a solution in this space.)

The solution is to limit the search of the confidence array to values
that are equal to or less than:

    avg
    --- sqrt (N) ..................................(1.29)
    rms

which can be accomplished by setting integer variable, top, usually
set to sigma_limit - 1, to this value.

Note that from the equation:

         sqrt (avg - e) + 1
    Pc = ------------------
                 2

and solving for avg - e, the effective value of avg compensated for
accuracy of measurement by statistical estimation:

                               2
    avg - e = ((2 * P * c) - 1)  ..................(1.30)

and substituting into the equation:

        sqrt (avg) + 1
    P = --------------
              2

                                          2
    avg - e = (((sqrt (avg) + 1) * c) - 1)  .......(1.31)

and defining the effective value of avg as avgeff:

    avgeff = avg - e ..............................(1.32)

It can be seen that if optimality exists, ie., f = 2P - 1,
or:

             2
    avg = rms  ....................................(1.33)

or:

    rmseff = sqrt (avgeff) ........................(1.34)

As an example of this algorithm, if the Shannon probability, P, is
0.52, corresponding to an avg of 0.0016, and an rms of 0.04, then the
confidence level, c, would be 0.987108, or the error level, e, would
be 0.000893, for a data set size, N, of 10000.

Likewise, if P is 0.6, corresponding to an rms of 0.2, and an avg of
0.04, then the confidence level, c, would be 0.922759, or the error
level, e, would be 0.028484, for a data set size of 100.

The Shannon probability of a time series is the likelihood that the
value of the time series will increase in the next time interval. The
Shannon probability is measured using the average, avg, and root mean
square, rms, of the normalized increments of the time series. Using
both the avg and the rms to compute the Shannon probability, P:

        avg
        --- + 1
        rms
    P = ------- ...................................(1.35)
           2

However, there is an error associated with both the measurement of avg
and rms do to the size of the data set, N, (ie., the number of records
in the time series,) used in the calculation of avg and rms. The
confidence level, c, is the likelihood that this error is less than
some error level, e.

Over the many time intervals represented in the time series, the error
will be greater than the error level, e, (1 - c) * 100 percent of the
time-requiring that the Shannon probability, P, be reduced by a factor
of c to accommodate the measurement error:

                  avg - ea
                  -------- + 1
                  rms + er
    P * ca * cr = ------------ ....................(1.36)
                       2

where the error level, ea, and the confidence level, ca, are
calculated using statistical estimates, for avg, and the error level,
er, and the confidence level, cr, are calculated using statistical
estimates for rms, and the product P * ca * cr is the effective
Shannon probability that should be used in the calculation of optimal
wagering strategies, (which is the product of the Shannon probability,
P, times the superposition of the two confidence levels, ca, and cr,
ie., P * ca * cr = Pc, eg., the assumption is made that the error in
avg and the error in rms are independent.)

The error, er, expressed in terms of the standard deviation of the
measurement error do to an insufficient data set size, esigmar, is:

              er
    esigmar = --- sqrt (2N) .......................(1.37)
              rms

where N is the data set size = number of records. From this, the
confidence level can be calculated from the cumulative sum, (ie.,
integration) of the normal distribution, ie.:

    cr     esigmar
    --------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

Note that the equation:

               avg
             -------- + 1
             rms + er
    P * cr = ------------ .........................(1.38)
                  2

will require an iterated solution since the cumulative normal
distribution is transcendental. For convenience, let F(esigmar) be the
function that given esigmar, returns cr, (ie., performs the table
operation, above,) then:

                       avg
                     -------- + 1
                     rms + er
    P * F(esigmar) = ------------ =
                          2

                             avg
                     ------------------- + 1
                           esigmar * rms
                     rms + -------------
                             sqrt (2N)
                     ----------------------- ......(1.39)
                                2

Then:

    avg
    --- + 1
    rms
    ------- * F(esigmar) =
       2

                   avg
           ------------------- + 1
                 esigmar * rms
           rms + -------------
                   sqrt (2N)
           ----------------------- ................(1.40)
                      2

or:

     avg
    (--- + 1) * F(esigmar) =
     rms

                   avg
           ------------------- + 1 ................(1.41)
                 esigmar * rms
           rms + -------------
                   sqrt (2N)

Letting a decision variable, decision, be the iteration error created
by this equation not being balanced:

                       avg
    decision =  ------------------- + 1
                      esigmar * rms
                rms + -------------
                       sqrt (2N)

                   avg
                - (--- + 1) * F(esigmar) ..........(1.42)
                   rms

which can be iterated to find F(esigmar), which is the confidence
level, cr.

The error, ea, expressed in terms of the standard deviation of the
measurement error do to an insufficient data set size, esigmaa, is:

              ea
    esigmaa = --- sqrt (N) ........................(1.43)
              rms

where N is the data set size = number of records. From this, the
confidence level can be calculated from the cumulative sum, (ie.,
integration) of the normal distribution, ie.:

    ca     esigmaa
    --------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

Note that the equation:

             avg - ea
             -------- + 1
               rms
    P * ca = ------------ .........................(1.44)
                  2

will require an iterated solution since the cumulative normal
distribution is transcendental. For convenience, let F(esigmaa) be the
function that given esigmaa, returns ca, (ie., performs the table
operation, above,) then:

                     avg - ea
                     -------- + 1
                       rms
    P * F(esigmaa) = ------------ =
                          2

                           esigmaa * rms
                     avg - -------------
                             sqrt (N)
                     ------------------- + 1
                               rms
                     ----------------------- ......(1.45)
                                2
Then:

    avg
    --- + 1
    rms
    ------- * F(esigmaa) =
       2

                 esigmaa * rms
           avg - -------------
                   sqrt (N)
           ------------------- + 1
                     rms
           ----------------------- ................(1.46)
                      2

or:

     avg
    (--- + 1) * F(esigmaa) =
     rms

                 esigmaa * rms
           avg - -------------
                   sqrt (N)
           ------------------- + 1 ................(1.47)
                     rms

Letting a decision variable, decision, be the iteration error created
by this equation not being balanced:

                     esigmaa * rms
               avg - -------------
                       sqrt (N)
    decision = ------------------- + 1
                         rms

           avg
        - (--- + 1) * F(esigmaa) ..................(1.48)
           rms

which can be iterated to find F(esigmaa), which is the confidence
level, ca.

Note that from the equation:

               avg
             -------- + 1
             rms + er
    P * cr = ------------
                  2

and solving for rms + er, the effective value of rms compensated for
accuracy of measurement by statistical estimation:

                     avg
    rms + er = ---------------- ...................(1.49)
               (2 * P * cr) - 1

and substituting into the equation:

        avg
        --- + 1
        rms
    P = -------
           2

                       avg
    rms + er = -------------------- ...............(1.50)
                 avg
               ((--- + 1) * cr) - 1
                 rms

and defining the effective value of avg as rmseff:

    rmseff = rms +/- er ...........................(1.51)

Note that from the equation:

             avg - ea
             -------- + 1
               rms
    P * ca = ------------
                  2

and solving for avg - ea, the effective value of avg compensated for
accuracy of measurement by statistical estimation:

    avg - ea = ((2 * P * ca) - 1) * rms ...........(1.52)

and substituting into the equation:

        avg
        --- + 1
        rms
    P = -------
           2

                  avg
    avg - ea = (((--- + 1) * ca) - 1) * rms .......(1.53)
                  rms

and defining the effective value of avg as avgeff:

    avgeff = avg - ea .............................(1.54)

As an example of this algorithm, if the Shannon probability, P, is
0.51, corresponding to an rms of 0.02, then the confidence level, c,
would be 0.983847, or the error level in avg, ea, would be 0.000306,
and the error level in rms, er, would be 0.001254, for a data set
size, N, of 20000.

Likewise, if P is 0.6, corresponding to an rms of 0.2 then the
confidence level, c, would be 0.947154, or the error level in avg, ea,
would be 0.010750, and the error level in rms, er, would be 0.010644,
for a data set size of 10.

As a final discussion to this section, consider the time series for an
equity. Suppose that the data set size is finite, and avg and rms have
both been measured, and have been found to both be positive. The
question that needs to be resolved concerns the confidence, not only
in these measurements, but the actual process that produced the time
series. For example, suppose, although there was no knowledge of the
fact, that the time series was actually produced by a Brownian motion
fractal mechanism, with a Shannon probability of exactly 0.5. We would
expect a "growth" phenomena for extended time intervals [Sch91,
pp. 152], in the time series, (in point of fact, we would expect the
cumulative distribution of the length of such intervals to be
proportional to erf (1 / sqrt (t)).) Note that, inadvertently, such a
time series would potentially justify investment. What the methodology
outlined in this section does is to preclude such scenarios by
effectively lowering the Shannon probability to accommodate such
issues. In such scenarios, the lowered Shannon probability will cause
data sets with larger sizes to be "favored," unless the avg and rms of
a smaller data set size are "strong" enough in relation to the Shannon
probabilities of the other equities in the market. Note that if the
data set sizes of all equities in the market are small, none will be
favored, since they would all be lowered by the same amount, (if they
were all statistically similar.)

To reiterate, in the equation avg = rms * (2P - 1), the Shannon
probability, P, can be compensated by the size of the data set, ie.,
Peff, and used in the equation avgeff = rms * (2Peff - 1), where rms
is the measured value of the root mean square of the normalized
increments, and avgeff is the effective, or compensated value, of
the average of the normalized increments.

DATA SET DURATION CONSIDERATIONS

An additional accuracy issue, besides data set size, is the time
interval over which the data was obtained. There is some possibility
that the data set was taken during an extended run length, either
negative or positive, and the Shannon probability will have to be
compensated to accommodate this measurement error. The chances that a
run length will exceed time, t, is:

    1 - erf (1 / sqrt (t)) ........................(1.55)

or the Shannon probability, P, will have to be compensated by a factor
of:

    erf (1 / sqrt (t)) ............................(1.56)

giving a compensated Shannon probability, Pcomp:

    Pcomp = Peff * (1 - erf (1 / sqrt (t)))........(1.57)

Fortunately, since confidence levels are calculated from the normal
probability function, the same lookup table used for confidence
calculations (ie., the cumulative of a normal distribution,) can be
used to calculate the associated error function.

To use the value of the normal probability function to calculate the
error function, erf (N), proceed as follows; since erf (X / sqrt (2))
represents the error function associated with the normal curve:

    1) X = N * sqrt (2).

    2) Lookup the value of X in the normal probability function.

    3) Subtract 0.5 from this value.

    4) And, multiply by 2.

or:

    erf (N) = 2 * (normal (t * sqrt (2)) - 0.5) ...(1.58)

PROGRAM ARCHITECTURE

I) Data architecture manipulation functions:

    A) There are three functions used in these calculations to
    perform statistical estimation:

        1) static double confidencerms ().

        2) static double confidenceavg ().

        3) static double confidenceavgrms ().

    B) There is a forth function called the first time by any of these
    three functions that sets up the data table structure used by the
    statistical estimation algorithms.

II) Program description:

    A) The function main serves to handle the required command line
    arguments, and dispatch to the data from the command line to the
    three functions used to perform statistical estimation.

III) Notes and asides:

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

IV) Constructional and stylistic issues follow, generally, a
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
$Id: tsshannoneffective.c,v 1.7 2006/01/07 10:05:09 john Exp $
$Log: tsshannoneffective.c,v $
Revision 1.7  2006/01/07 10:05:09  john
Initial revision


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>

#ifdef __STDC__

#include <float.h>

#endif

#ifndef PI /* make sure PI is defined */

#define PI 3.14159265358979323846 /* pi to 20 decimal places */

#endif

static char rcsid[] = "$Id: tsshannoneffective.c,v 1.7 2006/01/07 10:05:09 john Exp $"; /* program version */
static char copyright[] = "Copyright (c) 1994-2006, John Conover, All Rights Reserved"; /* the copyright banner */

#ifdef __STDC__

static const char *help_message[] = /* help message index array */

#else

static char *help_message[] = /* help message index array */

#endif

{

    "\n",
    "Calculate the effective Shannon probability\n",
    "Usage: tsshannoneffective [-c] [-e] [-v] avg rms number\n",
    "    avg is the average of the normalized increments of the time series\n",
    "    rms is the root mean square of the normalized increments of the time series\n",
    "    number is the number of records used to calculate avg and rms\n",
    "    -c compensate the Shannon probability for run length duration\n",
    "    -e print only erf (1 / sqrt (number)), 1 - erf (1 / sqrt (number))\n",
    "    -v print the program's version information\n"
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

typedef struct hash_struct /* structure for a stock's data */
{
    int count; /* count of avg or rms values in the running sum of avg and rms values */
    double Par, /* Shannon probability, using avg and rms */
           Pa, /* Shannon probability, using avg */
           Pr, /* Shannon probability, using rms */
           Pconfar, /* the confidence level in the measurment accuracy of the Shannon probability, using avg and rms */
           Pconfa, /* the confidence level in the measurment accuracy of the Shannon probability, using avg */
           Pconfr, /* the confidence level in the measurment accuracy of the Shannon probability, using rms */
           Peffar, /* effective Shannon probability, using avg and rms, compensated for measurement accuracy by statistical estimate */
           Peffa, /* effective Shannon probability, using avg, compensated for measurement accuracy by statistical estimate */
           Peffr, /* effective Shannon probability, using rms, compensated for measurement accuracy by statistical estimate */
           avg, /* average of the normalized increments, avg */
           rms; /* root mean square of the normalized increments, rms */
} HASH;

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static void cumulativenormal (void); /* construct the cumulative of the normal distribution */
static double confidencerms (HASH *stock); /* calculate the compensated Shannon probability using P = (rms + 1) / 2 */
static double confidenceavg (HASH *stock); /* calculate the compensated Shannon probability using P = (sqrt (avg) + 1) / 2 */
static double confidenceavgrms (HASH *stock); /* calculate the compensated Shannon probability using P = (avg / rms + 1) / 2 */
static double normal (double n); /* lookup the value of the normal probability function */
static int tsgetopt (int argc, char *argv[], const char *opts); /* get an option letter from argument vector */

#else

static void print_message (); /* print any error messages */
static void cumulativenormal (); /* construct the cumulative of the normal distribution */
static double confidencerms (); /* calculate the compensated Shannon probability using P = (rms + 1) / 2 */
static double confidenceavg (); /* calculate the compensated Shannon probability using P = (sqrt (avg) + 1) / 2 */
static double confidenceavgrms (); /* calculate the compensated Shannon probability using P = (avg / rms + 1) / 2 */
static double normal (); /* lookup the value of the normal probability function */
static int tsgetopt (); /* get an option letter from argument vector */

#endif

#define SIGMAS 3 /* 3 sigma limit, ie., 0 to 3 sigma */

#define STEPS_PER_SIGMA 1000 /* each sigma has 1000 steps of granularity */

#define MIDWAY(a,b) (((a) + (b)) / 2) /* bisect a segment of the confidence array */

static double confidence[SIGMAS * STEPS_PER_SIGMA]; /* the array of confidence levels, ie., the cumulative of the normal distribution */

static int sigma_limit = SIGMAS * STEPS_PER_SIGMA; /* the array size of the array of confidence levels, ie., SIGMAS * STEPS_PER_SIGMA, for calculation expediency */

static int cumulativeconstructed = 0; /* flag to determine whether the cumulative normal distribution array, confidence[], has been set up, 0 = no, 1 = yes */

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
    int retval = NOERROR, /* return value, assume no error */
        comp = 0, /* compensate the Shannon probability for run length duration flag, 0 = no, 1 = yes */
        e = 0, /* print only erf (1 / sqrt (number)), 1 - erf (1 / sqrt (number)) flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double erfcount; /* the error function of the count */

    HASH stock; /* the data carrier to the functions confidencerms (), confidenceavg (), and confidenceavgrms () */

    while ((c = tsgetopt (argc, argv, "cehv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'v':

                (void) printf ("%s\n", rcsid); /* print the version */
                (void) printf ("%s\n", copyright); /* print the copyright */
                optind = argc; /* force argument error */
                retval = EARGS; /* assume not enough arguments */
                break;

            case 'c': /* request to compensate the Shannon probability for run length duration? */

                comp = 1; /* yes, set the compensate the Shannon probability for run length duration flag, 0 = no, 1 = yes */
                break;

            case 'e': /* request for print only erf (1 / sqrt (number)), 1 - erf (1 / sqrt (number)) flag, 0 = no, 1 = yes? */

                e = 1; /* set the print only erf (1 / sqrt (number)), 1 - erf (1 / sqrt (number)) flag, 0 = no, 1 = yes */
                break;

            case '?':

                retval = EARGS; /* assume not enough arguments */
                break;

            case 'h': /* request for help? */

                optind = argc; /* force argument error */
                retval = EARGS; /* assume not enough arguments */
                break;

            default: /* illegal switch? */

                optind = argc; /* force argument error */
                retval = EARGS; /* assume not enough arguments */
                break;
        }

    }

    if (retval == NOERROR) /* enough arguments? */
    {
        retval = EARGS; /* no, assume not enough arguments */

        if (e == 0) /* print only erf (1 / sqrt (number)), 1 - erf (1 / sqrt (number)) flag, 0 = no, 1 = yes, set? */
        {

            if (argc - optind == 3) /* enough arguments? */
            {
                retval = NOERROR; /* assume no errors */
                stock.avg = atof (argv[optind]); /* no, save the average of the normalized increments */
                stock.rms = atof (argv[optind + 1]); /* save the root mean square of the normalized increments */
                stock.count = atoi (argv[optind + 2]); /* save the number of records, or data set size, used to calculate rms and avg */

                if (comp == 0) /* compensate the Shannon probability for run length duration flag, 0 = no, 1 = yes, set? */
                {
                    (void) confidenceavg (&stock); /* calculate the compensated Shannon probability using P = (sqrt (avg) + 1) / 2 */
                    (void) printf ("For P = (sqrt (avg) + 1) / 2:\n    P = %f\n    Peff = %f\n",  stock.Pa, stock.Peffa); /* print the values for Pc = (sqrt (avg) + 1) / 2 */

                    (void) confidencerms (&stock); /* calculate the compensated Shannon probability using P = (rms + 1) / 2 */
                    (void) printf ("For P = (rms + 1) / 2:\n    P = %f\n    Peff = %f\n", stock.Pr, stock.Peffr); /* print the values for Pc = (rms + 1) / 2 */

                    (void) confidenceavgrms (&stock); /* calculate the compensated Shannon probability using P = (avg / rms + 1) / 2 */
                    (void) printf ("For P = (avg / rms + 1) / 2:\n    P = %f\n    Peff = %f\n", stock.Par, stock.Peffar); /* print the values for Pc = (avg / rms + 1) / 2 */
                }

                else
                {
                    erfcount = (double) 2.0 * (normal (((double) 1.0 / sqrt ((double) stock.count)) * sqrt ((double) 2.0)) - (double) 0.5); /* calculate the error function of the count */

                    (void) confidenceavg (&stock); /* calculate the compensated Shannon probability using P = (sqrt (avg) + 1) / 2 */
                    (void) printf ("For P = (sqrt (avg) + 1) / 2:\n    P = %f\n    Pcomp = %f\n",  stock.Pa, stock.Peffa * ((double) 1.0 - erfcount)); /* print the values for Pc = (sqrt (avg) + 1) / 2 */

                    (void) confidencerms (&stock); /* calculate the compensated Shannon probability using P = (rms + 1) / 2 */
                    (void) printf ("For P = (rms + 1) / 2:\n    P = %f\n    Pcomp = %f\n", stock.Pr, stock.Peffr * ((double) 1.0 - erfcount)); /* print the values for Pc = (rms + 1) / 2 */

                    (void) confidenceavgrms (&stock); /* calculate the compensated Shannon probability using P = (avg / rms + 1) / 2 */
                    (void) printf ("For P = (avg / rms + 1) / 2:\n    P = %f\n    Pcomp = %f\n", stock.Par, stock.Peffar * ((double) 1.0 - erfcount)); /* print the values for Pc = (avg / rms + 1) / 2 */
                }

            }

        }

        else

        {

            if (argc - optind > 0) /* enough arguments? */
            {
                retval = NOERROR; /* assume no errors */
                stock.count = atoi (argv[argc - 1]); /* save the number of records, or data set size, used to calculate rms and avg */
                erfcount = (double) 2.0 * (normal (((double) 1.0 / sqrt ((double) stock.count)) * sqrt ((double) 2.0)) - (double) 0.5); /* calculate the error function of the count */
                (void) printf ("erf (1 / sqrt (%d)) = %f, 1 - erf (1 / sqrt (%d)) = %f\n", stock.count, erfcount, stock.count, 1 - erfcount); /* print the values for erf (1 / sqrt (n)), and 1 - erf (1 / sqrt (n)) */
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

I) The Shannon probability of a time series is the likelihood that the
value of the time series will increase in the next time interval. The
Shannon probability is measured using the average, avg, and root mean
square, rms, of the normalized increments of the time series. Using
the rms to compute the Shannon probability, P:

        rms + 1
    P = -------
           2

    A) However, there is an error associated with the measurement of
    rms do to the size of the data set, N, (ie., the number of records
    in the time series,) used in the calculation of rms. The
    confidence level, c, is the likelihood that this error is less
    than some error level, e.

    B) Over the many time intervals represented in the time series,
    the error will be greater than the error level, e, (1 - c) * 100
    percent of the time-requiring that the Shannon probability, P, be
    reduced by a factor of c to accommodate the measurement error:

         rms - e + 1
    Pc = -----------
              2

    where the error level, e, and the confidence level, c, are
    calculated using statistical estimates, and the product P times c
    is the effective Shannon probability that should be used in the
    calculation of optimal wagering strategies.

    C) The error, e, expressed in terms of the standard deviation of
    the measurement error do to an insufficient data set size, esigma,
    is:

              e
    esigma = --- sqrt (2N)
             rms

    where N is the data set size = number of records. From this, the
    confidence level can be calculated from the cumulative sum, (ie.,
    integration) of the normal distribution, ie.:

    c     esigma
    -------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

    D) Note that the equation:

         rms - e + 1
    Pc = -----------
              2

    will require an iterated solution since the cumulative normal
    distribution is transcendental. For convenience, let F(esigma) be
    the function that given esigma, returns c, (ie., performs the
    table operation, above,) then:

                                        rms * esigma
                                  rms - ------------ + 1
                    rms - e + 1          sqrt (2N)
    P * F(esigma) = ----------- = ----------------------
                         2                  2

    Then:

                                rms * esigma
                          rms - ------------ + 1
    rms + 1                      sqrt (2N)
    ------- * F(esigma) = ----------------------
       2                            2

    or:

                                  rms * esigma
    (rms + 1) * F(esigma) = rms - ------------ + 1
                                   sqrt (2N)

    E) Letting a decision variable, decision, be the iteration error
    created by this equation not being balanced:

                     rms * esigma
    decision = rms - ------------ + 1 - (rms + 1) * F(esigma)
                       sqrt (2N)

    which can be iterated to find F(esigma), which is the confidence
    level, c.

    Note that from the equation:

         rms - e + 1
    Pc = -----------
              2

    and solving for rms - e, the effective value of rms compensated
    for accuracy of measurement by statistical estimation:

    rms - e = (2 * P * c) - 1

    and substituting into the equation:

        rms + 1
    P = -------
           2

    rms - e = ((rms + 1) * c) - 1

    and defining the effective value of rms as rmseff:

    rmseff = rms - e

    From the optimization equations it, can be seen that if optimality
    exists, ie., f = 2P - 1, or:

             2
    avg = rms

    or:
                   2
    avgeff = rmseff

    F) As an example of this algorithm, if the Shannon probability, P,
    is 0.51, corresponding to an rms of 0.02, then the confidence
    level, c, would be 0.996298, or the error level, e, would be
    0.003776, for a data set size, N, of 100.

    Likewise, if P is 0.6, corresponding to an rms of 0.2 then the
    confidence level, c, would be 0.941584, or the error level, e,
    would be 0.070100, for a data set size of 10.

    G) Robustness is an issue in algorithms that, potentially, operate
    real time. The traditional means of implementation of statistical
    estimates is to use an integration process inside of a loop that
    calculates the cumulative of the normal distribution, controlled
    by, perhaps, a Newton Method approximation using the derivative of
    cumulative of the normal distribution, ie., the formula for the
    normal distribution:

                                 2
                 1           - x   / 2
    f(x) = ------------- * e
           sqrt (2 * PI)

    H) Numerical stability and convergence issues are an issue in such
    processes.

*/

/*

Construct the cumulative of the normal distribution.

static void cumulativenormal (void);

I) The process chosen uses a binary search of the cumulative of the
normal distribution. First a "graph" or "table" (ie., like the table
above,) is constructed in memory of the cumulative of the normal
distribution. This "graph" is then used in a binary search routine to
compute the confidence level.

II) Constructs the array, confidence[], which is used by the functions
double confidenceavg (), double confidencerms (), and confidencermsavg
(), as a lookup mechanism for the cumulative of a normal distribution.

III) This is simply a lookup table, with the implicit index
representing a sigma value, between half the distribution, and half
the distribution plus SIGMAS. Each sigma is divided into
STEPS_PER_SIGMA many steps.

Returns nothing.

*/

#ifdef __STDC__

static void cumulativenormal (void)

#else

static void cumulativenormal ()

#endif

{
    int i; /* step counter through cumulative of normal distribution */

    double scale = (double) 1.0 / sqrt ((double) 2.0 * (double) PI), /* scaling factor for cumulative of normal distribution, for math expediency */
           del = (double) 1.0 / (double) STEPS_PER_SIGMA, /* increment of granularity of x axis of cumulative of normal distribution */
           x = (double) 0.0, /* x variable in cumulative of normal distribution */
           cumulativesum = (double) 0.5; /* cumulative of normal distribution, begins at half of the distribution, ie., x = 0 */

    for (i = 0; i < sigma_limit; i ++) /* for each step in the cumulative of the normal distribution */
    {
        cumulativesum = cumulativesum + ((scale * (exp ((- (x * x)) / ((double) 2.0)))) / (double) STEPS_PER_SIGMA); /* add the value of the normal distribution for this x to the cumulative of the normal distribution */
        confidence[i] = cumulativesum; /* save the value of the cumulative of the normal distribution for this x */
        x = x + del; /* next increment of x in the cumulative of the normal distribution */
    }

    cumulativeconstructed = 1; /* set the flag to determine whether the cumulative normal distribution array, confidence[], has been set up, 0 = no, 1 = yes */
}

/*

Calculate the compensated Shannon probability using P = (rms + 1) / 2.

static double confidencerms (HASH *stock);

I) Calculate the compensated Shannon probability, given the root mean
square of the normalized increments, rms, and the size of the data
set, N, used to calculate rms, for the stock referenced. The
compensated Shannon probability is the value of the Shannon
probability, reduced by the accuracy of the measurement, based on
statistical estimate for a data set of size N. The stock structure
referenced is loaded with the computed statistical values.

Returns the compensated Shannon probability, given the root mean
square of the normalized increments, rms, and the size of the data
set, N, used to calculate rms, for the stock referenced.

*/

#ifdef __STDC__

static double confidencerms (HASH *stock)

#else

static double confidencerms (stock)
HASH *stock;

#endif

{
    int bottom = 0, /* bottom index of segment of confidence array, initialize to bottom index of confidence array */
        middle = 0, /* middle index of segment of confidence array */
        top = sigma_limit - 1, /* top index of segment of confidence array, (confidence array of size n elements is indexed 0 to n - 1,) initialize to top index of confidence array */
        N = stock->count; /* save the size of the data set */

    double scale, /* scaling factor, as a calculation expediency */
           decision, /* decision variable on whether to move up, or down, the confidence array, ie., the stearing variable */
           rms = stock->rms, /* root mean square of the normalized increments, rms */
           Pconfidence, /* the confidence level in the measurment accuracy of the Shannon probability */
           P, /* Shannon probability */
           Peff = (double) 0.0; /* effective Shannon probability, compensated for measurement accuracy by statistical estimate */

    if (cumulativeconstructed == 0) /* cumulative normal distribution array, confidence[], been set up? 0 = no, 1 = yes */
    {
        cumulativenormal (); /* no, set up the cumulative normal distribution array, confidence[] */
    }

    scale = rms / sqrt ((double) 2.0 * N); /* calculate the scaling factor, as a calculation expediency */

    while (top > bottom) /* while the top index of the segment of the confidence array is greater than the bottom index of the segment of the confidence array, if the top is ever equal to the bottom, the search is finished */
    {
        middle = MIDWAY(bottom, top); /* starting in the middle of the segment of the confidence array */
        decision = rms - (scale * ((double) middle / (double) STEPS_PER_SIGMA)) + (double) 1.0 - ((rms + 1) * confidence[middle]); /* calcluate the decision variable on whether to move up, or down, the confidence array, ie., the stearing variable */

        if (decision < 0) /* if the decision variable is negative, move down the confidence array */
        {
            top = middle - 1; /* the next segement of the confidence array will begin just below the current middle of the current segment of the confidence array */
        }

        else /* if the decision variable is positive, (or zero,) move up the confidence array */
        {
            bottom = middle + 1; /* the next segement of the confidence array will end just below the current middle of the current segment of the confidence array */
        }

    }

    Pconfidence = confidence[middle]; /* save the confidence level in the measurment accuracy of the Shannon probability */
    stock->Pconfr = Pconfidence; /* save the confidence level in the measurment accuracy of the Shannon probability, using rms */
    P = (rms + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
    stock->Pr = P; /* save the Shannon probability */
    Peff = P * Pconfidence; /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
    stock->Peffr = Peff; /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
    return (Peff); /* return the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
}

/*

II) The Shannon probability of a time series is the likelihood that the
value of the time series will increase in the next time interval. The
Shannon probability is measured using the average, avg, and root mean
square, rms, of the normalized increments of the time series. Using
the avg to compute the Shannon probability, P:

        sqrt (avg) + 1
    P = --------------
              2

    A) However, there is an error associated with the measurement of
    avg do to the size of the data set, N, (ie., the number of records
    in the time series,) used in the calculation of avg. The
    confidence level, c, is the likelihood that this error is less
    than some error level, e.

    B) Over the many time intervals represented in the time series,
    the error will be greater than the error level, e, (1 - c) * 100
    percent of the time-requiring that the Shannon probability, P, be
    reduced by a factor of c to accommodate the measurement error:

         sqrt (avg - e) + 1
    Pc = ------------------
                 2

    where the error level, e, and the confidence level, c, are
    calculated using statistical estimates, and the product P times c
    is the effective Shannon probability that should be used in the
    calculation of optimal wagering strategies.

    C) The error, e, expressed in terms of the standard deviation of
    the measurement error do to an insufficient data set size, esigma,
    is:

              e
    esigma = --- sqrt (N)
             rms

    where N is the data set size = number of records. From this, the
    confidence level can be calculated from the cumulative sum, (ie.,
    integration) of the normal distribution, ie.:

    c     esigma
    -------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

    D) Note that the equation:

         sqrt (avg - e) + 1
    Pc = ------------------
                 2

    will require an iterated solution since the cumulative normal
    distribution is transcendental. For convenience, let F(esigma) be
    the function that given esigma, returns c, (ie., performs the
    table operation, above,) then:

                    sqrt (avg - e) + 1
    P * F(esigma) = ------------------
                            2

                                rms * esigma
                    sqrt [avg - ------------] + 1
                                  sqrt (N)
                  = -----------------------------
                                 2

    Then:

    sqrt (avg)  + 1
    --------------- * F(esigma) =
           2

                    rms * esigma
        sqrt [avg - ------------] + 1
                      sqrt (N)
        -----------------------------
                     2

    or:

    (sqrt (avg) + 1) * F(esigma) =

                    rms * esigma
        sqrt [avg - ------------] + 1
                      sqrt (N)

    E) Letting a decision variable, decision, be the iteration error
    created by this equation not being balanced:

                            rms * esigma
    decision = sqrt [avg - ------------] + 1
                              sqrt (N)

               - (sqrt (avg) + 1) * F(esigma)

    which can be iterated to find F(esigma), which is the confidence
    level, c.

    F) There are two radicals that have to be protected from numerical
    floating point exceptions. The sqrt (avg) can be protected by
    requiring that avg >= 0, (and returning a confidence level of 0.5,
    or possibly zero, in this instance-a negative avg is not an
    interesting solution for the case at hand.)  The other radical:

                rms * esigma
    sqrt [avg - ------------]
                  sqrt (N)

    and substituting:

              e
    esigma = --- sqrt (N)
             rms

    which is:

                       e
                rms * --- sqrt (N)
                      rms
    sqrt [avg - ------------------]
                  sqrt (N)

    and reducing:

    sqrt [avg - e]

    requiring that:

    avg >= e

    G) Note that if e > avg, then Pc < 0.5, which is not an
    interesting solution for the case at hand. This would require:

              avg
    esigma <= --- sqrt (N)
              rms

    H) Obviously, the search algorithm must be prohibited from
    searching for a solution in this space. (ie., testing for a
    solution in this space.)

    The solution is to limit the search of the confidence array to
    values that are equal to or less than:

    avg
    --- sqrt (N)
    rms

    which can be accomplished by setting integer variable, top,
    usually set to sigma_limit - 1, to this value.

    I) Note that from the equation:

         sqrt (avg - e) + 1
    Pc = ------------------
                 2

    and solving for avg - e, the effective value of avg compensated
    for accuracy of measurement by statistical estimation:

                               2
    avg - e = ((2 * P * c) - 1)

    and substituting into the equation:

        sqrt (avg) + 1
    P = --------------
              2

                                          2
    avg - e = (((sqrt (avg) + 1) * c) - 1)

    and defining the effective value of avg as avgeff:

    avgeff = avg - e

    J) From the optimization equations, it can be seen that if
    optimality exists, ie., f = 2P - 1, or:

             2
    avg = rms

    or:

    rmseff = sqrt (avgeff)

    K) As an example of this algorithm, if the Shannon probability, P,
    is 0.52, corresponding to an avg of 0.0016, and an rms of 0.04,
    then the confidence level, c, would be 0.987108, or the error
    level, e, would be 0.000893, for a data set size, N, of 10000.

    Likewise, if P is 0.6, corresponding to an rms of 0.2, and an avg
    of 0.04, then the confidence level, c, would be 0.922759, or the
    error level, e, would be 0.028484, for a data set size of 100.

*/

/*

Calculate the compensated Shannon probability using P = (sqrt (avg) + 1) / 2.

static double confidenceavg (HASH *stock);

I) Calculate the compensated Shannon probability, given the average
and root mean square of the normalized increments, avg and rms, and
the size of the data set, N, used to calculate avg and rms, for the
stock referenced. The compensated Shannon probability is the value of
the Shannon probability, reduced by the accuracy of the measurement,
based on statistical estimate for a data set of size N. The stock
structure referenced is loaded with the computed statistical values.

II) Note: under numerical exceptions, (ie., rms = 0, or avg < 0, or
both,) the stock structure is set with the following, calculated from
a confidence level of 0.5:

    P  = 0.5
    Peff = 0.25

since both avg and rms must be less than or equal to unity, a avge and
rmse of unity would imply a "worst case" data set size of zero, and a
Brownian noise statistical sample, ie., a Shannon probability of 0.5,
with a confidence level of 50%. (ie., this would imply that avgeff and
rmseff would be negative numbers, with a Shannon probability of 0.5,
with a confidence level of 50%-this precaution is deemed necessary to
protect the operations in the function invest (), which may be
evaluating these parameters. A P and Peff of zero would also make
sense. Note that avg and rms are left unchanged.) Note that avg can
frequently be negative in small data set sizes, and rms can be zero
during initialization of the first record of a stock's time series.

Returns the compensated Shannon probability, given the average and
root mean square of the normalized increments, avg and rms, and the
size of the data set, N, used to calculate avg and rms, for the stock
referenced.

*/

#ifdef __STDC__

static double confidenceavg (HASH *stock)

#else

static double confidenceavg (stock)
HASH *stock;

#endif

{
    int bottom = 0, /* bottom index of segment of confidence array, initialize to bottom index of confidence array */
        middle = 0, /* middle index of segment of confidence array */
        top = sigma_limit - 1, /* top index of segment of confidence array, (confidence array of size n elements is indexed 0 to n - 1,) initialize to top index of confidence array */
        N = stock->count; /* save the size of the data set */

    double scale1, /* first scaling factor, as a calculation expediency */
           scale2, /* second scaling factor, as a calculation expediency */
           decision, /* decision variable on whether to move up, or down, the confidence array, ie., the stearing variable */
           avg = stock->avg, /* average of the normalized increments, avg */
           rms = stock->rms, /* root mean square of the normalized increments, rms */
           Pconfidence, /* the confidence level in the measurment accuracy of the Shannon probability */
           P, /* Shannon probability */
           Peff = (double) 0.0; /* effective Shannon probability, compensated for measurement accuracy by statistical estimate */

    if (cumulativeconstructed == 0) /* cumulative normal distribution array, confidence[], been set up? 0 = no, 1 = yes */
    {
        cumulativenormal (); /* no, set up the cumulative normal distribution array, confidence[] */
    }

    stock->Pa = (double) 0.5; /* save the Shannon probability, assuming a numerical exception */
    stock->Peffa = (double) 0.25; /* save the effective Shannon probability, assuming a numerical exception */
    stock->Pconfa = (double) 0.5; /* save the confidence level in the measurment accuracy of the Shannon probability, using avg, assuming a numerical exception */

    if (avg >= (double) 0.0) /* if the avg is negative, calculate using the assumed confidence level of 0.5 */
    {

        if (rms > (double) 0.0) /* if the rms is zero, calculate using the assumed confidence level of 0.5 */
        {
            scale1 = rms / sqrt ((double) N); /* calculate the first scaling factor, as a calculation expediency */
            scale2 = sqrt (avg) + (double) 1.0; /* calculate the second scaling factor, as a calculation expediency */

            top = (int) floor ((avg / scale1) * STEPS_PER_SIGMA) - 1; /* top index of segment of confidence array, (confidence array of size n elements is indexed 0 to n - 1,) initialize to top index of confidence array, not to exceed (avg / rms) * sqrt (N) */

            if (top > sigma_limit - 1) /* top greater than the top of the confidence array? */
            {
                top = sigma_limit - 1; /* yes, top index of segment of confidence array, (confidence array of size n elements is indexed 0 to n - 1,) initialize to top index of confidence array */
            }

            while (top > bottom) /* while the top index of the segment of the confidence array is greater than the bottom index of the segment of the confidence array, if the top is ever equal to the bottom, the search is finished */
            {
                middle = MIDWAY(bottom, top); /* starting in the middle of the segment of the confidence array */
                decision = sqrt (avg - (scale1 * ((double) middle / (double) STEPS_PER_SIGMA))) + (double) 1.0 - (scale2 * confidence[middle]); /* calcluate the decision variable on whether to move up, or down, the confidence array, ie., the stearing variable */

                if (decision < 0) /* if the decision variable is negative, move down the confidence array */
                {
                    top = middle - 1; /* the next segement of the confidence array will begin just below the current middle of the current segment of the confidence array */
                }

                else /* if the decision variable is positive, (or zero,) move up the confidence array */
                {
                    bottom = middle + 1; /* the next segement of the confidence array will end just below the current middle of the current segment of the confidence array */
                }

            }

            Pconfidence = confidence[middle]; /* save the confidence level in the measurment accuracy of the Shannon probability */
            stock->Pconfa = Pconfidence; /* save the confidence level in the measurment accuracy of the Shannon probability, using avg */
            P = (sqrt (avg) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
            stock->Pa = P; /* save the Shannon probability */
            Peff = P * Pconfidence; /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
            stock->Peffa = Peff; /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
        }

    }

    return (Peff); /* return the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
}

/*

III) The Shannon probability of a time series is the likelihood that
the value of the time series will increase in the next time
interval. The Shannon probability is measured using the average, avg,
and root mean square, rms, of the normalized increments of the time
series. Using both the avg and the rms to compute the Shannon
probability, P:

        avg
        --- + 1
        rms
    P = -------
           2

    A) However, there is an error associated with both the measurement
    of avg and rms do to the size of the data set, N, (ie., the number
    of records in the time series,) used in the calculation of avg and
    rms. The confidence level, c, is the likelihood that this error is
    less than some error level, e.

    B) Over the many time intervals represented in the time series,
    the error will be greater than the error level, e, (1 - c) * 100
    percent of the time-requiring that the Shannon probability, P, be
    reduced by a factor of c to accommodate the measurement error:

                  avg - ea
                  -------- + 1
                  rms + er
    P * ca * cr = ------------
                       2

    where the error level, ea, and the confidence level, ca, are
    calculated using statistical estimates, for avg, and the error
    level, er, and the confidence level, cr, are calculated using
    statistical estimates for rms, and the product P * ca * cr is the
    effective Shannon probability that should be used in the
    calculation of optimal wagering strategies, (which is the product
    of the Shannon probability, P, times the superposition of the two
    confidence levels, ca, and cr, ie., P * ca * cr = Pc, eg., the
    assumption is made that the error in avg and the error in rms are
    independent.)

    C) The error, er, expressed in terms of the standard deviation of
    the measurement error do to an insufficient data set size,
    esigmar, is:

              er
    esigmar = --- sqrt (2N)
              rms

where N is the data set size = number of records. From this, the
confidence level can be calculated from the cumulative sum, (ie.,
integration) of the normal distribution, ie.:

    cr     esigmar
    --------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

    D) Note that the equation:

               avg
             -------- + 1
             rms + er
    P * cr = ------------
                  2

    will require an iterated solution since the cumulative normal
    distribution is transcendental. For convenience, let F(esigmar) be
    the function that given esigmar, returns cr, (ie., performs the
    table operation, above,) then:

                       avg
                     -------- + 1
                     rms + er
    P * F(esigmar) = ------------
                          2

                             avg
                     ------------------- + 1
                           esigmar * rms
                     rms + -------------
                             sqrt (2N)
                   = -----------------------
                                2

    Then:

                                   avg
                           ------------------- + 1
    avg                          esigmar * rms
    --- + 1                rms + -------------
    rms                            sqrt (2N)
    ------- * F(esigmar) = -----------------------
       2                              2

    or:

     avg                             avg
    (--- + 1) * F(esigmar) = ------------------- + 1
     rms                           esigmar * rms
                             rms + -------------
                                     sqrt (2N)

    E) Letting a decision variable, decision, be the iteration error
    created by this equation not being balanced:

                       avg                 avg
    decision =  ------------------- + 1 - (--- + 1) * F(esigmar)
                      esigmar * rms        rms
                rms + -------------
                       sqrt (2N)

    which can be iterated to find F(esigmar), which is the confidence
    level, cr.

    F) The error, ea, expressed in terms of the standard deviation of
    the measurement error do to an insufficient data set size,
    esigmaa, is:

              ea
    esigmaa = --- sqrt (N)
              rms

    where N is the data set size = number of records. From this, the
    confidence level can be calculated from the cumulative sum, (ie.,
    integration) of the normal distribution, ie.:

    ca     esigmaa
    --------------
    50     0.67
    68.27  1.00
    80     1.28
    90     1.64
    95     1.96
    95.45  2.00
    99     2.58
    99.73  3.00

    G) Note that the equation:

             avg - ea
             -------- + 1
               rms
    P * ca = ------------
                  2

    will require an iterated solution since the cumulative normal
    distribution is transcendental. For convenience, let F(esigmaa) be
    the function that given esigmaa, returns ca, (ie., performs the
    table operation, above,) then:

                     avg - ea
                     -------- + 1
                       rms
    P * F(esigmaa) = ------------
                          2

                           esigmaa * rms
                     avg - -------------
                             sqrt (N)
                     ------------------- + 1
                               rms
                   = -----------------------
                                2
    Then:

                                 esigmaa * rms
                           avg - -------------
    avg                            sqrt (N)
    --- + 1                ------------------- + 1
    rms                              rms
    ------- * F(esigmaa) = -----------------------
       2                              2

    or:

                                   esigmaa * rms
                             avg - -------------
     avg                             sqrt (N)
    (--- + 1) * F(esigmaa) = ------------------- + 1
     rms                               rms

    H) Letting a decision variable, decision, be the iteration error
    created by this equation not being balanced:

                     esigmaa * rms
               avg - -------------
                       sqrt (N)           avg
    decision = ------------------- + 1 - (--- + 1) * F(esigmaa)
                         rms              rms

    which can be iterated to find F(esigmaa), which is the confidence
    level, ca.

    I) Note that from the equation:

               avg
             -------- + 1
             rms + er
    P * cr = ------------
                  2

    and solving for rms + er, the effective value of rms compensated
    for accuracy of measurement by statistical estimation:

                     avg
    rms + er = ----------------
               (2 * P * cr) - 1

    and substituting into the equation:

        avg
        --- + 1
        rms
    P = -------
           2

                       avg
    rms + er = --------------------
                 avg
               ((--- + 1) * cr) - 1
                 rms

    and defining the effective value of avg as rmseff:

    rmseff = rms +/- er

    J) Note that from the equation:

             avg - ea
             -------- + 1
               rms
    P * ca = ------------
                  2

    and solving for avg - ea, the effective value of avg compensated
    for accuracy of measurement by statistical estimation:

    avg - ea = ((2 * P * ca) - 1) * rms

    and substituting into the equation:

        avg
        --- + 1
        rms
    P = -------
           2

                  avg
    avg - ea = (((--- + 1) * ca) - 1) * rms
                  rms

    and defining the effective value of avg as avgeff:

    avgeff = avg - ea

    K) As an example of this algorithm, if the Shannon probability, P,
    is 0.51, corresponding to an rms of 0.02, then the confidence
    level, c, would be 0.983847, or the error level in avg, ea, would
    be 0.000306, and the error level in rms, er, would be 0.001254,
    for a data set size, N, of 20000.

    Likewise, if P is 0.6, corresponding to an rms of 0.2 then the
    confidence level, c, would be 0.947154, or the error level in avg,
    ea, would be 0.010750, and the error level in rms, er, would be
    0.010644, for a data set size of 10.

    L) As a final discussion to this section, consider the time series
    for an equity. Suppose that the data set size is finite, and avg
    and rms have both been measured, and have been found to both be
    positive. The question that needs to be resolved concerns the
    confidence, not only in these measurements, but the actual process
    that produced the time series. For example, suppose, although
    there was no knowledge of the fact, that the time series was
    actually produced by a Brownian motion fractal mechanism, with a
    Shannon probability of exactly 0.5. We would expect a "growth"
    phenomena for extended time intervals [Sch91, pp. 152], in the
    time series, (in point of fact, we would expect the cumulative
    distribution of the length of such intervals to be proportional to
    1 / sqrt (t).)  Note that, inadvertently, such a time series would
    potentially justify investment. What the methodology outlined in
    this section does is to preclude such scenarios by effectively
    lowering the Shannon probability to accommodate such issues. In
    such scenarios, the lowered Shannon probability will cause data
    sets with larger sizes to be "favored," unless the avg and rms of
    a smaller data set size are "strong" enough in relation to the
    Shannon probabilities of the other equities in the market. Note
    that if the data set sizes of all equities in the market are
    small, none will be favored, since they would all be lowered by
    the same amount, (if they were all statistically similar.)

    To reiterate, in the equation avg = rms * (2P - 1), the Shannon
    probability, P, can be compensated by the size of the data set,
    ie., Peff, and used in the equation avgeff = rms * (2Peff - 1),
    where rms is the measured value of the root mean square of the
    normalized increments, and avgeff is the effective, or compensated
    value, of the average of the normalized increments.

*/

/*

Calculate the compensated Shannon probability using P = (avg / rms + 1) / 2.

static double confidenceavgrms (HASH *stock);

I) Calculate the compensated Shannon probability, given the average
and root mean square of the normalized increments, avg and rms, and
the size of the data set, N, used to calculate avg and rms, for the
stock referenced. The compensated Shannon probability is the value of
the Shannon probability, reduced by the accuracy of the measurement,
based on statistical estimate for a data set of size N. The stock
structure referenced is loaded with the computed statistical values.

II) Note: under numerical exceptions, (ie., rms = 0,) the stock
structure is set with the following, calculated from a confidence
level of 0.5:

    P  = 0.5
    Peff = 0.25

since both avg and rms must be less than or equal to unity, a avge and
rmse of unity would imply a "worst case" data set size of zero, and a
Brownian noise statistical sample, ie., a Shannon probability of 0.5,
with a confidence level of 50%. (ie., this would imply that avgeff and
rmseff would be negative numbers, with a Shannon probability of 0.5,
with a confidence level of 50%-this precaution is deemed necessary to
protect the operations in the function invest (), which may be
evaluating these parameters. A P and Peff of zero would also make
sense. Note that avg and rms are left unchanged.) Note rms can be zero
during initialization of the first record of a stock's time series.

Returns the compensated Shannon probability, given the average and
root mean square of the normalized increments, avg and rms, and the
size of the data set, N, used to calculate avg and rms, for the stock
referenced.

*/

#ifdef __STDC__

static double confidenceavgrms (HASH *stock)

#else

static double confidenceavgrms (stock)
HASH *stock;

#endif

{
    int bottom = 0, /* bottom index of segment of confidence array, initialize to bottom index of confidence array */
        middle = 0, /* middle index of segment of confidence array */
        top = sigma_limit - 1, /* top index of segment of confidence array, (confidence array of size n elements is indexed 0 to n - 1,) initialize to top index of confidence array */
        N = stock->count; /* save the size of the data set */

    double cr, /* confidence level of rms */
           ca, /* confidence level of avg */
           scale1, /* first scaling factor, as a calculation expediency */
           scale2, /* second scaling factor, as a calculation expediency */
           decision, /* decision variable on whether to move up, or down, the confidence array, ie., the stearing variable */
           avg = stock->avg, /* average of the normalized increments, avg */
           rms = stock->rms, /* root mean square of the normalized increments, rms */
           Pconfidence, /* the confidence level in the measurment accuracy of the Shannon probability */
           P, /* Shannon probability */
           Peff = (double) 0.0; /* effective Shannon probability, compensated for measurement accuracy by statistical estimate */

    if (cumulativeconstructed == 0) /* cumulative normal distribution array, confidence[], been set up? 0 = no, 1 = yes */
    {
        cumulativenormal (); /* no, set up the cumulative normal distribution array, confidence[] */
    }

    stock->Par = (double) 0.5; /* save the Shannon probability, assuming a numerical exception */
    stock->Peffar = (double) 0.25; /* save the effective Shannon probability, assuming a numerical exception */
    stock->Pconfar = (double) 0.5; /* save the confidence level in the measurment accuracy of the Shannon probability, using avg and rms, assuming a numerical exception */

    if (rms > (double) 0.0) /* if the rms is zero, return the assumed confidence level of 0.5 */
    {
        scale1 = rms / sqrt ((double) 2.0 * N); /* calculate the first scaling factor, as a calculation expediency */
        scale2 = (avg / rms) + (double) 1.0; /* calculate the second scaling factor, as a calculation expediency */

        while (top > bottom) /* while the top index of the segment of the confidence array is greater than the bottom index of the segment of the confidence array, if the top is ever equal to the bottom, the search is finished */
        {
            middle = MIDWAY(bottom, top); /* starting in the middle of the segment of the confidence array */
            decision = (avg) / (rms + (((double) middle / (double) STEPS_PER_SIGMA) * scale1)) + (double) 1.0 - (scale2 * confidence[middle]); /* calcluate the decision variable on whether to move up, or down, the confidence array, ie., the stearing variable */

            if (decision < 0) /* if the decision variable is negative, move down the confidence array */
            {
                top = middle - 1; /* the next segement of the confidence array will begin just below the current middle of the current segment of the confidence array */
            }

            else /* if the decision variable is positive, (or zero,) move up the confidence array */
            {
                bottom = middle + 1; /* the next segement of the confidence array will end just below the current middle of the current segment of the confidence array */
            }

        }

        cr = confidence[middle]; /* save the confidence level of rms */
        bottom = 0; /* bottom index of segment of confidence array, initialize to bottom index of confidence array */
        top = sigma_limit - 1; /* top index of segment of confidence array, (confidence array of size n elements is indexed 0 to n - 1,) initialize to top index of confidence array */
        scale1 = rms / sqrt ((double) N); /* calculate the first scaling factor, as a calculation expediency */

        while (top > bottom) /* while the top index of the segment of the confidence array is greater than the bottom index of the segment of the confidence array, if the top is ever equal to the bottom, the search is finished */
        {
            middle = MIDWAY(bottom, top); /* starting in the middle of the segment of the confidence array */
            decision = (((avg - (((double) middle / (double) STEPS_PER_SIGMA) * scale1)) / rms) + (double) 1.0 - (scale2 * confidence[middle])); /* calcluate the decision variable on whether to move up, or down, the confidence array, ie., the stearing variable */

            if (decision < 0) /* if the decision variable is negative, move down the confidence array */
            {
                top = middle - 1; /* the next segement of the confidence array will begin just below the current middle of the current segment of the confidence array */
            }

            else /* if the decision variable is positive, (or zero,) move up the confidence array */
            {
                bottom = middle + 1; /* the next segement of the confidence array will end just below the current middle of the current segment of the confidence array */
            }

        }

        ca = confidence[middle]; /* save the confidence level of avg */
        Pconfidence = ca * cr; /* save the confidence level in the measurment accuracy of the Shannon probability */
        stock->Pconfar = Pconfidence; /* save the confidence level in the measurment accuracy of the Shannon probability, using avg and rms */
        P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* calculate the Shannon probability */
        stock->Par = P; /* save the Shannon probability */
        Peff = P * Pconfidence; /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
        stock->Peffar = Peff; /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
    }

    return (Peff); /* return the effective Shannon probability, compensated for measurement accuracy by statistical estimate */
}

/*

Lookup the value of the normal probability function.

static double normal (double n);

I) Since the array, confidence[], is a lookup table for the cumulative
of a normal distribution, it can be used to calculate the normal
probability function and associated error function.

II) Lookup the value of the normal probability function, given the
standard deviation value.

III) To use the value of the normal probability function to calculate
the error function, erf (N), proceed as follows; since erf (X / sqrt
(2)) represents the error function associated with the normal curve:

    A) X = N * sqrt (2).

    B) Lookup the value of X in the normal probability function.

    C) Subtract 0.5 from this value.

    D) And, multiply by 2.

ie., erf (N) = (double) 2.0 * (normal ((double) N * sqrt ((double)
2.0)) - (double) 0.5).

Returns the value of the normal probability function, given n, the
standard deviation value.

*/

#ifdef __STDC__

static double normal (double n)

#else

static double normal (n)
double n;

#endif

{
    int confidence_index; /* index into confidence array = cumulative of the normal distribution */

    if (cumulativeconstructed == 0) /* cumulative normal distribution array, confidence[], been set up? 0 = no, 1 = yes */
    {
        cumulativenormal (); /* no, set up the cumulative normal distribution array, confidence[] */
    }

    confidence_index = (int) floor ((double) STEPS_PER_SIGMA * n); /* save the index into confidence array = cumulative of the normal distribution */

    if (confidence_index < sigma_limit) /* past the array size of the array of confidence levels, ie., SIGMAS * STEPS_PER_SIGMA, for calculation expediency? */
    {
        return (confidence[confidence_index]); /* no, lookup, and return, the value of the normal probability function */
    }

    else
    {
        return ((double) 1.0); /* yes, return unity */
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
