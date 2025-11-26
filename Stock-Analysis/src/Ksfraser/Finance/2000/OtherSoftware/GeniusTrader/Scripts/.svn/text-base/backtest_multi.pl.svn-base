#!/usr/local/bin/perl -w

#!/usr/bin/perl -w

# Copyright 2004 Oliver Bossert
# This file is distributed under the terms of the General Public License
# version 2 or (at your option) any later version.

use lib '..';

use strict;
use vars qw($db);

use GT::Prices;
use GT::Portfolio;
use GT::PortfolioManager;
use GT::Calculator;
use GT::Report;
use GT::BackTest;
use GT::BackTest::Spool;
use GT::List;
use GT::Eval;
use GT::Conf;
use GT::DateTime;
use GT::Tools qw(:conf :timeframe);
use Pod::Usage;
use Getopt::Long;
use Pod::Usage;

GT::Conf::load();

( our $prog_name = $0 ) =~ s@^.*/@@;    # lets identify ourself

=head1 B<backtest_multi.pl> [ options ] <market file> <system file>

=head2 Description

This version of B<backtest_multi.pl> reads plain text input files
the first of which F<market file> containing codes, one per line
(identical to typical GT F<market files>).

The second input file F<system file> containing gt trading system
specification descriptions (see I<GT/Docs/gt_files.pod>).

Specifically for B<backtest_multi.pl> a F<system file>
may include more than one gt trading system description.
Long line continuation is supported
for F<system file> using the '\' character immediately prior to the
end-of-line character sequence.

B<backtest_multi.pl> will test all systems listed in F<system file>
on all the market codes in F<market file>.

The F<system file> contains one line per defined system, where each
system is defined by its full system name or by an alias. An alias is 
defined in the GT configuration file with entries of the form 

    Aliases::Global::<alias_name> <full_system_name>.

The full system name consists of a set of properties, such as trade 
filters, close strategy, etc., together with their parameters, 
separated by vertical bars ("|").

Multiple properties of the same type can be defined, e.g., there
could be a set of close strategies.
For example,

    System:ADX 30 | TradeFilters:Trend 2 5 | MoneyManagement:Normal

defines a system based on the "ADX" system, using a trend following trade
filter "Trend", and the "Normal" money management.

The following GT standard abbreviations are supported:
    Systems = SY
    CloseStrategy = CS
    TradeFilters = TF
    MoneyManagement = MM
    OrderFactory = OF
    Signals = S
    Indicators = I
    Generic = G

Another example of a full system name is 

    SY:TFS | CS:SY:TFS | CS:Stop:Fixed 4 | MM:VAR

=head2 Options

=over 4

=item --full, --start=<date>, --end=<date>, --nb-item=<nr>

Determines the time interval to consider for analysis. In detail:

=over

=item --start=2001-1-10, --end=2002-11-17

The start and end dates considered for analysis. The date needs to be in the
format configured in ~/.gt/options and must match the timeframe selected. 

=item --nb-items=100

The number of periods to use in the analysis.

=item --full

Consider all available periods.

=back

The periods considered are relative to the selected time frame
(i.e., if timeframe is "day", these indicate a date;
if timeframe is "week", these indicate a week; etc.).
In GT format, use "YYYY-MM-DD" or "YYYY-MM-DD hh:mm:ss" for days
(the latter giving intraday data), "YYYY-WW" for weeks, "YYYY/MM"
for months, and "YYYY" for years.

The interval of periods examined is determined as follows:

=over

=item 1. use --start and --end if present

otherwise default to last (end) price.

=item 2. use --nb-item if present

from first (start) or last (end), whichever has been determined.

=item 3. if --full is present

use first (start) or last (end) price, whichever has B<not> yet been determined.

=item 4. otherwise, evaluate a two year interval.

=back

The first period determined following this procedure is chosen.
If additional options are given, these are ignored
(e.g., if --start, --end, --full are given, --full is ignored).

=back

=over 4

=item --timeframe

The timeframe can be any of the available modules in GT/DateTime.
[ 1min | 5min | 10min | 15min | 30min | hour | 3hour | day | week | month | year ]

