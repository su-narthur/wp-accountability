<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once( dirname( dirname( __FILE__ ) ) . '/HfTestCase.php' );

class TestAdminPanel extends HfTestCase {
    // Helper Functions

    // Tests

    public function testGenerateAdminPanelButtons() {
        $Mailer       = $this->makeMock( 'HfMailer' );
        $URLFinder    = $this->makeMock( 'HfUrlFinder' );
        $DbConnection = $this->makeMock( 'HfMysqlDatabase' );
        $UserManager  = $this->makeMock( 'HfUserManager' );
        $Cms          = $this->makeMock( 'HfWordPress' );

        $AdminPanel = new HfAdminPanel( 'test.com', $this->mockMarkupGenerator, $Mailer, $URLFinder, $DbConnection, $UserManager, $Cms );

        $expectedHtml = '<form action="test.com" method="post"><p><input type="submit" name="sendTestReportRequestEmail" value="Send test report request email" /></p><p><input type="submit" name="sendTestInvite" value="Send test invite" /></p><p><input type="submit" name="sudoReactivateExtension" value="Sudo reactivate extension" /></p></form>';
        $resultHtml   = $AdminPanel->generateAdminPanelForm();

        $this->assertEquals( $expectedHtml, $resultHtml );
    }

    public function testSetsAdminPageIcon() {
        $this->expectOnce(
            $this->mockCms,
            'addPageToAdminMenu',
            array('HF Plugin', 'hfAdmin', array($this->mockedAdminPanel, 'generateAdminPanel'),'dashicons-unlock',3)
        );

        $this->mockedAdminPanel->registerAdminPanel();
    }
}
