
-- INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`, `timestamp`) VALUES ('apps', 'react-dev', '../client-app/dev-mode/build', '100', '1', '0', '', NULL, '', '0', '0', '1', '', '1', '2017-07-23 09:33:02');
-- UPDATE globals SET gl_value = 'style_clinikal_generic.css' WHERE gl_name = 'css_header';
UPDATE globals SET gl_value = '1' WHERE gl_name = 'rest_api';
REPLACE INTO `globals` (`gl_name`, `gl_index`, `gl_value`) VALUES ('oauth_password_grant', '0', '1');

ALTER TABLE `patient_data`
ADD `mh_house_no` VARCHAR(10) NOT NULL AFTER `guardianemail`,
ADD `mh_pobox` VARCHAR(20) NOT NULL AFTER `mh_house_no`,
ADD `mh_type_id` VARCHAR(20) NOT NULL AFTER `mh_pobox`,
ADD `mh_english_name` VARCHAR(50) NOT NULL AFTER `mh_type_id`,
ADD `mh_insurance_organiz` VARCHAR(20) NOT NULL AFTER `mh_english_name`;


REPLACE INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`, `timestamp`) VALUES
('lists', 'mh_cities', 'moh cities', 311, 1, 0, '', '', '', 0, 0, 1, '', 1, '2017-03-02 07:07:44');


REPLACE INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`, `timestamp`) VALUES
('lists', 'mh_streets', 'moh streets', 312, 1, 0, '', '', '', 0, 0, 1, '', 1, '2017-03-02 07:07:44');

-- ALTER TABLE patient_data ADD COLUMN IF NOT EXISTS column_a VARCHAR(255);



DELETE FROM `list_options` WHERE `list_id` like "userlist3";
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`,`activity`)
VALUES
('userlist3', 'teudat_zehut', 'Teudat zehut', '10', '1', '0','','1'),
('userlist3', 'passport', 'Passport', '20', '0', '0','', '1'),
('userlist3', 'temporary', 'Temporary', '30', '0', '0','' ,'1');


CREATE TABLE `clinikal_patient_tracking_changes` (
  `facility_id` int(11) NOT NULL,
  `update_date` datetime NOT NULL DEFAULT current_timestamp,
   PRIMARY KEY (`facility_id`)
) ;

CREATE TABLE `clinikal_templates_map` (
  `form_id` varchar(50) NOT NULL COMMENT 'FK registry -> directory',
  `form_field` varchar(50) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `reason_code` varchar(50) NOT NULL,
  `message_id` varchar(50) NOT NULL,
  `seq` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `clinikal_templates_map`
  ADD PRIMARY KEY (`form_id`,`form_field`,`service_type`,`reason_code`,`message_id`);

ALTER TABLE `clinikal_templates_map` ADD `active` tinyint(1) NOT NULL DEFAULT 1;
ALTER TABLE `clinikal_templates_map` ADD `update_by` int(11)  NOT NULL;
ALTER TABLE `clinikal_templates_map` ADD `update_date` datetime NOT NULL DEFAULT current_timestamp;




CREATE TABLE `form_context_map` (
    `form_id`           INT NOT NULL,
    `context_type`      varchar(255) NOT NULL COMMENT 'reason_code / service_type',
    `context_id`        INT NOT NULL,
    PRIMARY KEY (`form_id`,`context_type`,`context_id`)
);



INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`) VALUES
('lists', 'clinikal_templates', 'Clinikal templates', 0, 0, 0, '', '', '', 0, 0, 1, '', 1);


ALTER TABLE `registry` ADD `component_name` VARCHAR(255) NULL AFTER `aco_spec`;


DELETE FROM list_options where list_id="drug_route";
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`) VALUES
('drug_route', '1', 'Per oris', 10, 1),
('drug_route', '2', 'To skin', 20, 1),
('drug_route', '3', 'Per nostril', 30, 1),
('drug_route', '4', 'Both ears', 40, 1),
('drug_route', '5', 'Other', 50, 1);



