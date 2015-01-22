<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once( dirname( dirname( __FILE__ ) ) . '/HfTestCase.php' );

class TestGoalsShortcode extends HfTestCase {
    // Helper Functions

    private function setValidReportRequest555() {
        $_GET['n'] = 555;
        $this->setReturnValue( $this->MockMailer, 'isReportRequestValid', true );
    }

    private function setDefaultIterables() {
        $this->setReturnValue( $this->MockGoals, 'getGoalSubscriptions', array() );

        $mockPartners = $this->makeMockPartners( 'Jack' );
        $this->setReturnValue( $this->MockUserManager, 'getPartners', $mockPartners );
    }

    private function makeMockPartners( $name ) {
        $mockPartner             = new stdClass();
        $mockPartner->ID         = 1;
        $mockPartner->user_login = $name;

        return array($mockPartner);
    }

    private function setReportSuccess() {
        $this->setDefaultIterables();
        $_POST['submit'] = '';
        $_POST[1]        = '1';
    }

    private function setDefaultQuotationValues() {
        $this->setReturnValue( $this->MockPhpLibrary, 'randomKeyFromArray', 0 );
        $MockQuotation = $this->makeMockQuotation();
        $this->setReturnValue( $this->MockMysqlDatabase, 'getQuotations', array($MockQuotation) );
    }

    private function makeMockQuotation() {
        $MockQuotation            = new stdClass();
        $MockQuotation->post_content = 'hello';
        $MockQuotation->post_title = 'Nathan';

        return $MockQuotation;
    }

    // Tests

    public function testGoalsShortcodeGenerateReportNoticeEmail() {
        $_POST['submit'] = '';
        $_POST[1]        = '1';
        $_POST[2]        = '0';

        $this->setDefaultQuotationValues();

        $mockPartners = $this->makeMockPartners( 'Dan' );
        $this->setReturnValue( $this->MockUserManager, 'getPartners', $mockPartners );

        $mockGoalSubs = array(new stdClass());
        $this->setReturnValue( $this->MockGoals, 'getGoalSubscriptions', $mockGoalSubs );

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue( $this->MockUserManager, 'getUsernameById', 'Don' );
        $this->setReturnValues( $this->MockGoals, 'getGoalTitle', array('Eat durian', 'Go running') );

        $Goals = new HfGoalsShortcode(
            $this->MockUserManager,
            $this->MockMailer,
            $this->MockPageLocator,
            $this->MockGoals,
            $this->MockSecurity,
            $this->Factory->makeMarkupGenerator(),
            $this->MockPhpLibrary,
            $this->MockMysqlDatabase
        );

        $expectedBody =
            "<p>Hello, Dan,</p><p>Your friend Don just reported on their progress. Here's how they're doing:</p><ul><li>Eat durian: Success</li><li>Go running: Setback</li></ul>";

        $this->expectOnce( $this->MockMailer, 'sendEmailToUser', array(1, 'Don just reported', $expectedBody) );

        $Goals->getOutput();
    }

    public function testGoalsShortcodeGenerateReportNoticeEmailScenarioTwo() {
        $this->setDefaultIterables();
        $_POST['submit'] = '';
        $_POST[1]        = '0';

        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue( $this->MockUserManager, 'getUsernameById', 'Jim' );
        $this->setReturnValue( $this->MockGoals, 'getGoalTitle', 'Eat durian' );

        $Goals = new HfGoalsShortcode(
            $this->MockUserManager,
            $this->MockMailer,
            $this->MockPageLocator,
            $this->MockGoals,
            $this->MockSecurity,
            $this->Factory->makeMarkupGenerator(),
            $this->MockPhpLibrary,
            $this->MockMysqlDatabase
        );

        $expectedBody =
            "<p>Hello, Jack,</p><p>Your friend Jim just reported on their progress. Here's how they're doing:</p><ul><li>Eat durian: Setback</li></ul>";

        $this->expectOnce( $this->MockMailer, 'sendEmailToUser', array(1, 'Jim just reported', $expectedBody) );

        $Goals->getOutput();
    }

    public function testGoalsShortcodeClassExists() {
        $this->assertTrue( class_exists( 'HfGoalsShortcode' ) );
    }

    public function testGoalsShortcodeOutputsAnything() {
        $GoalsShortcode = $this->Factory->makeGoalsShortcode();
        $output         = $GoalsShortcode->getOutput();

        $this->assertTrue( strlen( $output ) > 0 );
    }

