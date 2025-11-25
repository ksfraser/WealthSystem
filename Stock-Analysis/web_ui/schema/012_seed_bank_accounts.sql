-- v1.2: Pre-populate Canadian banks
INSERT IGNORE INTO bank_accounts (bank_name, account_number, account_nickname, account_type, currency) VALUES
('Royal Bank of Canada', 'SAMPLE-001', 'RBC Sample Account', 'Investment Account', 'CAD'),
('TD Canada Trust', 'SAMPLE-002', 'TD Sample Account', 'Investment Account', 'CAD'),
('Bank of Nova Scotia', 'SAMPLE-003', 'Scotia Sample Account', 'Investment Account', 'CAD'),
('Bank of Montreal', 'SAMPLE-004', 'BMO Sample Account', 'Investment Account', 'CAD'),
('Canadian Imperial Bank of Commerce', 'SAMPLE-005', 'CIBC Sample Account', 'Investment Account', 'CAD'),
('National Bank of Canada', 'SAMPLE-006', 'NBC Sample Account', 'Investment Account', 'CAD'),
('Desjardins Group', 'SAMPLE-007', 'Desjardins Sample Account', 'Investment Account', 'CAD'),
('HSBC Bank Canada', 'SAMPLE-008', 'HSBC Sample Account', 'Investment Account', 'CAD'),
('Laurentian Bank', 'SAMPLE-009', 'Laurentian Sample Account', 'Investment Account', 'CAD'),
('Canadian Western Bank', 'SAMPLE-010', 'CWB Sample Account', 'Investment Account', 'CAD'),
('Other', 'SAMPLE-999', 'Other Bank Sample', 'Investment Account', 'CAD');
