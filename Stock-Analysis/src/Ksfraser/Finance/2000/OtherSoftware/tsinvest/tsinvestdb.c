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

tsinvestdb.c is a C source code template for programs that manipulate
the tsinvest(1) time series database(s). It contains the hash
algorithm look up tables for expedient development of specialized
database systems.

The time series database file structure is a text file consisting of
records, in temporal order, one record per time series sample of each
equity.  Blank records are ignored, and comment records are signified
by a '#' character as the first non white space character in the
record. Each data record represents an equity transaction, consisting
of a minium of three fields, separated by white space. The fields are
ordered by time stamp, equity ticker identifier, and closing price,
for example:

    1      ABC     333.6
    2      DEF     7.2
    3      GHI     335.9

PROGRAM ARCHITECTURE

I) Data architecture:

    A) Each equity has a data structure, of type HASH, that contains
    the statistical information on the fluctuations in the equity's
    value. The structure is maintained in a hash table, referenced by
    the equity's name. (The elements HASH *previous and HASH *next are
    used for the maintenence of the hash lookup table, and the element
    char *hash_data references the equity's name.)

        1) Additionally, each HASH structure has a linked list
        construct:

            a) A singly linked list of all HASH structures, ie., a
            list of all equities. This list is referenced by the
            global HASH *decision_list.  This list is constructed
            using the element HASH *next_decision.

II) Data architecture manipulation functions:

    A) The hash lookup operations are performed by the functions,
    static int hash_init (), static int hash_insert (), and static
    HASH *hash_find ().

III) Program description:

    A) The function main serves to read the input file, and dispatch
    to appropriate data handling functions, in the following order:

        1) handle any command line arguments.

        2) Open the input file.

        3) For each record in the input file:

            a) Parse the record using the function int strtoken (),
            checking that the record has exactly 3 fields, and if it
            does, then check that the equity's value represented by
            this record is greater than zero. (Note: many of the data
            handling functions will exhibit numerical exceptions with
            data values of zero, or less-this is the only protection
            from numerical exceptions in the program.)

            b) Lookup the equity's HASH structure represented by the
            record using the function HASH *get_stock (). (The
            function get_stock () will return the structure if it
            exists, or create one if it doesn't.)

            c) Save the data contained in the record in the equity's
            HASH structure.

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
$Id: tsinvestdb.c,v 1.7 2006/01/07 10:05:09 john Exp $
$Log: tsinvestdb.c,v $
Revision 1.7  2006/01/07 10:05:09  john
Initial revision


*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>

#ifndef __STDC__

#include <malloc.h>

#endif

static char rcsid[] = "$Id: tsinvestdb.c,v 1.7 2006/01/07 10:05:09 john Exp $"; /* program version */
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
    "Manipulate a tsinvest time series database\n",
    "Usage: tsinvestdb [-v] [filename]\n",
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
    struct hash_struct *previous, /* reference to next element in hash's doubly, circular linked list */
                       *next, /* reference to previous element in hash's doubly, circular linked list */
                       *next_decision, /* reference to next element in qsortlist ()'s sort of the decision criteria list */
                       *next_investment, /* reference to next element in invested list */
                       *next_print; /* reference to next element in print list */
    char *hash_data;  /* stock tick identifier, which is the hash key element */
    int transitions, /* number of transitions for the stock */
        current_updated, /* updated in current interval flag, 0 = no, 1 = yes */
        last_updated; /* updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
    double currentvalue, /* current value of stock */
           lastvalue; /* last value of stock */
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

HASH *decision_list = (HASH *) 0; /* list of decision criteria for investment in a stock */

#define PUSHDECISION(x) (x)->next_decision = decision_list; decision_list = (x) /* method to push a HASH element on the decision criteria list, this pushes a HASH struct for sorting by qsortlist () */

#ifdef __STDC__

static void print_message (int retval); /* print any error messages */
static HASH *get_stock (HASHTABLE *stock_table, void *name); /* get a stock from the hash table */
static int update_stocks (HASH *stock_list); /* update the list of stocks */
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
static int tsgetopt (int argc, char *argv[], const char *opts); /* get an option letter from argument vector */

#else

static void print_message (); /* print any error messages */
static HASH *get_stock (); /* get a stock from the hash table */
static int update_stocks (); /* update the list of stocks */
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
static int tsgetopt (); /* get an option letter from argument vector */

#endif

static HASHTABLE text_table = {2729, (HASH *) 0, text_mkhash, text_cmphash, text_rmhash, hash_text}; /* declare the hash table descriptor for text keys */

