ALTER TABLE wcf1_uzbot ADD lexiconModificationData		TEXT;

ALTER TABLE wcf1_uzbot ADD lexiconCountAction			VARCHAR(15);
ALTER TABLE wcf1_uzbot ADD topLexiconCount				INT(10) DEFAULT 1;
ALTER TABLE wcf1_uzbot ADD topLexiconInterval			TINYINT(1) DEFAULT 1;
ALTER TABLE wcf1_uzbot ADD topLexiconNext				INT(10) DEFAULT 0;

ALTER TABLE wcf1_uzbot ADD lexiconChangeUpdate			TINYINT(1) DEFAULT 1;
ALTER TABLE wcf1_uzbot ADD lexiconChangeDelete			TINYINT(1) DEFAULT 1;
