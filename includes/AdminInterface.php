<?php

namespace Codemanas\UserActivationValidate;
/**
 * Class AdminInterface
 * @package Codemanas\UserActivationValidate
 */
class AdminInterface {

	private static $_instance = null;
	private $admin_tabs = null;
	public $page_suffix = null;

	/**
	 * Create only one instance so that it may not Repeat
	 *
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	/*
	 * Load Plugin Admin Scripts
	 */
	public function load_scripts( $hook_suffix ) {
		wp_register_script( 'uav-admin-js', CODEMANAS_UAV_DIR_URL . 'assets/js/admin.js', array( 'jquery' ), '1.0.0', true );
		if ( $hook_suffix == $this->page_suffix ) {
			wp_localize_script( 'uav-admin-js', 'uav', [
			        'pluginFolder' => CODEMANAS_UAV_DIR_URL,
                    'uav_ajax_nonce' => wp_create_nonce( 'uav_check_ajax_nonce' )
            ] );
			wp_enqueue_script( 'uav-admin-js' );
		}
	}



	/**
	 * Add a sub menu under the Users admin menu.
	 *
	 * @since 1.0.0
	 */
	public function create_admin_menu() {
		$this->page_suffix = add_submenu_page(
			'users.php',
			'User Activation Validate',
			'User Activation Validate',
			'manage_options',
			'uav',
			array( $this, 'generate_plugin_page' )
		);
	}

	/**
	 * Callback function of add_submenu_page
	 *
	 * @since 1.0.0
	 */
	public function generate_plugin_page() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
			if ( isset( $_GET['tab'] ) ) {
				$this->plugin_tab( $_GET['tab'] );
			} else {
				$this->plugin_tab( 'users' );
			}
			settings_errors();
			?>
        </div>
		<?php
	}

	/**
	 * Generates tab titles, highlights and displays the current viewing tab content
     *
     * @param $current
	 *
	 * @since 1.0.0
	 */
	public function plugin_tab( $current = 'users' ) {
		echo '<div id="icon-themes" class="icon32"><br></div>';
		echo '<div class="error" id="uav-error-msg" style="display: none;"></div>';
		echo '<h2 class="nav-tab-wrapper">';


		$this->admin_tabs = apply_filters( 'uav_admin_pages', array(
			'users' => array(
				'name' => __( 'Inactive Users', 'uav' ),
				'path' => CODEMANAS_UAV_DIR_PATH . '/views/'
			)
		) );

		foreach ( $this->admin_tabs as $key => $value ) {
			$class = ( $key == $current ) ? ' nav-tab-active' : '';
			$name = $value['name'];
			echo "<a class='nav-tab$class' href='?page=uav&tab=$key'>$name</a>";
		}

		echo '</h2>';

		// now load the appropriate view
        if( array_key_exists($current, $this->admin_tabs ) ) {

            $file = $this->admin_tabs[ $current ]['path'] . $current . '.php';

	        if ( file_exists( $file ) ) {
		        require_once $file;
	        } else {
	            echo __( 'File not found', 'uav' );
            }

        } else {
	        echo '<p>' . __( 'Sorry this content is only for pro version features. Click on the available tabs above.', 'uav' ) . '</p>';
        }
	}


}
