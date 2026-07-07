-- EcoLot LK updated runnable schema
-- Based on the uploaded SQL, edited for latest workflow compatibility.
-- Main additions:
-- 1) area_collection_dates.campaign_id for campaign-wise area schedules.
-- 2) collection_routes.date_id for exact schedule/date traceability.
-- 3) indexes and uniqueness constraints for route/request and E-Lot item consistency.
-- NOTE: This script DROPS and recreates the ecolot_lk database.

DROP DATABASE IF EXISTS ecolot_lk;
CREATE DATABASE ecolot_lk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecolot_lk;

-- =========================================================
-- 01. USERS AND ROLE-BASED ACCESS
-- =========================================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM(
        'PUBLIC_USER',
        'MUNICIPAL_OFFICER',
        'COLLECTOR',
        'AUTHORIZED_RECYCLER',
        'ADMIN'
    ) NOT NULL,
    status ENUM('PENDING', 'ACTIVE', 'SUSPENDED', 'REJECTED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(150) NOT NULL,
    description TEXT,
    ip_address VARCHAR(60),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- =========================================================
-- 02. MUNICIPAL AREA + POSTAL CODE SCHEDULING
-- =========================================================

CREATE TABLE local_councils (
    council_id INT AUTO_INCREMENT PRIMARY KEY,
    council_name VARCHAR(150) NOT NULL,
    district VARCHAR(100),
    province VARCHAR(100),
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE collection_areas (
    area_id INT AUTO_INCREMENT PRIMARY KEY,
    council_id INT NOT NULL,
    area_name VARCHAR(150) NOT NULL,
    postal_code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (council_id) REFERENCES local_councils(council_id)
        ON DELETE CASCADE
);

-- Monthly campaigns are defined before area schedules because
-- area_collection_dates.campaign_id references collection_campaigns.
CREATE TABLE collection_campaigns (
    campaign_id INT AUTO_INCREMENT PRIMARY KEY,
    council_id INT NOT NULL,
    campaign_name VARCHAR(150) NOT NULL,
    campaign_month TINYINT NOT NULL,
    campaign_year YEAR NOT NULL,
    request_cutoff_date DATE NOT NULL,
    status ENUM('DRAFT', 'OPEN', 'CLOSED', 'COMPLETED', 'CANCELLED') DEFAULT 'DRAFT',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (council_id) REFERENCES local_councils(council_id)
        ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE CASCADE,
    UNIQUE (council_id, campaign_month, campaign_year),
    INDEX idx_collection_campaigns_council_status (council_id, status),
    INDEX idx_collection_campaigns_month_year (campaign_month, campaign_year)
);

CREATE TABLE area_collection_dates (
    date_id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NOT NULL,
    campaign_id INT NULL,
    collection_date DATE NOT NULL,
    max_requests INT DEFAULT 100,
    status ENUM('OPEN', 'CLOSED', 'FULL', 'CANCELLED') DEFAULT 'OPEN',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES collection_areas(area_id)
        ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES collection_campaigns(campaign_id)
        ON DELETE CASCADE,
    UNIQUE (campaign_id, area_id, collection_date),
    INDEX idx_area_collection_dates_campaign (campaign_id),
    INDEX idx_area_collection_dates_area_date (area_id, collection_date),
    INDEX idx_area_collection_dates_status (status)
);

-- =========================================================
-- 03. ROLE-SPECIFIC PROFILES
-- =========================================================

CREATE TABLE public_user_profiles (
    public_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20) NOT NULL,
    area_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES collection_areas(area_id)
        ON DELETE SET NULL
);

CREATE TABLE municipal_officer_profiles (
    officer_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    council_id INT NOT NULL,
    employee_no VARCHAR(80),
    designation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (council_id) REFERENCES local_councils(council_id)
        ON DELETE CASCADE
);

CREATE TABLE collector_profiles (
    collector_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    council_id INT NOT NULL,
    employee_no VARCHAR(80),
    availability_status ENUM('AVAILABLE', 'ASSIGNED', 'INACTIVE') DEFAULT 'AVAILABLE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (council_id) REFERENCES local_councils(council_id)
        ON DELETE CASCADE
);

CREATE TABLE recycler_profiles (
    recycler_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(180) NOT NULL,
    license_no VARCHAR(120),
    license_expiry_date DATE,
    address TEXT,
    verification_status ENUM('PENDING', 'VERIFIED', 'REJECTED', 'SUSPENDED') DEFAULT 'PENDING',
    verified_by INT NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- =========================================================
-- 04. VEHICLES
-- =========================================================

CREATE TABLE vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    council_id INT NOT NULL,
    vehicle_no VARCHAR(50) NOT NULL UNIQUE,
    vehicle_type VARCHAR(80),
    capacity_kg DECIMAL(10,2),
    status ENUM('AVAILABLE', 'ASSIGNED', 'MAINTENANCE', 'INACTIVE') DEFAULT 'AVAILABLE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (council_id) REFERENCES local_councils(council_id)
        ON DELETE CASCADE
);

-- =========================================================
-- 05. E-WASTE CATEGORIES, ITEMS, RISK RULES
-- =========================================================

CREATE TABLE ewaste_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE'
);

