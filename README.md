## What is clinikal?

Clinikal is a Electric Medical Records application.  
Clinical offers a new experience of administrative and medical management for variety of clinics.  
The foundation of the application is the popular open source [OpenEMR](https://github.com/openemr/openemr), we developed new layer of Fhir API base on ZF2 modules and new and modern React.js application to enjoy from wonderful user experience.  
Clinikal continues to use OpenEMR interfaces as Content Management System for manage users, permissions, lists etc. (we doesn't supply compatibility with all Openemr screens) 

The principle that guides us is **clean and clear**.  
Each user sees only the screens and forms relevant to his role in the clinic.   
This ability is caused by using a system of roles and privileges for each profession in the clinic and a different installation process for each medical field which creates an innovative and convenient user experience!

### Get started and documentation
To get started and documentation at https://clinikal-documentation.readthedocs.io/

### Clinikal-beckend
The product composed of a few component, Server-side modules and client-side application.   
This repository is part of server-side components and contains several modules and configurations that common for all the medical fields in the system.    
Main contents:  
* API layer
* General functionality
* Custom menus
* Import data module
* More helpers  

### Resources
* [Docker installation](https://clinikal-documentation.readthedocs.io/en/latest/get_started/docker_installation/)  
* [Manual installation](https://clinikal-documentation.readthedocs.io/en/latest/get_started/openemr_modules/)  
* [Internationalization](https://clinikal-documentation.readthedocs.io/en/latest/get_started/internationalization/)
* [FHIR API](https://clinikal-documentation.readthedocs.io/en/latest/api/fhir/)

### License
Please see the [license agreement](https://github.com/israeli-moh/clinikal-react/blob/develop/LICENSE).


