import MySQLdb

start_year = 1800
end_year = 2020

db = MySQLdb.connect(user='words', db='wordusage')
c = db.cursor()

def get_usage_periods(word, region):
    word = word.lower()
    
    c.execute('''
        SELECT periods
        FROM usage_periods
        WHERE word = %s AND region = %s
        ''', (word, region))
    
    row = c.fetchone()
    if row:
        periods, = row
        period_list = [[None if year == '' else int(year)
                        for year in period.split('-')]
                       for period in periods.split(';')]
    else:
        period_list = []
    
    return period_list
                                    
def classes_for_period(y1, y2, prefix):
    if y1 < start_year:
        y1 = start_year
    if y1 > y2:
        return []
    classes = []
    if y1 == start_year and y2 / 100 > start_year / 100:
        y1 = y1 / 100 * 100
    y = y1
    while y <= y2:
        if y % 100 == 0 and y2 - y >= 99:
            classes.append(prefix + str(y / 100) + 'xx')
            y += 100
        elif y % 50 == 0 and y2 - y >= 49:
            cent = y / 100
            if y == cent * 100:
                classes.append(prefix + str(cent) + 'lx')
            else:
                classes.append(prefix + str(cent) + 'rx')
            y += 50
        elif y % 10 == 0 and y2 - y >= 9:
            classes.append(prefix + str(y / 10) + 'x')
            y += 10
        elif y % 5 == 0 and y2 - y >= 4:
            dec = y / 10
            if y == dec * 10:
                classes.append(prefix + str(dec) + 'l')
            else:
                classes.append(prefix + str(dec) + 'r')
            y += 5
        else:
            classes.append(prefix + str(y))
            y += 1
    return classes
    
def classes_for_word(word, region):
    classes = []
    ranges = get_usage_periods(word, region)
    nranges = len(ranges)
    if nranges:
        classes += classes_for_period(start_year, ranges[0][0] - 1, 'n')
        end = ranges[0][1] or end_year
        for i in xrange(1, nranges):
            start = ranges[i-1][1]
            end = ranges[i][0]
            # mid = int(math.ceil((start + end) * 0.5))
            # classes += classes_for_period(start + 1, mid - 1, 'o')
            # classes += classes_for_period(mid, end - 1, 'n')
            classes += classes_for_period(start + 1, end - 1, 'l')
        classes += classes_for_period((ranges[-1][1] or end_year) + 1, end_year, 'o')
    return ''.join(classes)
    
    
c.execute('TRUNCATE TABLE word_classes')
try:
    c.execute('ALTER TABLE word_classes DROP INDEX idx_word_classes')
except:
    pass

c.execute('''
    SELECT word, region
    FROM usage_periods
    ''')
rows = c.fetchall()
nrows = len(rows)
for i, (word, region) in enumerate(rows):
    if i % 10000 == 0:
        print i, '/', nrows
        db.commit()
    classes = classes_for_word(word, region)
    if classes:
        c.execute('''
            INSERT INTO word_classes (word, region, classes)
            VALUES (%s, %s, %s)
            ''', (word.lower(), region, classes))     
db.commit()
    
print 'Creating index...'
c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 4')
c.execute('CREATE INDEX idx_word_classes ON word_classes (word) USING HASH')
db.commit()
