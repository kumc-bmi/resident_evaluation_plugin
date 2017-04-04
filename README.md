
#REDCap Resident Evaluation Plugin#


##INTRODUCTION##

This plugin is developed to meet the requirements of a Project - Resident Evalution. 

It has four basic functions.
    1. To generate survey links for resident and faculty
    2. To fetch the resident and faculty emails from their info projects based on the selection of resident/faculty from the dropdown of Resident Evaluation initial survey. 
    3. To load these fetched emails and generated survey links into invite information instrument of Resident Evaluation.
    4. To assign DAG to the record based on the institution.
    
This execution is followed by the notifications hook which send out the survey links as email notifications.


##REQUIREMENTS##

This code requires that the REDCap Hook Registry code be installed. The hook registry was developed along side this notification plugin. More information about the REDCap Hook Registry can be found at https://github.com/kumc-bmi/redcap-hook-registry.

ProjectModel.php: The implementation uses the ProjectModel(MVC architecture) which is present as part of  REDCap Plugin Framework. 

PluginConfig.php: This contains a class definition for an immutable object, which implements the PHP array interface and contains configuration option pulled in from an ini file.

RestCallRequest.php: This code was written by REDCap developer for use with their API, and distributed on the REDCap Consortium site (http://project-redcap.org).


##INSTALLATION##

To install this code:

Clone the Resident Evaluation plugin code into <redcap-root>/plugins/reseval.

Create a new REDCap project for Resident Evaluation, and upload the data_dictionary.csv file in this directory.


##CONFIGURATION##

The Resident Evaluation plugin configuration can be found in reseval.ini

A sample reseval.ini is attached to the Resident Evaluation repository in github.


##MAINTAINERS##

Current maintainers:

Nazma Kotcherla nkotcherla@kumc.edu 



