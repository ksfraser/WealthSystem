#!/bin/sh
# -*-Perl-*-
# ***************************************************************************
# *   Copyright (C) 2005 by Michael Williamson                              *
# *   mswilliamson@uwaterloo.ca                                             *
# *                                                                         *
# *   This program is free software; you can redistribute it and/or modify  *
# *   it under the terms of the GNU General Public License as published by  *
# *   the Free Software Foundation; either version 2 of the License, or     *
# *   (at your option) any later version.                                   *
# *                                                                         *
# *   This program is distributed in the hope that it will be useful,       *
# *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
# *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
# *   GNU General Public License for more details.                          *
# *                                                                         *
# *   You should have received a copy of the GNU General Public License     *
# *   along with this program; if not, write to the                         *
# *   Free Software Foundation, Inc.,                                       *
# *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
# ***************************************************************************
exec perl -x $0 ${1+"$@"}
exit 1;
#!perl

# This program will install AST, trying to determine
# the settings apache uses.
use strict;

sub ast_die
{
  my($msg) = @_;
  print "---------------------------------------------------------------------\n";
  print "-                           ERROR OCCURED                           -\n";
  print "---------------------------------------------------------------------\n";
  print "- Installation failed with error:                                   -\n";
  print "- $msg\n";
  print "-                                                                   -\n";
  print "- Please report this bug to:                                        -\n";
  print '-       ast-users@lists.sourceforge.net                             -'."\n";
  print '- or    http://sourceforge.net/tracker/?group_id=148045&atid=770268 -'."\n";
  print "---------------------------------------------------------------------\n";
  die;
}

sub find_line
{
  my $line = shift;
  my @files = @_;
  foreach my $file (@files)
  {
    my($res) = `grep -E $line $file 2>/dev/null`; chomp($res);
    return $res if( $res ne "" );
  }

  ast_die "Could not find $line in (" . join(", ",@files) .")";
}

sub dirname
{
  my $file = $_[0];
  return substr($file,0,rindex($file,'/'));
}

sub hasprog
{
  my $prog = $_[0];
  my $test = `which $prog 2>/dev/null`; chomp($test);
  return 0 if $test eq "";
  return 1;
}

sub install_pkg
{
  my( $pkgman, $pkg ) = @_;
  print "Installing package $pkg\n$pkgman $pkg\n";
  ast_die "$pkg installation failed." if( system( "$pkgman $pkg" ) != 0 );
}

die "You must be root to run this script.\n" if getpwuid($>) ne "root";

