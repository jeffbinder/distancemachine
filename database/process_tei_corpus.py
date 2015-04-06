# Strips out XML tags and date information, so that a TEI-encoded corpus can
# be loaded with load_corpus_counts.py.  Specify input and output directories
# and the XSLT file (e.g. tei-to-text.xsl from here:
# https://github.com/TEIC/Stylesheets/tree/master/txt
# on the command line.  You must also have the Saxon XML converter installed.

import codecs
import os
import sys
import lxml.etree as ET

try:
    indir = str(sys.argv[1])
    outdir = str(sys.argv[2])
    xslt_file = str(sys.argv[3])
except IndexError:
    print 'Specify the input and output directories and the XSLT file.'
    exit()

NS = {'tei': 'http://www.tei-c.org/ns/1.0'}

completed_files = [s.split('_')[1] for s in os.listdir(outdir)]

for filename in os.listdir(indir):
    if filename == 'extras' or filename in completed_files:
        continue
    infile = os.path.join(indir, filename)
    tree = ET.parse(infile)
    date = tree.find('.//tei:edition/tei:date', namespaces=NS)
    text = tree.find('.//tei:text', namespaces=NS)
    print filename, date.text
    outfile = os.path.join(outdir, date.text + '_' + filename)
    os.system('saxon {0} {1} >{2}'.format(infile, xslt_file, outfile))
