<?php

class HfMysqlDatabase implements Hf_iDatabase {
    private $Cms;
    private $CodeLibrary;

    public function HfMysqlDatabase( Hf_iCms $ContentManagementSystem, Hf_iCodeLibrary $CodeLibrary ) { //constructor
        $this->Cms         = $ContentManagementSystem;
        $this->CodeLibrary = $CodeLibrary;
    }

    public function installDb() {
        $currentDbVersion  = "4.7";
        $previousDbVersion = $this->Cms->getOption( "hfDbVersion" );

        if ( $previousDbVersion != $currentDbVersion ) {
            $this->updateDatabaseSchema();
            $this->populateTables();
            update_option( "hfDbVersion", $currentDbVersion );
        }
    }

    private function updateDatabaseSchema() {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $emailTableSql = "CREATE TABLE " . $prefix . "hf_email (
					emailID int NOT NULL AUTO_INCREMENT,
					sendTime timestamp DEFAULT current_timestamp NOT NULL,
					subject VARCHAR(500) NOT NULL,
					body text NOT NULL,
					userID int NOT NULL,
					deliveryStatus bit(1) DEFAULT 0 NOT NULL,
					openTime datetime NULL,
					address varchar(80) NULL,
					KEY userID (userID),
					PRIMARY KEY  (emailID)
				);";

