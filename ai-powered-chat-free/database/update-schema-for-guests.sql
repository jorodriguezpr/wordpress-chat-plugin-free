-- Update the apc_conversations table to support negative user IDs for guests
-- Run this SQL query in phpMyAdmin or MySQL command line

ALTER TABLE wp_apc_conversations MODIFY user_id BIGINT NOT NULL;

-- Verify the change
DESCRIBE wp_apc_conversations;
