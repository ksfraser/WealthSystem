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

tsstock.c is for simulating the gains of a stock investment using
Shannon probability.

The input file structure is a text file consisting of records, in
temporal order, one record per time series sample.  Blank records are
ignored, and comment records are signified by a '#' character as the
first non white space character in the record. Data records must
contain at least one field, which is the data value of the sample, but
may contain many fields-if the record contains many fields, then the
first field is regarded as the sample's time, and the last field as
the sample's value at that time.

A large mathematical infrastructure of analytical techniques and
methodologies have been dedicated to the analysis of equity market
time series.

See:

    "Introduction to Fractals and Chaos," Richard M. Crownover, Jones
    and Bartlett Publishers International, London, England, 1995, ISBN
    0-86720-464-8, pp. 249.

    "Fractals, Chaos, Power Laws," Manfred Schroeder, W. H. Freeman
    and Company, New York, New York, 1991, ISBN 0-7167-2136-8,
    pp. 126, 139.

    "Chaos and Order in the Capital Markets," Edgar E. Peters, John
    Wiley & Sons, New York, New York, 1991, ISBN 0-471-53372-6.

    "Complexity," Roger Lewin, Macmillan, New York, New York, 1992,
    ISBN 0-671-76789-5, pp. 196, 269, 273, 329.

    "Searching for Certainty," John L. Casti, William Morrow, New
    York, New York, 1990, ISBN 0-688-08980-1, pp. 195, 214.

    "Complexification," John L. Casti, HarperCollins, New York, New
    York, 1994, ISBN 0-06-0168888-9, pp. 82, 106, 102, 255, 269.

    "Predictions," Theodore Modis, Simon & Schuster, New York, New
    York, 1992, ISBN 0-671-75917-5, pp. 155.

In addition, a large infrastructure of information-theoretic
techniques have been suggested for optimal speculative wagering
strategies in the equity markets, based, generally, on the suggested
interpretation in the Kelly reference.

See:

    "New Interpretation of Information Rate," J. L. Kelly, Jr., Bell
    System Technical Journal, Vol. 35, (July, 1956,) pp. 917.

    "An Introduction to Information Theory," John R. Pierce, Dover
    Publications, New York, New York, 1980, ISBN 0-486-24061-4,
    pp. 270.

    "An Introduction to Information Theory," Fazlollah M. Reza, Dover
    Publications, New York, New York, 1994, ISBN 0-486-68210-2,
    pp. 450.

    "Information Theory," Robert B. Ash, Dover Publications, New York,
    New York, 1965, ISBN 0-486-66521-6, pp. 9.

    "The Mathematical Theory of Communication," Claude E. Shannon and
    Warren Weaver, The University of Illinois Press, Urbana, Illinois,
    1949, pp. 39.

    "Fuzzy Sets, Uncertainty, and Information," George J. Klir and
    Tina A. Folger, Prentice-Hall, Englewood Cliffs, New Jersey, 1988,
    ISBN 0-13-345984-5, pp. 155.

    "Fractals, Chaos, Power Laws," Manfred Schroeder, W. H. Freeman
    and Company, New York, New York, 1991, ISBN 0-7167-2136-8,
    pp. 128, 151.

This program is an investigation into whether a stock price time
series could be modeled as a fractal Brownian motion time series, and,
further, whether, a mechanical wagering strategy could be devised to
optimize portfolio growth in the equity markets.

Specifically, the paradigm is to establish an isomorphism between the
fluctuations in a gambler's capital in the speculative unfair tossed
coin game, as suggested in Schroeder, and speculative investment in
the equity markets.  The advantage in doing this is that there is a
large infrastructure in mathematics dedicated to the analysis and
optimization of parlor games, specifically, the unfair tossed coin
game. See Schroeder reference.

Currently, there is a repository of historical price time series for
stocks available at,

    http://www.ai.mit.edu/stocks.html

that contains the historical price time series of many hundreds of
stocks. The stock's prices are by close of business day, and are
updated daily.

The stock price history files in the repository are available via
anonymous ftp, (ftp.ai.mit.edu,) and the programs tsfraction(1),
tsrms(1), tsavg(1), and tsnormal(1) can be used to verify that, as a
reasonable first approximation, stock prices can be represented as a
fractional Brownian motion fractal, as suggested by Schroeder and
Crownover.  (Note the assumption that, as a first approximation, a
stock's price time series can be generated by independent increments.)

This would tend to imply that there is an isomorphism between the
underlying mechanism that produces the fluctuations in speculative
stock prices and the the mechanism that produces the fluctuations in a
gambler's captial that is speculating on iterations of an unfair
tossed coin.

If this is a reasonably accurate approximation, then the underlying
mechanism of a stock's price time series can be analyzed, (by
"disassembling" the time series,) and a wagering strategy, similar to
that of the optimal wagering strategy in the iterated unfair coin
tossing game, can be formulated to optimize equity market portfolio
growth.

As a note in passing, it is an important and subtile point, that there
are "operational" differences in wagering on the iterated unfair coin
game, and wagering on a stock. Specifically, in the coin game, a
fraction of the gambler's capital is wagered on the speculative
outcome of the toss of the coin, and, depending on whether the toss of
the coin resulted in a win, (or a loss,) the wager is added to the
gambler's capital, (or subtracted from it,) respectively.  However, in
the speculative stock game, the gambler wagers on the anticipated
FLUCTUATIONS of the stock's price, by purchasing the stock. The
important difference is that the stock gambler does not win or loose
an amount that was equal to the stock's price, (which was equivalent
to the wager in the iterated unfair coin game,) but only the
fluctuations of the stock's price, ie., it is an important concept
that a portfolio's value (which has an investment in a stock,) and the
stock's price do not, necessarily, "track" each other.

In some sense, wagering on a stock is NOT like a gambler wagering on
the outcome of the toss of an unfair coin, but like wagering on the
capital of the gambler that wagered on the outcome of the toss of an
unfair coin. A very subtile difference, indeed.

Note that the paradigm of the isomorphism between wagering on a stock
and wagering in an unfair tossed coin game is that the graph, (ie.,
time series,) of the gambler's capital, who is wagering on the
iterated oucomes of an unfair tossed coin, and the graph of a stock's
price over time are statistically similar.

If this is the case, at least in principle, it should be possible to
"dissect" the time series of both "games," and determine the
underlying statistical mechanism of both. Further, it should be, at
least in principle, possible to optimize portfolio growth of
speculative investments in the equity markets using
information-theoretic entropic techniques. See Kelly, Pierce, Reza,
and Schroeder.

Under these assumptions, the amount of capital won or lost in each
iteration of the unfair tossed coin game would be:

    V(t) - V(t - 1)

for all data points in the gambler's capital time series.  This would
correspond to the amount of money won or lost on each share of stock
at each interval in the stock price time series.

Likewise, the normalized increments of the gambler's capital time
series can be obtained by subtracting the value of the gambler's
capital in the last interval from the value of the gambler's capital
in the current interval, and dividing by the value of the gambler's
capital in the last interval:

    V(t) - V(t - 1)
    ---------------
       V(t - 1)

for all data points in the gambler's capital time series. This would
correspond to the fraction of the gambler's capital that was won or
lost on each iteration of the game, or, alternatively, the fraction
that the stock price increased or decreased in each interval.