static int stocks = 0; /* the number of stocks encoutered in the input file */

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
    char buffer[BUFLEN], /* i/o buffer */
         parsebuffer[BUFLEN], /* parsed i/o buffer */
         *token[BUFLEN / 2], /* reference to tokens in parsed i/o buffer */
         time_stamp[BUFLEN]; /* last time stamp, from the first column of the input file */

    int retval = NOERROR, /* return value, assume no error */
        fields, /* number of fields in a record */
        period_counter = 0, /* period counter, incremented when time_stamp changes, which changes when the first field of the records change */
        c; /* command line switch */

    double currentvalue; /* current value of stock */

    FILE *infile = stdin; /* reference to input file */

    HASH *stock;  /* reference to hash table stock element */

    time_stamp[0] = '\0'; /* initialize the last time stamp, from the first column of the input file */

    while ((c = tsgetopt (argc, argv, "hv")) != EOF) /* for each command line switch */
    {

        switch (c) /* which switch? */
        {

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

                                    if ((stock = get_stock (&text_table, token[1])) != (HASH *) 0) /* get the stock from the hash table */
                                    {

                                        if (strcmp (time_stamp, token[0]) != 0) /* no, new time stamp, from the first column of the input file? */
                                        {

                                            if ((retval = update_stocks (decision_list)) != NOERROR) /* update the list of stocks */
                                            {
                                                break; /* couldn't update the list of stocks, exit */
                                            }

                                            (void) strcpy (time_stamp, token[0]); /* save the new time stamp, from the first column of the input file */
                                            period_counter ++; /* time_stamp changed, increment the period counter */
                                        }

                                        stock->lastvalue = stock->currentvalue;
                                        stock->currentvalue = currentvalue; /* save current value of the stock */
                                        stock->current_updated = 1; /* set the updated in current interval flag, 0 = no, 1 = yes */
                                        stock->transitions ++; /* increment the number of transitions for the stock */
                                        (void) printf ("%s", buffer); /* its a record with a hash table element, print the record to stdout */
                                    }

                                    else
                                    {
                                        retval = hash_error; /* couldn't get the stock from the hash table, set the error */
                                       (void) fprintf (stderr, "%s", buffer); /* its a record that created a hash table error, print the record to stderr */
                                        break; /* couldn't get the stock from the hash table, stop reading records */
                                    }

                                }

                                else
                                {
                                    (void) fprintf (stderr, "%s", buffer); /* its a record with a value of zero, or less, print the record to stderr */
                                }

                            }

                            else
                            {
                                (void) fprintf (stderr, "%s", buffer); /* its a record with more than three fields, print the record to stderr */
                            }

                        }

                        else
                        {
                            (void) printf ("%s", buffer); /* its a comment, print the comment to stdout */
                        }

                    }

                }

                if (retval == NOERROR) /* any errors? */
                {

                    if (period_counter != 0) /* any records? */
                    {
                        retval = update_stocks (decision_list); /* update the list of stocks */
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

Get a stock from the hash table.

static HASH *get_stock (HASHTABLE *stock_table, void *name);

I) Get the HASH structure for the stock identified by name, (the HASH
structure element hash_data references the stock's name.)

    A) If the stock's HASH structure exists in the hash lookup table,
    return it.

    B) If the stock's HASH structure does not exist, create it and
    return it.

Returns a reference to the stock HASH element, zero on error.

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

Update the list of stocks.

static int update_stocks (HASH *stock_list);

I) Scan the list of all available equities, calculating the statistics
for the history of each equity:

    A) "Walk through" the linked list of decision criteria, maintained
    by the HASH element HASH *next_decision. The head of the list is
    referenced by the global HASH *decision_list. This list contains
    the list of all HASH structures for all stocks "seen" by the input
    file.

        1) Update each stock's last value, which is used to calculate
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

Returns NOERROR if successful, EALLOC on memory allocation error in
int statistics ().

*/

#ifdef __STDC__

static int update_stocks (HASH *stock_list)

#else

static int update_stocks (stock_list)
HASH *stock_list;

#endif

{
    int retval = NOERROR; /* return value, assume no error */

    HASH *stock; /* reference to HASH struct */

    stock = stock_list; /* reference the first element in the decision criteria list for investment in a stock */

    while (stock != (HASH *) 0 && retval == NOERROR) /* count the elements in the list of decision criteria for investment in a stock, but not greater than n */
    {

        if (stock->current_updated == 0) /* updated in current interval flag, 0 = no, 1 = yes? */
        {
            stock->last_updated = 0; /* no, reset the updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
        }

        else
        {
            stock->last_updated ++; /* yes, increment the updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
        }

        stock->current_updated = 0; /* reset the updated in current interval flag, 0 = no, 1 = yes */
        stock = stock->next_decision; /* reference the next element in the decision criteria list */
    }

    return (retval); /* return any errors */
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

    if ((obj_ref = (char *) malloc ((1 + strlen (data)) * sizeof (char))) != (char *) 0) /* allocate the hash table element key's data area */
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
            element->transitions = 0; /* initialize the number of transitions for the stock */
            element->current_updated = 0; /* initialize the updated in current interval flag, 0 = no, 1 = yes */
            element->last_updated = 0; /* initialize the updated in last interval flag, 0 = no, else contains count of consecutive updated intervals */
            element->currentvalue = (double) 0.0; /* initialize the current value of stock */
            element->lastvalue = (double) 0.0; /* initialize the last value of the stock */
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
    free (element->hash_data); /* free the key's data area allocated in text_mkhash () */
    free (element); /* free the hash table element allocated in text_mkhash () */
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
