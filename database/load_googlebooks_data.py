# Loads Google Books 1grams data from the .gz files available here:
#   http://storage.googleapis.com/books/ngrams/books/datasetsv2.html
# into a MySQL database.  You will need to create the database using the the schema in
# usage.sql and insert the appropriate credentials into the "MySQLdb.connect" line of this
# script.  The script will automatically download and install the 2012 version
# of the data.
#
# This version only loads 1grams, and it removes part of speech tags, book counts, and
# the distinctions between different capitalizations of words.
#
# A database I created for the American and British 1grams takes up about 37GB total.
# On my system (a 2012 MacBook Pro) this script took about 4 hours to run.  MySQL
# required an additional 70GB of scratch space to create the index.

import gzip
import os
import re

import MySQLdb

db = MySQLdb.connect(user='words', db='wordusage')
c = db.cursor()

c.execute('''
CREATE TABLE count_tmp (    
    corpus ENUM('us', 'gb') NOT NULL,
    
    word VARCHAR(63) NOT NULL,
'''
    #pos VARCHAR(15) NOT NULL,
+'''
    year SMALLINT NOT NULL,
    ntokens BIGINT NOT NULL,
    nbooks MEDIUMINT NOT NULL
) ENGINE=MyISAM;
''')

# To include parts of speech, replace the egrep command with:
# sed -E \'s/(.)(_(NOUN|VERB|ADJ|ADV|PRON|DET|ADP|NUM|CONJ|PRT|\\.|X))?\t/\\1\t\\3\t/\'
for corpus in ('us', 'gb'):
    for part in '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o other p punctuation q r s t u v w x y z'.split(' '):
        print 'Loading', corpus, part   
        filename = 'googlebooks-eng-{0}-all-1gram-20120701-{1}.gz'.format(corpus, part)
        os.system('mkfifo /tmp/input.dat; '
                + 'chmod 666 /tmp/input.dat; '
                + 'cat ' + filename + ' | gunzip '
                    + '| egrep -v \'._(NOUN|VERB|ADJ|ADV|PRON|DET|ADP|NUM|CONJ|PRT|\\.|X)\t\' '
                    + '> /tmp/input.dat &'
                + 'mysql --local_infile=1 -u words -e "LOAD DATA LOCAL INFILE \'/tmp/input.dat\' '
                + 'INTO TABLE count_tmp FIELDS ESCAPED BY \'\' '
                + '(word, year, ntokens, nbooks) '
                + 'SET corpus = \'{0}\';" wordusage; '.format(corpus)
                + 'rm /tmp/input.dat; ')

    print 'Loading', corpus, 'totals'
    filename = 'googlebooks-eng-{0}-all-totalcounts-20120701.txt'.format(corpus)
    url = 'http://storage.googleapis.com/books/ngrams/books/' + filename
    f = open(filename, 'r')
    data = f.read()
    data = data[2:-1]
    for line in data.split('\t'):
        year, ntokens, npages, nbooks = line.split(',')
        year = int(year)
        ntokens = int(ntokens)
        npages = int(npages)
        nbooks = int(nbooks)
        c.execute('''
            INSERT INTO total (year, corpus, ntokens, npages, nbooks)
            VALUES (%s, %s, %s, %s, %s)
            ''', (year, corpus, ntokens, npages, nbooks))
    db.commit()

print 'Creating index...'
c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 100')
c.execute('CREATE INDEX idx_count_word_tmp ON count_tmp (word) USING HASH')
db.commit()

# This is to combine entries for the same word with different capitalization.
print 'Condensing data...'
c.execute('''
INSERT INTO count
SELECT corpus, word, year, sum(ntokens)
FROM count_tmp
GROUP BY corpus, word, year
''')

print 'Creating final index...'
c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 100')
c.execute('CREATE INDEX idx_count_word ON count (word) USING HASH')
db.commit()

print 'Dropping tmp table...'
#c.execute('DROP TABLE count_tmp')
db.commit()
