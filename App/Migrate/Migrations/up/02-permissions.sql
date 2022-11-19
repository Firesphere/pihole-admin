USER:CREATE TABLE IF NOT EXISTS permission (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL);
USER:CREATE TABLE IF NOT EXISTS section (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT UNIQUE NOT NULL);
USER:CREATE TABLE IF NOT EXISTS user_permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, permission_id INTEGER, user_id INTEGER);
USER:CREATE TABLE IF NOT EXISTS permission_section (id INTEGER PRIMARY KEY AUTOINCREMENT, permission_id INTEGER, section_id INTEGER);
USER:INSERT INTO section (title) VALUES ('dashboard'),('dns'),('group'),('longterm'),('queries'),('settings'),('tools');
USER:INSERT INTO permission (name) VALUES ('admin');
USER:INSERT INTO user_permissions (permission_id, user_id) VALUES (1,1);
USER:INSERT INTO permission_section (permission_id, section_id) VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7);