# -----------------------------------------------------------------------------
#
# A license is hereby granted to reproduce this software source code and
# to create executable versions from this source code for personal,
# non-commercial use.  The copyright notice included with the software
# must be maintained in all copies produced.
#
# THIS PROGRAM IS PROVIDED "AS IS". THE AUTHOR PROVIDES NO WARRANTIES
# WHATSOEVER, EXPRESSED OR IMPLIED, INCLUDING WARRANTIES OF
# MERCHANTABILITY, TITLE, OR FITNESS FOR ANY PARTICULAR PURPOSE.  THE
# AUTHOR DOES NOT WARRANT THAT USE OF THIS PROGRAM DOES NOT INFRINGE THE
# INTELLECTUAL PROPERTY RIGHTS OF ANY THIRD PARTY IN ANY COUNTRY.
#
# Copyright (c) 1994-2006, John Conover, All Rights Reserved.
#
# Comments and/or bug reports should be addressed to:
#
#     john@email.johncon.com (John Conover)
#
# -----------------------------------------------------------------------------
#
# Make the LaTeX parameters for this market
#
# Input must be successive records that contain:
#
#     0) the mean of the file data.tsfraction, from file
#         "data.tsfraction.tsnormal-p.mean"
#     1) the standard deviation of the file data.tsfraction, from the file,
#         "data.tsfraction.tsnormal-p.stddev"
#     2) the root mean square of the file data.tsfraction, from file
#         "data.tsfraction.tsrms-p"
#     3) the fraction of cumulative returns wagered, from file
#         "data.tsfraction.abs.tsnormal-p.mean"
#     4) the standard deviation of cumulative returns wagered, from the
#         the file "data.tsfraction.abs.tsnormal-p.stddev"
#     5) the constant in the least squares approximation of the file
#         data.tsfraction, from the file "data.tsfraction.tslsq-p.constant"
#     6) the slope in the least squares approximation of the file
#         data.tsfraction, from the file "data.tsfraction.tslsq-p.slope"
#     7) the constant in the least squares approximation of the file
#         data.tsfraction.abs , from the file
#         "data.tsfraction.abs.tslsq-p.constant"
#     8) the slope in the least squares approximation of the file
#         data.tsfraction.abs, from the file
#         "data.tsfraction.abs.tslsq-p.slope"
#     9) the hurst coefficient, from file
#        "data.tsfraction.tshurst-d.tslsq-p.hurstall"
#     10) the hurst coefficient for the lower end of the graph, from file
#         "data.tsfraction.tshurst-d.tslsq-p.low.hurstlow"
#     11) the h parameter, from file
#         "data.tsfraction.tshcalc-d.tslsq-p.hcalcall"
#     12) the h parameter for the lower end end of the graph, from file
#         "data.tsfraction.tshcalc-d.tslsq-p.low.hcalclow"
#     13) the maximum value of Shannon probability, from file
#         "data.tsshannonmax-p.max"
#     14) the logrithmic returns using tslogreturns, from file
#         "data.tslogreturns-p.tsshannon.returns"
#     15) the count of records with negative signes in data.tsfraction, from
#         the file "data.tsfraction.pmaxnumerator"
#     16) the count of records in data.tsfraction, from the file
#         "data.tsfraction.pmaxdenominator"
#     17) the mean of the file tsunfairbrownian-f.tsfraction, from file
#         "tsunfairbrownian-f.fraction.mean"
#     18) the standard deviation of the file tsunfairbrownian-f.tsfraction,
#         from file "tsunfairbrownian-f.fraction.mean"
#     19) the Shannon probability using tslogreturns, from file
#         "data.tslogreturns-p.tsshannon.probability"
#     20) the logrithmic returns in bits from the file
#         "data.tslsq-e-p.bits"
#     21) the traditional hurst coefficient, from file
#        "data.tshurst.tslsq-p.hurstall"
#     22) the traditional hurst coefficient for the lower end of the graph,
#         from file "data.tshurst.tslsq-p.low.hurstlow"
#     23) the traditional h parameter, from file
#         "data.tshcalc.tslsq-p.hcalcall"
#     24) the traditional h parameter for the lower end end of the graph,
#         from file "data.tshcalc.tslsq-p.low.hcalclow"
#     25) the chi-squared value, from file "chisquared"
#     26) the critical value for the chi-squared value, from file
#         "critical"
#
# $Revision: 0.0 $
# $Date: 2006/01/19 19:52:07 $
# $Id: maketex.awk,v 0.0 2006/01/19 19:52:07 john Exp $
# $Log: maketex.awk,v $
# Revision 0.0  2006/01/19 19:52:07  john
# Initial version
#
#
{

    if (linectr == 0)
    {
        datafractionmean = $0
        printf ("\\renewcommand{\\datafractionmean}{%f}\n", datafractionmean)
        datafractionmeanbits = log (datafractionmean + 1.0) / log (2.0)
        printf ("\\renewcommand{\\datafractionmeanbits}{%f}\n", datafractionmeanbits)
        datafractionmeanq = datafractionmean / 3.0
        printf ("\\renewcommand{\\datafractionmeanq}{%f}\n", datafractionmeanq)
        datafractionmeanbitsq = log (datafractionmeanq + 1.0) / log (2.0)
        printf ("\\renewcommand{\\datafractionmeanbitsq}{%f}\n", datafractionmeanbitsq)
    }

    if (linectr == 1)
    {
        datafractionstddev = $0
        printf ("\\renewcommand{\\datafractionstddev}{%f}\n", datafractionstddev)
    }

    if (linectr == 2)
    {
        datafractionrms = $0
        printf ("\\renewcommand{\\datafractionrms}{%f}\n", datafractionrms)
        avgrms = ((datafractionmean / datafractionrms) + 1.0) / 2.0
        printf ("\\renewcommand{\\avgrms}{%f}\n", avgrms)
        ncompanies = datafractionmean / (datafractionrms * datafractionrms)
        printf ("\\renewcommand{\\ncompanies}{%f}\n", ncompanies)
        pncompanies = ((datafractionmean / (sqrt (ncompanies) * datafractionrms)) + 1.0) / 2.0
        printf ("\\renewcommand{\\pncompanies}{%f}\n", pncompanies)
    }

    if (linectr == 3)
    {
        datafractionabsmean = $0
        printf ("\\renewcommand{\\datafractionabsmean}{%f}\n", datafractionabsmean)
    }

    if (linectr == 4)
    {
        datafractionabsstddev = $0
        printf ("\\renewcommand{\\datafractionabsstddev}{%f}\n", datafractionabsstddev)
    }

    if (linectr == 5)
    {
        datafractionconstant = $0
        printf ("\\renewcommand{\\datafractionconstant}{%f}\n", datafractionconstant)
        datafractionconstantbits = log (datafractionconstant + 1.0) / log (2.0)
        printf ("\\renewcommand{\\datafractionconstantbits}{%f}\n", datafractionconstantbits)
        datafractionconstantq = datafractionconstant / 3.0
        printf ("\\renewcommand{\\datafractionconstantq}{%f}\n", datafractionconstantq)
        datafractionconstantbitsq = log (datafractionconstantq + 1.0) / log (2.0)
        printf ("\\renewcommand{\\datafractionconstantbitsq}{%f}\n", datafractionconstantbitsq)
    }

    if (linectr == 6)
    {
        datafractionslope = $0
        printf ("\\renewcommand{\\datafractionslope}{%f}\n", datafractionslope)
    }

    if (linectr == 7)
    {
        datafractionabsconstant = $0
        printf ("\\renewcommand{\\datafractionabsconstant}{%f}\n", datafractionabsconstant)
    }

    if (linectr == 8)
    {
        datafractionabsslope = $0
        printf ("\\renewcommand{\\datafractionabsslope}{%f}\n", datafractionabsslope)
    }

    if (linectr == 9)
    {
        hurstall = $0
        printf ("\\renewcommand{\\hurstall}{%f}\n", hurstall)
    }

    if (linectr == 10)
    {
        hurstlow = $0
        printf ("\\renewcommand{\\hurstlow}{%f}\n", hurstlow)
        hurstlowtwo = hurstlow * 2.0
        printf ("\\renewcommand{\\hurstlowtwo}{%f}\n", hurstlowtwo)
        hurstlowhundred = hurstlow * 100.0
        printf ("\\renewcommand{\\hurstlowhundred}{%f}\n", hurstlowhundred)
    }

    if (linectr == 11)
    {
        hcalcall = $0
        printf ("\\renewcommand{\\hcalcall}{%f}\n", hcalcall)
    }

    if (linectr == 12)
    {
        hcalclow = $0
        printf ("\\renewcommand{\\hcalclow}{%f}\n", hcalclow)
    }

    if (linectr == 13)
    {
        shannonmax = $0
        printf ("\\renewcommand{\\shannonmax}{%f}\n", shannonmax)
        twoponemax = 2.0 * shannonmax - 1.0
        printf ("\\renewcommand{\\twoponemax}{%f}\n", twoponemax)
    }

    if (linectr == 14)
    {
        logreturns = $0
        printf ("\\renewcommand{\\logreturns}{%f}\n", logreturns)
        twologreturns = exp (logreturns * log (2.0))
        printf ("\\renewcommand{\\twologreturns}{%f}\n", twologreturns)
        twologreturnshundred = (twologreturns - 1.0) * 100.0
        printf ("\\renewcommand{\\twologreturnshundred}{%f}\n", twologreturnshundred)
        oneoverlogreturns = 1.0 / logreturns
        printf ("\\renewcommand{\\oneoverlogreturns}{%f}\n", oneoverlogreturns)
    }

    if (linectr == 15)
    {
        pmaxnumerator = $0
    }

    if (linectr == 16)
    {
        pmaxdenominator = $0
        pmax = (pmaxdenominator - pmaxnumerator) / pmaxdenominator
        if (pmax == 1)
        {
            pmax =0.99999
        }
        printf ("\\renewcommand{\\pmax}{%f}\n", pmax)
        twopminusone = (2.0 * pmax) - 1.0
        printf ("\\renewcommand{\\twopminusone}{%f}\n", twopminusone)
        rmsp = datafractionrms * twopminusone
        printf ("\\renewcommand{\\rmsp}{%f}\n", rmsp)
        twopx = ((2.0 * pmax) - 1.0) / (2.0 * sqrt (pmax * (1.0 - pmax)))
        printf ("\\renewcommand{\\twopx}{%f}\n", twopx)
        sigmap = datafractionstddev * twopx
        printf ("\\renewcommand{\\sigmap}{%f}\n", sigmap)

    }

    if (linectr == 17)
    {
        tsunfairbrownianfractionmean = $0
        printf ("\\renewcommand{\\tsunfairbrownianfractionmean}{%f}\n", tsunfairbrownianfractionmean)
    }

    if (linectr == 18)
    {
        tsunfairbrownianfractionstddev = $0
        printf ("\\renewcommand{\\tsunfairbrownianfractionstddev}{%f}\n", tsunfairbrownianfractionstddev)
    }

    if (linectr == 19)
    {
        shannonlogreturns = $0
        printf ("\\renewcommand{\\shannonlogreturns}{%f}\n", shannonlogreturns)
        shannonlogreturnshundred = shannonlogreturns * 100.0
        printf ("\\renewcommand{\\shannonlogreturnshundred}{%f}\n", shannonlogreturnshundred)
        twopone =  (2.0 * shannonlogreturns) - 1.0
        printf ("\\renewcommand{\\twopone}{%f}\n", twopone)
        twoponehundred = twopone * 100.0
        printf ("\\renewcommand{\\twoponehundred}{%f}\n", twoponehundred)
        hundredtwoponehundred = 100.0 - twoponehundred
        printf ("\\renewcommand{\\hundredtwoponehundred}{%f}\n", hundredtwoponehundred)
        hundredshannonlogreturnshundred = 100.0 - shannonlogreturnshundred
        printf ("\\renewcommand{\\hundredshannonlogreturnshundred}{%f}\n", hundredshannonlogreturnshundred)
    }

    if (linectr == 20)
    {
        datatslsqepbits = $0
        printf ("\\renewcommand{\\datatslsqepbits}{%f}\n", datatslsqepbits)
    }

    if (linectr == 21)
    {
        thurstall = $0
        printf ("\\renewcommand{\\thurstall}{%f}\n", thurstall)
    }

    if (linectr == 22)
    {
        thurstlow = $0
        printf ("\\renewcommand{\\thurstlow}{%f}\n", thurstlow)
        thurstlowtwo = thurstlow * 2.0
        printf ("\\renewcommand{\\thurstlowtwo}{%f}\n", thurstlowtwo)
        thurstlowhundred = thurstlow * 100.0
        printf ("\\renewcommand{\\thurstlowhundred}{%f}\n", thurstlowhundred)
    }

    if (linectr == 23)
    {
        thcalcall = $0
        printf ("\\renewcommand{\\thcalcall}{%f}\n", thcalcall)
    }

    if (linectr == 24)
    {
        thcalclow = $0
        printf ("\\renewcommand{\\thcalclow}{%f}\n", thcalclow)
    }

    if (linectr == 25)
    {
        chisquared = $0
        printf ("\\renewcommand{\\chisquared}{%f}\n", chisquared)
    }

    if (linectr == 26)
    {
        critical = $0
        printf ("\\renewcommand{\\critical}{%f}\n", critical)
    }

    linectr++
}
