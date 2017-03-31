<?php
/**
 * This is an implementation of the reseval_save_record hook 
 * This has four basic functions.
 * 1. To generate survey links for resident and faculty
 * 2. To fetch the resident and faculty emails from their info projects 
 *    based on the selection from dropdown of resident_evaluation first survey
 * 3. To load these emails and generated survey links into 
 *    invite information instrument of resident_evaluation.
 * 4. Assigns DAG to the record based on the institution.
 * Then the notifications hook is invoked and send emails 
 * based on the invite information.
 * To support cron job, time and date are also captured here and 
 * is loaded into invite_information.
 */

define('RC_YES', 1);
define('RC_NO', 0);
define('RC_FORM_COMPLETE', 2);


function reseval_save_record($project_id, $record, $instrument, $event_id,
                             $group_id, $survey_hash, $response_id)
{
    global $conn; // REDCapism
    
    require_once(REDCAP_ROOT.'redcap_connect.php');
    
    define('FRAMEWORK_ROOT', REDCAP_ROOT.'plugins/framework/');
    define('RESIDENT_EVAL_ROOT', REDCAP_ROOT.'plugins/reseval/');
    
    require_once(FRAMEWORK_ROOT.'PluginConfig.php');
    require_once(FRAMEWORK_ROOT.'ProjectModel.php');
    
    $CONFIG = new PluginConfig(RESIDENT_EVAL_ROOT.'reseval.ini');
    
    $resident_eval =  new ProjectModel(
        $project_id, 
        $conn
    );
    
    $resident_info =  new ProjectModel(
        $CONFIG['resident_info_pid'], 
        $conn
    );
    
    $faculty_info  =  new ProjectModel(
        $CONFIG['faculty_info_pid'], 
        $conn
    );
    
    
    $resident_eval->make_writeable(
        $CONFIG['api_url'],
        $CONFIG['proj_token']
    ); 
    
    
    $survey_instruments = array(
        $CONFIG['res_eval_link'] => $CONFIG['res_eval_by_fac'],
        $CONFIG['fac_eval_link'] => $CONFIG['fac_eval_by_res']
    );

    $record_data = $resident_eval->get_record_by(
        'record',
        $record
    );   

    foreach ($survey_instruments as $survey_field => $survey_name)
    {
	    $s_link =  REDCap::getSurveyLink($record,$survey_name);
        
        if(empty($s_link))
        {
            error_log('Failed in generating '.
                      'the survey link for '.$survey_name);
		}
        
        $result_save_link = save_value_in_record(
            $resident_eval,
            $record,
            $s_link,
            $survey_field
        );

        if($result_save_link == false)
        {
            error_log('Failed in saving the survey '.
                      'link in invite information in'.$survey_filed);
        }	

    }
  

    $result_save_DAG  =  save_DAG_for_record( 
        $project_id, 
        $event_id, 
        $record, 
        $record_data
    );
    
    if( $result_save_DAG == false)
    {
        error_log('Failed in saving the DAG '.
                  'information of record'.$record);
    }	
   
                  
    $result_res_email =  get_and_save_emails(
        $resident_eval,
        $record,
        $record_data,
        $CONFIG['resident_insts'],
        $CONFIG['res_email_label'],
        $resident_info,
        $CONFIG['res_info_email_field']
    );

   
    if($result_res_email == false)
    {
        error_log('Failed in saving the '.
                  'resident email in invite information');
    }

    $result_eval_email =  get_and_save_emails(
        $resident_eval,
        $record,
        $record_data,
        $CONFIG['faculty_insts'],
        $CONFIG['fac_email_label'],
        $faculty_info,
        $CONFIG['fac_info_email_field']
    );


    if($result_eval_email == false)
    {
        error_log('Failed in saving the '.
                  'faculty email in invite information');
    }
    
        
    $current_time = date("H:i:s");
    
    $result_save_time = save_value_in_record( 
        $resident_eval,
        $record,
        $current_time,
        $CONFIG['submit_time']
    );

    $current_date = date("m/d/Y");
    
    $result_save_date = save_value_in_record( 
        $resident_eval,
        $record,
        $current_date,
        $CONFIG['submit_date']
    );

    register_shutdown_function('shutDownFunction');
        
    return true;

}

// function that is specific to Resident evaluation project 
// that gets the email addresses and saves in other instrument 

function get_and_save_emails($resident_eval, $record, $record_data, 
                             $institutions, $invite_email_label, 
                             $info_project, $info_email_field)
{
    
    $get_inst_value = array();
    
    foreach ($institutions as $inst)
    {
        array_push($get_inst_value, $record_data[$inst]);
    }     
    
    $rec_num = max($get_inst_value);
    
    $rec_data = $info_project->get_record_by(
        'record',
        $rec_num
    );
     
    $result_save_emails =  save_value_in_record(
        $resident_eval,
        $record,
        $rec_data[$info_email_field],
        $invite_email_label
    );
    
    if($result_save_emails == false)
    {
        return false;  
    }
    
    return true;
    
    
}
// function to save the generated link in the record field.

function save_value_in_record($project_info,$record,$rec_value,$rec_field)
{

    $record_data_to_save = array($rec_field => $rec_value);

    $result_save_data_api = $project_info->save_record(
        $record_data_to_save, 
        $record
    );
      
    return true;

}

function save_DAG_for_record( $project_id, $event_id, $record, $record_data)
{
    
    $DAG_name  = $record_data['institution'];
    
    $record_data_formatted = array($record => array($event_id => $record_data));
    
    $response = REDCap::saveData(
        $project_id,
        'array',
        $record_data_formatted,
        'normal',
        'MDY',
        'eav',
        $DAG_name
    );
        
    if($response['errors'] !== null)
    {
        return false;
    }      
  
    return true;

} 

function shutDownFunction()
{
    $error = error_get_last();
    error_log(print_r($error,true));

}


?>
