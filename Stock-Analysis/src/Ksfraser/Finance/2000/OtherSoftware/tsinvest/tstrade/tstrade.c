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

tstrade.c is for simulating the optimal gains of multiple equity
investments. The program decides which of all available equities to
invest in at any single time, by calculating the instantaneous Shannon
probability of all equities, and using an approximation to statistical
estimation techniques to estimate the accuracy of the calculated
Shannon probability.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Each data record
represents an equity transaction, consisting of a minium of six
fields, separated by white space. The fields are ordered by time
stamp, equity ticker identifier, maximum price in time unit, minimum
price in time unit, closing price in time unit, and trade volume.  The
existence of a record with more than 6 fields is used to suspend
transactions on the equity, concluding with the record, for example:

    930830      AA      38.125  37.875  37.938  333.6
    930830      AALR    3.250   2.875   3.250   7.2     Suspend
    930830      AHP     64.375  63.625  64.375  335.9

Note: this program usees the following functions from other
references:

    ran1, which returns a uniform random deviate between 0.0 and
    1.0. See "Numerical Recipes in C: The Art of Scientific
    Computing," William H. Press, Brian P. Flannery, Saul
    A. Teukolsky, William T. Vetterling, Cambridge University Press,
    New York, 1988, ISBN 0-521-35465-X, page 210, referencing Knuth.

INTRODUCTION

One of the prevailing concepts in financial quantitative analysis,
(eg., "financial engineering,") is that equity prices exhibit "random
walk," (eg., Brownian motion, or fractal,) characteristics. The
presentation by Brian Arthur [Art95] offers a compelling theoretical
framework for the random walk model.  William A. Brock and Pedro
J. F. de Lima [BdL95], among others, have published empirical evidence
supporting Arthur's theoretical arguments.

There is a large mathematical infrastructure available for
applications of fractal analysis to equity markets. For example, the
publications authored by Richard M. Crownover [Cro95], Edgar E. Peters
[Pet91], and Manfred Schroeder [Sch91] offer formal methodologies,
while the books by John L. Casti [Cas90], [Cas94] offer a less formal
approach for the popular press.

There are interesting implications that can be exploited if equity
prices exhibit fractal characteristics:

    1) It would be expected that equity portfolio volatility would be
    equal to the root mean square of the individual equity
    volatilities in the portfolio.

    2) It would be expected that equity portfolio growth would be
    equal to the linear addition of the growths of the individual
    equities in the portfolio.

    3) It would be expected that an equity's price would fluctuate,
    over time, and the range, of these fluctuations (ie., the maximum
    price minus the minimum price,) would increase with the square
    root of time.

    4) It would be expected that the number of equity price
    fluctuations in a time interval, (ie., the number of times an
    equity's price reaches a local maximum, then reverse direction and
    decreases to a local minimum,) would increase with the square root
    of time.

    5) It would be expected that the time between fluctuations in an
    equity's price, (ie., the interval between an equity's price
    reaching a local maximum and then a local minimum,) would decrease
    with the reciprocal of the square root of time.

    6) It would be expected that an equity's price, over time, would
    be mean reverting, (ie., if an equity's price is below its
    average, there would be a propensity for the equity's price to
    increase, and vice versa.)

Note that 1) and 2) above can be exploited to formulate an optimal
hedging strategy; 3), 4), and 5) would tend to imply that "market
timing" is not attainable; and 6) can be exploited to formulate an
optimal buy-sell strategy.

DERIVATION

As a tutorial, the derivation will start with a simple compound
interest equation. This equation will be extended to a first order
random walk model of equity prices. Finally, optimizations will
derived based on the random walk model that are useful in optimizing
equity portfolio performance.

If we consider capital, V, invested in a savings account, and
calculate the growth of the capital over time:

    V(t) = V(t - 1)(1 + a(t)) .......................(1.1)

where a(t) is the interest rate at time t, (usually a constant[1].)
In equities, a(t) is not constant, and varies, perhaps being negative
at certain times, (meaning that the value of the equity decreased.)
This fluctuation in an equity's value can be represented by modifying
a(t) in Equation 1.1:

    a(t)  = f(t) * F(T) .............................(1.2)

where the product f * F is the fluctuation in the equity's value at
time t.  An equity's value, over time, is similar to a simple tossed
coin game [Sch91, pp. 128], where f(t) is the fraction of a gambler's
capital wagered on a toss of the coin, at time t, and F(t) is a random
variable[2], signifying whether the game was a win, or a loss, ie.,
whether the gambler's capital increased or decreased, and by how much.
The amount the gambler's capital increased or decreased is f(t) *
F(t).

In general, F(t) is a function of a random variable, with an average,
over time, of avgf, and a root mean square value, rmsf, of unity.
Note that for simple, time invariant, compound interest, F(t) has an
average and root mean square, both being unity, and f(t) is simply the
interest rate, which is assumed to be constant. For a simple, single
coin game, F(t) is a fixed increment, (ie., either +1 or -1,) random
generator.  From an analytical perspective, it would be advantageous
to measure the the statistical characteristics of the generator.
Substituting Equation 1.2 into Equation 1.1[3]:


    V(t) = V(t - 1)(1 + f(t) * F(t)) ...............(1.3)

and subtracting V(t - 1) from both sides:


    V(t) - V(t - 1) = V(t - 1) (1 + f(t) * F(t)) -

    V(t - 1) .......................................(1.4)

and dividing both sides by V(t - 1):

    V(t) - V(t - 1)
    --------------- =
        V(t - 1)

    V(t - 1) (1 + f(t) * F(t)) - V(t - 1)
    ------------------------------------- ..........(1.5)
                 V(t - 1)

and combining:

    V(t) - V(t - 1)
    --------------- =
        V(t - 1)

    (1 + f(t) * F(t) ) - 1 = f(t) * F(t) ...........(1.6)

We now have a "prescription," or process, for calculating the
characteristics of the random process that determines an equity's
price, over time.  That process is, for each unit of time, subtract
the value of the equity at the previous time from the value of the
equity at the current time, and divide this by the value of the equity
at the previous time. The root mean square[4] of these values are the
root mean square value of the random process.  The average of these
values are the average of the random process, avgf.  The root mean
square of these values can be calculated by any convenient means, and
will be represented by rms. The average of these values can be found
by any convenient means, and will be represented by avg[5].
Therefore, if f(t) = f, and assuming that it does not vary over time:

    rms = f ........................................(1.7)

which, if there are sufficiently many samples, is a metric of the
equity's price "volatility," and:


    avg = f * F(t) .................................(1.8)

and if there are sufficiently many samples, the average of F(t) is
simply avgf, or:

    avg = f * avgf .................................(1.9)

which is a metric on the equity's rate of "growth." Note that this is
the "effective" compound interest rate from Equation 1.1.  Equations
1.7 and 1.9 are important equations, since they can be used in
portfolio management.  For example, Equation 1.7 states that portfolio
volatility is calculated as the root mean square of the individual
volatility of the equities in the portfolio.  Equation 1.9 states that
the growths of the equity prices add together linearly[6] in the
portfolio.  Dividing Equation 1.9 by Equation 1.7 results in the two
f's canceling, or:

    avg
    --- = avgf ....................................(1.10)
    rms

There may be analytical advantages to "model" F(t) as a simple tossed
coin game, (either played with a single coin, or multiple coins, ie.,
many coins played at one time, or a single coin played many times[7].)
The number of wins minus the number of losses, in many iterations of a
single coin tossing game would be:

    P - (1 - P) = 2P - 1 ..........................(1.11)

where P is the probability of a win for the tossed coin.  (This
probability is traditionally termed, the "Shannon probability" of a
win.) Note that from the definition of F(t) above, that P = avgf. For
a fair coin, (ie., one that comes up with a win 50% of the time,) P =
0.5, and there is no advantage, in the long run, to playing the game.
However, if P > 0.5, then the optimal fraction of capital wagered on
each iteration of the single coin tossing game, f, would be 2P - 1.
Note that if multiple coins were used for each iteration of the game,
we would expect that the volatility of the gambler's capital to
increase as the square root of the number of coins used, and the
growth to increase linearly with the number of coins used,
irregardless of whether many coins were tossed at once, or one coin
was tossed many times, (ie., our random generator, F(t) would assume a
binomial distribution and if the number of coins was very large, then
F(t) would assume, essentially, a Gaussian distribution.)  Many
equities have a Gaussian distribution for the random process, F(t).
It may be advantageous to determine the Shannon probability to analyze
equity investment strategies.  From Equation 1.10:

    avg
    --- = avgf = 2P - 1 ...........................(1.12)
    rms

or:

    avg
    --- + 1 = 2P ..................................(1.13)
    rms

and:

        avg
        --- + 1
        rms
    P = ------- ...................................(1.14)
           2

where only the average and root mean square of the normalized
increments need to be measured, using the "prescription" or process
outlined above.

Interestingly, what Equation 1.12 states is that the "best" equity
investment is not, necessarily, the equity that has the largest
average growth, avgf.  The best equity investment is the equity that
has the largest growth, while simultaneously having the smallest
volatility.  In point of fact, the optimal decision criteria is to
choose the equity that has the largest ratio of growth to volatility,
where the volatility is measured by computing the root mean square of
the normalized increments, and the growth is computed by averaging the
normalized increments.

MARKET

We now have a "first order prescription" that enables us to analyze
fluctuations in equity values, although we have not explained why
equity values fluctuate the way they do.  For a formal presentation on
the subject, see the bibliography in [Art95] which, also, offers
non-mathematical insight into the subject.

Consider a very simple equity market, with only two people holding
equities. Equity value "arbitration" (ie., how equity values are
determined,) is handled by one person posting (to a bulletin board,) a
willingness to sell a given number of equities at a given price, to
the other person.  There is no other communication between the two
people. If the other person buys the equity, then that is the value of
the equity at that time.  Obviously, the other person will not buy the
equity if the price posted is too high-even if ownership of the equity
is desired.  For example, the other person could simply decide to wait
in hopes that a favorable price will be offered in the future.  What
this means is that the seller must consider not only the behavior of
the other person, but what the other person thinks the seller's
behavior will be, ie., the seller must base the pricing strategy on
the seller's pricing strategy. Such convoluted logical processes are
termed "self referential," and the implication is that the market can
never operate in a consistent fashion that can be the subject of
deductive analysis [Pen89, pp. 101][8].  As pointed out by [Art95,
Abstract], these types of indeterminacies pervade economics[9].  What
the two players do, in absence of a deductively consistent and
complete theory of the market, is to rely on inductive reasoning. They
form subjective expectations or hypotheses about how the market
operates.  These expectations and hypothesis are constantly formulated
and changed, in a world that forms from others' subjective
expectations. What this means is that equity values will fluctuate as
the expectations and hypothesis concerning the future of equity values
change[10]. The fluctuations created by these indeterminacies in the
equity market are represented by the term f(t) * F(t) in Equation 1.3,
and since there are many such indeterminacies, we would anticipate
F(t) to have a Gaussian distribution.  This is a rather interesting
conclusion, since analyzing the aggregate actions of many "agents,"
each operating on subjective hypothesis in a market that is
deductively indeterminate, can result in a system that can not only be
analyzed, but optimized.

OPTIMIZATION

The only remaining derivation is to show that the optimal wagering
strategy is, as cited above:

    f = rms = 2P - 1 ..............................(1.15)

where f is the fraction of a gambler's capital wagered on each toss of
a coin that has a Shannon probability, P, of winning.  Following
[Rez94, pp. 450], consider that the gambler has a private wire into
the future who places wagers on the outcomes of a game of chance.  We
assume that the side information which he receives has a probability,
P, of being true, and of 1 - P, of being false.  Let the original
capital of gambler be V(0), and V(n) his capital after the n'th wager.
Since the gambler is not certain that the side information is entirely
reliable, he places only a fraction, f, of his capital on each wager.
Thus, subsequent to n many wagers, assuming the independence of
successive tips from the future, his capital is:

                   w        l
    V(n)  = (1 + f)  (1 - f) V (0) ................(1.16)

where w is the number of times he won, and l = n - w, the number of
times he lost. These numbers are, in general, values taken by two
random variables, denoted by W and L. According to the law of large
numbers:

                  1
    lim           - W = P .........................(1.17)
    n -> infinity n


                  1
    lim           - L = q = 1 - P .................(1.18)
    n -> infinity n

