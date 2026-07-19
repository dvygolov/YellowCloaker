PRAGMA foreign_keys = on;
BEGIN IMMEDIATE;
CREATE TABLE campaigns
(
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	name TEXT NOT NULL,
	settings TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS clicks (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	campaign_id INTEGER,
	time INTEGER NOT NULL,
	ip TEXT NOT NULL,
	country TEXT,
	lang TEXT,
	os TEXT,
	osver REAL,
	device TEXT,
	brand TEXT,
	model TEXT,
	isp TEXT,
	client TEXT,
	clientver REAL,
	ua TEXT,
	userid TEXT NOT NULL,
	unique_hash BLOB NULL,
	unique_flags INTEGER NULL CHECK (unique_flags IS NULL OR unique_flags BETWEEN 0 AND 3),
	clickid TEXT NOT NULL,
	flow TEXT,
	path TEXT DEFAULT '[]',
	step INTEGER DEFAULT 0,
	events TEXT DEFAULT '{}',
	params TEXT,
	leaddata TEXT,
	status TEXT,
	cost NUMERIC DEFAULT 0,
	payout NUMERIC DEFAULT 0,
    FOREIGN KEY (
        campaign_id
    )
    REFERENCES campaigns (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_camp_time ON clicks (campaign_id,time);
CREATE INDEX IF NOT EXISTS idx_camp_time_status ON clicks (campaign_id,time,status);
CREATE INDEX IF NOT EXISTS idx_userid ON clicks (userid);
CREATE UNIQUE INDEX IF NOT EXISTS idx_clickid ON clicks (clickid);
CREATE INDEX IF NOT EXISTS idx_camp_flow ON clicks (campaign_id,flow);
CREATE INDEX IF NOT EXISTS idx_camp_flow_step ON clicks (campaign_id,flow,step);
CREATE INDEX IF NOT EXISTS idx_unique_campaign_hash ON clicks (campaign_id,unique_hash,time DESC)
    WHERE unique_flags IS NOT NULL AND unique_hash IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_unique_flow_hash ON clicks (campaign_id,flow,unique_hash,time DESC)
    WHERE unique_flags IS NOT NULL AND unique_hash IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_unique_campaign_cookie ON clicks (campaign_id,userid,time DESC)
    WHERE unique_flags IS NOT NULL AND userid <> '';
CREATE INDEX IF NOT EXISTS idx_unique_flow_cookie ON clicks (campaign_id,flow,userid,time DESC)
    WHERE unique_flags IS NOT NULL AND userid <> '';

CREATE TABLE IF NOT EXISTS click_event_log (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	clickid TEXT NOT NULL,
	time INTEGER NOT NULL,
	step_index INTEGER NOT NULL,
	event_name TEXT NOT NULL,
	event_value NUMERIC NOT NULL,
	FOREIGN KEY (clickid) REFERENCES clicks (clickid) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_event_clickid_time ON click_event_log (clickid,time);
CREATE INDEX IF NOT EXISTS idx_event_name_time ON click_event_log (event_name,time);

CREATE INDEX IF NOT EXISTS idx_country ON clicks (country);
CREATE INDEX IF NOT EXISTS idx_lang ON clicks (lang);
CREATE INDEX IF NOT EXISTS idx_os ON clicks (os);
CREATE INDEX IF NOT EXISTS idx_osver ON clicks (osver);
CREATE INDEX IF NOT EXISTS idx_brand ON clicks (brand);
CREATE INDEX IF NOT EXISTS idx_model ON clicks (model);
CREATE INDEX IF NOT EXISTS idx_device ON clicks (device);
CREATE INDEX IF NOT EXISTS idx_isp ON clicks (isp);
CREATE INDEX IF NOT EXISTS idx_client ON clicks (client);
CREATE INDEX IF NOT EXISTS idx_clientver ON clicks (clientver);
CREATE INDEX IF NOT EXISTS idx_step ON clicks (step);

CREATE TABLE IF NOT EXISTS click_steps (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	clickid TEXT NOT NULL,
	step INTEGER NOT NULL,
	variant TEXT NOT NULL,
	time INTEGER NOT NULL,
	UNIQUE (clickid, step),
	FOREIGN KEY (clickid) REFERENCES clicks (clickid) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_click_steps_clickid_step ON click_steps (clickid,step);
CREATE INDEX IF NOT EXISTS idx_click_steps_step_variant ON click_steps (step,variant);

CREATE TABLE IF NOT EXISTS blocked (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	campaign_id INTEGER,
	time INTEGER NOT NULL,
	ip TEXT NOT NULL,
	country TEXT,
	lang TEXT,
	os TEXT,
	osver REAL,
	device TEXT,
	brand TEXT,
	model TEXT,
	isp TEXT,
	client TEXT,
	clientver REAL,
	ua TEXT,
	params TEXT,
	reason TEXT,
	FOREIGN KEY (
        campaign_id
    )
    REFERENCES campaigns (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_bcamp_time ON blocked (campaign_id,time);
CREATE INDEX IF NOT EXISTS idx_bcountry ON blocked (country);
CREATE INDEX IF NOT EXISTS idx_bos ON blocked (os);
CREATE INDEX IF NOT EXISTS idx_bdevice ON blocked (device);
CREATE INDEX IF NOT EXISTS idx_bisp ON blocked (isp);
CREATE INDEX IF NOT EXISTS idx_breason ON blocked (reason);
CREATE INDEX IF NOT EXISTS idx_bip ON blocked (ip);
CREATE INDEX IF NOT EXISTS idx_bparams ON blocked (params);

CREATE TABLE IF NOT EXISTS trafficback (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	time INTEGER NOT NULL,
	ip TEXT NOT NULL,
	country TEXT,
	lang TEXT,
	os TEXT,
	osver REAL,
	device TEXT,
	brand TEXT,
	model TEXT,
	isp TEXT,
	client TEXT,
	clientver REAL,
	ua TEXT,
	params TEXT
);
CREATE INDEX IF NOT EXISTS idx_tbtime ON trafficback (time);
CREATE INDEX IF NOT EXISTS idx_tbcountry ON trafficback (country);
CREATE INDEX IF NOT EXISTS idx_tbos ON trafficback (os);
CREATE INDEX IF NOT EXISTS idx_tbdevice ON trafficback (device);
CREATE INDEX IF NOT EXISTS idx_tbisp ON trafficback (isp);
CREATE INDEX IF NOT EXISTS idx_tbip ON trafficback (ip);
CREATE INDEX IF NOT EXISTS idx_tbparams ON trafficback (params);

CREATE TABLE IF NOT EXISTS common (
    settings TEXT
);
COMMIT TRANSACTION;
PRAGMA foreign_keys = on;
PRAGMA journal_mode = wal;
PRAGMA synchronous = normal;
PRAGMA cache_size = -32000;
PRAGMA mmap_size = 268435456;
