UPDATE globals SET gl_value = '1' WHERE gl_name = 'rest_fhir_api';

CREATE TABLE `fhir_healthcare_services` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `active` BOOLEAN NOT NULL DEFAULT 1,
    `providedBy` INT NOT NULL,
    `category` INT NOT NULL,
    `type` INT NOT NULL,
    `name` VARCHAR(125) NOT NULL,
    `comment` TEXT,
    `extraDetails` TEXT,
    `availableTime` JSON,
    `notAvailable` JSON,
    `availabilityExceptions` TEXT,
    CONSTRAINT time_json CHECK (Json_valid(`availableTime`)),
    CONSTRAINT tn_avlbl_json CHECK (Json_valid(`notAvailable`)),
    PRIMARY KEY (`id`)
) ENGINE = innodb;

-- fhir routing rest api builders table

 CREATE TABLE `fhir_rest_elements` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `active` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `fhir_rest_elements`
ADD PRIMARY KEY (`id`);

ALTER TABLE `fhir_rest_elements`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE encounter_reasoncode_map (
eid INT(6) UNSIGNED,
reason_code  INT(6) UNSIGNED
);

CREATE TABLE questionnaires_schemas(
    qid int(11) NOT NULL AUTO_INCREMENT,
    form_name varchar(255) NOT NULL,
    form_table varchar(255) NOT NULL,
    column_name varchar(255) DEFAULT NULL,
    column_type varchar(255) NOT NULL,
    question varchar(255) DEFAULT NULL,
    PRIMARY KEY (`qid`,`form_name`)
);


CREATE TABLE `questionnaire_response`(
    id bigint(20) NOT NULL AUTO_INCREMENT,
    form_name varchar(255) NOT NULL,
    encounter bigint(20) NOT NULL,
    subject bigint(20) NOT NULL,
    subject_type VARCHAR(255) NOT NULL DEFAULT 'Patient',
    create_date datetime DEFAULT current_timestamp,
    update_date datetime DEFAULT current_timestamp,
    create_by bigint(20) NOT NULL,
    update_by bigint(20) NOT NULL,
    source  bigint(20) NOT NULL,
    source_type VARCHAR(255) NOT NULL DEFAULT 'Patient',
    status  varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
);


CREATE TABLE `fhir_value_sets` (
    `id` VARCHAR(125) NOT NULL,
    `title` VARCHAR(125) NOT NULL,
    `status` ENUM('active', 'retired') NOT NULL DEFAULT 'active',
    `language` VARCHAR(3) NOT NULL DEFAULT 'en',
    PRIMARY KEY(`id`)
) ENGINE=INNODB DEFAULT CHARSET=UTF8;

CREATE TABLE `fhir_value_set_systems` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `vs_id` VARCHAR(125) NOT NULL,
    `system` VARCHAR(125) NOT NULL,
    `type` ENUM('All', 'Partial', 'Exclude', 'Filter', 'Codes') NOT NULL,
    `filter` VARCHAR(125) DEFAULT NULL,
    PRIMARY KEY(`id`)
) ENGINE=INNODB DEFAULT CHARSET=UTF8;

CREATE TABLE `fhir_value_set_codes` (
    `vss_id` INT NOT NULL,
    `code` VARCHAR(125) NOT NULL,
    PRIMARY KEY(`vss_id`, `code`)
) ENGINE=INNODB DEFAULT CHARSET=UTF8;

ALTER TABLE `openemr_postcalendar_events`
            ADD `pc_priority` INT NOT NULL DEFAULT '1' AFTER `pc_gid`,
            ADD `pc_service_type` INT NULL DEFAULT NULL AFTER `pc_priority`,
            ADD `pc_healthcare_service_id` INT NULL DEFAULT NULL AFTER `pc_service_type`;


