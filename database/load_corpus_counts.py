# Loads word count data from a full-text corpus.  Files must be in the current
# directory; the corpus name must be specified on the command line.
#
# Files should be plain text, with the publication year as the first 4
# characters of the name.  To load TEI-encoded texts, use the script
# process_tei_corpus.py.

import codecs
import os
import sys

import MySQLdb
db = MySQLdb.connect(user='words', db='wordusage', charset='utf8')
c = db.cursor()

from nltk.tokenize import RegexpTokenizer
tokenizer = RegexpTokenizer(r'[\w&]([\w&\']*[\w&])?|\S|\s')

try:
    corpus = str(sys.argv[1])
except IndexError:
    print 'Specify the corpus name.'
    exit()

counts = {}
totals = {}

for filename in os.listdir('.'):
    print filename
    year = filename[:4]
    year = year.replace('u', '0')
    year = int(year)

    f = codecs.open(filename, 'r', 'utf-8')
    text = f.read()
    toks = tokenizer.tokenize(text)
    ntokens = len(toks)
    for tok in toks:
        if tok.isspace():
            continue
        if len(tok) > 63:
            continue
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