The normalized increments are a very useful "tool" in analyzing time
series data.  In the case of the unfair coin tossing game, the
normalized increments are a "graph," (or time series,) of the fraction
of the capital that was won or lost, every iteration of the
game. Obviously, in the unfair coin game, to win or lose, a wager had
to be made, and the graph of the absolute value, or more
appropriately, the root mean square, (the absolute value of the
normalized increments, when averaged, is related to the root mean
square of the increments by a constant. If the normalized increments
are a fixed increment, the constant is unity. If the normalized
increments have a Gaussian distribution, the constant is ~0.8
depending on the accuracy of of "fit" to a Gaussian distribution,) of
the normalized increments is the fraction of the capital that was
wagered on each iteration of the game. As suggested in Schroeder, if
an unfair coin has a chance, P, of coming up heads, (winning) and a
chance 1 - P, of coming up tails, (loosing,) then the optimal wagering
strategy would be to wager a fraction, f, of the gambler's capital, on
every iteration of the game, that is:

    f = 2P - 1

This would optimize the exponential growth of the gambler's
capital. Wagering more than this value would result in less capital
growth, and wagering less than this value would result in less capital
growth, over time. The variable f is also equal to the root mean
square of the normalized increments, rms, and the average, avg, of the
normalized increments is the constant of the average exponential
growth of the gambler's capital:

                    t
    C(t) = (1 + avg)

where C(t) is the gambler's capital. It can be shown that the formula
for the probability, P, as a function of avg and rms is:

        avg
        --- + 1
        rms
    P = -------
           2

where the empirical measurement of avg and rms are:

              n
            -----
          1 \     V(t) - V(t - 1)
    avg = -  >    ---------------
          n /        V(t - 1)
            -----
            i = 0

and,

               n
             -----                     2
       2   1 \      [ V(t) - V(t - 1) ]
    rms  = -  >     [ --------------- ]
           n /      [   V(t - 1)      ]
             -----
             i = 0

respectively, (additionally note that these formulas can be used to
produce the running average and running root mean square, ie., they
will work "on the fly.")

The formula for the probability, P, will be true whether the game is
played optimally, or not, ie., the game we are "dissecting," may not
be played with f = 2P - 1. However, the formula for the probability,
P:

         rms + 1
    P' = -------
            2

will be the same as P, only if the game is played optimally, (which,
also, is applicable in "on the fly" methodologies.)

Interestingly, the measurement, perhaps dynamically, (ie., "on the
fly,") of the average and root mean square of the normalized
increments is all that is necessary to optimize the "play of the
game." Note that if P' is smaller than P, then we need to increase
rms, by increasing f, and, likewise, if P' is larger than P, we need
to decrease f. Thus, without knowing any of the underlying mechanism
of the game, we can formulate a methodology for an optimal wagering
strategy. (The only assumption being that the capital can be
represented as an independent increment fractal-and, this too can, and
should, be verified with meticulous application of fractal analysis
using the programs tsfraction(1), tsrms(1), tsavg(1), and
tsnormal(1).)

At this point, it would seem that the optimal wagering strategy and
analytical methodology used to optimize the growth of the gambler's
capital in the the unfair tossed coin gain is well in
hand. Unfortunately, when applying the methodology to the equity
markets, one finds that, for almost all stocks, P is greater than P',
perhaps tending to imply that in the equity markets, stocks are over
priced.

To illustrate a simple stock wagering strategy, suppose that
analytical measurements are made on a stock's price time series, and
it is found, conveniently, that P = P', implying that f = rms, (after
computing the normalized increments of the stock's price time series
and calculating avg, rms, P, and P'.)  Note that in the optimized
unfair coin tossing game, that wagering a fraction, f = rms, of the
gambler's capital would optimize the exponential growth of the
gambler's capital, and that the fluctuations, over time, of the
gambler's capital would simply be the normalized increments of the
gambler's capital. The root mean square of the fluctuations, over
time, are the fraction of that the gambler's capital wagered, over
time.  To achieve an optimal strategy when wagering on a stock, the
objective would be that the normalized increments in the value of the
portfolio, and the root mean square value of the normalized increments
of the portfolio, also, satisfy the criteria, f = rms. Note that the
fraction of the portfolio that is invested in the stock will have
normalized increments that have a root mean square value that are the
same as the root mean square value of the normalized increments of the
stock.

The issue is to determine the fraction of the stock portfolio that
should be invested in the stock such that that fraction of the
portfolio would be equivalent to the gambler wagering a fraction of
the capital on a coin toss. It is important to note that the optimized
wagering strategy used by the gambler, when wagering on the outcome of
a coin toss, is to never wager the entire capital, but to hold some
capital in reserve, and wager only a fraction of the capital-and in
the optimum case this wager fraction is f = rms. In a stock portfolio,
even though the investment is totally in stocks, it could be
considered that some of this value is wagered, and the rest held in
reserve. The amount wagered would be the root mean square of the
normalized increments of the stocks price, and the amount held in
reserve would be the remainder of the portfolio's value. (Note the
paradigm-there is an isomorphism between the fluctuating gambler's
capital in the unfair coin tossing game, and the fluctuating value of
a stock portfolio.)  In the simple case where P = P', the fraction of
the portfolio value that should be invested in the stock is f = root
mean square of the stock's normalized increments, which would be the
same as f = 2P - 1, where P = ((avg/rms) + 1) / 2 or P = (rms + 1) /
2. Note that the fluctuations in the value of the portfolio do to the
fluctuations in the stocks price would be statistically similar to the
fluctuations in the gambler's capital when playing the unfair coin
tossing game.

This also leads to a generality, where P and P' are not equal. If the
root mean square of the normalized increments of the stock price time
series are too small, say by a factor of 2, then the fraction of the
portfolio invested in the stock should be increased, by a factor of 2
(in this example.) This would make the root mean square of the
fluctuations in the value of the portfolio the same as the the root
mean square of the fluctuations in the gambler's capital under similar
statistical circumstances, (albeit with twice as much of the
portfolio's equivalent "cash reserves" tied up in the investment in
the stock.

To calculate the ratio by which the fraction of the portfolio invested
in a stock must be increased:

        avg
        --- + 1
        rms
    P = -------
           2

and,

    f = 2P - 1 = rms

and letting the measured rms by rms ,
                                   m

                    avg
                       m
                    ---- + 1
                    rms            avg
                       m              m
    f = 2P - 1 = 2  -------- - 1 = ---- = rms
                       2           rms
                                      m

    (Note that both of the values, avg and rms, are functions of
    the probability, P, but their ratio is not.)

and letting F be the ratio by which the fraction of the portfolio
invested in a stock must be increased to accomodate P not being equal
to P':

                avg
         rms       m
    F =  ---- = ----
         rms       2
            m   rms
                   m

and multiplying both sides of the equation by f, to get the fraction
of the portfolio that should be invested in the stock while
accommodating P not being equal to P':

                             2
            avg    avg    avg
               m      m      m
    F * f = ---- * ---- = ----
               2   rms       3
            rms       m   rms
               m             m

which can be computed, dynamically, or "on the fly," and where avg and
rms are the average and root mean square of the normalized increments
of the stock's price time series, and assuming that the stock's price
time series is composed of independent increments, and can be
represented as a fractional Brownian motion fractal.

Representing such an algorithm in pseudo code:

    1) for each data point in the stock's price time series, find the,
    possibly running, normalized increment from the following
    equation:

        V(t) - V(t - 1)
        ---------------
           V(t - 1)

    2) calculate the, possibly running, average of all normalized
    increments in the stock's price time series by the following
    equation:

                  n
                -----
              1 \     V(t) - V(t - 1)
        avg = -  >    ---------------
              n /        V(t - 1)
                -----
                i = 0

    3) calculate the, possibly running, root mean square of all
    normalized increments in the stock's price time series by the
    following equation:

                   n
                 -----                     2
           2   1 \      [ V(t) - V(t - 1) ]
        rms  = -  >     [ --------------- ]
               n /      [   V(t - 1)      ]
                 -----
                 i = 0

    4) calculate the, possibly running, fraction of the portfolio to
    be invested in the stock, F * f:

                   2
                avg
                   m
        F * f = ----
                   3
                rms
                   m

