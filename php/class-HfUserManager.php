<?php

if (!class_exists("HfUserManager")) {
	class HfUserManager {
		
		function HfUserManager() { //constructor
		}
		
		private function currentUser() {
			return wp_get_current_user();
		}
		
		function processAllUsers() {
			$users = get_users();
			foreach ($users as $user) {
				$this->processNewUser($user->ID);
			}
		}
		
		function processNewUser($userID) {
			$HfMain = new HfAccountability();
			$Mailer = new HfMailer();
			$DbManager = new HfDbManager();
			$table = "hf_user_goal";
			$data = array( 'userID' => $userID,
				'goalID' => 1 );
			$DbManager->insertIgnoreIntoDb($table, $data);
			$settingsPageURL = $HfMain->getURLByTitle('Settings');
			$message = "<p>Welcome to HabitFree! 
				You've been subscribed to periodic accountability emails. 
				You can <a href='".$settingsPageURL."'>edit your subscription settings by clicking here</a>.</p>";
			$Mailer->sendEmail($userID, 'Welcome!', $message);
		}
		
		function getCurrentUserLogin() {
			return $this->currentUser()->user_login;
		}
		
		function getCurrentUserId() {
			return $this->currentUser()->ID;
		}
		
		function userGoalLevel($goalID, $userID) {
			$DbManager = new HfDbManager();			
			$daysOfSuccess = $this->daysOfSuccess($goalID, $userID);	
			$whereLevel = 'target > ' . $daysOfSuccess . ' ORDER BY target ASC';
			return $DbManager->getRow('hf_level', $whereLevel);
		}
		
		function daysToNextLevel($goalID, $userID) {
			$daysOfSuccess = $this->daysOfSuccess($goalID, $userID);
			$target = $this->currentLevelTarget($daysOfSuccess);
			return $target - $daysOfSuccess;
		}
		
		function currentLevelTarget($daysOfSuccess) {
			$DbManager = new HfDbManager();
			$whereCurrentLevel = 'target > ' . $daysOfSuccess . ' ORDER BY target ASC';
			return $DbManager->getVar('hf_level', 'target', $whereCurrentLevel);
		}
		
		function levelBarForGoal($goalID, $userID) {
			$HfMain = new HfAccountability();
			$percent = $this->levelPercentComplete($goalID, $userID);
			$daysOfSuccess = $this->daysOfSuccess($goalID, $userID);
			return $HfMain->progressBar($percent, '');
		}
		
		function nextLevelName($daysOfSuccess) {
			$DbManager = new HfDbManager();
			$whereCurrentLevel = 'target > ' . $daysOfSuccess . ' ORDER BY target ASC';
			$currentLevelID = $DbManager->getVar('hf_level', 'levelID', $whereCurrentLevel);
			$whereNextLevel = 'levelID = ' . ($currentLevelID + 1);
			return $DbManager->getVar('hf_level', 'title', $whereNextLevel);
		}
		
		function levelPercentComplete($goalID, $userID) {
			$daysOfSuccess = $this->daysOfSuccess($goalID, $userID);
			return ($this->daysOfSuccess($goalID, $userID) / $this->currentLevelTarget($daysOfSuccess)) * 100;
		}

		function daysOfSuccess($goalID, $userID) {
			global $wpdb;
			$DbManager = new HfDbManager();
			$prefix = $wpdb->prefix;
			$table = 'hf_report';
			$tableName = $prefix . $table;
			$select = 'date';
			
			$whereFirstSuccess = 'goalID = ' . $goalID .
				' AND userID = ' . $userID .
				' AND reportID=(
					SELECT min(reportID) 
					FROM ' . $tableName . 
					' WHERE isSuccessful = 1)';
			$whereLastSuccess = 'goalID = ' . $goalID .
				' AND userID = ' . $userID .
				' AND reportID=(
					SELECT max(reportID) 
					FROM ' . $tableName . 
					' WHERE isSuccessful = 1)';
			$whereLastFail = 'goalID = ' . $goalID .
				' AND userID = ' . $userID .
				' AND reportID=(
					SELECT max(reportID) 
					FROM ' . $tableName . 
					' WHERE NOT isSuccessful = 1)';
			
			$dateInSecondsOfFirstSuccess = strtotime($DbManager->getVar($table, $select, $whereFirstSuccess));
			$dateInSecondsOfLastSuccess = strtotime($DbManager->getVar($table, $select, $whereLastSuccess));
			$dateInSecondsOfLastFail = strtotime($DbManager->getVar($table, $select, $whereLastFail));
			
			$secondsInADay = 86400;
			
			if (!$dateInSecondsOfLastSuccess) {
				$daysOfSuccess = 0;
			} elseif (!$dateInSecondsOfLastFail) {
				$daysOfSuccess = ($dateInSecondsOfLastSuccess - $dateInSecondsOfFirstSuccess) / $secondsInADay;
			} else {
				$difference = $dateInSecondsOfLastSuccess - $dateInSecondsOfLastFail;
				$daysOfSuccess = $difference / $secondsInADay;
				if ($daysOfSuccess < 0) {
					$daysOfSuccess = 0;
				}
			}
			
			return $daysOfSuccess;
		}
		
		function userButtonsShortcode() {
			$HfMain = new HfAccountability();
			$welcome = 'Welcome back, ' . $this->getCurrentUserLogin() . ' | ';
			$logInOutLink = wp_loginout( $HfMain->getCurrentPageUrl(), false );
			if ( is_user_logged_in() ) {
				$settingsURL = $HfMain->getURLByTitle('Settings');
				return $welcome . $logInOutLink . ' | <a href="'.$settingsURL.'">Settings</a>';
			} else {
				$registerURL = $HfMain->getURLByTitle('Register');
				return $logInOutLink . ' | <a href="'.$registerURL.'">Register</a>';
			}
				
		}

		function requireLogin() {
			return 'You must be logged in to view this page. ' . wp_login_form( array('echo' => false) );
		}
		
		function isAnyGoalDue($userID) {
			$DbManager = new HfDbManager();
			$goals = $DbManager->getRows('hf_goal', 'userID = ' . $userID);
			foreach ($goals as $goal) {
				if ($this->isGoalDue($goal->goalID, $userID)) {
					return true;
				}
			}
			return false;
		}
		
		function isGoalDue($goalID, $userID) {
			$dbManager = new HfDbManager();
			$level = $this->userGoalLevel($goalID, $userID);
			$emailInterval = $level->emailInterval;
			$daysSinceLastReport = $this->daysSinceLastReport($goalID, $userID);
			return $daysSinceLastReport > $emailInterval;
		}
		
		function daysSinceLastReport($goalID, $userID) {
			global $wpdb;
			$DbManager = new HfDbManager();
			$prefix = $wpdb->prefix;
			$table = 'hf_report';
			$tableName = $prefix . $table;
			$whereLastReport = 'goalID = ' . $goalID .
				' AND userID = ' . $userID .
				' AND reportID=( SELECT max(reportID) FROM '.$tableName.' )';
			$dateInSecondsOfLastReport = strtotime($DbManager->getVar('hf_report', 'date', $whereLastReport));
			$secondsInADay = 86400;
			return ( time() - $dateInSecondsOfLastReport ) / $secondsInADay;
		}
	}
}

?>