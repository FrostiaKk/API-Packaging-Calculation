BEGIN;
INSERT INTO packaging (id, external_id, width, height, length, max_weight) VALUES (1, 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d', 2.5, 3.0, 1.0, 20);
INSERT INTO packaging (id, external_id, width, height, length, max_weight) VALUES (2, 'b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e', 4.0, 4.0, 4.0, 20);
INSERT INTO packaging (id, external_id, width, height, length, max_weight) VALUES (3, 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f', 2.0, 2.0, 10.0, 20);
INSERT INTO packaging (id, external_id, width, height, length, max_weight) VALUES (4, 'd4e5f6a7-b8c9-4d0e-1f2a-3b4c5d6e7f8a', 5.5, 6.0, 7.5, 30);
INSERT INTO packaging (id, external_id, width, height, length, max_weight) VALUES (5, 'e5f6a7b8-c9d0-4e1f-2a3b-4c5d6e7f8a9b', 9.0, 9.0, 9.0, 30);
COMMIT;
