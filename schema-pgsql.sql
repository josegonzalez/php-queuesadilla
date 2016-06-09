-- Converted by db_converter
START TRANSACTION;
SET standard_conforming_strings=off;
SET escape_string_warning=off;
SET CONSTRAINTS ALL DEFERRED;
-- Drop indexes --
DROP INDEX IF EXISTS "queue";
-- END Drop indexes --

DROP TABLE IF EXISTS "jobs";
CREATE TABLE "jobs" (
    "id" mediumint(20) NOT NULL,
    "queue" char(32) NOT NULL DEFAULT 'default',
    "data" text NOT NULL,
    "priority" integer NOT NULL DEFAULT '0',
    "expires_at" timestamp with time zone DEFAULT NULL,
    "delay_until" timestamp with time zone DEFAULT NULL,
    "locked" int4 NOT NULL DEFAULT '0',
    PRIMARY KEY ("id")
);


-- Post-data save --
COMMIT;
START TRANSACTION;

-- Typecasts --
ALTER TABLE "jobs" ALTER COLUMN "locked" DROP DEFAULT;
ALTER TABLE "jobs" ALTER COLUMN "locked" TYPE boolean USING CAST("locked" as boolean);
ALTER TABLE "jobs" ALTER COLUMN "locked" SET DEFAULT FALSE;

-- Foreign keys --

-- Sequences --


-- Indexes --
CREATE INDEX "queue" ON jobs ("queue","locked");

COMMIT;
