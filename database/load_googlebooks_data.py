# Loads Google Books 1grams data from the .gz files available here:
#   http://storage.googleapis.com/books/ngrams/books/datasetsv2.html
# into a MySQL database.  You will need to create the database using the the schema in
# usage.sql and insert the appropriate credentials into the "MySQLdb.connect" line of this
# script.  The script will automatically download and install the 2012 version
# of the data.
#
# This version only loads 1grams.
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

c.execute('TRUNCATE TABLE count')
c.execute('TRUNCATE TABLE total')
try:
    c.execute('ALTER TABLE count DROP INDEX idx_count_word')
except:
    pass
db.commit()

for region in ('us', 'gb'):
    for part in '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o other p punctuation q r s t u v w x y z'.split(' '):
        print region, part
        filename = 'googlebooks-eng-{0}-all-1gram-20120701-{1}.gz'.format(region, part)
        url = 'http://storage.googleapis.com/books/ngrams/books/' + filename
        os.system('mkfifo /tmp/input.dat; '
                + 'chmod 666 /tmp/input.dat; '
                + 'curl ' + url + ' | gunzip ' # | egrep "^[a-zA-Z\'&-]+[\\t_]"
                    + '| sed -E \'s/(.)(_([A-Z]+))?\t/\\1\t\\3\t/\' '
                    + '> /tmp/input.dat &'
                + 'mysql --local_infile=1 -u words -e "LOAD DATA LOCAL INFILE \'/tmp/input.dat\' '
                + 'INTO TABLE count FIELDS ESCAPED BY \'\' '
                + '(word, pos, year, ntokens, nbooks) '
                + 'SET region = \'{0}\';" wordusage; '.format(region)
                + 'rm /tmp/input.dat; ')
        db.commit()

    print region, 'totals'
    url = 'http://storage.googleapis.com/books/ngrams/books/googlebooks-eng-{0}-all-totalcounts-20120701.txt'.format(region)
    os.system('curl ' + url + ' >/tmp/totals.txt')
    f = open('/tmp/totals.txt', 'r')
    data = f.read()
    data = data[2:-1]
    for line in data.split('\t'):
        year, ntokens, npages, nbooks = line.split(',')
        year = int(year)
        ntokens = int(ntokens)
        npages = int(npages)
        nbooks = int(nbooks)
        c.execute('''
            INSERT INTO total (year, region, ntokens, npages, nbooks)
            VALUES (%s, %s, %s, %s, %s)
            ''', (year, region, ntokens, npages, nbooks))
    db.commit()

print 'Creating index...'
c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 100')
c.execute('CREATE INDEX idx_count_word ON count (word) USING HASH')
db.commit()
