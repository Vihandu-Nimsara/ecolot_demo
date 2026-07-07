-- EcoLot LK municipal officer business-rule alignment
-- Safe additive migration only. Review current data before making nullable
-- columns NOT NULL.

-- 1) Tie area collection schedules to monthly campaigns.
ALTER TABLE area_collection_dates
    ADD COLUMN campaign_id INT NULL AFTER date_id;

ALTER TABLE area_collection_dates
    ADD INDEX idx_area_collection_dates_campaign (campaign_id),
    ADD INDEX idx_area_collection_dates_area_date (area_id, collection_date);

ALTER TABLE area_collection_dates
    ADD CONSTRAINT fk_area_collection_dates_campaign
    FOREIGN KEY (campaign_id) REFERENCES collection_campaigns(campaign_id)
    ON DELETE CASCADE;

-- 2) Tie collection routes back to the selected area schedule/date.
ALTER TABLE collection_routes
    ADD COLUMN date_id INT NULL AFTER area_id;

UPDATE collection_routes cr
INNER JOIN area_collection_dates acd
    ON cr.area_id = acd.area_id
    AND cr.collection_date = acd.collection_date
SET cr.date_id = acd.date_id
WHERE cr.date_id IS NULL;

ALTER TABLE collection_routes
    ADD INDEX idx_collection_routes_date (date_id);

ALTER TABLE collection_routes
    ADD CONSTRAINT fk_collection_routes_area_date
    FOREIGN KEY (date_id) REFERENCES area_collection_dates(date_id)
    ON DELETE SET NULL;

-- 3) Filtering / validation indexes.
ALTER TABLE ewaste_requests
    ADD INDEX idx_ewaste_requests_preferred_date (preferred_date_id),
    ADD INDEX idx_ewaste_requests_area_status (area_id, status);

ALTER TABLE route_stops
    ADD INDEX idx_route_stops_request (request_id);

ALTER TABLE pickup_records
    ADD INDEX idx_pickup_records_verification (verification_status);

-- Rollback note:
-- Drop the added foreign keys/indexes before dropping area_collection_dates.campaign_id
-- or collection_routes.date_id. Do not drop these columns after live data starts
-- depending on them without first exporting/backing up route and schedule mappings.
