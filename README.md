The Distance Machine
===============

[The Distance Machine](http://distancemachine.org) is a Web-based tool that finds words in a text that were uncommon in a given year.  This Git repository includes both the source code for this Web application (written in an Ajaxy way with JavaScript and PHP) and some scripts that were used to create the data underlying it.  The tool relies on a statistical model for determining when words come into or go out of common usage, and this model may be useful for other applications.

The Distance Machine is under the MIT license.  This package incorporates [D3 3.4.8](http://d3js.org/), [jQuery 1.10.2](http://jquery.com), [jQuery UI 1.10.4](http://jqueryui.com), and the jQuery [cookie](https://github.com/carhartl/jquery-cookie) and [watermark](https://code.google.com/p/jquery-watermark/) modules.  D3 is under the BSD license; the other packages are all available under the MIT license.

# The Word Count Data

In order to determine when words come in and go out of common usage, this tool needs information about how frequently each word in the language was used in each year.  The live version of the program does this using data based on Google Books.

The script load_googlebooks_data.py is used to load the 1-grams data from Google (available [here](http://storage.googleapis.com/books/ngrams/books/datasetsv2.html)) into a MySQL database.  It is designed to work with the 2012 version of the 1-grams and total_counts files.  This script might also be useful if you are attempting to load the data into MySQL for other applications.  For this script to work, you will need to set up a MySQL database using "usage.sql", and then modify the code so that it has the correct MySQL connection information.  You may also need to modify the script so that it knows where to look for the .gz files (by default, it looks for them in a directory called "googlebooks-datasets").

You do not necessarily need to use the data from Google Books; the rest of the system will work with any data in the appropriate format.  However, it should be noted that, for the purpose of finding anachronistic usages, it is more important to have a very large corpus than to have a very accurate one.  Smaller corpora tend to produce spurious results with regard to uncommon words.

In some versions of MySQL, it is necessary to mess around with the text encoding and collation settings to ensure that accented characters are handled correctly.  If things are working right, you will get different results for these two queries:

   SELECT * FROM count WHERE word = 'tree';

   SELECT * FROM count WHERE word = 'trée';

# The Usage Period Model

Based on the yearly word counts, we need to determine the periods in which each word was in use.  This is done using a two-state hidden Markov model, which we compute using the Viterbi algorithm.  This happens in the script compute_usage_periods.py, which goes through every single word in the word count data set, computes the model, and deposits the results in the usage_periods table.  For each word, this table will contain a string representing the periods in which that word was in common use, separated by semicolons (e.g. "1810-1840;1952-").  By default, this script will only look at data going back to 1750, because before then the number of books printed is so small that the statistics are unstable.  You can change this by modifying the source code.

Since this script can take a _very_ long time to run on an ordinary computer (on the order of weeks, even on a high-end workstation), I have included the results in the file usage_periods.txt.gz.  This file includes the usage periods for every word in the US and UK English data sets from Google in a format that can be loaded directly into the usage_periods table in MySQL.

If you just want to work with the usage period algorithm, you can stop here; however, if you want to get the Distance Machine Web application working, you will need to do a few more things.

# The Processed Data

Further processing is needed to get the data into an efficient format for the Web interface to work with.  This is done with the compute_classes.py script, which converts the usage periods from a human-readable format (e.g. "1851-1910") to a format from which the status of a word in any given year can be determined with a regular expression search (in that case, "n18lxn1850o1911o1912o1913o1914o191ro192xo193xo194xo19rx").  Each 5-character sequence in this string represents a period in which a word is either more common later (indicated with first character "n" for "new"), more common earlier ("o" for "old"), or more common both earlier and later ("l" for "lapsed").  The remaining four characters represent either a year, a decade ("192x"), a half-decade ("192l" for 1920-1924 or "192r" for 1925-1929), a century ("18xx"), or a half-century ("18lx" for 1800-1849 or "18rx" for 1850-1899).  The reason for this complex representation is that the JavaScript frontend needs to figure out which words to highlight near-instantaneously in order for the slider to be responsive.  Inelegant as this format is, it proved to be far more efficient than any alternative I could devise.

Finally, the application uses a cache of the data for a list of very common words, which is generated with the create_cache.py script.  The resulting file (named "CACHE") needs to be stored on the server in a location accessible by the Web server.  If you are using data for a language other than English, you should alter this script to use a different list of words.

# WordNet

The Web application also uses a copy of [WordNet](http://wordnet.princeton.edu) to provide definitions of words.  You can use [these scripts](https://github.com/gnugeek/wordnet-mysql) to load the WordNet data into MySQL.

# The Web Application

In order to get the Web application running, you will need to load the MySQL data as described above, and then you will need to set up a few things on the server.  The application needs two directories in which to store the texts that users upload (a temporary storage location for unsaved texts and a permanent one for saved texts); it also needs access to the CACHE file and a lockfile to prevent operations from interfering with one another.  You will need to modify config.php to specify the MySQL credentials and the locations of these resources.  If you are using a different data set from the default one (e.g. different time period limits or different languages), you should also modify config.js and config.php accordingly.  You will, finally, need to set up URL aliases as indicated in the htaccess file and create a cronjob to remove unused texts (see the "crontab" file in the database directory).

The admin_tools directory contains some scripts that are useful in monitoring the usage of the site.  If you want them to work, you will need to alter connection.php with the correct MySQL credentials.  These scripts will need to be run under a user account that has access to the directories where texts are stored.
