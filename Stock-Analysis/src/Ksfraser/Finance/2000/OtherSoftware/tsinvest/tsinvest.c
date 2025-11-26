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

tsinvest.c is for simulating the optimal gains of multiple equity
investments. The program decides which of all available equities to
invest in at any single time, by calculating the instantaneous Shannon
probability and statistics of all equities, and then using statistical
estimation techniques to estimate the accuracy of the calculated
statistics.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample of each equity.
Blank records are ignored, and comment records are signified by a '#'
character as the first non white space character in the record. Each
data record represents an equity transaction, and consists of exactly
three fields, separated by white space. The fields are ordered by time
stamp, equity ticker identifier, and closing price, for example:

    1      ABC     333.6
    2      DEF     7.2
    3      GHI     335.9

The output file structure is a Unix tab delimited text file, with the
optional time stamp field, (if the -t argument is given,) the optional
index value, (if the -i option is given,) the portfolio value, the
optional margin fraction, (if the -q option is given,) followed by an
optional list of the equity ticker identifiers, (if the -s option is
given,) each followed by an optional equal, '=' character and the
fraction of the portfolio that should invested in that equity, (if the
-a option is given,) for example:

    1       999.64  1000.00 0.65    ABC=0.10\
                                    DEF=0.10 GHI=0.10
    2       998.19  1010.00 0.65    ABC=0.10\
                                    DEF=0.10 GHI=0.10
    3       998.19  1017.55 0.65    ABC=0.11\
                                    DEF=0.12 GHI=0.08
    4       997.52  1020.76 0.65    ABC=0.10\
                                    DEF=0.07 GHI=0.12

which at time interval 4, the index value was 997.52, the portfolio
value was 1020.76, and 65% of the portfolio should be on margin, 10%
of the portfolio's total value should be invested in the equity with
equity identifier ABC, 7% in DEF, and, 12% in GHI. The fields are tab
delimited.

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
    transitions in a time interval, (ie., the number of times an
    equity's price reaches a local maximum, then reverse direction and
    decreases to a local minimum,) would increase with the square root
    of time.

    5) It would be expected that the zero-free voids in an equity's
    price, (ie., the length of time an equity's price is above
    average, or below average,) would have a cumulative distribution
    that decreases with the reciprocal of the square root of time.

    6) It would be expected that an equity's price, over time, would
    be mean reverting, (ie., if an equity's price is below its
    average, there would be a propensity for the equity's price to
    increase, and vice versa.)

    7) It would be expected that some equity prices, over time, would
    exhibit persistence, ie., "price momentum".

Note that 1) and 2) above can be exploited to formulate an optimal
hedging strategy; 3), and 4) would tend to imply that "market timing"
is not attainable; 5), and 6) can be exploited to formulate an optimal
buy-sell strategy; and 7) would tend to indicate that, under certain
circumstances, "market timing", at least in a probabilistic sense, may
be viable.

DERIVATION

As a tutorial, the derivation will start with a simple compound
interest equation. This equation will be extended to a first order
random walk model of equity prices. Finally, optimizations will
derived based on the random walk model that are useful in optimizing
equity portfolio performance.

If we consider capital, V, invested in a savings account, and
calculate the growth of the capital over time:

    V(t) = V(t - 1)(1 + a(t)) ......................(1.1)

where a(t) is the interest rate at time t, (usually a constant[1].)
In equities, a(t) is not constant, and fluctuates, perhaps being
negative at certain times, (meaning that the value of the equity
decreased.)  This fluctuation in an equity's value can be represented
by modifying a(t) in Equation (1.1):

    a(t)  = f(t) * F(T) ............................(1.2)

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
to measure the statistical characteristics of the generator.
Substituting Equation (1.2) into Equation (1.1)[3]:

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

which are the metrics of the equity's random process. Note that this
is the "effective" compound interest rate from Equation (1.1).
Equations (1.7) and (1.9) are important equations, since they can be
used in portfolio management.  For example, Equation (1.7) states that
portfolio volatility is calculated as the root mean square sum of the
individual volatilities of the equities in the portfolio.  Equation
(1.9) states that the averages of the normalized increments of the
equity prices add together linearly[6] in the portfolio.  Dividing
Equation (1.9) by Equation (1.7) results in the two f's canceling, or:

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
equity investment strategies.  From Equation (1.10):

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

Interestingly, what Equation (1.12) states is that the "best" equity
investment is not, necessarily, the equity that has the largest
average growth.  A better investment criteria is to choose the equity
that has the largest growth, while simultaneously having the smallest
volatility.

Continuing with this line of reasoning, and rearranging Equation
(1.12):

    avg = rms * (2P - 1) ..........................(1.15)

which is an important equation since it states that avg, (and the
parameter that should be maximized,) is equal to rms, which is the
measure of the volatility of the equity's value, multiplied by the
quantity, twice the likelihood that the equity's value will increase
in the next time interval, minus unity.

As derived in the Section, OPTIMIZATION, below, the optimal growth
occurs when f = rms = 2P - 1. Under optimal conditions, Equation
(1.14) becomes:

        rms + 1
    P = ------- ...................................(1.16)
           2

or, sqrt (avg) = rms, (again, under optimal conditions,) and
substituting into Equation (1.14):

        sqrt (avg) + 1
    P = -------------- ............................(1.17)
              2

giving three different computational methods for measuring the
statistics of an equity's value.

Note that, from Equations (1.14) and (1.12), that since avgf = avg /
rms = (2P - 1), choosing the largest value of the Shannon probability,
P, will also choose the largest value of the ratio of avg / rms, rms,
or avg, respectively, in Equations (1.14), (1.16), or (1.17). This
suggests a method for determination of equity selection
criteria. (Note that under optimal conditions, all three equations are
identical-only the metric methodology is different. Under non-optimal
conditions, Equation (1.14) should be used. Unfortunately, any
calculation involving the average of the normalized increments of an
equity value time series will be very "sluggish," meaning that
practical issues may prevail, suggesting a preference for Equation
(1.17).)  However, this would imply that the equities are known to be
optimal, ie., rms = 2P + 1, which, although it is nearly true for most
equities, is not true for all equities. There is some possibility that
optimality can be verified by metrics:

                2
    if avg < rms

        then rms = f is too large in Equation (1.12)

                     2
    else if avg > rms

        then rms = f is too small in Equation (1.12)

                  2
    else avg = rms

        and the equities time series is optimal, ie.,
        rms = f = 2P - 1 from Equation (1.36), below

HEURISTIC APPROACHES

There have been several heuristic approaches suggested, for example,
using the absolute value of the normalized increments as an
approximation to the root mean square, rms, and calculating the
Shannon probability, P by Equation (1.16), using the absolute value,
abs, instead of the rms. The statistical estimate in such a scheme
should use the same methodology as in the root mean square.

Another alternative is to model equity value time series as a fixed
increment fractal, ie., by counting the up movements in an equity's
value. The Shannon probability, P, is then calculated by the quotient
of the up movements, divided by the total movements. There is an issue
with this model, however. Although not common, there can be adjacent
time intervals where an equity's value does not change, and it is not
clear how the accounting procedure should work. There are several
alternatives. For example, no changes can be counted as up movements,
or as down movements, or disregarded entirely, or counted as both.
The statistical estimate should be performed as in Equation (1.14),
with an rms of unity, and an avg that is the Shannon probability
itself-that is the definition of a fixed increment fractal.

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
equity market are represented by the term f(t) * F(t) in Equation
(1.3), and since there are many such indeterminacies, we would
anticipate F(t) to have a Gaussian distribution.  This is a rather
interesting conclusion, since analyzing the aggregate actions of many
"agents," each operating on subjective hypothesis in a market that is
deductively indeterminate, can result in a system that can not only be
analyzed, but optimized.

OPTIMIZATION

The only remaining derivation is to show that the optimal wagering
strategy is, as cited above:

    f = rms = 2P - 1 ..............................(1.18)

where f is the fraction of a gambler's capital wagered on each toss of
a coin that has a Shannon probability, P, of winning.  Following
[Rez94, pp. 450], or [Kel56], consider that the gambler has a private
wire into the future, (ie., an inductive hypothesis,) who places
wagers on the outcomes of a game of chance.  We assume that the side
information which he receives has a probability, P, of being true, and
of 1 - P, of being false.  Let the original capital of gambler be
V(0), and V(n) his capital after the n'th wager.  Since the gambler is
not certain that the side information is entirely reliable, he places
only a fraction, f, of his capital on each wager.  Thus, subsequent to
n many wagers, assuming the independence of successive tips from the
future, his capital is:

                   w        l
    V(n)  = (1 + f)  (1 - f) V (0) ................(1.19)

where w is the number of times he won, and l = n - w, the number of
times he lost. These numbers are, in general, values taken by two
random variables, denoted by W and L. According to the law of large
numbers:

                  1
    lim           - W = P .........................(1.20)
    n -> infinity n


                  1
    lim           - L = q = 1 - P .................(1.21)
    n -> infinity n

The problem with which the gambler is faced is the determination of f
leading to the maximum of the average exponential rate of growth of
his capital. That is, he wishes to maximize the value of:

                      1    V(n)
    G = lim           - ln ---- ...................(1.22)
        n -> infinity n    V(0)

with respect to f, assuming a fixed original capital and specified P:

                      W              L
    G = lim           - ln (1 + f) + - ln (1 - f) .(1.23)
        n -> infinity n              n

or:


    G = P ln (1 + f) + q ln (1 - f) ...............(1.24)

which, by taking the derivative with respect to f, and equating to
zero, can be shown to have a maxima when:

    dG           P - 1        1 - P
    -- = P(1 + f)      (1 - f)      -
    df

                  1 - P - 1        P
    (1 - P)(1 - f)          (1 + f)  = 0 ..........(1.25)

combining terms:


                P - 1        1 - P
    0 = P(1 + f)      (1 - f)      -

                  P         P
    (1 - P)(1 - f)  (1 + f )  .....................(1.26)

and splitting:

            P - 1        1 - P
    P(1 + f)      (1 - f)      =

                  P        P
    (1 - P)(1 - f)  (1 + f)  ......................(1.27)

then taking the logarithm of both sides:

    ln (P) + (P - 1) ln (1 + f) + (1 - P) ln (1 - f) =

    ln (1 - P) - P ln (1 - f) + P ln (1 + f) ......(1.28)

and combining terms:

    (P - 1) ln (1 + f) - P ln (1 + f) +

    (1 - P) ln (1 - f) + P ln (1 - f) =

    ln (1 - P) - ln (P) ...........................(1.29)

or:

    ln (1 - f) - ln (1 + f) =

    ln (1 - P)  - ln (P)...........................(1.30)

and performing the logarithmic operations:

       1 - f      1 - P
    ln ----- = ln ----- ...........................(1.31)
       1 + f        P

and exponentiating:

    1 - f   1 - P
    ----- = ----- .................................(1.32)
    1 + f     P

which reduces to:

    P(1 - f) = (1 - P)(1 + f) .....................(1.33)

and expanding:

    P - Pf = 1 - Pf - P + f .......................(1.34)

or:

    P = 1 - P + f .................................(1.35)

and, finally:

    f = 2P - 1 ....................................(1.36)

Note that Equation (1.24), which, since rms = f, can be rewritten:

    G = P ln (1 + rms) + (1 - P) ln (1 - rms) .....(1.37)

where G is the average exponential rate of growth in an equity's
value, from one time interval to the next, (ie., the exponentiation of
this value minus unity[11] is the "effective interest rate", as
expressed in Equation (1.1),) and, likewise, Equation (1.36) can be
rewritten:

    rms = 2P - 1 ..................................(1.38)

and substituting:

    G = P ln (1 + 2P - 1) +

        (1 - P) ln (1 - (2P - 1)) .................(1.39)

or:

    G = P ln (2P) +

        (1 - P) ln (2 (1 - P)) ....................(1.40)

using a binary base for the logarithm:

    G = P ln (2P) +
            2

        (1 - P) ln (2 (1 - P)) ....................(1.41)
                  2

and carrying out the operations:

    G = P ln (2) + P ln (P) +
            2          2

        (1 - P) ln (2) + (1 - P) ln (1 - P)) ......(1.42)
                  2                2

which is:

    G = P ln (2) + P ln (P) +
            2          2

        ln (2) - P ln (2) + (1 - P) ln (1 - P) ....(1.43)
          2          2                2

and canceling:

    G = 1 + P ln (P) + (1 - P) ln (1 - P) .........(1.44)
                2                2

if the gambler's wagering strategy is optimal, ie., f = rms = 2P - 1,
which is identical to the equation in [Sch91, pp. 151].

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

If we let V(0) be the initial value of the gambler's capital, V(1) be
the value of the gambler's capital after the first iteration of the
game, then:

    V(1) = V(0) * (1 + c(1) * f(1)) ...............(1.45)

after the first iteration of the game, and:

    V(2) = V(0) * ((1 + c(1) * f(1)) *

           (1 + c(2) * f(2)))  ....................(1.46)

after the second iteration of the game, and, in general, after the
n'th iteration of the game:

    V(n) = V(0) * ((1 + c(1) * f(1)) *

           (1 + c(2) * f(2)) * ...

           * (1 + c(n) * f(n)) *

           (1 + c(n + 1) * f(n + 1))) .............(1.47)

For the normalized increments of the time series of the gambler's
capital, it would be convenient to rearrange these formulas. For the
first iteration of the game:

    V(1) - V(0) = V(0) * (1 + c(1) * f(1)) - V(0) .(1.48)

or

    V(1) - V(0)   V(0) * (1 + c(1) * f(1)) - V(0)
    ----------- = ------------------------------- .(1.49)
       V(0)                   V(0)

and after reducing, the first normalized increment of the gambler's
capital time series is:

    V(1) - V(0)
    ----------- = (1 + c(1) * f(1)) - 1
       V(0)

                = c(1) * f(1) .....................(1.50)

and for the second iteration of the game:

    V(2) = V(0) * ((1 + c(1) * f(1)) *

           (1 + c(2) * f(2))) .....................(1.51)

but V(0) * ((1 + c(1) * f(1)) is simply V(1):

    V(2) = V(1) * (1 + c(2) * f(2)) ...............(1.52)

or:

    V(2) - V(1) = V(1) * (1 + c(2) * f(2)) - V(1) .(1.53)

which is:

    V(2) - V(1)   V(1) * (1 + c(2) * f(2)) - V(1)
    ----------- = ------------------------------- .(1.54)
       V(1)                    V(1)

and after reducing, the second normalized increment of the gambler's
capital time series is:

    V(2) - V(1)
    ----------- = 1 + c(2) * f(2)) - 1
       V(1)

                = c(2) * f(2) .....................(1.55)

and it should be obvious that the process can be repeated
indefinitely, so, the n'th normalized increment of the gambler's
capital time series is:

    V(n) - V(n - 1)
    --------------- = c(n) * f(n) .................(1.56)
         V(n)

which is Equation (1.6).

DATA SET SIZE CONSIDERATIONS

This section addresses the question "is there reasonable evidence to
justify investment in an equity based on data set size?"

The Shannon probability of a time series is the likelihood that the
value of the time series will increase in the next time interval. The
Shannon probability is measured using the average, avg, and root mean
square, rms, of the normalized increments of the time series. Using
the rms to compute the Shannon probability, P:

        rms + 1
    P = ------- ...................................(1.57)
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
    Pc = ----------- ..............................(1.58)
              2

where the error level, e, and the confidence level, c, are calculated
using statistical estimates, and the product P times c is the
effective Shannon probability that should be used in the calculation
of optimal wagering strategies.

The error, e, expressed in terms of the standard deviation of the
measurement error do to an insufficient data set size, esigma, is:

              e
    esigma = --- sqrt (2N) ........................(1.59)
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
    Pc = ----------- ..............................(1.60)
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
                  = ---------------------- ........(1.61)
                              2

Then:

                                rms * esigma
                          rms - ------------ + 1
    rms + 1                      sqrt (2N)
    ------- * F(esigma) = ---------------------- ..(1.62)
       2                            2

or:

                                  rms * esigma
    (rms + 1) * F(esigma) = rms - ------------ + 1 (1.63)
                                   sqrt (2N)

Letting a decision variable, decision, be the iteration error created
by this equation not being balanced:

                     rms * esigma
    decision = rms - ------------ + 1
                       sqrt (2N)

                - (rms + 1) * F(esigma) ...........(1.64)

which can be iterated to find F(esigma), which is the confidence
level, c.

Note that from Equation (1.58):

         rms - e + 1
    Pc = -----------
              2

and solving for rms - e, the effective value of rms compensated for
accuracy of measurement by statistical estimation:

    rms - e = (2 * P * c) - 1 .....................(1.65)

and substituting into Equation (1.57):

        rms + 1
    P = -------
           2

    rms - e = ((rms + 1) * c) - 1 .................(1.66)

and defining the effective value of rms as rmseff:

    rmseff = rms - e ..............................(1.67)

From Equations (1.16) and (1.17) it can be seen that if optimality
exists, ie., f = 2P - 1, or:

             2
    avg = rms  ....................................(1.68)

or:
                   2
    avgeff = rmseff  ..............................(1.69)

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
    f(x) = ------------- * e           ............(1.70)
           sqrt (2 * PI)

Numerical stability and convergence issues are an issue in such
processes.

The Shannon probability of a time series is the likelihood that the
value of the time series will increase in the next time interval. The
Shannon probability is measured using the average, avg, and root mean
square, rms, of the normalized increments of the time series. Using
the avg to compute the Shannon probability, P:

        sqrt (avg) + 1
    P = -------------- ............................(1.71)
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
    Pc = ------------------ .......................(1.72)
                 2

where the error level, e, and the confidence level, c, are calculated
using statistical estimates, and the product P times c is the
effective Shannon probability that should be used in the calculation
of optimal wagering strategies.

The error, e, expressed in terms of the standard deviation of the
measurement error do to an insufficient data set size, esigma, is:

              e
    esigma = --- sqrt (N) .........................(1.73)
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
    Pc = ------------------ .......................(1.74)
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
                  = ----------------------------- .(1.75)
                                 2

Then:

    sqrt (avg)  + 1
    --------------- * F(esigma) =
           2

                    rms * esigma
        sqrt [avg - ------------] + 1
                      sqrt (N)
        ----------------------------- .............(1.76)
                     2

or:

    (sqrt (avg) + 1) * F(esigma) =

                    rms * esigma
        sqrt [avg - ------------] + 1 .............(1.77)
                      sqrt (N)

Letting a decision variable, decision, be the iteration error created
by this equation not being balanced:

                            rms * esigma
    decision = sqrt [avg - ------------] + 1
                              sqrt (N)

               - (sqrt (avg) + 1) * F(esigma) .....(1.78)

which can be iterated to find F(esigma), which is the confidence
level, c.

There are two radicals that have to be protected from numerical
floating point exceptions. The sqrt (avg) can be protected by
requiring that avg >= 0, (and returning a confidence level of 0.5, or
possibly zero, in this instance-a negative avg is not an interesting
solution for the case at hand.)  The other radical:

                rms * esigma
    sqrt [avg - ------------] .....................(1.79)
                  sqrt (N)

and substituting:

              e
    esigma = --- sqrt (N) .........................(1.80)
             rms

which is:

                       e
                rms * --- sqrt (N)
                      rms
    sqrt [avg - ------------------] ...............(1.81)
                  sqrt (N)

and reducing:

    sqrt [avg - e] ................................(1.82)

requiring that:

    avg >= e ......................................(1.83)

Note that if e > avg, then Pc < 0.5, which is not an interesting
solution for the case at hand. This would require:

              avg
    esigma <= --- sqrt (N) ........................(1.84)
              rms

Obviously, the search algorithm must be prohibited from searching for
a solution in this space. (ie., testing for a solution in this space.)

The solution is to limit the search of the confidence array to values
that are equal to or less than:

    avg
    --- sqrt (N) ..................................(1.85)
    rms

which can be accomplished by setting integer variable, top, usually
set to sigma_limit - 1, to this value.

Note that from Equation (1.72):

         sqrt (avg - e) + 1
    Pc = ------------------
                 2

and solving for avg - e, the effective value of avg compensated for
accuracy of measurement by statistical estimation:

                               2
    avg - e = ((2 * P * c) - 1)  ..................(1.86)

and substituting into Equation (1.71):

        sqrt (avg) + 1
    P = --------------
              2

                                          2
    avg - e = (((sqrt (avg) + 1) * c) - 1)  .......(1.87)

and defining the effective value of avg as avgeff:

    avgeff = avg - e ..............................(1.88)

From Equations (1.16) and (1.17) it can be seen that if optimality
exists, ie., f = 2P - 1, or:

             2
    avg = rms  ....................................(1.89)

or:

    rmseff = sqrt (avgeff) ........................(1.90)

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
    P = ------- ...................................(1.91)
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
    P * ca * cr = ------------ ....................(1.92)
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
    esigmar = --- sqrt (2N) .......................(1.93)
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
    P * cr = ------------ .........................(1.94)
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
                     ----------------------- ......(1.95)
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
           ----------------------- ................(1.96)
                      2

or:

     avg
    (--- + 1) * F(esigmar) =
     rms

                   avg
           ------------------- + 1 ................(1.97)
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
                - (--- + 1) * F(esigmar) ..........(1.98)
                   rms

which can be iterated to find F(esigmar), which is the confidence
level, cr.

The error, ea, expressed in terms of the standard deviation of the
measurement error do to an insufficient data set size, esigmaa, is:

              ea
    esigmaa = --- sqrt (N) ........................(1.99)
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
    P * ca = ------------ ........................(1.100)
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
                     ----------------------- .....(1.101)
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
           ----------------------- ...............(1.102)
                      2

or:

     avg
    (--- + 1) * F(esigmaa) =
     rms

                 esigmaa * rms
           avg - -------------
                   sqrt (N)
           ------------------- + 1 ...............(1.103)
                     rms

Letting a decision variable, decision, be the iteration error created
by this equation not being balanced:

                     esigmaa * rms
               avg - -------------
                       sqrt (N)
    decision = ------------------- + 1
                         rms

           avg
        - (--- + 1) * F(esigmaa) .................(1.104)
           rms

which can be iterated to find F(esigmaa), which is the confidence
level, ca.

Note that from Equation (1.94):

               avg
             -------- + 1
             rms + er
    P * cr = ------------
                  2

and solving for rms + er, the effective value of rms compensated for
accuracy of measurement by statistical estimation:

                     avg
    rms + er = ---------------- ..................(1.105)
               (2 * P * cr) - 1

and substituting into Equation (1.100):

        avg
        --- + 1
        rms
    P = -------
           2

                       avg
    rms + er = -------------------- ..............(1.106)
                 avg
               ((--- + 1) * cr) - 1
                 rms

and defining the effective value of avg as rmseff:

    rmseff = rms +/- er ..........................(1.107)

Note that from Equation (1.100):

             avg - ea
             -------- + 1
               rms
    P * ca = ------------
                  2

and solving for avg - ea, the effective value of avg compensated for
accuracy of measurement by statistical estimation:

    avg - ea = ((2 * P * ca) - 1) * rms ..........(1.108)

and substituting into Equation (1.91):

        avg
        --- + 1
        rms
    P = -------
           2

                  avg
    avg - ea = (((--- + 1) * ca) - 1) * rms ......(1.109)
                  rms

and defining the effective value of avg as avgeff:

    avgeff = avg - ea ............................(1.110)

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
run length will exceed time, t, is [Sch91, pp. 160]:

    1 - erf (1 / sqrt (t)) .......................(1.111)

or the Shannon probability, P, will have to be compensated by a factor
of:

    erf (1 / sqrt (t)) ...........................(1.112)

giving a compensated Shannon probability, Pcomp:

    Pcomp = Peff * (1 - erf (1 / sqrt (t))).......(1.113)

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

    erf (N) = 2 * (normal (t * sqrt (2)) - 0.5) ..(1.114)

PORTFOLIO OPTIMIZATION

Let K be the number of equities in the equity portfolio, and assume
that the capital is invested equally in each of the equities, (ie., if
V is the capital value of the portfolio, then the amount invested in
each equity is V / K.)  The portfolio value, over time, would be a
time series with a root mean square value of the normalized
increments, rmsp, and an average value of the normalized increments,
avgp. Obviously, it would be advantageous to optimize the portfolio
growth. From Equation (1.16):

         rmsp + 1
    Pp = -------- ................................(1.115)
            2

where the root mean square value of the normalized increments of the
portfolio value, rmsp, is the root mean square sum of the root mean
square values of the normalized increments of each individual equity:

                  1     2   1     2
    rmsp = sqrt ((-rms ) + (-rms )  + ...
                  K   1     K   2

                  1     2
           ... + (-rms ) ) .......................(1.116)
                  K   K

or:

           1          2      2            2
    rmsp = - sqrt (rms  + rms  + ... + rms  ) ....(1.117)
           K          1      2            K

and Pp is the Shannon probability (ie., the likelyhood,) that the
value of the portfolio time series will increase in the next time
interval.

Note that Equation (1.16) presumes that the portfolio's time series
will be optimal, ie., rmsp = sqrt (avgp). This is probably not the
case, since rmsp will always be less than the individual values of rms
for the equities. Additionally, note that assuming the distribution of
capital, V / K, invested in each equity to be identical may not be
optimal. It is not clear if there is a formal optimization for the
distribution, and, perhaps, the application of simulated annealing,
linear/mathematical programming, or genetic algorithms to the
distribution problem may be of some benefit.

