<?php

require_once( dirname( __FILE__ ) . '/../hf-accountability.php' );

class UnitWpSimpleTest extends UnitTestCase {
    private $functionsFacade;
    private $Factory;

    public function __construct() {
        $this->Factory = new HfFactory();
    }

    public function setUp() {

    }

//    Helper Functions

    private function makeUserManagerMockDependencies() {
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfMailer' );
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfPhpLibrary' );

        $UrlFinder = new MockHfUrlFinder();
        $Database  = new MockHfMysqlDatabase();
        $Messenger = new MockHfMailer();
        $Cms       = new MockHfWordPressInterface();
        $PhpApi    = new MockHfPhpLibrary();

        return array($UrlFinder, $Database, $Messenger, $Cms, $PhpApi);
    }

    private function makeRegisterShortcodeMockDependencies() {
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfPhpLibrary' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfUserManager' );

        $UrlFinder   = new MockHfUrlFinder();
        $Database    = new MockHfMysqlDatabase();
        $PhpLibrary  = new MockHfPhpLibrary();
        $Cms         = new MockHfWordPressInterface();
        $UserManager = new MockHfUserManager();

        return array($UrlFinder, $Database, $PhpLibrary, $Cms, $UserManager);
    }

    private function makeDatabaseMockDependencies() {
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfPhpLibrary' );

        $Cms         = new MockHfWordPressInterface();
        $CodeLibrary = new MockHfPhpLibrary();

        return array($Cms, $CodeLibrary);
    }

    private function makeLogInShortcodeMockDependencies() {
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfPhpLibrary' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfUserManager' );

        $UrlFinder   = new MockHfUrlFinder();
        $PhpLibrary  = new MockHfPhpLibrary();
        $Cms         = new MockHfWordPressInterface();
        $UserManager = new MockHfUserManager();

        return array($UrlFinder, $PhpLibrary, $Cms, $UserManager);
    }

    private function classImplementsInterface( $class, $interface ) {
        $interfacesImplemented = class_implements( $class );

        return in_array( $interface, $interfacesImplemented );
    }

