# Loads Google Books 1grams data from the .gz files available here:
#   http://storage.googleapis.com/books/ngrams/books/datasetsv2.html
# into a MySQL database.  You will need to create the database using the the schema in
# usage.sql and insert the appropriate credentials into the "MySQLdb.connect" line of this
# script.  The script is designed to work with the 2012 version of the data, which you
# can download with the download_googlebooks_data.py script.
#
# This version only loads 1grams, and it removes part of speech tags, book counts, and
# the distinctions between different capitalizations of words.
#
# A database I created for the American and British 1grams takes up about 16GB total.
# On my system (a 2012 Mac Pro) this script took about 18 hours to run.  You will
# required about 100GB of additional disk space to consolidate the data.

corpora = ('us', 'gb')

import datetime
import gzip
import os
import re

import MySQLdb

db = MySQLdb.connect(user='words', db='wordusage', charset='utf8')
c = db.cursor()

for corpus in corpora:

    if corpus in ('us', 'gb'):
        google_corpus_name = 'eng-' + corpus
    elif corpus == 'fic':
        google_corpus_name = 'eng-fiction'
    else:
        google_corpus_name = corpus

    c.execute('''
CREATE TABLE count_tmp (    
    corpus ENUM('us', 'gb', 'eng', 'fic') NOT NULL,
    
    word VARCHAR(63) NOT NULL,
'''
    #pos VARCHAR(15) NOT NULL,
+'''
    year SMALLINT NOT NULL,
    ntokens INT UNSIGNED NOT NULL,
    nbooks MEDIUMINT NOT NULL
) ENGINE=MyISAM;
''')
        
    # To include parts of speech, replace the egrep command with:
    # sed -E \'s/(.)(_(NOUN|VERB|ADJ|ADV|PRON|DET|ADP|NUM|CONJ|PRT|\\.|X))?\t/\\1\t\\3\t/\'
    # To preserve capitalization, remove the tr command and load directly into the
    # count table rather than count_tmp.  You will need to uncomment the nbooks column
    # in usage.sql.  You can then skip the "Condensing data" step.
    for part in '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o other p punctuation q r s t u v w x y z'.split(' '):
        print 'Loading', corpus, part   
        filename = 'googlebooks-{0}-all-1gram-20120701-{1}.gz'.format(google_corpus_name, part)
        os.system('mkfifo /tmp/input.dat; '
                + 'chmod 666 /tmp/input.dat; '
                + 'cat ' + filename + ' | gunzip '
                    + '| egrep -v \'._(NOUN|VERB|ADJ|ADV|PRON|DET|ADP|NUM|CONJ|PRT|\\.|X)\t\' '
                    + '| tr \'[:upper:]\' \'[:lower:]\' > /tmp/input.dat &'
                + 'mysql --local_infile=1 -u words -e "LOAD DATA LOCAL INFILE \'/tmp/input.dat\' '
                + 'INTO TABLE count_tmp FIELDS ESCAPED BY \'\' '
                + '(word, year, ntokens, nbooks) '
                + 'SET corpus = \'{0}\';" wordusage; '.format(corpus)
                + 'rm /tmp/input.dat; ')

    print 'Loading', corpus, 'totals'
    filename = 'googlebooks-{0}-all-totalcounts-20120701.txt'.format(google_corpus_name)
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

    print 'Creating initial index...'
    c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 4')
    c.execute('CREATE INDEX idx_count_word_tmp ON count_tmp (corpus, word, year) USING BTREE')
    db.commit()

    # This is to combine entries for the same word with different capitalization.
    print 'Condensing data...'
    try:
        c.execute('ALTER TABLE count DROP INDEX idx_count_word')
    except:
        pass
    c.execute('''
INSERT INTO count
SELECT corpus, word, year, sum(ntokens)
FROM count_tmp
FORCE INDEX FOR GROUP BY (idx_count_word_tmp)
GROUP BY corpus, word, year
''')

    print 'Dropping tmp table...'
    c.execute('DROP TABLE count_tmp')
    db.commit()
    
    print 'Done processing data for', corpus

print 'Creating final index...'
c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 4')
a = datetime.datetime.now()
c.execute('CREATE INDEX idx_count_word ON count (word) USING HASH')
db.commit()
b = datetime.datetime.now()
print b - a
