import math
import numpy
import re
import scipy.stats

corpora = ('us', 'gb')

# import matplotlib.pyplot as plt

import MySQLdb

start_year = 1750
end_year = 2009

db = MySQLdb.connect(user='words', db='wordusage')
c = db.cursor()

# Get the total usage data from the database.
totals = {}
for corpus in corpora:
    c.execute('''
        SELECT year, ntokens
        FROM total
        WHERE corpus = %s
        ''', (corpus,))
    totals[corpus] = dict(c.fetchall())

# This function computes the usage periods for a given word using the
# Viterbi algorithm, based on the data taken from MySQL.
def compute_usage_periods(word, corpus, plot=False):
        
    # Get the usage data from the database.
    c.execute('''
        SELECT year, ntokens
        FROM count
        WHERE word = %s
            AND corpus = %s
        ''', (word, corpus))
    counts = dict(c.fetchall())
    years = counts.keys()
    if not years:
        return []
    
    min_year = min(years)
    if min_year < start_year:
        min_year = start_year
    max_year = max(years)
    nyears = max_year - min_year + 1
    if nyears <= 0:
        return []
    
    corpus_totals = totals[corpus]

    # Figure out how the states will be defined.  State 0 assumes mean
    # 0; state 1 assumes mean = to the average frequency in all years.
    max_frequency = 0.0
    total_frequency = 0.0
    for y in xrange(min_year, max_year + 1):
        if y in corpus_totals:
            freq = float(counts.get(y, 0.0)) / float(corpus_totals[y])
            if freq > max_frequency:
                max_frequency = freq
            total_frequency += freq
    mean_frequency = total_frequency * 1.0 / (end_year - start_year + 1)
            
    probability_increment = mean_frequency
    transition_cost_coef = 1
    
    # This could be used to compute the same model with more than two states.
    # State n would assume a mean equal to n * the average freq. in all years.
    # This formula computes an upper bound on the number of states used in the
    # optimal state sequence.
    #k = int(math.ceil(max_frequency / probability_increment))

    # Default behavior: 2 states only.
    k = 2

    transition_cost_mult = transition_cost_coef * math.log(nyears)
    def transition_cost(i, j):
        return abs(j - i) * transition_cost_mult
            
    probabilities = [probability_increment * x for x in xrange(0, k)]
    def logpdf(state, nusages, total_words):
        if total_words == 0:
            x = 0.0
        else:
            x = float(nusages) / float(total_words)
        return scipy.stats.norm.logpdf(x, probabilities[state], probability_increment)
    
    # Perform the Viterbi algorithm.
    C = [0] + [float("inf")] * (k - 1)
    for t in xrange(nyears):
        Cprime = [None] * k
        qprime = [None] * k
        for i in xrange(k):
            qprime[i] = [None] * (t + 1)
        for j in xrange(k):
            costs = [C[ell] + transition_cost(ell, j) for ell in xrange(k)]
            ell = numpy.argmin(costs)
            logprob = logpdf(j, counts.get(min_year + t, 0.0),
                             corpus_totals.get(min_year + t, 0.0))
            Cprime[j] = costs[ell] - logprob
            for i in xrange(t):
                qprime[j][i] = q[ell][i]
            qprime[j][t] = j
        C = Cprime
        q = qprime
    
    j = numpy.argmin(C)
    q = q[j]
    
    # Extract the usage periods.
    ranges = []
    range_start = None
    for t in xrange(nyears):
        if range_start:
            if q[t] == 0:
                range_end = min_year + t - 1
                ranges.append((range_start, range_end))
                range_start = None
        else:
            if q[t] > 0:
                range_start = min_year + t
    if range_start:
        ranges.append((range_start, None))

    # This can be used to plot the results using MatPlotLib.
    # x = []
    # y1 = []
    # y2 = []
    # for year in xrange(min_year, max_year + 1):
    #     x.append(year)
    #     if year in corpus_totals:
    #         y1.append(float(counts.get(year, 0.0)) / float(corpus_totals[year]))
    #     else:
    #         y1.append(0.0)
    #         y2.append(probabilities[q[year - min_year]])
    # plt.plot(x, y1, '-', linewidth=2)
    # plt.plot(x, y2, '-', linewidth=2)
    # plt.show()
    
    return ranges, mean_frequency
    

print 'Computing usage periods...'

try:
    c.execute('ALTER TABLE usage_periods DROP INDEX idx_usage_periods')
except:
    pass

c.execute('''
    SELECT DISTINCT word
    FROM count
    ''')
rows = c.fetchall()
nrows = len(rows)
for i, (word,) in enumerate(rows):
    if i % 100 == 0:
        print i, '/', nrows
        db.commit()
    for corpus in corpora:
        periods, mean = compute_usage_periods(word, corpus)
        periods_string = ';'.join('{0}-{1}'.format(a, b or '') for (a, b) in periods)
        if periods_string:
            c.execute('''
                INSERT INTO usage_periods (word, corpus, periods, mean_frequency)
                VALUES (%s, %s, %s, %s)
                ''', (word.lower(), corpus, periods_string, mean))     
db.commit()
    
print 'Creating index...'
c.execute('SET myisam_sort_buffer_size = 1024 * 1024 * 1024 * 4')
c.execute('CREATE INDEX idx_usage_periods ON usage_periods (word) USING HASH')
db.commit()