=item --max-loaded-items

Determines the number of periods (back from the last period)
that are loaded for a given market from the data base.
Care should be taken to ensure that
these are consistent with the performed analysis.
If not enough data is loaded to satisfy dependencies,
for example, correct results cannot be obtained.
This option is effective only for certain data base
modules and ignored otherwise.

=item --initial_value="<investment amount>" or --iv

set the investment amount for the backtest analysis -- default is 10000.
can also be set using the config option "Backtest::initial_value" which
will is be loaded from $HOME/.gt/options if present.

=item --broker="NoCosts"

Calculate commissions and annual account charge, if applicable,
using GT::Brokers::<broker_name> as broker.

=item --set=I<SETNAME>

A dual purpose option.
Without --set no xml file based trading records will be saved.
When --set=I<SETNAME> is specified it causes B<backtest_multi.pl>
to use I<SETNAME> in the trading history filename.
I<SETNAME> must be a string of one or more valid filename characters.

Two trading record files are written: F<index> and
F<MULTI-SETNAME.bkt> where I<SETNAME> will be the supplied string.

   examples:
    --set=3           will yield file F<MULTI-3.bkt>
    --set=bt_multi    will yield file F<MULTI-bt_multi.bkt>

Stores the backtest results in the "backtests" directory
(refer to your options file for the location of this directory)
using the set name F<SETNAME>.
Use the --set option of B<analyze_backtest.pl> to differentiate
between the different backtest results in your directory.

=item --output-directory=<value>

Specify the output directory path in which to store the
xml based analysis trading record files: F<index> and
the trading history file.
This option can be abbreviated as 'od'.

The following command line fragment

   ... --od=/var/tmp --set=bt_multi ...

results in writing the analysis files on F</var/tmp/index> and
F</var/tmp/MULTI-bt_multi.bkt>

This version of B<backtest_multi.pl> will attempt to create
the specified output-directory if it does not already exist.


=item --options=<key>=<value>

A configuration option (typically given in the options file) in the
form of a key=value pair. For example,

   --option=DB::Text::format=0

sets the format used to parse markets via the DB::Text module to 0.




=back

=head2 Examples

=over 4

=item  [ VALIDATE THIS EXAMPLE ]

   $  ./backtest_multi.pl ../Listes/fr/CAC40 ../BackTest/HCB.txt \
   --output-dir=../BackTest/ --set=HCB --full


=item  [ VALIDATE THIS EXAMPLE ]

   $  ./backtest_multi.pl -start=2010-08-01 --end=2012-08-01 \
   --output-directory /var/tmp --broker=InteractiveBrokers \
   --iv=500000 --timeframe=day \
   ./bt_multi_codes.txt --set=bt_multi_codes \
   ./bt_multi_sys.txt

=back

=head2 Example of system description

  SY:TFS 50 7 | CS:SY:TFS 50 | CS:Stop:Fixed 6 \
  | MM:VAR 10 2 | MM:PositionSizeLimit 100


=head2 Example of multiple trading system F<system file> file

   $   cat bt_multi_3_sys.txt
   SY:G \
    { S:G:CrossOverUp   \
       { I:SMA 20 {I:Prices CLOSE} } { I:SMA 60 {I:Prices CLOSE} } } \
    { S:G:CrossOverDown \
       { I:SMA 20 {I:Prices CLOSE} } { I:SMA 60 {I:Prices CLOSE} } } \
    | CS:OppositeSignal \
    | MM:Portfolio::FixedFractional 15
   
   SY:TFS | CS:SY:TFS | CS:Stop:Fixed 4 | MM:VAR
   
   SY:TFS 50 7 | CS:SY:TFS 50 | CS:Stop:Fixed 6 \
   | MM:VAR 10 2 | MM:PositionSizeLimit 100

=cut

# Gestion des options
my ($full, $nb_item, $start, $end, $timeframe, $max_loaded_items) =
   (0,     0,        '',     '',   'day',      -1);
my $man = 0;
my @options;
our $verbose = 0;
our $debug = 0;

