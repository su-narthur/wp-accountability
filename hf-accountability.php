<?php
/*
Plugin Name: HabitFree Accountability
Description: Keeps people accountable.
Author: Nathan Arthur
Version: 1.0
Author URI: http://NathanArthur.com/
*/

register_activation_hook( __FILE__, "hfActivate" );
register_deactivation_hook( __FILE__, "hfDeactivate" );

function hfActivate() {
    $Factory = new HfFactory();

    $Database    = $Factory->makeDatabase();
    $UserManager = $Factory->makeUserManager();

    wp_clear_scheduled_hook( 'hfEmailCronHook' );
    wp_schedule_event( time(), 'daily', 'hfEmailCronHook' );

    $Database->installDb();

    add_action( 'wp_loaded', array($UserManager, 'processAllUsers') );

    error_log( "my plugin activated", 0 );
}

function hfDeactivate() {
    wp_clear_scheduled_hook( 'hfEmailCronHook' );
}

require_once( dirname( __FILE__ ) . '/interfaces/Hf_iMarkupGenerator.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iContentManagementSystem.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iCodeLibrary.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iView.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iShortcode.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iGoals.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iMessenger.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iAssetLocator.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iDatabase.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iUserManager.php' );
require_once( dirname( __FILE__ ) . '/interfaces/Hf_iSecurity.php' );

require_once( dirname( __FILE__ ) . '/abstractClasses/abstractClass-HfForm.php' );

require_once( dirname( __FILE__ ) . '/classes/HfUrlFinder.php' );
require_once( dirname( __FILE__ ) . '/classes/HfSecurity.php' );
require_once( dirname( __FILE__ ) . '/classes/HfMailer.php' );
require_once( dirname( __FILE__ ) . '/classes/HfMysqlDatabase.php' );
require_once( dirname( __FILE__ ) . '/classes/HfUserManager.php' );
require_once( dirname( __FILE__ ) . '/classes/HfAdminPanel.php' );
require_once( dirname( __FILE__ ) . '/classes/HfHtmlGenerator.php' );
require_once( dirname( __FILE__ ) . '/classes/HfWordPress.php' );
require_once( dirname( __FILE__ ) . '/classes/HfGoals.php' );
require_once( dirname( __FILE__ ) . '/classes/HfPhpLibrary.php' );
require_once( dirname( __FILE__ ) . '/classes/HfGenericForm.php' );
require_once( dirname( __FILE__ ) . '/classes/HfSettingsShortcode.php' );
require_once( dirname( __FILE__ ) . '/classes/HfFactory.php' );
require_once( dirname( __FILE__ ) . '/classes/HfGoalsShortcode.php' );
require_once( dirname( __FILE__ ) . '/classes/HfAccountabilityForm.php' );
require_once( dirname( __FILE__ ) . '/classes/HfAuthenticateShortcode.php' );
require_once( dirname( __FILE__ ) . '/classes/HfUserButtonsShortcode.php' );
require_once( dirname( __FILE__ ) . '/classes/HfInvitePartnerShortcode.php' );
require_once( dirname( __FILE__ ) . '/classes/HfPartnerListShortcode.php' );

date_default_timezone_set( 'America/Chicago' );

$HfFactory     = new HfFactory();
$HfGoals       = $HfFactory->makeGoals();
$HfUserManager = $HfFactory->makeUserManager();
$HfAdminPanel  = $HfFactory->makeAdminPanel();

add_action( 'hfEmailCronHook', array($HfGoals, 'sendReportRequestEmails') );
add_action( 'user_register', array($HfUserManager, 'processNewUser') );
add_action( 'admin_menu', array($HfAdminPanel, 'registerAdminPanel') );
add_action( 'admin_head', array($HfAdminPanel, 'addToAdminHead') );
add_action( 'init', 'hfRegisterShortcodes' );

function hfRegisterShortcodes() {
    $Factory               = new HfFactory();
    $SettingsShortcode     = $Factory->makeSettingsShortcode();
    $GoalsShortcode        = $Factory->makeGoalsShortcode();
    $AuthenticateShortcode = $Factory->makeAuthenticateShortcode();
    $UserButtonsShortcode  = $Factory->makeUserButtonsShortcode();
    $InvitePartnerShortcode = $Factory->makeInvitePartnerShortcode();

    add_shortcode( 'hfSettings', array($SettingsShortcode, 'getOutput') );
    add_shortcode( 'hfGoals', array($GoalsShortcode, 'getOutput') );
    add_shortcode( 'hfUserButtons', array($UserButtonsShortcode, 'getOutput') );
    add_shortcode( 'hfAuthenticate', array($AuthenticateShortcode, 'getOutput') );
    add_shortcode( 'hfInvitePartner', array($InvitePartnerShortcode, 'getOutput') );
}