The problem with which the gambler is faced is the determination of f
leading to the maximum of the average exponential rate of growth of
his capital. That is, he wishes to maximize the value of:

                      1    V(n)
    G = lim           - ln ---- ...................(1.19)
        n -> infinity n    V(0)

with respect to f, assuming a fixed original capital and specified P:

                      W              L
    G = lim           - ln (1 + f) + - ln (1 - f) .(1.20)
        n -> infinity n              n

or:


    G = P ln (1 + f) + q ln (1 - f) ...............(1.21)

which, by taking the derivative with respect to f, and equating to
zero, can be shown to have a maxima when:

    dG           P - 1        1 - P
    -- = P(1 + f)      (1 - f)      -
    df

                  1 - P - 1
    (1 - P)(1 - f)          (1 + f)P = 0 ..........(1.22)

combining terms:


                P - 1        1 - P
    0 = P(1 + f)      (1 - f)      -

                  P         P
    (1 - P)(1 - f)  (1 + f )  .....................(1.23)

and splitting:

            P - 1        1 - P
    P(1 + f)      (1 - f)      =

                  P        P
    (1 - P)(1 - f)  (1 + f)  ......................(1.24)

then taking the logarithm of both sides:

    ln (P) + (P - 1) ln (1 + f) + (1 - P) ln (1 - f) =

    ln (1 - P) - P ln (1 - f) + P ln (1 + f) ......(1.25)

and combining terms:

    (P - 1) ln (1 + f) - P ln (1 + f) +

    (1 - P) ln (1 - f) + P ln (1 - f) =

    ln (1 - P) - ln (P) ...........................(1.26)

or:

    ln (1 - f) - ln (1 + f) =

    ln (1 - P)  - ln (P)...........................(1.27)

and performing the logarithmic operations:

       1 - f      1 - P
    ln ----- = ln ----- ...........................(1.28)
       1 + f        P

and exponentiating:

    1 - f   1 - P
    ----- = ----- .................................(1.29)
    1 + f     P

which reduces to:

    P(1 - f) = (1 - P)(1 + f) .....................(1.30)

and expanding:

    P - Pf = 1 - Pf - P + f .......................(1.31)

or:

    P = 1 - P + f .................................(1.32)

and, finally:

    f = 2P - 1 ....................................(1.33)

FIXED INCREMENT FRACTAL

It was mentioned that it would be useful to model equity prices as a
fixed increment fractal, ie., an unfair tossed coin game.

