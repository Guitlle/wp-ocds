<?php

/**
 * REST API to serve OCDS data.
 *
 * @link       http://guilles.website
 * @since      1.0.0
 *
 * @package    Wp_Ocds
 */

/* Load WordPress */
require_once '../../../wp-load.php';
require_once ABSPATH . '/wp-admin/includes/taxonomy.php';


/**
 * REST-like API to serve OCDS data.
 *
 * This class will serve the list of works, paginated:
 *      ocds-api.php?/page=1
 * information for individual works:
 *      ocds-api.php?/work-id
 * and also a summary for the map:
 *      ocds-api.php?/summary
 *
 * @package    Wp_Ocds
 * @author     Guillermo Ambrosio <yo@guilles.website>
 */
class Wp_Ocds_API {
    public function __construct( $base_url) {
        $this->base_url = $base_url;
    }

    public function summary () {

    }

    public function record( $id ) {

    }

    public function records_page( $pagen ) {

    }

    public function records_handler( $route ) {
        switch( $route[0] ) {
            case "summary":
                /* summary for map data */
                return $this->summary()
            case "page":
                return $this->records_page(intval($route[1]));
            default:
                if (count($route) == 2) {
                    return $this->record($route[1]);
                }
        }
    }

    public resources() {
        ?>{ "resources": [ { "name": "records", "description": "OCDS records", "url": "<? echo $this->base_url; ?>/records" } ] }<?
    }

    public function router( $input ) {
        $route = explode("/", $input);
        if ($route AND count($route) == 0) return;
        switch($route[0]) {
            case "records":
                return $this->records_handler($route);
        }
        return $this->resources();
    }
}


$base = get_site_url() . "/wp-content/wp-ocds/ocds-api.php";
$api = new Wp_Ocds_API( $base );
$query = $_SERVER["QUERY_STRING"];

$api->router($query);