To reiterate what we have so far, consider a gambler, iterating a
tossed unfair coin. The gambler's capital, over time, could be a
represented as a Brownian fractal, on which measurements could be
performed to optimize the gambler's wagering strategy. There is
supporting evidence that stock prices can be "modeled" as a Brownian
fractal, and it would seem reasonable that the optimization techniques
that the gambler uses could be applied to stock portfolios. As an
example, suppose that it is desired to invest in a stock. We would
measure the average and root mean square of the normalized increments
of the stock's price time series to determine a wagering strategy for
investing in the stock. Suppose that the measurement yielded that the
the the fraction of the capital to be invested, f, was 0.2, (ie., a
Shannon probability of 0.6,) then we might invest the entire portfolio
in the stock, and our portfolio would be modeled as 20% of the
portfolio would be wagered at any time, and 80% would be considered as
"cash reserves," even though the 80% is actually invested in the
stock. Additionally, we have a metric methodology, requiring only the
measurement of the average and root mean square of the increments of
the stock price time series, to formulate optimal wagering strategies
for investment in the stocks.  The assumption is that the stock's
price time series is composed of independent increments, and can be
represented as a fractional Brownian motion fractal, both of which can
be verified through a metric methodology.

Note the isomorphism. Consider a gambler that goes to a
casino, buys some chips, then plays many iterations of an
unfair coin tossing game, and then cashes in the
chips. Then consider investing in a stock, and some time
later, selling the stock. If the Shannon probability of
the time series of the unfair coin tossing game is the
same as the time series of the stock's value, then both
"games" would be statistically similar. In point of fact,
if the toss of the unfair coin was replaced with whether
the stock price movement was up or down, then the two time
series would be identical. The implication is that stock
values can be modeled by an unfair tossed coin. In point
of fact, stock values are, generally, fractional Brownian
motion in nature, implying that the day to day
fluctuations in price can be modeled with a time sampled
unfair tossed coin game.

There is an implication with the model. It would appear that the
"best" portfolio strategy would be to continually search the stock
market exchanges for the stock that has the largest value of the
quotient of the average and root mean square of the normalized
increments of the stock's price time series, (ie., avg / rms,) and
invest 100% of the portfolio in that single stock. This is in
contention with the concept that a stock portfolio should be
"diversified," although it is not clear that the prevailing concept of
diversification has any scientific merit.

To address the issue of diversification of stocks in a stock
portfolio, consider the example where a gambler, tossing an unfair
coin, makes a wager.  If the coin has a 60% chance of coming up heads,
then the gambler should wager 20% of the capital on hand on the next
toss of the coin. The remaining 80% is kept as "cash reserves." It can
be argued that the cash reserves are not being used to enhance the
capital, so the gambler should play multiple games at once, investing
all of the capital, investing 20% of the capital, in each of 5 games
at once, (assuming that the coins used in each game have a probability
of coming up heads 60% of the time-note that the fraction of capital
invested in each game would be different for each game if the
probabilities of the coins were different, but could be measured by
calculating the avg /rms of each game.)

