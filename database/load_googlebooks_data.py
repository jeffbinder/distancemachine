# Loads Google Books 1grams data from the .gz files available here:
#   http://storage.googleapis.com/books/ngrams/books/datasetsv2.html
# into a MySQL database.  You will need to create the database using the the schema in
# usage.sql, insert the appropriate credentials into the "MySQLdb.connect" line of this
# script, and replace the path "../googlebooks-datasets/" with the path to the directory
# where the .gz files are located.  This script is designed to work with the 2012 version
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

re_filename = re.compile(r'googlebooks-(?P<language>[^-]*)-(?P<region>[^-]*)'
                       + r'-(?P<subset>[^-]*)-(?P<datatype>[^-]*)-(?P<date>[^-]*)'
                       + r'(-(?P<part>[^-]*))?.(gz|txt)')

for filename in os.listdir('googlebooks-datasets/'):
    print filename
    m = re_filename.match(filename)
    if m:
        region = m.group('region')
        datatype = m.group('datatype')
        part = m.group('part')
        if part == 'pos':
            continue
        filename = 'googlebooks-datasets/' + filename
        if datatype == '1gram':
            os.system('mkfifo /tmp/input.dat; '
                    + 'chmod 666 /tmp/input.dat; '
                    + 'gunzip -c "{0}" '.format(filename) # | egrep "^[a-zA-Z\'&-]+[\\t_]"
                        + '| sed -E \'s/(.)(_([A-Z]*))?\t/\\1\t\\3\t/\' '
                        + '> /tmp/input.dat &'
                    + 'mysql --local_infile=1 -u words -e "LOAD DATA LOCAL INFILE \'/tmp/input.dat\' '
                    + 'INTO TABLE count FIELDS ESCAPED BY \'\' '
                    + '(word, pos, year, ntokens, nbooks) '
                    + 'SET region = \'{0}\';" wordusage; '.format(region)
                    + 'rm /tmp/input.dat; ')
        elif datatype == 'totalcounts':
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
                    INSERT INTO total (year, region, ntokens, npages, nbooks)
                    VALUES (%s, %s, %s, %s, %s)
                    ''', (year, region, ntokens, npages, nbooks))
        else:
            print 'Don\'t know what to do with file', filename
    else:
        print 'Don\'t know what to do with file', filename
    db.commit()

print 'Creating index...'
c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 100')
c.execute('CREATE INDEX idx_count_word ON count (word) USING HASH')
db.commit()