Again, letting K be the number of equities in the equity portfolio,
and assuming that the capital is invested equally in each of the
equities, (ie., if V is the capital value of the portfolio, then the
amount invested in each equity is V / K.)  The portfolio value, over
time, would be a time series with a root mean square value of the
normalized increments, rmsp, and an average value of the normalized
increments, avgp. Obviously, it would be advantageous to optimize the
portfolio growth. From Equation (1.17):

         sqrt (avgp) + 1
    Pp = --------------- .........................(1.118)
                2

where the average value of the normalized increments of the portfolio
value, avgp, is the sum of the average values of the normalized
increments of each individual equity:

           1        1             1
    avgp = - avg  + - avg + ... + - avg  .........(1.119)
           K    1   K    2        K    K

or:

           1
    avgp = - (avg  + avg + ... + avg  ) ..........(1.120)
           K     1      2           K

and Pp is the Shannon probability (ie., the likelyhood,) that the
value of the portfolio time series will increase in the next time
interval.

Note that Equation (1.17) presumes that the portfolio's time series
will be optimal, ie., rmsp = sqrt (avgp). This is probably not the
case, since rmsp will always be less than the individual values of rms
for the equities. Additionally, note that assuming the distribution of
capital, V / K, invested in each equity to be identical may not be
optimal. It is not clear if there is a formal optimization for the
distribution, and, perhaps, the applications of simulated annealing or
genetic algorithms to the distribution problem may be of some benefit.

Again, letting K be the number of equities in the equity portfolio,
and assuming that the capital is invested equally in each of the
equities, (ie., if V is the capital value of the portfolio, then the
amount invested in each equity is V / K.)  The portfolio value, over
time, would be a time series with a root mean square value of the
normalized increments, rmsp, and an average value of the normalized
increments, avgp. Obviously, it would be advantageous to optimize the
portfolio growth. From Equation (1.14):

         avgp
         ---- + 1
         rmsp
    Pp = -------- ................................(1.121)
            2

where the average value of the normalized increments of the portfolio
value, avgp, is the sum of the average values of the normalized
increments of each individual equity, and rmsp is the root mean square
sum of the root mean square values of the normalized increments of
each individual equity:

           1        1             1
    avgp = - avg  + - avg + ... + - avg  .........(1.122)
           K    1   K    2        K    K

and:

                  1     2   1     2
    rmsp = sqrt ((-rms ) + (-rms )  + ...
                  K   1     K   2

                  1     2
           ... + (-rms ) ) .......................(1.123)
                  K   K

or:

           1
    avgp = - (avg  + avg + ... + avg  ............(1.124)
           K     1      2           K

and:

           1          2      2            2
    rmsp = - sqrt (rms  + rms  + ... + rms  ) ....(1.125)
           K          1      2            K

and dividing:

              (avg  + avg + ... + avg  )
    avgp          1      2           K
    ---- = -------------------------------- ......(1.126)
    rmsp            2      2            2
           sqrt (rms  + rms  + ... + rms  )
                    1      2            K

and Pp is the Shannon probability (ie., the likelyhood,) that the
value of the portfolio time series will increase in the next time
interval.

The portfolio's average exponential rate of growth, Gp, would be, from
Equation (1.37):

    Gp = Pp ln (1 + rmsp) +

         (1 - Pp) ln (1 - rmsp) ..................(1.127)

where the Shannon probability of the portfolio, Pp, is determined by
one of the Equations, (1.115), (1.118), or (1.121).

Note that assuming the distribution of capital, V / K, invested in
each equity to be identical may not be optimal. It is not clear if
there is a formal optimization for the distribution, and, perhaps, the
applications of simulated annealing or genetic algorithms to the
distribution problem may be of some benefit. Additionally, note that
Equation (1.121) should be used for portfolio management, as opposed
to Equations (1.115) and (1.118), which are not applicable, (Equations
(1.115) and (1.118) are monotonically decreasing on K, the number of
equities held concurrently.)

Interestingly, plots of Equation (1.127) using Equations (1.121) and
(1.127) to calculate the Shannon probability, Pp, of the portfolio,
with the number of equities held, K, as a parameter for various values
of avg and rms, tends to support the prevailing concept that the best
number of equities to hold is approximately 10. There is little
advantage in holding more, and a distinct disadvantage in holding
less[12].

MEAN REVERTING DYNAMICS

It can be shown that the number of expected equity value "high and
low" transitions scales with the square root of time, meaning that the
cumulative distribution of the probability of an equity's "high or
low" exceeding a given time interval is proportional to the reciprocal
of the square root of the time interval, (or, conversely, that the
probability of an equity's "high or low" exceeding a given time
interval is proportional to the reciprocal of the time interval raised
to the power 3/2 [Sch91, pp. 153]. What this means is that a histogram
of the "zero free" run-lengths of an equity's price would have a 1 /
(l^3/2) characteristic, where l is the length of time an equity's
price was above or below "average.") This can be exploited for a short
term trading strategy, which is also called "noise trading."

The rationale proceeds as follows. Let l be the run length, (ie., the
number of time intervals,) that an equity's value has been above or
below average, then the probability that it will continue to do so in
the next time interval will be:

    Pt = erf (1 / sqrt (l + 1)) ..................(1.128)

where Pt is the "transient" probability. Naturally, it would be
desirable to buy low and sell high. So, if an equity's price is below
average, then the probability of an upward movement is given by
Equation (1.128). If an equity's price is above average, then, then
the probability that it will continue the trend is:

    Pt = 1 - erf (1 / sqrt (l + 1)) ..............(1.129)

Equations (1.128) and (1.129) can be used to find the optimal time to
trade one equity for another.

Note that equation (1.37) can be used to find whether an equity's
current price is above, or below average:

    G = P ln (1 + rms) + (1 - P) ln (1 - rms)

by exponentiating both sides of the equation, and subtracting the
value from the current price of the equity.

Note that there is a heuristic involved in this procedure. The
original derivation [Sch91, pp. 152], assumed a fixed increment
Brownian motion fractal, (ie., V (n + 1) = V (n) + F (n)), which is
different than Equation (1.3), V (n + 1) = V (n) (1 + F (n)). However,
simulations of Equation (1.3) tend to indicate that a histogram of the
"zero free" run-lengths of an equity's price would have a 1 / (l^3/2)
characteristic, where l is the length of time an equity's price was
above or below "average." Note that in both formulas, with identical
statistical processes, the values would, intuitively, be above, or
below, average in much the same way. Additionally, note that in the
case of a fixed increment Brownian motion fractal, the average is
known-zero, by definition. However, in this procedure, the average is
measured, and this can introduce errors, since the average itself is
fluctuating slightly, do to a finite data set size.

Note, also, that mean reverting functionality was implemented on the
infrastructure available in the program, ie., the measurement of avg
and rms to determine the average growth of an equity. There are
probably more expeditious implementations, for example, using a single
or multi pole filter as described in APPENDIX 1 to measure the average
growth of an equity.

PERSISTENCE

The above derivations assume random walk fractal characteristics as a
first order approximation to equity prices. Although adequate for many
types of analysis, empirical evidence indicates that instead of a 50%
chance of consecutive like movements, (as in a random walk fractal,)
many equity prices have slightly greater than a 50% chance, which in
principle, is exploitable. The traditional method of determination of
persistence is by metrics on the Hurst exponent, [Pet91, pp. 61],
[Cas94, pp. 253], [Fed88, pp. 149], and [Sch91, pp. 129]. As an
alternative method, the number of consecutive like movements in an
equity's price can be tallied, and the coefficient of the resultant
distribution determined. For a simple random walk fractal, it will be
the combinatorics, ie., 0.5, or 50%, of finding two like
movements. Persistence means that there is greater than 50%, and is a
metric on what is called "price momentum". Note that this is an
exploitable attribute in "noise trading". For example, if the Hurst
exponent is greater than 0.5, then both up movements, and down
movements, tend to cluster, ie., such strategies as buying after two
consecutive down movements, and selling after two consecutive up
movements, may be viable.

OTHER PROVISIONS

For simulation, the equities are represented, one per time unit.
However, in the "real world," an equity can be represented multiple
times in the same time unit, or not at all. This issue is addressed
by:

    1) If an equity has multiple representations in a single time
    unit, (ie., multiple instances with the same time stamp,) only the
    last is used.

    2) If an equity was not represented in a time unit, then at the
    end of that time unit, the equity is processed as if it was
    represented in the time unit, but with no change in value.

The advantage of this scheme is that, since fractal time series are
self-similar, it does not affect the wagering operations of the
equities in relation to one another.

APPENDIX 1

    Approximating Statistical Estimates to a Time Series with a
    Single Pole Filter

Note: The prototype to this program implemented statistical estimates
with a single pole filter. The documentation for the implementation
was moved to this Appendix. Although the approximation is marginal,
reasonably good results can be obtained with this
technique. Additionally, the time constants for the filters are
adjustable, and, at least in principle, provide a means of adaptive
computation to control the operational dynamics of the program.

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
normalized increments is equal to the square of the
rms. Unfortunately, the measurements of avg and rms must be made over
a long period of time, to construct a very large data set for
analytical purposes do to the necessary accuracy
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
large" must be analyzed quantitatively. For example, Table I is the
statistical estimate for a Shannon probability, P, of a time series,
vs, the number of records required, based on a mean of the normalized
increments = 0.04, (ie., a Shannon probability of 0.6 that is optimal,
ie., rms = (2P - 1) * avg):

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

                 Table I.

where avg is the average of the normalized increments, e is the error
estimate in avg, c is the confidence level of the error estimate, and
n is the number of records required for that confidence level in that
error estimate.  What Table I means is that if a step function, from
zero to 0.04, (corresponding to a Shannon probability of 0.6,) is
applied to the system, then after 27 records, we would be 70%
confident that the error level was not greater than 0.0396, or avg was
not lower than 0.0004, which corresponds to an effective Shannon
probability of 0.51. Note that if many iterations of this example of
27 records were performed, then 30% of the time, the average of the
time series, avg, would be less than 0.0004, and 70% greater than
0.0004. This means that the Shannon probability, 0.6, would have to be
reduced by a factor of 0.85 to accommodate the error created by an
insufficient data set size to get the effective Shannon probability of
0.51. Since half the time the error would be greater than 0.0004, and
half less, the confidence level would be 1 - ((1 - 0.85) * 2) = 0.7,
meaning that if we measured a Shannon probability of 0.6 on only 27
records, we would have to use an effective Shannon probability of
0.51, corresponding to an avg of 0.0004. For 33 records, we would use
an avg of 0.0016, corresponding to a Shannon probability of 0.52, and
so on.

Following like reasoning, Table II is the statistical estimate for a
Shannon probability, P, of a time series, vs, the number of records
required, based on a root mean square of the normalized increments =
0.2, (ie., a Shannon probability of 0.6 that is optimal, ie., rms =
(2P - 1) * avg):

     P     rms       e      c     n
    0.51   0.02    0.18  0.7000  1
    0.52   0.04    0.16  0.7333  1
    0.53   0.06    0.14  0.7667  2
    0.54   0.08    0.12  0.8000  3
    0.55   0.10    0.10  0.8333  4
    0.56   0.12    0.08  0.8667  8
    0.57   0.14    0.06  0.9000  16
    0.58   0.16    0.04  0.9333  42
    0.59   0.18    0.02  0.9667  227
    0.60   0.20    0.00  1.0000  infinity

                 Table II.

where rms is the average of the normalized increments, e is the error
estimate in rms, c is the confidence level of the error estimate, and
n is the number of records required for that confidence level in that
error estimate.  What Table II means is that if a step function, from
zero to 0.2, (corresponding to a Shannon probability of 0.6,) is
applied to the system, then after 1 records, we would be 70% confident
that the error level was not greater than 0.18, or rms was not lower
than 0.02, which corresponds to an effective Shannon probability of
0.51. Note that if many iterations of this example of 1 records were
performed, then 30% of the time, the root mean square of the time
series, rms, would be less than 0.01, and 70% greater than 0.02. This
means that the Shannon probability, 0.6, would have to be reduced
by a factor of 0.85 to accommodate the error created by an
insufficient data set size to get the effective Shannon probability of
0.51. Since half the time the error would be greater than 0.02, and
half less, the confidence level would be 1 - ((1 - 0.85) * 2) = 0.7,
meaning that if we measured a Shannon probability of 0.6 on only 1
record, we would have to use an effective Shannon probability of 0.51,
corresponding to an rms of 0.02. For 2 records, we would use an rms of
0.06, corresponding to a Shannon probability of 0.53, and so on.

And curve fitting to Tables I and II:

     P      avg         e       c
    0.51   0.0004    0.0396  0.7000
    0.52   0.0016    0.0384  0.7333
    0.53   0.0036    0.0364  0.7667
    0.54   0.0064    0.0336  0.8000
    0.55   0.0100    0.0300  0.8333
    0.56   0.0144    0.0256  0.8667
    0.57   0.0196    0.0204  0.9000
    0.58   0.0256    0.0144  0.9333
    0.59   0.0324    0.0076  0.9667
    0.60   0.0400    0.0000  1.0000

     P     n            pole
    0.51  27        0.000059243
    0.52  33        0.000455135
    0.53  42        0.000357381
    0.54  57        0.000486828
    0.55  84        0.000545072
    0.56  135       0.000526139
    0.57  255       0.000420259
    0.58  635       0.000256064
    0.59  3067      0.000086180
    0.60  infinity  -----------

                 Table III.

where the pole frequency, fp, is calculated by:

                   avg
           ln (1 - ----)
                   0.04
    fp = - ------------ ..........................(1.130)
              2 PI n

which was derived from the exponential formula for a single pole
filter, vo = vi ( 1 - e^(-t / rc)), where the pole is at 1 / (2 PI
rc). The average of the necessary poles is 0.000354700, although
an order of magnitude smaller could be used, as could 50% larger.

     P     rms       e      c
    0.51   0.02    0.18  0.7000
    0.52   0.04    0.16  0.7333
    0.53   0.06    0.14  0.7667
    0.54   0.08    0.12  0.8000
    0.55   0.10    0.10  0.8333
    0.56   0.12    0.08  0.8667
    0.57   0.14    0.06  0.9000
    0.58   0.16    0.04  0.9333
    0.59   0.18    0.02  0.9667
    0.60   0.20    0.00  1.0000

     P     n            pole
    0.51  1         0.016768647
    0.52  1         0.035514399
    0.53  2         0.028383290
    0.54  3         0.027100141
    0.55  4         0.027579450
    0.56  8         0.018229025
    0.57  16        0.011976139
    0.58  42        0.006098810
    0.59  227       0.001614396
    0.60  infinity  -----------

                 Table IV.

where the pole frequency, fp, is calculated by:

                   rms
           ln (1 - ---)
                   0.2
    fp = - ------------ ..........................(1.131)
              2 PI n

which was derived from the exponential formula for a single pole
filter, vo = vi ( 1 - e^(-t / rc)), where the pole is at 1 / (2 PI
rc). The average of the necessary poles is 0.019251589, although an
order of magnitude smaller could be used, as could 50% larger.

Tables I, II, III, and IV represent an equity with a Shannon
probability of 0.6, which is about the maximum that will be seen in
the equity markets.  Tables V and VI represent similar reasoning, but
with a Shannon probability of 0.51, which is at the low end of the
probability spectrum for equity markets:

      P       avg           e          c
    0.501   0.000004    0.000396  0.964705882
    0.502   0.000016    0.000384  0.968627451
    0.503   0.000036    0.000364  0.972549020
    0.504   0.000064    0.000336  0.976470588
    0.505   0.000100    0.000300  0.980392157
    0.506   0.000144    0.000256  0.984313725
    0.507   0.000196    0.000204  0.988235294
    0.508   0.000256    0.000144  0.992156863
    0.509   0.000324    0.000076  0.996078431
    0.510   0.000400    0.000000  1.000000000

      P      n           pole
    0.501   10285     0.000000156
    0.502   11436     0.000000568
    0.503   13358     0.000001124
    0.504   16537     0.000001678
    0.505   22028     0.000002079
    0.506   32424     0.000002191
    0.507   55506     0.000001931
    0.508   124089    0.000001310
    0.509   524307    0.000000504
    0.510   infinity  -----------

                 Table V.

where the pole frequency, fp, is calculated by:

                    avg
           ln (1 - ------)
                   0.0004
    fp = - --------------- .......................(1.132)
              2 PI n

which was derived from the exponential formula for a single pole
filter, vo = vi ( 1 - e^(-t / rc)), where the pole is at 1 / (2 PI
rc). The average of the necessary poles is 0.000001282, although
an order of magnitude smaller could be used, as could 70% larger.

      P      rms       e         c
    0.501   0.002    0.018  0.964705882
    0.502   0.004    0.016  0.968627451
    0.503   0.006    0.014  0.972549020
    0.504   0.008    0.012  0.976470588
    0.505   0.010    0.010  0.980392157
    0.506   0.012    0.008  0.984313725
    0.507   0.014    0.006  0.988235294
    0.508   0.016    0.004  0.992156863
    0.509   0.018    0.002  0.996078431
    0.510   0.020    0.000  1.000000000

      P     n            pole
    0.501  3         0.005589549
    0.502  4         0.008878600
    0.503  5         0.011353316
    0.504  8         0.010162553
    0.505  11        0.010028891
    0.506  19        0.007675379
    0.507  36        0.005322728
    0.508  89        0.002878090
    0.509  415       0.000883055
    0.510  infinity  -----------

                 Table VI.

where the pole frequency, fp, is calculated by:

                   rms
           ln (1 - ----)
                   0.02
    fp = - ------------ ..........................(1.133)
              2 PI n

which was derived from the exponential formula for a single pole
filter, vo = vi ( 1 - e^(-t / rc)), where the pole is at 1 / (2 PI
rc). The average of the necessary poles is 0.006974618, although an
order of magnitude smaller could be used, as could 60% larger.

Table V presents real issues, in that metrics for equities with low
Shannon probabilities may not be attainable with adequate precision to
formulate consistent wagering strategies. (For example, 524307
business days is a little over two millenia-the required size of the
data set for day trading.) Another issue is that the pole frequency
changes with magnitude of the Shannon probability, as shown by
comparison of Tables III, V, and IV, VI, respectively. There is some
possibility that adaptive filter techniques could be implemented by
dynamically change the constants in the statistical estimation filters
to correspond to the instantaneous measured Shannon probability. The
equations are defined, below.

Another alternative is to work only with the root mean square values
of the normalized increments, since the pole frequency is not as
sensitive to the Shannon probability, and can function on a much
smaller data set size for a given accuracy in the statistical
estimate. This may be an attractive alternative if all that is desired
is to rank equities by growth, (ie., pick the top 10,) since, for a
given data set size, a larger Shannon probability will be chosen over
a smaller. However, this would imply that the equities are known to be
optimal, ie., rms = 2P + 1, which, although it is nearly true for most
equities, is not true for all equities. There is some possibility that
optimality can be verified by metrics:

                2
    if avg < rms

        then rms = f is too large in Equation (1.12)

                     2
    else if avg > rms

        then rms = f is too small in Equation (1.12)

                  2
    else avg = rms

        and the equities time series is optimal, ie.,
        rms = f = 2P - 1 from Equation (1.36)

These metrics would require identical statistical estimate filters for
both the average and the root mean squared filters, ie., the square of
rms would have the same filter pole as avg, which would be at
0.000001282, and would be conservative for Shannon probabilities above
0.51.

The Shannon probability can be calculated by several methods using
Equations (1.6) and (1.14). Equation (1.14):

        avg
        --- + 1
        rms
    P = -------
           2

has two other useful alternative solutions if it is assumed that the
equity time series is optimal, ie., rms = 2P - 1, and by substitution
into Equation (1.14):

        rms + 1
    P = ------- ..................................(1.134)
           2

 and:

        sqrt (avg) + 1
    P = -------------- ...........................(1.135)
              2

Note that in Equation (1.14) the confidence levels listed in Tables I
and II should be multiplied together, and a new table made for the
quotient of the average and the root mean square of the normalized
increments, avg and rms respectively. However, with a two order of
magnitude difference in the pole frequencies for avg and rms, the
response time of the statistical estimate approximation is dominated
by the avg pole.

The decision criteria will be based on variations of the Shannon
probability, P, and the average and root mean square of the normalized
increments, avg and rms, respectively. Note that from Equation (1.14),
avg = rms (2P - 1), which can be optimized/maximized. P can be
calculated from Equations (1.14), (1.61), or (1.62). The measurement
of the average, avg, and root mean square, rms, of the normalized
increments can use different filter parameters than the root mean
square of multiplier, ie., there can be an rms that uses different
filter parameters, than the RMS in the equation, avg = RMS (2P -
1). By substitution, Equation (1.14) will have a decision criteria of
the largest value of RMS * avg / rms, Equation (1.61) will have a
decision criteria of the largest value of RMS * rms, and Equation
(1.62) will have a decision criteria of RMS * sqrt (avg), or avg.
These interpretations offer an alternative to the rather sluggish
filters shown in Tables I, III, and V, since there can be two sets of
filters, one to perform a statistical estimate approximation to the
Shannon probability, and the other to perform a statistical estimate
on rms, which can be several orders of magnitude faster than the
filters used for the Shannon probability, enhancing dynamic operation.

As a review of the methodology used to construct Tables I, II, III,
IV, V, and VI, the size of the data set was obtained using the
tsstatest(1) program, which can be approximated by a single pole low
pass recursive discreet time filter [Con78], with the pole frequency
at 0.000053 times the time series sampling frequency, for the average
of the normalized increments of the time series, avg. (The rationale
behind this value is that if we consider an equity with a measured
Shannon probability of 0.51-a typical value-and we wish to include an
uncertainty in the precision of this value based on the size of the
data set, then we must decrease the Shannon probability by a factor of
0.960784314. This number comes from the fact that a Shannon
probability, P', would be (0.5 / 0.51) * P = 0.980392157 * P = 0.51 *
0.980392157 = 0.5, a Shannon probability below which, no wager should
be made, (as an absolute lower limit.)  But if such a scenario is set
up as an experiment that was performed many times, it would be
expected that half the time, the measured value Shannon probability
would be greater than 0.51, and half less, than the "real" value of
the Shannon probability. So the Shannon probability must be reduced by
a factor of c = 1 - 2(1 - 0.980392157) = 0.960784314. This value is
the confidence level in the statistical estimate of the measurement
error of the average of the normalized increments, avg, which for a
Shannon probability of 0.51 is 0.0004, since the root mean square,
rms, of the normalized increments of a time series with a Shannon
probability of 0.51 is 0.02, and, if the time series is optimal, where
avg = (2P - 1) * rms, then avg = 0.0004.  So, we now have the error
level, 0.0004, and the required confidence level, 0.960784314, and the
number of required records, ie., the data set size, would be 9773,
from the tsstatest(1) program. From this it can be calculated that the
response to a unit step function, of a filter with the pole frequency
at 0.000053 at the 9773'th record is 0.960784314. Note that at the
single pole filter will provide an approximation to the statistical
estimate that is low for values below this; for example, by
comparison, at 2083 records it will give a confidence level of 0.5,
and the tsstatest(1) program will give a confidence level of 0.5 at
1046 records-a sizeable error, but in a conservative direction.)
Likewise, for the rms, with a Shannon probability of 0.51, the rms
would be 0.02. For an error level of 0.02, below which no wagers
should be made, and a confidence level, c, of c = 1 - 2(1 - (0.5 /
0.51)) = 0.960784314, the data set size would be between 3 and 4
records, or a pole frequency of 0.13 to 0.18. These two values, an rms
pole of 0.15, and an avg pole of 0.000053 probably represent the
highest bandwidth that is attainable.

The advantage of the discreet time recursive single pole filter
approximation to a statistical estimate is that it requires only 3
lines of code in the implementation-two for initialization, and one in
the calculation construct. A "running average" methodology would offer
far greater accuracy as an approximation to the statistical estimate,
however the memory requirements for the average could be prohibitive
if many equities were being tracked concurrently, (see Table V,) and
computational resource requirements for circular buffer operation
could possible be an issue. The other alternative would be to perform
a true statistical estimate, however the architecture of a recursive
algorithm implementation may be formidable.

The single pole low pass filter is implemented from the following
discrete time equation:

    v      = I * k2 + v  * k1 ....................(1.136)
     t + 1             t

where I is the value of the current sample in the time series, v are
the value of the output time series, and k1 and k2 are constants
determined from the following equations:

          -2 * p * pi
    k1 = e            ............................(1.137)

and:

    k2 = 1 - k1 ..................................(1.138)

where p is a constant that determines the frequency of the pole-a
value of unity places the pole at the sample frequency of the time
series.

APPENDIX 2

    Number of Concurrently Held Equities

Note: The prototype to this program was implemented with a user
configurable fixed number of equities in the equity portfolio, as
determined by the reasoning outlined in this Appendix.  This
methodology was superceded by dynamically determining the number of
equities held as outlined in the Section, PORTFOLIO OPTIMIZATION.

The remaining issue is the number of equities held
concurrently. Measuring the average and root mean square of the
normalized increments of many equities (600 equities selected from all
three American markets, 1 January, 1993 to 1 May, 1996,) resulted in
an average Shannon probability of 0.52, and an average root mean
square of the normalized increments of 0.03. Only infrequently was a
volatility found that exceed the optimal, ie., where rms = 2P - 1, by
a factor of 3, (approximately a one sigma limit.) However, once,
(approximately a 3 sigma limit,) a factor of slightly in excess of 10
was found for a short interval of time.  There is a possibility that
the equities with the maximum Shannon probability and maximum growth
will also have a volatility that is 3 times too large. From footnote
[5], the volatilities add root mean square, meaning that to reduce the
capital volatility by a factor 3, 9 equities would have to be held
concurrently, (the reciprocal of the square root of 9 is 1 / 3.) There
is some possibility that the optimal number of equities concurrently
held could be dynamically implemented with an adaptive computation
control construct. For example, the volatility of the capital could be
measured, and the optimal number of concurrently held equities
adjusted dynamically, or the instantaneous volatility of all equities
held could be calculated, and the root mean square of the volatilities
calculated, as the investment is made in each equity. Such a control
loop is complicated by the fact that the equities invested in can have
a minimum Shannon probability requirement, or a minimum decision
criteria specified, ie., there may ambiguous stipulations that effect
the number of equities held concurrently.