DELETE FROM list_options where list_id="drug_interval";
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`) VALUES
('drug_interval', '1', 'Once a day', 10, 1),
('drug_interval', '2', 'Twice a day', 20, 1),
('drug_interval', '3', '3 times a day', 30, 1),
('drug_interval', '4', '4 times a day', 40, 1),
('drug_interval', '5', 'Every hour', 50, 1),
('drug_interval', '6', 'Every 3 hours', 60, 1),
('drug_interval', '7', 'Every 4 hours', 70, 1),
('drug_interval', '8', 'Every 5 hours', 80, 1),
('drug_interval', '9', 'Every 6 hours', 90, 1),
('drug_interval', '10', 'Every 8 hours', 100, 1),
('drug_interval', '11', 'Before eating', 110, 1),
('drug_interval', '12', 'After eating', 120, 1),
('drug_interval', '13', 'Before noon', 130, 1),
('drug_interval', '14', 'Afternoon', 140, 1),
('drug_interval', '15', 'Before bedtime', 150, 1),
('drug_interval', '16', 'As needed', 160, 1);


DELETE FROM list_options where list_id="drug_form";
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`) VALUES
('drug_form', '1', 'Tablet', 10, 1),
('drug_form', '2', 'Drops', 20, 1),
('drug_form', '3', 'Tsp', 30, 1),
('drug_form', '4', 'ml', 40, 1),
('drug_form', '5', 'Ointment', 50, 1),
('drug_form', '6', 'Cream', 60, 1),
('drug_form', '7', 'Solution', 70, 1),
('drug_form', '8', 'Suspension', 80, 1);


CREATE TABLE form_commitment_questionnaire(
    id bigint(20) NOT NULL AUTO_INCREMENT,
    encounter varchar(255) DEFAULT NULL,
    form_id bigint(20) NOT NULL,
    question_id int(11) NOT NULL,
    answer text,
    PRIMARY KEY (`id`)
);

ALTER TABLE `form_commitment_questionnaire` ADD UNIQUE `unique_index`( `form_id`, `question_id`);

INSERT INTO `fhir_questionnaire` (`name`, `directory`, `state`, `aco_spec`) VALUES
('Commitment questionnaire', 'commitment_questionnaire', '1', 'encounters|notes');

INSERT INTO `questionnaires_schemas` (`qid`, `form_name`,`form_table`, `column_type`, `question`)
VALUES
('1', 'commitment_questionnaire','form_commitment_questionnaire', 'integer', 'Commitment number'),
('2', 'commitment_questionnaire','form_commitment_questionnaire', 'date', 'Commitment date'),
('3', 'commitment_questionnaire','form_commitment_questionnaire', 'date', 'Commitment expiration date'),
('4', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Signing doctor'),
('5', 'commitment_questionnaire','form_commitment_questionnaire', 'integer', 'doctor license number'),
('6', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Payment amount'),
('7', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Payment method'),
('8', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Receipt number'),
('9', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Exemption reason'),
('10', 'commitment_questionnaire','form_commitment_questionnaire', 'string', 'Comment');


INSERT INTO `fhir_value_sets` (`id`, `title`)
VALUES ('drugs_list', 'Drugs list');

INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`,`filter`)
VALUES ('drugs_list', '9911', 'Codes', NULL);


CREATE TABLE manage_templates_letters(
    id int(11) NOT NULL AUTO_INCREMENT,
    `letter_name` varchar(255) NOT NULL,
    `letter_class` varchar(255) NOT NULL,
    `letter_class_action` varchar(255) NOT NULL,
    `active` tinyint(1) NOT NULL,
    `letter_post_json` mediumtext DEFAULT NULL,
    PRIMARY KEY (`id`)
);



INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`) VALUES
('lists', 'clinikal_form_fields_templates', 'Form fields that use templates', 0, 0, 0, '', '', '', 0, 0, 1, '', 1);

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`) VALUES
('lists', 'clinikal_no_payment_reason', 'Reasons for encounter without payment', 0, 1),
('clinikal_no_payment_reason', 'personal', 'Personal', 20, 1),
('clinikal_no_payment_reason', 'followup_encounter', 'Follow up encounter', 30, 1);

INSERT INTO `fhir_value_sets` (`id`, `title`)
VALUES ('no_payment_reasons', 'Reasons for encounter without payment');

INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`,`filter`)
VALUES ('no_payment_reasons', 'clinikal_no_payment_reason', 'All', NULL);
