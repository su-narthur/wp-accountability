<?php

class HfWordPress implements Hf_iContentManagementSystem {
    private $wpdb;

    function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function getUserEmail( $userID ) {
        return get_userdata( $userID )->user_email;
    }

    public function sendWpEmail( $to, $subject, $message ) {
        return wp_mail( $to, $subject, $message );
    }

    public function getSubscribedUsers() {
        return get_users( array(
            'meta_key'   => 'hfSubscribed',
            'meta_value' => true
        ) );
    }

    public function currentUser() {
        return wp_get_current_user();
    }

    public function getVar( $query ) {
        return $this->wpdb->get_var( $query );
    }

    public function getDbPrefix() {
        return $this->wpdb->prefix;
    }

    public function executeQuery( $query ) {
        $this->wpdb->query( $query );
    }

    public function createUser( $username, $password, $email ) {
        return !$this->isError(wp_create_user( $username, $password, $email ));
    }

    public function isUserLoggedIn() {
        return is_user_logged_in();
    }

    public function getRows( $table, $where, $outputType = OBJECT ) {
        global $wpdb;
        $prefix = $wpdb->prefix;
        if ( $where === null ) {
            return $wpdb->get_results( "SELECT * FROM " . $prefix . $table, $outputType );
        } else {
            return $wpdb->get_results( "SELECT * FROM " . $prefix . $table . " WHERE " . $where, $outputType );
        }
    }

    function getRow( $table, $criterion ) {
        $prefix = $this->getDbPrefix();
        $query = "SELECT * FROM " . $prefix . $table . " WHERE " . $criterion;

        return $this->wpdb->get_row( $query );
    }

    public function deleteRows( $table, $where ) {
        return $this->wpdb->delete( $table, $where );
    }

    public function isEmailTaken( $email ) {
        return email_exists( $email );
    }

    public function authenticateUser( $username, $password ) {
        $credentials                  = array();
        $credentials['user_login']    = $username;
        $credentials['user_password'] = $password;

        return !$this->isError(wp_signon( $credentials ));
    }

    public function isError( $thing ) {
        return is_wp_error( $thing );
    }

    public function getShortcodeOutput( $shortcode ) {
        return do_shortcode( $shortcode );
    }

    public function addPageToAdminMenu($name, $slug, $function) {
        add_menu_page( $name, $name, 'activate_plugins', $slug, $function );
    }

    public function getPluginAssetUrl( $fileName ) {
        return plugins_url( $fileName, dirname( __FILE__ ) );
    }

    public function isUsernameTaken($username) {
        return username_exists( $username );
    }

    public function expandShortcodes( $string ) {
        return do_shortcode( $string );
    }

    public function getUserIdByEmail($email) {
        return get_user_by('email', $email)->ID;
    }

    public function getLogoutUrl($redirect) {
        return wp_logout_url( $redirect );
    }

    public function getResults($query) {
        return $this->wpdb->get_results($query);
    }

    public function insertIntoDb($table, $data) {
        $this->wpdb->insert($table, $data);
    }

    public function updateRowsSafe($table, $data, $where) {
        $this->wpdb->update($table, $data, $where);
    }
} 