    public function testGoalsShortcodeChecksNonce() {
        $_GET['n'] = 555;
        $this->expectOnce( $this->MockMailer, 'isReportRequestValid', array(555) );
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeDoesNotAuthenticateUserWhenNoNonce() {
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );
        $this->expectOnce( $this->MockSecurity, 'requireLogin' );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeDoesNotDeleteReportRequest() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $this->expectNever( $this->MockMailer, 'deleteReportRequest' );
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeGetsUserIdFromReportRequest() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $this->expectOnce( $this->MockMailer, 'getReportRequestUserId', array(555) );
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeGetsUserIdFromUserManager() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $this->expectOnce( $this->MockUserManager, 'getCurrentUserId' );
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeUpdatesExpirationDate() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $this->expectOnce( $this->MockMailer, 'updateReportRequestExpirationDate' );
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeSetsExpirationDateToOneHourInFuture() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $this->expectOnce( $this->MockMailer, 'updateReportRequestExpirationDate', array(555, 10 + ( 60 * 60 )) );
        $this->setReturnValue( $this->MockPhpLibrary, 'getCurrentTime', 10 );
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeDeletesReportRequestIfReportSubmitted() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $_POST['submit'] = '';

        $this->expectOnce( $this->MockMailer, 'deleteReportRequest', array(555) );

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeUpdatsReportRequestWhenUserLoggedIn() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->expectOnce( $this->MockMailer, 'updateReportRequestExpirationDate' );
        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeDoesNotUpdateReportRequestWhenReportSubmitted() {
        $this->setValidReportRequest555();
        $this->setDefaultIterables();
        $_POST['submit'] = '';
        $this->expectNever( $this->MockMailer, 'updateReportRequestExpirationDate' );
        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeIgnoresInvalidNonces() {
        $this->setDefaultIterables();
        $_GET['n'] = 666;
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue( $this->MockMailer, 'isReportRequestValid', false );
        $this->expectNever( $this->MockMailer, 'updateReportRequestExpirationDate' );
        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeUsesReportRequestToGetUsernameWhenUserNotLoggedIn() {
        $this->setValidReportRequest555();
        $this->setReportSuccess();
        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockMailer, 'getReportRequestUserId', 9 );
        $this->setReturnValue( $this->MockMailer, 'isReportRequestValid', true );
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', false );
        $this->expectOnce( $this->MockMailer, 'getReportRequestUserId' );
        $this->expectOnce( $this->MockUserManager, 'getUsernameById', array(9) );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeDeletesExpiredReportRequests() {
        $this->expectOnce( $this->MockMailer, 'deleteExpiredReportRequests' );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeAsksForQuotationForSuccess() {
        $this->setReportSuccess();
        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );

        $this->expectOnce( $this->MockMysqlDatabase, 'getQuotations', array('For Success') );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeSelectsRandomQuotation() {
        $this->setReportSuccess();
        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue($this->MockMysqlDatabase, 'getQuotations', array($this->setDefaultQuotationValues()));

        $this->expectOnce($this->MockPhpLibrary, 'randomKeyFromArray', array(array($this->makeMockQuotation())));

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeUsesMarkupGeneratorToCreateSuccessMessage() {
        $this->setReportSuccess();
        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );

        $this->expectOnce( $this->mockMarkupGenerator, 'makeSuccessMessage', array('Thanks for checking in!') );

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeMakesQuoteMessage() {
        $this->setReportSuccess();
        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue($this->MockMysqlDatabase, 'getQuotations', 'duck');

        $this->expectOnce($this->mockMarkupGenerator, 'makeQuoteMessage');

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeUsesRandomQuotationToMakeQuoteMessage() {
        $this->setReportSuccess();
        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue($this->MockMysqlDatabase, 'getQuotations', array($this->makeMockQuotation()));

        $this->expectOnce($this->mockMarkupGenerator, 'makeQuoteMessage', array($this->makeMockQuotation()));

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeDisplaysRandomQuotationOnSuccess() {
        $GoalsShortcode = new HfGoalsShortcode(
            $this->MockUserManager,
            $this->MockMailer,
            $this->mockAssetLocator,
            $this->MockGoals,
            $this->MockSecurity,
            $this->Factory->makeMarkupGenerator(),
            $this->MockPhpLibrary,
            $this->MockMysqlDatabase
        );

        $this->setReportSuccess();
        $this->setDefaultQuotationValues();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue($this->MockMysqlDatabase, 'getQuotations', array($this->makeMockQuotation()));

        $haystack = $GoalsShortcode->getOutput();
        $needle = '<p class="quote">"hello" — Nathan</p>';

        $this->assertContains($needle, $haystack);
    }

    public function testGoalsShortcodeGetsQuotationForSetbackWhenSetbackOccurred() {
        $this->setDefaultIterables();
        $_POST['submit'] = '';
        $_POST[1]        = '0';
        $this->setDefaultQuotationValues();
        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );

        $this->expectOnce($this->MockMysqlDatabase, 'getQuotations', array('For Setback'));

        $this->MockedGoalsShortcode->getOutput();
    }

    public function testGoalsShortcodeDoesntDisplayEmptyQuotation() {
        $this->setReportSuccess();

        $this->setReturnValue( $this->MockUserManager, 'isUserLoggedIn', true );
        $this->setReturnValue($this->MockMysqlDatabase, 'getQuotations', array());

        $this->expectNever($this->mockMarkupGenerator, 'makeQuoteMessage');

        $this->MockedGoalsShortcode->getOutput();
    }
}