        $goalTableSql = "CREATE TABLE " . $prefix . "hf_goal (
					goalID int NOT NULL AUTO_INCREMENT,
					title VARCHAR(500) NOT NULL,
					description text NULL,
					thumbnail VARCHAR(80) NULL,
					isPositive bit(1) DEFAULT 0 NOT NULL,
					isPrivate bit(1) DEFAULT 1 NOT NULL,
					creatorID int NULL,
					dateCreated timestamp DEFAULT current_timestamp NOT NULL,
					KEY creatorID (creatorID),
					PRIMARY KEY  (goalID)
				);";

        $reportTableSql = "CREATE TABLE " . $prefix . "hf_report (
					reportID int NOT NULL AUTO_INCREMENT,
					userID int NOT NULL,
					goalID int NOT NULL,
					referringEmailID INT NULL,
					isSuccessful tinyint NOT NULL,
					date timestamp DEFAULT current_timestamp NOT NULL,
					KEY userID (userID),
					KEY goalID (goalID),
					KEY referringEmailID (referringEmailID),
					PRIMARY KEY  (reportID)
				);";

        $userGoalTableSql = "CREATE TABLE " . $prefix . "hf_user_goal (
					userID int NOT NULL,
					goalID int NOT NULL,
					dateStarted timestamp DEFAULT current_timestamp NOT NULL,
					isActive bit(1) DEFAULT 1 NOT NULL,
					PRIMARY KEY  (userID, goalID)
				);";

        $levelTableSql = "CREATE TABLE " . $prefix . "hf_level (
					levelID int NOT NULL,
					title VARCHAR(500) NOT NULL,
					description text NULL,
					size int NOT NULL,
					emailInterval int NOT NULL,
					target int NOT NULL,
					PRIMARY KEY  (levelID)
				);";

        $inviteTableSql = "CREATE TABLE " . $prefix . "hf_invite (
					inviteID varchar(250) NOT NULL,
					inviterID int NOT NULL,
					inviteeEmail VARCHAR(80) NOT NULL,
					emailID int NOT NULL,
					expirationDate datetime NOT NULL,
					KEY inviterID (inviterID),
					KEY emailID (emailID),
					PRIMARY KEY  (inviteID)
				);";

        $relationshipTableSql = "CREATE TABLE " . $prefix . "hf_relationship (
					userID1 int NOT NULL,
					userID2 int NOT NULL,
					PRIMARY KEY  (userID1, userID2)
				);";

        $reportRequestTableSql = "CREATE TABLE " . $prefix . "hf_report_request (
					requestID varchar(250) NOT NULL,
					userID int NOT NULL,
					emailID int NOT NULL,
					KEY requestID (requestID),
					KEY userID (userID),
					KEY emailID (emailID),
					PRIMARY KEY  (requestID)
				);";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $emailTableSql );
        dbDelta( $goalTableSql );
        dbDelta( $reportTableSql );
        dbDelta( $userGoalTableSql );
        dbDelta( $levelTableSql );
        dbDelta( $inviteTableSql );
        dbDelta( $relationshipTableSql );
        dbDelta( $reportRequestTableSql );
    }

    private function populateTables() {
        $this->populateGoalTable();
        $this->populateLevelsTable();
    }

    private function populateGoalTable() {
        $defaultGoal = array(
            'goalID'     => 1,
            'title'      => 'Pornography Abstinence',
            'isPositive' => 1,
            'isPrivate'  => 0
        );

        $table = $this->Cms->getDbPrefix() . 'hf_goal';
        $this->Cms->insertOrReplaceRow( $table, $defaultGoal, array( '%d', '%s', '%d', '%d' ) );
    }

    private function populateLevelsTable() {
        $defaultLevel0 = array(
            'levelID'       => 0,
            'title'         => 'Hibernation',
            'size'          => 0,
            'emailInterval' => 0,
            'target'        => 0
        );

        $defaultLevel1 = array(
            'levelID'       => 1,
            'title'         => 'Dawn',
            'size'          => 2,
            'emailInterval' => 1,
            'target'        => 14
        );

        $defaultLevel2 = array(
            'levelID'       => 2,
            'title'         => 'Breach',
            'size'          => 5,
            'emailInterval' => 7,
            'target'        => 30
        );

        $defaultLevel3 = array(
            'levelID'       => 3,
            'title'         => 'Progress',
            'size'          => 10,
            'emailInterval' => 14,
            'target'        => 90
        );

        $defaultLevel4 = array(
            'levelID'       => 4,
            'title'         => 'Conquest',
            'size'          => 15,
            'emailInterval' => 30,
            'target'        => 365
        );

        $defaultLevel5 = array(
            'levelID'       => 5,
            'title'         => 'Conquering',
            'size'          => 30,
            'emailInterval' => 90,
            'target'        => 1095 // 3 years
        );

        $defaultLevel6 = array(
            'levelID'       => 6,
            'title'         => 'Triumph',
            'size'          => 60,
            'emailInterval' => 365,
            'target'        => 1095 // 3 years
        );

        $defaultLevel7 = array(
            'levelID'       => 7,
            'title'         => 'Vigilance',
            'size'          => 0,
            'emailInterval' => 365,
            'target'        => 0
        );

        $levelFormat = array(
            '%d',
            '%s',
            '%d',
            '%d',
            '%d',
        );

        $table = $this->Cms->getDbPrefix() . 'hf_level';

        $this->Cms->insertOrReplaceRow( $table, $defaultLevel0, $levelFormat );
        $this->Cms->insertOrReplaceRow( $table, $defaultLevel1, $levelFormat );
        $this->Cms->insertOrReplaceRow( $table, $defaultLevel2, $levelFormat );
        $this->Cms->insertOrReplaceRow( $table, $defaultLevel3, $levelFormat );
        $this->Cms->insertOrReplaceRow( $table, $defaultLevel4, $levelFormat );
        $this->Cms->insertOrReplaceRow( $table, $defaultLevel5, $levelFormat );
        $this->Cms->insertOrReplaceRow( $table, $defaultLevel6, $levelFormat );
        $this->Cms->insertOrReplaceRow( $table, $defaultLevel7, $levelFormat );
    }

    public function generateEmailId() {
        $table     = 'hf_email';
        $tableName = $this->Cms->getDbPrefix() . $table;
        $query     = $this->Cms->prepareQuery( 'SELECT max(emailID) FROM %s', array( $tableName ) );

        return $this->Cms->getVar( $query ) + 1;
    }

    public function daysSinceLastEmail( $userID ) {
        $table = $this->Cms->getDbPrefix() . 'hf_email';

        $query = $this->Cms->prepareQuery(
            'SELECT sendTime FROM %s WHERE userID = %d ORDER BY emailID DESC LIMIT 1',
            array( $table, $userID )
        );

        $dateOfLastEmail = $this->Cms->getVar( $query );
        $timeOfLastEmail = strtotime( $dateOfLastEmail );
        $timeNow         = $this->CodeLibrary->getCurrentTime();
        $secondsInADay   = 86400;

        return ( $timeNow - $timeOfLastEmail ) / $secondsInADay;
    }

    public function daysSinceSecondToLastEmail( $userId ) {
        $timeNow = $this->CodeLibrary->getCurrentTime();
        $table   = $this->Cms->getDbPrefix() . 'hf_email';

        $query = $this->Cms->prepareQuery(
            'SELECT sendTime FROM (SELECT * FROM %s WHERE userID = %d ORDER BY emailID DESC LIMIT 2) AS T ORDER BY emailID LIMIT 1',
            array( $table, $userId )
        );

        $timeString              = $this->Cms->getVar( $query );
        $timeOfSecondToLastEmail = strtotime( $timeString );
        $secondsInADay           = 86400;

        return ( $timeNow - $timeOfSecondToLastEmail ) / $secondsInADay;
    }

    public function recordEmail( $userID, $subject, $message, $emailID = null, $emailAddress = null ) {
        $data = array(
            'subject' => $subject,
            'body'    => $message,
            'userID'  => $userID,
            'emailID' => $emailID,
            'address' => $emailAddress
        );

        $table = $this->Cms->getDbPrefix() . "hf_email";
        $data  = $this->removeNullValuePairs( $data );

        $this->Cms->insertIntoDb( $table, $data, array( '%s', '%s', '%d', '%d', '%s' ) );
    }

    public function removeNullValuePairs( $array ) {
        foreach ( $array as $key => $value ) {
            if ( $value === null ) {
                unset( $array[$key] );
            }
        }

        return $array;
    }

    public function timeOfFirstSuccess( $goalId, $userId ) {
        $table = $this->Cms->getDbPrefix() . 'hf_report';

        $query = $this->Cms->prepareQuery(
            'SELECT date FROM %s
            WHERE goalID = %d AND userID = %d
            AND reportID=( SELECT min(reportID) FROM %s WHERE isSuccessful = 1)',
            array( $table, $goalId, $userId, $table )
        );

        $timeString = $this->Cms->getVar( $query );

        return strtotime( $timeString );
    }

    public function timeOfLastSuccess( $goalId, $userId ) {
        $table = $this->Cms->getDbPrefix() . 'hf_report';

        $query = $this->Cms->prepareQuery(
            'SELECT date FROM %s
            WHERE goalID = %d AND userID = %d
            AND reportID=( SELECT max(reportID) FROM %s WHERE isSuccessful = 1)',
            array( $table, $goalId, $userId, $table )
        );

        $timeString = $this->Cms->getVar( $query );

        return strtotime( $timeString );
    }

    public function timeOfLastFail( $goalId, $userId ) {
        $table = $this->Cms->getDbPrefix() . 'hf_report';

        $query = $this->Cms->prepareQuery(
            'SELECT date FROM %s
            WHERE goalID = %d AND userID = %d
            AND reportID=( SELECT max(reportID) FROM %s WHERE NOT isSuccessful = 1)',
            array( $table, $goalId, $userId, $table )
        );

        $timeString = $this->Cms->getVar( $query );

        return strtotime( $timeString );
    }

    public function getLevel( $daysOfSuccess ) {
        $where = $this->Cms->prepareQuery(
            'target > %d ORDER BY target ASC',
            array( $daysOfSuccess )
        );

        return $this->Cms->getRow( 'hf_level', $where );
    }

    public function daysSinceLastReport( $goalId, $userId ) {
        $table = $this->Cms->getDbPrefix() . 'hf_report';

        $query = $this->Cms->prepareQuery(
            'SELECT date FROM %s
            WHERE goalID = %d AND userID = %d
            AND reportID=( SELECT max(reportID) FROM %s )',
            array( $table, $goalId, $userId, $table )
        );

        $dateInSecondsOfLastReport = strtotime( $this->Cms->getVar( $query ) );
        $secondsInADay             = 86400;

        return ( time() - $dateInSecondsOfLastReport ) / $secondsInADay;
    }

    public function daysSinceAnyReport( $userId ) {
        $table = $this->Cms->getDbPrefix() . 'hf_report';

        $query = $this->Cms->prepareQuery(
            'SELECT date FROM %s
            WHERE userID = %d
            AND reportID=( SELECT max(reportID) FROM %s )',
            array( $table, $userId, $table )
        );

        $dateInSecondsOfLastReport = strtotime( $this->Cms->getVar( $query ) );
        $secondsInADay             = 86400;

        return ( time() - $dateInSecondsOfLastReport ) / $secondsInADay;
    }

    public function idOfLastEmail() {
        $table = $this->Cms->getDbPrefix() . 'hf_email';

        $query = $this->Cms->prepareQuery(
            'SELECT max(emailID) FROM %s',
            array( $table )
        );

        return intval( $this->Cms->getVar( $query ) );
    }

    public function getInviterID( $nonce ) {
        $where = $this->Cms->prepareQuery(
            "inviteID = %s",
            array( $nonce )
        );

        $invite = $this->Cms->getRow( 'hf_invite', $where );

        return intval( $invite->inviterID );
    }

    public function createRelationship( $userOneID, $userTwoID ) {
        if ( $userOneID < $userTwoID ) {
            $row = array(
                'userID1' => $userOneID,
                'userID2' => $userTwoID
            );
        } else {
            $row = array(
                'userID1' => $userTwoID,
                'userID2' => $userOneID
            );
        }

        $table = $this->Cms->getDbPrefix() . 'hf_relationship';
        $this->Cms->insertOrReplaceRow( $table, $row, array( '%d', '%d' ) );
    }

    public function recordAccountabilityReport( $userID, $goalID, $isSuccessful, $emailID = null ) {
        $data = array(
            'userID'           => $userID,
            'goalID'           => $goalID,
            'isSuccessful'     => $isSuccessful,
            'referringEmailID' => $emailID
        );

        $table = $this->Cms->getDbPrefix() . 'hf_report';
        $data  = $this->removeNullValuePairs( $data );

        $this->Cms->insertIntoDb( $table, $data, array( '%d', '%d', '%d', '%d' ) );
    }

    public function isEmailValid( $userID, $emailID ) {
        $email = $this->Cms->getRow( 'hf_email',
            'userID = ' . $userID .
            ' AND emailID = ' . $emailID );

        return $email != null;
    }

    public function getGoalSubscriptions( $userID ) {
        return $this->Cms->getRows( 'hf_user_goal', 'userID = ' . $userID );
    }

    public function deleteInvite( $inviteID ) {
        $table = $this->Cms->getDbPrefix() . 'hf_invite';
        $where = array( 'inviteID' => $inviteID );

        $this->Cms->deleteRows( $table, $where );
    }

    public function getPartners( $userId ) {
        $usersTable         = $this->Cms->getDbPrefix() . 'users';
        $relationshipsTable = $this->Cms->getDbPrefix() . 'hf_relationship';

        $query = $this->Cms->prepareQuery(
            'SELECT * FROM %s INNER JOIN %s
            WHERE (userID1 = ID OR userID2 = ID)
            AND (userID1 = %d OR userID2 = %d) AND ID != $d',
            array( $usersTable, $relationshipsTable, $userId, $userId, $userId )
        );

        return $this->Cms->getResults( $query );
    }

    public function getGoal( $goalId ) {
        $where = 'goalID = ' . $goalId;

        return $this->Cms->getRow( 'hf_goal', $where );
    }

    public function recordReportRequest( $requestId, $userId, $emailId, $expirationDate ) {
        $data = array(
            'requestID'      => $requestId,
            'userID'         => $userId,
            'emailID'        => $emailId,
            'expirationDate' => $expirationDate
        );

        $table = $this->Cms->getDbPrefix() . 'hf_report_request';
        $data  = $this->removeNullValuePairs( $data );

        $this->Cms->insertIntoDb( $table, $data, array( '%d', '%d', '%d', '%s' ) );
    }

    public function isReportRequestValid( $requestId ) {
        $table = $this->Cms->getDbPrefix() . 'hf_report_request';

        $query = $this->Cms->prepareQuery(
            "SELECT * FROM %s WHERE requestID = %d",
            array( $table, $requestId )
        );

        return $this->Cms->getResults( $query ) != null;
    }

    public function deleteReportRequest( $requestId ) {
        $table = $this->Cms->getDbPrefix() . 'hf_report_request';
        $where = array( 'requestID' => $requestId );

        $this->Cms->deleteRows( $table, $where );
    }

    public function getReportRequestUserId( $requestId ) {
        $ReportRequest = $this->Cms->getRow( 'hf_report_request', "requestID = '" . $requestId . "'" );

        return $ReportRequest->userID;
    }

    public function updateReportRequestExpirationDate( $requestId, $expirationTime ) {
        $data = array(
            'expirationDate' => date( 'Y-m-d H:i:s', $expirationTime )
        );

        $where = array(
            'requestID' => $requestId
        );

        $tableName = $this->Cms->getDbPrefix() . 'hf_report_request';

        $this->Cms->updateRowsSafe( $tableName, $data, $where );
    }

    public function getAllInvites() {
        return $this->Cms->getRows( 'hf_invite', null );
    }

    public function getAllReportRequests() {
        return $this->Cms->getRows( 'hf_report_request', null );
    }

    public function getQuotations( $context ) {
        $contextId = $this->getContextId( $context );

        $prefix     = $this->Cms->getDbPrefix();
        $postsTable = $prefix . 'posts';
        $termsTable = $prefix . 'term_relationships';

        var_dump( $context );
        var_dump( $postsTable );
        var_dump( $termsTable );
        var_dump( $contextId );

        $query = $this->Cms->prepareQuery(
            "SELECT * FROM %s INNER JOIN %s WHERE post_type =  'hf_quotation' AND post_status =  'publish' AND object_id = id AND term_taxonomy_id = %d",
            array( $postsTable, $termsTable, $contextId )
        );

        return $this->Cms->getResults( $query );
    }

    private function getContextId( $context ) {
        $prefix = $this->Cms->getDbPrefix();

        $query = $this->Cms->prepareQuery(
            "SELECT term_id FROM %s WHERE name = %s",
            array( $prefix . 'terms', $context )
        );

        return $this->Cms->getVar( $query );
    }

    public function deleteRelationship( $userId1, $userId2 ) {
        $userId1 = intval( $userId1 );
        $userId2 = intval( $userId2 );
        $table   = $this->Cms->getDbPrefix() . 'hf_relationship';
        $where   = $this->createDeleteRelationshipWhereCriteria( $userId1, $userId2 );
        $this->Cms->deleteRows( $table, $where );
    }

    private function createDeleteRelationshipWhereCriteria( $userId1, $userId2 ) {
        if ( $userId1 < $userId2 ) {
            return array( 'userID1' => $userId1, 'userID2' => $userId2 );
        } else {
            return array( 'userID1' => $userId2, 'userID2' => $userId1 );
        }
    }

    public function setDefaultGoalSubscription( $userId ) {
        $sub   = array(
            'userID' => $userId,
            'goalID' => 1
        );
        $table = $this->Cms->getDbPrefix() . 'hf_user_goal';
        $this->Cms->insertOrReplaceRow( $table, $sub, array( '%d', '%d' ) );
    }

    public function recordInvite( $inviteID, $inviterID, $inviteeEmail, $emailID, $expirationDate ) {
        $data = array(
            'inviteID'       => $inviteID,
            'inviterID'      => $inviterID,
            'inviteeEmail'   => $inviteeEmail,
            'emailID'        => $emailID,
            'expirationDate' => $expirationDate
        );

        $table = $this->Cms->getDbPrefix() . "hf_invite";
        $this->Cms->insertIntoDb( $table, $data, array( '%d', '%d', '%s', '%d', '%s' ) );
    }
}