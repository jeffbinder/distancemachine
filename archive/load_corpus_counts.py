# Loads word count data from a full-text corpus.  This is similar to the version included
# in the database directory, except it uses the metadata file created by
# extract_metadata.py instead of finding the dates in the filenames.

import codecs
import os
import sys

import MySQLdb
db = MySQLdb.connect(user='words', db='wordusage2', charset='utf8')
c = db.cursor()

from nltk.tokenize import RegexpTokenizer
tokenizer = RegexpTokenizer(r'[\w&]([\w&\']*[\w&])?|\S|\s')

try:
    corpus = str(sys.argv[1])
    metadata_file = str(sys.argv[2])
except IndexError:
    print 'Specify the corpus name and metadata file.'
    exit()
    
years = {}
f = codecs.open(metadata_file, 'r', 'utf-8')
for row in f.readlines():
    id, year = row.strip().split('\t')[:2]
    years[id] = year

counts = {}
totals = {}

for filename in os.listdir('.'):
    print filename
    id = filename.split('.')[0]
    year = years[id]
    try:
        year = int(year)
    except ValueError:
        continue

    f = codecs.open(filename, 'r', 'utf-8')
    text = f.read()
    toks = tokenizer.tokenize(text)
    ntokens = 0
    for tok in toks:
        if tok.isspace():
            continue
        if len(tok) > 63:
            continue
        ntokens += 1
        tok = tok.replace('\\', '\\\\')
        tok = tok.lower()
        counts.setdefault(tok, {}).setdefault(year, 0)
        counts[tok][year] += 1

    if year in totals:
        ntokens_old, _, nbooks_old = totals[year]
        totals[year] = (ntokens_old + ntokens, 0, nbooks_old + 1)
    else:
        totals[year] = (ntokens, 0, 1)

f = codecs.open('/tmp/counts.txt', 'w', 'utf-8')
for tok in counts:
    for year in counts[tok]:
        f.write(u'\t'.join([unicode(x) for x in (corpus, tok, year, counts[tok][year])]) + '\n')
f.close()

f = codecs.open('/tmp/totals.txt', 'w', 'utf-8')
for year in totals:
    f.write(u'\t'.join([unicode(x) for x in (corpus, year) + totals[year]]) + '\n')
f.close()

c.execute('''
LOAD DATA INFILE '/tmp/counts.txt'
INTO TABLE count
''')

c.execute('''
LOAD DATA INFILE '/tmp/totals.txt'
INTO TABLE total
''')

db.commit()
