<?php

if (!class_exists("HfUserManager")) {
	class HfUserManager {
		private $DbConnection;
        private $Messenger;
        private $UrlFinder;
        private $WebsiteApi;

        function HfUserManager($DbConnection, $Messenger, $UrlFinder, $WebsiteApi) {
			$this->DbConnection = $DbConnection;
            $this->Messenger = $Messenger;
            $this->UrlFinder = $UrlFinder;
            $this->WebsiteApi = $WebsiteApi;
		}
		
		function processAllUsers() {
			$users = get_users();
			foreach ($users as $user) {
				$this->processNewUser($user->ID);
			}
		}
		
		function processNewUser($userID) {
			$table = "hf_user_goal";
			$data = array( 'userID' => $userID,
				'goalID' => 1 );
			$this->DbConnection->insertIgnoreIntoDb($table, $data);
			$settingsPageURL = $this->UrlFinder->getURLByTitle('Settings');
			$message = "<p>Welcome to HabitFree! 
				You've been subscribed to periodic accountability emails. 
				You can <a href='".$settingsPageURL."'>edit your subscription settings by clicking here</a>.</p>";
			$this->Messenger->sendEmailToUser($userID, 'Welcome!', $message);
		}
		
		function getCurrentUserLogin() {
			return $this->WebsiteApi->currentUser()->user_login;
		}
		
		function getCurrentUserId() {
			return $this->WebsiteApi->currentUser()->ID;
		}
		
		function userButtonsShortcode() {
			$URLFinder = new HfUrlFinder();
			$welcome = 'Welcome back, ' . $this->getCurrentUserLogin() . ' | ';
			$logInOutLink = wp_loginout( $URLFinder->getCurrentPageUrl(), false );
			if ( is_user_logged_in() ) {
				$settingsURL = $URLFinder->getURLByTitle('Settings');
				return $welcome . $logInOutLink . ' | <a href="'.$settingsURL.'">Settings</a>';
			} else {
				$registerURL = $URLFinder->getURLByTitle('Register');
				return $logInOutLink . ' | <a href="'.$registerURL.'">Register</a>';
			}
				
		}

		function requireLogin() {
			return 'You must be logged in to view this page. ' . wp_login_form( array('echo' => false) );
		}

		function getUsernameByID($userID, $initialCaps = false) {
			$user = get_userdata( $userID );
			if ($initialCaps === true) {
				return ucwords($user->user_login);
			} else {
				return $user->user_login;
			}
		}

        function sendInvitation( $inviterID, $address, $daysToExpire ) {
            $inviteID			= $this->Messenger->generateInviteID();
            $inviteURL			= $this->Messenger->generateInviteURL($inviteID);
            $inviterUsername	= $this->getUsernameByID( $inviterID, true );
            $subject			= $inviterUsername . ' just invited you to join them at HabitFree!';
            $body				= "<p>HabitFree is a community of young people striving for God's ideal of purity and Christian freedom.</p><p><a href='" . $inviteURL . "'>Click here to join " . $inviterUsername . " in his quest!</a></p>";

            $emailID = $this->Messenger->sendEmailToAddress($address, $subject, $body);

            $expirationDate = date('Y-m-d H:i:s', strtotime('+'.$daysToExpire.' days'));

            if ($emailID !== false) {
                $this->Messenger->recordInvite($inviteID, $inviterID, $address, $emailID, $expirationDate);
            }

            return $inviteID;
        }
	}
}

?>