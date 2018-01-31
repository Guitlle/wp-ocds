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
        $this->per_page = 10;
    }

    public function summary () {
        $pagen   = intval($pagen);
        $args    = array( "numberposts" => -1, "post_type" => "ocdsrecord" );
        $records = get_posts( $args );
        $response = array( "records" => array() );
        foreach ($records as $record) {
            $data = get_post_meta($record->ID, "wp-ocds-record-data");
            $id   = get_post_meta($record->ID, "wp-ocds-record-id");
            try {
                $data  = json_decode($data[0]);
                $value = 0;
                foreach( $data->releases[0]->awards as $adjudicacion ) {
                    if ($adjudicacion->value->currency == "GTQ")
                        $value  += floatval($adjudicacion->value->amount);
                }

                $summary = array(
                    "coordinates" => array(
                        "lat"           => $data->releases[0]->ocmp_extras->location->lat,
                        "lon"           => $data->releases[0]->ocmp_extras->location->lon ),
                    "municipality"      => $data->releases[0]->ocmp_extras->location->municipality,
                    "name"              => $data->releases[0]->tender->title,
                    "description"       => $data->releases[0]->tender->description,
                    "monto"             => $value,
                    "alcalde"           => $data->releases[0]->ocmp_extras->alcalde,
                    "partido"           => $data->releases[0]->ocmp_extras->partido,
                    "avance_fisico"     => $data->releases[0]->ocmp_extras->progress->physical,
                    "avance_financiero" => $data->releases[0]->ocmp_extras->progress->financial,
                    "inicio_contrato"   => $data->releases[0]->contracts[0]->period->startDate,
                    "final_contrato"    => $data->releases[0]->contracts[0]->period->endDate
                );
                array_push($response["records"], $summary);
            } catch (Exception $e) {
                continue;
            }
        }
        echo json_encode($response);
    }

    public function record( $id ) {
        $searchArgs = array(
            "post_type"  => "ocdsrecord",
            "meta_key"   => "wp-ocds-record-id",
            "meta_value" => addslashes($id)
        );
        $posts = query_posts( $searchArgs );
        $data = get_post_meta($posts[0]->ID, "wp-ocds-record-data");
        echo $data[0];
    }

    public function records_page( $pagen ) {
        $pagen   = intval($pagen);
        $args    = array( "posts_per_page" => $this->per_page, "offset" => ($pagen-1) * $this->per_page, "post_type" => "ocdsrecord" );
        $records = get_posts( $args );
        echo "{\"next_page\": \"".$this->base_url."/records/page/".strval($pagen+1)."\", ".
             ( ($pagen > 1) ?"\"previous_page\": \"".$this->base_url."/records/page/".strval($pagen-1)."\", " : "").
             " \"records\":[";
        foreach ($records as $record) {
            $data = get_post_meta($record->ID, "wp-ocds-record-data");
            $id   = get_post_meta($record->ID, "wp-ocds-record-id");
            echo  $data[0].", ";
        }
        echo "]}";
    }

    public function records_handler( $route ) {
        switch( $route[2] ) {
            case "summary":
                /* summary for map data */
                return $this->summary();
            case "page":
                return $this->records_page(intval($route[2]));
            default:
                if (count($route) == 3) {
                    return $this->record($route[2]);
                }
        }
        return $this->records_page(1);
    }

    public function resources() {
        ?>{ "resources": [ { "name": "records", "description": "OCDS records", "url": "<? echo $this->base_url; ?>/records" } ] }<?
    }

    public function router( $input ) {
        $route = explode("/", $input);
        if ($route AND count($route) < 2) return;
        switch($route[1]) {
            case "records":
                return $this->records_handler($route);
        }
        return $this->resources();
    }
}

/* TODO: Use custom wordpress htaccess routes. For now, hardcode ugly php file. */
$base  = get_site_url() . "/wp-content/plugins/wp-ocds/ocds-api.php?";
$api   = new Wp_Ocds_API( $base );
$query = $_SERVER["QUERY_STRING"];
$api   ->router($query);
