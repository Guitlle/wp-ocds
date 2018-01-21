<?php

/**
 * REST API to serve OCDS data.
 *
 * @link       http://guilles.website
 * @since      1.0.0
 *
 * @package    Wp_Ocds
 */

/**
 * REST API to serve OCDS data.
 *
 * This class will serve the list of works, paginated:
 *      ocds-api.php?/page=1
 * information for individual works:
 *      ocds-api.php?/work-id
 * and also a summary for the map:
 *      ocds-api.php?/summary
 *
 * @package    Wp_Ocds
 * @subpackage Wp_Ocds/public
 * @author     Guillermo Ambrosio <yo@guilles.website>
 */
class Wp_Ocds_API {
    public function __construct( $plugin_name, $version ) {
        $query = $_HTTP["QUERY_STRING"];
    }

    public function summary () {

    }

    public function record( $id ) {

    }

    public function records_page( $pagen ) {

    }

    public function router(url) {

    }
}

new Wp_Ocds_API();
