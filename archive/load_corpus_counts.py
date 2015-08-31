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

c.execute('''
CREATE TEMPORARY TABLE count_tmp (    
    corpus VARCHAR(31) NOT NULL,
    
    word VARCHAR(63) NOT NULL,
    year SMALLINT NOT NULL,
    ntokens INT UNSIGNED NOT NULL,
    
    PRIMARY KEY (year, word)
) ENGINE=MyISAM;
''')

c.execute('''
CREATE TEMPORARY TABLE total_tmp (
    corpus VARCHAR(31) NOT NULL,
    
    year SMALLINT NOT NULL,
    ntokens BIGINT NOT NULL,
    npages BIGINT NOT NULL,
    nbooks BIGINT NOT NULL,
    
    PRIMARY KEY (year)
);
''')

i = 0
files = os.listdir('.')
nfiles = len(files)
for filename in files:
    print filename
    if i % 10000 == 0:
        print i, '/', nfiles
    i += 1
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
        c.execute('''
SELECT ntokens
FROM count_tmp
WHERE word = %s AND year = %s
''',
                  (tok, year))
        if c.rowcount:
            ntokens_old = c.fetchone()[0]
            c.execute('''
UPDATE count_tmp
SET ntokens = %s
WHERE word = %s AND year = %s
''',
                      (ntokens_old + 1, tok, year))
        else:
            c.execute('''
INSERT INTO count_tmp (corpus, ntokens, word, year)
VALUES (%s, 1, %s, %s)
''',
                      (corpus, tok, year))

    c.execute('''
SELECT ntokens, nbooks
FROM total_tmp
WHERE year = %s
''',
                  (year,))
    if c.rowcount:
        ntokens_old, nbooks_old = c.fetchone()
        c.execute('''
UPDATE total_tmp
        SET ntokens = %s, nbooks = %s
WHERE year = %s
''',
                      (ntokens_old + ntokens, nbooks_old + 1, year))
    else:
            c.execute('''
INSERT INTO total_tmp (corpus, ntokens, npages, nbooks, year)
VALUES (%s, %s, 0, 1, %s)
''',
                      (corpus, ntokens, year))

    f.close()

c.execute('''
INSERT INTO count
SELECT * FROM count_tmp
''')

c.execute('''
INSERT INTO total
SELECT * FROM total_tmp
''')

db.commit()
