-- Database containing historical word usage data (like that used by the Google
-- ngrams viewer).

CREATE DATABASE wordusage CHARACTER SET 'utf8' COLLATE 'utf8_bin';
USE wordusage;

CREATE TABLE count (    
    corpus ENUM('us', 'gb', 'eng', 'fic', 'eebotcp1') NOT NULL,
    
    word VARCHAR(63) NOT NULL,
    --pos VARCHAR(15) NOT NULL,
    year SMALLINT NOT NULL,
    ntokens INT UNSIGNED NOT NULL--,
    --nbooks MEDIUMINT NOT NULL
) ENGINE=MyISAM;

CREATE TABLE total (
    corpus ENUM('us', 'gb', 'eng', 'fic', 'eebotcp1') NOT NULL,
    
    year SMALLINT NOT NULL,
    ntokens BIGINT NOT NULL,
    npages BIGINT NOT NULL,
    nbooks BIGINT NOT NULL,
    
    PRIMARY KEY (year, corpus)
);

CREATE TABLE usage_periods (
    corpus ENUM('us', 'gb', 'eng', 'fic', 'eebotcp1') NOT NULL,
    word VARCHAR(63) NOT NULL,
    periods VARCHAR(255) NOT NULL,
    mean_frequency FLOAT NOT NULL
) ENGINE=MyISAM;

CREATE TABLE word_classes (
    corpus ENUM('us', 'gb', 'eng', 'fic', 'eebotcp1') NOT NULL,
    word VARCHAR(63) NOT NULL,
    classes VARCHAR(1024) NOT NULL
) ENGINE=MyISAM;

-- This is used by the frontend to keep track of currently running and completed tasks.
CREATE TABLE task (
    id VARCHAR(63) NOT NULL PRIMARY KEY,
    title TEXT,
    corpus ENUM('us', 'gb', 'eng', 'fic', 'eebotcp1'),
    total_characters BIGINT,
    characters_completed BIGINT,
    words_completed BIGINT,
    lines_completed BIGINT,
    words_marked BIGINT,
    cache_hits BIGINT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME,
    status ENUM('created', 'running', 'killed', 'aborted', 'completed', 'saved', 'deleted') DEFAULT 'created',
    uploader INT UNSIGNED
);

CREATE INDEX idx_task_status ON task (status);

CREATE TABLE dict (
  dict VARCHAR(15) NOT NULL,
  id INT(11) DEFAULT NULL,
  headword VARCHAR(255) NOT NULL,
  entry TEXT COLLATE 'utf8_bin',
  pos VARCHAR(7),
  PRIMARY KEY (dict, headword)
);

CREATE TABLE dict_usage_notes (
  dict VARCHAR(15) NOT NULL,
  headword VARCHAR(255) NOT NULL,
  note_class ENUM('v', 'o') NOT NULL, 
  note VARCHAR(63) NOT NULL,
  KEY (headword)
);

CREATE TABLE dict_etymology (
  dict VARCHAR(15) NOT NULL,
  headword VARCHAR(255) NOT NULL,
  language VARCHAR(15) NOT NULL,
  KEY (headword)
);

CREATE TABLE dict_index (
  dict VARCHAR(15) NOT NULL,
  headword VARCHAR(255) NOT NULL,
  lemma VARCHAR(255) NOT NULL,
  count MEDIUMINT UNSIGNED NOT NULL,
  KEY (headword),
  KEY (lemma)
);

CREATE TABLE word_lookup_log (
  time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  word VARCHAR(255) NOT NULL,
  KEY (time)
);