This, also, seems consistent Equation (1.127),

    Gp = Pp ln (1 + rmsp) +

         (1 - Pp) ln (1 - rmsp)

and substituting Equation (1.121) for Pp:

         avgp
         ---- + 1
         rmsp
    Gp = -------- ln (1 + rmsp) +
             2

             avgp
         1 - ----
             rmsp
         -------- ln (1 - rmsp) ..................(1.139)
            2

and iterating plots of equities with similar statistical
characteristics as a parameter, (ie., using a P of 0.51, etc., and
plotting the portfolio gain, Gp, with the number of equities held as a
parameter.) There seems to be little advantage in holding more than 10
equities concurrently, which is also consistent with the advice of
many brokers.

APPENDIX 3

    Number of Concurrently Held Equities, and Number of Days Held

For n many equities, (assuming an ergotic Brownian motion model of
equity prices,) and an equal investment in each, the averages of the
marginal increments add linearly in the portfolio:

           avg    avg          avg
              1      2            n
    avgp = ---- + ---- + ... + ---- =
            n      n            n

        1
        - (avg  + avg  + ... + avg ) ............ (1.140)
        n     1      2            n

where avgp is the average of the marginal increments in portfolio
value. The root mean square of the marginal increments, rmsp, is:

                           2             2
    rmsp = sqrt ((rms  / n)  + (rms  / n)  + ...
                     1             2

                    2    1          2      2
        + (rms  / n) ) = - sqrt (rms  + rms  + ...
              n          n          1      2

           2
        rms  ) .................................. (1.141)
           n

and the ratio:

              avg  + avg  + ... + avg
    avgp         1      2            n
    ---- = ------------------------------- ...... (1.142)
    rmsp            2      2            2
           sqrt (rms  + rms  + ... + rms )
                    1      2            n

is useful in the calculation of the Shannon probability, P, of the
portfolio, P = (avgp / rmsp + 1) / 2, which is the likelihood that the
portfolio will increase in value on any given day. Assuming all
equities have identical fractal statistics, the average of the
marginal increments in the portfolio's value would be avg, (ie., n
many, divided by n,) and the root mean square of the marginal
increments, (ie., the volatility,) would be rms / sqrt (n), (ie., sqrt
(n) / n.)

For one equity, held N many days, (assuming an ergotic Brownian motion
model of equity prices,) the average of the marginal increments at the
end of the N'th day would be the sum of the daily marginal increments:

    avgp = avg  + avg  + ... + avg  ............. (1.143)
              1      2            N

and the root mean square of the marginal increments at the end of the
N'th day would be:

    rmsp = sqrt (rms  + rms  + ... + rms ) ...... (1.144)
                    1      2            N

and the ratio:

              avg  + avg  + ... + avg
    avgp         1      2            N
    ---- = ------------------------------- ...... (1.145)
    rmsp   sqrt (rms  + rms  + ... + rms )
                    1      2            N

is useful in the calculation of the Shannon probability, P, of the
portfolio, P = (avgp / rmsp + 1) / 2, which is the likelihood that the
portfolio will increase in value at the end of the N'th day. If the
statistics are stationary for N many days, then: the average of the
marginal increments in the portfolio's value would be N * avg, (ie., N
many,) and the root mean square of the marginal increments would be
sqrt (N) * rms, (ie., the square root of N many.)

Combining Equations (1.142) and (1.145), (again, assuming all equities
have identical fractal statistics, and an ergotic Brownian motion
model of equity prices,) the average of the marginal increments, avgp,
of the portfolio, for n many equities, held N many days, would be:

    avgp = N * avg .............................. (1.146)

and the root mean square, rmsp:

                 N
    rmsp = sqrt (-) * rms ....................... (1.147)
                 n

Note that if rmsp = avgp, then the Shannon probability, (ie., the
likelihood of an up movement,) from Equation (1.14) would be unity,
implying a no risk investment strategy:

                                  N
    avgp = rmsp = N * avg = sqrt (-) * rms ...... (1.148)
                                  n

and solving:

                   rms
    sqrt (n * N) = --- .......................... (1.150)
                   avg

For example, if rms = 0.02, and avg = 0.0004, (typical for the US
equity markets,) then n * N = 2500, or holding 10 equities, for 250
trading days, (or about a calendar year,) would be a safe strategy.

However, if the averages of the marginal increments for the stocks
differ, and/or if the root mean square of the marginal increments for
the stocks differ, the optimum fraction, f, of the portfolio to invest
in each stock, n, can be approximated from Equation (1.36).

    f  = 2P  - 1 .................................(1.151)
     n     n

where:

         avg
            n
         ---- + 1
         rms
            n
    P  = -------- ................................(1.152)
     n       2

The heuristic arguments justifying the approximation are that in a
universe of stocks, by investing in each and every stock, the
portfolio growth characteristics will be a nearly smooth exponential,
since the root mean square of the marginal increments of the stocks
add root mean square, and the average of the marginal increments, add
linearly, as per Equations (1.143) and (1.144). Equation (1.151)
assumes that the remainder of the portfolio:

    1 - f  .......................................(1.152)
         n

is not at risk. If there are sufficiently many stocks in the
portfolio, then Equation (1.151) can be used to hedge the fluctuations
in value of the n'th stock, against the combined values of the other
stocks in the portfolio, which is regarded, taken cumulative, as
stable.

However, Equation (1.150) indicates that there is very little marginal
utility in adding more than ten stocks to the portfolio, implying that
Equation (1.151) would be an adequate approximation to optimizing
asset allocation in the portfolio.

Note that for the approximation to be valid, 0.0 << avg << rms << 1.0
for all stocks in the universe with a sufficiently large number of
choices.

APPENDIX 4

    Optimal Margin Buying

It is usually, but not always, the case that a well balanced portfolio
will not have sufficient risk to simultaneously maximize the
portfolio's gain, Gp, while at the same time minimizing the risk of
investing in the portfolio. From Equation (1.151), the optimal value
at risk, (f, which is VaR,) should equal rmsp, or the margin factor,
M, would be:

        2Pp - 1
    M = -------  .................................(1.153)
         rmsp

where the fraction of the portfolio bought on margin would be 1 - (1 /
M).

FOOTNOTES

[1] For example, if a = 0.06, or 6%, then at the end of the first time
interval the capital would have increased to 1.06 times its initial
value.  At the end of the second time interval it would be (1.06 *
1.06), and so on.  What Equation (1.1) states is that the way to get
the value, V in the next time interval is to multiply the current
value by 1.06. Equation (1.1) is nothing more than a "prescription,"
or a process to make an exponential, or "compound interest"
mechanism. In general, exponentials can always be constructed by
multiplying the current value of the exponential by a constant, to get
the next value, which in turn, would be multiplied by the same
constant to get the next value, and so on.  Equation (1.1) is a
construction of V (t) = exp(kt) where k = ln(1 + a). The advantage of
representing exponentials by the "prescription" defined in Equation
(1.1) is analytical expediency. For example, if you have data that is
an exponential, the parameters, or constants, in Equation (1.1) can be
determined by simply reversing the "prescription," ie., subtracting
the previous value, (at time t - 1,) from the current value, and
dividing by the previous value would give the exponentiating constant,
(1 + at). This process of reversing the "prescription" is termed
calculating the "normalized increments."  (Increments are simply the
difference between two values in the exponential, and normalized
increments are this difference divided by the value of the
exponential.) Naturally, since one usually has many data points over a
time interval, the values can be averaged for better precision-there
is a large mathematical infrastructure dedicated to these types of
precision enhancements, for example, least squares approximation to
the normalized increments, and statistical estimation.

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

[3] Equation (1.3) is interesting in many other respects.  For
example, adding a single term, m * V(t - 1), to the equation results
in V(t) = v(t - 1) (1 + f(t) * F(t) + m * V(t - 1)) which is the
"logistic," or 'S' curve equation,(formally termed the "discreet time
quadratic equation,") and has been used successfully in many unrelated
fields such as manufacturing operations, market and economic
forecasting, and analyzing disease epidemics [Mod92, pp. 131]. There
is continuing research into the application of an additional
"non-linear" term in Equation (1.3) to model equity value
non-linearities. Although there have been modest successes, to date,
the successes have not proven to be exploitable in a systematic
fashion [Pet91, pp. 133]. The reason for the interest is that the
logistic equation can exhibit a wide variety of behaviors, among them,
"chaotic." Interestingly, chaotic behavior is mechanistic, but not
"long term" predictable into the future. A good example of such a
system is the weather. It is an important concept that compound
interest, the logistic function, and fractals are all closely related.

[4] In this Section, "root mean square" is used to mean the variance
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
computer source codes available for both.  See the programs tsrms(1)
and tsavg(1).  The method used is not consequential.

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
example, Equation (1.6) could be modified by dividing both the
normalized increments, and the square of the normalized increments by
the daily trading volume.  The quotient of the normalized increments
divided by the trading volume is the instantaneous, average, avg, of
the equity, on a per-share basis.  Likewise, the square root of the
square of the normalized increments divided by the daily trading
volume is the instantaneous root mean square, rmsf, of the equity on a
per-share basis, ie., its instantaneous volatility of the equity.
(Note that these instantaneous values are the statistical
characteristics of the equity on a per-share bases, similar to a coin
toss, and not on time.)  Additionally, it can be shown that the
range-the maximum minus the minimum-of an equity's value over a time
interval will increase with the square root of of the size of the
interval of time [Fed88, pp. 178]. Also, it can be shown that the
number of expected equity value "high and low" transitions scales with
the square root of time, meaning that the cumulative distribution of
the probability of an equity's "high or low" exceeding a given time
interval is proportional to the reciprocal of the square root of the
time interval, (or, conversely, that the probability of an equity's
"high or low" exceeding a given time interval is proportional to the
reciprocal of the time interval raised to the power 3/2 [Sch91,
pp. 153]. What this means is that a histogram of the "zero free"
run-lengths of an equity's price would have a 1 / (l^3/2)
characteristic, where l is the length of time an equity's price was
above or below "average.")

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

[11] Note that in a time interval of sufficiently many tosses of the
coin, say N many, that there will be PN many wins, and (1 - P)N many
losses. In each toss, the gambler's capital, V, increased, or
decreased by an amount f = rms. So, after the first iteration, the
gambler's capital would be V(1) = V(0) (1 + rms F(1)), and after the
second would be V(2) = V(0) (1 + rms F(1)) (1 + rms F(2)), and after
the N'th, V(N) = V(0) (1 + rms F(1)) (1 + rms F(2)) ... (1 + rms
F(N)), where F is either plus or minus unity.  Since the
multiplications are transitive, the terms may be rearranged, and there
will be PN many wins, and (1 - P) many losses, or V(N) = V(0) * (1 +
rms)^(P) * (1 - rms)^(1 - P). Dividing both sides by V(0), the
starting value of the gambler's capital, and taking the logarithm of
both sides, results in ln (V(N) / V(0)) = P ln (1 + rms) + (1 - P) ln
(1 - rms), which is the equation for G = ln (V(N) / V(0)), the average
exponential rate of growth over N many tosses, providing that N is
sufficiently large. Note that the "effective interest rate" as
expressed in Equation (1.1), is a = exp (G) - 1.

[12] If the plotting program "gnuplot" is available, then the
following commands will plot Equation (1.127) using the method of
computation for the Shannon probability from Equations (1.121) through
(1.126), (1.115) through (1.117), and, (1.118) through (1.120),
respectively.

    plot [1:50] ((1 + (0.02 / sqrt (x))) **
        ((((0.0004 / 0.02) * sqrt (x)) + 1) / 2)) *
        ((1 - (0.02 / sqrt (x))) **
        ((1 - ((0.0004 / 0.02) * sqrt (x))) / 2))

    plot [1:50] ((1 + (0.02 / sqrt (x))) **
        (((0.02 / sqrt (x)) + 1) / 2)) *
        ((1 - (0.02 / sqrt (x))) **
        ((1 - (0.02 / sqrt (x))) / 2))

    plot [1:50] ((1 + (sqrt (0.0004) / sqrt (x))) **
        ((sqrt (0.0004) + 1) / 2)) *
        ((1 - (sqrt (0.0004) / sqrt (x))) **
        ((1 - sqrt (0.0004)) / 2))

BIBLIOGRAPHY

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

[Kel56] J. L. Kelly, Jr. "A New Interpretation of Information Rate",
Bell System Tech. J. vol. 35, pp. 917-926, 1956,
http://www.bjmath.com/bjmath/kelly/kelly.pdf.

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

PROGRAM ARCHITECTURE

I) Data architecture:

    A) Each equity has a data structure, of type HASH, that contains
    the statistical information on the fluctuations in the equity's
    value. The structure is maintained in a hash table, referenced by
    the equity's name. (The elements HASH *previous and HASH *next are
    used for the maintenence of the hash lookup table, and the element
    char *hash_data references the equity's name.)

        1) Additionally, each HASH structure has elements for two
        linked list constructs:

            a) A singly linked list of all HASH structures, ie., a
            list of all equities. This list is referenced by the
            global HASH *decision_list.  This list is constructed
            using the element HASH *next_decision, and is used for two
            purposes:

                i) It is sorted, by a linked list quick sort function,
                static void qsortlist (), to order the list into
                decreasing desirability of the equities, based on the
                value of the double decision element in the HASH
                structure, (which is set by the int static decisions
                () function, using the data compiled by the static int
                statistics () function.)

                ii) To provide an access means to each equity's HASH
                structure for update at the end of a time interval,
                (ie., "spin" this list to update the statistics for
                each equity with the data collected in the last time
                interval.)

            b) A singly linked list of the HASH structures that are
            currently invested in, using the element HASH
            *next_investment. This list is referenced by the global
            HASH *invested_list, and is set up in the static void
            invest () function.)

II) Data architecture manipulation functions:

    A) The hash lookup operations are performed by the functions,
    static int hash_init (), static int hash_insert (), and static
    HASH *hash_find ().

    B) The HASH data structures for the equities are ordered, in
    descending order of desirability, by the function static void
    qsortlist (), which is called at the beginning of the static void
    invest () function, prior to making investments for the next time
    interval.

    C) The function static int statistics () is used to calculate the
    "running" statistics for each equity, (ie., the rms, avg, Shannon
    probability, etc.,) using the data from each equity's HASH
    structure. There are three functions used in these calculations to
    perform statistical estimation:

        1) static double confidencerms ().

        2) static double confidenceavg ().

        3) static double confidenceavgrms ().


    D) The function static int decisions () is used to decide the
    desirability of an equity, using the data generated by the
    function static int statistics ().

    E) The function static void invest () is used to analyze/optimize
    the investment in the equities, using the data contained from each
    equity's HASH structure.

    F) The function int static invest_decisions () is used by the
    function static void invest () to assemble the portfolio.

    G) The function static void printstocks () is used to print the
    equities invested in for the next time interval.

    H) The static int statistics (), static int decisions (), and then
    the static void invest (), which calls static int invest_decisions
    (), and then static void printstocks (), functions are called at
    the conclusion of a time interval to update the statistics of all
    equities, then to invest in the equities with the most
    desirability, based on the statistics.

III) Program description:

    A) The function main serves to read the input file, and dispatch
    to appropriate data handling functions, in the following order:

        1) handle any command line arguments.

        2) Open the input file.

        3) For each record in the input file:

            a) Parse the record using the function static int strtoken
            (), checking that the record has exactly 3 fields, and if
            it does, then check that the equity's value represented by
            this record is greater than zero. (Note: many of the data
            handling functions will exhibit numerical exceptions with
            data values of zero, or less-this is the only protection
            from numerical exceptions in the program.)

            b) Lookup the equity's HASH structure represented by the
            record using the function static HASH *get_stock (). (The
            function get_stock () will return the structure if it
            exists, or create one if it doesn't.)

            c) Compare the time stamp of the record with the time
            stamp of the previous record, and if it is different, call
            the function static int update_stocks () to update the
            statistics for all equities for the last time interval,
            which in turn, calls the function static int statistics
            (), followed by the function static int decisions (), to
            calculate the statistics for all equities by "spinning"
            through the list of all equities, (using the HASH element
            HASH *next_decision.)  After the statistics for all
            equities has been updated, call the function static void
            invest (), to make the investments for the next time
            interval.  Note: the time stamps of the records are not
            used, and have no lexical or order meaning to the
            program-only that they must change to signify that a time
            interval has ended. They may be different for each record,
            which would imply real time "ticker" operation.

            d) Save the data contained in the record in the equity's
            HASH structure.

        4) At EOF of the input file, repeat III)A)3)c) for the last
        time interval, and close the input file. Note: if it is
        desired to implement a "dump and restart" mechanism, (ie., use
        this program to maintain a database of market statistics,)
        this is the appropriate place to insert the code. The data
        structure size for this program for an entire market is
        modest, and the HASH structures can be dumped, and reloaded
        when the program re-starts-a significant advantage over
        maintaining historical market data.

IV) Comments on various functions used in the program:

    A) The function static int main () is used only to read data from
    the input file, into the individual HASH structures for each
    equity, and call update functions when a time change is detected
    in the input file.

    B) The function static void invest () is probably the most
    modified function in the program-it is where the investment
    strategy, and portfolio management occurs. The singly linked list,
    maintained by the HASH element HASH *next_investment, is the list
    of equities invested in at any time. At the beginning of this
    function, the list is read, returning all investments into
    capital, (ie., making the list null, which means no investments.)
    The list of all equities is then sorted, using the static void
    qsortlist () function, into descending order of desirability of
    equities, and a new list constructed of equities that are invested
    in, and the process repeated to EOF of the input file.

    C) The next most modified function in the program is static int
    statistics (), where the statistics for each equity, for each time
    interval, is calculated. This function maintains a running average
    and root mean square of the normalized increments for each
    equity's HASH structure. Depending on the method used to calculate
    the Shannon probability, (one of enum decision_method,) one of
    three functions will be called from a switch construct to compute
    the statistical estimation information used by the function static
    void invest (). (The statistical estimation functions are, static
    double confidencerms (), static double confidenceavg (), and
    static double confidenceavgrms ()-there is a forth function called
    the first time by any of these three functions that sets up the
    data table structure used by the statistical estimation
    algorithms. See the sources for details.)  Note that there are
    only two numerical exceptions in the statistical methodology, avg
    being negative, or rms being zero, (which can happen on very small
    data sets.) These are detected, and non-harmful data returned as a
    method of numerical exception handling. Note that it is not a
    requirement that the statistics of the history of the entire
    market be maintained by this program. A window approach would also
    be permissable, perhaps implemented with a fixed length circular
    buffer to calculate the "moving" root mean square and average of
    the normalized increments of each equity.  Such a scheme may have
    advantages in exploiting the dynamics of the market.

    D) The function static int update_stocks () is called at the end
    of every time interval, to update the statistics for each and
    every equity. This is significantly faster than updating the data
    directly as input from the input file. The "index," ie., value of
    the aggregate market of all equities, is calculated in this
    function. This architecture has the following advantages:

        1) It is permissable for a single equity to have multiple
        updates from the input file in any time interval-something
        that shouldn't be a requirement, but frequently happens on
        real time "tickers." Note that the statistics for the equity
        will be calculated for such a scenario only at the end of a
        legitimate time interval, using the last, ie., latest, values
        from the input file.

        2) It is permissable for equities not to be represented in a
        time interval, since, under such a scenario, the equities
        statistics will be calculated anyway, with a no-change in
        equity value, ie., the statistical information for the equity
        will remain valid, in relation to the other equities in the
        market.

    E) The function static int strtoken () parses input records into
    fields.  Field delimiters are a sequence of one or more of the
    white space characters as defined by #define
    TOKEN_SEPARATORS. Comment records are discerned in static int main
    () as a record that begins with a '#' character.

    F) The defines #define PUSHDECISION(x), #define PUSHINVESTMENT(y),
    and #define POPINVESTMENT(), are used for formilization of list,
    or stack operations on the linked lists described in I)A)1),
    above, for robustness considerations.

    G) The defines typedef HASH LIST, typedef LIST *list, and #define
    element_comp(x,y) are used by the quick sort function, static void
    qsortlist (), which is described in the sources. Its use in the
    program is to sort the linked list, as described in I)A)1)a)i),
    above.

    H) The #define SIGMAS, #define STEPS_PER_SIGMA, and #define
    MIDWAY(a,b), are used by the statistical estimation computation
    functions, and define the number of sigma accuracy, and steps per
    sigma, respectively. These functions perform an iterated search
    for solution to the statistical estimation problem, as described
    in the Section entitled DATA SET SIZE CONSIDERATIONS, above, and
    II)C).

    I) The functions static int hash_init (), static int hash_insert
    (), static HASH *hash_find (), static void hash_term (), static
    int hash_text (), static int text_cmphash (), static HASH
    *text_mkhash (), and static void text_rmhash (), make up the hash
    lookup table system, which are described in the sources. Each HASH
    structure is initialized in the function static HASH *text_mkhash
    ().

    J) The function static int tsgetopt () is the same as the standard
    unix getopt (1). The reason for including it in the sources was to
    resolve portability issues, as to where the various variables are
    declared in the "dot h" files. In some systems, it is declared in
    unistd.h, in others, getopt.h, which would require finding the
    declarations prior to compile time, and altering the sources
    accordingly. Since the function is small, it is included to avoid
    having to search the system in a configure/setup phase.

V) Notes and asides:

    A) The program flow follows the derivation, and many of the
    computational formulas were transcribed from the text. Although
    this may enhance clarity, it is probably not in the best interest
    of expeditious computation.

    B) The programming stylistics used were to encourage modifications
    to the program without an in depth understanding of
    programming. Specifically, although the program is capable of
    operating real time on the US equity market tickers, if efficiency
    is an issue, using indirect referencing on doubles that are passed
    as arguments to functions, and implementing implicit addresses of
    arrays with pointers would be recommended.

VI) Constructional and stylistic issues follow, generally, a
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
$Id: tsinvest.c,v 1.7 2006/01/07 10:05:09 john Exp $
$Log: tsinvest.c,v $
Revision 1.7  2006/01/07 10:05:09  john
Initial revision


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

#ifndef PI /* make sure PI is defined */

#define PI 3.14159265358979323846 /* pi to 20 decimal places */

#endif

static char rcsid[] = "$Id: tsinvest.c,v 1.7 2006/01/07 10:05:09 john Exp $"; /* program version */
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
    "Optimal concurrent investments in equities\n",
    "Usage: tsinvest [-a 0|1|2] [-C] [-c] [-D D] [-d 1|2|3|4|5|6] [-I I] [-i]\n",
    "                [-j] [-M M] [-m m] [-o o] [-P] [-p] [-q q] [-r] [-s] [-t]\n",
    "                [-u] [-v] [filename]\n",
    "    -a, optimize asset allocation for each stock held, (0):\n",
    "        -a 0: equal asset allocation.\n",
    "        -a 1: maximize gain.\n",
    "        -a 2: minimize risk.\n",
    "    -C, don't compensate the Shannon probability, P, for data set size\n",
    "    -c, compensate the Shannon probability, P, for run length duration\n",
    "    -D D, D = minimum growth in value of an equity, as calculated by the\n",
    "              method specified by the -d argument, (1.0)\n",
    "    -d d, d = method of calculating growth in value of an equity, G, (1):\n",
    "        -d 1: G = (1 + rms)^P * (1 - rms)^(1 - P), P = ((avg / rms) + 1) / 2.\n",
    "        -d 2: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (rms + 1) / 2.\n",
    "        -d 3: G = (1 + sqrt (avg))^P * (1 - sqrt (avg))^(1 - P),\n",
    "              P = (sqrt (avg) + 1) / 2.\n",
    "        -d 4: G = (1 + rms)^P * (1 - rms)^(1 - P),\n",
    "              P = erf (1 / sqrt (run length)).\n",
    "        -d 5: G = (1 + rms)^P * (1 - rms)^(1 - P),\n",
    "              P = Hurst exponent =\n",
    "              short term persistence.\n",
    "        -d 6: G = random.\n",
    "    -I I, I = initial capital, (1000)\n",
    "    -i, print the average index of all stocks in the output time series\n",
    "    -j, index = average value of stocks, instead of average balanced growth\n",
    "    -M M, M = maximum number of stocks to invest in concurrently, (10)\n",
    "    -m m, m = minimum number of stocks to invest in concurrently, (10)\n",
    "    -o o, o = maximum acceptable marginal increment in stock's value, (1)\n",
    "    -P, preclude calculating statistics for stocks not updated in interval\n",
    "    -p, preclude investing in stocks not updated in interval\n",
    "    -q q, maximum margin fraction, (0.0)\n",
    "    -r, dump internal data on exit, as comments in tsinvestsim(1) format\n",
    "    -s, print the names of stocks held in the output time series\n",
    "    -t, print the time stamps in the output time series\n",
    "    -u, reverse the sense of the decision criteria\n",
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

