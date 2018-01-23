<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://guilles.website
 * @since      1.0.0
 *
 * @package    Wp_Ocds
 * @subpackage Wp_Ocds/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Wp_Ocds
 * @subpackage Wp_Ocds/admin
 * @author     Guillermo Ambrosio <yo@guilles.website>
 */
class Wp_Ocds_Editor {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $loader;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_loader ) {
		$this->loader = $plugin_loader;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->enabled = false;

		$plugin_loader->add_action( 'init', $this, 'initialize');
		$plugin_loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts' );

		$this->loader->add_action( 'add_meta_boxes', $this, 'init_metabox' );

		$this->loader->add_action( 'ocdsrecord_add_form_fields', $this, 'render_form', 10, 2 );
        $this->loader->add_action( 'ocdsrecord_edit_form_fields', $this, 'render_form', 10, 2 );
		$this->loader->add_action( 'save_post', $this, 'save', 10, 2 );
	}

	public function initialize() {
		$args = array(
			'label'               => "OCDS Record",
			'description'         => "OCDS record.",
			'labels'              => $labels,
			'supports'            => array('title', 'thumbnail', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'menu_position'       => 4,
			'show_ui'             => true,
			'show_in_admin_bar'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'rewrite' => array('slug' => 'ocds','with_front' => false),
		);

		register_post_type( 'ocdsrecord', $args );
	}

	public function init_metabox() {
		add_meta_box( 'ocds-meta-box-id', 'OCDS Record', array($this, render_form), 'ocdsrecord', 'normal', 'high' );
	}

	/**
	 * Register the stylesheets and script.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$current_screen = get_current_screen();

		if ($current_screen->post_type === "ocdsrecord") {

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-ocds-editor.css', array(), $this->version, 'all' );
			wp_enqueue_style( "pikaday", plugin_dir_url( __FILE__ ) . 'css/pikaday.css', array(), $this->version, 'all' );

	 		wp_enqueue_script( "underscore", plugin_dir_url( __FILE__ ) . 'js/underscore-min.js', array(), '1.8.3', false );
			wp_enqueue_script( "vue", plugin_dir_url( __FILE__ ) . 'js/vue.js', array(), '2.5.13', false );
			wp_enqueue_script( "moment", plugin_dir_url( __FILE__ ) . 'js/moment.min.js', array(), NULL, false );
			wp_enqueue_script( "pikaday", plugin_dir_url( __FILE__ ) . 'js/pikaday.js', array(), NULL, false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-ocds-editor.js', array(), $this->version, false );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function save() {
		$post_id = get_the_ID();
		if (isset($_POST["wp_ocds_data"])) {
			$data = json_decode(stripslashes($_POST["wp_ocds_data"]));
			if (is_object($data)) {
				update_post_meta($post_id, "wp-ocds-record-data", $_POST["wp_ocds_data"]);
				update_post_meta($post_id, "wp-ocds-record-id", $data->releases[0]->id);
			}
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function render_form() {
		include("partials/wp-ocds-editor-templates.php");
		include("partials/wp-ocds-editor-display.php");
	}
}