//    Tests

    public function testTestingFramework() {
        $this->assertEqual( 1, 1 );
    }

    public function testGettingCurrentUserLogin() {
        $UserManager = $this->Factory->makeUserManager();
        $user         = wp_get_current_user();

        $this->assertEqual( $UserManager->getCurrentUserLogin(), $user->user_login );
    }

    public function testShortcodeRegistration() {
        $this->assertEqual( shortcode_exists( 'hfSettings' ), true );
    }

    public function testDbDataNullRemoval() {
        $Database = $this->Factory->makeDatabase();

        $data = array(
            'one'   => 'big one',
            'two'   => 'two',
            'three' => null,
            'four'  => 4,
            'five'  => 'null',
            'six'   => 0,
            'seven' => false
        );

        $expectedData = array(
            'one'   => 'big one',
            'two'   => 'two',
            'four'  => 4,
            'five'  => 'null',
            'six'   => 0,
            'seven' => false
        );

        $this->assertEqual( $Database->removeNullValuePairs( $data ), $expectedData );
    }

    public function testRandomStringCreationLength() {
        $Security     = $this->Factory->makeSecurity();
        $randomString = $Security->createRandomString( 400 );

        $this->assertEqual( strlen( $randomString ), 400 );
    }

    public function testEmailInviteSendingUsingMocks() {
        list( $UrlFinder, $Database, $Messenger, $Cms, $PhpApi ) = $this->makeUserManagerMockDependencies();

        $Messenger->returns( 'generateInviteID', 555 );
        $Database->returns( 'generateEmailID', 5 );

        $UserManager = new HfUserManager( $Database, $Messenger, $UrlFinder, $Cms, $PhpApi );
        $result      = $UserManager->sendInvitation( 1, 'me@test.com', 3 );

        $this->assertEqual( $result, 555 );
    }

    public function testPHPandMySQLtimezonesMatch() {
        $phpTime = date( 'Y-m-d H:i:s' );
        global $wpdb;
        $mysqlTime = $wpdb->get_results( "SELECT NOW()", ARRAY_A );
        $this->assertEqual( $phpTime, $mysqlTime[0]['NOW()'] );
    }

    public function testInviteStorageInInviteTableUsingMocks() {
        list( $UrlFinder, $Database, $Messenger, $Cms, $PhpApi ) = $this->makeUserManagerMockDependencies();

        $UserManager = new HfUserManager( $Database, $Messenger, $UrlFinder, $Cms, $PhpApi );

        $Database->returns( 'generateEmailID', 5 );

        $expirationDate = date( 'Y-m-d H:i:s', strtotime( '+' . 3 . ' days' ) );

        $expectedRecord = array(
            'inviteID'       => 555,
            'inviterID'      => 1,
            'inviteeEmail'   => 'me@test.com',
            'emailID'        => 5,
            'expirationDate' => $expirationDate
        );

        $Database->expectAt(
            1, 'insertIntoDb',
            array('hf_invite', $expectedRecord) );

        $UserManager->sendInvitation( 1, 'me@test.com', 3 );
    }

    public function testSendEmailByUserID() {
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfSecurity' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfWordPressInterface' );

        $UrlFinder    = new MockHfUrlFinder();
        $Security     = new MockHfSecurity();
        $DbConnection = new MockHfMysqlDatabase();
        $WordPressApi = new MockHfWordPressInterface();

        $WordPressApi->returns( 'getVar', 5 );
        $WordPressApi->returns( 'getUserEmail', 'me@test.com' );

        $DbConnection->expectOnce(
            'recordEmail',
            array(1, 'test', 'test', 5, 'me@test.com') );

        $Mailer = new HfMailer( $UrlFinder, $Security, $DbConnection, $WordPressApi );
        $Mailer->sendEmailToUser( 1, 'test', 'test' );
    }

    public function testSendEmailToUserAndSpecifyEmailID() {
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfSecurity' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfWordPressInterface' );

        $UrlFinder    = new MockHfUrlFinder();
        $Security     = new MockHfSecurity();
        $DbConnection = new MockHfMysqlDatabase();
        $WordPressApi = new MockHfWordPressInterface();

        $Mailer = new HfMailer( $UrlFinder, $Security, $DbConnection, $WordPressApi );

        $userID  = 1;
        $subject = 'test subject';
        $body    = 'test body';
        $emailID = 123;

        $WordPressApi->returns( 'sendWpEmail', true );
        $WordPressApi->returns( 'getUserEmail', 'me@test.com' );

        $DbConnection->expectOnce(
            'recordEmail',
            array($userID, $subject, $body, $emailID, 'me@test.com') );

        $Mailer->sendEmailToUserAndSpecifyEmailID( $userID, $subject, $body, $emailID );

    }

    public function testSendReportRequestEmailsChecksThrottling() {
        Mock::generate( 'HfMailer' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfHtmlGenerator' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfPhpLibrary' );

        $Messenger     = new MockHfMailer();
        $WebsiteApi    = new MockHfWordPressInterface();
        $HtmlGenerator = new MockHfHtmlGenerator();
        $DbConnection  = new MockHfMysqlDatabase();
        $CodeLibrary   = new MockHfPhpLibrary();

        $mockUser     = new stdClass();
        $mockUser->ID = 1;
        $mockUsers    = array($mockUser);
        $WebsiteApi->returns( 'getSubscribedUsers', $mockUsers );

        $mockGoalSub         = new stdClass();
        $mockGoalSub->goalID = 1;
        $mockGoalSubs        = array($mockGoalSub);
        $DbConnection->returns( 'getRows', $mockGoalSubs );

        $mockLevel                = new stdClass();
        $mockLevel->emailInterval = 1;
        $DbConnection->returns( 'level', $mockLevel );

        $DbConnection->returns( 'daysSinceLastReport', 2 );
        $Messenger->returns( 'isThrottled', true );

        $Messenger->expectAtLeastOnce( 'isThrottled' );

        $Goals = new HfGoals( $Messenger, $WebsiteApi, $HtmlGenerator, $DbConnection, $CodeLibrary );
        $Goals->sendReportRequestEmails();
    }

    public function testDaysSinceLastEmail() {
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfPhpLibrary' );

        $WebsiteAPI = new MockHfWordPressInterface();
        $PhpApi     = new MockHfPhpLibrary();

        $WebsiteAPI->returns( 'getVar', '2014-05-27 16:04:29' );
        $PhpApi->returns( 'convertStringToTime', 1401224669.0 );
        $PhpApi->returns( 'getCurrentTime', 1401483869.0 );

        $Database = new HfMysqlDatabase( $WebsiteAPI, $PhpApi );
        $result   = $Database->daysSinceLastEmail( 1 );

        $this->assertEqual( $result, 3 );
    }
    // ========= stopped here =========

    public function testSendReportRequestEmailsSendsEmailWhenReportDue() {
        Mock::generate( 'HfMailer' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfHtmlGenerator' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfPhpLibrary' );

        $Messenger     = new MockHfMailer();
        $WebsiteApi    = new MockHfWordPressInterface();
        $HtmlGenerator = new MockHfHtmlGenerator();
        $DbConnection  = new MockHfMysqlDatabase();
        $CodeLibrary   = new MockHfPhpLibrary();

        $mockUser     = new stdClass();
        $mockUser->ID = 1;
        $mockUsers    = array($mockUser);
        $WebsiteApi->returns( 'getSubscribedUsers', $mockUsers );

        $mockGoalSub         = new stdClass();
        $mockGoalSub->goalID = 1;
        $mockGoalSubs        = array($mockGoalSub);
        $DbConnection->returns( 'getRows', $mockGoalSubs );

        $mockLevel                = new stdClass();
        $mockLevel->emailInterval = 1;
        $DbConnection->returns( 'level', $mockLevel );

        $DbConnection->returns( 'daysSinceLastEmail', 2 );
        $DbConnection->returns( 'daysSinceLastReport', 2 );
        $Messenger->returns( 'isThrottled', false );

        $Messenger->expectAtLeastOnce( 'sendReportRequestEmail' );

        $Goals = new HfGoals( $Messenger, $WebsiteApi, $HtmlGenerator, $DbConnection, $CodeLibrary );
        $Goals->sendReportRequestEmails();
    }

    public function testSendReportRequestEmailsDoesNotSendEmailWhenReportNotDue() {
        Mock::generate( 'HfMailer' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfHtmlGenerator' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfPhpLibrary' );

        $Messenger     = new MockHfMailer();
        $WebsiteApi    = new MockHfWordPressInterface();
        $HtmlGenerator = new MockHfHtmlGenerator();
        $DbConnection  = new MockHfMysqlDatabase();
        $CodeLibrary   = new MockHfPhpLibrary();

        $mockUser     = new stdClass();
        $mockUser->ID = 1;
        $mockUsers    = array($mockUser);
        $WebsiteApi->returns( 'getSubscribedUsers', $mockUsers );

        $mockGoalSub         = new stdClass();
        $mockGoalSub->goalID = 1;
        $mockGoalSubs        = array($mockGoalSub);
        $DbConnection->returns( 'getRows', $mockGoalSubs );

        $mockLevel                = new stdClass();
        $mockLevel->emailInterval = 1;
        $DbConnection->returns( 'level', $mockLevel );

        $DbConnection->returns( 'daysSinceLastEmail', 2 );
        $DbConnection->returns( 'daysSinceLastReport', 0 );

        $Messenger->expectNever( 'sendReportRequestEmail' );

        $Goals = new HfGoals( $Messenger, $WebsiteApi, $HtmlGenerator, $DbConnection, $CodeLibrary );
        $Goals->sendReportRequestEmails();
    }

    public function testIsThrottledReturnsFalse() {
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfSecurity' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfWordPressInterface' );

        $UrlFinder    = new MockHfUrlFinder();
        $Security     = new MockHfSecurity();
        $DbConnection = new MockHfMysqlDatabase();
        $ApiInterface = new MockHfWordPressInterface();

        $DbConnection->returns( 'daysSinceAnyReport', 100 );
        $DbConnection->returns( 'daysSinceLastEmail', 10 );
        $DbConnection->returns( 'daysSinceSecondToLastEmail', 12 );

        $Mailer = new HfMailer( $UrlFinder, $Security, $DbConnection, $ApiInterface );
        $result = $Mailer->isThrottled( 1 );

        $this->assertEqual( $result, false );
    }

    public function testIsThrottledReturnsTrue() {
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfSecurity' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfWordPressInterface' );

        $UrlFinder    = new MockHfUrlFinder();
        $Security     = new MockHfSecurity();
        $DbConnection = new MockHfMysqlDatabase();
        $ApiInterface = new MockHfWordPressInterface();

        $DbConnection->returns( 'daysSinceAnyReport', 100 );
        $DbConnection->returns( 'daysSinceLastEmail', 10 );
        $DbConnection->returns( 'daysSinceSecondToLastEmail', 17 );

        $Mailer = new HfMailer( $UrlFinder, $Security, $DbConnection, $ApiInterface );
        $result = $Mailer->isThrottled( 1 );

        $this->assertEqual( $result, true );
    }

    public function testSendReportRequestEmailsDoesNotSendEmailWhenUserThrottled() {
        Mock::generate( 'HfMailer' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfHtmlGenerator' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfPhpLibrary' );

        $Messenger     = new MockHfMailer();
        $WebsiteApi    = new MockHfWordPressInterface();
        $HtmlGenerator = new MockHfHtmlGenerator();
        $DbConnection  = new MockHfMysqlDatabase();
        $CodeLibrary   = new MockHfPhpLibrary();

        $mockUser     = new stdClass();
        $mockUser->ID = 1;
        $mockUsers    = array($mockUser);
        $WebsiteApi->returns( 'getSubscribedUsers', $mockUsers );

        $mockGoalSub         = new stdClass();
        $mockGoalSub->goalID = 1;
        $mockGoalSubs        = array($mockGoalSub);
        $DbConnection->returns( 'getRows', $mockGoalSubs );

        $mockLevel                = new stdClass();
        $mockLevel->emailInterval = 1;
        $DbConnection->returns( 'level', $mockLevel );

        $DbConnection->returns( 'daysSinceLastEmail', 2 );
        $DbConnection->returns( 'daysSinceLastReport', 5 );
        $Messenger->returns( 'IsThrottled', true );

        $Messenger->expectNever( 'sendReportRequestEmail' );

        $Goals = new HfGoals( $Messenger, $WebsiteApi, $HtmlGenerator, $DbConnection, $CodeLibrary );
        $Goals->sendReportRequestEmails();
    }

    public function testStringToInt() {
        $PhpApi = new HfPhpLibrary();
        $string = '7';
        $int    = $PhpApi->convertStringToInt( $string );

        $this->assertTrue( $int === 7 );
    }

    public function testCurrentLevelTarget() {
        Mock::generate( 'HfMailer' );
        Mock::generate( 'HfWordPressInterface' );
        Mock::generate( 'HfHtmlGenerator' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfPhpLibrary' );

        $Messenger     = new MockHfMailer();
        $WebsiteApi    = new MockHfWordPressInterface();
        $HtmlGenerator = new MockHfHtmlGenerator();
        $DbConnection  = new MockHfMysqlDatabase();
        $CodeLibrary   = new MockHfPhpLibrary();

        $mockLevel         = new stdClass();
        $mockLevel->target = 14;
        $DbConnection->returns( 'level', $mockLevel );

        $Goals = new HfGoals( $Messenger, $WebsiteApi, $HtmlGenerator, $DbConnection, $CodeLibrary );

        $target = $Goals->currentLevelTarget( 5 );

        $this->assertEqual( $target, 14 );
    }

    public function testHfFormClassExists() {
        $this->assertTrue( class_exists( 'HfGenericForm' ) );
    }

    public function testFormOuterTags() {
        $Form = new HfGenericForm( 'test.com' );
        $html = $Form->getHtml();

        $this->assertEqual( $html, '<form action="test.com" method="post"></form>' );
    }

    public function testAddTextBoxInputToForm() {
        $Form  = new HfGenericForm( 'test.com' );
        $name  = 'test';
        $label = 'Hello, there';

        $Form->addTextBox( $name, $label, '', false );

        $html = $Form->getHtml();

        $this->assertEqual( $html,
            '<form action="test.com" method="post"><p><label for="test">Hello, there: <input type="text" name="test" value="" /></label></p></form>'
        );
    }

    public function testAddSubmitButton() {
        $Form  = new HfGenericForm( 'test.com' );
        $name  = 'submit';
        $label = 'Submit';

        $Form->addSubmitButton( $name, $label );

        $html = $Form->getHtml();

        $this->assertEqual( $html, '<form action="test.com" method="post"><p><input type="submit" name="submit" value="Submit" /></p></form>' );
    }

    public function testGenerateAdminPanelButtons() {
        Mock::generate( 'HfMailer' );
        Mock::generate( 'HfUrlFinder' );
        Mock::generate( 'HfMysqlDatabase' );
        Mock::generate( 'HfUserManager' );
        Mock::generate( 'HfWordPressInterface' );

        $Mailer       = new MockHfMailer();
        $URLFinder    = new MockHfUrlFinder();
        $DbConnection = new MockHfMysqlDatabase();
        $UserManager  = new MockHfUserManager();
        $Cms          = new MockHfWordPressInterface();

        $URLFinder->returns( 'getCurrentPageURL', 'test.com' );

        $AdminPanel = new HfAdminPanel( $Mailer, $URLFinder, $DbConnection, $UserManager, $Cms );

        $expectedHtml = '<form action="test.com" method="post"><p><input type="submit" name="sendTestReportRequestEmail" value="Send test report request email" /></p><p><input type="submit" name="sendTestInvite" value="Send test invite" /></p><p><input type="submit" name="sudoReactivateExtension" value="Sudo reactivate extension" /></p></form>';
        $resultHtml   = $AdminPanel->generateAdminPanelForm();

        $this->assertEqual( $expectedHtml, $resultHtml );
    }

    public function testRegistrationShortcodeExists() {
        $this->assertTrue( shortcode_exists( 'hfAuthenticate' ) );
    }

    public function testRegistrationShortcodeHtml() {
        list( $UrlFinder, $Database, $PhpLibrary, $Cms, $UserManager ) = $this->makeRegisterShortcodeMockDependencies();

        $UrlFinder->returns( 'getCurrentPageURL', 'test.com' );
        $PhpLibrary->returns( 'isPostEmpty', true );

        $RegisterShortcode = new HfRegisterShortcode( $UrlFinder, $Database, $PhpLibrary, $Cms, $UserManager );

        $expectedHtml = '<form action="test.com" method="post"><p><label for="username"><span class="required">*</span> Username: <input type="text" name="username" value="" required /></label></p><p><label for="email"><span class="required">*</span> Email: <input type="text" name="email" value="" required /></label></p><p><label for="password"><span class="required">*</span> Password: <input type="password" name="password" required /></label></p><p><label for="passwordConfirmation"><span class="required">*</span> Confirm Password: <input type="password" name="passwordConfirmation" required /></label></p><p><input type="submit" name="submit" value="Register" /></p></form>';
        $resultHtml   = $RegisterShortcode->getOutput();

        $this->assertEqual( $expectedHtml, $resultHtml );
    }

    public function testWordPressPrintToScreenMethodExists() {
        $Php = new HfPhpLibrary();

        $this->assertTrue( method_exists( $Php, 'printToScreen' ) );
    }

    public function testViewInterfaceExists() {
        $this->assertTrue( interface_exists( 'Hf_iView' ) );
    }

    public function testSettingsShortcodeClassExists() {
        $this->assertTrue( class_exists( 'HfSettingsShortcode' ) );
    }

    public function testFactoryClassExists() {
        $this->assertTrue( class_exists( 'HfFactory' ) );
    }

    public function testFactoryMakeGoals() {
        $Goals   = $this->Factory->makeGoals();

        $this->assertTrue( is_a( $Goals, 'HfGoals' ) );
    }

    public function testFactoryMakeUserManager() {
        $UserManager = $this->Factory->makeUserManager();

        $this->assertTrue( is_a( $UserManager, 'HfUserManager' ) );
    }

    public function testFactoryMakeMailer() {
        $Mailer  = $this->Factory->makeMessenger();

        $this->assertTrue( is_a( $Mailer, 'HfMailer' ) );
    }

    public function testFactoryMakeUrlFinder() {
        $UrlFinder = $this->Factory->makeUrlFinder();

        $this->assertTrue( is_a( $UrlFinder, 'HfUrlFinder' ) );
    }

    public function testFactoryMakeHtmlGenerator() {
        $HtmlGenerator = $this->Factory->makeHtmlGenerator();

        $this->assertTrue( is_a( $HtmlGenerator, 'HfHtmlGenerator' ) );
    }

    public function testFactoryMakeDatabase() {
        $Database = $this->Factory->makeDatabase();

        $this->assertTrue( is_a( $Database, 'HfMysqlDatabase' ) );
    }

    public function testFactoryMakePhpLibrary() {
        $PhpLibrary = $this->Factory->makeCodeLibrary();

        $this->assertTrue( is_a( $PhpLibrary, 'HfPhpLibrary' ) );
    }

    public function testFactoryMakeWordPressInterface() {
        $WordPressInterface = $this->Factory->makeContentManagementSystem();

        $this->assertTrue( is_a( $WordPressInterface, 'HfWordPressInterface' ) );
    }

    public function testFactoryMakeSecurity() {
        $Security = $this->Factory->makeSecurity();

        $this->assertTrue( is_a( $Security, 'HfSecurity' ) );
    }

    public function testFactoryMakeSettingsShortcode() {
        $SettingsShortcode = $this->Factory->makeSettingsShortcode();

        $this->assertTrue( is_a( $SettingsShortcode, 'HfSettingsShortcode' ) );
    }

    public function testSettingsShortcodeOutputsAnything() {
        $SettingsShortcode = $this->Factory->makeSettingsShortcode();
        $output            = $SettingsShortcode->getOutput();

        $this->assertTrue( strlen( $output ) > 0 );
    }

    public function testGoalsShortcodeClassExists() {
        $this->assertTrue( class_exists( 'HfGoalsShortcode' ) );
    }

    public function testGoalsShortcodeOutputsAnything() {
        $GoalsShortcode = $this->Factory->makeGoalsShortcode();
        $output         = $GoalsShortcode->getOutput();

        $this->assertTrue( strlen( $output ) > 0 );
    }

    public function testFormAbstractClassExists() {
        $this->assertTrue( class_exists( 'HfForm' ) );
    }

    public function testHfAccountabilityFormClassExists() {
        $this->assertTrue( class_exists( 'HfAccountabilityForm' ) );
    }

    public function testHfAccountabilityFormClassHasPopulateMethod() {
        Mock::generate( 'HfGoals' );
        $Goals              = new MockHfGoals();
        $AccountabilityForm = new HfAccountabilityForm( 'test.com', $Goals );
        $this->assertTrue( method_exists( $AccountabilityForm, 'populate' ) );
    }

    public function testGetGoalSubscriptions() {
        list( $Cms, $CodeLibrary ) = $this->makeDatabaseMockDependencies();

        $Cms->expectOnce( 'getRows' );

        $Database = new HfMysqlDatabase( $Cms, $CodeLibrary );

        $Database->getGoalSubscriptions( 1 );
    }

    public function testSendEmailReportRequests() {
        $Factory = new HfFactory();
        $Goals   = $Factory->makeGoals();
        $Goals->sendReportRequestEmails();
    }

    public function testRegisterShortcodeExists() {
        $this->assertTrue( class_exists( 'HfRegisterShortcode' ) );
    }

    public function testRegisterShortcodeUsesShortcodeInterface() {
        $this->assertTrue( $this->classImplementsInterface( 'HfRegisterShortcode', 'Hf_iShortcode' ) );
    }

    public function testCmsHasDeleteRowsFunction() {
        $Cms = new HfWordPressInterface();

        $this->assertTrue( method_exists( $Cms, 'deleteRows' ) );
    }

    public function testDatabaseHasDeleteInvitationMethod() {
        list( $Cms, $CodeLibrary ) = $this->makeDatabaseMockDependencies();

        $Database = new HfMysqlDatabase( $Cms, $CodeLibrary );

        $this->assertTrue( method_exists( $Database, 'deleteInvite' ) );
    }

    public function testDatabaseCallsDeleteRowsMethod() {
        list( $Cms, $CodeLibrary ) = $this->makeDatabaseMockDependencies();

        $Database = new HfMysqlDatabase( $Cms, $CodeLibrary );

        $Cms->expectOnce( 'deleteRows' );

        $Database->deleteInvite( 777 );
    }

    public function testRegisterShortcodeCallsDeleteInvitation() {
        list( $UrlFinder, $Database, $PhpLibrary, $Cms, $UserManager ) = $this->makeRegisterShortcodeMockDependencies();

        $PhpLibrary->returns( 'isPostEmpty', false );
        $PhpLibrary->returns( 'isUrlParameterEmpty', false );
        $PhpLibrary->returns( 'getPost', 'test@gmail.com' );

        $mockInvite            = new stdClass();
        $mockInvite->inviterID = 777;

        $Database->returns( 'getInvite', $mockInvite );

        $UserManager->expectOnce( 'processInvite' );

        $RegisterShortcode = new HfRegisterShortcode( $UrlFinder, $Database, $PhpLibrary, $Cms, $UserManager );

        $RegisterShortcode->getOutput();
    }

    public function testIsEmailTakenMethodExists() {
        $Factory = new HfFactory();
        $Cms     = $Factory->makeContentManagementSystem();

        $this->assertTrue( method_exists( $Cms, 'isEmailTaken' ) );
    }

    public function testRegisterShortcodeRejectsTakenEmails() {
        list( $UrlFinder, $Database, $PhpLibrary, $Cms, $UserManager ) = $this->makeRegisterShortcodeMockDependencies();

        $RegisterShortcode = new HfRegisterShortcode( $UrlFinder, $Database, $PhpLibrary, $Cms, $UserManager );

        $PhpLibrary->returns( 'isPostEmpty', false );
        $PhpLibrary->returns( 'isUrlParameterEmpty', false );
        $PhpLibrary->returns( 'getPost', 'test@gmail.com' );

        $Cms->returns( 'isEmailTaken', true );

        $mockInvite            = new stdClass();
        $mockInvite->inviterID = 777;

        $Database->returns( 'getInvite', $mockInvite );

        $Cms->expectAtLeastOnce( 'isEmailTaken' );
        $Cms->expectNever( 'createUser' );

        $output = $RegisterShortcode->getOutput();

        $this->assertTrue( strstr( $output, "<p class='fail'>Oops. That email is already in use.</p>" ) );
    }

    public function testLogInShortcodeImplementsShortcodeInterface() {
        $this->assertTrue( $this->classImplementsInterface( 'HfLogInShortcode', 'Hf_iShortcode' ) );
    }

    public function testLogInShortcodeOutputsLogInForm() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isUrlParameterEmpty', true );
        $PhpLibrary->returns( 'isPostEmpty', true );
        $UrlFinder->returns( 'getCurrentPageUrl', 'test.com' );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );
        $resultHtml     = $LogInShortcode->getOutput();

        $expectedHtml = '<form action="test.com" method="post"><p><label for="username"><span class="required">*</span> Username: <input type="text" name="username" value="" required /></label></p><p><label for="password"><span class="required">*</span> Password: <input type="password" name="password" required /></label></p><p><input type="submit" name="submit" value="Log In" /></p></form>';

        $this->assertEqual( $resultHtml, $expectedHtml );
    }

    public function testLogInShortcodeWithAlternateActionUrl() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isUrlParameterEmpty', true );
        $PhpLibrary->returns( 'isPostEmpty', true );
        $UrlFinder->returns( 'getCurrentPageUrl', 'anothertest.com' );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );
        $resultHtml     = $LogInShortcode->getOutput();

        $expectedHtml = '<form action="anothertest.com" method="post"><p><label for="username"><span class="required">*</span> Username: <input type="text" name="username" value="" required /></label></p><p><label for="password"><span class="required">*</span> Password: <input type="password" name="password" required /></label></p><p><input type="submit" name="submit" value="Log In" /></p></form>';

        $this->assertEqual( $resultHtml, $expectedHtml );
    }

    public function testLogInShortcodeOutputsSuccessMessage() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isUrlParameterEmpty', true );
        $PhpLibrary->returns( 'isPostEmpty', false );
        $Cms->returns( 'authenticateUser', new stdClass() );
        $Cms->returns( 'isError', false );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );

        $resultHtml   = $LogInShortcode->getOutput();
        $expectedHtml = '<p class="success">You have been successfully logged in.</p><p><a href="/">Onward!</a></p>';

        $this->assertEqual( $resultHtml, $expectedHtml );
    }

    public function testLogInShortcodeDisplaysEmptyFieldErrors() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isUrlParameterEmpty', true );
        $PhpLibrary->returns( 'isPostEmpty', true );
        $PhpLibrary->returnsAt( 0, 'isPostEmpty', false );
        $PhpLibrary->returnsAt( 2, 'isPostEmpty', false );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );

        $resultHtml   = $LogInShortcode->getOutput();
        $expectedHtml = '<p class="fail">Please provide a valid username and password combination.</p>';

        $this->assertTrue( strstr( $resultHtml, $expectedHtml ) );
    }

    public function testLogInShortcodeAuthenticatesUser() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isUrlParameterEmpty', true );
        $Cms->expectOnce( 'authenticateUser' );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );

        $LogInShortcode->getOutput();
    }

    public function testLogInShortcodeOutputsErrorMessageWhenLogInUnsuccessful() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isUrlParameterEmpty', true );
        $PhpLibrary->returns( 'isPostEmpty', false );
        $Cms->returns( 'isError', true );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );

        $resultHtml   = $LogInShortcode->getOutput();
        $expectedHtml = '<p class="fail">Please provide a valid username and password combination.</p>';

        $this->assertTrue( strstr( $resultHtml, $expectedHtml ) );
    }

    public function testLogInShortcodeLooksForUsernameAndPassword() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isUrlParameterEmpty', true );
        $PhpLibrary->expectAt( 0, 'getPost', array('username') );
        $PhpLibrary->expectAt( 1, 'getPost', array('password') );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );

        $LogInShortcode->getOutput();
    }

    public function testLogInShortcodeChecksForNonce() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();


        $PhpLibrary->returns( 'isPostEmpty', false );
        $Cms->returns( 'isError', false );

        $mockUser     = new stdClass();
        $mockUser->ID = 1;
        $Cms->returns( 'authenticateUser', $mockUser );

        $PhpLibrary->expectAtLeastOnce( 'getUrlParameter', array('n') );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );
        $LogInShortcode->getOutput();
    }

    public function testLogInShortcodeCreatesRelationship() {
        list( $UrlFinder, $PhpLibrary, $Cms, $UserManager ) = $this->makeLogInShortcodeMockDependencies();

        $PhpLibrary->returns( 'isPostEmpty', false );
        $Cms->returns( 'isError', false );

        $mockUser     = new stdClass();
        $mockUser->ID = 1;
        $Cms->returns( 'authenticateUser', $mockUser );

        $UserManager->expectAtLeastOnce( 'processInvite' );

        $LogInShortcode = new HfLogInShortcode( $UrlFinder, $PhpLibrary, $Cms, $UserManager );
        $LogInShortcode->getOutput();
    }

    public function testAuthenticateShortcodeClassExists() {
        $this->assertTrue( class_exists( 'HfAuthenticateShortcode' ) );
    }

    public function testAuthenticateShortcodeClassImplementsShortcodeInterface() {
        $this->assertTrue( $this->classImplementsInterface( 'HfAuthenticateShortcode', 'Hf_iShortcode' ) );
    }

    public function testHtmlGeneratorCreatesTabs() {
        $HtmlGenerator = $this->Factory->makeHtmlGenerator();

        $contents = array(
            'duck1' => 'quack',
            'duck2' => 'quack, quack',
            'duck3' => 'quack, quack, quack'
        );

        $expected = '[su_tabs active="1"][su_tab title="duck1"]quack[/su_tab][su_tab title="duck2"]quack, quack[/su_tab][su_tab title="duck3"]quack, quack, quack[/su_tab][/su_tabs]';

        $result = $HtmlGenerator->generateTabs( $contents, 1 );

        $this->assertTrue( strstr( $result, $expected ) );
    }

    public function testHtmlGeneratorCreatesDifferentTabs() {
        $HtmlGenerator = $this->Factory->makeHtmlGenerator();

        $contents = array(
            'duck1' => 'quack',
            'duck2' => 'quack, quack'
        );

        $expected = '<div class="su-tabs su-tabs-style-default" data-active="2"><div class="su-tabs-nav"><span class="">duck1</span><span class="">duck2</span></div><div class="su-tabs-panes"><div class="su-tabs-pane su-clearfix">quack</div>
<div class="su-tabs-pane su-clearfix">quack, quack</div></div></div>';

        $result = $HtmlGenerator->generateTabs( $contents, 2 );

        $this->assertTrue( strstr( $result, $expected ) );
    }

    public function testAuthenticateShortcodeGeneratesTabs() {
        $AuthenticateShortcode = $this->Factory->makeAuthenticateShortcode();

        $result = $AuthenticateShortcode->getOutput();

        var_dump($result);
        $this->assertTrue( strstr( $result, '<div class="su-tabs su-tabs-style-default" data-active="1">' ) );
    }

    public function testAuthenticateShortcodeGeneratesLogInTab() {
        $AuthenticateShortcode = $this->Factory->makeAuthenticateShortcode();

        $result = $AuthenticateShortcode->getOutput();

        $this->assertTrue( strstr( $result, '[su_tab title="Log In"]' ) );
    }

    public function testAuthenticateShortcodeGeneratesRegisterTab() {
        $AuthenticateShortcode = $this->Factory->makeAuthenticateShortcode();

        $result = $AuthenticateShortcode->getOutput();

        $this->assertTrue( strstr( $result, '[su_tab title="Register"]' ) );
    }

    public function testAuthenticateShortcodeIncludesLogInForm() {
        $AuthenticateShortcode = $this->Factory->makeAuthenticateShortcode();

        $result = $AuthenticateShortcode->getOutput();

        $logInHtml = '<form action="anothertest.com" method="post"><p><label for="username"><span class="required">*</span> Username: <input type="text" name="username" value="" required /></label></p><p><label for="password"><span class="required">*</span> Password: <input type="password" name="password" required /></label></p><p><input type="submit" name="login" value="Log In" /></p></form>';

        $this->assertTrue( strstr( $result, $logInHtml ) );
    }

    public function testMakeAuthenticateShortcode() {
        $AuthenticateShortcode = $this->Factory->makeAuthenticateShortcode();

        $this->assertTrue( is_a($AuthenticateShortcode, 'HfAuthenticateShortcode') );
    }
}

?>
