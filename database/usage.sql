-- Database containing historical word usage data (like that used by the Google
-- ngrams viewer).

CREATE DATABASE wordusage;
USE wordusage;

CREATE TABLE count (    
    word VARCHAR(63) NOT NULL,
    pos VARCHAR(15) NOT NULL,
    
    year SMALLINT NOT NULL,
    region ENUM('us', 'gb') NOT NULL,
    
    ntokens BIGINT NOT NULL,
    nbooks MEDIUMINT NOT NULL
) ENGINE=MyISAM;

CREATE TABLE total (
    year SMALLINT NOT NULL,
    region ENUM('us', 'gb'),
    
    ntokens BIGINT NOT NULL,
    npages BIGINT NOT NULL,
    nbooks BIGINT NOT NULL,
    
    PRIMARY KEY (year, region)
);

CREATE TABLE usage_periods (
    word VARCHAR(63) NOT NULL,
    region ENUM('us', 'gb') NOT NULL,
    periods VARCHAR(255) NOT NULL
) ENGINE=MyISAM;

CREATE TABLE word_classes (
    word VARCHAR(63) NOT NULL,
    region ENUM('us', 'gb') NOT NULL,
    classes VARCHAR(1024) NOT NULL
) ENGINE=MyISAM;

-- This is used by the frontend to keep track of currently running and completed tasks.
CREATE TABLE task (
    id VARCHAR(63) NOT NULL PRIMARY KEY,
    title TEXT,
    region ENUM('us', 'gb'),
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
