<?php
require_once( dirname( dirname( __FILE__ ) ) . '/HfTestCase.php' );

class TestMailer extends HfTestCase {
    // Helper Functions

    // Tests

    public function testSendEmailByUserID() {
        $this->setReturnValue( $this->MockCms, 'getVar', 5 );
        $this->setReturnValue( $this->MockCms, 'getUserEmail', 'me@test.com' );

        $this->expectAtLeastOnce( $this->MockDatabase, 'recordEmail', array(1, 'test', 'test', 5, 'me@test.com') );

        $this->MailerWithMockedDependencies->sendEmailToUser( 1, 'test', 'test' );
    }

    public function testSendEmailToUserAndSpecifyEmailID() {
        $this->setReturnValue( $this->MockCms, 'sendWpEmail', true );
        $this->setReturnValue( $this->MockCms, 'getUserEmail', 'me@test.com' );

        $this->expectAtLeastOnce( $this->MockDatabase, 'recordEmail', array(1, 'test subject', 'test body', 123, 'me@test.com') );

        $this->MailerWithMockedDependencies->sendEmailToUserAndSpecifyEmailID( 1, 'test subject', 'test body', 123 );
    }

    public function testIsThrottledReturnsFalse() {
        $this->setReturnValue( $this->MockDatabase, 'daysSinceAnyReport', 100 );
        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastEmail', 10 );
        $this->setReturnValue( $this->MockDatabase, 'daysSinceSecondToLastEmail', 12 );

        $result = $this->MailerWithMockedDependencies->isThrottled( 1 );

        $this->assertEquals( $result, false );
    }

    public function testIsThrottledReturnsTrue() {
        $this->setReturnValue( $this->MockDatabase, 'daysSinceAnyReport', 100 );
        $this->setReturnValue( $this->MockDatabase, 'daysSinceLastEmail', 10 );
        $this->setReturnValue( $this->MockDatabase, 'daysSinceSecondToLastEmail', 17 );

        $result = $this->MailerWithMockedDependencies->isThrottled( 1 );

        $this->assertEquals( $result, true );
    }

    public function testMailerPointsInviteUrlToRegistrationTab() {
        $this->setReturnValue( $this->MockAssetLocator, 'getPageUrlByTitle', 'habitfree.org/authenticate' );

        $result   = $this->MailerWithMockedDependencies->generateInviteURL( 777 );
        $expected = 'habitfree.org/authenticate?n=777&tab=2';

        $this->assertEquals( $expected, $result );
    }

    public function testIsEmailValid() {
        $this->setReturnValue( $this->MockDatabase, 'isEmailValid', true );

        $this->assertTrue( $this->MailerWithMockedDependencies->isEmailValid( 1, 1 ) );
    }

    public function testSendReportRequestEmailGeneratesNonce() {
        $this->expectAtLeastOnce($this->MockSecurity, 'createRandomString', array(250));
        $this->MailerWithMockedDependencies->sendReportRequestEmail(1);
    }

    public function testGenerateReportUrlUsesNonce() {
        $baseURL = 'habitfree.org/test';
        $this->setReturnValue($this->MockAssetLocator, 'getPageUrlByTitle', $baseURL);

        $actual = $this->MailerWithMockedDependencies->generateReportURL(555);

        $expected = $baseURL . '?n=555';

        $this->assertEquals($expected, $actual);
    }

    public function testSendReportRequestEmailUsesNonceToCreateUrl() {
        $this->setReturnValue($this->MockSecurity, 'createRandomString', 555);
        $baseURL = 'habitfree.org/test';
        $this->setReturnValue($this->MockAssetLocator, 'getPageUrlByTitle', $baseURL);
        $reportUrl = $baseURL . '?n=555';

        $userID = 1;
        $subject = "How's it going?";
        $body = "<p>Time to <a href='" . $reportUrl . "'>check in</a>.</p>";
        $emailID = null;
        $to = null;

        $this->expectOnce($this->MockDatabase, 'recordEmail', array( $userID, $subject, $body, $emailID, $to ));

        $this->MailerWithMockedDependencies->sendReportRequestEmail($userID);
    }

    public function testSendReportRequestEmailRecordsRequest() {
        $this->setReturnValue($this->MockSecurity, 'createRandomString', 555);
        $this->setReturnValue($this->MockDatabase, 'generateEmailID', 7);

        $userId = 1;
        $emailId = 7;

        $this->expectOnce($this->MockDatabase, 'recordReportRequest', array(555, $userId, $emailId));

        $this->MailerWithMockedDependencies->sendReportRequestEmail($userId);
    }

    public function testIsReportRequestValid() {
        $this->setReturnValue($this->MockDatabase, 'isReportrequestValid', true);
        $this->assertTrue($this->MailerWithMockedDependencies->isReportRequestValid(555));
    }

    public function testIsReportRequestValidReturnsFalse() {
        $this->setReturnValue($this->MockDatabase, 'isReportrequestValid', false);
        $this->assertFalse($this->MailerWithMockedDependencies->isReportRequestValid(555));
    }

    public function testDeleteReportRequest() {
        $this->expectOnce($this->MockDatabase, 'deleteReportRequest', array(555));
        $this->MailerWithMockedDependencies->deleteReportRequest(555);
    }

    public function testGetReportRequestUserId() {
        $this->expectOnce($this->MockDatabase, 'getReportRequestUserId', array(555));
        $this->MailerWithMockedDependencies->getReportRequestUserId(555);
    }

    public function testGetReportRequestUserIdReturnsValue() {
        $this->setReturnValue($this->MockDatabase, 'getReportRequestUserId', 5);
        $actual = $this->MailerWithMockedDependencies->getReportRequestUserId(555);
        $this->assertEquals(5, $actual);
    }

    public function testUpdateReportRequestExpirationDate() {
        $this->expectOnce($this->MockDatabase, 'updateReportRequestExpirationDate', array(555,'abcd'));
        $this->MailerWithMockedDependencies->updateReportRequestExpirationDate(555, 'abcd');
    }

    public function testMailerCreatesLigitExpirationDateForReportRequests() {
//        $time = time();
//        var_dump($time);
//        var_dump(date('Y-m-d H:i:s', $time + (7 * 24 * 60 * 60)));
//
//        int(1403551104)
//        string(19) "2014-06-30 14:18:24"

        $this->setReturnValue($this->MockSecurity, 'createRandomString', 123);
        $this->setReturnValue($this->MockDatabase, 'generateEmailId', 5);
        $this->setReturnValue($this->MockCodeLibrary, 'getCurrentTime', 1403551104);

        $this->expectOnce($this->MockDatabase, 'recordReportRequest', array(123, 1, 5, "2014-06-30 14:18:24"));

        $this->MailerWithMockedDependencies->sendReportRequestEmail(1);
    }
}