my ($outputdir, $broker, $set) = 
   ('',         '',      '');
$outputdir = GT::Conf::get("BackTest::Directory") || '';
my $initial_value = 0;

GetOptions('full!'              => \$full,
           'nb-item=i'          => \$nb_item,
           "start=s"            => \$start,
           "end=s"              => \$end,
           "max-loaded-items=s" => \$max_loaded_items,
           "timeframe=s"        => \$timeframe,
           'broker=s'           => \$broker,
           'set=s'              => \$set,
           "option=s"           => \@options,
           'verbose+'           => \$verbose,
           'od|output-directory=s' => \$outputdir,
           'iv|initial_value=s'    => \$initial_value,
           "help|man|?!"           => \$man,
          );

if ( $#ARGV <= -1 ) {
  pod2usage( -verbose => 3);
  exit 0;
}

if ( $verbose >= 3 ) {
    $verbose = 0;
    ++$debug;
}

pod2usage( -verbose => 2 ) if ($man);

foreach (@options) {
    my ($key, $value) = split (/=/, $_);
    GT::Conf::set($key, $value);
}

# Checks
if (! -d $outputdir) {
    unless ( mkdir "$outputdir", 0744 ) {
        die "$prog_name: error: The directory '$outputdir' does not exist!\n";
    }
    else {
        warn "$prog_name: info: created output dir '$outputdir'\n"
         if $verbose || $debug;
    }
}

# Verify dates and adjust to timeframe, comment out if not desired
$timeframe = GT::DateTime::name_to_timeframe($timeframe);
check_dates($timeframe, $start, $end);

# Create all the framework
my $list = GT::List->new;
my $file = shift;
if ( ! $file ) {
    die "$prog_name: error: require symbol list filename\n"
     .  "$prog_name [ options ] <market file> <system file>\n"
     .  "where <market file> is list of stock symbols\n"
     .  "      <system file> is list of signal-systems to evaluate\n"
     .  "      <system file> can be one or more file names and stdin\n";
}

if (! -e $file) {
    die "$prog_name: error: File $file does not exist.\n";
}
$list->load($file);

# Create the Portfoliomanager
my $pf_manager = GT::PortfolioManager->new;

# Build the list of systems to test
my @desc_systems = <>;
my @sys_manager = {};
my @brokers = ();
my $cnt = 0;

