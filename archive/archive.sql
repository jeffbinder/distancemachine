-- To use the Distance Machine's archival features, add this to the database
-- and create the archive using create_archive.php or create_archive_tei.php.
CREATE TABLE text (
    corpus ENUM('us', 'gb', 'eng', 'wright', 'eebotcp1'),
    uri VARCHAR(255) NOT NULL,
    title TEXT,
    author TEXT,
    pub_year SMALLINT,
    text MEDIUMTEXT,
    word_count MEDIUMINT NOT NULL,
    PRIMARY KEY (corpus, uri),
    FULLTEXT idx (text)
) ENGINE=MyISAM;

CREATE TABLE word_search_log (
  time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  query VARCHAR(1000) NOT NULL,
  KEY (time)
);
