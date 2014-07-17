<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once( dirname( dirname( __FILE__ ) ) . '/HfTestCase.php' );

class TestGoals extends HfTestCase {
    public function testSendReportRequestEmailsChecksThrottling() {
        $this->setMockReturns();

        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastReport', 2 );
        $this->setReturnValue( $this->MockMessenger, 'isThrottled', true );

        $this->expectAtLeastOnce( $this->MockMessenger, 'isThrottled' );

        $this->GoalsWithMockedDependencies->sendReportRequestEmails();
    }

    private function setMockReturns() {
        $mockUsers = $this->makeMockUsers();
        $this->setReturnValue( $this->MockCms, 'getSubscribedUsers', $mockUsers );

        $mockGoalSubs = $this->makeMockGoalSubs();
        $this->setReturnValue( $this->MockDatabase, 'getGoalSubscriptions', $mockGoalSubs );

        $mockLevel = $this->makeMockLevel();
        $this->setReturnValue( $this->MockDatabase, 'getLevel', $mockLevel );
    }

    private function makeMockUsers() {
        $mockUser     = new stdClass();
        $mockUser->ID = 1;
        $mockUsers    = array($mockUser);

        return $mockUsers;
    }

    private function makeMockGoalSubs() {
        $mockGoalSub  = $this->makeMockGoalSub();
        $mockGoalSubs = array($mockGoalSub);

        return $mockGoalSubs;
    }

    private function makeMockLevel() {
        $mockLevel                = new stdClass();
        $mockLevel->levelID       = 2;
        $mockLevel->title         = 'Title';
        $mockLevel->target        = 14;
        $mockLevel->emailInterval = 1;

        return $mockLevel;
    }

    private function makeMockGoalSub() {
        $mockGoalSub         = new stdClass();
        $mockGoalSub->goalID = 1;
        $mockGoalSub->userID = 7;

        return $mockGoalSub;
    }

    public function testSendReportRequestEmailsSendsEmailWhenReportDue() {
        $this->setMockReturns();

        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastEmail', 2 );
        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastReport', 2 );
        $this->setReturnValue( $this->MockMessenger, 'isThrottled', false );

        $this->expectAtLeastOnce( $this->MockMessenger, 'sendReportRequestEmail' );

        $this->GoalsWithMockedDependencies->sendReportRequestEmails();
    }

    public function testSendReportRequestEmailsDoesNotSendEmailWhenReportNotDue() {
        $this->setMockReturns();

        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastEmail', 2 );
        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastReport', 0 );

        $this->expectNever( $this->MockMessenger, 'sendReportRequestEmail' );

        $this->GoalsWithMockedDependencies->sendReportRequestEmails();
    }

    public function testSendReportRequestEmailsDoesNotSendEmailWhenUserThrottled() {
        $this->setMockReturns();

        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastEmail', 2 );
        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastReport', 5 );
        $this->setReturnValue( $this->MockMessenger, 'IsThrottled', true );

        $this->expectNever( $this->MockMessenger, 'sendReportRequestEmail' );

        $this->GoalsWithMockedDependencies->sendReportRequestEmails();
    }

    public function testCurrentLevelTarget() {
        $mockLevel = $this->makeMockLevel();
        $this->setReturnValue( $this->MockDatabase, 'getLevel', $mockLevel );

        $target = $this->GoalsWithMockedDependencies->currentLevelTarget( 5 );

        $this->assertEquals( $target, 14 );
    }

    public function testGetGoalTitle() {
        $mockGoal        = new stdClass();
        $mockGoal->title = 'Eat durian';
        $this->setReturnValue( $this->MockDatabase, 'getGoal', $mockGoal );

        $goalTitle = $this->GoalsWithMockedDependencies->getGoalTitle( 1 );

        $this->assertEquals( $mockGoal->title, $goalTitle );
    }

    public function testRecordAccountabilityReport() {
        $this->expectOnce( $this->MockDatabase, 'recordAccountabilityReport', array(1, 2, 3, 4) );

        $this->GoalsWithMockedDependencies->recordAccountabilityReport( 1, 2, 3, 4 );
    }

    public function testGetGoalSubscriptions() {
        $expected = array(1, 2, 3, 4);

        $this->setReturnValue( $this->MockDatabase, 'getGoalSubscriptions', $expected );

        $actual = $this->GoalsWithMockedDependencies->getGoalSubscriptions( 1 );

        $this->assertEquals( $expected, $actual );
    }

    public function testSendEmailReportRequests() {
        $Factory = new HfFactory();
        $Goals   = $Factory->makeGoals();
        $Goals->sendReportRequestEmails();
    }

    public function testIsAnyGoalDueGetsGoalSubscriptionsFromDatabase() {
        $this->setMockReturns();

        $this->expectOnce( $this->MockDatabase, 'getGoalSubscriptions', array(1) );

        $this->GoalsWithMockedDependencies->sendReportRequestEmails();
    }

    public function testGenerateGoalCardUsesDatabaseGetGoalMethod() {
        $MockGoal              = new stdClass();
        $MockGoal->title       = 'Title';
        $MockGoal->description = 'Description';
        $this->setReturnValue( $this->MockDatabase, 'getGoal', $MockGoal);

        $MockLevel = $this->makeMockLevel();
        $this->setReturnValue( $this->MockDatabase, 'getLevel', $MockLevel );

        $this->expectOnce( $this->MockDatabase, 'getGoal', array(1) );

        $MockSub = $this->makeMockGoalSub();
        $this->GoalsWithMockedDependencies->generateGoalCard( $MockSub );
    }
} 