push @brokers, $broker; # the same broker for all systems
my $buf = '';
foreach my $line (@desc_systems) {
    chomp($line);

    next if ($line =~ /^\s*#|^$/);  # remove comment and blank lines

    my $sys_manager = GT::SystemManager->new;

    if ( $line =~ /\\$/ ) { # detect and remove \
        $line =~ s/\\//;      # remove \
        $buf .= $line;        # save line
        next;                 # get next line
    }
    else {
        $line = $buf . $line; # collect complete line into $line
        $buf = '';            # reset line buffer
    }

    # squeeze out extra spaces
    $line =~ tr/ \t/ \t/s;     # squeeze out multiple adjacent whitespaces

    # Aliases
    if ($line !~ /\|/) {
      my $alias = resolve_alias($line);
      die "$prog_name: error: Alias unknown '$alias'" unless $alias;
      $sys_manager->set_alias_name($line);
      $line = $alias;
    }

    $pf_manager->setup_from_name($line);
    $sys_manager->setup_from_name($line);
    $sys_manager->finalize;

    $sys_manager[$cnt] = $sys_manager;

    $cnt++;
}

if ( $initial_value == 0 ) {
    if ( GT::Conf::get( 'Backtest::initial_value' ) ) {
        $initial_value = GT::Conf::get('Backtest::initial_value');
    } else {
        $initial_value = 10000;
    }
}

my $def_rule = create_standard_object("MoneyManagement::Basic");
$pf_manager->default_money_management_rule($def_rule);

$pf_manager->finalize;

my @codes;
for (my $d = 0; $d < $list->count; $d++) {
  push @codes, $list->get($d);
}

my $db = create_db_object();

# Precalc the intervals
my $longest_first = 0;
my $longest_last = 0;
my $longest_code = 0;
my @calcs = ();

foreach my $i ( 0..$#codes ) {

    my ($calc, $first, $last) = find_calculator($db, $codes[$i],
     $timeframe, $full, $start, $end, $nb_item, $max_loaded_items);

    $calcs[$i] = $calc;

    # Set this code as reference if possible
    if ( ($last - $first) > ($longest_last - $longest_first) ) {
        $longest_last = $last;
        $longest_first = $first;
        $longest_code = $i;
    }

    foreach my $j ( 0..$#sys_manager ) {
        $sys_manager[$j]->precalculate_interval($calc, $first, $last);
    }

# this appears to be diagnostic like output
print STDERR $calc->code() . " --> " . $first . " / " . $last . "\n"
 if $verbose;

}

# this appears to be diagnostic like output
if ( $verbose ) {
    print STDERR " --> LONG-CODE:  " . $calcs[$longest_code]->code() . "\n";
    print STDERR " --> LONG-FIRST: " . $longest_first . "\n";
    print STDERR " --> LONG-LAST:  " . $longest_last . "\n";

    print STDERR " --> LONG-FIRST: "
     . $calcs[$longest_code]->prices->at($longest_first)->[$DATE] . "\n";
    print STDERR " --> LONG-LAST:  "
     . $calcs[$longest_code]->prices->at($longest_last)->[$DATE] . "\n";
}

# Now the hard part...
my $analysis = backtest_multi( $pf_manager, \@sys_manager,
                               \@brokers, \@calcs,
                               $longest_first, $longest_last,
                               $longest_code, $initial_value );

# Print the analysis
GT::Report::Portfolio($analysis->{'portfolio'}, 1);
print "## Global analysis (initial value of portfolio: $initial_value)\n";

print "##\n## Analysis of codes @codes using Multiple System Specs\n";

print "##\n## Individual positions vary as determined by market and systems analyzed\n";

# foreach ( @sys_manager ) {
#     my $sysspec = join " | ", $_->get_name, $pf_manager->get_name;
#     print STDERR "sysspec1\n:$sysspec:\n" if $verbose > 3;
#     $sysspec =~ s/([^\s])(\|)/$1 $2/g;
#     print STDERR "sysspec2\n:$sysspec:\n" if $verbose > 3;
#     $sysspec =~ s/(\|)([^\s])/$1 $2/g;
#     print STDERR "sysspec3\n:$sysspec:\n" if $verbose > 3;
#     $sysspec =~ s/([^\s}])(\})/$1 $2/g;
#     print STDERR "sysspec3a\n:$sysspec:\n" if $verbose > 3;
#     $sysspec =~ tr/ \t/ \t/s;
#     print "##\n## Analysis of codes @codes using System Spec\n";
#         # formatted $systyp $sysdesc
#         my $save_line_breaks = $:;
#         $: = "|}\n";
#         my $format =
#     '
#     ^<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<~~
#     ';
#         formline($format, $sysspec);
#         my $ftxt = $^A;
#         $^A = "";
#         $: = $save_line_breaks;
#         $ftxt =~ s/\n\n/\n/g;
#         print "$ftxt\n";
# }

GT::Report::PortfolioAnalysis($analysis->{'real'}, 1);
print "\n";

$db->disconnect;

if ($set) {
    my $bkt_spool = GT::BackTest::Spool->new($outputdir);
    my $stats = [ $analysis->{'real'}{'std_performance'},
                  $analysis->{'real'}{'performance'},
                  $analysis->{'real'}{'max_draw_down'},
                  $analysis->{'real'}{'std_buyandhold'},
                  $analysis->{'real'}{'buyandhold'}
                ];

    delete $analysis->{'portfolio'}->{objects};

    print STDERR $set . " --> " . $file . "\n"
     if $verbose;

    $bkt_spool->update_index();
    $bkt_spool->add_alias_name($set."-".$file, $set);
    $bkt_spool->add_results($set."-".$file, "MULTI", $stats,
                            $analysis->{'portfolio'}, $set);
    $bkt_spool->sync();
}


