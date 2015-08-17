# Pulls out some metadata from XML files.  This is useful if you want to create a plain-
# text archive based on texts that also exist in TEI format (e.g. Wright American Fiction
# or EEBO-TCP).

import codecs
import os
import sys
import lxml.etree as ET

try:
    indir = str(sys.argv[1])
    outfile = str(sys.argv[2])
except IndexError:
    print 'Specify the input directory and output file.'
    exit()

NS = {'tei': 'http://www.tei-c.org/ns/1.0'}

outfile = codecs.open(outfile, 'w', 'utf-8')

for filename in os.listdir(indir):
    if filename == 'extras':
        continue
    id = filename.split('.')[0]
    infile = os.path.join(indir, filename)
    tree = ET.parse(infile)
    date = tree.find('.//tei:edition/tei:date', namespaces=NS)
    if not date.text:
        date = tree.find('.//tei:publicationStmt/tei:date', namespaces=NS)
    title = tree.find('.//tei:titleStmt/tei:title', namespaces=NS)
    author = tree.find('.//tei:titleStmt/tei:author', namespaces=NS)
    date = date.text
    if date[0] == '[':
        date = date[1:5]
    else:
        date = date[:4]
    date = date.replace('u', '0')
    date = date.replace('-', '0')
    print filename, date
    title = title.text.replace('\n', ' ').replace('\t', ' ').strip()
    if author:
        author = author.text.replace('\n', ' ').replace('\t', ' ').strip()
    else:
        author = ''
    outfile.write('\t'.join([id, date, title, author]) + '\n')