# Determine installation type.
if( -d 'src' )
{
  if( ! -f 'ast' )
  {
    if( ! -f 'src/src/ast' )
    {
      my $answer = "";
      while( $answer ne "yes" and $answer ne "no" )
      {
        print "You have not yet compiled AST. Would you like me to do this for you? [yes/no]:  ";
        $answer = <STDIN>; chomp($answer);
        print "Please answer yes or no.\n\n" if( $answer ne "yes" and $answer ne "no" );
      }
      exit if( $answer eq "no" );
  
      # They must have answered yes, compile it!

      # Look for any other packages missing.
      my $answer = "";
      while( $answer ne "yes" and $answer ne "no" )
      {
          print "Would you like me to scan your system for missing packages? [yes/no]:  ";
          $answer = <STDIN>; chomp($answer);
          print "Please answer yes or no.\n\n" if( $answer ne "yes" and $answer ne "no" );
      }

      if( $answer eq "yes" )
      {
        # Find out which package manager they have:
        # 1. yum (invoke yum install)
        # 2. yast (invoke yast -i)
        # 3. apt-get (invoke apt-get install)

        my $pkgman;
	      my $ubuntu, my $bsd;
        if( &hasprog("yum") == 1 )
        {
          $pkgman = "yum install";
        }elsif( &hasprog("yast") == 1 )
        {
          $pkgman = "yast -i";
        }elsif( &hasprog( "apt-get" ) == 1 )
        {
          $pkgman = "apt-get install";
          # If using apt-get, then always assume ubuntu, debian, etc.
	        $ubuntu = 1;
        }elsif( &hasprog( "pkg_add" ) == 1 )
        {
          $pkgman = "pkg_add -r";
          $bsd = 1;
        }

        if( $pkgman eq "" )
        {
          print "\nI don't understand your package manager. Sorry!\n\n";
        }else
        {
          # Check for curl-devel
          if( &hasprog("curl-config") == 0 )
          {
            if( $ubuntu != 0 )
            {
              &install_pkg( $pkgman, "libcurl3-dev" );
            }elsif( $bsd != 0 )
            {
              &install_pkg( $pkgman, "curl" );
            }else
            {
              &install_pkg( $pkgman, "curl-devel" );
            }
          }

          # libxml2-devel
          if( &hasprog("xml2-config") == 0 )
          {
            if( $ubuntu != 0 )
            {
              &install_pkg( $pkgman, "libxml2-dev" );
            }elsif( $bsd != 0 )
            {
              &install_pkg( $pkgman, "libxml2" );
            }else
            {
              &install_pkg( $pkgman, "libxml2-devel" );
            }
          }
          
          # gd-devel
          if( &hasprog("gdlib-config") == 0 )
          {
            if( $ubuntu != 0 )
            {
              &install_pkg( $pkgman, "libgd2-dev" );
            }elsif( $bsd != 0 )
            {
              &install_pkg( $pkgman, "libgd" );
            }else
            {
              &install_pkg( $pkgman, "gd-devel" );
            }
          }

          # gnuplot
          if( &hasprog("gnuplot") == 0 )
          {
            &install_pkg( $pkgman, "gnuplot" );
          }

          # gnuplot
          if( &hasprog("wget") == 0 )
          {
            &install_pkg( $pkgman, "wget" );
          }
        }
      }

      # TA-lib requires libcurl, so do this after installing curl.
      # Check to make sure they have TA-lib
      if( ! -e "/usr/lib/ta-lib/libta_common_csr.a" )
      {
          # For to install it.
          my $answer = "";
          while( $answer ne "yes" and $answer ne "no" )
          {
              print "You do not have TA-lib installed. Would you like me to do this for you? [yes/no]:  ";
              $answer = <STDIN>; chomp($answer);
              print "Please answer yes or no.\n\n" if( $answer ne "yes" and $answer ne "no" );
          }
          exit if( $answer eq "no" );
      
          # Download, compile, install ta-lib.
          my $pwd = `pwd`; chomp($pwd);
          chdir('/tmp');
	  # Only download ta-lib if they haven't already, gives manual
	  # download option if the automatic download doesn't work (e.g.
	  # behind a proxy/firewall, sf.net error, etc.
	  if( not -e "ta-lib-0.2.0-src.tar.gz" )
	  {
	      if( system( "wget -c -t 3 http://easynews.dl.sourceforge.net/sourceforge/ta-lib/ta-lib-0.2.0-src.tar.gz" ) != 0 )
	      {
		  # try a mirror
		  ast_die "Failed to download ta-lib. Try downloading manually (version 0.2.0) from http://ta-lib.sf.net" if system( "wget -c -t 3 http://surfnet.dl.sourceforge.net/sourceforge/ta-lib/ta-lib-0.2.0-src.tar.gz") != 0;
	      }
	  }
          ast_die "Failed to extract ta-lib" if( system( "tar -zxf ta-lib-0.2.0-src.tar.gz" ) != 0 );
          chdir( "ta-lib/c/make/csr/linux/g++" ) or ast_die "Cannot change directory to ta-lib/c/make/csr/linux/g++";
          ast_die "Failed to build ta-lib" if( system("make") != 0 );
          ast_die "Failed to create ta-lib directories" if( system("mkdir -p /usr/include/ta-lib") != 0 );
          ast_die "Failed to create ta-lib directories" if( system("mkdir -p /usr/lib/ta-lib") != 0 );
          chdir("../../../../include") or ast_die "Cannot change directory to ../../../../include";
          ast_die "Failed to install ta-lib" if( system("cp * /usr/include/ta-lib/") != 0 );
          chdir("../lib") or ast_die "Cannot change directory to ../lib";
          ast_die "Failed to install ta-lib" if( system("cp * /usr/lib/ta-lib/") != 0 );
          chdir($pwd) or ast_die "Cannot change directory to $pwd";
      }

      # Compile AST:
      chdir('src') or ast_die "Cannot change directory to src";
      ast_die "Failed to run configure." if( system( 'CXXFLAGS="-O2 -g0 -pipe" ./configure --without-mhash' ) != 0 );
      ast_die "Failed to run make" if( system("make -j3") != 0 );
      ast_die "make did not create ast" if( ! -f 'src/ast' );
      chdir('..');
    }
    ast_die "Cannot copy ast" if( system( 'cp src/src/ast .' ) != 0 );
    ast_die "Cannot strip ast" if( system( 'strip ast' ) != 0 );
  }
}

