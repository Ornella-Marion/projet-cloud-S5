CREATE TABLE users(
   id SERIAL,
   email VARCHAR(255)  NOT NULL,
   password VARCHAR(255)  NOT NULL,
   name VARCHAR(255)  NOT NULL,
   role VARCHAR(50)  DEFAULT 'user' CHECK(role IN('visitor', 'user', 'manager')),
   is_active BOOLEAN DEFAULT true,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY(id),
   UNIQUE(email)
);

CREATE TABLE login_attempts(
   id SERIAL,
   email VARCHAR(255)  NOT NULL,
   attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   success BOOLEAN DEFAULT false,
   user_id INTEGER,
   PRIMARY KEY(id),
   FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE sessions(
   id SERIAL,
   token VARCHAR(500)  NOT NULL,
   refresh_token VARCHAR(500) ,
   expires_at TIMESTAMP NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   is_active BOOLEAN DEFAULT true,
   PRIMARY KEY(id),
   UNIQUE(token),
   UNIQUE(refresh_token)
);

CREATE TABLE account_locks(
   id SERIAL,
   locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   unlock_at TIMESTAMP,
   reason VARCHAR(255)  DEFAULT 'Too many failed login attempts',
   id_1 INTEGER NOT NULL,
   PRIMARY KEY(id),
   FOREIGN KEY(id_1) REFERENCES users(id)
);

CREATE TABLE roads(
   id INTEGER,
   designation VARCHAR(50)  NOT NULL,
   longitude NUMERIC(5,2)   NOT NULL,
   latitude NUMERIC(5,2)   NOT NULL,
   area NUMERIC(10,2)   NOT NULL,
   PRIMARY KEY(id)
);

CREATE TABLE entrerprises(
   id INTEGER,
   designation VARCHAR(50)  NOT NULL,
   PRIMARY KEY(id)
);

CREATE TABLE status(
   id INTEGER,
   label VARCHAR(50)  NOT NULL,
   percentage NUMERIC(5,2)  ,
   PRIMARY KEY(id)
);

CREATE TABLE roadworks(
   id NUMERIC(10,2)  ,
   created_at TIMESTAMP NOT NULL,
   budget NUMERIC(10,2)   NOT NULL,
   finished_at TIMESTAMP NOT NULL,
   status_id INTEGER NOT NULL,
   road_id INTEGER NOT NULL,
   enterprise_id INTEGER NOT NULL,
   PRIMARY KEY(id),
   UNIQUE(road_id),
   FOREIGN KEY(status_id) REFERENCES status(id),
   FOREIGN KEY(road_id) REFERENCES roads(id),
   FOREIGN KEY(enterprise_id) REFERENCES entrerprises(id)
);

CREATE TABLE roadwork_status(
   roadwork_id NUMERIC(10,2)  ,
   status_id INTEGER,
   updated_at TIMESTAMP NOT NULL,
   PRIMARY KEY(roadwork_id, status_id),
   FOREIGN KEY(roadwork_id) REFERENCES roadworks(id),
   FOREIGN KEY(status_id) REFERENCES status(id)
);

CREATE TABLE reporting(
   id SERIAL,
   report_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   description TEXT  NOT NULL,
   road_id INTEGER NOT NULL,
   user_id INTEGER NOT NULL,
   target_type VARCHAR(50) NOT NULL,
   PRIMARY KEY(id),
   FOREIGN KEY(road_id) REFERENCES roads(id),
   FOREIGN KEY(user_id) REFERENCES users(id)
);
