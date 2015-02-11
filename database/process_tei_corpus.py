# Strips out XML tags and date information, so that a TEI-encoded corpus can
# be loaded with load_corpus_counts.py.  Specify input and output directories
# on the command line.

import codecs
import os
import sys
import xml.etree.ElementTree as ET

try:
    indir = str(sys.argv[1])
    outdir = str(sys.argv[2])
except IndexError:
    print 'Specify the input and output directories.'
    exit()

NS = {'tei': 'http://www.tei-c.org/ns/1.0'}

for filename in os.listdir(indir):
    if filename == 'extras':
        continue
    infile = os.path.join(indir, filename)
    tree = ET.parse(infile)
    date = tree.find('.//tei:edition/tei:date', namespaces=NS)
    text = tree.find('.//tei:text', namespaces=NS)
    print filename, date.text
    outfile = os.path.join(outdir, date.text + '_' + filename)
    f = codecs.open(outfile, 'w', 'utf-8')
    f.write(''.join(text.itertext()))
