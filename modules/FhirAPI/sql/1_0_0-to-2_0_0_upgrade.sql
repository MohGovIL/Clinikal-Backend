--
--  Comment Meta Language Constructs:
--
--  #IfNotTable
--    argument: table_name
--    behavior: if the table_name does not exist,  the block will be executed

--  #IfTable
--    argument: table_name
--    behavior: if the table_name does exist, the block will be executed

--  #IfColumn
--    arguments: table_name colname
--    behavior:  if the table and column exist,  the block will be executed

--  #IfMissingColumn
--    arguments: table_name colname
--    behavior:  if the table exists but the column does not,  the block will be executed

--  #IfNotColumnType
--    arguments: table_name colname value
--    behavior:  If the table table_name does not have a column colname with a data type equal to value, then the block will be executed

--  #IfNotRow
--    arguments: table_name colname value
--    behavior:  If the table table_name does not have a row where colname = value, the block will be executed.

--  #IfNotRow2D
--    arguments: table_name colname value colname2 value2
--    behavior:  If the table table_name does not have a row where colname = value AND colname2 = value2, the block will be executed.

--  #IfNotRow3D
--    arguments: table_name colname value colname2 value2 colname3 value3
--    behavior:  If the table table_name does not have a row where colname = value AND colname2 = value2 AND colname3 = value3, the block will be executed.

--  #IfNotRow4D
--    arguments: table_name colname value colname2 value2 colname3 value3 colname4 value4
--    behavior:  If the table table_name does not have a row where colname = value AND colname2 = value2 AND colname3 = value3 AND colname4 = value4, the block will be executed.

--  #IfNotRow2Dx2
--    desc:      This is a very specialized function to allow adding items to the list_options table to avoid both redundant option_id and title in each element.
--    arguments: table_name colname value colname2 value2 colname3 value3
--    behavior:  The block will be executed if both statements below are true:
--               1) The table table_name does not have a row where colname = value AND colname2 = value2.
--               2) The table table_name does not have a row where colname = value AND colname3 = value3.

--  #IfRow2D
--    arguments: table_name colname value colname2 value2
--    behavior:  If the table table_name does have a row where colname = value AND colname2 = value2, the block will be executed.

--  #IfRow3D
--        arguments: table_name colname value colname2 value2 colname3 value3
--        behavior:  If the table table_name does have a row where colname = value AND colname2 = value2 AND colname3 = value3, the block will be executed.

--  #IfIndex
--    desc:      This function is most often used for dropping of indexes/keys.
--    arguments: table_name colname
--    behavior:  If the table and index exist the relevant statements are executed, otherwise not.

--  #IfNotIndex
--    desc:      This function will allow adding of indexes/keys.
--    arguments: table_name colname
--    behavior:  If the index does not exist, it will be created

--  #EndIf
--    all blocks are terminated with a #EndIf statement.

--  #IfNotListReaction
--    Custom function for creating Reaction List

--  #IfNotListOccupation
--    Custom function for creating Occupation List

--  #IfTextNullFixNeeded
--    desc: convert all text fields without default null to have default null.
--    arguments: none

--  #IfTableEngine
--    desc:      Execute SQL if the table has been created with given engine specified.
--    arguments: table_name engine
--    behavior:  Use when engine conversion requires more than one ALTER TABLE

--  #IfInnoDBMigrationNeeded
--    desc: find all MyISAM tables and convert them to InnoDB.
--    arguments: none
--    behavior: can take a long time.

--  #IfTranslationNeeded
--    desc: find all MyISAM tables and convert them to InnoDB.
--    arguments: constant_name english hebrew
--    behavior: can take a long time.

REPLACE INTO `list_options` (`list_id`, `option_id`, `title`, `seq`,`mapping` ,`notes`, `activity`,`subtype`) VALUES
('loinc_org', '8480-6', 'Systolic blood pressure', 10,'bps','{"placeholder": "___" ,"label":"Blood pressure", "mask": "###/###"}', 1,'mmHg'),
('loinc_org', '8462-4', 'Diastolic blood pressure', 20,'bpd','{"placeholder": "___","label":"Blood pressure", "mask": "###/###"}', 1,'mmHg'),
('loinc_org', '8308-9', 'Body height --standing', 30,'height','{"label": "Height","placeholder":"___"}', 1,'cm'),
('loinc_org', '8335-2', 'Body weight Estimated', 40,'weight','{"label": "Weight","placeholder":"___._"}', 1,'Kg'),
('loinc_org', '69000-8','Heart rate --sitting', 50,'pulse','{"label": "Pulse","placeholder": "___"}', 1,'PRA'),
('loinc_org', '8310-5', 'Body temperature', 60,'temperature','{"label": "Fever","placeholder": "__._","mask": "##.#"}', 1,'C'),
('loinc_org', '8327-9', 'Body temperature measurement site', 70,'temp_method','', 1,''),
('loinc_org', '20564-1', 'Oxygen saturation in Blood', 80,'oxygen_saturation','{"label": "Saturation","placeholder": "___", "symbol": "%"}', 1,'%'),
('loinc_org', '39156-5', 'Body mass index (BMI) [Ratio]', 90,'BMI','', 1,'kg/m2'),
('loinc_org', '59574-4', 'Body mass index (BMI) [Percentile]', 100,'BMI_status','', 1,''),
('loinc_org', '8280-0', 'Waist Circumference at umbilicus by Tape measure', 110,'waist_circ','', 1,'cm'),
('loinc_org', '8287-5', 'Head Occipital-frontal circumference by Tape measure', 120,'head_circ','', 1,'cm'),
('loinc_org', '9303-9', 'Respiratory rate --resting', 130,'respiration','{"label": "Breaths per minute","placeholder": "__"}', 1,'BPM'),
('loinc_org', '72514-3', 'Pain severity - 0-10 verbal numeric rating [Score] - Reported', 140,'pain_severity','{"label": "Pain level","placeholder": "__"}', 1,''),
('loinc_org', '74774-1', 'Glucose [Mass/volume] in Serum, Plasma or Blood', 150,'glucose','{"label": "Blood sugar","placeholder": "___"}', 1,'mg/dL');

#IfMissingColumn fhir_value_sets language
ALTER TABLE fhir_value_sets ADD `language` VARCHAR(3) NOT NULL DEFAULT 'en';
#EndIf