As above, consider a gambler, wagering on the iterated outcomes of an
unfair tossed coin game. A fraction, f, of the gambler's capital will
be wagered on the outcome of each iteration of the unfair tossed coin,
and if the coin comes up heads, with a probability, P, then the
gambler wins the iteration, (and an amount equal to the wager is added
to the gambler's capital,) and if the coin comes up tails, with a
probability of 1 - P, then the gambler looses the iteration, (and an
amount of the wager is subtracted from the gambler's capital.)

If we let the outcome of the first coin toss, (ie., whether it came up
as a win or a loss,) be c(1) and the outcome of the second toss be
c(2), and so on, then the outcome of the n'th toss, c(n), would be:

           [win, with a probability of P
    c(n) = [
           [loose, with a probability of 1 - P

for convenience, let a win to be represented by +1, and a loss by -1:

           [+1, with a probability of P
    c(n) = [
           [-1, with a probability of 1 - P

for the reason that when we multiply the wager, f, by c(n), it will be
a positive number, (ie., the wager will be added to the capital,) and
for a loss, it will be a negative number, (ie., the wager will be
subtracted from the capital.)  This is convenient, since the
increment, by with the gambler's capital increased or decreased in the
n'th iteration of the game is f * c(n).

If we let C(0) be the initial value of the gambler's capital, C(1) be
the value of the gambler's capital after the first iteration of the
game, then:

    C(1) = C(0) * (1 + c(1) * f(1)) ...............(1.34)

after the first iteration of the game, and:

    C(2) = C(0) * ((1 + c(1) * f(1)) *

    (1 + c(2) * f(2)))  ...........................(1.35)

after the second iteration of the game, and, in general, after the
n'th iteration of the game:

    C(n) = C(0) * ((1 + c(1) * f(1)) *

    (1 + c(2) * f(2)) * ...
    * (1 + c(n) * f(n)) *
    (1 + c(n + 1) * f(n + 1))) ....................(1.36)

For the normalized increments of the time series of the gambler's
capital, it would be convenient to rearrange these formulas. For the
first iteration of the game:

    C(1) - C(0) = C(0) * (1 + c(1) * f(1)) - C(0) .(1.37)

or

    C(1) - C(0)   C(0) * (1 + c(1) * f(1)) - C(0)
    ----------- = ------------------------------- .(1.38)
       C(0)                   C(0)

and after reducing, the first normalized increment of the gambler's
capital time series is:

    C(1) - C(0)
    ----------- = (1 + c(1) * f(1)) - 1
       C(0)

                = c(1) * f(1) .....................(1.39)

and for the second iteration of the game:

    C(2) = C(0) * ((1 + c(1) * f(1)) *

    (1 + c(2) * f(2))) ............................(1.40)

but C(0) * ((1 + c(1) * f(1)) is simply C(1):

    C(2) = C(1) * (1 + c(2) * f(2)) ...............(1.41)

or:

    C(2) - C(1) = C(1) * (1 + c(2) * f(2)) - C(1) .(1.42)

which is:

    C(2) - C(1)   C(1) * (1 + c(2) * f(2)) - C(1)
    ----------- = ------------------------------- .(1.43)
       C(1)                    C(1)

and after reducing, the second normalized increment of the gambler's
capital time series is:

    C(2) - C(1)
    ----------- = 1 + c(2) * f(2)) - 1
       C(1)

                = c(2) * f(2) .....................(1.44)

and it should be obvious that the process can be repeated
indefinitely, so, the n'th normalized increment of the gambler's
capital time series is:

    C(n) - C(n - 1)
    --------------- = c(n) * f(n) .................(1.45)
         C(n)

which is equation (1.6).

DATA SET REQUIREMENTS

One of the implications of considering equity prices to have fractal
characteristics, ie., random walk or Brownian motion, is that future
prices can not be predicted from past equity price performance. The
Shannon probability of a equity price time series is the likelihood
that a equity price will increase in the next time interval. It is
typically 0.51, on a day to day bases, (although, occasionally, it
will be as high as 0.6) What this means, for a typical equity, is that
51% of the time, a equity's price will increase, and 49% of the time
it will decrease-and there is no possibility of determining which will
occur-only the probability.

However, another implication of considering equity prices to have
fractal characteristics is that there are statistical optimizations to
maximize portfolio performance. The Shannon probability, P, is related
to the optimal volatility of a equity's price, (measured as the root
mean square of the normalized increments of the equity's price time
series,) rms, by rms = 2P - 1. Also, the optimized average of the
normalized increments is the growth in the equity's price, and is
equal to the square of the rms. Unfortunately, the measurements of avg
and rms must be made over a long period of time, to construct a very
large data set for analytical purposes do to the necessary accuracy
requirements. Statistical estimation techniques are usually employed
to quantitatively determine the size of the data set for a given
analytical accuracy.

The calculation of the Shannon probability, P, from the average and
root mean square of the normalized increments, avg and rms,
respectively, will require require specialized filtering, (to "weight"
the most recent instantaneous Shannon probability more than the least
recent,) and statistical estimation (to determine the accuracy of the
measurement of the Shannon probability.)

This measurement would be based on the normalized increments, as
derived in Equation (1.6):

    V(t) - V(t - 1)
    ---------------
       V(t - 1)

which, when averaged over a "sufficiently large" number of increments,
is the mean of the normalized increments, avg. The term "sufficiently
large" must be analyzed quantitatively. For example, the following
table is the statistical estimate for a Shannon probability, P, of a
time series, vs, the number of records required, based on a mean of
the normalized increments = 0.04:

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
filter [Con78], with the pole frequency at 0.00045 times the time
series sampling frequency. The accuracy of the approximation is about
+/- 10% for the first 260 samples, with the approximation accuracy
prediction becoming optimistic thereafter, ie., about +30%.

A pole frequency of 0.033 seems a good approximation for working with
the root mean square of the normalized increments, with a reasonable
approximation to about 5-10 time units.

The "instantaneous," weighted, and statistically estimated Shannon
probability, P, can be determined by dividing the filtered rms by the
filtered avg, adding unity, and dividing by two, as in Equation
(1.14).

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

Footnotes:

[1] For example, if a = 0.06, or 6%, then at the end of the first time
interval the capital would have increased to 1.06 times its initial
value.  At the end of the second time interval it would be (1.06 *
1.06), and so on.  What Equation 1.1 states is that the way to get the
value, V in the next time interval is to multiply the current value by
1.06. Equation 1.1 is nothing more than a "prescription," or a process
to make an exponential, or "compound interest" mechanism. In general,
exponentials can always be constructed by multiplying the current
value of the exponential by a constant, to get the next value, which
in turn, would be multiplied by the same constant to get the next
value, and so on.  Equation 1.1 is a construction of V (t) = exp(kt)
where k = ln(1 + a). The advantage of representing exponentials by the
"prescription" defined in Equation 1.1 is analytical expediency. For
example, if you have data that is an exponential, the parameters, or
constants, in Equation 1.1 can be determined by simply reversing the
"prescription," ie., subtracting the previous value, (at time t - 1,)
from the current value, and dividing by the previous value would give
the exponentiating constant, (1 + at). This process of reversing the
"prescription" is termed calculating the "normalized increments."
(Increments are simply the difference between two values in the
exponential, and normalized increments are this difference divided by
the value of the exponential.) Naturally, since one usually has many
data points over a time interval, the values can be averaged for
better precision-there is a large mathematical infrastructure
dedicated to these types of precision enhancements, for example, least
squares approximation to the normalized increments, and statistical
estimation.

[2] "Random variable" means that the process, F(t), is random in
nature, ie., there is no possibility of determining what the next
value will be. However, F can be analyzed using statistical methods
[Fed88, pp. 163], [Sch91, pp. 128]. For example, F typically has a
Gaussian distribution for equity prices [Cro95, pp. 249], in which
case the it is termed a "fractional Brownian motion," or simply a
"fractal" process. In the case of a single tossed coin, it is termed
"fixed increment fractal," "Brownian," or "random walk" process.  The
determination of the statistical characteristics of F(t) are the
essence of analysis. Fortunately, there is a large mathematical
infrastructure dedicated to the subject. For example, F could be
verified as having a Gaussian distribution using, perhaps, Chi-Square
techniques. Frequently, it is convenient, from an analytical
standpoint, to "model" F using a mathematically simpler process
[Sch91, pp. 128]. For example, multiple iterations of tossing a coin
can be used to approximate a Gaussian distribution, since the
distribution of many tosses of a coin is binomial-which if the number
of coins tossed is sufficient will represent a Gaussian distribution
to any required precision [Sch91, pp. 144], [Fed88, pp. 154].

[3] Equation 1.3 is interesting in many other respects.  For example,
adding a single term, m * V(t - 1), to the equation results in V(t) =
v(t - 1) (1 + f(t) * F(t) + m * V(t - 1)) which is the "logistic," or
'S' curve equation,(formally termed the "discreet time quadratic
equation,") and has been used successfully in many unrelated fields
such as manufacturing operations, market and economic forecasting, and
analyzing disease epidemics [Mod92, pp. 131]. There is continuing
research into the application of an additional "non-linear" term in
Equation 1.3 to model equity value non-linearities. Although there
have been modest successes, to date, the successes have not proven to
be exploitable in a systematic fashion [Pet91, pp. 133]. The reason
for the interest is that the logistic equation can exhibit a wide
variety of behaviors, among them, "chaotic." Interestingly, chaotic
behavior is mechanistic, but not "long term" predictable into the
future. A good example of such a system is the weather. It is an
important concept that compound interest, the logistic function, and
fractals are all closely related.

[4] In this section, "root mean square" is used to mean the variance
of the normalized increments. In Brownian motion fractals, this is
computed by sigmatotal^2 = sigma1^2 + sigma2^2 ... However, in many
fractals, the variances are not calculated by adding the squares,
(ie., a power of 2,) of the values-the power may be "fractional," ie.,
3 / 2 instead of 2, for example [Sch91, pp. 130], [Fed88, pp.
178]. However, as a first order approximation, the variances of the
normalized increments of equity prices can be added root mean square
[Cro95, kpp. 250]. The so called "Hurst" coefficient determines the
process to be used.  The Hurst coefficient is range of the equity
values over a time interval, divided by the standard deviation of the
values over the interval, and its determination is commonly called "R
/ S" analysis. As pointed out in [Sch91, pp. 157] the errors committed
in such simplified assumptions can be significant-however, for
analysis of equities, squaring the variances seems to be a reasonably
accurate simplification.

[5] For example, many calculators have averaging and root mean square
functionality, as do many spreadsheet programs-additionally, there are
computer source codes available for both.  See the programs tsrms and
tsavg.  The method used is not consequential.

[6] There are significant implications do to the fact that equity
volatilities are calculated root mean square.  For example, if capital
is invested in N many equities, concurrently, then the volatility of
the capital will be rms / sqrt (N) of an individual equity's
volatility, rms, provided all the equites have similar statistical
characteristics. But the growth in the capital will be unaffected,
ie., it would be statistically similar to investing all the capital in
only one equity. What this means is that capital, or portfolio,
volatility can be minimized without effecting portfolio growth-ie.,
volatility risk can addressed.  There are further applications.  For
example, Equation 1.6 could be modified by dividing both the
normalized increments, and the square of the normalized increments by
the daily trading volume.  The quotient of the normalized increments
divided by the trading volume is the instantaneous growth, avg, of the
equity, on a per-share basis.  Likewise, the square root of the square
of the normalized increments divided by the daily trading volume is
the instantaneous root mean square, rmsf, of the equity on a per-share
basis, ie., its instantaneous volatility of the equity.  (Note that
these instantaneous values are the statistical characteristics of the
equity on a per-share bases, similar to a coin toss, and not on time.)
Additionally, it can be shown that the range-the maximum minus the
minimum-of an equity's value over a time interval will increase with
the square root of of the size of the interval of time [Fed88,
pp. 178]. Also, it can be shown that the number of expected equity
value "high and low" transitions scales with the square root of time,
meaning that the probability of an equity value "high or low"
exceeding a given time interval is proportional to the square root of
the time interval [Schroder, pp. 153].

[7] Here the "model" is to consider two black boxes, one with an
equity "ticker" in it, and the other with a casino game of a tossed
coin in it. One could then either invest in the equity, or,
alternatively, invest in the tossed coin game by buying many casino
chips, which constitutes the starting capital for the tossed coin
game.  Later, either the equity is sold, or the chips "cashed in." If
the statistics of the equity value over time is similar to the
statistics of the coin game's capital, over time, then there is no way
to determine which box has the equity, or the tossed coin game. The
advantage of this model is that gambling games, such as the tossed
coin, have a large analytical infrastructure, which, if the two black
boxes are statistically the same, can be used in the analysis of
equities.  The concept is that if the value of the equity, over time,
is statistically similar to the coin game's capital, over time, then
the analysis of the coin game can be used on equity values.  Note that
in the case of the equity, the terms in f(t) * F(t) can not be
separated. In this case, f = rms is the fraction of the equity's
value, at any time, that is "at risk," of being lost, ie., this is the
portion of a equity's value that is to be "risk managed."  This is
usually addressed through probabilistic methods, as outlined below in
the discussion of Shannon probabilities, where an optimal wagering
strategy is determined. In the case of the tossed coin game, the
optimal wagering strategy is to bet a fraction of the capital that is
equal to f = rms = 2P - 1 [Sch91, pp. 128, 151], where P is the
Shannon probability. In the case of the equity, since f = rms is not
subject to manipulation, the strategy is to select equities that
closely approximate this optimization, and the equity's value, over
time, on the average, would increase in a similar fashion to the coin
game.  As another alternative, various equities can be invested in
concurrently to exercise control over portfolio volatility. The growth
of either investment would be equal to avg = rms^2, on average, for
each iteration of the coin game, or time unit of equity/portfolio
investment. This is an interesting concept from risk management since
it maximizes the gain in the capital, while, simultaneously,
minimizing risk exposure to the capital.

[8] Penrose, referencing Russell's paradox, presents a very good
example of logical contradiction in a self-referential system.
Consider a library of books. The librarian notes that some books in
the library contain their titles, and some do not, and wants to add
two index books to the library, labeled "A" and "B," respectively; the
"A" book will contain the list of all of the titles of books in the
library that contain their titles; and the "B" book will contain the
list of all of the titles of the books in the library that do not
contain their titles.  Now, clearly, all book titles will go into
either the "A" book, or the "B" book, respectively, depending on
whether it contains its title, or not. Now, consider in which book,
the "A" book or the "B" book, the title of the "B" book is going to be
placed-no matter which book the title is placed, it will be
contradictory with the rules. And, if you leave it out, the two books
will be incomplete.

[9] [Art95] cites the "El Farol Bar" problem as an example. Assume one
hundred people must decide independently each week whether go to the
bar. The rule is that if a person predicts that more than, say, 60
will attend, it will be too crowded, and the person will stay home; if
less than 60 is predicted, the person will go to the bar. As trivial
as this seems, it destroys the possibility of long-run shared,
rational expectations.  If all believe few will go, then all will go,
thus invalidating the expectations. And, if all believe many will go,
then none will go, likewise invalidating those expectations.
Predictions of how many will attend depend on others' predictions, and
others' predictions of others' predictions. Once again, there is no
rational means to arrive at deduced a-priori predictions. The
important concept is that expectation formation is a self-referential
process in systems involving many agents with incomplete information
about the future behavior of the other agents. The problem of
logically forming expectations then becomes ill-defined, and rational
deduction, can not be consistent or complete. This indeterminacy of
expectation-formation is by no means an anomaly within the real
economy. On the contrary, it pervades all of economics and game
theory.

[10] Interestingly, the system described is a stable system, ie., if
the players have a hypothesis that changing equity positions may be of
benefit, then the equity values will fluctuate-a self fulfilling
prophecy.  Not all such systems are stable, however.  Suppose that one
or both players suddenly discover that equity values can be "timed,"
ie., there are certain times when equities can be purchased, and
chances are that the equity values will increase in the very near
future. This means that at certain times, the equites would have more
value, which would soon be arbitrated away. Such a scenario would not
be stable.

Bibliography:

[Art95] W. Brian Arthur.  "Complexity in Economic and Financial
Markets."  Complexity, 1, pp. 20-25, 1995.  Also available from
http://www.santafe.edu/arthur, February 1995.

[BdL95] William A. Brock and Pedro J. F. de Lima. "Nonlinear time
series, complexity theory, and finance." To appear in "Handbook of
Statistics Volume 14: Statistical Methods in Finance," edited by
G. Maddala and C. Rao. New York: North Holland, forthcoming. Also
available from http://www.santafe.edu/sfi/publications, March 1995.

[Cas90] John L. Casti. "Searching for Certainty." William Morrow, New
York, New York, 1990.

[Cas94] John L. Casti. "Complexification." HarperCollins, New York,
New York, 1994.

[Con78] John Conover. "An analog, discrete time, single pole filter."
Fairchild Journal of Semiconductor Progress, 6(4), July/August 1978.

[Cro95] Richard M. Crownover.  "Introduction to Fractals and Chaos."
Jones and Bartlett Publishers International, London, England, 1995.

[Fed88] Jens Feder. "Fractals." Plenum Press, New York, New York,
1988.

[Mod92] Theodore Modis. "Predictions." Simon & Schuster, New York, New
York, 1992.

[Pen89] Roger Penrose. "The Emperor's New Mind." Oxford University
Press, New York, New York, 1989.

[Pet91] Edgar E. Peters.  "Chaos and Order in the Capital Markets."
John Wiley & Sons, New York, New York, 1991.

[Rez94] Fazlollah M. Reza.  "An Introduction to Information Theory."
Dover Publications, New York, New York, 1994.

[Sch91] Manfred Schroeder. "Fractals, Chaos, Power Laws."
W. H. Freeman and Company, New York, New York, 1991.

$Revision: 0.0 $
$Date: 2006/01/18 20:28:55 $
$Id: tstrade.c,v 0.0 2006/01/18 20:28:55 john Exp $
$Log: tstrade.c,v $
Revision 0.0  2006/01/18 20:28:55  john
Initial version


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <unistd.h>

#ifdef __STDC__

#include <float.h>

#else

#include <malloc.h>

#endif

#ifndef PI /* make sure PI is defined */

#define PI 3.141592653589793 /* pi to 15 decimal places as per CRC handbook */

#endif

static char rcsid[] = "$Id: tstrade.c,v 0.0 2006/01/18 20:28:55 john Exp $"; /* program version */
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
    "Optimal trade of multiple concurrent stock investments\n",
    "Usage: tstrade [-a a] [-D D] [-d 1|2|3|4] [-I] [-i i] [-n n]\n",
    "               [-p p] [-R R] [-r r] [-s] [-t] [-u] [-V] [-v]\n",
    "               [filename]\n",
    "    -a a, pole frequency for the average of the normalized increments,\n",
    "          avg, of a stock's time series\n",
    "    -D D, minimum decision criteria for investment in a stock, ie., the\n",
    "          minimum value of RMS * (avg / rms), RMS * rms, avg, or randomly\n",
    "    -d 1|2|3|4, decision method for investment in a stock:\n",
    "        -d 1: RMS * (avg / rms), P = ((avg / rms) + 1) / 2\n",
    "        -d 2: RMS * rms, P = (rms + 1) / 2\n",
    "        -d 3: avg, P = (sqrt (avg) + 1) / 2\n",
    "        -d 4: randomly, P = ((avg / rms) + 1) / 2\n",
    "    -I, print the average index of all stocks in the output time series\n",
    "    -i i, initial capital\n",
    "    -n n, maximum number of stocks to invest in concurrently\n",
    "    -p p, minimum Shannon probability, P, for investment in a stock\n",
    "    -R R, pole frequency for the root mean square of the normalized\n",
    "          increments, RMS, of a stock's time series\n",
    "    -r r, pole frequency for the root mean square of the normalized\n",
    "          increments, rms, of a stock's time series\n",
    "    -s, print the names of stocks held in the output time series\n",
    "    -t, print the time stamps in the output time series\n",
    "    -u, reverse the sense of the decision criteria\n",
    "    -V, compute Shannon probability, P, based on trading volumes\n",
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
    "Error allocating memory\n",
    "Error hash table already initialized\n",
    "Error duplicate key when inserting key ino hash table\n",
    "Error hash table mkhash () failure\n",
    "Error hash table key not found\n"
};

#define NOERROR 0 /* error values, one for each index in the error message array */
#define EARGS 1 /* command line argument error */
#define EOPEN 2 /* error opening file */
#define ECLOSE 3 /* error closing file */
#define EALLOC 4 /* error allocating memory */
#define HASH_INI_ERR 5 /* hash table already initialized */
#define HASH_DUP_ERR 6 /* duplicate key when inserting key into hash table */
#define HASH_MK_ERR 7 /* hash table mkhash () failure */
#define HASH_KEY_ERR 8 /* hash table key not found */

typedef struct hash_struct /* hash structure for each stock */
{
    struct hash_struct *previous; /* reference to next element in hash's doubly, circular linked list */
    struct hash_struct *next; /* reference to previous element in hash's doubly, circular linked list */
    struct hash_struct *next_decision; /* reference to next element in qsortlist ()'s sort of the decision criteria list */
    struct hash_struct *next_investment; /* reference to next element in invested list */
    char *hash_data;  /* stock tick identifier, which is the hash key element */
    int suspend, /* suspend transations on this stock flag, 0 = no, 1 = yes */
        transactions; /* number of changes in this stock's value */
    double currentvalue, /* current value of stock */
           lastvalue, /* last value of stock */
           capital, /* amount of capital invested in the stock */
           fraction, /* normalized increment of the stock's value */
           avgfilter, /* filtered value of average of the normalized increments, avg */
           rmsfilter, /* filtered value of the root mean square of the normalized increments, rms */
           RMSfilter, /* filtered value of the root mean square of the normalized increments, RMS */
           decision, /* decision criteria for investment in a stock, one of enum decision_method */
           P; /* stock's Shannon probability */
} HASH;

#ifdef __STDC__

typedef struct hashtable_struct /* hash table descriptor */
{
    size_t hash_size; /* size of hash table */
    HASH *table; /* reference to hash table index */
    HASH *(*mkhash) (void *data); /* reference to the function that allocates hash elements */
    int (*cmphash) (void *data, struct hash_struct *element); /* reference to the function that compares element's keys */
    void (*rmhash) (struct hash_struct *element); /* reference to the function that deallocates hash elements */
    int (*comphash) (struct hashtable_struct *hash_table, void *key); /* reference to the function that computes the hash value */
} HASHTABLE;

#else

typedef struct hashtable_struct /* hash table descriptor */
{
    size_t hash_size; /* size of hash table */
    HASH *table; /* reference to hash table */
    HASH *(*mkhash) (); /* reference to the function that allocates data and hash elements */
    int (*cmphash) (); /* reference to the function that compares element's keys */
    void (*rmhash) (); /* reference to the function that deallocates data hash elements */
    int (*comphash) (); /* reference to the function that computes the hash value */
} HASHTABLE;

#endif

static int hash_error = 0; /* hash success/failure error code */

typedef HASH LIST; /* struct HASH is also a decision list for qsortlist () elements, and investment list */

typedef LIST *list; /* reference to HASH/LIST structure for qsortlist () */

#define element_comp(x,y) ((u == 0) ? (y)->decision - (x)->decision : (x)->decision - (y)->decision) /* comparison for decision criteria for investment in a stock, sorted by qsortlist (), in decreasing order of the value of the decision element in the HASH structs */

HASH *decision_list = (HASH *) 0; /* list of decision criteria for investment in a stock */

HASH *invested_list = (HASH *) 0; /* reference to invested list-stocks that have investments in them are in this list */

#define PUSH(x,y) (y)->next_decision=(x);(x)=(y) /* method to push a HASH element on the decision criteria list, this pushes a HASH struct for sorting by qsortlist () */

#define PUSHINVESTMENT(x,y) (y)->next_investment=(x);(x)=(y) /* method to push a HASH element on the investment list */

#define POPINVESTMENT(x) (x);(x)=(x)->next_investment /* method to pop a HASH element from the investment list */

enum decision_method /* method of decision criteria for investment in a stock */
{
    M_AVGRMS, /* RMS * (avg / rms) */
    M_RMS, /* RMS */
    M_AVG, /* avg */
    M_RANDOM /* randomly */
};

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static HASH *get_stock (HASHTABLE *stock_table, void *name); /* get a stock from the hash table */
static void shannon_probability (HASH *stock, double minimum, double maximum, double currentvalue, double volume, int volume_flag, enum decision_method method); /* Shannon probability calculation */
static void statistical_filter (HASH *stock, double avgfraction, double rmsfraction); /* filter normalized increments */
static void invest (int maximum_n, double minimum_P, double minimum_decision, int verboseprint, char *time_stamp, int timeprint, int indexprint); /* invest in the stocks */
static int strtoken (char *string, char *parse_array, char **parse, const char *delim); /* parse a record based on sequential delimiters */
static int hash_init (HASHTABLE *hash_table); /* initialize the hash table */
static int hash_insert (HASHTABLE *hash_table, void *data); /* insert a key and data into the hash table */
static HASH *hash_find (HASHTABLE *hash_table, void *data); /* find data in the hash table */
#ifdef HASH_DELETE
static int hash_delete (HASHTABLE *hash_table, void *data); /* delete data from the hash table */
#endif
static void hash_term (HASHTABLE *hash_table); /* remove a hash table */
static int hash_text (HASHTABLE *hash_table, void *key); /* compute the hash value for a text key */
static int text_cmphash (void *data, HASH *element); /* function to compare a text key with a hash table's element key */
static HASH *text_mkhash (void *data); /* function to allocate a text hash table element and data */
static void text_rmhash (HASH *element); /* function to deallocate a text hash table element and data */
static void qsortlist (list *top, list bottom); /* quick sort a linked list */
static double ran1 (int *idum); /* return a uniform random deviate between 0.0 and 1.0 */

#else

static void print_message (); /* print any error messages */
static HASH *get_stock (); /* get a stock from the hash table */
static void shannon_probability (); /* Shannon probability calculation */
static void statistical_filter (); /* filter normalized increments */
static void invest (); /* invest in the stocks */
static int strtoken ();  /* parse a record based on sequential delimiters */
static int hash_init ();  /* initialize the hash table */
static int hash_insert (); /* insert a key and data into the hash table */
static HASH *hash_find (); /* find data in the hash table */
#ifdef HASH_DELETE
static int hash_delete ();  /* delete data from the hash table */
#endif
static void hash_term ();  /* remove a hash table */
static int hash_text ();  /* compute the hash value for a text key */
static int text_cmphash (); /* function to compare a text key with a hash table's element key */
static HASH *text_mkhash (); /* function to allocate a text hash table element and data */
static void text_rmhash (); /* function to deallocate a text hash table element and data */
static void qsortlist ();  /* quick sort a linked list */
static double ran1 (); /* return a uniform random deviate between 0.0 and 1.0 */

#endif

static HASHTABLE text_table = {2729, (HASH *) 0, text_mkhash, text_cmphash, text_rmhash, hash_text}; /* declare the hash table descriptor for text keys */

static double pa = (double) 0.00045, /* avg pole frequency */
              pb = (double) 0.033, /* rms pole frequency */
              pc = (double) 0.033, /* RMS pole frequency */
              k1, /* coefficient k1 in the avg recursive filter */
              k2, /* coefficient k2 in the avg recursive filter */
              k3, /* coefficient k1 in the rms recursive filter */
              k4, /* coefficient k2 in the rms recursive filter */
              k5, /* coefficient k1 in the RMS recursive filter */
              k6; /* coefficient k2 in the RMS recursive filter */

static int stocks = 0; /* the number of stocks encoutered in the input file */

static double capital = (double) 1000; /* capital invested in all stocks */

static double average = (double) 1000; /* average index, computed on the initial capital invested in all stocks */

static int u = 0; /* reverse the decision criteria sense flag, 0 = no, 1 = yes */

static int idem = -1000; /* random number initialize flag */

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
         time_stamp[BUFLEN]; /* last time stamp, from the first column of the input file */

    int count = 0, /* input file record counter */
        retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        I = 0, /* print the average index in the time series flag, 0 = no, 1 = yes */
        maximum_n = 10, /* number of stocks to invest in concurrently */
        V = 0, /* calculate Shannon probability based on trading volume, 0 = no, 1 = yes */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        s = 0, /* print the amounts of the capital invested in stock(s) flag, 0 = no, 1 = yes */
        c; /* command line switch */

    double currentvalue, /* current value of stock */
           minimum, /* minumum value of stock in time interval */
           maximum, /* maximum value of stock in time interval */
           volume, /* trading volume of stock in time interval */
           minimum_P = (double) 0.5, /* stock's minumum Shannon probability for investment in the stock */
           minimum_decision = (double) 0.0; /* stock's minimum decision criteria for investment in the stock */

    FILE *infile = stdin; /* reference to input file */

    enum decision_method method = M_AVGRMS; /* method of decision criteria for investment in a stock */

    HASH *stock;  /* reference to hash table stock element */

    while ((c = getopt (argc, argv, "a:d:D:Ii:n:p:R:r:stuVv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'a': /* request for avg pole frequency? */

                pa = atof (optarg); /* yes, save the avg pole frequency */
                break;

            case 'D': /* request for stock's minimum decision criteria for investment in a stock */

                minimum_decision = atof (optarg); /* yes, save the stock's minimum decision criteria for investment in the stock */
                break;

            case 'd': /* request for method of decision criteria for investment in a stock */

                switch (atoi (optarg) - 1) /* yes, save the method of decision criteria for investment in a stock */
                {

                    case M_AVGRMS: /* RMS * (avg / rms) */

                        method = M_AVGRMS;
                        break;

                    case M_RMS: /* RMS */

                        method = M_RMS;
                        break;

                    case M_AVG: /* avg */

                        method = M_AVG;
                        break;

                    case M_RANDOM: /* randomly */

                        method = M_RANDOM;
                        break;

                    default: /* illegal switch? */

                        optind = argc; /* force argument error */
                        retval = EARGS; /* assume not enough arguments */
                        break;

                }

                break;

            case 'I': /* request for print the average index in the time series? */

                I = 1; /* yes, set the print the average index in the time series flag */
                break;

            case 'i': /* request for capital invested in all stocks? */

                capital = average = atof (optarg); /* yes, save the capital invested in all stocks */
                break;

            case 'n': /* request for number of stocks to invest in concurrently? */

                maximum_n = atoi (optarg); /* yes, save the number of stocks to invest in concurrently */
                break;

            case 'p': /* request for minumum Shannon probability for investment? */

                minimum_P = atof (optarg); /* yes, save the minumum Shannon probability for investment */
                break;

            case 'R': /* request for RMS pole frequency? */

                pc = atof (optarg); /* yes, save the RMS pole frequency */
                break;

            case 'r': /* request for rms pole frequency? */

                pb = atof (optarg); /* yes, save the rms pole frequency */
                break;

            case 's': /* request for print the amounts of the capital invested in stock(s) flag */

                s = 1; /* yes, set the print the amounts of the capital invested in stock(s) flag */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

            case 'u': /* request for reverse the decision criteria sense? */

                u = 1; /* yes, set the reverse the decision criteria sense flag */
                break;

            case 'V': /* request calculate Shannon probability based on trading volume? */

                V = 1; /* yes, set the calculate Shannon probability based on trading volume flag */
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
    k5 = exp (- ((double) 2.0 * (double) PI * pc)); /* calculate the coefficient k1 in the rms recursive filter */
    k6 = (double) 1.0 - k3; /* calculate the coefficient k2 in the rms recursive filter */

    if (retval == NOERROR)  /* any errors? */
    {

        if ((retval = hash_init (&text_table)) == NOERROR) /* initialize the hash table */
        {
            retval = EOPEN; /* assume error opening file */

            if ((infile = argc <= optind ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the stock's input file */
            {
                retval = NOERROR; /* assume no errors */

                while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the next record from the stock's input file */
                {

                    if ((fields = strtoken (buffer, parsebuffer, token, TOKEN_SEPARATORS)) != 0) /* parse the stock's record into fields, skip the record if there are no fields */
                    {

                        if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                        {

                            if (fields >= 6) /* 6 fields are required */
                            {
                                minimum = atof (token[2]);  /* save the minumum value of stock in time interval */
                                maximum = atof (token[3]); /* save the maximum value of stock in time interval */
                                currentvalue = atof (token[4]); /* save the current value of the stock */
                                volume = atof (token[5]); /* save the trading volume of stock in time interval */

                                if (minimum > (double) 0.0 && maximum > (double) 0.0 && currentvalue > (double) 0.0 && volume > (double) 0.0) /* a negative or zero value(s) makes no sense, add protection */
                                {

                                    if (count == 0) /* first record from the input file(s) */
                                    {
                                        (void) strcpy (time_stamp, token[0]); /* save the last time stamp, from the first column of the input file */
                                    }

                                    count ++; /* increment the count of records from the input file(s) */

                                    if ((stock = get_stock (&text_table, token[1])) != (HASH *) 0) /* get the stock from the hash table */
                                    {

                                        if (fields > 6 || stock->transactions == 0) /* the record for this stock contain a command, or is this the first transaction for this stock? */
                                        {
                                            stock->suspend = 1; /* yes, currently, the only valid command is to cease transactions on this stock */
                                        }

                                        else
                                        {
                                            stock->suspend = 0; /* reset the suspend transactions on this stock flag */
                                        }

                                        if (strcmp (time_stamp, token[0]) != 0) /* no, new time stamp, from the first column of the input file? */
                                        {
                                            invest (maximum_n, minimum_P, minimum_decision, s, time_stamp, t, I); /* arrange the investments */
                                            (void) strcpy (time_stamp, token[0]); /* save the new time stamp, from the first column of the input file */
                                        }

                                        shannon_probability (stock, minimum, maximum, currentvalue, volume, V, method); /* calculate the Shannon probability */
                                        stock->capital = stock->capital * ((double) 1.0 + stock->fraction); /* yes, adjust the capital to the win or loss */
                                        stock->transactions ++; /* increment the number of changes in this stock's value */
                                        average = average * ((double) 1.0 + (stock->fraction / (double) stocks)); /* calculate the average index */
                                    }

                                    else
                                    {
                                        retval = hash_error; /* couldn't get the stock from the hash table, set the error */
                                        break; /* couldn't get the stock from the hash table, stop reading records */
                                    }

                                }

                            }

                        }

                    }

                }

                if (count != 0) /* any records? */
                {
                    invest (maximum_n, minimum_P, minimum_decision, s, time_stamp, t, I); /* yes, arrange the investments for the last time stamp */
                }

                if (argc > optind) /* using stdin as input? */
                {

                    if (fclose (infile) == EOF) /* no, close the input file */
                    {
                        retval = ECLOSE; /* error closing file */
                    }

                }

            }

            hash_term (&text_table); /* terminate the hash table */
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

invest in the stocks

static void invest (int maximum_n, double minimum_P, double minimum_decision, int verboseprint, char *time_stamp, int timeprint, int indexprint);

*/

#ifdef __STDC__

static void invest (int maximum_n, double minimum_P, double minimum_decision, int verboseprint, char *time_stamp, int timeprint, int indexprint)

#else

static void invest (maximum_n, minimum_P, minimum_decision, verboseprint, time_stamp, timeprint, indexprint)
int maximum_n;
double minimum_P;
double minimum_decision;
int verboseprint;
char *time_stamp;
int timeprint;
int indexprint;

#endif

{
    int i, /* HASH struct counter */
        k; /* HASH struct counter */

    double investment; /* the amount invested in each stock */

    HASH *j; /* reference to HASH struct */

    while (invested_list != (HASH *) 0) /* withdraw all investments to rearrange them */
    {
        j = POPINVESTMENT(invested_list); /* pop the invested HASH element from the investment list */
        capital = capital + j->capital; /* add the investment to the capital invested in all stocks */
        j->capital = (double) 0.0;  /* zero the amount of capital invested in the stock */
        j->next_investment = 0; /* null the reference to next element in invested list */
    }

    qsortlist (&decision_list, (list) 0); /* sort the decision criteria list for investment in a stock */
    j = decision_list; /* reference the first element in the decision criteria list for investment in a stock */
    i = 0; /* no counted elements in the list of decision criteria for investment in a stock */

    while (j != (HASH *) 0 && i < maximum_n) /* count the elements in the list of decision criteria for investment in a stock, but not greater than n */
    {

        if (j->suspend == 0) /* suspend transactions on this stock flag set? */
        {

            if (j->decision <= minimum_decision) /* decision criteria for investment in a stock less than the minimum? */
            {
                break; /* yes, terminate early */
            }

            if (j->P <= minimum_P) /* Shannon probability of this stock less than minimum? */
            {
                break; /* yes, terminate early */
            }

            i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
        }

        j = j->next_decision; /* reference the next element in the decision criteria list */
    }

    if (i == 0) /* i = 0 means that there are no stocks to invest in */
    {
        investment = 0; /* ie., the investment is zero */
    }

    else
    {
        investment = capital / (double) i; /* calculate the amount of capital invested in each stock */
    }

    j = decision_list; /* reference the first element in the decision list, this element has the largest decision value */

    if (timeprint == 1) /* print time of samples flag set? */
    {
        (void) printf ("%s, ", time_stamp); /* yes, print the time stamp */
    }

    if (indexprint == 0) /* print the average index in the time series flag set? */
    {
        (void) printf ("%.2f", capital); /* no, start the print with the capital */
    }

    else
    {
        (void) printf ("%.2f, %.2f", average, capital); /* yes, start the print with the capital, and the average index */
    }

    k = 0; /* no counted elements in the list of decision criteria for investment in a stock */

    while (k < i) /* count the elements in the list of decision criteria for investment in a stock */
    {

        if (j->suspend == 0) /* suspend transactions on this stock flag set? */
        {

            if (verboseprint == 1) /* print the amounts of the capital invested in stock(s) flag set? */
            {
                (void) printf (", %s", j->hash_data); /* yes, print the stock, the amount invested is equal for all stocks */
            }

            j->capital = investment; /* set the amount of capital invested in the stock */
            capital = capital - investment; /* subtract the investment from the capital invested in all stocks */
            PUSHINVESTMENT(invested_list,j); /* push the HASH element on the investment list */
            k ++; /* one more counted elements in the list of decision criteria for investment in a stock */
        }

        j = j->next_decision; /* reference the next element in the decision criteria list */
    }

    (void) printf ("\n"); /* terminate this investment record */
}

/*

get a stock from the hash table

static HASH *get_stock (HASHTABLE *stock_table, void *name);

get the HASH struct for the stock identified by name-if it does not
exist, then create it

returns a reference to the stock HASH element, zero on error

*/

#ifdef __STDC__

static HASH *get_stock (HASHTABLE *stock_table, void *name)

#else

static HASH *get_stock (stock_table, name)
HASHTABLE *stock_table;
void *name;

#endif

{
    HASH *stock;  /* reference to hash table stock element */

    if ((stock = hash_find (stock_table, name)) == (HASH *) NOERROR) /* find a hash table element with the stock tick identifier in the hash table */
    {

        if (hash_insert (stock_table, name) == NOERROR) /* couldn't find it, add the stock tick identifier to the hash table */
        {

            if ((stock = hash_find (stock_table, name)) != (HASH *) NOERROR) /* find the hash table element with the stock tick identifier in the hash table */
            {
                stocks ++; /* increment the number of stocks encountered in the input file */
            }

            else
            {
                stock = (HASH *) 0;  /* couldn't find the hash table element with the stock tick identifier in the hash table, set the error */
            }

        }

        else
        {
            stock = (HASH *) 0; /* couldn't add the stock tick identifier to the hash table, set the error */
        }

    }

    return (stock); /* return a reference to the stock HASH element, zero on error */
}

/*

Shannon probability calculation

static void shannon_probability (HASH *stock, double minimum, double maximum, double currentvalue, double volume, int volume_flag, enum decision_method method);

The Shannon probability, which is the likelihood that a stock's value
will increase in the next time interval, is calculated by:

        avg
        --- + 1
        rms
    P = -------
           2

where avg is the average of the normalized increments, and rms the
root mean square of the normalized increments

both the root mean square and average of the normalized increments
have a "running" statistical filter estimates for the calculation of
the Shannon probability to compensate the probability for an
inadaquate data set size

under numerical exception, (ie., rms = 0,) assume P = 0.5-this can
only happen when the time series is a constant, or during
initialization and settling of the filters-other alternatives are P =
0, or P = 1

the volume_flag is for computing the fundamental Shannon probability
of a time series, given a stocks value, and the number of shares
traded, in each time interval; the value of a sample in the time
series is divided by the volume, and added to the cumulative sum of
the samples for a statistical estimate of the average of the
fundamental Shannon probability, and the square of the value, after
dividing by the volume, is added to the sum of the squares for a
statistical estimate on the root mean square of the funamental Shannon
probability-the same three options for computing the Shannon
probability, as above, are available with this option

note: this option if for investigating the fundamental Shannon
probability of a stock, based on daily data; in principle, if the
stock exhibits fractal characteristics, then the root mean square of
the normalized increments will increase with the square root of the
time interval-the average of the normalized increments will increase
linearly; what this means is that the root mean square should increase
with the square root of the volume, the average, linearly, ie:

              v  - v
               t    t - 1   1
        avg = ----------- * -
                v           N
                 t - 1

    where N is the trading volume, at time t.

    2) The root mean square of the normalized increment, rms, and is
    computed by:

                            2
               [v  - v     ]
           2   [ t    t - 1]   1
        rms  = [-----------] * -
               [  v        ]   N
               [   t - 1   ]

    where N is the trading volume, at time t.

conceptually, this option is used to ``adjust'' the Shannon
probability of a stock by considering the volumes of trade in a time
interval. The option should be regarded as experimental, and used
with caution

if no transactions on the stock are permitted, (either this being the
first instance of the stock, or transactions on the stock have been
discontinued,) then initialize the data structure as if this was the
first instance of the stock, ie., start all over if transactions on
the stock have been discontinued

returns the Shannon probability, P

*/

#ifdef __STDC__

static void shannon_probability (HASH *stock, double minimum, double maximum, double currentvalue, double volume, int volume_flag, enum decision_method method)

#else

static void shannon_probability (stock, minimum, maximum, currentvalue, volume, volume_flag, method)
HASH *stock;
double minimum;
double maximum;
double currentvalue;
double volume;
int volume_flag;
enum decision_method method;

#endif

{
    double avg, /* stock's filtered value of average of the normalized increments, avg, in the equation RMS * (avg / rms) */
           rmssquared, /* stock's filtered value of the root mean square of the normalized increments, rms, in the equation RMS * (avg / rms) */
           rms, /* filtered value of the root mean square of the normalized increments, rms, in the equation RMS * (avg / rms) */
           RMSsquared, /* stock's filtered value of the root mean square of the normalized increments, RMS, in the equation RMS * (avg / rms) */
           RMS, /* stock's filtered value of the root mean square of the normalized increments, RMS, in the equation RMS * (avg / rms) */
           lastvalue, /* last value of stock */
           fraction; /* normalized increment of stock's value */

    if (stock->suspend == 0) /* suspend transactions on this stock flag set? */
    {
        lastvalue = stock->currentvalue; /* no, save the last value of the stock */
        fraction = (currentvalue - lastvalue) / lastvalue; /* save the normalized increment of the stock */
        stock->currentvalue = currentvalue; /* save current value of the stock */
        stock->lastvalue = lastvalue; /* save the last value of the stock */
        stock->fraction = fraction; /* save the normalized increment of the stock's value */

        if (volume_flag == 0) /* calculate Shannon probability based on trading volume flag set? */
        {
            statistical_filter (stock, fraction, fraction * fraction); /* filter the normalized increments */
        }

        else
        {
            statistical_filter (stock, fraction / volume, (fraction * fraction) / volume); /* filter the normalized increments */
        }

        avg = stock->avgfilter; /* save the stock's filtered value of average of the normalized increments, avg */
        rmssquared = stock->rmsfilter; /* save the stock's filtered value of the root mean square of the normalized increments, rms */
        RMSsquared = stock->RMSfilter; /* save the stock's filtered value of the root mean square of the normalized increments, RMS */
        rms = sqrt (rmssquared); /* calculate the stock's filtered value of the root mean square of the normalized increments, rms, in the equation RMS * (avg / rms) */
        RMS = sqrt (RMSsquared); /* calculate the stock's filtered value of the root mean square of the normalized increments, RMS, in the equation RMS * (avg / rms) */

        switch (method) /* which method of decision criteria for investment in a stock */
        {

            case M_AVGRMS: /* RMS * (avg / rms) */

                if (rms == (double) 0.0) /* stock's filtered value of the root mean square of the normalized increments, rms, in the equation RMS * (avg / rms), zero? */
                {
                    stock->P = (double) 0.5; /* yes, division exception, assume the stock's Shannon probability is 0.5 */
                    stock->decision = (double) 0.0; /* division exception, assume the stock's decision criteria for investment is 0 */
                }

                else
                {
                    stock->P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* no, calculate the stock's Shannon probability from the equation P = ((avg / rms) + 1) / 2 */
                    stock->decision = RMS * (avg / rms); /* save the stock's decision criteria for investment in a stock, RMS * (avg / rms) */
                }

                break;

            case M_RMS: /* RMS */

                stock->P = (rms + (double) 1.0) / (double) 2.0; /* calculate the stock's Shannon probability from the equation P = (rms + 1) / 2 */
                stock->decision = RMS * rms; /* save the stock's decision criteria for investment in a stock, RMS * rms */
                break;

            case M_AVG: /* avg */

                if (avg < (double) 0.0) /* stock's filtered value of the average of the normalized increments, avg, in the equation RMS * (avg / rms), less than zero? */
                {
                    stock->P = (sqrt (-avg) + (double) 1.0) / (double) 2.0; /* yes, square root exception, assume the stock's Shannon probability is (sqrt (-avg) + 1) / 2 */
                }

                else
                {
                    stock->P = (sqrt (avg) + (double) 1.0) / (double) 2.0; /* calculate the stock's Shannon probability from the equation (sqrt (avg) + 1) / 2 */
                }

                stock->decision = avg; /* save the stock's decision criteria for investment in a stock, avg */
                break;

            case M_RANDOM: /* randomly */

                if (rms == (double) 0.0) /* stock's filtered value of the root mean square of the normalized increments, rms, in the equation RMS * (avg / rms), zero? */
                {
                    stock->P = (double) 0.5; /* yes, division exception, assume the stock's Shannon probability is 0.5 */
                }

                else
                {
                    stock->P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* no, calculate the stock's Shannon probability from the equation P = ((avg / rms) + 1) / 2 */
                }

                stock->decision = ran1 (&idem) - (double) 0.5; /* save the stock's decision criteria for investment in a stock, a random number */
                break;

            default: /* illegal switch? */

                if (rms == (double) 0.0) /* stock's filtered value of the root mean square of the normalized increments, rms, in the equation RMS * (avg / rms), zero? */
                {
                    stock->P = (double) 0.5; /* yes, division exception, assume the stock's Shannon probability is 0.5 */
                    stock->decision = (double) 0.0; /* division exception, assume the stock's decision criteria for investment is 0 */
                }

                else
                {
                    stock->P = ((avg / rms) + (double) 1.0) / (double) 2.0; /* no, calculate the stock's Shannon probability from the equation P = ((avg / rms) + 1) / 2 */
                    stock->decision = RMS * (avg / rms); /* save the stock's decision criteria for investment in a stock, RMS * (avg / rms) */
                }

                break;

        }

    }

    else
    {
        stock->currentvalue = currentvalue; /* yes, save current value of the stock */
        stock->lastvalue = (double) 0.0; /* initialize the last value of the stock */
        stock->fraction = (double) 0.0; /* initialize the normalized increment of the stock's value */
        stock->avgfilter = (double) 0.0; /* initialize the filtered value of average of the normalized increments, avg */
        stock->rmsfilter = (double) 0.0; /* initialize the filtered value of the root mean square of the normalized increments, rms */
        stock->RMSfilter = (double) 0.0; /* initialize the filtered value of the root mean square of the normalized increments, RMS */
        stock->decision = (double) 0.0; /* initialize the decision criteria for investment in a stock, qsortlist () will sort the list of next_decision elements by this value */
        stock->P = (double) 0.0; /* initialize the stock's Shannon probability */
    }

#ifdef LINT

    minimum = maximum; /* for lint issues */

#endif

}

/*

filter normalized increments

static void statistical_filter (HASH *stock, double avgfraction, double rmsfraction);

The statistical estimate can be approximated by a single pole low pass
recursive discreet time filter, with the pole frequency at 0.00045
times the time series sampling frequency. The accuracy of the
approximation is about +/- 10% for the first 260 samples, with the
approximation accuracy prediction becoming optimistic thereafter, ie.,
about +30%.

A pole frequency of 0.033 seems a good approximation for working with
the root mean square of the normalized increments, with a reasonable
approximation to about 5-10 time units.

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

there is no return value

*/

#ifdef __STDC__

static void statistical_filter (HASH *stock, double avgfraction, double rmsfraction)

#else

static void statistical_filter (stock, avgfraction, rmsfraction)
HASH * stock;
double avgfraction;
double rmsfraction;

#endif

{
    stock->avgfilter = avgfraction * k2 + stock->avgfilter * k1; /* compute the stock's filtered value of average of the normalized increments, avg */
    stock->rmsfilter = rmsfraction * k4 + stock->rmsfilter * k3; /* compute the stock's filtered value of the root mean square of the normalized increments, rms */
    stock->RMSfilter = rmsfraction * k6 + stock->RMSfilter * k5; /* compute the stock's filtered value of the root mean square of the normalized increments, RMS */
}

/*

parse a record based on sequential delimiters

int strtoken (char *string, char *parse_array, char **parse, const char *delim);

parse a character array, string, into an array, parse_array, using
consecutive characters from delim as field delimiters, point the
character pointers, token, to the beginning of each field, return the
number of fields parsed

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

hash table functions

static int hash_init (HASHTABLE *hash_table);
static int hash_insert (HASHTABLE *hash_table, void *data);
static HASH *hash_find (HASHTABLE *hash_table, void *data);
static int hash_delete (HASHTABLE *hash_table, void *data);
static void hash_term (HASHTABLE *hash_table);

I) the objective of the hash functions are to provide a means of
building look up tables in an expedient fashion

    A) each hash table consists of elements

        1) a hash table index, consisting of an array of hash
        elements, of type HASH

            a) the number of elements in the hash table index is
            determined by the hash table's descriptor structure
            element, hash_size

            b) each of the elements in the hash table index are the
            head elements in a doubly linked list

                i) each doubly linked list contains the hash table
                index at its head, and hash table elements, also of
                type HASH, that reference data stored in the hash
                table

        2) each element in the hash table is referenced via a key
        value

            a) there can be no assumptions made about the data, data's
            structure, or data's complexity

            b) this means that functions, unique to each hash table,
            must be implemented to:

                i) allocate any data space required

                ii) compare key values that reference the data

                iii) deallocate any data allocated

            c) references to these functions are stored in the hash
            table's descriptor structure, which has the following
            elements:

                i) hash_size is the size of the hash table

                ii) table is a reference to the hash table

                iii) mkhash is a reference to the function that
                allocates hash table elements and data space

                iv) cmphash is a reference to the function that
                compares hash table element keys

                v) rmhash is a reference to the function that
                deallocates hash table elements and data space

                vi) comphash is a reference to the function that
                computes the hash value of a key

        3) the hash table element structure, of type HASH, has the
        following elements:

            a) struct hash_struct *previous, which is a reference to
            next element in hash's doubly, circular linked list, and
            used by the hash system's internal list operations

            b) struct hash_struct *next, which is a reference to
            previous element in hash's doubly, circular linked list,
            and used by the hash system's internal list operations

            c) any collection of other elements as defined
            appropriately by the user, and can include:

                i) references to other data and data structures

                ii) numerical data, etc.

                iii) data referenced by these elements are allocated
                and deallocated by the user definable functions,
                mkhash () and rmhash (), respectively

    B) the hash table operations are performed by the following
    functions:

        1) int hash_init (HASHTABLE *hash_table), which initializes
        the hash table

        2) int hash_insert (HASHTABLE *hash_table, void *data);, which
        inserts elements, keys, and data in the hash table

        3) HASH *hash_find (HASHTABLE *hash_table, void *data), which
        fetches elements from the hash table

        4) int hash_delete (HASHTABLE *hash_table, void *data), which
        deletes elements from the hash table

        5) void hash_term (HASHTABLE *hash_table), which terminates
        use of the hash table, calling hash_delete () for each element
        remaining in the hash table

    C) All hash table functions return success or failure:

        1) functions that return an integer success/failure error code
        set the integer, hash_error to the return value of the
        function

        2) functions that return an indirect reference return
        success/failure error code (NULL corresponds to an error,) and
        set the integer, hash_error to a unique error value

        3) all hash functions set hash_error (numerical assignments
        are made in hash.h:)

            a) NOERROR if no error

            b) EALLOC if error allocating memory

            c) HASH_DUP_ERR if a duplicate key when inserting key into
            hash table

            d) HASH_MK_ERR if hash table mkhash () failure, and mkhash
            () did not set hash_error to NOERROR

            e) HASH_KEY_ERR if hash table key not found

II) int hash_init (HASHTABLE *hash_table) initializes the hash tables
data structures, and must be called prior to any operations on a hash
table:

    A) the single argument:

        1) hash_table is a reference to the hash table descriptor
        structure

    B) the return value:

        1) returns NOERROR if successful

        2) returns EALLOC if a memory allocation error

        3) returns HASH_INI_ERR if the hash table was already
        initialized

III) int hash_insert (HASHTABLE *hash_table, void *data) inserts a new
hash element into a hash table:

     A) the arguments:

         1) hash_table is a reference to the hash table descriptor
         structure

         2) data is a reference to the key value

             a) this reference is passed to the key comparison routine
             and the hash element construction routines specified in
             the hash descriptor structure

     B) the return value:

         1) returns NOERROR if successful

         2) returns HASH_DUP_ERR if a duplicate key was found

         3) if an error occured in mkhash ()

             a) if mkhash () set hash_error to other than NOERROR,
             returns the value in hash_error

             b) if mkhash () did not set hash_error to other than
             NOERROR, returns HASH_MK_ERR

IV) HASH *hash_find (HASHTABLE *hash_table, void *data) searches the
hash table for an element that matches a given key, according to the
key comparison routine specified in the hash table descriptor
structure

    A) the arguments:

        1) hash_table is a reference to the hash table descriptor

        2) data is a reference to the element's key value:

            a) this reference is passed to the key comparison routine
            specified in the hash table descriptor structure

    B) the return value:

        1) returns a reference to the hash element found in the hash
        table

        2) returns NULL if the element was not found in the hash table

V) int hash_delete (HASHTABLE *hash_table, void *data) deletes an
element from a hash table:

    A) the arguments:

        1) hash_table is a reference to the hash table descriptor

        2) data is a reference to the element's key value:

            a) this reference is passed to the key comparison routine
            specified in the hash table descriptor structure

    B) the return value:

        1) returns NOERROR if successful

        2) returns HASH_DEL_ERR if key not found

VI) void hash_term (HASHTABLE *hash_table) deletes a hash table,
including all remaining elements, and data space referenced by the
elements:

    A) the single argument:

        1) hash_table is a reference to the hash table descriptor
        structure

    B) there is no return value:

VII) hash table descriptor structure:

    A) before any use of hash routines, the handler functions, mkhash
    (), cmphash (), rmhash (), and comphash (), must be specified
    along with the hash table size, hash_size, in a hash table
    descriptor of type HASHTABLE

        1) the HASHTABLE element, table, which references the hash
        table, should be initialized to zero before calling
        hash_init ()

        2) for example:

            HASHTABLE my_table =
                {2729, 0, my_mkhash, my_cmphash, my_rmhash, my_comphash};

    B) the hash table descriptor structure is defined in hash.h, with
    the following elements:

        1) hash_size, size of hash table

            a) hash_size should be a prime number for optimal
            distribution of the hash elements in the hash array

        2) table, which is a reference to hash to the table

            a) this element is, generally, used only by the hash
            algorithms, but should be initialized to zero before
            calling hash_init ()

        3) mkhash, which is a reference to the function that allocates
        hash elements and data space

            a) mkhash () creates a hash element and its allocated data
            for insertion into a hash table

            b) struct HASH *(*mkhash) (void *data)

            c) the single argument:

                i) data is the address of the key associated with the
                element

            d) the return value:

                i) the address of the element constructed

                ii) returns NULL if no element was constructed

            e) mkhash must initialize the link elements, next and
            previous, both, to reference the element on return

        4) cmphash, which is a reference to the function that compares
        element's keys

            a) cmphash () compares a key against a key associated with
            a hash table element

            b) int (*cmphash) (void *data, HASH *element)

            c) the arguments:

                i) data is a reference to a key

                ii) element is a reference to a HASH structure to
                which the key should be compared

            d) the return value:

                i) returns 0 if data and the key associated with the
                element key are equal

                ii) returns non-zero if data and the key associated
                with the element are not equal

        5) rmhash, which is a reference to the function that
        deallocates hash elements and data space

            a) rmhash () is called to delete a hash table element and
            its allocated data from a hash table-given the address of
            the element created by mkhash (), it should reverse
            operations of mkhash ()

            b) void (*rmhash) (HASH *element)

            c) the single argument:

                i) element is a reference to the hash table element to
                delete

            d) there is no return value

        6) comphash, which is reference to the function that computes
        the hash value of a key

            a) comphash () is called to compute the hash value of a
            key

            b) int (*comphash) (void *key)

            c) the arguments:

                i) hash_table is a reference to the hash table
                descriptor

                ii) key is a reference to the key to be hashed

            d) the return value is the key's hash

VIII) performance issues:

    A) note that the number of comparisons, ch, required for a key
    search in the hash table is (on average):

        ch = (1 / 2) * (n / hash_size);

    where n is the number of keys in the hash table, and hash_size is
    the size of the hash table

    B) by comparison, the number of comparisons, cb, required for a
    key search using a binary search routine is (on average):

        cb = (1 / 2) * (log (n) / log (2));

    where it is assumed that an each key compared has an equal
    probability of being the key that is being searched for (probably
    an overly optimistic assumption)

    C) for a similar number of comparisons:

        hash_size = ((n * log (2)) / log (n));

    D) in powers of 2, the hash table size, hash_size, should be:

        N               C               I               P

        2               1               2               2
        4               2               2               2
        8               3               2               2
        16              4               4               5
        32              5               6               7
        64              6               10              11
        128             7               18              19
        256             8               32              37
        512             9               56              59
        1024            10              102             103
        2048            11              186             191
        4096            12              341             347
        8192            13              630             631
        16384           14              1170            1171
        32768           15              2184            2203
        65536           16              4096            4099
        131072          17              7710            7717
        262144          18              14563           14563
        524288          19              27594           27611
        1048576         20              52428           52433
        2097152         21              99864           99871
        4194304         22              190650          190657
        8388608         23              364722          364739
        16777216        24              699050          699053
        33554432        25              1342177         1342177
        67108864        26              2581110         2581121
        134217728       27              4971026         4971037
        268435456       28              9586980         9586981
        536870912       29              18512790        18512807
        1073741824      30              35791394        35791397
        2147483648      31              69273666        69273719

        where:

        1) N is the number of keys in the lookup table

        2) C is the maximum number of key comparisons (minus 1)
        required to locate a key, eg., floor (log (N) / log (2)

        3) I is the size of the hash table index, hash_size,
        floor (N / (log (N) / log (2)))

        4) P is the next larger prime than I

IX) data structure (with exactly one data space object allocated,
referenced by the HASH structure element, hash_data:)

    hash index array, an array of HASHTABLE->hash_size many HASH structures
+--------------------------------------------------------------------------
|
|
|
+-->typedef struct hash_struct
    {
        struct hash_struct *previous;
        void *hash_data;
        struct hash_struct *next;
    } HASH;
    typedef struct hash_struct
    {
        struct hash_struct *previous;
        void *hash_data;
        struct hash_struct *next;
    } HASH;
                      .
                      .
                      .
+-----------------------------------------------------------------------------+
|                                                                             |
|                                                                             |
+-->typedef struct hash_struct         +-->typedef struct hash_struct         |
|   {                                  |   {                                  |
|       struct hash_struct *previous;  |       struct hash_struct *previous;--+
|       void *hash_data; (null)        |       void *hash_data;--------------+
|       struct hash_struct *next;------+       struct hash_struct *next;--+  |
|   } HASH;                                } HASH;                        |  |
|                                                                         |  |
|                                                                         |  |
+-------------------------------------------------------------------------+  |
                      .                                                      |
                      .                                                      |
                      .                                                      |
    typedef struct hash_struct         +-------------------------------------+
    {                                  |
        struct hash_struct *previous;  |
        void *hash_data;               +-->[object data area]
        struct hash_struct *next;
    } HASH;
    typedef struct hash_struct
    {
        struct hash_struct *previous;
        void *hash_data;
        struct hash_struct *next;
    } HASH;

XII) the hash table index size, hash_size, should be a prime number,
(for best operation,); the following program will list the prime
numbers from 2 to the value of the command line argument:

    #include <stdio.h>
    #include <stdlib.h>

    int main (int argc,char *argv[])
    {
        int i,
            j,
            prime_flag,
            max_num;

        if (argc < 2)
        {
            (void) printf ("no maximum number\n");
            exit (1);
        }

        if ((max_num = atoi (argv[1])) < 2)
        {
            (void) printf ("maximum number must be greater than 2");
            exit (2);
        }

        for (i = 2;i <= max_num;i ++)
        {
            prime_flag = 1;

            for (j = 2;j < i;j ++)
            {

                if (i % j == 0)
                {
                    prime_flag = 0;
                    break;
                }

            }

            if (prime_flag == 1)
            {
                (void) printf ("%d\n",i);
            }

        }

        exit (0);
    }

*/

static HASH *temp_hash; /* temporary HASH reference in SWAP() */

#define SWAP(x,y) temp_hash = (x);(x) = (y);(y) = temp_hash /* linked list link element manipulator */

/*

initialize the hash table

static int hash_init (HASHTABLE *hash_table);

if this function has not been executed for this HASHTABLE structure,
allocate hash_table->hash_size many HASH structures for the hash table
index, initialize each element in the hash table index's next and
previous references to reference the element itself

the required argument is a reference to the hash table descriptor
structure, hash_table

returns NOERROR if successful, EALLOC if a memory allocation error,
and HASH_INI_ERR if the hash table was already initialized

*/

#ifdef __STDC__

static int hash_init (HASHTABLE *hash_table)

#else

static int hash_init (hash_table)
HASHTABLE *hash_table;

#endif

{
    size_t i; /* hash table element counter */

    HASH *hash_ref; /* reference to element in hash table index */

    hash_error = HASH_INI_ERR; /* assume hash table already initilaized error */

    if (hash_table->table == (HASH *) 0) /* protect from repeated calls by testing if hash table index has been allocated */
    {
        hash_error = EALLOC; /* assume memory error */

        if ((hash_table->table = (HASH *) malloc (hash_table->hash_size * sizeof (HASH))) != (HASH *) 0) /* allocate the hash table index */
        {
            hash_error = NOERROR; /* assume no error */

            for (i = 0; i < hash_table->hash_size; i ++) /* initialize the hash table doubly, circular linked list */
            {
                hash_ref = &hash_table->table[i]; /* reference the element in the hash table index */
                hash_ref->next = hash_ref; /* references its own element */
                hash_ref->previous = hash_ref; /* references its own element */
            }

        }


    }

    return (hash_error); /* return any error */
}

/*

insert a key and data into the hash table

static int hash_insert (HASHTABLE *hash_table, void *data);

calculate the key's hash, search the hash table index implicitly
addressed by the key's hash for a matching key, if the key is not
found, insert the key and data at the end of the doubly linked list
for this hash table index element

the required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, data

returns NOERROR if successful, HASH_DUP_ERR if a duplicate key was
found, HASH_MK_ERR if an error occured in mkhash () and hash_error was
not set by mkhash (), else the value of hash_error that was set by
mkhash ()

*/

#ifdef __STDC__

static int hash_insert (HASHTABLE *hash_table, void *data)

#else

static int hash_insert (hash_table, data)
HASHTABLE *hash_table;
void *data;

#endif

{
    int hashindex; /* index of element in hash table */

    HASH *element, /* new element to be inserted in hash table */
         *next_element, /* reference to next element in hash index's doubly, circular linked list */
         *last_element, /* reference to last element in hash index's doubly, circular linked list */
         *head_element; /* reference to head element in hash index's doubly, circular linked list */

    hash_error= NOERROR; /* assume no error */

    hashindex = (*hash_table->comphash) (hash_table, data); /* get the data's hash */
    head_element = next_element =&hash_table->table[hashindex]; /* reference the head element in hash index's doubly, circular linked list */

    while ((next_element = next_element->next) != head_element) /* while the element in the hash index's doubly, circular linked list is not the list's head */
    {

        if ((*hash_table->cmphash) (data, next_element) == 0) /* element compare with the data ? */
        {
            hash_error = HASH_DUP_ERR; /* yes, data is already in hash table */
            break;
        }

    }

    if (hash_error == NOERROR) /* any errors? */
    {
        hash_error = HASH_MK_ERR; /* no, assume mkhash () error */

        if ((element = (*hash_table->mkhash) (data)) != (HASH *) 0) /* no, get the hash element and data space */
        {
            last_element = next_element->previous; /* reference the last element in the doubly, circular linked list */
            SWAP (last_element->next, element->next); /* the new element is last in the doubly, circular linked list */
            SWAP (next_element->previous, element->previous);
            hash_error = NOERROR; /* assume no error */
        }

    }

    return (hash_error); /* return any error */
}

/*

find data in the hash table

static HASH *hash_find (HASHTABLE *hash_table, data *data);

calculate the key's hash, search for the key and data, starting at the
beginning of the doubly linked list for this hash table index element,
which is implicitly addressed by the key's hash

the required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, data

returns a reference to the hash table's element referencing the data,
0 if key is not found

*/

#ifdef __STDC__

static HASH *hash_find (HASHTABLE *hash_table, void *data)

#else

static HASH *hash_find (hash_table, data)
HASHTABLE *hash_table;
void *data;

#endif

{
    int hashindex; /* index of element in hash table */

    HASH *next_element, /* reference to next element in hash index's doubly, circular linked list */
         *head_element, /* reference to head element in hash index's doubly, circular linked list */
         *element = (HASH *) 0; /* return value, assume error */

    hash_error = HASH_KEY_ERR; /* assume key not found error */

    hashindex = (*hash_table->comphash) (hash_table, data); /* get the data's hash */
    head_element = next_element = &hash_table->table[hashindex]; /* reference the head element in hash index's doubly, circular linked list */

    while ((next_element = next_element->next) != head_element) /* while the element in the hash index's doubly, circular linked list is not the list's head */
    {

        if ((*hash_table->cmphash) (data,next_element) == 0) /* element compare with the data ? */
        {
            element = next_element; /* yes, found it, return a reference to the element */
            hash_error = NOERROR; /* assume no error */
            break;
        }

    }

    return (element); /* return any error */
}

#ifdef HASH_DELETE

/*

delete data from the hash table

static int hash_delete (HASHTABLE *hash_table, void *data);

calculate the key's hash, search for the key and data, starting at the
beginning of the doubly linked list for this hash table index element,
which is implicitly addressed by the key's hash, if found, remove the
key's HASH element, and data

the required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, data

returns NOERROR if successful, HASH_DEL_ERR if key not found

*/

#ifdef __ANSI__

static int hash_delete (HASHTABLE *hash_table,void *data)

#else

static int hash_delete (hash_table,data)
    HASHTABLE *hash_table;
    void *data;

#endif

{
    int hashindex; /* index of element in hash table */

    HASH *element, /* reference to element to be deleted from hash table */
         *next_element, /* reference to next element in hash index's doubly, circular linked list */
         *previous_element, /* reference to previous element in hash index's doubly, circular linked list */
         *head_element; /* reference to head element in hash index's doubly, circular linked list */

    hash_error = HASH_KEY_ERR; /* assume key not found error */

    hashindex = (*hash_table->comphash) (hash_table, data); /* get the data's hash */
    head_element = element = &hash_table->table[hashindex]; /* reference the head element in hash index's doubly, circular linked list */

    while ((element = element->next) != head_element) /* while the element in the hash index's doubly, circular linked list is not the list's head */
    {

        if ((*hash_table->cmphash) (data,element) == 0) /* element compare with the data ? */
        {
            next_element = element->next; /* yes, reference the next element in the hash's doubly, circular linked list */
            previous_element = element->previous; /* reference the previous element in the hash's doubly, circular linked list */
            SWAP (next_element->previous,element->previous); /* delete the element from the hash's doubly, circular linked list */
            SWAP (previous_element->next,element->next);
            (*hash_table->rmhash) (element); /* delete the element, and data referenced by the element */
            hash_error = NOERROR; /* found it, assume no error */
            break;
        }

    }

    return (hash_error); /* return any error */
}

#endif

/*

remove a hash table

static void hash_term (HASHTABLE *hash_table);

for each element in the hash table's index, for each element in the
doubly linked lest referenced by the hash table's index element,
remove the key's HASH element

the required argument is a reference to the hash table descriptor
structure, hash_table

always returns NOERROR

*/

#ifdef __STDC__

static void hash_term (HASHTABLE *hash_table)

#else

static void hash_term (hash_table)
    HASHTABLE *hash_table;

#endif

{
    size_t i;

    HASH *element, /* reference to element being deleted from hash index's doubly, circular linked list */
         *next_element, /* reference to next element in hash index's doubly, circular linked list */
         *head_element; /* reference to head element in hash index's doubly, circular linked list */

    hash_error = NOERROR; /* assume no error */

    for (i = 0; i < hash_table->hash_size && hash_error == NOERROR; i ++) /* for each of the hash table's index's (while there are no errors) */
    {
        head_element = &hash_table->table[i]; /* reference the head element in hash index's doubly, circular linked list */
        next_element = head_element->next; /* start with the first element in the hash index's doubly, circular linked list */

        while (next_element != head_element) /* while the element in the hash's doubly, circular linked list is not the list's head */
        {
            element = next_element; /* reference the element to be deleted from the doubly, circular linked list */
            next_element = next_element->next; /* reference next element in the hash's doubly, circular linked list */
            SWAP (next_element->previous,element->previous); /* delete the element from the hash's doubly, circular linked list */
            SWAP (head_element->next,element->next);
            (*hash_table->rmhash) (element); /* delete the element, and data referenced by the element */
        }

    }

    free (hash_table->table); /* no, free the hash table index */
}

/*

compute the hash value for a text key

static int hash_text (HASHTABLE *hash_table, void *key);

the routine requires a long to be 32 bits, which represents some
portability issues

the required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, key

returns the hash value for the key

*/

#ifdef __STDC__

static int hash_text (HASHTABLE *hash_table, void *key)

#else

static int hash_text (hash_table, key)
    HASHTABLE *hash_table;
    void *key;

#endif

{
    char *char_ref; /* byte reference into data */

    unsigned long num,
                  hash_num = (unsigned long) 0, /* hash number is initially zero */
                  ref = 0x0f000000; /* reference to make 0xf0000000 */

    ref = ref << 4; /* start with first four bits true */

    for (char_ref = key;*char_ref != (char) 0;char_ref ++) /* for each character in the key */
    {
        hash_num = (hash_num << 4) + (unsigned long) (((int) (*char_ref) < (int) 0) ? (long) (-(*char_ref)) : (long) (*char_ref)); /* multiply the hash by 16, and add the absolute value of the character */

        if ((num = hash_num & ref) != (unsigned long) 0) /* any high order bits in bit positions 24 - 28 ? */
        {
            hash_num = hash_num ^ (num >> 24); /* yes, divide by 16777216, and exor on itself */
            hash_num = hash_num ^ num; /* reset the high order bits to 0 */
        }

    }

    return ((int) (hash_num % (unsigned long) hash_table->hash_size)); /* return the hash number */
}

/*

compare a text key with a hash table element's text key

static int text_cmphash (void *data, HASH *element);

function that compares a text key with a hash table element's text key

(note that the data reference in HASH is a void reference, and
requires a cast to the appropriate data type)

returns 0 if data and the key associated with the element key are
equal

returns non-zero if data and the key associated with the element are
not equal

*/

#ifdef __STDC__

static int text_cmphash (void *data, HASH *element)

#else

static int text_cmphash (data, element)
void *data;
HASH *element;

#endif

{
    return (strcmp (data, ((char *) element->hash_data))); /* return the value of the comparison */
}

/*

allocate and loads a hash table element

static HASH *text_mkhash (void *data);

function that allocates and loads a hash table element, and allocates
a data (which in this simple case, is simply a copy of the key)

returns a reference to the element constructed if successful

returns NULL if no element was constructed

Note: hash_error, is set to EALLOC if a memory allocation error
occured

*/

#ifdef __STDC__

static HASH *text_mkhash (void *data)

#else

static HASH *text_mkhash (data)
void *data;

#endif

{
    void *obj_ref; /* reference to data */

    HASH *element = (HASH *) 0; /* reference to the hash table element */

    hash_error = EALLOC; /* assume memory error */

    if ((obj_ref = (char *) malloc ((1 + strlen (data)) * sizeof (char))) != (char *) 0) /* allocate the hash table element key's data area */
    {

        if ((element = (HASH *) malloc (sizeof (HASH))) != (HASH *) 0) /* allocate the hash table element */
        {
            element->next = element; /* all of the elements link references reference the element itself */
            element->previous = element; /* all of the elements link references reference the element itself */
            element->next_decision = element; /* reference to next element in qsortlist ()'s sort of the decision criteria list references itself */
            element->next_investment = element; /* reference to next element in invested list references itself */
            element->hash_data = obj_ref; /* reference to element's data */
            (void) strcpy (obj_ref, data); /* save the hash table element's key as its data */
            element->suspend = 1; /* initialize the suspend transations on this stock flag, initially, transactions are suspended */
            element->transactions = 0; /* initialize the number of changes in this stock's value */
            element->currentvalue = (double) 0.0; /* initialize the current value of stock */
            element->lastvalue = (double) 0.0; /* initialize the last value of the stock */
            element->capital = (double) 0.0; /* initialize the amount of capital invested in the stock */
            element->fraction = (double) 0.0; /* initialize the normalized increment of the stock's value */
            element->avgfilter = (double) 0.0; /* initialize the filtered value of average of the normalized increments, avg */
            element->rmsfilter = (double) 0.0; /* initialize the filtered value of the root mean square of the normalized increments, rms */
            element->RMSfilter = (double) 0.0; /* initialize the filtered value of the root mean square of the normalized increments, RMS */
            element->decision = (double) 0.0; /* initialize the decision criteria for investment in a stock, qsortlist () will sort the list of next_decision elements by this value */
            element->P = (double) 0.0; /* initialize the stock's Shannon probability */
            PUSH (decision_list, element); /* push the HASH element on the decision list */
            hash_error = NOERROR; /* assume no error */
        }

        else
        {
            free (obj_ref); /* allocate the hash table element failed, deallocate the hash table element key's data area */
        }

    }

    return (element); /* return a reference to the hash table element */
}

/*

deallocate a text hash table element

static void text_rmhash (HASH *element);

function to deallocate a text hash table element

returns nothing

*/

#ifdef __STDC__

static void text_rmhash (HASH *element)

#else

static void text_rmhash (element)
HASH *element;

#endif
{
    free (element->hash_data); /* free the key's data area allocated in text_mkhash () */
    free (element); /* free the hash table element allocated in text_mkhash () */
}

/*

quick sort a linked list

static void qsortlist (list *top, list bottom);

a stable quick sort for linked lists

Tail recursion is used to limit recursion to ln (n) levels, which cuts
the number of recursive calls by a factor of 2. Sorting time will be
proportional to n ln (n).

Note: this algorithm uses double level indirection-modifications must
be made with meticulous care.

The algorithm is as follows (the pivot is the dividing line between
high and low sub lists in a sub list):

   append each item in the beginning of a sub list to the high or low
   sub list, (at the completion of this loop, the low sub list has
   already been linked into the calling context at the beginning of
   the sub list)

   the pivot element is appended after the low sub list, and the end
   of the high sublist is then linked into the calling context sub
   list

   the beginning of the high sub list is finally appended after the
   pivot

Note: although the re linking must be done in this order, the order of
sorting the sub lists is not critical.

Usage is to typedef LIST as the structure element to be sorted, and
the token "list" as a reference to a LIST type of element, for
example:

typedef struct my_struct
{
    .
    .
    .
    int count;
    struct my_struct *next;
} MYSTRUCT;

typedef MYSTRUCT LIST;

typedef LIST *list;

where the tokens "LIST" and "list" are used internal to qsortlist
module.

Additionally, the structure element must have a numerical element,
"count," (in this example case, which is the sort key-but could
conceivably be any type, or token name,) and a reference element to
the next structure in the list, with a token name of "next," which is
used internal to the qsortlist module.

It is also necessary to include a comparison utility, either by
#define or function, that can compare the key elements in two list
elements. For example:

#define element_comp(x,y) (x)->count - (y)->count

The comparison utility must have the token name "element_comp," which
is used internal to the qsortlist module, and has the same return
value operations as strcmp(2), ie., if the first argument is lexically
larger, the return should be positive, and if it is smaller, it should
be negative, and if equal, zero is returned. Reverse the scenario for
a reverse sort on lexical order.

For a detailed description of quicksorting linked lists, see
"Quicksorting Linked Lists," Jeff Taylor, "C Gazette," Volume 5,
Number 6, October/November, 1991, ISSN 0897-4055, P.O. Box 70167,
Eugene, OR 97401-0110. Published by Oakley Publishing Company, 150
N. 4th Street, Springfield, OR 97477-5454.

The first argument references the first element in the linked list,
the second argument is null for linear linked lists, or references the
final element in a circularly linked list. The sort is stable, meaning
that elements with the same key value will remain in the same relative
order after the sorting process.

Returns nothing, but the linked list's next elements are rearranged
such that the list elements are in sorted order on the key.

*/

#ifdef __STDC__

static void qsortlist (list *top, list bottom)

#else

static void qsortlist (top, bottom)
list *top;
list bottom;

#endif

{
    int n; /* sub list's length, greater than 0 means high sub list is larger, less than 0 means lower sub list is larger */

    list *high, /* reference to top of sub list */
         high_list, /* reference to top of list */
         *low, /* reference to bottom of sub list */
         pivot, /* reference to pivot element for list sub division */
         previous; /* reference to previous element to pivot element */

    while (*top != bottom) /* starting at the top of the list, when the end of the list is reached, this recursion is finished */
    {
        previous = pivot = *top; /* save the top of the list */
        low = top; /* save the reference to the top of the list */
        high = &high_list; /* reference the high list */
        n = 0; /* sub list has no length */

        while ((previous = previous->next_decision) != bottom) /* scan the list to find the partition-this is the pivot value */
        {

            if (element_comp (previous, pivot) <= 0) /* compare this element's value with the value in the pivot */
            {
                *low = previous; /* if it less than or equal, the low value references this element */
                low = &previous->next_decision; /* and the low value references the next element in the list */
                n--; /* decrement the sub list's length */
            }

            else
            {
                *high = previous; /* if it is higher, the high value references this element */
                high = &previous->next_decision; /* and the high value references the next element in the list */
                n++; /* increment the sub list's length */
            }

        }

        *low = pivot; /* reassemble with pivot between parts, reference the pivot element */
        *high = bottom; /* reference the end of the list */
        pivot->next_decision = high_list; /* the pivot element's list references the top of the list */

        if (n > 0) /* sort sublists-always sort the larger sub list: is the high part is larger? */
        {
            qsortlist (top, pivot); /* yes, recurse on lower part */
            top = &pivot->next_decision; /* and the top of the list references the element past the pivot element */
        }

        else
        {
            qsortlist (&pivot->next_decision, bottom); /* no, recurse on high part */
            bottom = pivot; /* and the end of the list references the pivot */
        }

    }

}

#define M1 259200
#define IA1 7141
#define IC1 54773
#define RM1 (1.0/M1)
#define M2 134456
#define IA2 8121
#define IC2 28411
#define RM2 (1.0/M2)
#define M3 243000
#define IA3 4561
#define IC3 51349

/*

Returns a uniform random deviate between 0.0 and 1.0. Set idum to any
negative value to initialize or reinitialize the sequence. See
"Numerical Recipes in C: The Art of Scientific Computing," William
H. Press, Brian P. Flannery, Saul A. Teukolsky, William T. Vetterling,
Cambridge University Press, New York, 1988, ISBN 0-521-35465-X, page
210, referencing Knuth.

*/

#ifdef __STDC__

static double ran1 (int *idum)

#else

static double ran1 (idum)
int *idum;

#endif

{
    static int iff = 0;

    static long ix1,
                ix2,
                ix3;

    static double r[98];

    int j;

    double temp;

    if (*idum < 0 || iff == 0) /* initialize on first call even if idum is not negative */
    {
        iff = 1;
        ix1 = (IC1 - (*idum)) % M1; /* seed first routine */
        ix1 = (IA1 * ix1 + IC1) % M1;
        ix2 = ix1 % M2; /* use first to seed second routine */
        ix1 = (IA1 * ix1 +IC1) % M1;
        ix3 = ix1 % M3; /* use first to seed third routine */

        for (j = 1; j <= 97; j++) /* fill table with sequential uniform deviates generated by first two routines */
        {
            ix1 = (IA1 * ix1 + IC1) % M1;
            ix2 = (IA2 * ix2 + IC2) % M2;
            r[j] = (ix1 + ix2 * RM2) * RM1; /* low and high order pieces combined here */
        }

        *idum = 1;
    }

    ix1 = (IA1 * ix1 + IC1) % M1; /* except when initializing, this is the start-generate the next number for each sequence */
    ix2 = (IA2 * ix2 + IC2) % M2;
    ix3 = (IA3 * ix3 + IC3) % M3;
    j = 1 + ((97 * ix3)/M3); /* use the third sequence to get an integer between 1 and 97 */

    if (j > 97 || j < 1)
    {
        (void) fprintf (stderr, "RAN1: This can not happen.\n");
        exit (1);
    }

    temp = r[j]; /* return that table entry */
    r[j] = (ix1 + ix2 * RM2) * RM1; /* refill the table's entry */
    return (temp);
}