enum decision_method /* method of computation for determination of a stock's decision criteria, used in stock selection */
{
    M_AVGRMS, /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (avg / rms + 1) / 2 */
    M_RMS, /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (rms + 1) / 2 */
    M_AVG, /* decision criteria: G = (1 + sqrt (avg))^P * (1 - sqrt (avg))^(1 - P), P = (sqrt (avg) + 1) / 2 */
    M_LENGTH, /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = erf (1 / sqrt (run length)) */
    M_PERSISTENCE, /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = Hurst exponent = short term persistence */
    M_RANDOM /* decision criteria: random G = random */
};

enum allocation_method /* method of asset allocation for determination of fraction of portfolio invested in each stock */
{
    M_EQUAL, /* equal allocation */
    M_MAXIMUM_GAIN, /* allocate for maximum gain */
    M_MINIMUM_RISK /* allocate for minimum risk */
};

typedef struct persistence /* persistence structure, one per number of consecutive like movements for each stock, both positive and negative */
{
    int count; /* count of consecutive like movements */
    double rootmean; /* sum of variances of marginal increments of consecutive like movements */
} PERSISTENCE;

typedef struct hash_struct /* hash structure for each stock */
{
    struct hash_struct *previous, /* reference to next element in hash's doubly, circular linked list */
                       *next, /* reference to previous element in hash's doubly, circular linked list */
                       *next_decision, /* reference to next element in qsortlist ()'s sort of the decision criteria list */
                       *next_investment, /* reference to next element in invested list */
                       *next_print; /* reference to next element in print list */
    char *hash_data;  /* stock tick identifier, which is the hash key element */
    int transactions, /* number of changes in this stock's value */
        count, /* count of avg or rms values in the running sum of avg and rms values */
        voidcount, /* count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
        comp, /* compensate the Shannon probability, P, for run length duration flag, 0 = no, 1 = yes */
        noest, /* don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate */
        positive_consecutive, /* running number of consecutive up movements */
        negative_consecutive, /* running number of consecutive down movements */
        positive_size, /* number of elements in the positive_histogram */
        negative_size, /* number of elements in the positive_histogram */
        current_updated, /* updated in current interval flag, 0 = no, 1 = yes */
        last_updated, /* updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
        invest_update, /* invest only if stock has been updated in current interval flag, 0 = no, 1 = yes */
        stats_update; /* don't calculate stock's statistics if it has not been updated in the current interval flag, 0 = no, 1 = yes */
    double currentvalue, /* current value of stock */
           lastvalue, /* last value of stock */
           start_value, /* the start value of stock */
           consecutive_start, /* start value for a run of consecutive like movements */
           capital, /* amount of capital invested in the stock */
           fraction, /* normalized increment of the stock's value */
           Gn, /* normalized growth, ie., the value of the stock if its initial value was one dollar */
           Par, /* Shannon probability, using avg and rms */
           Pa, /* Shannon probability, using avg */
           Pr, /* Shannon probability, using rms */
           Pt, /* mean reverting probability */
           Pp, /* persistence probability */
           Pconfar, /* the confidence level in the measurment accuracy of the Shannon probability, using avg and rms */
           Pconfa, /* the confidence level in the measurment accuracy of the Shannon probability, using avg */
           Pconfr, /* the confidence level in the measurment accuracy of the Shannon probability, using rms */
           Peffar, /* effective Shannon probability, using avg and rms, compensated for measurement accuracy by statistical estimate */
           Peffa, /* effective Shannon probability, using avg, compensated for measurement accuracy by statistical estimate */
           Peffr, /* effective Shannon probability, using rms, compensated for measurement accuracy by statistical estimate */
           Pefft, /* effective Shannon probability, using mean reverting probability, compensated for measurement accuracy by statistical estimate */
           Peffp, /* effective Shannon probability, using persistence probability, compensated for measurement accuracy by statistical estimate */
           Pcomp, /* compensation for run length duration for Shannon probability */
           avgsum, /* running sum of avg values */
           avg, /* average of the normalized increments, avg */
           rmssum, /* running sum of rms values */
           rms, /* root mean square of the normalized increments, rms */
           rootmean, /* variances of marginal increments of consecutive like movements */
           maxinc, /* maximum acceptable marginal increment in stock's value */
           decision, /* decision criteria for investment in a stock, qsortlist () will sort the list of next_decision elements by this value */
           allocation_fraction, /* the fraction of the portfolio that is to be allocated to a stock */
           allocation_percentage; /* the percentage of the portfolio that is to be allocated to a stock */
    PERSISTENCE *positive_histogram, /* consecutive up movments histogram */
                *negative_histogram; /* consecutive down movments histogram */
    enum decision_method method; /* method used to calculate the Shannon probability, one of enum decision_method */
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

HASH *print_list  = (HASH *) 0; /* reference to print list-stocks that have investments in them are in this list, in reverse order of invested_list */

HASH *invested_list_temp; /* temporary reference to element in invested list, used by POPINVESTMENT() */

#define PUSHDECISION(x) (x)->next_decision = decision_list; decision_list = (x) /* method to push a HASH element on the decision criteria list, this pushes a HASH struct for sorting by qsortlist () */

#define PUSHINVESTMENT(x) (x)->next_investment = invested_list; invested_list = (x) /* method to push a HASH element on the investment list */

#define PUSHPRINT(x) (x)->next_print = print_list; print_list = (x) /* method to push a HASH element on the print list */

#define POPINVESTMENT() invested_list; invested_list_temp = invested_list; invested_list = invested_list->next_investment; invested_list_temp->next_investment = (HASH *) 0 /* method to pop a HASH element from the investment list */

#define SIGMAS 3 /* 3 sigma limit, ie., 0 to 3 sigma */

#define STEPS_PER_SIGMA 1000 /* each sigma has 1000 steps of granularity */

#define MIDWAY(a,b) (((a) + (b)) / 2) /* bisect a segment of the confidence array */

#define GAIN(rms,P) ((pow (((double) 1.0 + (rms)), (P))) * (pow (((double) 1.0 - (rms)), ((double) 1.0 - (P))))) /* calculate the calculated growth in stock value, ((1 + rms)^P) * ((1 - rms)^(P - 1)) */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static HASH *get_stock (HASHTABLE *stock_table, void *name, enum decision_method method, int comp, int noest, int update_invest, int update_stats, double maxinc, double currentvalue); /* get a stock from the hash table */
static int update_stocks (HASH *stock_list, enum allocation_method allocate_assets, int margin_buy); /* update the list of stocks */
static int statistics (HASH *stock); /* calculate the statistics for a stock */
static int decisions (HASH *stock, enum allocation_method allocate_assets); /* set the decision criteria for a stock */
static void cumulativenormal (void); /* construct the cumulative of the normal distribution */
static double confidencerms (HASH *stock); /* calculate the compensated Shannon probability using P = (rms + 1) / 2 */
static double confidenceavg (HASH *stock); /* calculate the compensated Shannon probability using P = (sqrt (avg) + 1) / 2 */
static double confidenceavgrms (HASH *stock); /* calculate the compensated Shannon probability using P = (avg / rms + 1) / 2 */
static double normal (double n); /* lookup the value of the normal probability function */
static void invest (int minimum_n, int maximum_n, double minimum_decision, enum allocation_method allocate_assets); /* invest in the stocks */
static int invest_decisions (HASH *stock, int minimum_n, double minimum_decision, int maximum_n); /* decide whether to invest in a stock */
static void printstocks (int verboseprint, char *time_stamp, int timeprint, int indexprint, int index_type, enum allocation_method allocate_assets, int margin_buy); /* print the stocks invested in */
static int strtoken (char *string, char *parse_array, char **parse, const char *delim); /* parse a record based on sequential delimiters */
static int hash_init (HASHTABLE *hash_table); /* initialize the hash table */
static int hash_insert (HASHTABLE *hash_table, void *data); /* insert a key and data into the hash table */
static HASH *hash_find (HASHTABLE *hash_table, void *data); /* find data in the hash table */

#ifdef HASH_DELETE

static int hash_delete (HASHTABLE *hash_table, void *data); /* delete data from the hash table, not currently used, but for possible future use */

#endif

static void hash_term (HASHTABLE *hash_table); /* remove a hash table */
static int hash_text (HASHTABLE *hash_table, void *key); /* compute the hash value for a text key */
static int text_cmphash (void *data, HASH *element); /* function to compare a text key with a hash table's element key */
static HASH *text_mkhash (void *data); /* function to allocate a text hash table element and data */
static void text_rmhash (HASH *element); /* function to deallocate a text hash table element and data */
static void qsortlist (list *top, list bottom); /* quick sort a linked list */
static int tsgetopt (int argc, char *argv[], const char *opts); /* get an option letter from argument vector */

#else

static void print_message (); /* print any error messages */
static HASH *get_stock (); /* get a stock from the hash table */
static int update_stocks (); /* update the list of stocks */
static int statistics (); /* calculate the statistics for a stock */
static int decisions (); /* set the decision criteria for a stock */
static void cumulativenormal (); /* construct the cumulative of the normal distribution */
static double confidencerms (); /* calculate the compensated Shannon probability using P = (rms + 1) / 2 */
static double confidenceavg (); /* calculate the compensated Shannon probability using P = (sqrt (avg) + 1) / 2 */
static double confidenceavgrms (); /* calculate the compensated Shannon probability using P = (avg / rms + 1) / 2 */
static double normal (); /* lookup the value of the normal probability function */
static void invest (); /* invest in the stocks */
static int invest_decisions (); /* decide whether to invest in a stock */
static void printstocks (); /* print the stocks invested in */
static int strtoken ();  /* parse a record based on sequential delimiters */
static int hash_init ();  /* initialize the hash table */
static int hash_insert (); /* insert a key and data into the hash table */
static HASH *hash_find (); /* find data in the hash table */

#ifdef HASH_DELETE

static int hash_delete (); /* delete data from the hash table, not currently used, but for possible future use */

#endif

static void hash_term ();  /* remove a hash table */
static int hash_text ();  /* compute the hash value for a text key */
static int text_cmphash (); /* function to compare a text key with a hash table's element key */
static HASH *text_mkhash (); /* function to allocate a text hash table element and data */
static void text_rmhash (); /* function to deallocate a text hash table element and data */
static void qsortlist ();  /* quick sort a linked list */
static int tsgetopt (); /* get an option letter from argument vector */

#endif

static HASHTABLE text_table = {2729, (HASH *) 0, text_mkhash, text_cmphash, text_rmhash, hash_text}; /* declare the hash table descriptor for text keys */

static int stocks = 0; /* the number of stocks encoutered in the input file */

static double confidence[SIGMAS * STEPS_PER_SIGMA]; /* the array of confidence levels, ie., the cumulative of the normal distribution */

static int sigma_limit = SIGMAS * STEPS_PER_SIGMA; /* the array size of the array of confidence levels, ie., SIGMAS * STEPS_PER_SIGMA, for calculation expediency */

static int cumulativeconstructed = 0; /* flag to determine whether the cumulative normal distribution array, confidence[], has been set up, 0 = no, 1 = yes */

static double capital = (double) 1000.0; /* capital invested in all stocks */

static double portfolio; /* portfolio value */

static double average = (double) 1000.0; /* average index, computed on the initial capital invested in all stocks */

static double average_value = (double) 0.0; /* average value of index, computed as the average value of a share of stock */

static int u = 0; /* reverse the decision criteria sense flag, 0 = no, 1 = yes */

static double sqrt_2; /* square root of 2 for math expediency */

static char *optarg; /* reference to vector argument in tsgetopt () */

static int optind = 1; /* count of arguments in tsgetopt () */

static double margin_reciprocal = (double) 1.0; /* 1 - (1 / margin_reciprocal) = how much of the portfolio can be bought on margin */

static double max_margin_reciprocal = (double) 1.0; /* 1 - (1 / max_margin_reciprocal) = how much of the portfolio can be bought on margin, maximum */

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

    int retval = NOERROR, /* return value, assume no error */
        period_counter = 0, /* period counter, incremented when time_stamp changes, which changes when the first field of the records change */
        fields, /* number of fields in a record */
        I = 0, /* print the average index in the time series flag, 0 = no, 1 = yes */
        j = 0, /* index = average value of stock, instead of average balanced growth flag, 0 = no, 1 = yes */
        minimum_n = 10, /* minimum number of stocks to invest in concurrently */
        maximum_n = 10, /* maximum number of stocks to invest in concurrently */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        p = 0, /* invest only if stock has been updated in current interval flag, 0 = no, 1 = yes */
        P = 0, /* don't calculate stock's statistics if it has not been updated in the current interval flag, 0 = no, 1 = yes */
        q = 0, /* optimize margin buying flag, 0 = no, 1 = yes, */
        r = 0, /* dump internal data on exit flag, 0 = no, 1 = yes */
        s = 0, /* print the stock(s) flag, 0 = no, 1 = yes */
        comp = 0, /* compensate the Shannon probability, P, for run length duration flag, 0 = no, 1 = yes */
        noest = 0, /* don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate */
        c; /* command line switch */

    double currentvalue, /* current value of stock */
           minimum_decision = (double) 1.0, /* minimum decision critera, below which, a stock will not be invested in */
           o = (double) 1.0, /* maximum acceptable marginal increment in stock's value */
           h; /* Hurst exponent, used with the -r option */

    FILE *infile = stdin; /* reference to input file */

    HASH *stock;  /* reference to hash table stock element */

    enum decision_method method = M_AVGRMS; /* method of computation for determination of a stock's decision criteria, used in stock selection */

    enum allocation_method allocate_assets = M_EQUAL; /* method of asset allocation for determination of fraction of portfolio invested in each stock */

    sqrt_2 = sqrt ((double) 2.0); /* set the square root of 2 for math expediency */

    time_stamp[0] = '\0'; /* initialize the last time stamp, from the first column of the input file */

    while ((c = tsgetopt (argc, argv, "a:CcD:d:hI:ijM:m:o:Ppq:rstuv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'a': /* request for optimize asset allocation for each stock invested in? */

                switch (atoi (optarg)) /* yes, set the optimize asset allocation for each stock invested in */
                {

                    case M_EQUAL: /* equal allocation? */

                        allocate_assets = M_EQUAL; /* yes, set equal allocation */
                        break;

                    case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                        allocate_assets = M_MAXIMUM_GAIN; /* yes, set allocate for maximum gain */
                        break;

                    case M_MINIMUM_RISK: /* allocate for minimum risk */

                        allocate_assets = M_MINIMUM_RISK; /* yes, set allocate for minimum risk */
                        break;

                    default: /* illegal switch? */

                        optind = argc; /* force argument error */
                        retval = EARGS; /* assume not enough arguments */
                        break;

                }

                break;

            case 'C': /* request for don't compensate the Shannon probability, P, for data set size? */

                noest = 1; /* yes, set the don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate */
                break;

            case 'c': /* request for compensate the Shannon probability, P, for run length duration? */

                comp = 1; /* yes, set the compensate the Shannon probability, P, for run length duration flag, 0 = no, 1 = yes */
                break;

            case 'D': /* request for minimum value of decision critera, below which, a stock will not be invested in? */

                minimum_decision = atof (optarg); /* yes, save the minimum decision critera, below which, a stock will not be invested in */
                break;

            case 'd': /* request for method of computation for determination of a stock's decision criteria used in stock selection */

                switch (atoi (optarg) - 1) /* yes, save the method of computation for determination of a stock's decision criteria, used in stock selection */
                {

                    case M_AVGRMS: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (avg / rms + 1) / 2 */

                        method = M_AVGRMS;
                        break;

                    case M_RMS: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (rms + 1) / 2 */

                        method = M_RMS;
                        break;

                    case M_AVG: /* decision criteria: G = (1 + sqrt (avg))^P * (1 - sqrt (avg))^(1 - P), P = (sqrt (avg) + 1) / 2 */

                        method = M_AVG;
                        break;

                    case M_LENGTH: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = erf (1 / sqrt (run length)) */

                        method = M_LENGTH;
                        break;

                    case M_PERSISTENCE: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = Hurst exponent = short term persistence */

                        method = M_PERSISTENCE;
                        break;

                    case M_RANDOM: /* decision criteria: G = random */

                        minimum_decision = (double) -1.0; /* defeat the minimum decision critera, below which, a stock will not be invested in */
                        method = M_RANDOM;
                        break;

                    default: /* illegal switch? */

                        optind = argc; /* force argument error */
                        retval = EARGS; /* assume not enough arguments */
                        break;

                }

                break;

            case 'i': /* request for print the average index in the time series? */

                I = 1; /* yes, set the print the average index in the time series flag */
                break;

            case 'I': /* request for capital invested in all stocks? */

                capital = average = atof (optarg); /* yes, save the capital invested in all stocks */
                break;

            case 'j': /* request for index = average value of stock, instead of average balanced growth flag, 0 = no, 1 = yes */

                j = 1; /* yes, set the index = average value of stock, instead of average balanced growth flag, 0 = no, 1 = yes */
                break;

            case 'M': /* request for maximum number of stocks to invest in concurrently? */

                maximum_n = atoi (optarg); /* yes, save the maximum number of stocks to invest in concurrently */
                break;

            case 'm': /* request for minimum number of stocks to invest in concurrently? */

                minimum_n = atoi (optarg); /* yes, save the minimum number of stocks to invest in concurrently */
                break;

            case 'o': /* request for maximum acceptable marginal increment in stock's value? */

                o = atof (optarg); /* yes, save the maximum acceptable marginal increment in stock's value */
                break;

            case 'P': /* request for don't calculate stock's statistics if it has not been updated in the current interval flag, 0 = no, 1 = yes */

                P = 1; /* yes, set the don't calculate stock's statistics if it has not been updated in the current interval flag, 0 = no, 1 = yes */
                break;

            case 'p': /* request for invest only if stock has been updated in current interval flag, 0 = no, 1 = yes */

                p = 1; /* yes, set the invest only if stock has been updated in current interval flag, 0 = no, 1 = yes */
                break;

            case 'q': /* request for optimize margin buying flag, 0 = no, 1 = yes */

                q = 1; /* yes, set optimize margin buying flag, 0 = no, 1 = yes */
                max_margin_reciprocal = ((double) 1.0 / ((double) 1.0 - atof (optarg))); /* save the 1 - (1 / max_margin_reciprocal) = how much of the portfolio can be bought on margin, maximum */

                if (max_margin_reciprocal <= (double) 1.0) /* 1 < max_margin_reciprocal < infinity */
                {
                    optind = argc; /* force argument error */
                    retval = EARGS; /* assume not enough arguments */
                }

                break;

            case 'r': /* request for dump internal data on exit, 0 = no, 1 = yes? */

                r = 1; /* yes, set the dump internal data on exit flag, 0 = no, 1 = yes? */
                break;

            case 's': /* request for print the stock(s) flag, 0 = no, 1 = yes */

                s = 1; /* yes, set the print the stock(s) flag, 0 = no, 1 = yes */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

            case 'u': /* request for reverse the decision criteria sense? */

                u = 1; /* yes, set the reverse the decision criteria sense flag */
                minimum_decision = (double) -1.0; /* defeat the minimum decision critera, below which, a stock will not be invested in */
                break;


            case 'v':

                (void) printf ("%s\n", rcsid); /* print the version */
                (void) printf ("%s\n", copyright); /* print the copyright */
                optind = argc; /* force argument error */
                retval = EARGS; /* assume not enough arguments */
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

    if (retval == NOERROR)  /* any errors? */
    {

        if ((retval = hash_init (&text_table)) == NOERROR) /* initialize the hash table */
        {
            retval = EOPEN; /* assume error opening file */

            if ((infile = (argc <= optind) ? stdin : fopen (argv[optind], "r")) != (FILE *) 0) /* yes, open the stock's input file */
            {
                retval = NOERROR; /* assume no errors */

                while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the next record from the stock's input file */
                {

                    if ((fields = strtoken (buffer, parsebuffer, token, TOKEN_SEPARATORS)) != 0) /* parse the stock's record into fields, skip the record if there are no fields */
                    {

                        if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                        {

                            if (fields == 3) /* 3 fields are required */
                            {
                                currentvalue = atof (token[2]); /* save the current value of the stock */

                                if (currentvalue > (double) 0.0) /* a negative or zero value(s) makes no sense, add protection */
                                {

                                    if (period_counter == 0) /* first record from the input file(s) */
                                    {
                                        (void) strcpy (time_stamp, token[0]); /* save the last time stamp, from the first column of the input file */
                                        period_counter ++; /* time_stamp changed, increment the period counter */
                                    }

                                    if ((stock = get_stock (&text_table, token[1], method, comp, noest, p, P, o, currentvalue)) != (HASH *) 0) /* get the stock from the hash table */
                                    {

                                        if (strcmp (time_stamp, token[0]) != 0) /* no, new time stamp, from the first column of the input file? */
                                        {

                                            if ((retval = update_stocks (decision_list, allocate_assets, q)) != NOERROR) /* update the list of stocks */
                                            {
                                                break; /* couldn't update the list of stocks, exit */
                                            }

                                            invest (minimum_n, maximum_n, minimum_decision, allocate_assets); /* arrange the investments */
                                            printstocks (s, time_stamp, t, I, j, allocate_assets, q); /* print the stocks invested in */
                                            (void) strcpy (time_stamp, token[0]); /* save the new time stamp, from the first column of the input file */
                                            period_counter ++; /* time_stamp changed, increment the period counter */
                                        }

                                        stock->currentvalue = currentvalue; /* save current value of the stock */
                                        stock->current_updated = 1; /* set the updated in current interval flag, 0 = no, 1 = yes */
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

                if (retval == NOERROR) /* any errors? */
                {

                    if (period_counter != 0) /* any records? */
                    {

                        if ((retval = update_stocks (decision_list, allocate_assets, q)) == NOERROR) /* update the list of stocks */
                        {
                            invest (minimum_n, maximum_n, minimum_decision, allocate_assets); /* arrange the investments */
                            printstocks (s, time_stamp, t, I, j, allocate_assets, q); /* print the stocks invested in */
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

            }

            if (retval == NOERROR) /* any errors? */
            {

                if (r == 1) /* dump internal data on exit flag, 0 = no, 1 = yes, set? */
                {
                    stock = decision_list; /* reference the first element in the decision criteria list for investment in a stock */

                    while (stock != (HASH *) 0) /* for each stock in the list of decision criteria */
                    {

                        if (stock->positive_histogram[0].count + stock->negative_histogram[0].count > 0) /* count of both positive and negative consecutive like movements greater than zero, ie., can it be divided by? */
                        {
                            h = ((double) (stock->positive_histogram[1].count + stock->negative_histogram[1].count)) / ((double) (stock->positive_histogram[0].count + stock->negative_histogram[0].count)); /* use the sum of the positive and negative consecutive like movements in the first two elements of the histograms to calculate the Hurst exponent, h */
                        }

                        else
                        {
                            h = (double) 0.5; /* no negative or positive like movements, default to Brownian motion */
                        }

                        (void) printf ("# %s, p = %f, f = %f, h = %f, i = %f\n", stock->hash_data, stock->Par, stock->rms, h, stock->start_value); /* print the tsinvestsim(1) format record for the stock */
                        stock = stock->next_decision; /* reference the next element in the decision criteria list */
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

Invest in the stocks.

static void invest (int minimum_n, int maximum_n, double minimum_decision, enum allocation_method allocate_assets);

I) Data structures:

    A) The singly linked list, maintained by the HASH element HASH
    *next_investment, is the list of equities invested in at any
    time. The head of this list is referenced by the global HASH
    *invested_list. The list is null terminated.

    B) The singly linked list, maintained by the HASH element HASH
    *next_decision, is the list of decision criteria for all
    stocks. The head of this list is referenced by the global HASH
    *decision_list. This list is sorted, in descending order of
    desirability, by the function void qsortlist (), ie., after
    sorting, HASH *decision_list references the most desirable
    stock. The decision criteria is derived from the statistics of the
    stock's time series in the function int statistics (), and the
    function int decisions (), and is stored in the HASH structure
    decision element. The list is null terminated.

II) Function execution:

    A) At the beginning of this function, the invested list is "walked
    through," returning all investments in stocks to capital, (ie.,
    making the list null, which means no investments.) This,
    effectively, "sells" all stocks.

    B) The decision list is then sorted, by void qsortlist (), to
    arrange the decision list into descending order of desirability,
    based on the statistics acquired in the last time interval.

    C) The decision list is then "walked through," choosing those
    stocks, in descending order of desirability, for investment, by
    calling int invest_decisions ().

    D) The new investment list is then "walked through," transferring
    money from the capital to investments in the stocks-the list is
    reversed in this operation to make a print list, in descending
    order of disability. This list is referenced by the variable
    print_list.

    E) Note that this is a rudimentary investment strategy. A
    sophisticated implementation would probably use combinations of
    the strategies specified on the command line with the -d option,
    for example, -d1 and -d4. Also, the wagering strategy is
    rudimentary, in that there is equal dispersion of investment in
    the equities.

III) Additionally, note that there is an approximation involved. The
way the void invest () function works is to allocate an equal fraction
of the capital in each equity. If the increments of equity values are
a Brownian motion fractal with a Gaussian/normal distribution, the
fraction of the capital invested in each equity should be be
proportional to the reciprocal of the the root mean square of the
increments, ie., such that the risk, (which is proportional to the
rms,) contributed by each equity is equal. This is very difficult to
achieve in practice because of the leptokurtosis in the
increments-there is no mathematical infrastructure for adding
Pareto-Levy distributions with different fractal dimensions.

However, if the averages of the marginal increments for the stocks
differ, and/or if the root mean square of the marginal increments for
the stocks differ, the optimum fraction can be approximated through
heuristic arguments justifying the approximation are that in a
universe of stocks, by investing in each and every stock, the
portfolio growth characteristics will be a nearly smooth exponential,
since the root mean square of the marginal increments of the stocks
add root mean square, and the average of the marginal increments, add
linearly.

Note: the process of II)C) is probably one of the most modified
sections of this program-it is where the investment strategy, and
portfolio management occurs.

Returns nothing.

*/

#ifdef __STDC__

static void invest (int minimum_n, int maximum_n, double minimum_decision, enum allocation_method allocate_assets)

#else

static void invest (minimum_n, maximum_n, minimum_decision, allocate_assets)
int minimum_n;
int maximum_n;
double minimum_decision;
enum allocation_method allocate_assets;

#endif

{
    int i; /* HASH struct counter */

    double investment = (double) 0.0, /* the amount invested in each stock */
           total_allocation_fraction = (double) 0.0, /* the total fraction of the portfolio that is to be allocated to a stock */
           allocation_fraction, /* fraction of portfolio allocated to a stock */
           avgP = (double) 0.0, /* the average  of the portfolio's marginal increments */
           rmsP, /* the deviation of the portfolio's marginal increments */
           rmssquaredP = (double) 0.0, /* the running sum of the deviation of the portfolio's marginal increments, squared */
           temp; /* temporary float */

    HASH *stock; /* reference to HASH struct in the decision or invested list */

    while (invested_list != (HASH *) 0) /* withdraw all investments to rearrange them */
    {
        stock = POPINVESTMENT(); /* pop the invested HASH element from the investment list */
        stock->next_print = (HASH *) 0; /* reset the reference to next element in print list */
        capital = capital + stock->capital; /* add the investment to the capital invested in all stocks */
        stock->capital = (double) 0.0;  /* zero the amount of capital invested in this stock */
    }

    portfolio = capital; /* save the portfolio value */
    print_list  = (HASH *) 0; /* reset the reference to print list-stocks that have investments in them are in this list, in reverse order of invested_list */
    qsortlist (&decision_list, (list) 0); /* sort the decision criteria list for investment in a stock */
    stock = decision_list; /* reference the first element in the decision criteria list for investment in a stock */
    i = invest_decisions (stock, minimum_n, minimum_decision, maximum_n); /* decide whether to invest in a stock */

    if (allocate_assets == M_EQUAL) /* optimize asset allocation for each stock invested in flag set, 0 = don't allocate, 1 = allocate? */
    {

        if (i > 0) /* any stocks to invest in? */
        {
            investment = capital / (double) i; /* yes, calculate the amount of capital invested in each stock */
            allocation_fraction = (double) 1.0 / (double) i; /* set the fraction of portfolio allocated to a stock */
        }

        else
        {
            allocation_fraction = (double) 0.0; /* set the fraction of portfolio allocated to a stock */
        }

    }

    else
    {
        stock = invested_list; /* reference the first element in the investment list for investment in a stock */

        while (stock != (HASH *) 0) /* while there are stocks remaining in the invested list */
        {
            total_allocation_fraction = total_allocation_fraction + stock->allocation_fraction; /* add this stock's fraction of the portfolio that is to be allocated for this stock to the total fraction of the portfolio that is to be allocated */
            stock = stock->next_investment; /* reference the next element in the investment list */
        }

    }

    stock = invested_list; /* reference the first element in the investment list for investment in a stock */

    while (stock != (HASH *) 0) /* while there are stocks remaining in the invested list */
    {
        PUSHPRINT(stock); /* push the HASH element for this stock on the print list */

        if (allocate_assets == M_EQUAL || (total_allocation_fraction <= (double) 0.0)) /* optimize asset allocation for each stock invested in flag set, 0 = don't allocate, 1 = allocate? ; if the total fraction of the portfolio that is to be allocated to a stock is zero, default to this method to protect division by zero, below */
        {
            stock->capital = investment; /* set the amount of capital invested in the stock */
            capital = capital - investment; /* subtract the investment from the capital invested in all stocks */
        }

        else
        {
            allocation_fraction = stock->allocation_fraction / total_allocation_fraction; /* set the fraction of portfolio allocated to a stock */
            stock->allocation_percentage = allocation_fraction; /* set the percentage of the portfolio that is to be allocated to the stock */
            stock->capital = stock->allocation_percentage * portfolio; /* set the amount of capital invested in the stock */
            capital = capital - stock->capital; /* subtract the investment from the capital invested in all stocks */
        }

        temp = (((double) 2.0 * stock->Peffa) - (double) 1.0); /* calculate the square root of the avg for the stock */
        avgP = avgP + ((temp * temp) * allocation_fraction); /* save the average  of the portfolio's marginal increments */
        temp = ((((double) 2.0 * stock->rms) - (((double) 2.0 * stock->Peffr) - (double) 1.0)) * allocation_fraction); /* calculate the rms for the stock */
        rmssquaredP = rmssquaredP + (temp * temp); /* calculate the running sum of the deviation of the portfolio's marginal increments, squared */
        stock = stock->next_investment; /* reference the next element in the investment list */
    }

    margin_reciprocal = (double) 1.0; /* save 1 - (1 / margin_reciprocal) = how much of the portfolio can be margin */
    rmsP = sqrt (rmssquaredP); /* save the deviation of the portfolio's marginal increments */

    if (rmsP > (double) 0.0) /* protection for numerical exceptions */
    {
        margin_reciprocal = (avgP / (rmsP * rmsP)) - (double) 1.0 ; /* save 1 - (1 / margin_reciprocal) = how much of the portfolio can be margin */
        margin_reciprocal = (margin_reciprocal <= (double) 1.0) ? (double) 1.0 : margin_reciprocal; /* margin less than unity? */
    }

    margin_reciprocal = (margin_reciprocal > max_margin_reciprocal) ? max_margin_reciprocal : margin_reciprocal; /* if 1 - (1 / max_margin_reciprocal) = how much of the portfolio can be bought on margin, maximum, is less, use it */
}

/*

Decide whether to invest in a stock.

static int invest_decisions (HASH *stock, int minimum_n, double minimum_decision, int maximum_n);

I) This function assembles the portfolio. The stocks are presented to
this function, in order of desirability, by the stock->next_decision
list. To balance the portfolio, a stock is added, and if the
calculated gain with the stock is greater than without it, it is added
to the portfolio.

II) The decision criteria is based on the presumptions of the
Efficient Market Hypothesis, ie., that the average marginal gain of
the stocks in a portfolio add linearly, and the root mean square of
the marginal gains, ie., the volatility, or risk, add root mean
square.

III) The methods used for deciding whether to add a stock to the
portfolio are:

    A) The first method uses both the average, avg, and root mean
    square, rms, of the stock's time series in the calculation of the
    decision criteria.

    B) The second method uses only the root mean square, rms, of the
    stock's time series in the calculation of the decision criteria.

    C) The third method uses only the average, avg, of the stock's
    time series in the calculation of the decision criteria.

    D) The fourth method chooses stocks using mean reverting dynamics.

    E) The fifth method chooses stocks using persistence.

    F) The sixth method chooses stocks at random, as a comparative
    strategy.

Note that the second and third methods for calculating the decision
criteria are derived on the assumption that the stock's time series
has optimal growth, ie., rms = sqrt (avg).

IV) Data structures:

    A) The singly linked list, maintained by the HASH element HASH
    *next_investment, is the list of equities invested in at any
    time. The head of this list is referenced by the global HASH
    *invested_list. The list is null terminated.

    B) The singly linked list, maintained by the HASH element HASH
    *next_decision, is the list of decision criteria for all
    stocks. The head of this list is referenced by the global HASH
    *decision_list. This list is sorted, in descending order of
    desirability, by the function void qsortlist (), ie., after
    sorting, HASH *decision_list references the most desirable
    stock. The decision criteria is derived from the statistics of the
    stock's time series in the function int statistics (), and the
    function int decisions (), and is stored in the HASH structure
    decision element. The list is null terminated.

V) Function execution:

    A) the decision list is "walked through," choosing those stocks,
    in descending order of desirability, for investment, and making a
    new investment list. There methods used are:

    1) Dynamically determine whether the portfolio's growth would be
    enhanced by adding the stock to the portfolio-this is done by
    maintaining a "running" estimate of the portfolio growth,
    portfolio root mean square, and portfolio average. The stocks are
    added to the portfolio, in decreasing order of desirability, and
    if the portfolio is enhanced, the stock is added to the portfolio.

    2) To avoid adding stocks if avg => rms of the portfolio, ie., the
    Shannon probability, is unity, since adding more stocks to the
    portfolio would not mediate risks further.

    3) To avoid a non-optimal portfolio volatility, ie., where avgp <
    rmsp^2, the minimum number of equities invested in concurrently
    may be violated, but not the maximum number of equities invested
    in concurrently.

VI) Additionally, note that there is an approximation involved. The
way the void invest () function works is to allocate an equal fraction
of the capital in each equity. If the increments of equity values are
a Brownian motion fractal with a Gaussian/normal distribution, the
fraction of the capital invested in each equity should be be
proportional to the reciprocal of the the root mean square of the
increments, ie., such that the risk, (which is proportional to the
rms,) contributed by each equity is equal. This is very difficult to
achieve in practice because of the leptokurtosis in the
increments-there is no mathematical infrastructure for adding
Pareto-Levy distributions with different fractal dimensions.

Note: using the paradigm of the Efficient Market Hypothesis is an
expediency. The prevailing assumption of the EMH is that equity values
can be modeled as a Brownian motion fractal, (ie., a persistence of
50%.) The -d 5 option assumes that equity values are fractional
Brownian in nature, ie., usually a persistence of greater than 50%.
Unfortunately, the mathematics of assembling a portfolio with
differing persistence is not trivial.

Returns the number of stocks in the portfolio.

*/

#ifdef __STDC__

static int invest_decisions (HASH * stock, int minimum_n, double minimum_decision, int maximum_n)

#else

static int invest_decisions (stock, minimum_n, minimum_decision, maximum_n)
HASH *stock;
int minimum_n;
double minimum_decision;
int maximum_n;

#endif

{

    /* the statics are to avoid having to allocate space on the stack for doubles */

    static double Gp, /* growth of the portfolio */
                  Gpnext, /* next growth of portfolio, with the next stock added */
                  avg, /* average of the normalized increments, avg */
                  avgpnextsum, /* next sum of the average of the normalized increments of the portfolio, with the next stock added */
                  avgpnext = (double) 0.0, /* next average of the normalized increments of the portfolio, with the next stock added */
                  avgpsum = (double) 0.0, /* sum of the normalized increments of the portfolio */
                  rms, /* root mean square of the normalized increments, rms */
                  rmspnextsquaredsum, /* the next square of the root mean square of the normalized increments of the portfolio, with the next stock added */
                  rmspnextsum, /* the next sum of the root mean square of the normalized increments of the portfolio, with the next stock added */
                  rmspnext = (double) 0.0, /* next root mean square of the normalized increments of the portfolio */
                  rmspsquaredsum, /* sum of the square of the root mean square of the normalized increments of the portfolio */
                  Pp, /* persistence probability */
                  Pt, /* mean reverting probability */
                  Ppnext, /* Shannon probability of the portfolio */
                  m; /* count of stocks invested in, plus 1, ie., i + 1 */

    int i = 0, /* HASH struct counter */
        finished = 0; /* finished looping through decision list, 0 = no, 1 = yes */

    avg = stock->avg; /* initialize the average of the normalized increments, avg */
    rms = stock->rms; /* initialize the root mean square of the normalized increments, rms */
    Pp = stock->Pp; /* initialize the persistence probability */
    Pt = stock->Pt; /* initialize the mean reverting probability */
    Gp = (double) 0.0; /* initialize the growth of the portfolio */
    avgpsum = (double) 0.0; /* initialize the sum of the normalized increments of the portfolio */
    rmspsquaredsum = (double) 0.0; /* initialize the sum of the square of the root mean square of the normalized increments of the portfolio */
    Ppnext = (double) 0.0; /* initialize the Shannon probability of the portfolio */

    while ((stock != (HASH *) 0) && (stock->decision > minimum_decision) && (i < maximum_n) && (finished == 0)) /* count the elements in the list of decision criteria for investment in a stock, where the equity's minimum decision critera, below which, a stock will not be invested in, is not too small, and the number of stocks invested in is less than the maximum number of stocks to invest in concurrently */
    {

        if ((stock->transactions > 1) && ((stock->last_updated > 1) || (stock->invest_update == 0))) /* two transactions are required for a stock, and updated in the current interval-which is now the last interval, ie., transitions, enough? */
        {

            if (Ppnext >= (double) 1.0) /* Shannon probability of the portfolio unity? */
            {

                if (i > minimum_n) /* yes, number of stocks invested in greater than minimum number of stocks to invest in concurrently? */
                {

                    if (avgpnext >= rmspnext) /* yes, never let the portfolio exist in a state where avgp < rmsp^2 */
                    {
                        finished = 1; /* finished looping through decision list, 0 = no, 1 = yes */
                        break; /* add no more stocks to the portfolio */
                    }

                }

            }

            switch (stock->method) /* which method of computation for determination of a stock's decision criteria, used in stock selection */
            {

                case M_AVGRMS: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (avg / rms + 1) / 2 */

                    avgpnextsum = avgpsum + avg; /* save the next sum of the average of the normalized increments of the portfolio, with the next stock added */
                    rmspnextsquaredsum = rmspsquaredsum + (rms * rms); /* save the next square of the root mean square of the normalized increments of the portfolio, with the next stock added */

                    if (rmspnextsquaredsum > (double) 0.0) /* protection for numerical exceptions */
                    {
                        m = (double) (i + 1); /* save the count of stocks invested in, plus 1, ie., i + 1 */
                        avgpnext = avgpnextsum / m; /* save the next average of the normalized increments of the portfolio, with the next stock added */
                        rmspnextsum = sqrt (rmspnextsquaredsum); /* save the next sum of the root mean square of the normalized increments of the portfolio, with the next stock added */
                        rmspnext = rmspnextsum / sqrt (m); /* save the next root mean square of the normalized increments of the portfolio */

                        if (rmspnext > (double) 0.0) /* protection for numerical exceptions */
                        {
                            Ppnext = ((avgpnext / rmspnext) + (double) 1.0) / (double) 2.0; /* save the Shannon probability of the portfolio */
                            Gpnext = GAIN (rmspnext, Ppnext); /* save the next growth of portfolio, with the next stock added */

                            if (Gpnext < Gp) /* growth of the portfolio by adding this stock? */
                            {

                                if (i > minimum_n) /* no, number of stocks invested in greater than minimum number of stocks to invest in concurrently? */
                                {

                                    if (avgpnext >= rmspnext) /* yes, never let the portfolio exist in a state where avgp < rmsp^2 */
                                    {
                                        finished = 1; /* finished looping through decision list, 0 = no, 1 = yes */
                                        break; /* add no more stocks to the portfolio */
                                    }

                                }

                            }

                            avgpsum = avgpnextsum; /* save the sum of the normalized increments of the portfolio */
                            rmspsquaredsum = rmspnextsquaredsum; /* save the sum of the square of the root mean square of the normalized increments of the portfolio */
                            Gp = Gpnext; /* save the growth of the portfolio */
                            PUSHINVESTMENT(stock); /* push the HASH element for this stock on the investment list */
                            i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
                        }

                    }

                    break;

                case M_RMS: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (rms + 1) / 2 */

                    rmspnextsquaredsum = rmspsquaredsum + (rms * rms); /* save the next square of the root mean square of the normalized increments of the portfolio, with the next stock added */

                    if (rmspnextsquaredsum > (double) 0.0) /* protection for numerical exceptions */
                    {
                        m = (double) (i + 1); /* save the count of stocks invested in, plus 1, ie., i + 1 */
                        rmspnextsum = sqrt (rmspnextsquaredsum); /* save the next sum of the root mean square of the normalized increments of the portfolio, with the next stock added */
                        rmspnext = rmspnextsum / sqrt (m); /* save the next root mean square of the normalized increments of the portfolio */

                        if (rmspnext > (double) 0.0) /* protection for numerical exceptions */
                        {
                            Ppnext = (rmspnext + (double) 1.0) / (double) 2.0; /* save the Shannon probability of the portfolio */
                            Gpnext = GAIN (rmspnext, Ppnext); /* save the next growth of portfolio, with the next stock added */

                            if (Gpnext < Gp) /* growth of the portfolio by adding this stock? */
                            {

                                if (i > minimum_n) /* no, number of stocks invested in greater than minimum number of stocks to invest in concurrently? */
                                {
                                    finished = 1; /* finished looping through decision list, 0 = no, 1 = yes */
                                    break; /* add no more stocks to the portfolio */
                                }

                            }

                            rmspsquaredsum = rmspnextsquaredsum; /* save the sum of the square of the root mean square of the normalized increments of the portfolio */
                            Gp = Gpnext; /* save the growth of the portfolio */
                            PUSHINVESTMENT(stock); /* push the HASH element for this stock on the investment list */
                            i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
                        }

                    }

                    break;

                case M_AVG: /* decision criteria: G = (1 + sqrt (avg))^P * (1 - sqrt (avg))^(1 - P), P = (sqrt (avg) + 1) / 2 */

                    avgpnextsum = avgpsum + avg; /* save the next sum of the average of the normalized increments of the portfolio, with the next stock added */

                    if (avgpnextsum > (double) 0.0) /* protection for numerical exceptions */
                    {
                        m = (double) (i + 1); /* save the count of stocks invested in, plus 1, ie., i + 1 */
                        avgpnext = avgpnextsum / m; /* save the next average of the normalized increments of the portfolio, with the next stock added */
                        rmspnext = sqrt (avgpnext); /* save the EFFECTIVE next root mean square of the normalized increments of the portfolio */

                        if (rmspnext > (double) 0.0) /* protection for numerical exceptions */
                        {
                            Ppnext = (rmspnext + (double) 1.0) / (double) 2.0; /* save the Shannon probability of the portfolio */
                            Gpnext = GAIN (rmspnext, Ppnext); /* save the next growth of portfolio, with the next stock added */

                            if (Gpnext < Gp) /* growth of the portfolio by adding this stock? */
                            {

                                if (i > minimum_n) /* no, number of stocks invested in greater than minimum number of stocks to invest in concurrently? */
                                {
                                    finished = 1; /* finished looping through decision list, 0 = no, 1 = yes */
                                    break; /* add no more stocks to the portfolio */
                                }

                            }

                            avgpsum = avgpnextsum; /* save the sum of the normalized increments of the portfolio */
                            Gp = Gpnext; /* save the growth of the portfolio */
                            PUSHINVESTMENT(stock); /* push the HASH element for this stock on the investment list */
                            i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
                        }

                    }

                    break;

                case M_LENGTH: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = erf (1 / sqrt (run length)) */

                    avgpnextsum = avgpsum + (((double) 2.0 * Pt) + (double) 1.0) * rms; /* save the next sum of the average of the normalized increments of the portfolio, with the next stock added, which is calculated by avg = (2Pt - 1) * rms */
                    rmspnextsquaredsum = rmspsquaredsum + (rms * rms); /* save the next square of the root mean square of the normalized increments of the portfolio, with the next stock added */

                    if (rmspnextsquaredsum > (double) 0.0) /* protection for numerical exceptions */
                    {
                        m = (double) (i + 1); /* save the count of stocks invested in, plus 1, ie., i + 1 */
                        avgpnext = avgpnextsum / m; /* save the next average of the normalized increments of the portfolio, with the next stock added */
                        rmspnextsum = sqrt (rmspnextsquaredsum); /* save the next sum of the root mean square of the normalized increments of the portfolio, with the next stock added */
                        rmspnext = rmspnextsum / sqrt (m); /* save the next root mean square of the normalized increments of the portfolio */

                        if (rmspnext > (double) 0.0) /* protection for numerical exceptions */
                        {
                            Ppnext = ((avgpnext / rmspnext) + (double) 1.0) / (double) 2.0; /* save the Shannon probability of the portfolio */
                            Gpnext = GAIN (rmspnext, Ppnext); /* save the next growth of portfolio, with the next stock added */

                            if (Gpnext < Gp) /* growth of the portfolio by adding this stock? */
                            {

                                if (i > minimum_n) /* no, number of stocks invested in greater than minimum number of stocks to invest in concurrently? */
                                {

                                    if (avgpnext >= rmspnext) /* yes, never let the portfolio exist in a state where avgp < rmsp^2 */
                                    {
                                        finished = 1; /* finished looping through decision list, 0 = no, 1 = yes */
                                        break; /* add no more stocks to the portfolio */
                                    }

                                }

                            }

                            avgpsum = avgpnextsum; /* save the sum of the normalized increments of the portfolio */
                            rmspsquaredsum = rmspnextsquaredsum; /* save the sum of the square of the root mean square of the normalized increments of the portfolio */
                            Gp = Gpnext; /* save the growth of the portfolio */
                            PUSHINVESTMENT(stock); /* push the HASH element for this stock on the investment list */
                            i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
                        }

                    }

                    break;

                case M_PERSISTENCE: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = Hurst exponent = short term persistence */

                    avgpnextsum = avgpsum + (((double) 2.0 * Pp) + (double) 1.0) * rms; /* save the next sum of the average of the normalized increments of the portfolio, with the next stock added, which is calculated by avg = (2Pp - 1) * rms */
                    rmspnextsquaredsum = rmspsquaredsum + (rms * rms); /* save the next square of the root mean square of the normalized increments of the portfolio, with the next stock added */

                    if (rmspnextsquaredsum > (double) 0.0) /* protection for numerical exceptions */
                    {
                        m = (double) (i + 1); /* save the count of stocks invested in, plus 1, ie., i + 1 */
                        avgpnext = avgpnextsum / m; /* save the next average of the normalized increments of the portfolio, with the next stock added */
                        rmspnextsum = sqrt (rmspnextsquaredsum); /* save the next sum of the root mean square of the normalized increments of the portfolio, with the next stock added */
                        rmspnext = rmspnextsum / sqrt (m); /* save the next root mean square of the normalized increments of the portfolio */

                        if (rmspnext > (double) 0.0) /* protection for numerical exceptions */
                        {
                            Ppnext = ((avgpnext / rmspnext) + (double) 1.0) / (double) 2.0; /* save the Shannon probability of the portfolio */
                            Gpnext = GAIN (rmspnext, Ppnext); /* save the next growth of portfolio, with the next stock added */

                            if (Gpnext < Gp) /* growth of the portfolio by adding this stock? */
                            {

                                if (i > minimum_n) /* no, number of stocks invested in greater than minimum number of stocks to invest in concurrently? */
                                {

                                    if (avgpnext >= rmspnext) /* yes, never let the portfolio exist in a state where avgp < rmsp^2 */
                                    {
                                        finished = 1; /* finished looping through decision list, 0 = no, 1 = yes */
                                        break; /* add no more stocks to the portfolio */
                                    }

                                }

                            }

                            avgpsum = avgpnextsum; /* save the sum of the normalized increments of the portfolio */
                            rmspsquaredsum = rmspnextsquaredsum; /* save the sum of the square of the root mean square of the normalized increments of the portfolio */
                            Gp = Gpnext; /* save the growth of the portfolio */
                            PUSHINVESTMENT(stock); /* push the HASH element for this stock on the investment list */
                            i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
                        }

                    }

                    break;

                case M_RANDOM: /* decision criteria: G = random */

                    PUSHINVESTMENT(stock); /* push the HASH element for this stock on the investment list */
                    i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
                    break;

                default: /* illegal switch? */

                    avgpnextsum = avgpsum + avg; /* save the next sum of the average of the normalized increments of the portfolio, with the next stock added */
                    rmspnextsquaredsum = rmspsquaredsum + (rms * rms); /* save the next square of the root mean square of the normalized increments of the portfolio, with the next stock added */

                    if (rmspnextsquaredsum > (double) 0.0) /* protection for numerical exceptions */
                    {
                        m = (double) (i + 1); /* save the count of stocks invested in, plus 1, ie., i + 1 */
                        avgpnext = avgpnextsum / m; /* save the next average of the normalized increments of the portfolio, with the next stock added */
                        rmspnextsum = sqrt (rmspnextsquaredsum); /* save the next sum of the root mean square of the normalized increments of the portfolio, with the next stock added */
                        rmspnext = rmspnextsum / sqrt (m); /* save the next root mean square of the normalized increments of the portfolio */

                        if (rmspnext > (double) 0.0) /* protection for numerical exceptions */
                        {
                            Ppnext = ((avgpnext / rmspnext) + (double) 1.0) / (double) 2.0; /* save the Shannon probability of the portfolio */
                            Gpnext = GAIN (rmspnext, Ppnext); /* save the next growth of portfolio, with the next stock added */

                            if (Gpnext < Gp) /* growth of the portfolio by adding this stock? */
                            {

                                if (i > minimum_n) /* no, number of stocks invested in greater than minimum number of stocks to invest in concurrently? */
                                {

                                    if (avgpnext >= rmspnext) /* yes, never let the portfolio exist in a state where avgp < rmsp^2 */
                                    {
                                        finished = 1; /* finished looping through decision list, 0 = no, 1 = yes */
                                        break; /* add no more stocks to the portfolio */
                                    }

                                }

                            }

                            avgpsum = avgpnextsum; /* save the sum of the normalized increments of the portfolio */
                            rmspsquaredsum = rmspnextsquaredsum; /* save the sum of the square of the root mean square of the normalized increments of the portfolio */
                            Gp = Gpnext; /* save the growth of the portfolio */
                            PUSHINVESTMENT(stock); /* push the HASH element for this stock on the investment list */
                            i ++; /* one more counted elements in the list of decision criteria for investment in a stock */
                        }

                    }

                    break;

            }

        }

        stock = stock->next_decision; /* reference the next element in the decision criteria list */
    }

    return (i); /* return the number of stocks */
}

/*

Print the stocks invested in.

static void printstocks (int verboseprint, char *time_stamp, int timeprint, int indexprint, int index_type, enum allocation_method allocate_assets, int margin_buy);

I) Data structures:

    A) The singly linked list, maintained by the HASH element HASH
    *next_print, is the list of equities invested in at any time. The
    head of this list is referenced by the global HASH *print_list. It
    contains a list, in decreasing order of desirability, of the
    stocks invested in. The list is null terminated.

    B) The singly linked list, maintained by the HASH element HASH
    *next_investment, is the list of equities invested in at any
    time. The head of this list is referenced by the global HASH
    *invested_list. The list is null terminated. This list is in the
    reverse order of A), above, ie., in increasing order of
    desirability, of the stocks invested in.

    C) The singly linked list, maintained by the HASH element HASH
    *next_decision, is the list of decision criteria for all
    stocks. The head of this list is referenced by the global HASH
    *decision_list. This list is sorted, in decreasing order of
    desirability, ie., HASH *decision_list references the most
    desirable stock. The decision criteria is derived from the
    statistics of the stock's time series in the function int
    statistics (), and the function int decisions (), and is stored in
    the HASH structure decision element. The list is null
    terminated. This list contains all stocks that have been
    encountered in the input file.

II) Function execution:

    A) "Walk through" the list of stocks to be printed, printing the
    requested data, and terminate the printed record with an EOL.

Returns nothing.

*/

#ifdef __STDC__

static void printstocks (int verboseprint, char *time_stamp, int timeprint, int indexprint, int index_type, enum allocation_method allocate_assets, int margin_buy)

#else

static void printstocks (verboseprint, time_stamp, timeprint, indexprint, index_type, allocate_assets, margin_buy)
int verboseprint;
char *time_stamp;
int timeprint;
int indexprint;
int index_type;
enum allocation_method allocate_assets;
int margin_buy;

#endif

{
    HASH *stock; /* reference to HASH struct in the decision or invested list */

    if (timeprint == 1) /* print time of samples flag set? */
    {
        (void) printf ("%s\t", time_stamp); /* yes, print the time stamp */
    }

    if (indexprint == 0) /* print the average index in the time series flag set? */
    {
        (void) printf ("%.2f", portfolio); /* no, start the print with the portfolio value */
    }

    else
    {

        if (index_type == 0) /* index = average value of stock, instead of average balanced growth flag, 0 = no, 1 = yes, set? */
        {
            (void) printf ("%.2f\t%.2f", average, portfolio); /* no, start the print with the portfolio value, and the average index */
        }

        else
        {
            (void) printf ("%.2f\t%.2f", average_value, portfolio); /* yes, start the print with the portfolio value, and the average index */
        }

    }

    if (margin_buy == 1) /* optimize margin buying flag, 0 = no, 1 = yes, set? */
    {
        (void) printf ("\t%0.2f", (double) 1.0 - ((double) 1.0 / margin_reciprocal)); /* yes, print 1 - (1 / margin_reciprocal) = how much of the portfolio can be bought on margin */
    }

    stock = print_list; /* reference the first element in the print list */

    while (stock != (HASH *) 0) /* while there are stocks remaining in print list */
    {

        if (verboseprint == 1) /* print the stock(s) flag set? */
        {
            (void) printf ("\t%s", stock->hash_data); /* yes, print the stock(s) */

            if (allocate_assets != M_EQUAL) /* optimize asset allocation for each stock? */
            {
                (void) printf ("=%0.2f", stock->allocation_percentage); /* print the percentage of the portfolio that is to be allocated to a stock */
            }

        }

        stock = stock->next_print; /* reference the next element in the print list */
    }

    (void) printf ("\n"); /* terminate this investment record */
}

/*

Get a stock from the hash table.

static HASH *get_stock (HASHTABLE *stock_table, void *name, enum decision_method method, int comp, int noest, int update_invest, int update_stats, double maxinc, double currentvalue);

I) Get the HASH structure for the stock identified by name, (the HASH
structure element hash_data references the stock's name.)

    A) If the stock's HASH structure exists in the hash lookup table,
    return it.

    B) If the stock's HASH structure does not exist, create it and
    return it.

Returns a reference to the stock HASH element, zero on error.

*/

#ifdef __STDC__

static HASH *get_stock (HASHTABLE *stock_table, void *name, enum decision_method method, int comp, int noest, int update_invest, int update_stats, double maxinc, double currentvalue)

#else

static HASH *get_stock (stock_table, name, method, comp, noest, update_invest, update_stats, maxinc, currentvalue)
HASHTABLE *stock_table;
void *name;
enum decision_method method;
int comp;
int noest;
int update_invest;
int update_stats;
double maxinc;
double currentvalue;

#endif

{
    HASH *stock;  /* reference to hash table stock element */

    if ((stock = hash_find (stock_table, name)) == (HASH *) NOERROR) /* find a hash table element with the stock tick identifier in the hash table */
    {

        if (hash_insert (stock_table, name) == NOERROR) /* couldn't find it, add the stock tick identifier to the hash table */
        {

            if ((stock = hash_find (stock_table, name)) != (HASH *) NOERROR) /* find the hash table element with the stock tick identifier in the hash table */
            {
                stock->method = method; /* save the method used to calculate the Shannon probability, one of enum decision_method */
                stock->comp = comp; /* save the compensate the Shannon probability, P, for run length duration flag, 0 = no, 1 = yes */
                stock->noest = noest; /* save the don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate */
                stock->invest_update = update_invest; /* save the invest only if stock has been updated in current interval flag, 0 = no, 1 = yes */
                stock->stats_update = update_stats; /* save the don't calculate stock's statistics if it has not been updated in the current interval flag, 0 = no, 1 = yes */
                stock->maxinc = maxinc; /* save the maximum acceptable marginal increment in stock's value */
                stock->start_value = currentvalue; /* save the start value of stock */
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

Update the list of stocks.

static int update_stocks (HASH *stock_list, enum allocation_method allocate_assets, int margin_buy);

I) Scan the list of all available equities, calculating the statistics
for the history of each equity:

    A) "Walk through" the linked list of decision criteria, maintained
    by the HASH element HASH *next_decision. The head of the list is
    referenced by the global HASH *decision_list. This list contains
    the list of all HASH structures for all stocks "seen" by the input
    file.

        1) Use the function int statistics (), and int decisions (),
        to calculate the statistics for each stock's HASH structure.

        2) Update each stock's last value, which is used to calculate
        the normalized increments of the stock's time series.

            a) It is permissable for a single equity to have multiple
            updates from the input file in any time interval-something
            that shouldn't be a requirement, but frequently happens on
            real time "tickers." Note that the statistics for the
            equity will be calculated for such a scenario only at the
            end of a legitimate time interval, in this function, using
            the last, ie., latest, values from the input file.

            b) It is permissable for equities not to be represented in
            a time interval, since, under such a scenario, the
            equities statistics will be calculated anyway, by this
            function, with a no-change in equity value, ie., the
            statistical information for the equity will remain valid,
            in relation to the other equities in the market.

        3) Update each stock's investment value, based on I)A)2).

        4) Calculate the change in the average index, ie., aggregate,
        average for all equities in the market, do to fluctuations in
        each stock's value, based on I)A)2).

Returns NOERROR if successful, EALLOC on memory allocation error in
int statistics ().

*/

#ifdef __STDC__

static int update_stocks (HASH *stock_list, enum allocation_method allocate_assets, int margin_buy)

#else

static int update_stocks (stock_list, allocate_assets, margin_buy)
HASH *stock_list;
enum allocation_method allocate_assets;
int margin_buy;

#endif

{
    int retval = NOERROR; /* return value, assume no error */

    double fraction, /* normalized increment of stock's value */
           fractionplusone; /* normalized increment of stock's value, plus 1 */

    HASH *stock; /* reference to HASH struct */

    average_value = (double) 0.0; /* reset the average value of index, computed as the average value of a share of stock */

    stock = stock_list; /* reference the first element in the decision criteria list for investment in a stock */

    while (stock != (HASH *) 0 && retval == NOERROR) /* count the elements in the list of decision criteria for investment in a stock, but not greater than n */
    {

        if ((retval = statistics (stock)) == NOERROR) /* calculate the statistics for a stock */
        {

            if ((retval = decisions (stock, allocate_assets)) == NOERROR) /* set the decision criteria for a stock */
            {

                if (stock->transactions > 1) /* two transactions are required for a stock, transitions, enough? */
                {

                    if (margin_buy == 1) /* optimize margin buying flag, 0 = no, 1 = yes, set? */
                    {
                        fraction = ((stock->currentvalue - stock->lastvalue) / stock->lastvalue) * margin_reciprocal; /* save the normalized increment of stock's value; multiplying by margin_reciprocal, where 1 - (1 / margin_reciprocal) = how much of the portfolio can be bought on margin, effectively increases the normalized increment of stock's value */
                    }

                    else
                    {
                        fraction = ((stock->currentvalue - stock->lastvalue) / stock->lastvalue); /* save the normalized increment of stock's value */
                    }

                    fractionplusone = (double) 1.0 + fraction; /* save the normalized increment of stock's value, plus 1 */
                    average = average * ((double) 1.0 + (fraction / (double) stocks)); /* calculate the average index */
                    stock->capital = stock->capital * fractionplusone; /* amount of capital invested in the stock has changed */
                    stock->Gn = stock->Gn * fractionplusone; /* the normalized growth, ie., the value of the stock if its initial value was one dollar, has changed */
                }

                stock->lastvalue = stock->currentvalue; /* save the last value of the stock */
                stock->transactions ++; /* increment the number of changes in this stock's value */

                if (stock->current_updated == 0) /* updated in current interval flag, 0 = no, 1 = yes? */
                {
                    stock->last_updated = 0; /* no, reset the updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
                }

                else
                {
                    stock->last_updated ++; /* yes, increment the updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
                }

                stock->current_updated = 0; /* reset the updated in current interval flag, 0 = no, 1 = yes */
                average_value = average_value + stock->currentvalue; /* add this stock's value into the average value of index, computed as the average value of a share of stock */
                stock = stock->next_decision; /* reference the next element in the decision criteria list */
            }

        }

    }

    average_value = average_value / (double) stocks; /* calculate the average value of index, computed as the average value of a share of stock, stocks can not be zero, main () requires a valid stock with a time stamp, ie., the time stamp must change to call update_stocks () */

    return (retval); /* return any errors */
}

/*

Calculate the statistics for a stock.

static int statistics (HASH *stock);

I) The Shannon probability, P, which is the likelihood that a equity's
value will increase in the next time interval.

    A) There are five methods used to calculate the Shannon
    probability:

        1)

                avg
                --- + 1
                rms
            P = -------
                   2

        2)

                rms + 1
            P = --------
                   2

        3)

                sqrt (avg) + 1
            P = --------------
                      2

        4) The run length of expansions and contractions, erf (1 /
        sqrt (run length)).

        5) The short term persistence, or Hurst exponent, ie., the
        likelihood of consecutive like movements.

        where avg is the average of the normalized increments, and rms
        the root mean square of the normalized increments, and are
        calculated from the running average and root mean square
        values from the equity's time series.

II) The average, avg, root mean square, rms, and Shannon probability, P,
are compensated, by statistical estimate, for the data set size used
to calculate avg and rms, by one of the functions, confidencerms (),
confidenceavg (), or confidenceavgrms, depending on the method used in
the computation of the Shannon probability. The compensated value of
the Shannon probability is Peff, avgeff for the average, and rmseff
for the root mean square, where avgeff = avg - avge, and rmseff = rms
- rmse, and avge and rmse are the error levels associated with
Peff. Rewriting the formulas above, the effective Shannon
probabilities are:

    1)

               avgeff       avg - avge
               ------ + 1   ---------- + 1
               rmseff       rms + rmse
        Peff = ---------- = --------------
                  2               2

    2)

               rmseff + 1   (rms - rmse) + 1
        Peff = ---------- = ----------------
                   2               2

    3)

               sqrt (avgeff) + 1   sqrt (avg - avge) + 1
        Peff = ----------------- = ---------------------
                      2                      2

III) The growth of the equity's value, (maximum is obviously best,) is
calculated by:

    1)

        G = (rms + 1)^P * (rms - 1)^(1 - P)

    2) or for compensated values:

        Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

IV) The advantage of calculating the growth, as opposed to measuring
it, is that the size of the data set required for any method using the
compensated value of the average, avgeff, is very large, ie., using
compensated values of root mean square has "higher bandwidth," making
the program more responsive to equity value dynamics.

V) There are many methodological modifications that can be applied to
this function. Obviously, using rms = sqrt (avg) is one such. Using
un-compensated values of avg and rms is another. Using avgeff = rms
(2Peff - 1) as the decision criteria is yet another.

    A) Currently, the methods used are:

        1)

                Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

            where:

                        avg - avge
                        ---------- + 1
                        rms + rmse
                Peff =  --------------
                              2

        2)

                Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

            where:

                       (rms - rmse) + 1
                Peff = ----------------
                              2

        3)

                Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

            where:

                       sqrt (avg - avge) + 1
                Peff = ---------------------
                                 2

            and:

                rms = sqrt (avg)

            Note that there is a potential numerical exception on data
            sets of inadequate size, since avg can be negative. Under
            this numerical exception, the decision criteria is set to
            zero. (When the program first starts, this is a frequent
            occurrence.)

        4)

                Geff = (rms + 1)^P * (rms - 1)^(1 - P)

            where:

                    +- erf (1 / sqrt (run length)),
                    |  for positive run lengths
                P = |
                    |  erf (1 - (1 / sqrt (run length))),
                    +- for negative run lengths

            Note that this method uses the data analyzed by V)A)1 to
            establish an equity's long term gain in value-deviations
            from this value are analyzed using the error function of
            the square root of the run lengths to predict whether an
            equity's price is underpriced or overpriced in an attempt
            to determine optimal times to buy/sell.

        5)

                G = (1 + rms)^P * (1 - rms)^(1 - P)

            where:

                P = Hurst exponent = short term persistence

            Note that the implementation is to tally the number of
            consecutive like movements, for both positive and negative
            movements. Persistence means that there is more than a 50%
            chance that the next movement in an equity's price will be
            like the last. If an equity's value had pure Brownian
            motion characteristics, the number of consecutive like
            movements would be combinatorial, 0.5, 0.25, 0.125, ...
            (or 0.25, 0.125, 0.0625 ... if positive and negative like
            movements are tallied separately.) However, if there is
            persistence in an equity's value, say 0.6, then the values
            would be 0.36, 0.216, 0.1296, ... It is not assumed that
            the persistence is constant through the consecutive
            movements, ie., the persistence, P, is calculated by
            dividing the tally of the next, by the tally in the
            previous, (ie., 0.216 / 0.36 = 0.6,) for short term
            pattern matching.

        6)

                Random

            where:

                        avg - avge
                        ---------- + 1
                        rms + rmse
                Peff =  --------------
                              2

VI) The advantage of calculating the compensated growth by these five
methods is for comparative reasons:

    1) The first method uses both the average, avg, and root mean
    square, rms, of the stock's time series in the calculation of the
    decision criteria.

    2) The second method uses only the root mean square, rms, of the
    stock's time series in the calculation of the decision criteria.

    3) The third method uses only the average, avg, of the stock's
    time series in the calculation of the decision criteria.

    4) The fourth method chooses stocks using mean reverting dynamics.

    5) The fifth method chooses stocks using persistence.

    6) The sixth method chooses stocks at random, as a comparative
    strategy.

VII) Additionally, note that there is an approximation involved. The
way the void invest () function works is to allocate an equal fraction
of the capital in each equity. If the increments of equity values are
a Brownian motion fractal with a Gaussian/normal distribution, the
fraction of the capital invested in each equity should be be
proportional to the reciprocal of the the root mean square of the
increments, ie., such that the risk, (which is proportional to the
rms,) contributed by each equity is equal. This is very difficult to
achieve in practice because of the leptokurtosis in the
increments-there is no mathematical infrastructure for adding
Pareto-Levy distributions with different fractal dimensions.

Note that the second and third methods for calculating the decision
criteria are derived on the assumption that the stock's time series
has optimal growth, ie., rms = sqrt (avg).

Note, also, that the computation of statistics restricts the
variable's values to "reasonable" values:

    0 <= Par <= 1, Shannon probability, using avg and rms
    0 <= Pa <= 1, Shannon probability, using avg
    0 <= Pr <= 1, Shannon probability, using rms
    0 <= Pt <= 1, mean reverting probability
    0 <= Pp <= 1, persistence probability
    0 <= Pconfar <= 1, the confidence level in the measurment accuracy of the Shannon probability, using avg and rms
    0 <= Pconfa <= 1, the confidence level in the measurment accuracy of the Shannon probability, using avg
    0 <= Pconfr <= 1, the confidence level in the measurment accuracy of the Shannon probability, using rms
    0 <= Peffar <= 1, effective Shannon probability, using avg and rms, compensated for measurement accuracy by statistical estimate
    0 <= Peffa <= 1, effective Shannon probability, using avg, compensated for measurement accuracy by statistical estimate
    0 <= Peffr <= 1, effective Shannon probability, using rms, compensated for measurement accuracy by statistical estimate
    0 <= Pefft <= 1, effective Shannon probability, using mean reverting probability, compensated for measurement accuracy by statistical estimate
    0 <= Peffp <= 1, effective Shannon probability, using persistence probability, compensated for measurement accuracy by statistical estimate
    0 <= Pcomp <= 1, compensation for run length duration for Shannon probability
    0 <= avg <= 1, average of the normalized increments, avg
    0 <= rms <= 1, root mean square of the normalized increments, rms
    0 <= rootmean <= 1, variances of marginal increments of consecutive like movements

and that these "reasonable" values may create numerical exceptions
elsewhere, for example:

    G = (1 + rms)^P * (1 - rms)^(1 - P)

emplemented as:

    G = pow (1 + rms, P) * pow (1 - rms, 1 - P)

can have a numerical exception when rms == 1, and P == 1.

Returns NOERROR if successful, EALLOC on memory allocation error.

*/

#ifdef __STDC__

static int statistics (HASH *stock)

#else

static int statistics (stock)
HASH *stock;

#endif

{

    /* the statics are to avoid having to allocate space on the stack for doubles */

    static int positive_consecutive, /* running number of consecutive up movements */
               positive_consecutiveminusone, /* one less than the running number of consecutive up movements */
               negative_consecutive, /* running number of consecutive down movements */
               negative_consecutiveminusone, /* one less than the running number of consecutive down movements */
               positive_size, /* number of elements in the positive_histogram */
               negative_size, /* number of elements in the positive_histogram */
               voidcount; /* count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */

    static double fraction, /* normalized increment of stock's value */
                  count, /* stock's count of avg or rms values, as a double for computational expediency */
                  consecutive_start, /* start value for a run of consecutive like movements */
                  G, /* calculated growth in stock value */
                  Gain, /* calculated growth in stock value */
                  Gn, /* normalized growth, ie., the value of the stock if its initial value was one dollar */
                  Pp, /* persistence probability */
                  Pt, /* mean reverting probability */
                  Par, /* Shannon probability, using avg and rms */
                  avg, /* average of the normalized increments, avg */
                  rms, /* root mean square of the normalized increments, rms */
                  rmssum, /* running sum of rms values */
                  rootmean, /* variances of marginal increments of consecutive like movements */
                  pcount, /* count of consecutive like movements, as a double */
                  pcountminusone, /* count of consecutive like movements, as a double */
                  lastvalue; /* last value of stock */

    static PERSISTENCE *positive_histogram, /* consecutive up movments histogram */
                       *negative_histogram, /* consecutive down movments histogram */
                       *lastpositive_histogram, /* last consecutive up movments histogram */
                       *lastnegative_histogram; /* last consecutive down movments histogram */

    int retval = NOERROR; /* return value, assume no error */

    if ((stock->transactions > 1) && ((stock->current_updated == 1 && stock->last_updated > 0) || (stock->stats_update == 0))) /* two transactions are required for a stock, and updated in the current interval, and updated in the last interval, or told to, unconditionally, ie., transitions, enough? */
    {
        lastvalue = stock->lastvalue; /* initialize the last value of stock */
        fraction = (stock->currentvalue - lastvalue) / lastvalue; /* save the normalized increment of the stock; lastvalue can not be zero-it is rejected as a bad record in main () if it is */

        if (fraction < stock->maxinc) /* marginal increment larger than maximum acceptable marginal increment in stock's value? */
        {
            positive_size = stock->positive_size; /* initialize the number of elements in the positive_histogram */
            negative_size = stock->negative_size; /* initialize the number of elements in the positive_histogram */
            consecutive_start = stock->consecutive_start; /* initialize the start value for a run of consecutive like movements */
            voidcount = stock->voidcount; /* initialize the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
            Gn = stock->Gn; /* initialize the normalized growth, ie., the value of the stock if its initial value was one dollar */
            Par = stock->Par; /* initialize the Shannon probability, using avg and rms */
            positive_consecutive = stock->positive_consecutive; /* initialize the running number of consecutive up movements */
            negative_consecutive = stock->negative_consecutive; /* initialize the running number of consecutive down movements */
            positive_consecutiveminusone = positive_consecutive - 1; /* save one less than the running number of consecutive up movements */
            negative_consecutiveminusone = negative_consecutive - 1; /* save one less than the running number of consecutive down movements */
            stock->fraction = fraction; /* no, save the normalized increment of the stock's value */
            stock->count ++; /* increment the count of avg or rms values in the running sum of avg and rms values */
            count = (double) stock->count; /* save the stock's count of avg or rms values */
            stock->avgsum = stock->avgsum + fraction; /* save the running sum of avg values */
            avg = stock->avg = stock->avgsum / count; /* save the running average is the sum of the averages, divided by the number of elements; count can not be zero, it was just incremented */
            avg = stock->avg = (avg > (double) 1.0) ? (double) 1.0 : avg; /* protect the running average to be less than or equal to unity */
            rmssum = stock->rmssum = stock->rmssum + (fraction * fraction); /* save the running sum of rms values */
            rmssum = stock->rmssum = (rmssum < (double) 0.0) ? (double) 0.0 : rmssum; /* rms is the some of things squared, which is always positive, but protect from numerical issues */
            rms = stock->rms = sqrt (rmssum / count); /* save the square root of the running rms which is the sum of the squares of the rms, divided by the number of elements, minus one for non-biased estimate-note that the first count == 1 actually includes two samples; count can not be zero, it was just incremented; rmssum is equal to, or greater than zero since it is protected, above */
            rms = stock->rms = (rms > (double) 1.0) ? (double) 1.0 : rms; /* protect the square root of the running rms which is the sum of the squares of the rms, divided by the number of elements, minus one for non-biased estimate-note that the first count == 1 actually includes two samples; count can not be zero, it was just incremented; rmssum is equal to, or greater than zero since it is protected, above */
            stock->Pcomp = (double) 1.0 - ((double) 2.0 * (normal (((double) 1.0 / sqrt (count)) * sqrt_2) - (double) 0.5)); /* calculate the compensation for run length duration for Shannon probability */
            (void) confidenceavgrms (stock); /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate, using avg and rms */
            (void) confidenceavg (stock); /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate, using avg */
            (void) confidencerms (stock); /* save the effective Shannon probability, compensated for measurement accuracy by statistical estimate, using rms */
            Pt = stock->Pt = (double) 0.0; /* save the mean reverting probability, a suitable default value under numerical exceptions */

            if (rms > (double) 0.0 || Par > (double) 0.0) /* protect GAIN () from numerical exceptions */
            {
                Gain = GAIN (rms, Par); /* save the calculated growth in stock value */
                G = pow (Gain, count); /* calculate the calculated growth in stock value; stock->count was incremented above, and Gain is greater than or equal to, zero */

                if (voidcount >= 0) /* growth above average, ie., "zero free" time intervals postive? */
                {

                    if (Gn >= G) /* yes, normalized growth greater than calculated growth in stock value, ie., growth above average? */
                    {
                        voidcount ++; /* yes, increment the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        stock->voidcount = voidcount; /* save the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        Pt = ((double) 2.0 * (normal (((double) 1.0 / sqrt ((double) (voidcount + 1))) * sqrt_2) - (double) 0.5)); /* calculate the mean reverting probability; voidcount was just incremented from something that was >= zero */
                    }

                    else
                    {
                        voidcount = -1; /* no, reset and decrement the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        stock->voidcount = voidcount; /* save the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        Pt = (double) 1.0 - ((double) 2.0 * (normal (((double) 1.0 / sqrt_2) * sqrt_2) - (double) 0.5)); /* calculate the mean reverting probability */
                    }

                }

                else
                {

                    if (Gn <= G) /* yes, normalized growth less than calculated growth in stock value, ie., growth below average? */
                    {
                        voidcount --; /* yes, decrement the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        stock->voidcount = voidcount; /* save the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        Pt = (double) 1.0 - ((double) 2.0 * (normal (((double) 1.0 / sqrt ((double) - (voidcount - 1))) * sqrt_2) - (double) 0.5)); /* calculate the mean reverting probability; void count was at least decremented from a minus one to minus 2, so its negative is always positive */
                    }

                    else
                    {
                        voidcount = 1; /* no, reset and increment the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        stock->voidcount = voidcount; /* save the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
                        Pt = ((double) 2.0 * (normal (((double) 1.0 / sqrt_2) * sqrt_2) - (double) 0.5)); /* calculate the mean reverting probability */
                    }

                }

                Pt = (Pt > (double) 1.0) ? (double) 1.0 : Pt; /* save the mean reverting probability, which must be between zero and one, inclusive */
                stock->Pt = Pt; /* save the mean reverting probability, a suitable default value under numerical exceptions */
            }

            if (fraction > (double) 0.0) /* up movement? */
            {

                if (positive_consecutive > 0) /* yes, already a running number of consecutive up movements? */
                {
                    positive_consecutive ++; /* yes, increment the running number of consecutive up movements */
                    positive_consecutiveminusone = positive_consecutive - 1; /* save one less than the running number of consecutive up movements */
                    stock->positive_consecutive = positive_consecutive; /* save the running number of consecutive up movements */
                }

                else if (negative_consecutive > 0) /* else, already in running number of consecutive down movements? */
                {
                    negative_consecutive = 0; /* this is a transition from down to up movements, reset the running number of consecutive down movements */
                    negative_consecutiveminusone = negative_consecutive - 1; /* save one less than the running number of consecutive down movements */
                    stock->negative_consecutive = negative_consecutive; /* save the running number of consecutive down movements */
                    positive_consecutive = 1; /* and, this is the first in the running number of consecutive up movements */
                    positive_consecutiveminusone = positive_consecutive - 1; /* save one less than the running number of consecutive up movements */
                    stock->positive_consecutive = positive_consecutive; /* save the running number of consecutive up movements */
                    consecutive_start = lastvalue; /* save the start value for a run of consecutive like movements */
                    stock->consecutive_start = consecutive_start; /* save the start value for a run of consecutive like movements */
                }

                else /* if (stock->positive_consecutive == 0 && stock->negative_consecutive == 0) */ /* else if no running number of consecutive up movements or down movements, then this is the start of a running number of consecutive up movements */
                {
                    positive_consecutive = 1; /* this is the first in a running number of consecutive up movements */
                    positive_consecutiveminusone = positive_consecutive - 1; /* save one less than the running number of consecutive up movements */
                    stock->positive_consecutive = positive_consecutive; /* save the running number of consecutive up movements */
                    consecutive_start = lastvalue; /* save the start value for a run of consecutive like movements */
                    stock->consecutive_start = consecutive_start; /* save the start value for a run of consecutive like movements */
                }

            }

            else if (fraction < (double) 0.0) /* down movement? */
            {

                if (negative_consecutive > 0) /* yes, already a running number of consecutive down movements? */
                {
                    negative_consecutive ++; /* yes, increment the running number of consecutive down movements */
                    negative_consecutiveminusone = negative_consecutive - 1; /* save one less than the running number of consecutive down movements */
                    stock->negative_consecutive = negative_consecutive; /* save the running number of consecutive down movements */
                }

                else if (positive_consecutive > 0) /* else, already in running number of consecutive up movements? */
                {
                    positive_consecutive = 0; /* this is a transition from up to down movements, reset the running number of consecutive up movements */
                    positive_consecutiveminusone = positive_consecutive - 1; /* save one less than the running number of consecutive up movements */
                    stock->positive_consecutive = positive_consecutive; /* save the running number of consecutive up movements */
                    negative_consecutive = 1; /* and, this is the first in the running number of consecutive down movements */
                    negative_consecutiveminusone = negative_consecutive - 1; /* save one less than the running number of consecutive down movements */
                    stock->negative_consecutive = negative_consecutive; /* save the running number of consecutive down movements */
                    consecutive_start = lastvalue; /* save the start value for a run of consecutive like movements */
                    stock->consecutive_start = consecutive_start; /* save the start value for a run of consecutive like movements */
                }

                else /* if (stock->positive_consecutive == 0 && stock->negative_consecutive == 0) */ /* if no running number of consecutive up or down movements, then this is the start of a running number of consecutive down movements */
                {
                    negative_consecutive = 1; /* this is the first in a running number of consecutive down movements */
                    negative_consecutiveminusone = negative_consecutive - 1; /* save one less than the running number of consecutive down movements */
                    stock->negative_consecutive = negative_consecutive; /* save the running number of consecutive down movements */
                    consecutive_start = lastvalue; /* save the start value for a run of consecutive like movements */
                    stock->consecutive_start = consecutive_start; /* save the start value for a run of consecutive like movements */
                }

            }

            else /* if (fraction == (double) 0.0) */ /* neither an up or down movement? */
            {

                if (positive_consecutive > 0) /* yes, already a running number of consecutive up movements? */
                {
                    positive_consecutive ++; /* yes, since it was not a COMPLETE transition from up to down, increment the running number of consecutive up movements */
                    positive_consecutiveminusone = positive_consecutive - 1; /* save one less than the running number of consecutive up movements */
                    stock->positive_consecutive = positive_consecutive; /* save the running number of consecutive up movements */
                }

                else if (negative_consecutive > 0) /* else if already a running number of consecutive down movements? */
                {
                    negative_consecutive ++; /* yes, since it was not a COMPLETE transition from down to up, increment the running number of consecutive down movements */
                    negative_consecutiveminusone = negative_consecutive - 1; /* save one less than the running number of consecutive down movements */
                    stock->negative_consecutive = negative_consecutive; /* save the running number of consecutive down movements */
                }

                else /* else if (stock->positive_consecutive == 0 && stock->negative_consecutive == 0) */ /* else if no running number of consecutive up or down movements */
                {
                    /* do nothing */
                }

            }

            if (positive_consecutive > 0) /* running number of consecutive up movements? */
            {
                positive_histogram = stock->positive_histogram; /* save the last consecutive up movments histogram */

                if (positive_size < positive_consecutive) /* number of elements in the positive_histogram large enough to hold the running number of consecutive up movements? */
                {
                    lastpositive_histogram = positive_histogram; /* save the last consecutive up movments histogram */

                    if ((positive_histogram = (PERSISTENCE *) realloc (positive_histogram, (size_t) (positive_consecutive) * sizeof (PERSISTENCE))) != (PERSISTENCE *) 0) /* allocate space for the consecutive up movments histogram */
                    {
                        stock->positive_histogram = positive_histogram; /* save the consecutive up movments histogram */
                        positive_size = positive_consecutive; /* and store the number of elements in the positive_histogram */
                        stock->positive_size = positive_size; /* save the number of elements in the positive_histogram */
                        positive_histogram[positive_consecutiveminusone].count = 0; /* and zero the count of consecutive like movements */
                        positive_histogram[positive_consecutiveminusone].rootmean = (double) 0.0; /* and zero the sum of the variances of marginal increments of consecutive like movements */
                    }

                    else
                    {
                        stock->positive_histogram = lastpositive_histogram; /* restore the consecutive up movments histogram */
                        retval = EALLOC; /* assume error allocating memory */
                    }

                }

                if (retval == NOERROR) /* any errors? */
                {
                    pcountminusone = (double) ++ positive_histogram[positive_consecutiveminusone].count; /* increment the running number of consecutive up movements in the positive_histogram */

                    if (positive_consecutive < positive_size) /* statistics on next like movement exist? */
                    {
                        pcount = (double) positive_histogram[positive_consecutive].count; /* save the running number of consecutive up movements in the positive_histogram */
                        Pp = pcount / pcountminusone; /* yes, calculate the persistence probability, ie., the likelyhood of an up movement in the next time interval; pcountminusone, can not be zero-it was just incremented */
                        Pp = (Pp > (double) 1.0) ? (double) 1.0 : Pp; /* save the persistence probability */
                        rootmean = pow (positive_histogram[positive_consecutive].rootmean / pcount, Pp); /* save the variance of marginal increments of consecutive like movements; pcount can not be zero if positive_consecutive < stock->positive_size; neither can positive_histogram[positive_consecutive].rootmean for the same reason; likewise for Pp, ie., all are strictly greater than zero */
                        rootmean = (rootmean > (double) 1.0) ? (double) 1.0 : rootmean; /* save the variance of marginal increments of consecutive like movements */
                        stock->Pp = Pp; /* save the persistence probability */
                        stock->rootmean = rootmean; /* save the variance of marginal increments of consecutive like movements */
                    }

                    else
                    {
                        stock->Pp = (double) 0.0; /* no, assume the persistence probability, ie., the likelyhood of an up movement in the next time interval is zero */
                        stock->rootmean = (double) 0.0; /* save the variance of marginal increments of consecutive like movements */
                    }


                    if (positive_consecutive > 1) /* running number of consecutive up movements greater than one? */
                    {
                        Pp = (double) positive_histogram[positive_consecutiveminusone].count / (double) positive_histogram[positive_consecutive - 2].count; /* calculate the persistence probability, ie., the likelyhood of an up movement in the next time interval; both positive_histogram[positive_consecutiveminusone].count and positive_histogram[positive_consecutive - 2].count are greater than zero if positive_consecutive > 1, so Pp must be greater than zero */
                        rootmean = positive_histogram[positive_consecutiveminusone].rootmean; /* save the sum of variances of marginal increments of consecutive like movements; positive_histogram[positive_consecutiveminusone].rootmean is greater than zero if positive_consecutive > 1 */
                        positive_histogram[positive_consecutiveminusone].rootmean = rootmean + pow (fraction, (double) 1.0 / Pp); /* save the sum of variances of marginal increments of consecutive like movements; both fraction and Pp are greater than zero */
                    }

                }

            }

            else if (negative_consecutive > 0) /* running number of consecutive down movements? */
            {
                negative_histogram = stock->negative_histogram; /* save the last consecutive down movments histogram */

                if (negative_size < negative_consecutive) /* number of elements in the negative_histogram large enough to hold the running number of consecutive down movements? */
                {
                    lastnegative_histogram = negative_histogram; /* save the last consecutive down movments histogram */

                    if ((negative_histogram = (PERSISTENCE *) realloc (negative_histogram, (size_t) (negative_consecutive) * sizeof (PERSISTENCE))) != (PERSISTENCE *) 0) /* allocate space for the consecutive down movments histogram */
                    {
                        stock->negative_histogram = negative_histogram; /* save the consecutive down movments histogram */
                        negative_size = negative_consecutive; /* and store the number of elements in the negative_histogram */
                        stock->negative_size = negative_size; /* save the number of elements in the positive_histogram */
                        negative_histogram[negative_consecutiveminusone].count = 0; /* and zero the count of consecutive like movements */
                        negative_histogram[negative_consecutiveminusone].rootmean = (double) 0.0; /* and zero the sum of the variances of marginal increments of consecutive like movements */
                    }

                    else
                    {
                        stock->negative_histogram = lastnegative_histogram; /* restore the consecutive down movments histogram */
                        retval = EALLOC; /* assume error allocating memory */
                    }

                }

                if (retval == NOERROR) /* any errors? */
                {
                    pcountminusone = (double) ++ negative_histogram[negative_consecutiveminusone].count; /* increment the running number of consecutive down movements in the negative_histogram */

                    if (negative_consecutive < negative_size) /* statistics on next like movement exist? */
                    {
                        pcount = (double) negative_histogram[negative_consecutive].count; /* save the running number of consecutive down movements in the negative_histogram */
                        Pp = pcount / pcountminusone; /* yes, calculate the persistence probability, ie., the likelyhood of a down movement in the next time interval; pcountminusone, can not be zero-it was just incremented */
                        Pp = (Pp > (double) 1.0) ? (double) 1.0 : Pp; /* save the persistence probability */
                        rootmean = pow (negative_histogram[negative_consecutive].rootmean / pcount, Pp); /* save the variance of marginal increments of consecutive like movements; pcount can not be zero if negative_consecutive < stock->negative_size; neither can negative_histogram[negative_consecutive].rootmean for the same reason; likewise for Pp, ie., all are strictly greater than zero */
                        rootmean = (rootmean > (double) 1.0) ? (double) 1.0 : rootmean; /* save the variance of marginal increments of consecutive like movements */
                        stock->Pp = (double) 1.0 - Pp; /* save the persistence probability */
                        stock->rootmean = rootmean; /* save the variance of marginal increments of consecutive like movements */
                    }

                    else
                    {
                        stock->Pp = (double) 1.0; /* no, assume the persistence probability, ie., the likelyhood of a down movement in the next time interval is zero, ie., the likelyhood of an up movement is unity */
                        stock->rootmean = (double) 0.0; /* save the variance of marginal increments of consecutive like movements */
                    }

                    if (negative_consecutive > 1) /* running number of consecutive down movements greater than one? */
                    {
                        Pp = (double) negative_histogram[negative_consecutiveminusone].count / (double) negative_histogram[negative_consecutive - 2].count; /* calculate the persistence probability, ie., the likelyhood of a down movement in the next time interval; both negative_histogram[negative_consecutiveminusone].count and negative_histogram[negative_consecutive - 2].count are greater than zero if negative_consecutive > 1, so Pp must be greater than zero */
                        rootmean = negative_histogram[negative_consecutiveminusone].rootmean; /* save the sum of variances of marginal increments of consecutive like movements; negative_histogram[negative_consecutiveminusone].rootmean is greater than zero if negative_consecutive > 1 */
                        negative_histogram[negative_consecutiveminusone].rootmean = rootmean + pow (-fraction, (double) 1.0 / Pp); /* save the sum of variances of marginal increments of consecutive like movements; both fraction and Pp are greater than zero */
                    }

                }

            }

            else /* if (negative_consecutive == 0 && positive_consecutive == 0) */ /* running number of consecutive down and up movements? */
            {
                /* do nothing */
            }

        }

    }

    return (retval); /* return any errors */
}

/*

Set the decision criteria for a stock.

static int decisions (HASH *stock, enum allocation_method allocate_assets);

I) The Shannon probability, P, which is the likelihood that a equity's
value will increase in the next time interval.

    A) There are five methods used to calculate the Shannon
    probability:

        1)

                avg
                --- + 1
                rms
            P = -------
                   2

        2)

                rms + 1
            P = --------
                   2

        3)

                sqrt (avg) + 1
            P = --------------
                      2

        4) The run length of expansions and contractions, erf (1 /
        sqrt (run length)).

        5) The short term persistence, or Hurst exponent, ie., the
        likelihood of consecutive like movements.

        where avg is the average of the normalized increments, and rms
        the root mean square of the normalized increments, and are
        calculated from the running average and root mean square
        values from the equity's time series.

II) The average, avg, root mean square, rms, and Shannon probability, P,
are compensated, by statistical estimate, for the data set size used
to calculate avg and rms, by one of the functions, confidencerms (),
confidenceavg (), or confidenceavgrms, depending on the method used in
the computation of the Shannon probability. The compensated value of
the Shannon probability is Peff, avgeff for the average, and rmseff
for the root mean square, where avgeff = avg - avge, and rmseff = rms
- rmse, and avge and rmse are the error levels associated with
Peff. Rewriting the formulas above, the effective Shannon
probabilities are:

    1)

               avgeff       avg - avge
               ------ + 1   ---------- + 1
               rmseff       rms + rmse
        Peff = ---------- = --------------
                  2               2

    2)

               rmseff + 1   (rms - rmse) + 1
        Peff = ---------- = ----------------
                   2               2

    3)

               sqrt (avgeff) + 1   sqrt (avg - avge) + 1
        Peff = ----------------- = ---------------------
                      2                      2

III) The growth of the equity's value, (maximum is obviously best,) is
calculated by:

    1)

        G = (rms + 1)^P * (rms - 1)^(1 - P)

    2) or for compensated values:

        Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

IV) The advantage of calculating the growth, as opposed to measuring
it, is that the size of the data set required for any method using the
compensated value of the average, avgeff, is very large, ie., using
compensated values of root mean square has "higher bandwidth," making
the program more responsive to equity value dynamics.

V) There are many methodological modifications that can be applied to
this function. Obviously, using rms = sqrt (avg) is one such. Using
un-compensated values of avg and rms is another. Using avgeff = rms
(2Peff - 1) as the decision criteria is yet another.

    A) Currently, the methods used are:

        1)

                Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

            where:

                        avg - avge
                        ---------- + 1
                        rms + rmse
                Peff =  --------------
                              2

        2)

                Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

            where:

                       (rms - rmse) + 1
                Peff = ----------------
                              2

        3)

                Geff = (rms + 1)^Peff * (rms - 1)^(1 - Peff)

            where:

                       sqrt (avg - avge) + 1
                Peff = ---------------------
                                 2

            and:

                rms = sqrt (avg)

            Note that there is a potential numerical exception on data
            sets of inadequate size, since avg can be negative. Under
            this numerical exception, the decision criteria is set to
            zero. (When the program first starts, this is a frequent
            occurrence.)

        4)

                Geff = (rms + 1)^P * (rms - 1)^(1 - P)

            where:

                    +- erf (1 / sqrt (run length)),
                    |  for positive run lengths
                P = |
                    |  erf (1 - (1 / sqrt (run length))),
                    +- for negative run lengths

            Note that this method uses the data analyzed by V)A)1 to
            establish an equity's long term gain in value-deviations
            from this value are analyzed using the error function of
            the square root of the run lengths to predict whether an
            equity's price is underpriced or overpriced in an attempt
            to determine optimal times to buy/sell.

        5)

                G = (1 + rms)^P * (1 - rms)^(1 - P)

            where:

                P = Hurst exponent = short term persistence

            Note that the implementation is to tally the number of
            consecutive like movements, for both positive and negative
            movements. Persistence means that there is more than a 50%
            chance that the next movement in an equity's price will be
            like the last. If an equity's value had pure Brownian
            motion characteristics, the number of consecutive like
            movements would be combinatorial, 0.5, 0.25, 0.125, ...
            (or 0.25, 0.125, 0.0625 ... if positive and negative like
            movements are tallied separately.) However, if there is
            persistence in an equity's value, say 0.6, then the values
            would be 0.36, 0.216, 0.1296, ... It is not assumed that
            the persistence is constant through the consecutive
            movements, ie., the persistence, P, is calculated by
            dividing the tally of the next, by the tally in the
            previous, (ie., 0.216 / 0.36 = 0.6,) for short term
            pattern matching.

        6)

                Random

            where:

                        avg - avge
                        ---------- + 1
                        rms + rmse
                Peff =  --------------
                              2

VI) The advantage of calculating the compensated growth by these three
methods is for comparative reasons:

    1) The first method uses both the average, avg, and root mean
    square, rms, of the stock's time series in the calculation of the
    decision criteria.

    2) The second method uses only the root mean square, rms, of the
    stock's time series in the calculation of the decision criteria.

    3) The third method uses only the average, avg, of the stock's
    time series in the calculation of the decision criteria.

    4) The fourth method chooses stocks using mean reverting dynamics.

    5) The fifth method chooses stocks using persistence.

    6) The sixth method chooses stocks at random, as a comparative
    strategy.

VII) Additionally, note that there is an approximation involved. The
way the void invest () function works is to allocate an equal fraction
of the capital in each equity. If the increments of equity values are
a Brownian motion fractal with a Gaussian/normal distribution, the
fraction of the capital invested in each equity should be be
proportional to the reciprocal of the the root mean square of the
increments, ie., such that the risk, (which is proportional to the
rms,) contributed by each equity is equal. This is very difficult to
achieve in practice because of the leptokurtosis in the
increments-there is no mathematical infrastructure for adding
Pareto-Levy distributions with different fractal dimensions.

However, if the averages of the marginal increments for the stocks
differ, and/or if the root mean square of the marginal increments for
the stocks differ, the optimum fraction can be approximated through
heuristic arguments justifying the approximation are that in a
universe of stocks, by investing in each and every stock, the
portfolio growth characteristics will be a nearly smooth exponential,
since the root mean square of the marginal increments of the stocks
add root mean square, and the average of the marginal increments, add
linearly.

A reasonable approximation to the optimal value at risk, (VAR,) rms =
f = 2P - 1, is when the asset allocation for all n many equities is
proportional to their individual avg / rms.  The rationale for the
approximation is that for a single investment, the optimal growth is
to put a fraction of the portfolio equal to rms at risk, which happens
when the fraction is equal to avg / rms, making avg =
rms^2.

Assembling the portfolio for minimum risk is somewhat more difficult
do to the data constructs chosen; it should be based on the maximum
rms, compensated for data set size and run length duration. This
means, use Pa, the Shannon probability using avg, to compute the
minimum avg, and Par, the Shannon probability using avg and rms, (both
possibly compensated for data set size and/or run length duration,)
since the rms used to compute Par is maximum, e.g., Pa * Pcomp = (sqrt
(avgm) + 1) / 2, where avgm is the minimum avg, and, Par * Pcomp =
(avg / rmsM + 1) / 2, or the fraction, f, allocated to a specific
stock would be proportional to 2 * Par * Pcomp - 1 / (2 * Pa * Pcomp -
1)^2, provided 0.5 < Par * Pcomp < 1 and 0.5 < Pa * Pcomp < 1.

Note that the second and third methods for calculating the decision
criteria are derived on the assumption that the stock's time series
has optimal growth, ie., rms = sqrt (avg).

Returns NOERROR, always.

*/

#ifdef __STDC__

static int decisions (HASH *stock, enum allocation_method allocate_assets)

#else

static int decisions (stock, allocate_assets)
HASH *stock;
enum allocation_method allocate_assets;

#endif

{

    /* the statics are to avoid having to allocate space on the stack for doubles */

    static double Pcomp, /* compensation for run length duration for Shannon probability */
                  Par, /* Shannon probability, using avg and rms */
                  Pa, /* Shannon probability, using avg */
                  Pr, /* Shannon probability, using rms */
                  Pt, /* mean reverting probability */
                  Pp, /* persistence probability */
                  avg, /* average of the normalized increments, avg */
                  rms, /* root mean square of the normalized increments, rms */
                  sqrtavg, /* square root of the average of the normalized increments, avg */
                  P, /* compensated Shannon probability */
                  temp1, /* temporary float */
                  temp2; /* temporary float */

    int retval = NOERROR; /* return value, assume no error */

    if ((stock->transactions > 1) && ((stock->current_updated == 1 && stock->last_updated > 0) || (stock->stats_update == 0))) /* two transactions are required for a stock, and updated in the current interval, and updated in the last interval, or told to, unconditionally, ie., transitions, enough? */
    {

        if (stock->noest == 0) /* don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate, set? */
        {
            Par = stock->Peffar; /* initialize the Shannon probability, using avg and rms */
            Pa = stock->Peffa; /* initialize the Shannon probability, using avg */
            Pr = stock->Peffr; /* initialize the Shannon probability, using rms */
            Pt = stock->Pt; /* initialize the mean reverting probability */
            Pp = stock->Pp; /* initialize the persistence probability */
        }

        else
        {
            Par = stock->Par; /* initialize the Shannon probability, using avg and rms */
            Pa = stock->Pa; /* initialize the Shannon probability, using avg */
            Pr = stock->Pr; /* initialize the Shannon probability, using rms */
            Pt = stock->Pt; /* initialize the mean reverting probability */
            Pp = stock->Pp; /* initialize the persistence probability */
        }

        avg = stock->avg; /* initialize the average of the normalized increments, avg */
        rms = stock->rms; /* initialize the root mean square of the normalized increments, rms */
        Pcomp = (stock->comp == 1) ? stock->Pcomp : (double) 1.0; /* if the compensate the Shannon probability, P, for run length duration flag set, save the compensation for run length duration for Shannon probability */
        stock->decision = (double) 0.0; /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value, assume an error */
        stock->allocation_fraction = (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock, assume an error */

        switch (stock->method) /* which method of computation for determination of a stock's decision criteria, used in stock selection */
        {

            case M_AVGRMS: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (avg / rms + 1) / 2 */

                P = Par * Pcomp; /* compensated Shannon probability */

                if (rms < (double) 1.0 && P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                {
                    stock->decision = GAIN (rms, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                    switch (allocate_assets) /* optimize asset allocation? */
                    {

                        case M_EQUAL: /* equal allocation? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                        case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                        case M_MINIMUM_RISK: /* allocate for minimum risk */

                            temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                            temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                            if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                            {
                                stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                            }

                            break;

                        default: /* illegal switch? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                    }

                }

                break;

            case M_RMS: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = (rms + 1) / 2 */

                P = Pr * Pcomp; /* compensated Shannon probability */

                if (rms < (double) 1.0 && P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                {
                    stock->decision = GAIN (rms, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                    switch (allocate_assets) /* optimize asset allocation? */
                    {

                        case M_EQUAL: /* equal allocation? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                        case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                        case M_MINIMUM_RISK: /* allocate for minimum risk */

                            temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                            temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                            if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                            {
                                stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                            }

                            break;

                        default: /* illegal switch? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                    }

                }

                break;

            case M_AVG: /* decision criteria: G = (1 + sqrt (avg))^P * (1 - sqrt (avg))^(1 - P), P = (sqrt (avg) + 1) / 2 */

                if (avg >= (double) 0.0) /* avg < zero is a numerical exception for the sqrt () function */
                {
                    sqrtavg = sqrt (avg); /* save the square root of the average of the normalized increments, avg */
                    P = Pa * Pcomp; /* compensated Shannon probability */

                    if (sqrtavg < (double) 1.0 && P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                    {
                        stock->decision = GAIN (sqrtavg, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                        switch (allocate_assets) /* optimize asset allocation? */
                        {

                            case M_EQUAL: /* equal allocation? */

                                stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                break;

                            case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                                stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                break;

                            case M_MINIMUM_RISK: /* allocate for minimum risk */

                                temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                                temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                                if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                                {
                                    stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                                }

                                break;

                            default: /* illegal switch? */

                                stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                break;

                        }

                    }

                }

                break;

            case M_LENGTH: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = erf (1 / sqrt (run length)) */

                if (rms < (double) 1.0) /* protect GAIN () from numerical exceptions */
                {

                    if (stock->noest == 0) /* don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate, set */
                    {
                        P = Pt * stock->Pconfr * Pcomp; /* compensated Shannon probability */

                        if (P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                        {
                            stock->decision = GAIN (rms, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                            switch (allocate_assets) /* optimize asset allocation? */
                            {

                                case M_EQUAL: /* equal allocation? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MINIMUM_RISK: /* allocate for minimum risk */

                                    temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                                    temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                                    if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                                    {
                                        stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                                    }

                                    break;

                                default: /* illegal switch? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                            }

                        }

                    }

                    else
                    {
                        P = Pt * Pcomp; /* compensated Shannon probability */

                        if (P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                        {
                            stock->decision = GAIN (rms, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                            switch (allocate_assets) /* optimize asset allocation? */
                            {

                                case M_EQUAL: /* equal allocation? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MINIMUM_RISK: /* allocate for minimum risk */

                                    temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                                    temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                                    if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                                    {
                                        stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                                    }

                                    break;

                                default: /* illegal switch? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                            }

                        }

                    }

                }

                break;

            case M_PERSISTENCE: /* decision criteria: G = (1 + rms)^P * (1 - rms)^(1 - P), P = Hurst exponent = short term persistence */

                if (rms < (double) 1.0) /* protect GAIN () from numerical exceptions */
                {

                    if (stock->noest == 0) /* don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate, set */
                    {
                        P = Pp * stock->Pconfr * Pcomp; /* compensated Shannon probability */

                        if (P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                        {
                            stock->decision = GAIN (rms, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                            switch (allocate_assets) /* optimize asset allocation? */
                            {

                                case M_EQUAL: /* equal allocation? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MINIMUM_RISK: /* allocate for minimum risk */

                                    temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                                    temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                                    if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                                    {
                                        stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                                    }

                                    break;

                                default: /* illegal switch? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                            }

                        }

                    }

                    else
                    {
                        P = Pp * Pcomp; /* compensated Shannon probability */

                        if (P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                        {
                            stock->decision = GAIN (rms, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                            switch (allocate_assets) /* optimize asset allocation? */
                            {

                                case M_EQUAL: /* equal allocation? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                                case M_MINIMUM_RISK: /* allocate for minimum risk */

                                    temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                                    temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                                    if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                                    {
                                        stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                                    }

                                    break;

                                default: /* illegal switch? */

                                    stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                                    break;

                            }

                        }

                    }

                }

                break;

            case M_RANDOM: /* decision criteria: G = random */

                stock->decision = (rand () / (double) RAND_MAX); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */
                stock->allocation_fraction =  (double) 1.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                break;

            default: /* illegal switch? */

                P = Par * Pcomp; /* compensated Shannon probability */

                if (rms < (double) 1.0 && P < (double) 1.0) /* protect GAIN () from numerical exceptions */
                {
                    stock->decision = GAIN (rms, P); /* save the decision criteria for investment in this stock, qsortlist () will sort the list of next_decision elements by this value */

                    switch (allocate_assets) /* optimize asset allocation? */
                    {

                        case M_EQUAL: /* equal allocation? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                        case M_MAXIMUM_GAIN: /* allocate for maximum gain? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                        case M_MINIMUM_RISK: /* allocate for minimum risk */

                            temp1 = Pa * Pcomp; /* save the Shannon probability, using avg */
                            temp2 = Par * Pcomp; /* save the Shannon probability, using avg and rms */

                            if ((double) 0.5 < temp1 && temp1 < (double) 1.0 && (double) 0.5 < temp2 && temp2 < (double) 1.0) /* protect against numerical exceptions */
                            {
                                stock->allocation_fraction = (((double) 2.0 * temp2) - (double) 1.0) / ((((double) 2.0 * temp1) - (double) 1.0) * (((double) 2.0 * temp1) - (double) 1.0)); /* save the fraction of the portfolio that is to be allocated to a stock */
                            }

                            break;

                        default: /* illegal switch? */

                            stock->allocation_fraction = (P > (double) 0.5) ? (((double) 2.0 * P) - (double) 1.0) : (double) 0.0; /* save the fraction of the portfolio that is to be allocated to a stock */
                            break;

                    }

                }

                break;

        }

    }

    return (retval); /* return any errors */
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

Hash table functions.

static int hash_init (HASHTABLE *hash_table);
static int hash_insert (HASHTABLE *hash_table, void *data);
static HASH *hash_find (HASHTABLE *hash_table, void *data);
static int hash_delete (HASHTABLE *hash_table, void *data);
static void hash_term (HASHTABLE *hash_table);

I) The objective of the hash functions are to provide a means of
building look up tables in an expedient fashion:

    A) Each hash table consists of elements:

        1) A hash table index, consisting of an array of hash
        elements, of type HASH:

            a) The number of elements in the hash table index is
            determined by the hash table's descriptor structure
            element, hash_size.

            b) Each of the elements in the hash table index are the
            head elements in a doubly linked list:

                i) Each doubly linked list contains the hash table
                index at its head, and hash table elements, also of
                type HASH, that reference data stored in the hash
                table.

        2) Each element in the hash table is referenced via a key
        value:

            a) There can be no assumptions made about the data, data's
            structure, or data's complexity.

            b) This means that functions, unique to each hash table,
            must be implemented to:

                i) Allocate any data space required.

                ii) Compare key values that reference the data.

                iii) Deallocate any data allocated.

            c) References to these functions are stored in the hash
            table's descriptor structure, which has the following
            elements:

                i) hash_size is the size of the hash table.

                ii) table is a reference to the hash table.

                iii) mkhash is a reference to the function that
                allocates hash table elements and data space.

                iv) cmphash is a reference to the function that
                compares hash table element keys.

                v) rmhash is a reference to the function that
                deallocates hash table elements and data space.

                vi) comphash is a reference to the function that
                computes the hash value of a key.

        3) The hash table element structure, of type HASH, has the
        following elements:

            a) struct hash_struct *previous, which is a reference to
            next element in hash's doubly, circular linked list, and
            used by the hash system's internal list operations.

            b) struct hash_struct *next, which is a reference to
            previous element in hash's doubly, circular linked list,
            and used by the hash system's internal list operations.

            c) Any collection of other elements as defined
            appropriately by the user, and can include:

                i) References to other data and data structures.

                ii) Numerical data, etc.

                iii) Data referenced by these elements are allocated
                and deallocated by the user definable functions,
                mkhash () and rmhash (), respectively.

    B) The hash table operations are performed by the following
    functions:

        1) int hash_init (HASHTABLE *hash_table), which initializes
        the hash table.

        2) int hash_insert (HASHTABLE *hash_table, void *data);, which
        inserts elements, keys, and data in the hash table.

        3) HASH *hash_find (HASHTABLE *hash_table, void *data), which
        fetches elements from the hash table.

        4) int hash_delete (HASHTABLE *hash_table, void *data), which
        deletes elements from the hash table.

        5) void hash_term (HASHTABLE *hash_table), which terminates
        use of the hash table, calling hash_delete () for each element
        remaining in the hash table.

    C) All hash table functions return success or failure:

        1) Functions that return an integer success/failure error code
        set the integer, hash_error to the return value of the
        function.

        2) Functions that return an indirect reference return
        success/failure error code (NULL corresponds to an error,) and
        set the integer, hash_error to a unique error value.

        3) All hash functions set hash_error (numerical assignments
        are made in hash.h:)

            a) NOERROR if no error.

            b) EALLOC if error allocating memory.

            c) HASH_DUP_ERR if a duplicate key when inserting key into
            hash table.

            d) HASH_MK_ERR if hash table mkhash () failure, and mkhash
            () did not set hash_error to NOERROR.

            e) HASH_KEY_ERR if hash table key not found.

