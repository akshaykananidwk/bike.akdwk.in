-- 002_taxi_categories.sql
-- Adds Taxi and Car-with-Driver categories so taxi/cab booking is a real option.
-- Idempotent: slug is unique, INSERT IGNORE skips if already present.

INSERT IGNORE INTO `{p}categories` (`name`,`name_gu`,`slug`,`icon`,`sort_order`,`status`) VALUES
  ('Taxi / Cab','ટેક્સી / કેબ','taxi','bi-taxi-front',7,'active'),
  ('Car with Driver','ડ્રાઇવર સાથે કાર','car-with-driver','bi-car-front-fill',8,'active'),
  ('Bus / Mini Bus','બસ / મિની બસ','bus','bi-bus-front',9,'active');
