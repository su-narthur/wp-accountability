<?php

if (!class_exists("HfUrlFinder")) {
    class HfUrlFinder {

        function HfUrlFinder() {
        }

        function getCurrentPageUrl() {
            $pageURL = 'http';
            if( isset($_SERVER["HTTPS"]) ) {
                if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
            }
            $pageURL .= "://";
            if ($_SERVER["SERVER_PORT"] != "80") {
                $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
            } else {
                $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
            }
            return $pageURL;
        }

        function getReportPageUrl() {
            return $this->getURLByTitle('Goals');
        }

        function getURLByTitle($title) {
            $page = get_page_by_title( $title );
            return get_permalink( $page->ID );
        }

    }
}

?>