II) int hash_init (HASHTABLE *hash_table) initializes the hash tables
data structures, and must be called prior to any operations on a hash
table:

    A) The single argument:

        1) hash_table is a reference to the hash table descriptor
        structure.

    B) The return value:

        1) Returns NOERROR if successful.

        2) Returns EALLOC if a memory allocation error.

        3) Returns HASH_INI_ERR if the hash table was already
        initialized.

III) int hash_insert (HASHTABLE *hash_table, void *data) inserts a new
hash element into a hash table:

     A) The arguments:

         1) hash_table is a reference to the hash table descriptor
         structure.

         2) data is a reference to the key value:

             a) This reference is passed to the key comparison routine
             and the hash element construction routines specified in
             the hash descriptor structure.

     B) The return value:

         1) Returns NOERROR if successful.

         2) Returns HASH_DUP_ERR if a duplicate key was found.

         3) If an error occured in mkhash ():

             a) If mkhash () set hash_error to other than NOERROR,
             returns the value in hash_error.

             b) If mkhash () did not set hash_error to other than
             NOERROR, returns HASH_MK_ERR.

IV) HASH *hash_find (HASHTABLE *hash_table, void *data) searches the
hash table for an element that matches a given key, according to the
key comparison routine specified in the hash table descriptor
structure:

    A) The arguments:

        1) hash_table is a reference to the hash table descriptor.

        2) data is a reference to the element's key value:

            a) This reference is passed to the key comparison routine
            specified in the hash table descriptor structure.

    B) The return value:

        1) Returns a reference to the hash element found in the hash
        table.

        2) Returns NULL if the element was not found in the hash
        table.