CREATE TABLE `event_codeReason_map` (
  `event_id` int(11) NOT NULL,
  `option_id` varchar(100) NOT NULL
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE event_codeReason_map ADD PRIMARY KEY (event_id, option_id);

ALTER TABLE `openemr_postcalendar_events` CHANGE
`pc_healthcare_service_id` `pc_healthcare_service_id` INT NULL DEFAULT NULL COMMENT 'fhir_healthcare_services.id';


ALTER TABLE `fhir_healthcare_services` CHANGE
`providedBy` `providedBy` INT NULL DEFAULT NULL COMMENT 'facility.id';

CREATE TABLE `related_person` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) DEFAULT NULL,
  `identifier_type` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `pid` bigint(20) NOT NULL,
  `relationship` varchar(255) DEFAULT NULL,
  `phone_home` varchar(255) DEFAULT NULL,
  `phone_cell` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `form_encounter`
             ADD `status` VARCHAR(100) NULL AFTER `parent_encounter_id`,
             ADD `eid` INT NULL AFTER `status`,
             ADD `priority` INT DEFAULT 1 AFTER `status`,
             ADD `service_type` INT DEFAULT NULL AFTER `priority`,
             ADD `arrival_way` VARCHAR(255) NULL DEFAULT NULL AFTER `eid`,
             ADD `reason_codes_details` TEXT NULL DEFAULT NULL AFTER `arrival_way`;


ALTER TABLE facility
             ADD active int DEFAULT 1;

ALTER TABLE `form_encounter`
             ADD `escort_id` BIGINT(20) NULL DEFAULT NULL COMMENT 'related_person.id' AFTER `service_type`;

CREATE TABLE `fhir_questionnaire` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name`      varchar(255) NOT NULL,
    `directory` varchar(255) NOT NULL,
    `state`     tinyint(4)   DEFAULT NULL,
    `aco_spec`  varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ;



INSERT INTO `fhir_rest_elements` (`name`, `active`)
VALUES
('Organization', 1),
('Patient', 1),
('Appointment', 1),
('HealthcareService', 1),
('Encounter', 1),
('ValueSet', 1),
('DocumentReference', 1),
( 'Questionnaire', 1),
('QuestionnaireResponse', 1),
('Practitioner', 1),
('RelatedPerson', 1),
('MedicationStatement', 1),
('Condition', 1),
('Observation', 1),
('MedicationRequest', 1),
('ServiceRequest', 1);


INSERT INTO `globals` (`gl_name`, `gl_index`, `gl_value`) VALUES
('fhir_type_validation', 0, '0');


DELETE FROM `list_options` WHERE `list_id` like "sex";
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`,`activity`)
VALUES
('sex', 'male', 'Male', '10', '1', '0','','1'),
('sex', 'female', 'Female', '20', '0', '0','', '1'),
('sex', 'other', 'Other', '30', '0', '0','', '1'),
('sex', 'unknown', 'Unknown', '40', '0', '0','' ,'0');


INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`, `filter`)
VALUES
('gender', 'sex', 'Partial', NULL);

INSERT INTO `fhir_value_set_codes` (`vss_id`, `code`) VALUES
((SELECT id FROM fhir_value_set_systems WHERE vs_id = 'gender'), 'female'),
((SELECT id FROM fhir_value_set_systems WHERE vs_id = 'gender'), 'male'),
((SELECT id FROM fhir_value_set_systems WHERE vs_id = 'gender'), 'other');

INSERT INTO fhir_value_sets (id, title, status) VALUES('gender', 'Gender', 'active');


INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`)
VALUES
('lists', 'clinikal_app_statuses', 'Clinikal Appointment Statuses', 0, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_app_statuses', 'pending', 'Pending Approval', 10, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_app_statuses', 'booked', 'Booked', 20, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_app_statuses', 'arrived', 'Arrived', 30, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_app_statuses', 'cancelled', 'Cancelled', 40, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_app_statuses', 'noshow', 'No Show', 50, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_app_statuses', 'waitlist', 'Waitlisted', 60, 0, 0, '', '', '', 0, 0, 1, '', 1);


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('appointment_statuses', 'Appointment Statuses');

INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('appointment_statuses', 'clinikal_app_statuses', 'All');



