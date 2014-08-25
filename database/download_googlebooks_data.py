# Downloads the Google 1grams data for the selected corpora to the current directory.

corpora = ('us', 'gb')

import os

for corpus in corpora:

    if corpus in ('us', 'gb'):
        google_corpus_name = 'eng-' + corpus
    elif corpus == 'fic':
        google_corpus_name = 'eng-fiction'
    else:
        google_corpus_name = corpus

    for part in '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o other p punctuation q r s t u v w x y z'.split(' '):
        print 'Downloading', corpus, part
        filename = 'googlebooks-{0}-all-1gram-20120701-{1}.gz'.format(google_corpus_name, part)
        url = 'http://storage.googleapis.com/books/ngrams/books/' + filename
        os.system('curl ' + url + ' >' + filename)
        
    print 'Downloading', corpus, 'totals'
    filename = 'googlebooks-{0}-all-totalcounts-20120701.txt'.format(google_corpus_name)
    url = 'http://storage.googleapis.com/books/ngrams/books/' + filename
    os.system('curl ' + url + ' >' + filename)