V) int hash_delete (HASHTABLE *hash_table, void *data) deletes an
element from a hash table:

    A) The arguments:

        1) hash_table is a reference to the hash table descriptor.

        2) data is a reference to the element's key value:.

            a) This reference is passed to the key comparison routine
            specified in the hash table descriptor structure.

    B) The return value:

        1) Returns NOERROR if successful.

        2) Returns HASH_DEL_ERR if key not found.

VI) void hash_term (HASHTABLE *hash_table) deletes a hash table,
including all remaining elements, and data space referenced by the
elements:

    A) The single argument:

        1) hash_table is a reference to the hash table descriptor
        structure.

    B) There is no return value.

VII) Hash table descriptor structure:

    A) Before any use of hash routines, the handler functions, mkhash
    (), cmphash (), rmhash (), and comphash (), must be specified
    along with the hash table size, hash_size, in a hash table
    descriptor of type HASHTABLE:

        1) The HASHTABLE element, table, which references the hash
        table, should be initialized to zero before calling
        hash_init ().

        2) For example:

            HASHTABLE my_table =
                {2729, 0, my_mkhash, my_cmphash, my_rmhash, my_comphash};

    B) The hash table descriptor structure is defined in hash.h, with
    the following elements:

        1) hash_size, size of hash table:

            a) hash_size should be a prime number for optimal
            distribution of the hash elements in the hash array.

        2) table, which is a reference to hash to the table:

            a) This element is, generally, used only by the hash
            algorithms, but should be initialized to zero before
            calling hash_init ().

        3) mkhash, which is a reference to the function that allocates
        hash elements and data space:

            a) mkhash () creates a hash element and its allocated data
            for insertion into a hash table.

            b) struct HASH *(*mkhash) (void *data)

            c) The single argument:

                i) data is the address of the key associated with the
                element.

            d) The return value:

                i) The address of the element constructed.

                ii) Returns NULL if no element was constructed.

            e) mkhash must initialize the link elements, next and
            previous, both, to reference the element on return.

        4) cmphash, which is a reference to the function that compares
        element's keys:

            a) cmphash () compares a key against a key associated with
            a hash table element.

            b) int (*cmphash) (void *data, HASH *element).

            c) The arguments:

                i) data is a reference to a key.

                ii) element is a reference to a HASH structure to
                which the key should be compared.

            d) The return value:

                i) Returns 0 if data and the key associated with the
                element key are equal.

                ii) Returns non-zero if data and the key associated
                with the element are not equal.

        5) rmhash, which is a reference to the function that
        deallocates hash elements and data space:

            a) rmhash () is called to delete a hash table element and
            its allocated data from a hash table-given the address of
            the element created by mkhash (), it should reverse
            operations of mkhash ().

            b) void (*rmhash) (HASH *element).

            c) The single argument:

                i) element is a reference to the hash table element to
                delete.

            d) There is no return value.

        6) comphash, which is reference to the function that computes
        the hash value of a key:

            a) comphash () is called to compute the hash value of a
            key.

            b) int (*comphash) (void *key).

            c) The arguments:

                i) hash_table is a reference to the hash table
                descriptor.

                ii) key is a reference to the key to be hashed.

            d) The return value is the key's hash.