ALTER TABLE `related_person` ADD `full_name` VARCHAR(255) NULL DEFAULT NULL AFTER `gender`;

DELETE  FROM `list_options` WHERE `list_id` LIKE 'outcome';

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`, `timestamp`) VALUES
('outcome', '1', 'active', 5, 0, 0, 'all', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '2', 'recurrence', 10, 0, 0, 'Condition', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '3', 'relapse', 15, 0, 0, 'Condition', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '4', 'inactive', 20, 0, 0, 'Condition', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '5', 'resolved', 25, 0, 0, 'Condition', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '6', 'remission', 25, 0, 0, 'Condition', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),

('outcome', '7', 'completed', 25, 0, 0, 'MedicationStatement', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '8', 'entered-in-error', 25, 0, 0, 'MedicationStatement', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '9', 'intended', 25, 0, 0, 'MedicationStatement', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '10', 'stopped', 25, 0, 0, 'MedicationStatement', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '11', 'on-hold', 25, 0, 0, 'MedicationStatement', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '12', 'unknown', 25, 0, 0, 'MedicationStatement', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44'),
('outcome', '13', 'not-taken', 25, 0, 0, 'MedicationStatement', NULL, '', 0, 0, 1, '', 1, '2021-05-26 07:07:44');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`)
VALUES
('lists', 'clinikal_enc_secondary_statuses', 'Clinikal Appointment Secondary Statuses', 0, 0, 0, '', 'In Progress', '', 0, 0, 1, '', 1);

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`)
VALUES
('lists', 'clinikal_enc_statuses', 'Clinikal Encounter Statuses', 0, 0, 0, '', '', '', 0, 0, 1, '', 1);

ALTER TABLE `form_encounter`
ADD `secondary_status` VARCHAR(255) NULL AFTER `reason_codes_details`,
ADD `status_update_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `secondary_status`;


INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`,`mapping` ,`notes`, `activity`,`subtype`) VALUES
('loinc_org', '8480-6', 'Systolic blood pressure', 10,'bps','{"mask": "___","label":"Blood pressure"}', 1,'mmHg'),
('loinc_org', '8462-4', 'Diastolic blood pressure', 20,'bpd','{"mask": "___","label":"Blood pressure"}', 1,'mmHg'),
('loinc_org', '8308-9', 'Body height --standing', 30,'height','{"label": "Height","mask":"___"}', 1,'cm'),
('loinc_org', '8335-2', 'Body weight Estimated', 40,'weight','{"label": "Weight","mask":"___._"}', 1,'Kg'),
('loinc_org', '69000-8','Heart rate --sitting', 50,'pulse','{"label": "Pulse","mask": "___"}', 1,'PRA'),
('loinc_org', '8310-5', 'Body temperature', 60,'temperature','{"label": "Fever","mask": "__._"}', 1,'C'),
('loinc_org', '8327-9', 'Body temperature measurement site', 70,'temp_method','', 1,''),
('loinc_org', '20564-1', 'Oxygen saturation in Blood', 80,'oxygen_saturation','{"label": "Saturation","mask": "___%"}', 1,'%'),
('loinc_org', '39156-5', 'Body mass index (BMI) [Ratio]', 90,'BMI','', 1,'kg/m2'),
('loinc_org', '59574-4', 'Body mass index (BMI) [Percentile]', 100,'BMI_status','', 1,''),
('loinc_org', '8280-0', 'Waist Circumference at umbilicus by Tape measure', 110,'waist_circ','', 1,'cm'),
('loinc_org', '8287-5', 'Head Occipital-frontal circumference by Tape measure', 120,'head_circ','', 1,'cm'),
('loinc_org', '9303-9', 'Respiratory rate --resting', 130,'respiration','{"label": "Breaths per minute","mask": "__"}', 1,'BPM'),
('loinc_org', '72514-3', 'Pain severity - 0-10 verbal numeric rating [Score] - Reported', 140,'pain_severity','{"label": "Pain level","mask": "__"}', 1,''),
('loinc_org', '74774-1', 'Glucose [Mass/volume] in Serum, Plasma or Blood', 150,'glucose','{"label": "Blood sugar","mask": "___"}', 1,'mg/dL');

ALTER TABLE `form_vitals`
ADD `glucose` INT NULL AFTER `external_id`,
ADD `pain_severity` INT NULL AFTER `glucose`,
ADD `eid` INT NULL AFTER `pain_severity`,
ADD `category` VARCHAR(255) NULL AFTER `eid`,
ADD `observation_status` VARCHAR(20) NULL AFTER `category`;


INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`)
VALUES
('lists', 'observation-category', 'Observation category', 0, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'social-history', 'Social History', 10, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'vital-signs', 'Vital Signs', 20, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'imaging', 'Imaging', 30, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'laboratory', 'Laboratory', 40, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'procedure', 'Procedure', 50, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'survey', 'Survey', 60, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'exam', 'Exam', 70, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'therapy', 'Therapy', 80, 0, 0, '', '', '', 0, 0, 1, '', 1),
('observation-category', 'activity', 'Activity', 90, 0, 0, '', '', '', 0, 0, 1, '', 1);


INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`)
VALUES
('lists', 'medicationrequest_status', 'medicationrequest status', 0, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '1', 'active', 10, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '2', 'on-hold', 20, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '0', 'cancelled', 30, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '3', 'completed', 40, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '4', 'entered-in-error', 50, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '5', 'stopped', 60, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '6', 'draft', 70, 0, 0, '', '', '', 0, 0, 1, '', 1),
('medicationrequest_status', '7', 'unknown', 80, 0, 0, '', '', '', 0, 0, 1, '', 1);


INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `toggle_setting_1`, `toggle_setting_2`, `activity`, `subtype`, `edit_options`) VALUES
('lists', 'clinikal_service_categories', 'Clinikal Service Categories', 0, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_service_categories', '14', '	Emergency Department', 10, 0, 0, '', '', '', 0, 0, 1, '', 1),
('clinikal_service_categories', '30', 'Specialist Radiology/Imaging', 10, 0, 0, '', '', '', 0, 0, 1, '', 1);

CREATE TABLE `fhir_service_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(255) DEFAULT NULL,
  `encounter` int(11) DEFAULT NULL,
  `reason_code` varchar(255) DEFAULT NULL,
  `patient` int(11) DEFAULT NULL,
  `instruction_code` varchar(255) DEFAULT NULL,
  `order_detail_code` varchar(255) DEFAULT NULL,
  `order_detail_system` varchar(255) DEFAULT NULL,
  `patient_instruction` text DEFAULT NULL,
  `requester` int(11) DEFAULT NULL,
  `authored_on` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `intent` varchar(30) NOT NULL,
  `note` text DEFAULT NULL,
  `performer` int(11) DEFAULT NULL,
  `occurrence_datetime` datetime DEFAULT NULL,
  `reason_reference_doc_id` int(11) DEFAULT NULL,
   PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('drug_interval', 'Drug Interval');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('drug_interval', 'drug_interval', 'All');


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('drug_form', 'Drug Form');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('drug_form', 'drug_form', 'All');


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('drug_route', 'Drug Route');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('drug_route', 'drug_route', 'All');



INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('condition_statuses', 'Condition Clinical Statuses');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('condition_statuses', 'outcome', 'All');


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('medication_statement_statuses', 'Medication Statement Statuses');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('medication_statement_statuses', 'outcome', 'All');

