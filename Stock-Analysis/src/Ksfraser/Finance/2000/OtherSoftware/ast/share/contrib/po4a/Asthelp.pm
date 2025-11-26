# Locale::Po4a::Recordjar -- Convert record jar files to PO file, for translation.
# $Id: Recordjar.pm,v 1.0 2007/01/27 mwilliamson Exp $
#
# This program is free software; you may redistribute it and/or modify it
# under the terms of GPL (see COPYING).
#
############################################################################
# Modules and declarations
############################################################################
use Locale::Po4a::TransTractor qw(process new);
use Locale::Po4a::Common;

package Locale::Po4a::Asthelp;

use 5.006;
use strict;
use warnings;

require Exporter;

use vars qw(@ISA @EXPORT $AUTOLOAD);
@ISA = qw(Locale::Po4a::TransTractor);
@EXPORT = qw();

my $debug=0;

sub initialize {}


sub parse
{
    my $self=shift;
    my ($line,$ref);
    my $par;

    LINE:
    ($line,$ref)=$self->shiftline();

    while (defined($line))
    {
        chomp($line);

        # Remove the identifier (if present)
        my $text = $line;
        $text =~ s/^\@[a-z]*//g;
        $text =~ s/^[ \t]+//g;
        $text =~ s/[ \t]+$//g;

        # Translate the string it
        my $par = $self->translate($text, $ref);
        # Escape the \n characters
        $par =~ s/\n/\\n/g;
        # Replace the translatd text in the orginal
        $text =~ s/\(/\\\(/g;
        $text =~ s/\)/\\\)/g;
        $line =~ s/$text/$par/;
        # Now push the result
        $self->pushline($line."\n");

        # Reinit the loop
        ($line,$ref)=$self->shiftline();
    }
}

##############################################################################
# Module return value and documentation
##############################################################################

1;
__END__
