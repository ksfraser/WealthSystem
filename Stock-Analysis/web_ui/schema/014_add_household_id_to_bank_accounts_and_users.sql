-- v1.1: Add household_id to bank_accounts and users
ALTER TABLE bank_accounts ADD COLUMN household_id INT NULL AFTER id;
ALTER TABLE bank_accounts ADD CONSTRAINT fk_bank_accounts_household FOREIGN KEY (household_id) REFERENCES households(id);

ALTER TABLE users ADD COLUMN household_id INT NULL AFTER id;
ALTER TABLE users ADD CONSTRAINT fk_users_household FOREIGN KEY (household_id) REFERENCES households(id);