VIII) performance issues:

    A) Note that the number of comparisons, ch, required for a key
    search in the hash table is (on average):

        ch = (1 / 2) * (n / hash_size);

    where n is the number of keys in the hash table, and hash_size is
    the size of the hash table.

    B) By comparison, the number of comparisons, cb, required for a
    key search using a binary search routine is (on average):

        cb = (1 / 2) * (log (n) / log (2));

    where it is assumed that an each key compared has an equal
    probability of being the key that is being searched for (probably
    an overly optimistic assumption).

    C) For a similar number of comparisons:

        hash_size = ((n * log (2)) / log (n));

    D) In powers of 2, the hash table size, hash_size, should be:

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

        1) N is the number of keys in the lookup table.

        2) C is the maximum number of key comparisons (minus 1)
        required to locate a key, eg., floor (log (N) / log (2).

        3) I is the size of the hash table index, hash_size,
        floor (N / (log (N) / log (2))).

        4) P is the next larger prime than I.

IX) Data structure (with exactly one data space object allocated,
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

XII) The hash table index size, hash_size, should be a prime number,
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

Initialize the hash table.

static int hash_init (HASHTABLE *hash_table);

If this function has not been executed for this HASHTABLE structure,
allocate hash_table->hash_size many HASH structures for the hash table
index, initialize each element in the hash table index's next and
previous references to reference the element itself.

