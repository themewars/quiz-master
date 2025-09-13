-- Quick fix for server migration issue
-- Run this SQL command on your server to rename the column

ALTER TABLE `plans` CHANGE `no_of_quiz` `no_of_exam` INT NOT NULL;
