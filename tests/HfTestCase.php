<?php

//require_once( dirname( dirname( __FILE__ ) ) . '/hf-accountability.php' );

abstract class HfTestCase extends \PHPUnit_Framework_TestCase {
    protected $backupGlobals = false;
    protected $Factory;

    protected $MockDatabase;
    protected $MockMessenger;
    protected $MockAssetLocator;
    protected $MockCms;
    protected $MockSecurity;
    protected $MockCodeLibrary;
    protected $MockUserManager;
    protected $MockPageLocator;
    protected $MockGoals;
    protected $MockMarkupGenerator;

    protected $UserManagerWithMockedDependencies;
    protected $MailerWithMockedDependencies;
    protected $GoalsShortcodeWithMockDependencies;
    protected $DatabaseWithMockedDependencies;

    function __construct() {
        $this->Factory = new HfFactory();
    }

    protected function setUp() {
        $_POST = array();
        $_GET = array();

        $this->resetMocks();
        $this->resetObjectsWithMockDependencies();
    }

    private function resetMocks() {
        $this->MockDatabase        = $this->makeMock( 'HfMysqlDatabase' );
        $this->MockMessenger       = $this->makeMock( 'HfMailer' );
        $this->MockAssetLocator    = $this->makeMock( 'HfUrlFinder' );
        $this->MockCms             = $this->makeMock( 'HfWordPress' );
        $this->MockSecurity        = $this->makeMock( 'HfSecurity' );
        $this->MockCodeLibrary     = $this->makeMock( 'HfPhpLibrary' );
        $this->MockUserManager     = $this->makeMock( 'HfUserManager' );
        $this->MockPageLocator     = $this->makeMock( 'HfUrlFinder' );
        $this->MockGoals           = $this->makeMock( 'HfGoals' );
        $this->MockMarkupGenerator = $this->makeMock( 'HfHtmlGenerator' );
    }

    private function resetObjectsWithMockDependencies() {
        $this->resetUserManagerWithMockedDependencies();
        $this->resetMailerWithMockedDependencies();
        $this->resetGoalsShortcodeWithMockDependencies();
        $this->DatabaseWithMockedDependencies = new HfMysqlDatabase(
            $this->MockCms,
            $this->MockCodeLibrary
        );
    }

    private function resetUserManagerWithMockedDependencies() {
        $this->UserManagerWithMockedDependencies = new HfUserManager(
            $this->MockDatabase,
            $this->MockMessenger,
            $this->MockAssetLocator,
            $this->MockCms,
            $this->MockCodeLibrary
        );
    }

    private function resetMailerWithMockedDependencies() {
        $this->MailerWithMockedDependencies = new HfMailer(
            $this->MockAssetLocator,
            $this->MockSecurity,
            $this->MockDatabase,
            $this->MockCms,
            $this->MockCodeLibrary
        );
    }

    private function resetGoalsShortcodeWithMockDependencies() {
        $this->GoalsShortcodeWithMockDependencies = new HfGoalsShortcode(
            $this->MockUserManager,
            $this->MockMessenger,
            $this->MockPageLocator,
            $this->MockGoals,
            $this->MockSecurity,
            $this->MockMarkupGenerator,
            $this->MockCodeLibrary
        );
    }

    protected function makeMock( $className ) {
        return $this->getMockBuilder( $className )->disableOriginalConstructor()->getMock();
    }

    protected function setReturnValue( $Mock, $method, $value ) {
        return $Mock->expects( $this->any() )->method( $method )->will( $this->returnValue( $value ) );
    }

    protected function setReturnValues( $Mock, $method, $values ) {
        $AdjustedMock     = $Mock->expects( $this->any() )->method( $method );
        $consecutiveCalls = call_user_func_array( array($this, "onConsecutiveCalls"), $values );

        return $AdjustedMock->will( $consecutiveCalls );
    }

    protected function expectAtLeastOnce( $Mock, $method, $args = array() ) {
        $ExpectantMock = $Mock->expects( $this->atLeastOnce() )->method( $method );

        $this->addWithArgsExpectation( $args, $ExpectantMock );
    }

    protected function expectAt( $Mock, $method, $at, $args = array() ) {
        // Any failure of at() expectations returns "Mocked method does not exist"
        // See http://stackoverflow.com/questions/3367513/phpunit-mocked-method-does-not-exist-when-using-mock-expectsthis-at

        $ExpectantMock = $Mock->expects( $this->at( $at ) )->method( $method );

        $this->addWithArgsExpectation( $args, $ExpectantMock );
    }

    protected function expectNever( $Mock, $method, $args = array() ) {
        $ExpectantMock = $Mock->expects( $this->never() )->method( $method );

        $this->addWithArgsExpectation( $args, $ExpectantMock );
    }

    protected function expectOnce( $Mock, $method, $args = array() ) {
        $ExpectantMock = $Mock->expects( $this->once() )->method( $method );

        $this->addWithArgsExpectation( $args, $ExpectantMock );
    }

    private function addWithArgsExpectation( $args, $ExpectantMock ) {
        if ( !empty( $args ) ) {
            $expectations = array();

            foreach ( $args as $arg ) {
                $expectations[] = $this->equalTo( $arg );
            }

            call_user_func_array( array($ExpectantMock, "with"), $expectations );
        }
    }

    protected function classImplementsInterface( $class, $interface ) {
        $interfacesImplemented = class_implements( $class );

        return in_array( $interface, $interfacesImplemented );
    }

    protected function haystackContainsNeedle( $haystack, $needle ) {
        return strstr( $haystack, $needle ) != false;
    }
} 