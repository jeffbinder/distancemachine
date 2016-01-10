CREATE TABLE total_count AS
SELECT corpus, word, sum(ntokens)
FROM count
GROUP BY corpus, word;

ALTER TABLE total_count
ADD INDEX idx_total_count (corpus, word);