ast_die "Cannot find ast" if( ! -f 'ast' );

my $documentroot;
my $apacheuser;
my $apachegroup;

my $apache = `which httpd 2>/dev/null`; chomp( $apache );

if( $apache eq "" )
{
  $apache = `which apache2 2>/dev/null`; chomp($apache);

  if( $apache eq "" )
  {
      # SuSE calls thier apache httpd2
      $apache = `which httpd2 2>/dev/null`; chomp($apache);
  }
}

ast_die "Could not find apache in " . $ENV{'PATH'} if $apache eq "";
print "Found apache2 installed as " . $apache . "\n";

my $conf = `$apache -V | grep SERVER_CONFIG_FILE | sed -e 's/^.*SERVER_CONFIG_FILE=\\\"\\([^\\\"]*\\)\\\"/\\1/'`; chomp($conf);

if( substr($conf,0,1) ne "/" )
{
  # if the conf path doesn't start with /, then
  # it is relative to SERVER_ROOT
  my $srvroot = `$apache -V | grep HTTPD_ROOT | sed -e 's/^.*HTTPD_ROOT=\\\"\\([^\\\"]*\\)\\\"/\\1/'`; chomp($srvroot);
  $conf = $srvroot . "/" . $conf;
}

ast_die "Cannot find configuration file." if $conf eq "";
print "Configuration file is $conf\n";

my $confdir = &dirname($conf);

my $docroot = &find_line( '^[^#]*DocumentRoot[[:space:]]', $conf, $confdir . "/*.conf", $confdir . "/vhosts.d/*.conf", $confdir . "/conf.d/*.conf", $confdir . "/sites-available/*" );
$documentroot = $docroot; $documentroot =~ s/^.*DocumentRoot[ \t\"]*//g; $documentroot =~ s/[ \t"]*$//g;
$apacheuser = &find_line( '^[^#]*User[[:space:]]', $conf, $confdir . "/*.conf", $confdir . "/vhosts.d/*.conf", $confdir . "/conf.d/*.conf", $confdir . "/sites-available/*" );
$apacheuser =~ s/^.*User[ \t\"]*//g; $apacheuser =~ s/[ \t"]*$//g;
$apachegroup = &find_line( '^[^#]*Group[[:space:]]', $conf, $confdir . "/*.conf", $confdir . "/vhosts.d/*.conf", $confdir . "/conf.d/*.conf". $confdir . "/sites-available/*" );
$apachegroup =~ s/^.*Group[ \t\"]*//g; $apachegroup =~ s/[ \t"]*$//g;

ast_die "Could not determine DocumentRoot" if $documentroot eq "";
ast_die "Could not determine User" if $apacheuser eq "";
ast_die "Could not determine Group" if $apachegroup eq "";