INSERT INTO `fhir_value_sets` (`id`, `title`)
VALUES ('reason_codes', 'All Reason Codes');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`)
VALUES ('reason_codes', 'clinikal_reason_codes', 'All');


INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`,`notes`) VALUES
('lists', 'observation_statuses', 'Observation Statuses', 0, 1,''),
('observation_statuses', 'registered', 'Registered', 10, 1,''),
('observation_statuses', 'preliminary', 'Preliminary', 20, 1,''),
('observation_statuses', 'final', 'Final', 30, 1,''),
('observation_statuses', 'amended', 'Amended', 40, 1,''),
('observation_statuses', 'corrected', 'Corrected', 60, 1,''),
('observation_statuses', 'entered-in-error', 'Entered In Error',70, 1,''),
('observation_statuses', 'unknown', 'Unknown', 80, 1,''),
('observation_statuses', 'cancelled', 'Cancelled', 50, 1,'');


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('observation_statuses', 'Observation Statuses');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('observation_statuses', 'observation_statuses', 'All');


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('medicationrequest_status', 'Medication Request Statuses');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('medicationrequest_status', 'medicationrequest_status', 'All');



INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`,`notes`) VALUES
('lists', 'servicerequest_statuses', 'Service Request Statuses', 0, 1,''),
('servicerequest_statuses', 'draft', 'Draft', 10, 1,''),
('servicerequest_statuses', 'active', 'Active', 20, 1,''),
('servicerequest_statuses', 'on-hold', 'On Hold', 30, 1,''),
('servicerequest_statuses', 'revoked', 'Revoked', 40, 1,''),
('servicerequest_statuses', 'completed', 'Completed', 60, 1,''),
('servicerequest_statuses', 'entered-in-error', 'Entered In Error',70, 1,''),
('servicerequest_statuses', 'unknown', 'Unknown', 80, 1,'');


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('servicerequest_statuses', 'Service Request Statuses');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('servicerequest_statuses', 'servicerequest_statuses', 'All');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `activity`,`notes`) VALUES
('lists', 'servicerequest_intent', 'Service Request Intent', 0, 1,''),
('servicerequest_intent', 'proposal', 'Proposal', 10, 1,''),
('servicerequest_intent', 'plan', 'Plan', 20, 1,''),
('servicerequest_intent', 'directive', 'Directive', 30, 1,''),
('servicerequest_intent', 'order', 'Order', 40, 1,''),
('servicerequest_intent', 'original-order', 'Original Order', 60, 1,''),
('servicerequest_intent', 'reflex-order', 'Reflex Order',70, 1,''),
('servicerequest_intent', 'filler-order', 'Filler Order',70, 1,''),
('servicerequest_intent', 'instance-order', 'Instance Order',70, 1,''),
('servicerequest_intent', 'option', 'Option', 80, 1,'');


INSERT INTO `fhir_value_sets` (`id`, `title`) VALUES
('servicerequest_intent', 'Service Request Intent');
INSERT INTO `fhir_value_set_systems` (`vs_id`, `system`, `type`) VALUES
('servicerequest_intent', 'servicerequest_intent', 'All');



CREATE TABLE `fhir_validation_settings` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`fhir_element` VARCHAR(255) NOT NULL ,
`filed_name` VARCHAR(255) NOT NULL ,
`request_action` ENUM('ALL','WRITE','UPDATE','POST','PUT','PATCH','DELETE','GET') NOT NULL ,
`validation` VARCHAR(255) NOT NULL ,
`validation_param` VARCHAR(255) NOT NULL ,
`type` ENUM('FHIR','DB') NOT NULL,
`active` BOOLEAN NOT NULL DEFAULT 0,
 PRIMARY KEY(`id`)
)  ENGINE = InnoDB;



INSERT INTO `fhir_validation_settings` (`fhir_element`, `filed_name`, `request_action`, `validation`, `validation_param`, `type`, `active`) VALUES
('Encounter', 'status', 'UPDATE', 'blockedEncounter', 'finished', 'DB', 1),
('Encounter', 'status', 'WRITE', 'required', '', 'DB', 1),
('Encounter', 'pid', 'WRITE', 'required', '', 'DB', 1),
('Encounter', 'date', 'WRITE', 'required', '', 'DB', 1),
('Encounter', 'status', 'WRITE', 'required', '', 'DB', 1),
('Encounter', 'service_type', 'WRITE', 'valuesetNotRequired', 'service_types', 'DB', 1),
('Appointment', 'pc_service_type', 'WRITE', 'valueset', 'service_types', 'DB', 1),
('Appointment', 'event_codeReason_map', 'WRITE', 'aptReasonCodes', 'reason_codes_', 'DB', 1),
('Appointment', 'pc_apptstatus', 'WRITE', 'valueset', 'appointment_statuses', 'DB', 1),
('Appointment', '', 'WRITE', 'aptDateRangeCheck', '', 'DB', 1),
('Appointment', 'pc_healthcare_service_id', 'WRITE', 'required', '', 'DB', 1),
('Appointment', 'pc_pid', 'WRITE', 'ifExist', 'patient_data', 'DB', 1),
('Patient', 'lname', 'WRITE', 'required', '', 'DB', 1),
('Patient', 'fname', 'WRITE', 'required', '', 'DB', 1),
('Patient', 'sex', 'WRITE', 'required', '', 'DB', 1),
('Patient', 'DOB', 'WRITE', 'required', '', 'DB', 1),
('RelatedPerson', 'pid', 'WRITE', 'ifExist', 'patient_data', 'DB', 1),
('Condition', 'type', 'WRITE', 'required', '', 'DB', 1),
('Condition', 'date', 'WRITE', 'required', '', 'DB', 1),
('Condition', 'diagnosis', 'WRITE', 'required', '', 'DB', 1),
('Condition', 'pid', 'WRITE', 'ifExist', 'patient_data', 'DB', 1),
('Condition', 'user', 'WRITE', 'ifExist', 'users', 'DB', 1),
('MedicationStatement', 'diagnosis', 'WRITE', 'required', '', 'DB', 1),
('MedicationStatement', 'pid', 'WRITE', 'ifExist', 'patient_data', 'DB', 1),
('Observation', 'observation_status', 'WRITE', 'valueset', 'observation_statuses', 'DB', 1),
('Observation', 'observation_status', 'UPDATE', 'blockedIfValue', 'final', 'DB', 1),
('Observation', 'date', 'WRITE', 'required', '', 'DB', 1),
('Observation', 'pid', 'WRITE', 'ifExist', 'patient_data', 'DB', 1),
('MedicationRequest', 'drug', 'WRITE', 'required', '', 'DB', 1),
('MedicationRequest', 'datetime', 'WRITE', 'required', '', 'DB', 1),
('MedicationRequest', 'drug_id', 'WRITE', 'valueset', 'drugs_list', 'DB', 1),
('MedicationRequest', 'form', 'WRITE', 'valueset', 'drug_form', 'DB', 1),
('MedicationRequest', 'route', 'WRITE', 'valueset', 'drug_route', 'DB', 1),
('MedicationRequest', 'interval', 'WRITE', 'valueset', 'drug_interval', 'DB', 1),
('MedicationRequest', 'active', 'WRITE', 'valueset', 'medicationrequest_status', 'DB', 1),
('MedicationRequest', 'patient_id', 'WRITE', 'ifExist', 'patient_data', 'DB', 1),
('MedicationRequest', 'user', 'WRITE', 'ifExist', 'users', 'DB', 1),
('MedicationRequest', 'encounter', 'WRITE', 'required', '', 'DB', 1),
('MedicationRequest', 'status', 'WRITE', 'valueset', 'medicationrequest_status', 'DB', 1),
('ServiceRequest', 'encounter', 'WRITE', 'required', '', 'DB', 1),
('ServiceRequest', 'instruction_code', 'WRITE', 'valueset', 'tests_and_treatments', 'DB', 1),
('ServiceRequest', 'status', 'WRITE', 'valueset', 'servicerequest_statuses', 'DB', 1),
('ServiceRequest', 'status', 'UPDATE', 'blockedIfValue', 'completed', 'DB', 1),
('ServiceRequest', 'intent', 'WRITE', 'valueset', 'servicerequest_intent', 'DB', 1),
('ServiceRequest', 'patient', 'WRITE', 'ifExist', 'patient_data', 'DB', 1);


ALTER TABLE `lists` ADD `diagnosis_valueset` VARCHAR(255) NULL AFTER `diagnosis`;
