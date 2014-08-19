# Downloads the Google 1grams data for the selected corpora to the current directory.

import os

for corpus in ('us', 'gb'):
    for part in '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o other p punctuation q r s t u v w x y z'.split(' '):
        print 'Downloading', corpus, part
        filename = 'googlebooks-eng-{0}-all-1gram-20120701-{1}.gz'.format(corpus, part)
        url = 'http://storage.googleapis.com/books/ngrams/books/' + filename
        os.system('curl ' + url + ' >' + filename)
        
    print 'Downloading', corpus, 'totals'
    filename = 'googlebooks-eng-{0}-all-totalcounts-20120701.txt'.format(corpus)
    url = 'http://storage.googleapis.com/books/ngrams/books/' + filename
    os.system('curl ' + url + ' >totals-' + corpus + '.txt')