The required argument is a reference to the hash table descriptor
structure, hash_table.

Returns NOERROR if successful, EALLOC if a memory allocation error,
and HASH_INI_ERR if the hash table was already initialized.

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

Insert a key and data into the hash table.

static int hash_insert (HASHTABLE *hash_table, void *data);

Calculate the key's hash, search the hash table index implicitly
addressed by the key's hash for a matching key, if the key is not
found, insert the key and data at the end of the doubly linked list
for this hash table index element.

The required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, data.

Returns NOERROR if successful, HASH_DUP_ERR if a duplicate key was
found, HASH_MK_ERR if an error occured in mkhash () and hash_error was
not set by mkhash (), else the value of hash_error that was set by
mkhash ().

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

        if ((*hash_table->cmphash) (data, next_element) == 0) /* element compare with the data? */
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

Find data in the hash table.

static HASH *hash_find (HASHTABLE *hash_table, data *data);

Calculate the key's hash, search for the key and data, starting at the
beginning of the doubly linked list for this hash table index element,
which is implicitly addressed by the key's hash.

The required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, data.

Returns a reference to the hash table's element referencing the data,
0 if key is not found.

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

        if ((*hash_table->cmphash) (data,next_element) == 0) /* element compare with the data? */
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

Delete data from the hash table.

static int hash_delete (HASHTABLE *hash_table, void *data);

Calculate the key's hash, search for the key and data, starting at the
beginning of the doubly linked list for this hash table index element,
which is implicitly addressed by the key's hash, if found, remove the
key's HASH element, and data.

The required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, data.

Returns NOERROR if successful, HASH_DEL_ERR if key not found.

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

        if ((*hash_table->cmphash) (data,element) == 0) /* element compare with the data? */
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

Remove a hash table.

static void hash_term (HASHTABLE *hash_table);

For each element in the hash table's index, for each element in the
doubly linked lest referenced by the hash table's index element,
remove the key's HASH element.

The required argument is a reference to the hash table descriptor
structure, hash_table.

Always returns NOERROR.

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

Compute the hash value for a text key.

static int hash_text (HASHTABLE *hash_table, void *key);

The routine requires a long to be 32 bits, which represents some
portability issues.

The required arguments are a reference to the hash table descriptor
structure, hash_table, and a reference to the key value, key.

Returns the hash value for the key.

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
                  hash_num = (unsigned long) 0; /* hash number is initially zero */

    for (char_ref = key;*char_ref != (char) 0;char_ref ++) /* for each character in the key */
    {
        hash_num = (hash_num << 4) + (unsigned long) (((int) (*char_ref) < (int) 0) ? (long) (-(*char_ref)) : (long) (*char_ref)); /* multiply the hash by 16, and add the absolute value of the character */

        if ((num = hash_num & 0xf0000000) != (unsigned long) 0) /* any high order bits in bit positions 24 - 28? */
        {
            hash_num = hash_num ^ (num >> 24); /* yes, divide by 16777216, and exor on itself */
            hash_num = hash_num ^ num; /* reset the high order bits to 0 */
        }

    }

    return ((int) (hash_num % (unsigned long) hash_table->hash_size)); /* return the hash number */
}

/*

Compare a text key with a hash table element's text key.

static int text_cmphash (void *data, HASH *element);

Runction that compares a text key with a hash table element's text key.

(Note that the data reference in HASH is a void reference, and
requires a cast to the appropriate data type.)

Returns 0 if data and the key associated with the element key are
equal.

Returns non-zero if data and the key associated with the element are
not equal.

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

Allocate and loads a hash table element.

static HASH *text_mkhash (void *data);

Function that allocates and loads a hash table element, and allocates
a data (which in this simple case, is simply a copy of the key.)

Returns a reference to the element constructed if successful.

Returns NULL if no element was constructed.

Note: hash_error, is set to EALLOC if a memory allocation error
occured.

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

    if ((obj_ref = (char *) malloc ((size_t) (1 + strlen (data)) * sizeof (char))) != (char *) 0) /* allocate the hash table element key's data area */
    {

        if ((element = (HASH *) malloc (sizeof (HASH))) != (HASH *) 0) /* allocate the hash table element */
        {
            element->previous = element; /* all of the elements link references reference the element itself */
            element->next = element; /* all of the elements link references reference the element itself */
            element->next_decision = (HASH *) 0; /* initialize the reference to next element in qsortlist ()'s sort of the decision criteria list */
            element->next_investment = (HASH *) 0; /* initialize the reference to next element in invested list */
            element->next_print = (HASH *) 0; /* initialize the reference to next element in print list */
            element->hash_data = obj_ref; /* reference to element's data */
            (void) strcpy (obj_ref, data); /* save the hash table element's key as its data */
            element->transactions = 0; /* initialize the number of changes in this stock's value */
            element->count = 0; /* initialize the count of avg or rms values in the running sum of avg and rms values */
            element->voidcount = 0; /* initialize the count of "zero free" time intervals in stock's growth, positive means stock value is above average, negative means below average */
            element->comp = 0; /* initialize the compensate the Shannon probability, P, for run length duration flag, 0 = no, 1 = yes */
            element->noest = 0; /* initialize the don't compensate the Shannon probability, P, for data set size flag, 0 = compensate, 1 = don't compensate */
            element->positive_consecutive = 0; /* initialize the running number of consecutive up movements */
            element->negative_consecutive = 0; /* initialize the running number of consecutive down movements */
            element->positive_size = 0; /* initialize the number of elements in the positive_histogram */
            element->negative_size = 0; /* initialize the number of elements in the positive_histogram */
            element->current_updated = 0; /* initialize the updated in current interval flag, 0 = no, 1 = yes */
            element->last_updated = 0; /* initialize the updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
            element->invest_update = 0; /* initialize the invest only if stock has been updated in current interval flag, 0 = no, 1 = yes */
            element->stats_update = 0; /* initialize the don't calculate stock's statistics if it has not been updated in the current interval flag, 0 = no, 1 = yes */
            element->currentvalue = (double) 0.0; /* initialize the current value of stock */
            element->lastvalue = (double) 0.0; /* initialize the last value of the stock */
            element->start_value = (double) 0.0; /* initialize the start value of stock */
            element->consecutive_start = (double) 0.0; /* initialize the start value for a run of consecutive like movements */
            element->capital = (double) 0.0; /* initialize the amount of capital invested in the stock */
            element->fraction = (double) 0.0; /* initialize the normalized increment of the stock's value */
            element->Gn = (double) 1.0; /* initialize the normalized growth, ie., the value of the stock if its initial value was one dollar */
            element->Par = (double) 0.0; /* initialize the Shannon probability, using avg and rms */
            element->Pa = (double) 0.0; /* initialize the Shannon probability, using avg */
            element->Pr = (double) 0.0; /* initialize the Shannon probability, using rms */
            element->Pt = (double) 0.0; /* initialize the mean reverting probability */
            element->Pp = (double) 0.0; /* initialize the persistence probability */
            element->Pconfar = (double) 0.0; /* initialize the confidence level in the measurment accuracy of the Shannon probability, using avg and rms */
            element->Pconfa = (double) 0.0; /* initialize the confidence level in the measurment accuracy of the Shannon probability, using avg */
            element->Pconfr = (double) 0.0; /* initialize the confidence level in the measurment accuracy of the Shannon probability, using rms */
            element->Peffar = (double) 0.0; /* initialize the effective Shannon probability, using avg and rms, compensated for measurement accuracy by statistical estimate */
            element->Peffa = (double) 0.0; /* initialize the effective Shannon probability, using avg, compensated for measurement accuracy by statistical estimate */
            element->Peffr = (double) 0.0; /* initialize the effective Shannon probability, using rms, compensated for measurement accuracy by statistical estimate */
            element->Pefft = (double) 0.0; /* initialize the effective Shannon probability, using mean reverting probability, compensated for measurement accuracy by statistical estimate */
            element->Peffp = (double) 0.0; /* initialize the effective Shannon probability, using persistence probability, compensated for measurement accuracy by statistical estimate */
            element->Pcomp = (double) 0.0; /* initialize the compensation for run length duration for Shannon probability */
            element->avgsum = (double) 0.0; /* initialize the running sum of avg values */
            element->avg = (double) 0.0; /* initialize the average of the normalized increments, avg */
            element->rmssum = (double) 0.0; /* initialize the running sum of rms values */
            element->rms = (double) 0.0; /* initialize the root mean square of the normalized increments, rms */
            element->rootmean = (double) 0.0;  /* initialzie the sum of variances of marginal increments of consecutive like movements */
            element->maxinc = (double) 1.0; /* initialize the maximum acceptable marginal increment in stock's value */
            element->decision = (double) 0.0; /* initialize the decision criteria for investment in a stock, qsortlist () will sort the list of next_decision elements by this value */
            element->allocation_fraction = (double) 0.0; /* initialize the fraction of the portfolio that is to be allocated to a stock */
            element->allocation_percentage = (double) 0.0; /* initialize the percentage of the portfolio that is to be allocated to a stock */
            element->positive_histogram = (PERSISTENCE *) 0; /* initialize the consecutive up movments histogram */
            element->negative_histogram = (PERSISTENCE *) 0; /* initialize the consecutive down movments histogram */
            element->method = M_AVGRMS; /* initialize the method of computation for determination of a stock's decision criteria, used in stock selection */
            PUSHDECISION(element); /* push the HASH element on the decision list */
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

Deallocate a text hash table element.

static void text_rmhash (HASH *element);

Function to deallocate a text hash table element.

Returns nothing.

*/

#ifdef __STDC__

static void text_rmhash (HASH *element)

#else

static void text_rmhash (element)
HASH *element;

#endif
{

    if (element->positive_histogram != (PERSISTENCE *) 0) /* consecutive up movments histogram allocated? */
    {
        free (element->positive_histogram); /* yes, free the consecutive up movments histogram */
    }

    if (element->negative_histogram != (PERSISTENCE *) 0) /* consecutive down movments histogram allocated? */
    {
        free (element->negative_histogram); /* yes, free the consecutive down movments histogram */
    }

    free (element->hash_data); /* free the key's data area allocated in text_mkhash () */
    free (element); /* free the hash table element allocated in text_mkhash () */
}

/*

Quick sort a linked list.

static void qsortlist (list *top, list bottom);

A stable quick sort for linked lists.

Note: Tail recursion is used to limit recursion to ln (n) levels,
which cuts the number of recursive calls by a factor of 2. Sorting
time will be proportional to n ln (n).

Note: this algorithm uses double level indirection-modifications must
be made with meticulous care.

I) The algorithm is as follows (the pivot is the dividing line between
high and low sub lists in a sub list):

   A) Append each item in the beginning of a sub list to the high or
   low sub list, (at the completion of this loop, the low sub list has
   already been linked into the calling context at the beginning of
   the sub list.)

   B) The pivot element is appended after the low sub list, and the
   end of the high sublist is then linked into the calling context sub
   list.

   C) The beginning of the high sub list is finally appended after the
   pivot.

   Note: although the re linking must be done in this order, the order
   of sorting the sub lists is not critical.

II) Usage is to typedef LIST as the structure element to be sorted,
and the token "list" as a reference to a LIST type of element, for
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
    conceivably be any type, or token name,) and a reference element
    to the next structure in the list, with a token name of "next,"
    which is used internal to the qsortlist module.

III) It is also necessary to include a comparison utility, either by
#define or function, that can compare the key elements in two list
elements. For example:

    #define element_comp(x,y) (x)->count - (y)->count

IV) The comparison utility:

    The comparison utility must have the token name "element_comp,"
    which is used internal to the qsortlist module, and has the same
    return value operations as strcmp(2), ie., if the first argument
    is lexically larger, the return should be positive, and if it is
    smaller, it should be negative, and if equal, zero is
    returned. Reverse the scenario for a reverse sort on lexical
    order.

For a detailed description of quicksorting linked lists, see
"Quicksorting Linked Lists," Jeff Taylor, "C Gazette," Volume 5,
Number 6, October/November, 1991, ISSN 0897-4055, P.O. Box 70167,
Eugene, OR 97401-0110. Published by Oakley Publishing Company, 150
N. 4th Street, Springfield, OR 97477-5454.

*/

/*

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
