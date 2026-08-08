-- ============================================================
--  Pharmacy Billing System — Clean Database Setup
--  Created: 2026-06-09
-- ============================================================

CREATE DATABASE IF NOT EXISTS A 
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE Pharmacy_Billing;

-- ============================================================
--  DROP TABLES (safe re-run order — children first)
-- ============================================================
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS invoice_counter;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS patients;

-- ============================================================
--  PATIENTS
-- ============================================================
CREATE TABLE patients (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    phone       VARCHAR(20),
    address     TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  DOCTORS
-- ============================================================
CREATE TABLE doctors (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    specialization  VARCHAR(100),
    phone           VARCHAR(20),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  PRODUCTS (medicines)
-- ============================================================
CREATE TABLE products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200) NOT NULL,
    salt            VARCHAR(200),
    batch_no        VARCHAR(50),
    expiry_date     VARCHAR(10),       -- stored as MM/YYYY
    rate            DECIMAL(10,2) DEFAULT 0.00,
    gst_percent     DECIMAL(5,2)  DEFAULT 0.00,
    mrp             DECIMAL(10,2) DEFAULT 0.00,
    stock           INT           DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SALES (invoice header)
-- ============================================================
CREATE TABLE sales (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no          VARCHAR(50) UNIQUE NOT NULL,
    invoice_date        DATE NOT NULL,
    patient_name        VARCHAR(150),
    patient_phone       VARCHAR(20),
    patient_address     TEXT,
    doctor_name         VARCHAR(150),
    reminder            TINYINT(1) DEFAULT 0,
    lab_charge          DECIMAL(10,2) DEFAULT 0.00,
    doctor_charge       DECIMAL(10,2) DEFAULT 0.00,
    injection_charge    DECIMAL(10,2) DEFAULT 0.00,
    nursing_charge      DECIMAL(10,2) DEFAULT 0.00,
    total_discount      DECIMAL(10,2) DEFAULT 0.00,
    product_subtotal    DECIMAL(10,2) DEFAULT 0.00,
    additional_charges  DECIMAL(10,2) DEFAULT 0.00,
    rounding_off        DECIMAL(5,2)  DEFAULT 0.00,
    grand_total         DECIMAL(10,2) DEFAULT 0.00,
    status              ENUM('saved','submitted') DEFAULT 'saved',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SALE ITEMS (invoice line-items)
-- ============================================================
CREATE TABLE sale_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sale_id         INT NOT NULL,
    product_id      INT NULL,
    product_name    VARCHAR(200),
    salt            VARCHAR(200),
    batch_no        VARCHAR(50),
    expiry_date     VARCHAR(10),
    qty             INT           DEFAULT 1,
    rate            DECIMAL(10,2) DEFAULT 0.00,
    gst_percent     DECIMAL(5,2)  DEFAULT 0.00,
    mrp             DECIMAL(10,2) DEFAULT 0.00,
    disc_percent    DECIMAL(5,2)  DEFAULT 0.00,
    total           DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT fk_sale_items_sale    FOREIGN KEY (sale_id)    REFERENCES sales(id)    ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  INVOICE COUNTER
-- ============================================================
CREATE TABLE invoice_counter (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    last_number INT  DEFAULT 0,
    last_date   DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO invoice_counter (last_number, last_date) VALUES (0, CURDATE());

-- ============================================================
--  INDEXES
-- ============================================================
CREATE INDEX idx_product_name      ON products(name);
CREATE INDEX idx_product_salt      ON products(salt);
CREATE INDEX idx_sales_invoice_no  ON sales(invoice_no);
CREATE INDEX idx_sales_date        ON sales(invoice_date);
CREATE INDEX idx_sale_items_sale   ON sale_items(sale_id);

-- ============================================================
--  SAMPLE DATA — PATIENTS  (5 rows)
-- ============================================================
INSERT INTO patients (name, phone, address) VALUES
('Ravi Kumar',        '9876543210', '12, Anna Nagar, Chennai'),
('Meena Devi',        '9843012345', '45, Gandhipuram, Coimbatore'),
('Arjun Murugan',     '9944556677', '7, West Masi Street, Madurai'),
('Lakshmi Sundaram',  '9600112233', '23, Krishnapuram, Tirunelveli'),
('Senthil Raj',       '9791234567', '89, Nehru Street, Salem');

-- ============================================================
--  SAMPLE DATA — DOCTORS  (6 rows)
-- ============================================================
INSERT INTO doctors (name, specialization, phone) VALUES
('Dr. Rajesh Kumar',  'General Physician',  '9442011111'),
('Dr. Priya Sharma',  'Pediatrician',       '9442022222'),
('Dr. Arun Mehta',    'Cardiologist',       '9442033333'),
('Dr. Sunita Patel',  'Dermatologist',      '9442044444'),
('Dr. Vikram Singh',  'Orthopedic',         '9442055555'),
('Dr. Deepa Nair',    'Gynecologist',       '9442066666');

-- ============================================================
--  SAMPLE DATA — PRODUCTS  (20 medicines)
-- ============================================================
INSERT INTO products (name, salt, batch_no, expiry_date, rate, gst_percent, mrp, stock) VALUES
('Paracetamol 500mg',      'Paracetamol',                   'BT2024A', '12/2027',  2.50, 5.00,  2.80, 500),
('Crocin 500mg',           'Paracetamol',                   'CR2024B', '06/2027',  5.00, 5.00,  5.50, 300),
('Amoxicillin 250mg',      'Amoxicillin',                   'AM2024C', '03/2027',  8.00,12.00,  9.50, 200),
('Augmentin 625mg',        'Amoxicillin + Clavulanic Acid', 'AU2024D', '09/2027', 45.00,12.00, 52.00, 150),
('Azithromycin 250mg',     'Azithromycin',                  'AZ2024E', '12/2027', 18.00,12.00, 20.00, 100),
('Metformin 500mg',        'Metformin Hydrochloride',       'MF2024F', '06/2028',  3.50, 5.00,  4.00, 400),
('Glucophage 500mg',       'Metformin',                     'GL2024G', '03/2028',  6.00, 5.00,  6.50, 250),
('Atorvastatin 10mg',      'Atorvastatin',                  'AT2024H', '12/2027', 12.00,12.00, 14.00, 180),
('Omeprazole 20mg',        'Omeprazole',                    'OM2024J', '06/2027',  4.50, 5.00,  5.00, 350),
('Pantoprazole 40mg',      'Pantoprazole',                  'PA2024K', '12/2027',  6.00, 5.00,  6.80, 280),
('Cetirizine 10mg',        'Cetirizine',                    'CE2024L', '03/2028',  3.00, 5.00,  3.50, 600),
('Allegra 120mg',          'Fexofenadine',                  'AL2024M', '06/2028', 14.00,12.00, 16.00, 200),
('Ibuprofen 400mg',        'Ibuprofen',                     'IB2024N', '09/2027',  5.00, 5.00,  5.80, 400),
('Dolo 650mg',             'Paracetamol',                   'DL2024P', '03/2028',  7.00, 5.00,  8.00, 350),
('Vitamin D3 60000IU',     'Cholecalciferol',               'VD2024Q', '06/2028', 35.00,12.00, 40.00, 150),
('Calcium Carbonate 500mg','Calcium Carbonate',             'CC2024R', '12/2027',  4.00, 5.00,  4.50, 500),
('Insulin Regular 40IU',   'Human Insulin',                 'IN2024S', '06/2027',180.00, 5.00,195.00,  80),
('Amlodipine 5mg',         'Amlodipine Besylate',           'AM2024T', '03/2028',  7.50,12.00,  8.50, 220),
('Losartan 50mg',          'Losartan Potassium',            'LO2024U', '09/2027',  9.00,12.00, 10.50, 190),
('Levothyroxine 50mcg',    'Levothyroxine Sodium',          'LV2024V', '12/2028', 22.00, 5.00, 24.00, 140);

-- ============================================================
--  SAMPLE DATA — SALES + SALE_ITEMS  (3 invoices)
-- ============================================================

-- Invoice 1
INSERT INTO sales (invoice_no, invoice_date, patient_name, patient_phone, patient_address,
    doctor_name, reminder, lab_charge, doctor_charge, injection_charge, nursing_charge,
    total_discount, product_subtotal, additional_charges, rounding_off, grand_total, status)
VALUES ('SAL-20260601-001', '2026-06-01', 'Ravi Kumar', '9876543210', '12, Anna Nagar, Chennai',
    'Dr. Rajesh Kumar', 0, 0, 0, 0, 0, 0, 18.48, 0, 0.52, 19, 'submitted');

INSERT INTO sale_items (sale_id, product_id, product_name, salt, batch_no, expiry_date,
    qty, rate, gst_percent, mrp, disc_percent, total)
VALUES
(1, 1, 'Paracetamol 500mg', 'Paracetamol', 'BT2024A', '12/2027', 3, 2.50, 5.00, 2.80, 0, 8.40),
(1, 9, 'Omeprazole 20mg',   'Omeprazole',  'OM2024J', '06/2027', 2, 4.50, 5.00, 5.00, 5, 9.50);

-- Invoice 2
INSERT INTO sales (invoice_no, invoice_date, patient_name, patient_phone, patient_address,
    doctor_name, reminder, lab_charge, doctor_charge, injection_charge, nursing_charge,
    total_discount, product_subtotal, additional_charges, rounding_off, grand_total, status)
VALUES ('SAL-20260603-002', '2026-06-03', 'Meena Devi', '9843012345', '45, Gandhipuram, Coimbatore',
    'Dr. Priya Sharma', 1, 50, 200, 0, 0, 5.25, 99.75, 250, 0.25, 350, 'submitted');

INSERT INTO sale_items (sale_id, product_id, product_name, salt, batch_no, expiry_date,
    qty, rate, gst_percent, mrp, disc_percent, total)
VALUES
(2, 3, 'Amoxicillin 250mg', 'Amoxicillin', 'AM2024C', '03/2027', 6, 8.00, 12.00, 9.50, 5.00, 53.97),
(2, 5, 'Azithromycin 250mg','Azithromycin','AZ2024E', '12/2027', 2,18.00, 12.00,20.00, 0,    40.00),
(2,11, 'Cetirizine 10mg',   'Cetirizine',  'CE2024L', '03/2028', 1, 3.00,  5.00, 3.50, 5.00,  3.33);

-- Invoice 3
INSERT INTO sales (invoice_no, invoice_date, patient_name, patient_phone, patient_address,
    doctor_name, reminder, lab_charge, doctor_charge, injection_charge, nursing_charge,
    total_discount, product_subtotal, additional_charges, rounding_off, grand_total, status)
VALUES ('SAL-20260608-003', '2026-06-08', 'Arjun Murugan', '9944556677', '7, West Masi Street, Madurai',
    'Dr. Arun Mehta', 0, 0, 300, 80, 0, 0, 175.50, 380, 0.50, 556, 'saved');

INSERT INTO sale_items (sale_id, product_id, product_name, salt, batch_no, expiry_date,
    qty, rate, gst_percent, mrp, disc_percent, total)
VALUES
(3, 8,  'Atorvastatin 10mg', 'Atorvastatin',          'AT2024H', '12/2027', 30,12.00,12.00,14.00, 0, 420.00),
(3, 18, 'Amlodipine 5mg',    'Amlodipine Besylate',   'AM2024T', '03/2028', 30, 7.50,12.00, 8.50, 0, 255.00),
(3, 10, 'Pantoprazole 40mg', 'Pantoprazole',           'PA2024K', '12/2027', 30, 6.00, 5.00, 6.80, 0, 204.00);

-- Update invoice counter to match last invoice
UPDATE invoice_counter SET last_number = 3, last_date = '2026-06-08' WHERE id = 1;
