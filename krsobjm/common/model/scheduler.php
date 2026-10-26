<?php
class ModelCommonScheduler extends Model {
	
	
	//  METHOD NAME: get_jobs
	//	INPUT:Nothing
	//	OUTPUT: An Array with all records of the Krs_scheduer table, else false.
	//	DESCRIPTION: Extract all records from the krs_scheduler table
	
	public function get_jobs() {
		try {
				$db = UtilityDB::getDBfromTable('krs_scheduler');		
				$sql = "SELECT a.* FROM t:schedulatore";	
				$stmt = $db->prepare($sql);
				$stmt->bindTable("schedulatore", "krs_scheduler", "a");
				$stmt->execute();
				return $stmt->fetchAll();
		} catch (Exception $e) {
			return false;
		}
	}
	
	
	//  METHOD NAME: set_job_last_run_date
	//	INPUT:Array Job id to update
	//	OUTPUT: True if the update finish correctly, else false
	//	DESCRIPTION: Update the JOB ID with the current date in the field LAST_RUN
	
	public function set_job_last_run_date($id) {
		try {
				$now = new DateTime;
				$data = $now->format('Ymd');
				$db = UtilityDB::getDBfromTable('krs_scheduler');
				$sql = "UPDATE t:schedulatore SET LAST_RUN='".$data."' WHERE ID=:id";
				$stmt = $db->prepare($sql);
				$stmt->bindTable("schedulatore", "krs_scheduler", "a");
				$stmt->bindParam("id", $id, KPDO::TYPE_INTEGER);
				$stmt->execute();
				return true;
		} catch (Exception $e) {
			return false;
		}
	}
	
	// METHOD NAME: check_job
	//	INPUT:Array with job to chak inside
	//	OUTPUT: True if this job must be run, else false.
	//	DESCRIPTION: A job must be run only if:
	//					1:	Field ATTIVO=1.
	//					2:	LAST_RUN field must be lesser then today. So we are sure that this job didn't already run today.
	//					3:	The TIME_RUN is lesser or equal to current time. (HHMM)
	//					4:	The DAY_OF_WEEK_RUN is equal to the current day of week or the DAY_OF_WEEK_RUN is '*' (means every days)
	//
	//	COMMENTS:	USAGE of krs_scheduler fields:
	//					-ID:				Autoincrement
	//					-DATE_RUN:			Run date in YYYYMMDD format (99999999 means every day)
	//					-LAST_RUN:			Last day when the job executed in format YYYYMMDD.
	//					-CONTROLLER:		Controller to run. To run a controllee called test in the example module toy must indicare: example/test.
	//										Adding the method name you can run also it and not the index standard method.
	//					-DAY_OF_WEEK_RUN:	Indicate the day of week the system must run this job. '*' is the wild card that means everyday.
	//					-TIME_RUN			Hour and minutes wher the scheduler must run the job (HH:MM format)
	//					-ATTIVO				1-Job active. 0-job inactive.
	
	public function check_job($job)	{
		//Parameters setup 
		$now = new DateTime;
		$today = $now->format('Ymd');
		$ora = $now->format('Hi');
		$day_week = $now->format('w');
		$time_run = str_replace(':','',$job["TIME_RUN"]);
		if ($job["DAY_OF_WEEK_RUN"]=='*'){
			$job["DAY_OF_WEEK_RUN"]= $day_week;
		}
		
		if 	(	($job["ATTIVO"]==1) 					and
				($job["LAST_RUN"]<$today)				and
				($time_run<=$ora)						and
				($day_week==$job["DAY_OF_WEEK_RUN"])
			)
		{
					return true;
		} else {
			return false;
		}
	
	}	
	
}
?>