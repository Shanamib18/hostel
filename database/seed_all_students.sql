USE hostel_lbscek;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------
-- COMPREHENSIVE SEED DATA WITH ALL STUDENTS
-- --------------------------------------------------

-- --------------------------------------------------
-- 1. HOSTEL
-- --------------------------------------------------

INSERT INTO hostels (id, name, type, total_rooms, capacity, address)
VALUES (1, 'Ladies'' Hostel LBSCEK', 'ladies', 141, 278,
        'LBS College of Engineering, Kasaragod')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- --------------------------------------------------
-- 2. ROOMS (141 rooms, capacity 2 each)
-- --------------------------------------------------

UPDATE students SET room_id = NULL WHERE room_id IS NOT NULL;
DELETE FROM rooms WHERE hostel_id = 1;

-- G CATEGORY (25 ROOMS)
INSERT INTO rooms (hostel_id, room_number, capacity, current_occupancy, status)
SELECT 1, CONCAT('G', LPAD(n,2,'0')), 2, 0, 'available'
FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25) t;

-- F CATEGORY (53 ROOMS)
INSERT INTO rooms (hostel_id, room_number, capacity, current_occupancy, status)
SELECT 1, CONCAT('F', LPAD(n,2,'0')), 2, 0, 'available'
FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35
UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40
UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45
UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50
UNION SELECT 51 UNION SELECT 52 UNION SELECT 53) t;

-- S CATEGORY (60 ROOMS)
INSERT INTO rooms (hostel_id, room_number, capacity, current_occupancy, status)
SELECT 1, CONCAT('S', LPAD(n,2,'0')), 2, 0, 'available'
FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35
UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40
UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45
UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50
UNION SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55
UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60) t;

-- --------------------------------------------------
-- 3. STAFF
-- --------------------------------------------------

DELETE FROM staff WHERE hostel_id = 1;

INSERT INTO staff (hostel_id, name, email, password_hash, role, phone)
VALUES
(1, 'Admin User', 'admin@lbscek.ac.in',
 '$2a$10$ebEm8uRxx7MywVeXWCd3quIFpwZyLygkxKz4Kk68xyVPZ8GrB1UvS', 'admin', '9999990001'),
(1, 'Chief Warden', 'warden@lbscek.ac.in',
 '$2a$10$ebEm8uRxx7MywVeXWCd3quIFpwZyLygkxKz4Kk68xyVPZ8GrB1UvS', 'warden', '9999990002'),
(1, 'Mess Manager', 'mess@lbscek.ac.in',
 '$2a$10$ebEm8uRxx7MywVeXWCd3quIFpwZyLygkxKz4Kk68xyVPZ8GrB1UvS', 'mess_manager', '9999990005');

-- --------------------------------------------------
-- 4. ALL STUDENTS FROM CSV (100+ students)
-- --------------------------------------------------

DELETE FROM students;

SET @pwd_hash = '$2a$10$ebEm8uRxx7MywVeXWCd3quIFpwZyLygkxKz4Kk68xyVPZ8GrB1UvS';