CREATE TABLE ewaste_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    collection_status ENUM('ACCEPTED', 'REVIEW_REQUIRED', 'NOT_COLLECTED') DEFAULT 'ACCEPTED',
    default_risk_level ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'LOW',
    notes TEXT,
    FOREIGN KEY (category_id) REFERENCES ewaste_categories(category_id)
        ON DELETE CASCADE
);

CREATE TABLE risk_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NULL,
    rule_name VARCHAR(150) NOT NULL,
    condition_text TEXT NOT NULL,
    risk_level ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL,
    action_required ENUM('ALLOW_COLLECTION', 'FLAG_FOR_REVIEW', 'REJECT_COLLECTION') NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES ewaste_items(item_id)
        ON DELETE SET NULL
);

CREATE TABLE recycler_capabilities (
    capability_id INT AUTO_INCREMENT PRIMARY KEY,
    recycler_profile_id INT NOT NULL,
    category_id INT NOT NULL,
    can_handle_high_risk BOOLEAN DEFAULT FALSE,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    FOREIGN KEY (recycler_profile_id) REFERENCES recycler_profiles(recycler_profile_id)
        ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES ewaste_categories(category_id)
        ON DELETE CASCADE,
    UNIQUE (recycler_profile_id, category_id)
);

-- =========================================================
-- 06. PUBLIC E-WASTE REQUESTS
-- =========================================================

CREATE TABLE ewaste_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    public_user_id INT NOT NULL,
    area_id INT NOT NULL,
    preferred_date_id INT NOT NULL,
    pickup_address TEXT NOT NULL,
    contact_phone VARCHAR(30),
    special_note TEXT,
    status ENUM(
        'SUBMITTED',
        'APPROVED',
        'REJECTED',
        'ASSIGNED',
        'PICKUP_PENDING',
        'COLLECTED',
        'PARTIALLY_COLLECTED',
        'CANCELLED',
        'COMPLETED'
    ) DEFAULT 'SUBMITTED',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (public_user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES collection_areas(area_id)
        ON DELETE CASCADE,
    FOREIGN KEY (preferred_date_id) REFERENCES area_collection_dates(date_id)
        ON DELETE CASCADE,
    INDEX idx_ewaste_requests_preferred_date (preferred_date_id),
    INDEX idx_ewaste_requests_area_status (area_id, status),
    INDEX idx_ewaste_requests_public_user (public_user_id)
);

CREATE TABLE request_items (
    request_item_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    estimated_weight_kg DECIMAL(10,2),
    condition_status ENUM('WORKING', 'DAMAGED', 'BROKEN', 'LEAKING', 'UNKNOWN') DEFAULT 'UNKNOWN',
    risk_flag ENUM('NONE', 'AUTO_FLAGGED', 'USER_REPORTED') DEFAULT 'NONE',
    note TEXT,
    FOREIGN KEY (request_id) REFERENCES ewaste_requests(request_id)
        ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES ewaste_items(item_id)
        ON DELETE CASCADE
);

