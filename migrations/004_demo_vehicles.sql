-- 004: Demo vehicles across all categories (so the site isn't empty + for SEO).
-- Each insert is guarded so it runs at most once; agency = first agency (or NULL).

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='bike' LIMIT 1),
  'Royal Enfield Classic 350','Royal Enfield','Classic 350','gear','petrol',2,3,80,800,3000,'Iconic cruiser for Dwarka rides and coastal trips.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='Royal Enfield Classic 350');

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='scooty' LIMIT 1),
  'TVS Jupiter','TVS','Jupiter','non_gear','petrol',2,5,40,400,1000,'Easy non-gear scooty, perfect for temple darshan.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='TVS Jupiter');

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='car' LIMIT 1),
  'Maruti Swift (Self-Drive)','Maruti Suzuki','Swift','manual','petrol',5,2,150,1600,5000,'Comfortable self-drive hatchback for family trips.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='Maruti Swift (Self-Drive)');

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='taxi' LIMIT 1),
  'Toyota Innova Crysta (Taxi)','Toyota','Innova Crysta','automatic','diesel',7,2,0,3500,0,'7-seater taxi with driver for Dwarka darshan & outstation.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='Toyota Innova Crysta (Taxi)');

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='car-with-driver' LIMIT 1),
  'Swift Dzire (With Driver)','Maruti Suzuki','Dzire','manual','petrol',4,3,0,2500,0,'Sedan with driver for local sightseeing and airport pickup.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='Swift Dzire (With Driver)');

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='tempo-traveller' LIMIT 1),
  'Force Tempo Traveller 12-Seater','Force','Traveller','manual','diesel',12,2,0,6500,0,'12-seater tempo traveller for group tours and pilgrimages.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='Force Tempo Traveller 12-Seater');

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='bus' LIMIT 1),
  'Mini Bus 20-Seater','Force','Traveller Bus','manual','diesel',20,1,0,9000,0,'20-seater mini bus for large groups and events.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='Mini Bus 20-Seater');

INSERT INTO `{p}vehicles` (agency_id,category_id,name,brand,model,transmission,fuel,seats,units,price_hour,price_day,deposit,description,status)
SELECT (SELECT id FROM `{p}agencies` ORDER BY id LIMIT 1), (SELECT id FROM `{p}categories` WHERE slug='cycle' LIMIT 1),
  'Hero Sprint Cycle','Hero','Sprint','na','none',1,10,20,150,500,'Geared bicycle for eco-friendly local exploring.','active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{p}vehicles` WHERE name='Hero Sprint Cycle');
