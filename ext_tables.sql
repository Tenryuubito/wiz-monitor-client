CREATE TABLE tx_wizmonitorclient_error (
	uid int(11) unsigned NOT NULL auto_increment,
	fingerprint varchar(64) DEFAULT '' NOT NULL,
	level varchar(16) DEFAULT '' NOT NULL,
	channel varchar(255) DEFAULT '' NOT NULL,
	message text,
	exception_class varchar(255) DEFAULT '' NOT NULL,
	exception_code varchar(64) DEFAULT '' NOT NULL,
	file varchar(1024) DEFAULT '' NOT NULL,
	line int(11) unsigned DEFAULT '0' NOT NULL,
	request_url varchar(1024) DEFAULT '' NOT NULL,
	occurrences int(11) unsigned DEFAULT '0' NOT NULL,
	first_seen int(11) unsigned DEFAULT '0' NOT NULL,
	last_seen int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY fingerprint (fingerprint),
	KEY last_seen (last_seen)
);
