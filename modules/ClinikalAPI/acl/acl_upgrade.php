<?php
use OpenEMR\Common\Acl\AclExtended;

return $ACL_UPGRADE = array(

    '0.2.0' => function () {

        AclExtended::addObjectSectionAcl('client_app', 'Client Application');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'PatientAdmission','Patient Admission');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'AddPatient','Add Patient');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'AppointmentsAndEncounters','Appointments And Encounters');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'EncounterSheet','Encounter Sheet');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'SuperUser','Super User');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'SearchPatient','Search Patient');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'Calendar','Calendar');
        AclExtended::addObjectAcl('client_app', 'Client Application', 'AppointmentDetails','Appointment Details');

        AclExtended::addObjectSectionAcl('clinikal_api', 'Clinikal API');
        AclExtended::addObjectAcl('clinikal_api', 'Clinikal API', 'general_settings','General settings');
        AclExtended::addObjectAcl('clinikal_api', 'Clinikal API', 'lists','Lists');
    },
    '2.0.0' => function() {
        AclExtended::addObjectAcl('client_app', 'Client Application', 'ManageTemplates','Manage Templates');
    }
);
