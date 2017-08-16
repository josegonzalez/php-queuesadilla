SET standard_conforming_strings = 'off';
SET backslash_quote = 'on';

DROP TABLE IF EXISTS jobs;
CREATE TABLE jobs (
  id SERIAL PRIMARY KEY,
  queue char(32) NOT NULL DEFAULT 'default',
  data text NOT NULL,
  priority smallint NOT NULL DEFAULT '0',
  expires_at timestamp DEFAULT NULL,
  delay_until timestamp DEFAULT NULL,
  locked smallint NOT NULL DEFAULT '0',
  attempts smallint DEFAULT '0',
  status varchar(50) NOT NULL DEFAULT 'new',
  executed_date timestamp DEFAULT NULL
);
CREATE INDEX queue ON JOBS (queue, locked);