INSERT IGNORE INTO students (hostel_id, student_id, name, email, phone, parent_name, parent_phone, department, year, room_id, password_hash, is_active) VALUES
(1, '2025B218', 'Aiswarya kp', 'aiswaryalizflair@gmail.com', '7356451911', 'Anil kumar kp', '7994218806', 'ECE', 1, (SELECT id FROM rooms WHERE room_number = 'F07'), @pwd_hash, TRUE),
(1, '2025B149', 'Raiza Bilal', 'raizabilal980@gmail.com', '7012366852', 'Bilal Koyammadath', '8606733173', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F38'), @pwd_hash, TRUE),
(1, '2025B599', 'Sreelakshmi Pramod', 'sree69676@gmail.com', '7736221911', 'Lathika.M', '9544186747', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F05'), @pwd_hash, TRUE),
(1, '2025B571', 'Nafia P', 'Nafiap683@gmail.com', '9895108189', 'Noushad kp', '7358926733', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F09'), @pwd_hash, TRUE),
(1, '2025B168', 'Bhadra R', 'bhadrar127@gmail.com', '9645053583', 'Ranjith R', '9645053583', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F10'), @pwd_hash, TRUE),
(1, '2025B506', 'Anamika V', 'anamikavaliyedath@gmail.com', '8281458084', 'Girish V', '9072728084', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F10'), @pwd_hash, TRUE),
(1, '2025B241', 'Gayathri H', 'hgayathri010@gmail.com', '8848940144', 'Haridas E K', '7034823507', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F11'), @pwd_hash, TRUE),
(1, '2025B605', 'Ayisha Hanan k', 'ayishahanan897@gmail.com', '9778527004', 'Ayisha Hanan k', '7907801581', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F17'), @pwd_hash, TRUE),
(1, '2025B555', 'Anushka.K', 'anushkak1235@gmail.com', '6238608875', 'Sheeja.P', '9496239343', 'AI & DS', 1, (SELECT id FROM rooms WHERE room_number = 'F18'), @pwd_hash, TRUE),
(1, '2025B111', 'Adheena Raj', 'adheenaraj249@gmail.com', '9496310167', 'Valsaraj KN', '9495410167', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F22'), @pwd_hash, TRUE),
(1, '2025B487', 'Dhiya Mohan', 'dhiyamohan81@gmail.com', '8075317468', 'Bindu K N', '9778161705', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F28'), @pwd_hash, TRUE),
(1, '2025B316', 'Fathima Hiba P', 'fathimahibapktl@gmail.com', '9961269056', 'Sidheeque', '9946462396', 'IT', 1, (SELECT id FROM rooms WHERE room_number = 'F37'), @pwd_hash, TRUE),
(1, '2025B384', 'Niveditha.k', 'niveditha2683@gmail.com', '9495112683', 'Vidya k', '8594073633', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F38'), @pwd_hash, TRUE),
(1, '2024B101', 'Nithya a', 'Nithyaavilary2005@gmail.com', '6238315191', 'Sajeevan', '9745908679', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'G22'), @pwd_hash, TRUE),
(1, '2024B160', 'V P Sreelakshmi', 'vpsreelakshmi1707@gmail.com', '8157801463', 'Anilkumar V P', '8281491474', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S57'), @pwd_hash, TRUE),
(1, '2024B003', 'Khadeejath thahnia', 'thanakdja06@gmail.com', '6282391227', '8129481365', '6282391227', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S18'), @pwd_hash, TRUE),
(1, '2023B127', 'Nandana Rajan', 'nandananandana192@gmail.com', '9895405870', 'Rajan KT', '9605990081', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S40'), @pwd_hash, TRUE),
(1, '2023B077', 'Neha.NV', 'nehanv2005@gmail.com', '7902500256', 'Lineesh.NV', '9387372502', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S29'), @pwd_hash, TRUE),
(1, '2024B046', 'Nafla Ismail', 'naflaismail01@gmail.com', '8281003742', 'Ismail olayikkara', '9447196116', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S57'), @pwd_hash, TRUE),
(1, '2204B314', 'Amrutha. S. N', 'amruthasmeenu@gmail.com', '7736148042', 'Shaji. N. N', '9446906042', 'IT', 2, (SELECT id FROM rooms WHERE room_number = 'S04'), @pwd_hash, TRUE),
(1, '2025BLO27', 'ARYANANDA.VB', 'aryaarathi497@gmail.com', '7560908730', 'VINOD.A', '8943028730', 'Civil Engineering', 2, (SELECT id FROM rooms WHERE room_number = 'G17'), @pwd_hash, TRUE),
(1, '2025BL074', 'SREEKALA K', 'sreekalak0049@gmail.com', '8075932449', 'SANTHA K', '9447216477', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'G17'), @pwd_hash, TRUE),
(1, '2025BL029', 'SNEHA V R', 'snehavr9656@gmail.com', '9846024765', 'RAMACHANDRAN A C', '9656037025', 'Civil Engineering', 2, (SELECT id FROM rooms WHERE room_number = 'G17'), @pwd_hash, TRUE),
(1, '2025BL073', 'VISMAYA MS', 'vismayams2@gmail.com', '9656804640', 'Sreejaya', '9846404640', 'Civil Engineering', 2, (SELECT id FROM rooms WHERE room_number = 'G17'), @pwd_hash, TRUE),
(1, '2024B294', 'ANANYA SUKESH', 'ananyasukesh07@gmail.com', '8301050344', 'SUKESH KUMAR V C', '9744400344', 'IT', 2, (SELECT id FROM rooms WHERE room_number = 'S20'), @pwd_hash, TRUE),
(1, '2024B442', 'Archana. R', 'achuachukutty001001@gmail.com', '6282292402', 'Rajesh. R', '9074300665', 'IT', 2, (SELECT id FROM rooms WHERE room_number = 'S20'), @pwd_hash, TRUE),
(1, '2024B531', 'Hima fathima', 'himafathima18@gmail.com', '8606397806', 'Zubaida', '9072814806', 'EEE', 2, (SELECT id FROM rooms WHERE room_number = 'S14'), @pwd_hash, TRUE),
(1, '2024BL031', 'Suchithra R', 'kukkusuchithra010@gmail.com', '8281581305', 'Sajan Kumar J', '9207261878', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S11'), @pwd_hash, TRUE),
(1, '2024B245', 'Gayana S', 'gayanasajan@gmail.com', '7012692599', 'Sajan KP', '9562676334', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S26'), @pwd_hash, TRUE),
(1, '2024B244', 'Vani Santhosh', 'vanisanthoshuc@gmail.com', '9633049765', 'Santhosh', '8547878174', 'ECE', 2, (SELECT id FROM rooms WHERE room_number = 'S03'), @pwd_hash, TRUE),
(1, '2024B043', 'Ananya O', 'ananyaooppath@gmail.com', '8075047087', 'Manoharan', '9995039460', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S41'), @pwd_hash, TRUE),
(1, '2024B431', 'Khadeejath Abshira', 'khadeejathabshiraabbas@gmail.com', '9567512987', 'Abbas', '9567512987', 'IT', 2, (SELECT id FROM rooms WHERE room_number = 'S14'), @pwd_hash, TRUE),
(1, '2025B223', 'Diya tv', 'diyatv098@gmail.com', '8590524107', 'Babitha sreejith', '9400675694', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F16'), @pwd_hash, TRUE),
(1, '2024B409', 'Anusree P T', 'anusreept13@gmail.com', '9947933680', 'Kumaran P T', '9048223123', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S22'), @pwd_hash, TRUE),
(1, '2023B191', 'Mayookha pradeep', 'mayookhapradeep11@gmail.com', '9036956738', 'Pradeep Kumar', '9900313143', 'IT', 3, (SELECT id FROM rooms WHERE room_number = 'S38'), @pwd_hash, TRUE),
(1, '2025B438', 'DIYA K NAMBIAR', 'diyamanojnbr@gmail.com', '9037521715', 'RAJINA K V', '9961529897', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F29'), @pwd_hash, TRUE),
(1, '2024B112', 'Devangana D V', 'devanganadv@gmail.com', '8281464566', 'Dinesh Kumar E C', '9497644566', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S04'), @pwd_hash, TRUE),
(1, '2024B140', 'Alakananda D S', 'alakanandads1@gmail.com', '7907451918', 'Sunil D', '8075256900', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S02'), @pwd_hash, TRUE),
(1, '2023B228', 'Zuha Muhammed Sali', 'zuhamuhammedsali@gmail.com', '9995739417', 'Muhammed Sali P', '9995730081', 'IT', 3, (SELECT id FROM rooms WHERE room_number = 'S44'), @pwd_hash, TRUE),
(1, '2024B413', 'Nadiya Nasrin M', 'nadiyanasrin328@gmail.com', '6235829445', 'Kunhamina M', '9539299445', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S02'), @pwd_hash, TRUE),
(1, '2024B138', 'Hida Bijesh m', 'hidabijesh@gmail.com', '8590454891', 'Bijesh', '9747140057', 'ECE', 2, NULL, @pwd_hash, TRUE),
(1, '2024B444', 'Sumna Fathima Sulaiman K', 'sumnafathimak@gmail.com', '9605511408', 'Suhara Sulaiman', '9526797029', 'IT', 2, (SELECT id FROM rooms WHERE room_number = 'S37'), @pwd_hash, TRUE),
(1, '2024B089', 'Saya K', 'sayashabu@gmail.com', '7907271055', 'PV SHABU', '9747778591', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S07'), @pwd_hash, TRUE),
(1, '2024B080', 'Ganga Praveen', 'gangapraveenc@gmail.com', '9544354527', 'Praveen C', '9747354527', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S03'), @pwd_hash, TRUE),
(1, '2025B528', 'Fathima kk', 'fathimakk841@gmail.com', '8089190059', 'Hafna', '7560860059', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F12'), @pwd_hash, TRUE),
(1, '2024B006', 'Laiba', 'fathimalaiba123@gmail.com', '7012', 'Nabela', '9747392435', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S50'), @pwd_hash, TRUE),
(1, '2024B144', 'Devangana SS', 'ssdevangana@gmail.com', '6238987176', 'Suresh babu', '8547172035', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S46'), @pwd_hash, TRUE),
(1, '2025B660', 'Anahita Jithendran', 'anahitajithendran24@gmail.com', '8289955390', 'Rakhee Raghavan', '9388705390', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F27'), @pwd_hash, TRUE),
(1, '2024B275', 'Aparna K P', 'aparnasivadas05@gmail.com', '9497090709', 'Sivadasan K', '9961640703', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S53'), @pwd_hash, TRUE),
(1, '2024B054', 'Bhavya P', 'bhavz475@gmail.com', '9526793470', 'Dhanya P', '6238547675', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S51'), @pwd_hash, TRUE),
(1, '2024B023', 'KV HIBAMUBARACK', 'hibamubarack123@gmail.com', '9074888731', 'Shahida', '9946112066', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S41'), @pwd_hash, TRUE),
(1, '2025B587', 'Sniya vishal', 'sniyavishalsniya@gmail.com', '9207689345', 'Sanila', '9961719985', 'IT', 1, (SELECT id FROM rooms WHERE room_number = 'F05'), @pwd_hash, TRUE),
(1, '2023B138', 'Rasha kp', 'rashasinu081@gmail.com', '8606726170', 'Abdul Rasheed kp', '9447526170', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S44'), @pwd_hash, TRUE),
(1, '2023B350', 'Vaishnavi Krishnan T K', 'vaishnavikrishnantk2005@gmail.com', '9778044511', 'Bindu Krishnan', '9645283395', 'IT', 3, (SELECT id FROM rooms WHERE room_number = 'S31'), @pwd_hash, TRUE),
(1, '2024B492', 'Amrutha k v', 'Amruthaanu33@gmail.com', '8589802792', 'Pradeep k v', '9745117695', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S21'), @pwd_hash, TRUE),
(1, '2023B054', 'Amrutha P', 'amruthaprakash30@gmail.com', '6238583655', 'Prakashan', '9846445204', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S27'), @pwd_hash, TRUE),
(1, '2025B140', 'Shizna', 'shiznashakeel@gmail.com', '8281520799', 'Minnath TP', '8089678074', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F34'), @pwd_hash, TRUE),
(1, '2025B243', 'Aiswarya pv', 'aiswaryapv07@gmail.com', '7025438806', 'Beena pv', '9778068155', 'EEE', 1, (SELECT id FROM rooms WHERE room_number = 'F31'), @pwd_hash, TRUE),
(1, '2024B109', 'Farhana Afrin P A', 'farhana2005afrin@gmail.com', '7034680329', 'P M Abdul Hassan', '9645405475', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S42'), @pwd_hash, TRUE),
(1, '2025BL051', 'Arya K K', 'adhi1155779@gmail.com', '7559890860', 'Madhu A av', '8156890870', 'Civil Engineering', 2, (SELECT id FROM rooms WHERE room_number = 'G16'), @pwd_hash, TRUE),
(1, '2025B388', 'Diyasree Shaji', 'diyasreeshaji@gmail.com', '9074576412', 'Shaji Chandran', '9850604065', 'ME', 1, (SELECT id FROM rooms WHERE room_number = 'F08'), @pwd_hash, TRUE),
(1, '2025B664', 'JYOTHIKA P', 'pjyothika31@gmail.com', '7907209542', 'Sreeja P', '7907209542', 'ME', 1, (SELECT id FROM rooms WHERE room_number = 'F33'), @pwd_hash, TRUE),
(1, '2024B226', 'Parvathy Menon', 'nirmalkunj881@gmail.com', '7736809881', 'Alka J Menon', '8907201881', 'ME', 2, (SELECT id FROM rooms WHERE room_number = 'G29'), @pwd_hash, TRUE),
(1, '2025B260', 'Aaliya Ansar', 'aaliyaansarpa74@gmail.com', '9567909976', 'Thahira A A', '9747413687', 'AI & DS', 1, (SELECT id FROM rooms WHERE room_number = 'F19'), @pwd_hash, TRUE),
(1, '2024B139', 'Nuva dhin ik', 'nuvadhin@gmail.com', '7560817184', 'Shamsudheen', '9539334808', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S26'), @pwd_hash, TRUE),
(1, '2024B110', 'Ridha Fathima', 'ridhafathima259@gmail.com', '8547672127', 'Ismail', '9400692127', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S51'), @pwd_hash, TRUE),
(1, '2024B161', 'Alifna M V', 'alifnamv514@gmail.com', '6235036972', 'Assainar', '8156925616', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S01'), @pwd_hash, TRUE),
(1, '2025B485', 'Rasiya KM', 'rasiyakm18@gmail.com', '9633082838', 'Mohammed Hussan KH', '9656490838', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F32'), @pwd_hash, TRUE),
(1, '2024B520', 'Fathimath Thanveera', 'thansh380@gmail.com', '8129840656', 'Thahira', '9567351806', 'Civil Engineering', 2, (SELECT id FROM rooms WHERE room_number = 'G29'), @pwd_hash, TRUE),
(1, '2024B326', 'Shivani pk', 'shivanipk1075@gmail.com', '7012967300', 'Ranjith pk', '8086460417', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S19'), @pwd_hash, TRUE),
(1, '2025B206', 'Anjalina E A', 'anjalina207@gmail.com', '8590072526', 'Edwin Y', '9497638345', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F48'), @pwd_hash, TRUE),
(1, '2024B145', 'Manasa Santhosh', 'manasasanthosh508@gmail.com', '8891679527', 'Santhosh P', '8089558323', 'ECE', 2, (SELECT id FROM rooms WHERE room_number = 'S19'), @pwd_hash, TRUE),
(1, '2023B354', 'Rajna p', 'rajna9931@gmail.com', '8137052450', 'Jaleel m', '8137052450', 'ECE', 3, (SELECT id FROM rooms WHERE room_number = 'S31'), @pwd_hash, TRUE),
(1, '2025B051', 'Nivedya N M', 'nmnivedya@gmail.com', '8281486853', 'Radhakrishnan N M', '9895253164', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F24'), @pwd_hash, TRUE),
(1, '2024B045', 'Keerthana k', 'keerthi28112005@gmail.com', '6238747671', 'Shiji E', '9349134771', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S23'), @pwd_hash, TRUE),
(1, '2025B527', 'K P KRISHNA', 'kpkrishnachandran@gmail.com', '6235722621', 'PRABITHA', '9847295627', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F07'), @pwd_hash, TRUE),
(1, '2025B239', 'Anulakshmi M', 'aanulakshmimadiyan@gmail.com', '9778502289', 'Sunitha M', '9495614411', 'ECE', 1, (SELECT id FROM rooms WHERE room_number = 'F42'), @pwd_hash, TRUE),
(1, '2025B297', 'Diya Fathima M', 'diyafaathimaa.m@gmail.com', '7902488390', 'Sajeena .M.C', '8547488390', 'EEE', 1, (SELECT id FROM rooms WHERE room_number = 'F23'), @pwd_hash, TRUE),
(1, '2024B128', 'Rishika S Naveen', 'rishikavariambath@gmail.com', '8606746118', 'Naveenkumar V', '9446268162', 'CSE', 2, (SELECT id FROM rooms WHERE room_number = 'S47'), @pwd_hash, TRUE),
(1, '2025B578', 'Gouri Parvathi T.M', 'gouri.madhav07@gmail.com', '9744556577', 'Suresh T.M', '7994224455', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F11'), @pwd_hash, TRUE),
(1, '2025B473', 'Vismaya', 'vismayamanoj2006@gmail.com', '8590185611', 'Seema', '9495607709', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F36'), @pwd_hash, TRUE),
(1, '2025B505', 'Devanivedhya C P', 'devanivedhyacp@gmail.com', '9778155539', 'Ramesan C P', '9207024483', 'CSE', 1, (SELECT id FROM rooms WHERE room_number = 'F36'), @pwd_hash, TRUE),
(1, '2023B206', 'Shiya sajeesh mv', 'shiyasajeesh@gmail.com', '', 'Sajeesh mv', '9778308169', 'ECE', 3, (SELECT id FROM rooms WHERE room_number = 'S52'), @pwd_hash, TRUE),
(1, '2023B215', 'Lulu mol kunnath', 'lulukunnathh@gmail.com', '9567234835', 'Abdul azee', '9895499370', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S49'), @pwd_hash, TRUE),
(1, '2023B265', 'Lubna Rahiman', 'lubnarahiman123@gmail.com', '9074104987', 'Abdul Rahiman', '9496357983', 'Civil Engineering', 3, (SELECT id FROM rooms WHERE room_number = 'S45'), @pwd_hash, TRUE),
(1, '2023B092', 'Haritha Hareesh', 'harithahareesh7@gmail.com', '9605905358', 'Ramya Hareesh', '9744616195', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S29'), @pwd_hash, TRUE),
(1, '2024BL027', 'NEETHU K', 'kneethu606@gmail.com', '8593942156', 'Pushpamma. C', '6238953068', 'Civil Engineering', 3, (SELECT id FROM rooms WHERE room_number = 'S10'), @pwd_hash, TRUE),
(1, '2023B268', 'Vismaya M', 'vvismaya908@gmail.com', '7034674539', 'Mani .S', '9495486950', 'EEE', 3, (SELECT id FROM rooms WHERE room_number = 'S33'), @pwd_hash, TRUE),
(1, '2023B184', 'Nandana PR', 'nandanapr18@gmail.com', '7012292018', 'Prakasan komath', '8089002485', 'ECE', 3, (SELECT id FROM rooms WHERE room_number = 'S32'), @pwd_hash, TRUE),
(1, '2023B157', 'Neenu Josey', 'noonu7025@gmail.com', '7025808618', 'Shyji Josey', '8714480676', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S28'), @pwd_hash, TRUE),
(1, '2023B185', 'Karthika Suresh', 'karthus2005@gmail.com', '8590358489', 'Veena Suresh', '9605173208', 'ECE', 3, (SELECT id FROM rooms WHERE room_number = 'S43'), @pwd_hash, TRUE),
(1, '2023B128', 'Jyothika J', 'jyothikaj0015@gmail.com', '9447776189', 'Jayadevan C K', '9846318189', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S52'), @pwd_hash, TRUE),
(1, '2024BL014', 'NANDANA P K', 'nandanapk078@gmail.com', '9778795067', 'Narayanan P k', '9447583078', 'Civil Engineering', 3, (SELECT id FROM rooms WHERE room_number = 'S11'), @pwd_hash, TRUE),
(1, '2023B261', 'Ridha shirin', 'ridhashirin59@gmail.com', '9072073028', 'Musthafa', '9447383027', 'Civil Engineering', 3, (SELECT id FROM rooms WHERE room_number = 'S45'), @pwd_hash, TRUE),
(1, '2023B359', 'Sanjana Rajesh CM', 'sunitharajeshcm@gmail.com', '9744208044', 'Sunitha CM', '9061208044', 'Civil Engineering', 3, (SELECT id FROM rooms WHERE room_number = 'S34'), @pwd_hash, TRUE),
(1, '2023B243', 'Fidha K Naufal', 'fidhaknaufal07@gmail.com', '8547641346', 'Aysha Ramsi', '9746770444', 'Civil Engineering', 3, (SELECT id FROM rooms WHERE room_number = 'S28'), @pwd_hash, TRUE),
(1, '2023B188', 'Nandana.M.K', 'nandanamk950@gmail.com', '8943394228', 'Divya Sajith', '9946569422', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S16'), @pwd_hash, TRUE),
(1, '2024BL097', 'DRISYA K R', 'drisyakr27@gmail.com', '7306547923', 'KRISHNADASAN P', '9946647825', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S15'), @pwd_hash, TRUE),
(1, '2023B220', 'A K Diya', 'akdiya2504@gmail.com', '9072143924', 'Divya PS', '97333165220', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S33'), @pwd_hash, TRUE),
(1, '2023B052', 'Naveena A', 'naveenamadikai8@gmail.com', '9188162219', 'Anilkumar k', '9447730391', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S38'), @pwd_hash, TRUE),
(1, '2023B344', 'Neshwa S', 'sujasneshwa@gmail.com', '9188025349', 'Thahasil J M', '9496922069', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S39'), @pwd_hash, TRUE),
(1, '2023B069', 'Archana S Lal', 'archanaslal2004@gmail.com', '9526790986', 'Manilal S', '7510390986', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S54'), @pwd_hash, TRUE),
(1, '2023B117', 'Hidha Riyas', 'hidhariyaskk@gmail.con', '7510518808', 'Riyas KK', '7034474352', 'CSE', 3, (SELECT id FROM rooms WHERE room_number = 'S54'), @pwd_hash, TRUE);

-- --------------------------------------------------
-- 5. UPDATE STUDENT PASSWORD HASHES
-- --------------------------------------------------

UPDATE students 
SET password_hash = '$2a$10$ebEm8uRxx7MywVeXWCd3quIFpwZyLygkxKz4Kk68xyVPZ8GrB1UvS'
WHERE password_hash IS NULL OR password_hash = '';

-- --------------------------------------------------
-- 6. UPDATE ROOM OCCUPANCY
-- --------------------------------------------------

UPDATE rooms r
SET current_occupancy = (
    SELECT COUNT(*) FROM students s WHERE s.room_id = r.id AND s.is_active = 1
),
status = CASE
    WHEN (
        SELECT COUNT(*) FROM students s WHERE s.room_id = r.id AND s.is_active = 1
    ) >= r.capacity THEN 'occupied'
    ELSE 'available'
END
WHERE r.hostel_id = 1;

-- --------------------------------------------------
-- 7. FEE STRUCTURE
-- --------------------------------------------------

DELETE FROM fee_structure WHERE hostel_id = 1;

INSERT INTO fee_structure
(hostel_id, fee_type, amount, period, effective_from, is_active)
VALUES
(1, 'Hostel Fee', 5000.00, 'monthly', CURDATE(), TRUE),
(1, 'Mess Fee', 3500.00, 'monthly', CURDATE(), TRUE);

-- --------------------------------------------------
-- 8. MESS SECRETARY
-- --------------------------------------------------

INSERT INTO mess_secretaries (name, email, password_hash) 
VALUES ('Mess Secretary', 'mess@lbscek.ac.in', '$2a$10$ebEm8uRxx7MywVeXWCd3quIFpwZyLygkxKz4Kk68xyVPZ8GrB1UvS')
ON DUPLICATE KEY UPDATE name=VALUES(name), password_hash=VALUES(password_hash);

SET FOREIGN_KEY_CHECKS = 1;