print 'DocumentRoot is ' . $documentroot . "\n";
print 'User is ' . $apacheuser . "\n";
print 'Group is ' . $apachegroup . "\n";

print "\nInstall AST into directory: [ast]  ";
my $installdir = <STDIN>; chomp($installdir);
$installdir = "ast" if $installdir eq "";
while( -e "$documentroot/$installdir" )
{
  print "Installation directory exists. Please choose another.\n";
  print "\nInstall AST into directory: [ast]  ";
  $installdir = <STDIN>; chomp($installdir);
  $installdir = "ast" if $installdir eq "";
}

system("clear");
print "\n------------------------------------------------------------------------\n";
print "Install Directory:         $documentroot/$installdir\n";
print "Install as User:           $apacheuser\n";
print "Install as Group:          $apachegroup\n";
print "Modify Configuration:      $conf\n";
print "------------------------------------------------------------------------\n\n\n";
my $answer = "";
while( $answer ne "yes" and $answer ne "no" )
{
  print "Are you sure you wish to continue [yes/no]:  ";
  $answer = <STDIN>; chomp($answer);
  print "Please answer yes or no.\n\n" if( $answer ne "yes" and $answer ne "no" );
}
exit if( $answer eq "no" );

my $instdir = "$documentroot/$installdir";

ast_die "Failed to create directory $instdir" if( system("mkdir -p $instdir") != 0 );
ast_die "Failed to populate $instdir" if( system("cp -R * $instdir/") != 0 );
ast_die "Failed to setup permissions in $instdir" if( system("chown $apacheuser:$apachegroup $instdir/* -R") != 0 );
ast_die "Failed to set permissions on $instdir/ast" if( system("chmod 6755 $instdir/ast") != 0 );

# AST is setup, now setup the crontab.
my $tmp = `mktemp`; chomp($tmp);
system("crontab -l > $tmp 2>/dev/null"); # Note: this can fail if the user does not have a crontab. 
ast_die "Failed to append to $tmp" if( system("echo \'*\\5 * * * * (cd $instdir ; ./ast --scheduler)\' >> $tmp") != 0 );
ast_die "Failed to set new crontab" if( system("crontab $tmp") != 0 );
unlink $tmp;

# Append to apache configuration.
ast_die "Failed to append apache config" if( system( "echo \'\' >> $conf" ) != 0 );
ast_die "Failed to append apache config" if( system( "echo \'<Directory \"$instdir\">\' >> $conf" ) != 0 );
ast_die "Failed to append apache config" if( system( "echo \'    AllowOverride All\' >> $conf" ) != 0 );
ast_die "Failed to append apache config" if( system( "echo \'    Options ExecCGI\' >> $conf" ) != 0 );
ast_die "Failed to append apache config" if( system( "echo \'    SetHandler cgi-script\' >> $conf" ) != 0 );
ast_die "Failed to append apache config" if( system( "echo \'    DirectoryIndex ast\' >> $conf" ) != 0 );
ast_die "Failed to append apache config" if( system( "echo \'</Directory>\' >> $conf" ) != 0 );

print "\n\nYou need to restart your web server. Type: \n";
print "/etc/init.d/apache2 restart\n" if( -e "/etc/init.d/apache2" );
print "/etc/init.d/apache restart\n" if( -e "/etc/init.d/apache" );
print "/etc/init.d/httpd restart\n" if( -e "/etc/init.d/httpd" );
print "/etc/rc.d/httpd restart\n" if( -e "/etc/rc.d/httpd" );
print "/etc/rc.d/apache2 restart\n" if( -e "/etc/rc.d/apache2" );
print "/etc/rc.d/apache restart\n" if( -e "/etc/rc.d/apache" );
print "\n\nYou can access your site at:\n";
print "http://localhost/$installdir/ast\n";
print "\nThank you for using AST!\n";

