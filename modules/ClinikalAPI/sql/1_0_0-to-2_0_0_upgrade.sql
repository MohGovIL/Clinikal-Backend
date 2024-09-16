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

#IfMissingColumn clinikal_templates_map active
ALTER TABLE `clinikal_templates_map` ADD `active` tinyint(1) NOT NULL DEFAULT 1;
#EndIf

#IfMissingColumn clinikal_templates_map update_by
ALTER TABLE `clinikal_templates_map` ADD `update_by` int(11)  NOT NULL;
#EndIf

#IfMissingColumn clinikal_templates_map update_date
ALTER TABLE `clinikal_templates_map` ADD `update_date` datetime NOT NULL DEFAULT current_timestamp;
#EndIf


#IfNotRow2D questionnaires_schemas form_name commitment_questionnaire qid 9
INSERT INTO `questionnaires_schemas` (`qid`, `form_name`,`form_table`, `column_type`, `question`)
VALUES
('9', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Exemption reason'),
('10', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Comment');
#EndIf

#IfNotRow2D list_options list_id lists option_id clinikal_no_payment_reason
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`) VALUES
('lists', 'clinikal_no_payment_reason', 'Reasons for encounter without payment', 0, 1),
('clinikal_no_payment_reason', 'personal', 'Personal', 20, 1),
('clinikal_no_payment_reason', 'followup_encounter', 'Follow up encounter', 30, 1);
#EndIf

#IfNotRow fhir_value_sets id no_payment_reasons
INSERT INTO `fhir_value_sets` (`id`, `title`)
VALUES ('no_payment_reasons', 'Reasons for encounter without payment');
#EndIf

#IfNotRow2D fhir_value_set_systems vs_id no_payment_reasons system clinikal_no_payment_reason
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`,`filter`)
VALUES ('no_payment_reasons', 'clinikal_no_payment_reason', 'All', NULL);
#EndIf