CREATE TABLE request_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    changed_by INT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES ewaste_requests(request_id)
        ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE complaints_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    public_user_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('OPEN', 'IN_REVIEW', 'RESOLVED', 'CLOSED') DEFAULT 'OPEN',
    officer_reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES ewaste_requests(request_id)
        ON DELETE SET NULL,
    FOREIGN KEY (public_user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- =========================================================
-- 07. ROUTES
-- =========================================================

CREATE TABLE collection_routes (
    route_id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    area_id INT NOT NULL,
    date_id INT NULL,
    route_name VARCHAR(150) NOT NULL,
    collection_date DATE NOT NULL,
    collector_id INT NULL,
    vehicle_id INT NULL,
    status ENUM('PLANNED', 'ASSIGNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') DEFAULT 'PLANNED',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES collection_campaigns(campaign_id)
        ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES collection_areas(area_id)
        ON DELETE CASCADE,
    FOREIGN KEY (date_id) REFERENCES area_collection_dates(date_id)
        ON DELETE SET NULL,
    FOREIGN KEY (collector_id) REFERENCES users(user_id)
        ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
        ON DELETE SET NULL,
    INDEX idx_collection_routes_date_id (date_id),
    INDEX idx_collection_routes_campaign_area_date (campaign_id, area_id, collection_date),
    INDEX idx_collection_routes_status (status)
);

CREATE TABLE route_stops (
    stop_id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    request_id INT NOT NULL,
    stop_order INT DEFAULT 1,
    stop_status ENUM('PENDING', 'ARRIVED', 'COLLECTED', 'FAILED', 'SKIPPED') DEFAULT 'PENDING',
    collector_note TEXT,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (route_id) REFERENCES collection_routes(route_id)
        ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES ewaste_requests(request_id)
        ON DELETE CASCADE,
    UNIQUE (route_id, request_id),
    UNIQUE (request_id),
    INDEX idx_route_stops_route_status (route_id, stop_status)
);

-- =========================================================
-- 08. COLLECTOR PICKUP RECORDS
-- =========================================================

CREATE TABLE pickup_records (
    pickup_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    route_id INT NOT NULL,
    collector_id INT NOT NULL,
    pickup_status ENUM('COLLECTED', 'PARTIALLY_COLLECTED', 'NOT_AVAILABLE', 'REJECTED_AT_PICKUP') NOT NULL,
    total_collected_weight_kg DECIMAL(10,2),
    collector_note TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verification_status ENUM('PENDING', 'VERIFIED', 'REJECTED') DEFAULT 'PENDING',
    verified_by INT NULL,
    verified_at DATETIME NULL,
    officer_note TEXT,
    FOREIGN KEY (request_id) REFERENCES ewaste_requests(request_id)
        ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES collection_routes(route_id)
        ON DELETE CASCADE,
    FOREIGN KEY (collector_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id)
        ON DELETE SET NULL,
    INDEX idx_pickup_records_verification_status (verification_status),
    INDEX idx_pickup_records_route_id (route_id),
    INDEX idx_pickup_records_request_id (request_id)
);

CREATE TABLE pickup_items (
    pickup_item_id INT AUTO_INCREMENT PRIMARY KEY,
    pickup_id INT NOT NULL,
    item_id INT NOT NULL,
    collected_quantity INT NOT NULL DEFAULT 1,
    collected_weight_kg DECIMAL(10,2),
    condition_status ENUM('GOOD', 'DAMAGED', 'BROKEN', 'LEAKING', 'UNKNOWN') DEFAULT 'UNKNOWN',
    note TEXT,
    FOREIGN KEY (pickup_id) REFERENCES pickup_records(pickup_id)
        ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES ewaste_items(item_id)
        ON DELETE CASCADE
);

CREATE TABLE flagged_items (
    flag_id INT AUTO_INCREMENT PRIMARY KEY,
    request_item_id INT NULL,
    pickup_item_id INT NULL,
    flagged_by INT NOT NULL,
    flag_reason TEXT NOT NULL,
    risk_level ENUM('MEDIUM', 'HIGH') NOT NULL,
    review_status ENUM('PENDING', 'APPROVED_FOR_COLLECTION', 'REJECTED', 'SPECIAL_HANDLING_REQUIRED') DEFAULT 'PENDING',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    officer_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_item_id) REFERENCES request_items(request_item_id)
        ON DELETE SET NULL,
    FOREIGN KEY (pickup_item_id) REFERENCES pickup_items(pickup_item_id)
        ON DELETE SET NULL,
    FOREIGN KEY (flagged_by) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- =========================================================
-- 09. E-LOT CREATION + SIMULATED BIDDING
-- =========================================================

CREATE TABLE elots (
    elot_id INT AUTO_INCREMENT PRIMARY KEY,
    council_id INT NOT NULL,
    created_by INT NOT NULL,
    elot_code VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(180) NOT NULL,
    category_id INT NOT NULL,
    total_weight_kg DECIMAL(10,2),
    description TEXT,
    status ENUM(
        'DRAFT',
        'OPEN_FOR_BIDDING',
        'BIDDING_CLOSED',
        'AWARDED',
        'HANDOVER_PENDING',
        'HANDED_OVER',
        'PROCESSING',
        'COMPLETED',
        'CANCELLED'
    ) DEFAULT 'DRAFT',
    bidding_start DATETIME NULL,
    bidding_end DATETIME NULL,
    winner_recycler_profile_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (council_id) REFERENCES local_councils(council_id)
        ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES ewaste_categories(category_id)
        ON DELETE CASCADE,
    FOREIGN KEY (winner_recycler_profile_id) REFERENCES recycler_profiles(recycler_profile_id)
        ON DELETE SET NULL
);

CREATE TABLE elot_items (
    elot_item_id INT AUTO_INCREMENT PRIMARY KEY,
    elot_id INT NOT NULL,
    pickup_item_id INT NOT NULL,
    quantity INT NOT NULL,
    weight_kg DECIMAL(10,2),
    FOREIGN KEY (elot_id) REFERENCES elots(elot_id)
        ON DELETE CASCADE,
    FOREIGN KEY (pickup_item_id) REFERENCES pickup_items(pickup_item_id)
        ON DELETE CASCADE,
    UNIQUE (elot_id, pickup_item_id),
    UNIQUE (pickup_item_id)
);

CREATE TABLE bids (
    bid_id INT AUTO_INCREMENT PRIMARY KEY,
    elot_id INT NOT NULL,
    recycler_profile_id INT NOT NULL,
    bid_amount DECIMAL(12,2) NOT NULL,
    bid_note TEXT,
    status ENUM('SUBMITTED', 'WITHDRAWN', 'REJECTED', 'WINNING_BID') DEFAULT 'SUBMITTED',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (elot_id) REFERENCES elots(elot_id)
        ON DELETE CASCADE,
    FOREIGN KEY (recycler_profile_id) REFERENCES recycler_profiles(recycler_profile_id)
        ON DELETE CASCADE,
    UNIQUE (elot_id, recycler_profile_id)
);

CREATE TABLE elot_status_history (
    elot_history_id INT AUTO_INCREMENT PRIMARY KEY,
    elot_id INT NOT NULL,
    changed_by INT NULL,
    old_status VARCHAR(60),
    new_status VARCHAR(60) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (elot_id) REFERENCES elots(elot_id)
        ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- =========================================================
-- 10. BASIC REPORT SNAPSHOT TABLE
-- Optional: useful for dashboard caching later.
-- =========================================================

CREATE TABLE report_snapshots (
    snapshot_id INT AUTO_INCREMENT PRIMARY KEY,
    council_id INT NULL,
    report_type VARCHAR(100) NOT NULL,
    report_month TINYINT,
    report_year YEAR,
    total_requests INT DEFAULT 0,
    completed_collections INT DEFAULT 0,
    pending_pickups INT DEFAULT 0,
    total_elots INT DEFAULT 0,
    total_bids INT DEFAULT 0,
    flagged_items_count INT DEFAULT 0,
    generated_by INT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (council_id) REFERENCES local_councils(council_id)
        ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- =========================================================
-- 11. SEED DATA
-- =========================================================

INSERT INTO local_councils (council_name, district, province)
VALUES
('Sample Municipal Council', 'Colombo', 'Western');

INSERT INTO collection_areas (council_id, area_name, postal_code)
VALUES
(1, 'Colombo 01 Area', '00100'),
(1, 'Colombo 02 Area', '00200'),
(1, 'Colombo 03 Area', '00300'),
(1, 'Colombo 04 Area', '00400');

-- Basic reference schedules are left without campaign_id for compatibility.
-- The latest logical massive bundle will create campaign-linked schedules.
INSERT INTO area_collection_dates (area_id, campaign_id, collection_date, max_requests, status)
VALUES
(1, NULL, '2026-08-05', 50, 'OPEN'),
(1, NULL, '2026-08-20', 50, 'OPEN'),
(2, NULL, '2026-08-07', 50, 'OPEN'),
(2, NULL, '2026-08-22', 50, 'OPEN'),
(3, NULL, '2026-08-10', 50, 'OPEN'),
(4, NULL, '2026-08-12', 50, 'OPEN');

INSERT INTO ewaste_categories (category_name, description)
VALUES
('Domestic E-Waste', 'Household electronic waste items'),
('Office E-Waste', 'Office and business electronic waste items'),
('Automobile E-Waste', 'Vehicle-related electronic waste items'),
('Industrial E-Waste', 'Industrial electronic waste items'),
('Medical E-Waste', 'Medical electronic waste items requiring careful review'),
('Do Not Collect', 'Items excluded from normal collection and should be flagged or rejected');

INSERT INTO ewaste_items 
(category_id, item_name, collection_status, default_risk_level, notes)
VALUES
-- Domestic
(1, 'LCD TVs/Monitors', 'ACCEPTED', 'LOW', NULL),
(1, 'LED lamps', 'ACCEPTED', 'LOW', NULL),
(1, 'Computer hardware', 'ACCEPTED', 'LOW', NULL),
(1, 'Radios/DVD players', 'ACCEPTED', 'LOW', NULL),
(1, 'Electric ovens/Microwave ovens', 'ACCEPTED', 'MEDIUM', NULL),
(1, 'Fans/Hair dryers', 'ACCEPTED', 'LOW', NULL),
(1, 'Mobile phones/Laptops/Chargers', 'ACCEPTED', 'LOW', NULL),
(1, 'Bluetooth speakers/Earbuds', 'ACCEPTED', 'LOW', NULL),
(1, 'Cameras/CCTV equipment', 'ACCEPTED', 'LOW', NULL),

-- Office
(2, 'Photocopy machines', 'ACCEPTED', 'MEDIUM', NULL),
(2, 'UPS units/UPS batteries', 'REVIEW_REQUIRED', 'MEDIUM', 'Battery items should be reviewed'),
(2, 'Printers/Scanners', 'ACCEPTED', 'LOW', NULL),
(2, 'Projectors/Speakers', 'ACCEPTED', 'LOW', NULL),
(2, 'Access control equipment', 'ACCEPTED', 'LOW', NULL),
(2, 'Network equipment', 'ACCEPTED', 'LOW', NULL),
(2, 'Telephone/Fax/Intercom equipment', 'ACCEPTED', 'LOW', NULL),
(2, 'Barcode readers/POS machines', 'ACCEPTED', 'LOW', NULL),

-- Automobile
(3, 'Dashboard electronics', 'ACCEPTED', 'LOW', NULL),
(3, 'LED headlights', 'ACCEPTED', 'LOW', NULL),
(3, 'Hybrid batteries/EV batteries', 'REVIEW_REQUIRED', 'HIGH', 'High-risk battery item'),
(3, 'Motors/Alternators', 'ACCEPTED', 'MEDIUM', NULL),
(3, 'Switches', 'ACCEPTED', 'LOW', NULL),
(3, 'Sensors', 'ACCEPTED', 'LOW', NULL),
(3, 'Cables', 'ACCEPTED', 'LOW', NULL),
(3, 'Relays', 'ACCEPTED', 'LOW', NULL),
(3, 'Heaters', 'ACCEPTED', 'MEDIUM', NULL),

-- Industrial
(4, 'Inverters/VFDs', 'ACCEPTED', 'MEDIUM', NULL),
(4, 'CNC machines', 'REVIEW_REQUIRED', 'MEDIUM', 'Large industrial item should be reviewed'),
(4, 'Elevator electronic components', 'ACCEPTED', 'MEDIUM', NULL),
(4, 'Sign board displays', 'ACCEPTED', 'LOW', NULL),
(4, 'Air conditioners', 'REVIEW_REQUIRED', 'HIGH', 'Should not be treated as normal item without review'),
(4, 'Automation equipment', 'ACCEPTED', 'MEDIUM', NULL),
(4, 'Power supply units', 'ACCEPTED', 'MEDIUM', NULL),
(4, 'Vending machine hardware', 'ACCEPTED', 'MEDIUM', NULL),
(4, 'Solar power equipment', 'ACCEPTED', 'MEDIUM', NULL),

-- Medical
(5, 'Ventilators/Insulin pumps', 'REVIEW_REQUIRED', 'HIGH', 'Medical item requires review'),
(5, 'Hearing aids/Electric wheelchairs', 'REVIEW_REQUIRED', 'MEDIUM', NULL),
(5, 'Oximeters/Electronic thermometers', 'REVIEW_REQUIRED', 'MEDIUM', NULL),
(5, 'Ultrasound machines and probes', 'REVIEW_REQUIRED', 'HIGH', NULL),
(5, 'Centrifuges/Spectrophotometers', 'REVIEW_REQUIRED', 'HIGH', NULL),
(5, 'Electronic medical record systems', 'REVIEW_REQUIRED', 'MEDIUM', NULL),
(5, 'Glucometers/Weight scales', 'REVIEW_REQUIRED', 'MEDIUM', NULL),

-- Do Not Collect
(6, 'CFL bulbs/Tube lights/Mercury lamps', 'NOT_COLLECTED', 'HIGH', 'Excluded from normal collection'),
(6, 'Refrigerators/Washing machines', 'NOT_COLLECTED', 'HIGH', 'Large white goods excluded'),
(6, 'CRT TVs/CRT monitors', 'NOT_COLLECTED', 'HIGH', 'Excluded from normal collection'),
(6, 'Leaking batteries', 'NOT_COLLECTED', 'HIGH', 'Hazardous item'),
(6, 'CT scanners/X-ray equipment', 'NOT_COLLECTED', 'HIGH', 'High-risk medical equipment'),
(6, 'Smoke detectors/Radioactive sources', 'NOT_COLLECTED', 'HIGH', 'Radioactive risk'),
(6, 'Items containing mercury/cadmium/phosphorous', 'NOT_COLLECTED', 'HIGH', 'Hazardous materials'),
(6, 'Biohazardous equipment', 'NOT_COLLECTED', 'HIGH', 'Biohazard risk');

INSERT INTO risk_rules 
(item_id, rule_name, condition_text, risk_level, action_required)
SELECT item_id,
       CONCAT(item_name, ' risk rule'),
       CONCAT(item_name, ' should not be collected as a normal item. It must be reviewed or rejected according to prototype rules.'),
       default_risk_level,
       CASE
           WHEN collection_status = 'NOT_COLLECTED' THEN 'REJECT_COLLECTION'
           WHEN collection_status = 'REVIEW_REQUIRED' THEN 'FLAG_FOR_REVIEW'
           ELSE 'ALLOW_COLLECTION'
       END
FROM ewaste_items;

INSERT INTO vehicles (council_id, vehicle_no, vehicle_type, capacity_kg)
VALUES
(1, 'WP-EC-1001', 'Small Truck', 1000.00),
(1, 'WP-EC-1002', 'Van', 500.00);

-- NOTE:
-- Users should be created from the PHP registration form using password_hash().
-- Do not manually store plain-text passwords.