Likewise, with the same reasoning, we would expect that stock
portfolio management would entail measuring the quotient of the
average and root mean square of the normalized increments of every
stock's price time series, (ie., avg / rms,) choosing those stocks
with the largest quotient, and investing a fraction of the portfolio
that is equal to the this quotient. Note that with an avg / rms = 0.1,
(corresponding to a Shannon probability of 0.55-which is "typical" for
the better performing stocks on the New York Stock Exchange,) we would
expect the portfolio to be diversified into 10 stocks, which seems
consistent with the recommendations of those purporting
diversification of portfolios. In reality, since most stocks in the
United States exchanges, (at least,) seem to be "over priced," (ie., P
larger than P',) it will take more capital than is available in the
value of the portfolio to invest, optimally, in all of the stocks in
the portfolio, (ie., the fraction of the portfolio that has to be
invested in each stock, for optimal portfolio performance, will sum to
greater than 100%.) The interpretation, I suppose, in the model, is
that at least a portion of the investment in each stock would be on
"margin," which is a relatively low risk investment, and, possibly,
could be extended into a formal optimization of "buying stocks on the
margin."

The astute reader would note that the fractions of the portfolio
invested in each stock was added linearly, when these values are
really the root mean square of the normalized increments, implying
that they should be added root mean square. The rationale in linear
addition is that the Hurst Coefficient in the near term is near unity,
and for the far term 0.5. (By definition, this is the characteristic
of a Brownian motion fractal process.)  Letting the Hurst Coefficient
be H, then the method of summing multiple processes would be:

     H      H    H
    V    = V  + V  + ...
     tot    1    2

so in the far term, the values would be added root mean square, and in
the near term, linearly. Note that this is also a quantitative
definition of the terms "near term" and "far term."  Since the Hurst
Coefficient plot is on a log-log scale, the demarcation between the
two terms is where 1 - ln (t) = 0.5 * ln (t), or when ln (t) = 2, or t
= 7.389... The important point is that the "root mean square formula"
used varies with time. For the near term, H = 1, and linear addition
is used. For the far term, a root mean square summation process is
used. (Note, also, that a far term H of 0.5 is unique to Brownian
motion fractals. In general, it can be different than 0.5. If it is
larger than 0.5, then it is termed fractional Brownian motion,
depending on who is doing the defining.)

There are some interesting implications to this near term/far term
interpretation. First, the "forecastability" is better in the near
term than far term-which could be interpreted as meaning that short
term strategies would yield better portfolio performance than long
term strategies-see the Peters reference, pp. 83-84. Secondly, it can
be used to optimize portfolio long term strategy. For example, suppose
that a stock's Shannon probability is 0.52, and all stocks in the
portfolio have the same Shannon probability. This means that the
portfolio should consist of 25 stocks. However, in the long run, the
portfolio would have a root mean square value of the square root of 25
times 0.04, or 0.2. This would tend to imply that, on the average,
over the long run, the stock portfolio would be one fifth of the total
investments. Naturally, this ratio could be adjusted, over time,
depending on the instantaneous value of the Shannon probabilities of
all different investments, like bonds, metals, etc.

This would imply that "timing of the market" would have to be
initiated to adjust the ratio of investment in stocks. One of the
implications of entropic theory is that this is impossible. However,
as the Shannon probability of the various investments change,
statistical estimation can be used to asses the statistical accuracy
of these movements, and the ratios adjusted accordingly. This would
tend to suggest that adaptive computational control system methodology
would be an applicable alternative.

As a note in passing, the average and root mean square of the
normalized increments of a stock's price time series, avg and rms,
respectively, represent a qualitative metric of the stock. The
average, avg, is an expression of the stock's growth in price, and the
root mean square, rms, is a expression of the stock's price
volatility. It would seem, incorrectly, at first glance that stocks
should be selected that have high price growth, and low price
volatility-however, it is a more complicated issue since avg and rms
are interrelated, and not independent of each other. See the
references for theoretical concepts.

In the diversified portfolio, the "volatilities" of the individual
stocks add root mean square to the volatility of the portfolio value,
so, everything else being equal, we would expect that the volatility
of the portfolio value to be about 1 /3 the volatility of the stocks
that make up the portfolio. (The ratio 1 / came from square root of 1
/ 10, which is about 1 / 3.) (There is a qualification here, it is
assumed that all stock price time series are made up of independent
increments, and can be represented as a fractional Brownian motion
fractal-note that this statement is not true if the time series is
characterized as simple Brownian motion, like the gambler's capital in
the unfair coin toss game-see Schroeder, pp. 157 for details.)  So, it
can be supposed, if one desires maximum performance in a stock
portfolio, then one should search the stock market exchanges for the
stock that has the highest quotient of the average and root mean
square of the normalized increments of stock price time series, and
invest 100% of the portfolio in that stock. As an alternative
strategy, one could diversify the portfolio, investing in multiple
stocks, and lower the portfolio volatility at the expense of lower
portfolio performance.  Arguments can probably be made for both
strategies.

As a note in passing, I have made the statment that, at least in the
United States exchanges, stocks tend to be over priced.  The rationale
behind the statement is as follows. If the stock's price time series
represents an independent increment, fractional Brownian fractal, and
if the stock's price performance is optimal, then the equation:

    f = 2P - 1

where P is the Shannon probability for the stock's price time series,
and f is the fraction of the capital wagered per game, (or unit time,
and where the capital is the stock's price,) will represent
fluctuations in the stock's price, since the symbol f is also the root
mean square value of the normalized increments of the stock's time
series. Also, the absolute value of the time derivative of the stock's
price time series is the fluctuations in the stocks price, ie., at any
instant, if V is the stock's price, then fV will be the fluctuation in
price, which is the derivative, D, or, V = D / f. In other words, the
fair market value of the stock, in relation to the normalized
increments of the stock's value, will be the derivative of the stock's
price, divided by the root mean square of the normalized increments of
the stock's price, which is also f. If the argument has merit, then,
at least the stocks available from http://www.ai.mit.edu/stocks.html
would seem to be over priced. (It is a straight forward shell
programming exercise, using the programs tsderivative(1),
tsfraction(1), tsmath(1), and tsrms(1), to verify this.)

A final derivation, following Reza and Kelly. Consider the case of a
gambler with a private wire into the future who places wagers on the
outcomes of a game of chance. We assume that the side information
which he receives has a probability, P, of being true, and of 1 - P,
of being false. Let the original capital of gambler be V(0), and V(n)
his capital after the n'th wager. Since the gambler is not certain
that the side information is entirely reliable, he places only a
fraction, f, of his capital on each wager. Thus, subsequent to n many
wagers, assuming the independence of successive tips from the future,
his capital is:

                  w       l
    V(n) = (1 + f) (1 - f) V(0)

where w is the number of times he won, and l = n - w, the number of
times he lost. These numbers are, in general, values taken by two
random variables, denoted by W and L. According to the law of large
numbers:

                  1
    lim           - W = P
    n -> infinity n

and:

                  1
    lim           - L = q = 1 - P
    n - >infinity n

The problem with which the gambler is faced is the determination of f
leading to the maximum of the average exponential rate of growth of
his capital. That is, he wishes to maximize the value of:

                      1     V(n)
    G = lim           - log ---
        n -> infinity n     V(0)

with respect to f, assuming a fixed original capital and s specified
P:

                      W               L
    G = lim           - log (1 + f) + - log (1 - f)
        n -> infinity n               n

or:

    G = P log (1 + f) + q log (1 - f)

which, by taking the derivative with respect to f, and equating to
zero, can be shown to have a maxima when:

    dG           P - 1        1 - P
    -- = P(1 + f)      (1 - f)
    df

                           1 - P - 1        P
         - (1 - P) (1 - f)           (1 + f)  = 0

combining terms:

            P - 1        1 - P                  P        P
    P(1 + f)      (1 - f)      - (1 - P) (1 - f)  (1 + f)  = 0

and splitting:

            P - 1        1 - P                  P        P
    P(1 + f)      (1 - f)      = (1 - P) (1 - f)  (1 + f)

taking the logarithm of both sides:

    ln (P) + (P - 1) ln (1 + f) + (1 - P) ln (1 - f) =
        ln (1 - P) - P ln (1 - f) + P ln (1 + f)

combining terms:


    (P - 1) ln (1 + f) - P ln (1 + f) + (1 - P) ln (1 - f) +
        P ln (1 - f) = ln (1 - P) - ln (P)

or:

    ln (1 - f) - ln (1 + f) =l n (1 - P) - ln (P)

and performing the logarithmic operations:


       1 - f      1 - P
    ln ----- = ln -----
       1 + f        P

and exponentiating:

    1 - f   1 - P
    ----- = -----
    1 + f     P

which reduces to:

    P(1 - f) = & (1 - P) (1 + f)

and expanding:

    P - Pf = 1 - Pf - P + f

or:

    P = 1 - P + f

and, finally:

    f = 2P - 1

As a passing note, the methodology used in this derivation comes from
information-theoretic concepts, formally called entropic principles,
and is firmly entrenched branch of market and economic analysis.

Continuing with the deriviation of the methodolgy used herein,
consider a gambler, wagering on the iterated outcomes of an unfair
tossed coin game. A fraction, f, of the gambler's capital will be
wagered on the outcome of each iteration of the unfair tossed coin,
and if the coin comes up heads, with a probability, P, then the
gambler wins the iteration, (and an amount equal to the wager is added
to the gambler's capital,) and if the coin comes up tails, with a
probability of 1 - P, then the gambler looses the iteration, (and an
amount of the wager is subtracted from the gambler's capital.)

As a passing note, the iterations of a random variable, a flipped coin
in this case, that are added together (ie., to a cumulative sum,) the
gambler's capital, in this case, are called "fractal" processes. The
origins of the name are recent and obscure, but there are different
varieties of fractal processes. In this case, since the distribution
of the increments is either plus or minus one, it is called a Brownian
motion fractal. If the distribution of the increments had a Gaussian,
or normal distribution, it would be called a fractional Brownian
motion fractal. (Typically distribution of the increments in a stock
price time series fall someplace in between the two.) The analytical
methodology of investigation into such matters is called "fractal
analysis," and that is what is going to be done here, in general, for
the gambler's capital, which is a Brownian motion fractal.

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

    C(1) = C(0) * (1 + c(1) * f(1))

after the first iteration of the game, and:

    C(2) = C(0) * ((1 + c(1) * f(1)) * (1 + c(2) * f(2)))

after the second iteration of the game, and, in general, after the
n'th iteration of the game:

    C(n) = C(0) * ((1 + c(1) * f(1)) * (1 + c(2) * f(2)) * ...
           * (1 + c(n) * f(n)) * (1 + c(n + 1) * f(n + 1)))

For the normalized increments of the time series of the gambler's
capital, it would be convenient to rearrange these formulas. For the
first iteration of the game:

    C(1) - C(0) = C(0) * (1 + c(1) * f(1)) - C(0)

or

    C(1) - C(0)   C(0) * (1 + c(1) * f(1)) - C(0)
    ----------- = -------------------------------
       C(0)                   C(0)

and after reducing, the first normalized increment of the gambler's
capital time series is:

    C(1) - C(0)
    ----------- = (1 + c(1) * f(1)) - 1 = c(1) * f(1)
       C(0)

and for the second iteration of the game:

    C(2) = C(0) * ((1 + c(1) * f(1)) * (1 + c(2) * f(2)))

but C(0) * ((1 + c(1) * f(1)) is simply C(1):

    C(2) = C(1) * (1 + c(2) * f(2))

or:

    C(2) - C(1) = C(1) * (1 + c(2) * f(2)) - C(1)

which is:

    C(2) - C(1)   C(1) * (1 + c(2) * f(2)) - C(1)
    ----------- = -------------------------------
       C(1)                    C(1)

and after reducing, the second normalized increment of the gambler's
capital time series is:

    C(2) - C(1)
    ----------- = 1 + c(2) * f(2)) - 1 = c(2) * f(2)
       C(1)

and it should be obvious that the process can be repeated
indefinitely, so, the n'th normalized increment of the gambler's
capital time series is:

    C(n) - C(n - 1)
    --------------- = c(n) * f(n)
         C(n)

Note that we can tell the fraction of the capital that the gambler
wagered in the n'th iteration, it is simply the absolute value of the
normalized increment for the iteration, | c(n) * f(n) |, ie., c(n) *
f(n) is what was won or lost in the n'th iteration, and removing c(n)
= plus or minus 1, is the fraction of the wager. Another, more formal
alternative, is to square the n'th normalized increment, (which, also,
removes any negative sign,) and then take the square root of the
square. Which leads to the formalization for the root mean square of
the normalized increments, rms, (provided that n is sufficiently
large):

               n
             -----                     2
       2   1 \      [ C(t) - C(t - 1) ]
    rms  = -  >     [ --------------- ]
           n /      [   C(t - 1)      ]
             -----
             i = 0

This is an important concept, since it shows that rms = f, or:

               n
             -----
       2   1 \       2   1      2    2
    rms  = -  >     f  = - n * f  = f
           n /           n
             -----
             i = 0

or, importantly:

    rms = f

For the average, avg, of the normalized increments of the gambler's
capital, consider that in an interval of n many iterations of the
game, (provided that n is sufficiently large,) there will be P many
wins, and 1 - P many losses, and since the gambler's capital increased
by +f for the wins, and -f for the losses, or:

    avg = f * [P - (1 - P)] = f * (2P - 1)

but since f = rms:

    avg = rms * (2P - 1)

or:

    avg
    --- = 2P - 1
    rms

and rearranging:

         avg
    2P = --- + 1
         rms

and solving for P:

        avg
        --- + 1
        rms
    P = -------
           2

Which is the formula for the Shannon probability, P, as a function of
the average and root mean square of the normalized increments of the
gambler's capital, avg and rms, respectively. It is an important
concept that with the measurement of these two quantities, (and the
metrics on these two quantities can be deduced dynamically, or "on the
fly,") that an optimal wagering strategy, (or cash flow optimization,)
can be formulated.

It should be noted that this derivation is for analyzing a time series
that is characterized as a Brownian motion fractal. A similar
derivation can be used for time series that are characterized by
fractional Brownian motion. However, the derivation is much more
formidable, mathematically.

As a matter of practical interest, the term "provided that n is
sufficiently large" needs to be qualified. Note that when the term
"running average" or "running root mean square" is used, we really
need to know how many iterations of coin tosses, n, are necessary to
be considered "sufficiently large." If we consider the formula:

        avg
        --- + 1
        rms
    P = -------
           2

and noting that the Shannon probability, P, has a range 0 <= P <= 1,
and we are using a summing process for both the average, and root mean
square of the normalized increments, then n would have to be 100 to
achieve a somewhat less than 1% error in P. The reasoning is that if
we sum 100 ones, then the resultant sum would be 100, and the next
iteration that is to be added to the sum could create at most a 1%
error. The implication of this is that one should use a window of at
least 100 time units. (hours, days, weeks, or whatever is being used
as a unit time in the time series being analyzed,) to achieve a 1%, or
better uncertainty in P. In stock price performance analysis, this is
a marginal accuracy, so a larger window size would be recommended.

As a few examples of using very simple programs to perform fractal
metric analysis on stock time series:

    tscoin -p 0.6 2500

would generate a fractal time series characterized by optimal Brownian
motion consisting of 2500 records, and a Shannon probability, P, of
0.6.

    tscoins -p 0.6 2500

would generate a fractal time series characterized by optimal
fractional Brownian motion consisting of 2500 records, and a Shannon
probability, P, of 0.6.

    tscoins -p 0.6 -f 0.55 2500

would generate a fractal time series characterized by non-optimal
fractional Brownian motion consisting of 2500 records, and a Shannon
probability, P, of 0.6, with a wagering fraction of 0.1.

    tscoins -p 0.6 -f 0.55 2500 | tsfraction

would generate the normalized increments of a fractal time series
characterized by non-optimal fractional Brownian motion consisting of
2500 records, and a Shannon probability, P, of 0.6, with a wagering
fraction of 0.1.

    tscoins -p 0.6 -f 0.55 2500 | tsfraction | tsavg -p
    tscoins -p 0.6 -f 0.55 2500 | tsfraction | tsrms -p

would generate average and the root mean square of the normalized
increments of a fractal time series characterized by non-optimal
fractional Brownian motion consisting of 2500 records, and a Shannon
probability, P, of 0.6, with a wagering fraction of 0.1.

    tsfraction my.stock | tsavg -p
    tsfraction my.stock | tsrms -p

would measure the average and the root mean square of the normalized
increments of the stock time series, my.stock.

It would be convenient to consolidate the various programs into a
single monolithic architecture for the analysis and simulation of
wagering strategies of stock market time series.  It would, further,
be convenient, from a comparative standpoint, to let value of the
portfolio, at time zero, be the same as the price of a single stock at
the beginning of the simulation, so that the portfolio value using the
wagering strategy to invest in a single stock can be compared to the
price of the stock, over time. To reiterate the previous concepts,
suppose that the measurement yielded that the the the fraction of the
capital to be invested, f, was 0.2, (ie., a Shannon probability of
0.6,) then we might invest the entire portfolio in the stock, and our
portfolio would be modeled as 20% of the portfolio would be wagered at
any time, and 80% would be considered as "cash reserves," even though
the 80% is actually invested in the stock. Assume the following pseudo
code:

    calculate the average and root mean square of the normalized
    increments, avg and rms, respectively

    capital = value of stock at time 0, (ie., the portfolio value at
                                        time zero, is one share of
                                        stock)

                    2
                 avg    1
    multiplier = ---- * -, (ie., the value of the multiplier, F * f
                    3   f  in the derivations, by which the the
                 rms       fraction of the capital that is to be
                           wagered must be increased, ie., F =
                           multiplier)

    for each time interval, (ie., for each increment in the time
                            series)

        if not the first interval?, (ie., we need to calculate the
                                    normalized increments, so the
                                    first interval can not be
                                    used)

            capital =
                lastcapital * multiplier *
                (1 + increment), (ie., this is the new capital for
                                  today)

            lastcapital = capital, (ie., this is yesterday's
                                   capital, tomorrow)

where the increment is calculated by subtracting todays stock value
from yesterday's stock value, and dividing by yesterday's stock value:

               V(t) - V(t - 1)
   increment = ---------------
                  V(t - 1)

Note that:

    capital = lastcapital * multiplier * (1 + increment)

                                              V(t) - V(t - 1)
    capital = lastcapital * multiplier * (1 + ---------------)
                                                 V(t - 1)

                                                V(t)
    capital = lastcapital * multiplier * (1 + -------- - 1)
                                              V(t - 1)

                                           V(t)
    capital = lastcapital * multiplier * --------
                                         V(t - 1)

which, not suprisingly, if multiplier = 1, (ie., P = P'):

                              V(t)
    capital = lastcapital * --------
                            V(t - 1)

meaning that the portfolio value would track the stock's value, as we
would expect. Likewise, if multiplier is greater than 1, the portfolio
value would linearly track the stock value, by a constant of
proportionality, and the amount of the portfolio invested in the stock
would be greater than the value of the portfolio, possibly indicating
that the the remainder of the stock investment was purchased on
margin. If the program is used to determine the fraction of the
portfolio that is to be invested in a specific stock, then the fraction
can be calculated from:

    f = 2P - 1

and:

   fraction = multiplier * f

The command line option to for this print output is -p.

It would also be desirable to be able to automatically determine the number
of stocks that should be held. The total capital invested in a stock is:

    capital * multiplier

and dividing this value by the current value of the stock will give
the number of stocks that should be invested in. The command line
option for this print output is -n, and the complete accounting of the
portfolio is printed out, ie., value of portfolio, value of cash, and
value of stocks held.

The -f command line option takes a single argument, and modifies the
value of multiplier. The default value is unity, and it is useful
for evaluating modifications to the portfolio wager strategy.

The -T command line option prints the "theoretical" maximum gains that
are attainable with a stock. The algorithm simply "looks ahead," into
the future, one day, to make a decision on whether to hold all stocks,
or sell all stocks. This option is, obviously, only available when
operating on stock histories, but is useful for comparing a wager
strategy against the maximum attainable growth value of a stock.

The -m command line option disables operations with the variable
multiplier. It simply sets it to unity, and again, is useful for
comparative analysis, to observe the effects of the multiplier
operation on portfolio value growth.

The -i command line option takes a single argument, which is the
amount of initial capital in the portfolio, in currency units. If
this option is not specified, then the initial value of one share
of stock is used as the initial portfolio value.

The -t option specifies that time units, which, if available as the
first column of the input file, should be printed to the output file
with the running value of the portfolio stock.

The -v option prints a list of the options.

The -w option takes a single argument that specifies a running window
size that is used in the calculation of the average and root mean
square of the normalized increments of the stock price time series.
This is useful for using the program to calculate, dynamically, or
"on the fly," stock buy/sell advisories for portfolio management.

Note that the portfolio investment simulation model is very simple,
and assumes perfect liquidity of the stock, (ie., as many as necessary
can be bought or sold at exactly the day's closing price of the
stock,) and that there are no transaction commissions.

Comments and/or bug reports should be addressed to:

    john@email.johncon.com (John Conover)

$Revision: 0.0 $
$Date: 2006/01/18 19:36:00 $
$Id: tsstock.c,v 0.0 2006/01/18 19:36:00 john Exp $
$Log: tsstock.c,v $
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

static char rcsid[] = "$Id: tsstock.c,v 0.0 2006/01/18 19:36:00 john Exp $"; /* program version */
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
    "Simulate the optimal gains of a stock investment\n",
    "Usage: tsstock [-f fraction] [-i value] [-m] [-n] [-p] [-P m] [-T] [-t]\n",
    "               [-w size] [-v] [filename]\n",
    "    -f fraction, optimal incremental changes are multiplied by fraction\n",
    "    -i value, initial value of capital\n",
    "    -m, set multiplier = 1.0\n",
    "    -n, print the (number held @ price = value of stocks) + cash = capital\n",
    "    -p, print the (f = (2 * P) - 1) * multiplier = portfolio fraction wagered\n",
    "    -P m, Shannon probabability, below which no wager will be made\n",
    "    -T, print the theoretical capability of the stock, instead of the\n",
    "        simulation\n",
    "    -t, sample's time will be included in the output time series\n",
    "    -w size, specifies the window size for the running average\n",
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
static int windowed (FILE *infile, int w, int m, int n, int p, int T, int t, double f, double P, double capital);
static int nonwindowed (FILE *infile, int m, int n, int p, int T, int t, double f, double P, double capital);

#else

static void print_message (); /* print any error messages */
static int strtoken ();
static int windowed ();
static int nonwindowed ();

#endif

#ifdef __STDC__

int main (int argc, char *argv[])

#else

int main (argc, argv)
int argc;
char *argv[];

#endif

{
    int retval = NOERROR, /* return value, assume no error */
        n = 0, /* print the number of stocks held? */
        m = 1, /* don't adjust multiplier flag, 0 = yes, don't adjust multiplier, 1 = no, don't adjust multiplier */
        p = 0, /* print only the fraction of capital to be wagered and the Shannon probability */
        T = 0, /* print the theoretical capability of the stock, instead of the simulation */
        t = 0, /* print time of samples flag, 0 = no, 1 = yes */
        w = 0, /* window size for the running average, 0 means to use entire time series */
        c; /* command line switch */

    double f = (double) 1.0, /* fraction of change in incremental changes */
           P = (double) 0.5, /* Shannon probabability, below which no wager will be made */
           capital = (double) 0.0; /* running value of the capital */

    FILE *infile; /* reference to input file */

    while ((c = getopt (argc, argv, "f:i:mnpP:Ttw:v")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

            case 'f': /* request for fraction of change in incremental changes */

                f = atof (optarg); /* yes, set the fraction of change in incremental changes */
                break;

            case 'i': /* request for initial value of capital? */

                capital = atof (optarg); /* yes, set the initial value of capital */
                break;

            case 'm': /* request for don't adjust multiplier? */

                m = 0; /* yes, reset the don't adjust multiplier flag */
                break;

            case 'n': /* request for print only the number of stocks held? */

                n = 1; /* yes, set the print the number of stocks held flag */
                break;

            case 'p': /* request for print only the fraction of capital to be wagered and the Shannon probability? */

                p = 1; /* yes, set the print only the fraction of capital to be wagered and the Shannon probability flag */
                break;

            case 'P': /* request for Shannon probabability, below which no wager will be made */

                P = atof (optarg); /* yes, set the Shannon probabability, below which no wager will be made */
                break;

            case 'T': /* request print the theoretical capability of the stock, instead of the simulation? */

                T = 1; /* yes, set the print the theoretical capability of the stock, instead of the simulation flag */
                break;

            case 't': /* request printing time of samples? */

                t = 1; /* yes, set the print time of samples flag */
                break;

            case 'w': /* request for window size for the running average */

                w = atoi (optarg); /* yes, set the window size for the running average */
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

            if (w == 0) /* window size flag not set yet? */
            {
                retval = nonwindowed (infile, m, n, p, T, t, f, P, capital); /* a window size has not been specified, pass all arguments to nonwindowed () */
            }

            else
            {
                retval = windowed (infile, w, m, n, p, T, t, f, P, capital); /* a window size has been specified, pass all arguments to windowed () */
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

/*

Construct the data set for the windowed Shannon probabilities.

static int windowed (FILE *infile, int w, int m, int n, int p, int T, int t, double f, double P, double capital)

The variables from main () are passed to this routine for construction
of the data structures for computation of all Shannon
probabilities-this routine is called if a window size was specified on
the command line-returns EALLOC if sufficient space could for the data
structure could not be allocated, NOERROR on success.

*/

#ifdef __STDC__

static int windowed (FILE *infile, int w, int m, int n, int p, int T, int t, double f, double P, double capital)

#else

static int windowed (infile, w, m, n, p, T, t, f, P, capital)
FILE *infile;
int w;
int m;
int n;
int p;
int T;
int t;
double f;
double P;
double capital;

#endif

{
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         token_separators[] = TOKEN_SEPARATORS;

    int count = 0, /* input file record counter */
        element = 0, /* element counter in the array of the last w many elements from the time series */
        retval = EALLOC,  /* return value, assume error allocating memory */
        fields = 0, /* number of fields in a record */
        wagering = 0; /* shannon probability > 0.5 means wagering, 0 = no, 1 = yes */

    double sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           sum = (double) 0.0, /* running value of cumulative sum of squares */
           currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           increment = (double) 0.0, /* value of a normalized increment from the time series */
           avg, /* value of the average of the increments of the time series */
           rms, /* value of root mean square of the increments of the time series */
           shannon, /* the Shannon probability, as calculated by (((avg / rms) + 1) / 2) */
           fraction, /* the optimal fraction to be wagered, as calculated by fraction = twice the Shannon probability minus one */
           multiplier, /* the amount the root mean squared value of the normalized increments is to be increased/decreased by */
           lastcapital = (double) 0.0, /* the last value of the capital */
           temp, /* temporary double storage */
           *value = (double *) 0; /* reference to the array of the last w many values from the time series */

    if ((value = (double *) malloc ((w) * sizeof (double))) != (double *) 0) /* allocate space for the array of the last w many normalized increments from the time series */
    {
        retval = NOERROR; /* assume no error */

        for (element = 0; element < w; element ++) /* for each element in the array of the last w many normalized increments from the time series */
        {
            value[element] = (double) 0.0; /* initialize each value element to zero */
        }

        element = 0; /* reset the element counter in the array of the last w many elements from the time series */

        while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* read the records from the input file */
        {

            if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
            {

                if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
                {
                    currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                    if (count > 0) /* not first record? */
                    {

                        /*

                        The idea here is to make a running window of the root mean square and
                        average of the normalized increments of the stock's price time series
                        so that the Shannon probability for the window can be calculated.
                        This is done by saving the last w many normalized increments of the
                        time series, and subtracting the least recent value in the window
                        from the running sum of the normalized increments of the time series,
                        and the square of the least recent value in the window from the sum of
                        the squares of the normalized increments of the time series. The
                        current value of the normalized increment of the time series is then
                        added to the sum, and squared and added to the sum of squares, and
                        replaces the least recent value of the normalized increments of the
                        window. The implicit index element is incremented to index the next
                        least recent element in the window; if it is beyond the array bounds,
                        it raps around to the first element in the array. The average, root
                        mean square, Shannon probability, fraction, and multiplier are
                        calculated.

                        */

                        increment = (currentvalue - lastvalue) / lastvalue; /* save the normalized increment of the element in the time series */

                        temp = value[element]; /* save the value of the oldest value of the normalized increment in the time series from the cumulative sum of the normalized increments of the time series */

                        sum = sum - temp; /* subtract the oldest value of the normalized increment in the time series from the cumulative sum of the normalized increments of the time series */
                        sum = sum + increment; /* add the value of the normalized increment of the current sample in the time series to the cumulative sum of the time series */

                        sumsquared = sumsquared - (temp * temp); /* subtract the square of the oldest value of the normalized increment in the time series from the cumulative sum of squares of the normalized increments of the time series */
                        sumsquared = sumsquared + (increment * increment); /* add the square of the normalized increment of the current sample in the time series to the cumulative sum of squares of the time series */

                        value[element] = increment; /* replace the oldest value of the normalized increment in the time series with the current value of the normalized increment from the time series */

                        element ++; /* next element in the array of the last w many elements from the time series */

                        if (element >= w) /* next element in the array of the last w many elements from the time series greater than the array size? */
                        {
                            element = 0; /* yes, next element in the array of the last w many elements from the time series is the first element in the array */
                        }

                        if (count >= w) /* yes, greater than w many records so far? */
                        {
                            avg = sum / (double) w; /* save the value of the average of the normalized increments in the window */
                            rms = sqrt (sumsquared / (double) w); /* save the value of the root mean square of the normalized increments in the window */
                            shannon = (((avg / rms) + (double) 1.0) / (double) 2.0); /* calculate the shannon probability, as calculated by (((avg / rms) + 1) / 2) */
                            fraction = ((double) 2.0 * shannon) - (double) 1.0; /* calculate the optimal fraction to be wagered, as calculated by fraction = twice the Shannon probability minus one */

                            if (m == 1) /* don't adjust multiplier flag set? */
                            {
                                multiplier = ((avg * avg) / (rms * rms * rms)) / fraction; /* yes, the amount the root mean squared value of the normalized increments is to be increased/decreased by */
                            }

                            else
                            {
                                multiplier = (double) 1.0; /* no, the multiplier and fraction are equal ie., a multiplier of unity */
                            }

                            if (count == w) /* first time through this loop, ie., the window array has been filled, is it time to consider a wager? */
                            {

                                if (capital == (double) 0.0) /* yes, capital assigned from the command line? */
                                {
                                    capital = lastcapital = currentvalue; /* no, save the beginning capital, assumed to be the value of the stock when count = w */
                                }

                            }

                            /*

                            If wagering = 1, then a wager was made in the previous time unit, and
                            the new value of capital has to be calculated based on the ratio of
                            the value of the stock in the previous time unit, and the value of the
                            stock in this time unit; if wagering = 0, no bet was made, and the
                            value of capital remains the same.

                            */

                            if (wagering == 1) /* shannon probability > 0.5 ? */
                            {
                                capital = lastcapital * ((double) 1.0 + (f * multiplier * increment)); /* yes, calculate the current value of the capital */
                            }

                            /*

                            If shannon > 0.5, then a wager will be made in this time unit, if
                            not, no wager will be made.

                            */

                            wagering = (shannon > P) ? 1 : 0; /* shannon probability > 0.5 means wagering, 0 = no, 1 = yes */

                            if (t == 1) /* print time of samples? */
                            {

                                if (fields > 1) /* yes, more that one field? */
                                {
                                    (void) printf ("%f\t", atof (token[0])); /* yes, print the sample's time */
                                }

                                else
                                {
                                    (void) printf ("%d\t", count); /* no, print the sample's time  which is assumed to be the record count */
                                }

                            }

                            if (p == 1) /* print only the fraction of capital to be wagered and the Shannon probability flag set? */
                            {
                                (void) printf ("(%f = (2 * %f) - 1) * %f = %f\n", fraction * f, shannon, multiplier, fraction * multiplier * f); /* yes, print only the fraction of capital to be wagered and the Shannon probability */
                            }

                            else
                            {

                                if (n == 1) /* print number of stocks held? */
                                {
                                    (void) printf ("(%f @ %f = %f) + %f = ", (capital * f * multiplier) / currentvalue, currentvalue, capital * f * multiplier, capital * (1 - (f * multiplier))); /* yes, print the number of stocks held, which is the fraction, multiplied by the multiplier, and divided by the current value of the stock */
                                }

                                (void) printf ("%f\n", capital); /* print the capital */
                            }

                            lastcapital = capital; /* save the last value of the capital */
                        }

                    }

                    lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                    count ++; /* increment the count of records from the input file */
                }

            }

        }

        free (value); /* free the space for the array of the last w many normalized increments from the time series */
    }

    return (retval); /* return any errors */

#ifdef LINT

    T = (double) 0.0; /* for lint issues */

#endif

}

/*

Construct the data set for the non-windowed Shannon probabilities.

static int nonwindowed (FILE *infile, int m, int n, int p, int T, int t, double f, double P, double capital);

The variables from main () are passed to this routine for construction
of the data structures for computation of all Shannon
probabilities-this routine is called if a window size was not
specified on the command line-returns EALLOC if sufficient space could
for the data structure could not be allocated, NOERROR on success.

This function is similar to windowed (), except that the Shannon
probability is measured for the entire time series, and then the time
series ran with a constant wagering strategy, as determined by the
average Shannon probability for the entire time series.

*/

#ifdef __STDC__

static int nonwindowed (FILE *infile, int m, int n, int p, int T, int t, double f, double P, double capital)

#else

static int nonwindowed (infile, m, n, p, T, t, f, P, capital)
FILE *infile;
int m;
int n;
int p;
int T;
int t;
double f;
double P;
double capital;

#endif

{
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         token_separators[] = TOKEN_SEPARATORS;

    int count = 0, /* input file record counter */
        retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        i; /* loop counter */

    double currentvalue, /* value of current sample in time series */
           lastvalue = (double) 0.0, /* value of last sample in time series */
           increment, /* value of the normalized increment of a sample in the time series */
           *value = (double *) 0, /* reference to array of data values from file */
           *position = (double *) 0, /* reference to array of time/position values from the file */
           *ref = (double *) 0, /* last reference to array of data from file */
           sum = (double) 0.0, /* running value of cumulative sum */
           sumsquared = (double) 0.0, /* running value of cumulative sum of squares */
           avg, /* value of the average of the increments of the time series */
           rms, /* value of root mean square of the increments of the time series */
           shannon, /* the Shannon probability, as calculated by (((avg / rms) + 1) / 2) */
           fraction, /* the optimal fraction to be wagered, as calculated by fraction = twice the Shannon probability minus one */
           multiplier, /* the amount the root mean squared value of the normalized increments is to be increased/decreased by */
           lastcapital = (double) 0.0; /* the last value of the capital */

    while (fgets (buffer, BUFLEN, infile) != (char *) 0) /* count the records in the input file */
    {

        if ((fields = strtoken (buffer, parsebuffer, token, token_separators)) != 0) /* parse the record into fields, skip the record if there are no fields */
        {

            if (token[0][0] != '#') /* if the first character of the first field is a '#' character, skip it */
            {
                currentvalue = atof (token[fields - 1]); /* save the value of the current sample in the time series */

                if (count != 0) /* not first record? */
                {
                    increment = ((currentvalue - lastvalue) / lastvalue); /* save the value of the normalized increment of a sample in the time series */
                    sum = sum + increment; /* add the value of the normalized increment of a sample in the time series to the running value of cumulative sum */
                    sumsquared = sumsquared + (increment * increment); /* add the square of the value of the normalized increment of a sample in the time series to the running value of cumulative sum of squares */
                }

                ref = value; /* save the last reference to array of data from file */

                if ((value = (double *) realloc (value, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of data values from the input file */
                {
                    value = ref; /* couldn't allocate space for the array of data values from the input file, restore the last reference to array of data from file */
                    retval = EALLOC;  /* assume error allocating memory */
                    break; /* and stop */
                }

                value[count] = currentvalue; /* save the sample's value */

                if (t == 1) /* print time of samples? */
                {
                    ref = position; /* save the last reference to array of data from file */

                    if ((position = (double *) realloc (position, (count + 1) * sizeof (double))) == (double *) 0) /* allocate space for the array of time/position values from the input file */
                    {
                        position = ref; /* couldn't allocate space for the array of time/position values from the input file, restore the last reference to array of time/position from file */
                        retval = EALLOC;  /* assume error allocating memory */
                        break; /* and stop */
                    }

                    if (fields > 1) /* yes, more that one field? */
                    {
                        position[count] = atof (token[0]); /* yes, save the sample's time/position */
                    }

                    else
                    {
                        position[count] = (double) count; /* no, save the sample's time/position which is assumed to be the record count */
                    }

                }

                lastvalue = currentvalue; /* save the current value of the sample in the time series as the last value */
                count ++; /* increment the count of records from the input file */
            }

        }

    }

    avg = sum / (double) count; /* save the value of the average of the increments of the time series */
    rms = sqrt (sumsquared / (double) count); /* save the value of root mean square of the time series */
    shannon = (((avg / rms) + (double) 1.0) / (double) 2.0); /* calculate the shannon probability, as calculated by (((avg / rms) + 1) / 2) */
    fraction = ((double) 2.0 * shannon) - (double) 1.0; /* calculate the optimal fraction to be wagered, as calculated by fraction = twice the Shannon probability minus one */

    if (m == 1) /* don't adjust multiplier flag set? */
    {
        multiplier = ((avg * avg) / (rms * rms * rms)) / fraction; /* yes, the amount the root mean squared value of the normalized increments is to be increased/decreased by */
    }

    else
    {
        multiplier = (double) 1.0; /* no, the multiplier and fraction are equal ie., a multiplier of unity */
    }

    if (capital == (double) 0.0) /* initial value of capital set? */
    {
        capital = value[0]; /* no, save the beginning capital, assumed to be the value of the stock at time zero */
    }

    if (T == 1) /* print the theortical capability of the stock, instead of the simulation flag set? */
    {

        for (i = 0; i < count; i ++) /* for each record in the input file */
        {

            if (i != 0) /* if not the first record in the input file */
            {

                if (value[i] > value[i - 1]) /* stock value increase? */
                {
                    capital = capital * (value[i] / value[i - 1]); /* yes, the capital raised by the amount the stock raised, since there is a hundred percent of the capital invested in the stock, else it remains constant, ie., it was sold at the last high, and repurchased in the interval preceeding the raise in value */
                }

            }

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* print the time/position of the record in the input file */
            }

            (void) printf ("%f\n", capital); /* print the capital */
            lastcapital = capital; /* save the last value of the capital */
        }

    }

    else
    {

        for (i = 0; i < count; i ++) /* for each record in the input file */
        {

            if (i != 0) /* if not the first record in the input file */
            {
                capital = lastcapital * (1 + (f * multiplier * ((value[i] - value[i - 1]) / value[i - 1]))); /* calculate the current value of the capital */
            }

            if (t == 1) /* print time of samples? */
            {
                (void) printf ("%f\t", position[i]); /* print the time/position of the record in the input file */
            }

            if (p == 1) /* print only the fraction of capital to be wagered and the Shannon probability flag set? */
            {
                (void) printf ("(%f = (2 * %f) - 1) * %f = %f\n", fraction * f, shannon, multiplier, fraction * multiplier * f); /* yes, print only the fraction of capital to be wagered and the Shannon probability */
            }

            else

            {

                if (n == 1) /* print number of stocks held? */
                {
                    (void) printf ("(%f @ %f = %f) + %f = ", (capital * f * multiplier) / value[i], value[i], capital * f * multiplier, capital * (1 - (f * multiplier))); /* yes, print the number of stocks held, which is the fraction, multiplied by the multiplier, and divided by the current value of the stock */
                }

                (void) printf ("%f\n", capital); /* print the capital */
            }

            lastcapital = capital; /* save the last value of the capital */
        }

    }

    if (value != (double *) 0) /* allocated space for the array of data values from the input file? */
    {
        free (value); /* yes, free the space for the array of data values from the input file */
    }

    if (position != (double *) 0) /* allocated space for the array of time/position values from the input file? */
    {
        free (position); /* yes, free the space for the array of time/position values from the input file */
    }

    return (retval); /* return any errors */

#ifdef LINT

    P = (double) 0.0; /* for lint issues */

#endif

}
