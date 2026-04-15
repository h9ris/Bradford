-- =====================================================
-- Bradford Portal - Migration Script v2
-- Run this on your EXISTING database to add new columns
-- =====================================================

USE bradford_portal;

-- Add first_name and last_name columns (safe - only adds if missing)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS first_name VARCHAR(255) DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS last_name  VARCHAR(255) DEFAULT NULL AFTER first_name;

-- Create Bradford schools table if it doesn't exist
CREATE TABLE IF NOT EXISTS bradford_schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    address VARCHAR(255),
    postcode VARCHAR(10),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    phone VARCHAR(30),
    ofsted_rating VARCHAR(50)
) ENGINE=InnoDB;

-- Seed Bradford schools (only if table is empty)
INSERT INTO bradford_schools (name, type, address, postcode, latitude, longitude, phone, ofsted_rating)
SELECT * FROM (VALUES
    ROW('Bradford Grammar School','Independent','Keighley Road, Bradford','BD9 4JP',53.8090,-1.7610,'01274 542492','Outstanding'),
    ROW('Belle Vue Girls Academy','Academy','Thorn Lane, Bradford','BD9 5AB',53.8020,-1.7680,'01274 490333','Good'),
    ROW('Bradford Academy','Academy','Teasdale Street, Bradford','BD4 7QB',53.7810,-1.7250,'01274 089200','Good'),
    ROW('Dixons City Academy','Academy','Ripley Street, Bradford','BD5 7RR',53.7860,-1.7500,'01274 400600','Outstanding'),
    ROW('Hanson Academy','Academy','Sutton Avenue, Bradford','BD7 4RL',53.7900,-1.7850,'01274 731011','Requires Improvement'),
    ROW('Immanuel College','Academy','New Line, Bradford','BD10 0JX',53.8300,-1.7220,'01274 620461','Good'),
    ROW('Beckfoot School','Academy','Wagon Lane, Bingley','BD16 1EE',53.8450,-1.8310,'01274 771444','Good'),
    ROW('Ilkley Grammar School','Academy','Cowpasture Road, Ilkley','LS29 8TR',53.9250,-1.8230,'01943 608424','Good'),
    ROW('Titus Salt School','Academy','Higher Coach Road, Baildon','BD17 5RH',53.8510,-1.7690,'01274 582212','Good'),
    ROW('St Bede and St Joseph Catholic College','Catholic Academy','Ashwell Road, Bradford','BD7 1QH',53.7960,-1.7780,'01274 501414','Good'),
    ROW('Saltaire Primary School','Primary','Holywell Ash Lane, Saltaire','BD18 4NN',53.8377,-1.7909,'01274 585074','Outstanding'),
    ROW('Shipley CE Primary School','Primary (CE)','Kirkgate, Shipley','BD18 3EH',53.8330,-1.7710,'01274 584056','Good'),
    ROW('Bingley Grammar School','Academy','Keighley Road, Bingley','BD16 2RS',53.8510,-1.8380,'01274 551376','Good'),
    ROW('Whetley Primary School','Primary','Whetley Lane, Bradford','BD8 9HS',53.8040,-1.7760,'01274 545408','Good'),
    ROW('Keighley College','College','Bradford Road, Keighley','BD21 4HK',53.8680,-1.9040,'01535 618600','Good')
) AS new_schools(name, type, address, postcode, latitude, longitude, phone, ofsted_rating)
WHERE (SELECT COUNT(*) FROM bradford_schools) = 0;

SELECT 'Migration complete!' AS status;
