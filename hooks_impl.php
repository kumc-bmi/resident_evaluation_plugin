<?php
/**
 * This is an implementation of the redcap_save_record hook which sends
 * preconfigured email notifications when an associated REDCap record is save,
 * and trigger conditions are meet.
 */
define('RC_YES', 1);
define('RC_NO', 0);
define('RC_FORM_COMPLETE', 2);


function shutDownFunction(){
	$error = error_get_last();
        error_log(print_r($error,true));

}


function reseval_save_record($project_id, $record, $instrument, $event_id,
                                   $group_id, $survey_hash, $response_id)
{
    // Provides access to REDCap helper functions and database connection.
    //print "hello";
    register_shutdown_function('shutDownFunction');
    error_log("**********************************ENTERED RESEVAL_SAVE FUNCTION*****************************");
    global $conn; // REDCapism
    require_once(REDCAP_ROOT.'redcap_connect.php');

    // Load configuration plugin configuration.
    define('FRAMEWORK_ROOT', REDCAP_ROOT.'plugins/framework/');
    error_log('test 1');
    require_once(FRAMEWORK_ROOT.'PluginConfig.php');
    
    error_log('test 2');
    require_once(FRAMEWORK_ROOT.'ProjectModel.php');
    error_log('test 3');

    $CONFIG = new PluginConfig(dirname(__FILE__).'/reseval.ini');
    //error_log("inside config");
    //error_log($CONFIG['resident_info_pid']);
    // This differs from REDCap's Record class in that project records can be
    // queried for by fields other than record id.
     require_once(dirname(__FILE__).'/../utils/records.php');

   // Evaluates REDCap branching logic syntax.
    require_once(APP_PATH_DOCROOT.'Classes/LogicTester.php');
    // Provides properly formated REDCap record data for use with LogicTester.
    require_once(APP_PATH_DOCROOT.'Classes/Records.php');

     error_log('test 4');

    $residents =  new ProjectModel($project_id, $conn);

    error_log("this is the new residents object");
   
    error_log(print_r($residents, true));
    
    $survey_instruments = array($CONFIG['res_eval_link'] => $CONFIG['res_eval_by_fac'],$CONFIG['fac_eval_link'] => $CONFIG['fac_eval_by_res']);
    
   // $resident_insts = array('resident_kumc','resident_amc','resident_slu','resident_evms','resident_wu','resident_uwm');    
    //$res_email_label = "resident_email";
    //$res_info_proj_num = '181';
    //$res_info_email_field = 'email';

   // $eval_insts = array('evaluator_kumc','evaluator_amc','evaluator_slu','evaluator_evms','evaluator_wu','evaluator_uwm');
    //$eval_email_label = 'faculty_email';
    //$eval_info_proj_num = '182';
    //$eval_info_email_field = 'email'; 

    
    error_log("the survey_instruments obj has");
    error_log(print_r($survey_instruments, TRUE));


    foreach ($survey_instruments as $survey_field => $survey_name){
	
		$s_link =  REDCap::getSurveyLink($record,$survey_name);
		
		if(empty($s_link)){
		
			error_log('Failed in generating the survey link for '.$survey_name);

		}
				
		$result_save_link = save_value_in_record(
				    	                 $record,
                  		        	         $CONFIG,
                                	    		 $s_link,
                                   			 $survey_field);

       		if($result_save_link == false){

        		error_log('Failed in saving the survey link in invite information in'.$survey_filed);
       
       		}	

    }
  
  
    $record_data = Records::getData('array', $record);
  
    $result_save_DAG = save_DAG_for_record( $project_id,
					    $record_data,
                                            $record_data[$record][$event_id]['institution']);   
  
    //$record_data = Records::getData('array', $record);

                   
    $result_res_email = get_and_save_emails(
                                         $residents,
		  			 $record,
		  			 $CONFIG,
		  			 $record_data,
                   			 $CONFIG['resident_insts'],
                  			 $CONFIG['res_email_label'],
                  			 $CONFIG['resident_info_pid'],
                  			 $CONFIG['res_info_email_field'],
		  			 $event_id, 
		  			 $conn);
    if($result_res_email == false){

    	error_log('Failed in saving the resident email in invite information');
    }

   $result_eval_email =  get_and_save_emails(
					 $residents,
                  			 $record,
                   		         $CONFIG,
                  			 $record_data,
                	  		 $CONFIG['faculty_insts'],
                  			 $CONFIG['fac_email_label'],
                  			 $CONFIG['faculty_info_pid'],
                  			 $CONFIG['fac_info_email_field'],
                  			 $event_id,
                  			 $conn);


     if($result_eval_email == false){

        error_log('Failed in saving the faculty email in invite information');
    }

    
    $current_time = date("H:i:s");
    $result_save_time = save_value_in_record( $record,
					      $CONFIG,
					      $current_time,
					      $CONFIG['submit_time']);

    $current_date = date("m/d/Y");
    $result_save_date = save_value_in_record( $record,
                                              $CONFIG,
                                              $current_date,
                                              $CONFIG['submit_date']);



    error_log("reached the end of the program bye bye");
    return true;

}

// function that is specific to Resident evaluation project that gets the email addresses and saves in other instrument 
function get_and_save_emails($residents,$record, $CONFIG, $record_data,$institutions,$invite_email_label,$proj_num,$info_email_field,$event_id, $conn){
 
  $get_inst_value = array();
 
  foreach ($institutions as $inst){
  	
	array_push($get_inst_value, $record_data[$record][$event_id][$inst]);

   }     

   $rec_num = max($get_inst_value);

   $rec_data =  get_record_data($rec_num, $proj_num, $conn);
     

   error_log("am i reaching here");

//   $rec_data = $residents->get_record_by('record',$rec_num);
 
//** By using the ProjectModel, there is no method in it that takes in the record number, project number and returns the record_data. All the functions in the framework are restricted to the current project.


   error_log(print_r ($rec_data , true)); 
   $result_save_emails =  save_value_in_record($record,
         			               $CONFIG,
				  	       $rec_data[$info_email_field],
                        	               $invite_email_label);
   if($result_save_emails == false){
 
 	error_log('Failed in saving the emails in invite information');
        return false;  


   }

   return true;


}
// function to save the generated link in the record field.

function save_value_in_record($record,$CONFIG,$rec_value,$rec_field){


//require_once(REDCAP_ROOT.'redcap_connect.php');

  error_log("inside save value function");
  error_log($CONFIG['proj_token']);
  error_log($rec_value);
  error_log($rec_field);
  error_log($CONFIG['api_url']);


   $field_val = array(array(
        'record' => $record,
        'field_name' => $rec_field,
        'value' => $rec_value
    ));


//   $response = REDCap::saveData(180,'json',json_encode($field_val),'normal','MDY','eav','amc');
//   error_log (print_r($response, true));

    list($success, $error_msg) = save_redcap_data(
        $CONFIG['api_url'],
        $CONFIG['proj_token'],
        $field_val
    );
    
    if(!$success) {
        error_log('Failed in saving the value to the record field: '.$error_msg);
        return false;
    }

    return true;


}

function save_DAG_for_record( $project_id,$record_data,$DAG_name)
{

   $response = REDCap::saveData($project_id,'array',$record_data,'normal','MDY','eav',$DAG_name);
   error_log (print_r($response, true));


}






?>
