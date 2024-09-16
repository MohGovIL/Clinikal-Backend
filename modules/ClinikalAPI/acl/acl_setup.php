
<?php

use OpenEMR\Common\Acl\AclExtended;


//using BY https://matrixil.sharepoint.com/:x:/r/sites/DTG/Clinical/_layouts/15/Doc.aspx?sourcedoc=%7B49114388-8709-407B-BF08-8E8F26C8119F%7D&file=%D7%A7%D7%9C%D7%99%D7%A0%D7%99%D7%A7%D7%9C%20%D7%93%D7%99%D7%9E%D7%95%D7%AA%20-%20%D7%94%D7%A8%D7%A9%D7%90%D7%95%D7%AA%20%D7%9C%D7%A4%D7%99%20%D7%AA%D7%A4%D7%A7%D7%99%D7%93%D7%99%D7%9D%20%D7%92%D7%A8%D7%A1%D7%94%201.xlsx&action=default&mobileredirect=true&cid=6559357a-ef09-499a-b410-0a6b0078e2e7
//OBJECT OF ACL
AclExtended::addObjectSectionAcl('client_app', 'Client Application');
AclExtended::addObjectAcl('client_app', 'Client Application', 'PatientAdmission','Patient Admission');
AclExtended::addObjectAcl('client_app', 'Client Application', 'AddPatient','Add Patient');
AclExtended::addObjectAcl('client_app', 'Client Application', 'AppointmentsAndEncounters','Appointments And Encounters');
AclExtended::addObjectAcl('client_app', 'Client Application', 'EncounterSheet','Encounter Sheet');
AclExtended::addObjectAcl('client_app', 'Client Application', 'SuperUser','Super User');
AclExtended::addObjectAcl('client_app', 'Client Application', 'SearchPatient','Search Patient');
AclExtended::addObjectAcl('client_app', 'Client Application', 'Calendar','Calendar');
AclExtended::addObjectAcl('client_app', 'Client Application', 'AppointmentDetails','Appointment Details');
AclExtended::addObjectAcl('client_app', 'Client Application', 'ManageTemplates','Manage Templates');

AclExtended::addObjectSectionAcl('clinikal_api', 'Clinikal API');
AclExtended::addObjectAcl('clinikal_api', 'Clinikal API', 'general_settings','General settings');
AclExtended::addObjectAcl('clinikal_api', 'Clinikal API', 'lists','Lists');




?>
<html>
<head>
    <title>___________ ACL Setup</title>
    <link rel=STYLESHEET href="interface/themes/style_blue.css">
</head>
<body>
<b>OpenEMR[_____________] ACL Setup</b>
<br>
All done configuring and installing access controls (php-GACL)!
</body>
</html>
