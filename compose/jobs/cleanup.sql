-- ==============================================================================
-- phpList 3.7 housekeeping - replaces the old CLI purge commands
-- Table prefix assumed: phplist_   (adjust if different)
-- Back up / test on a copy first.
-- Run:  db --table < cleanup.sql      (prints one row count per step)
-- ==============================================================================

-- Fix every cutoff once so rows can't cross the threshold mid-script
-- (which would leave orphaned child rows behind).
SET @msg_cutoff    = DATE_SUB(NOW(), INTERVAL 10  DAY);  -- eventlog + sent messages
SET @unconf_cutoff = DATE_SUB(NOW(), INTERVAL 2   DAY);  -- unconfirmed users
SET @black_cutoff  = DATE_SUB(NOW(), INTERVAL 365 DAY);  -- blacklisted users

SELECT NOW() AS run_started, @msg_cutoff AS msg_cutoff, @unconf_cutoff AS unconfirmed_cutoff, @black_cutoff AS blacklisted_cutoff;

START TRANSACTION;  -- only protects you if the tables are InnoDB

-- 1. Event log  (was: -peventlog action=deleteall daysago=10)
DELETE FROM phplist_eventlog WHERE entered < @msg_cutoff;
SELECT 'eventlog' AS step, ROW_COUNT() AS rows_deleted;

-- 2. Sent messages  (was: -pmessages delete=sent daysago=10)
--    Children first, phplist_message last. Mirrors deleteMessage() in lib.php.
DELETE x FROM phplist_usermessage x JOIN phplist_message m ON m.id = x.messageid WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'usermessage' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_listmessage x JOIN phplist_message m ON m.id = x.messageid WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'listmessage' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_message_view x JOIN phplist_message m ON m.id = x.messageid WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'message: user_message_view' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_linktrack_uml_click x JOIN phplist_message m ON m.id = x.messageid WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'message: linktrack_uml_click' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_linktrack_ml x JOIN phplist_message m ON m.id = x.messageid WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'linktrack_ml' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_message_bounce x JOIN phplist_message m ON m.id = x.message WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'message: user_message_bounce' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_message_forward x JOIN phplist_message m ON m.id = x.message WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'message: user_message_forward' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_message_attachment x JOIN phplist_message m ON m.id = x.messageid WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'message_attachment' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_messagedata x JOIN phplist_message m ON m.id = x.id WHERE m.status = 'sent' AND m.modified < @msg_cutoff;
SELECT 'messagedata' AS step, ROW_COUNT() AS rows_deleted;

DELETE FROM phplist_message WHERE status = 'sent' AND modified < @msg_cutoff;
SELECT 'MESSAGES (sent)' AS step, ROW_COUNT() AS rows_deleted;

-- Attachment rows no longer referenced by any message (phpList itself leaves these behind).
-- NOTE: this only removes DB rows; files in your attachment repository stay on disk.
DELETE a FROM phplist_attachment a
LEFT JOIN phplist_message_attachment ma ON ma.attachmentid = a.id
WHERE ma.id IS NULL;
SELECT 'orphaned attachments' AS step, ROW_COUNT() AS rows_deleted;

-- 3 + 4. Unconfirmed users (>2 days) and blacklisted users (>365 days) in one pass
--    (was: -puser delete=allunconfirmed daysago=2  /  delete=allblacklisted daysago=365)
--    Mirrors deleteUserRecordsLeaveBlacklistRecords(): phplist_user_blacklist and
--    phplist_user_blacklist_data are deliberately NOT touched.
DELETE x FROM phplist_linktrack_uml_click x JOIN phplist_user_user u ON u.id = x.userid
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: linktrack_uml_click' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_listuser x JOIN phplist_user_user u ON u.id = x.userid
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: listuser' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_usermessage x JOIN phplist_user_user u ON u.id = x.userid
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: usermessage' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_user_attribute x JOIN phplist_user_user u ON u.id = x.userid
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: user_user_attribute' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_user_history x JOIN phplist_user_user u ON u.id = x.userid
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: user_user_history' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_message_view x JOIN phplist_user_user u ON u.id = x.userid
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: user_message_view' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_message_bounce x JOIN phplist_user_user u ON u.id = x.user
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: user_message_bounce' AS step, ROW_COUNT() AS rows_deleted;
DELETE x FROM phplist_user_message_forward x JOIN phplist_user_user u ON u.id = x.user
 WHERE (u.confirmed <> 1 AND u.modified < @unconf_cutoff) OR (u.blacklisted = 1 AND u.modified < @black_cutoff);
SELECT 'user: user_message_forward' AS step, ROW_COUNT() AS rows_deleted;

DELETE FROM phplist_user_user
 WHERE (confirmed <> 1 AND modified < @unconf_cutoff) OR (blacklisted = 1 AND modified < @black_cutoff);
SELECT 'USERS (unconfirmed + blacklisted)' AS step, ROW_COUNT() AS rows_deleted;

COMMIT;
SELECT NOW() AS run_finished;