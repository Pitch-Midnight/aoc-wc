<?php
/**
 * Additional Order Costs for WooCommerce Settings class.
 *
 * @since   1.0.0
 * @package AOC_WC
 */

class AOC_WC_Settings {
	/**
	 * Parent plugin class.
	 *
	 * @var    AOC_WC
	 * @since  1.0.0
	 */
	protected $plugin = null;

	/**
	 * Option key, and option page slug.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $key = 'aoc_wc_settings'; 

	/**
	 * Options page metabox ID.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected static $metabox_id = 'aoc_wc_settings_metabox';

	/**
	 * Options Page title.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $title = '';

	/**
	 * Options Page hook.
	 *
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param  AOC_WC $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// The translated title assignment that used to be here is REMOVED.
		// It was the sole cause of WordPress 6.7's
		// "_load_textdomain_just_in_time ... triggered too early" notice for this
		// plugin: this constructor runs during plugin construction, well before
		// init, and calling esc_attr__() there forces WordPress to load the
		// textdomain just in time. Confirmed by backtrace on wp56tester.
		//
		// It was also dead: $this->title was assigned and never read anywhere in
		// this plugin. If a title is ever needed, set it on init or later.
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {

		// Hook in our actions to the admin.
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		
	}

	/**
	 * Add custom fields to the options page.
	 *
	 * @since  1.2.0
	 */
	public function add_options_page_metabox() {

		/**
		 * Registers secondary options page, and set main item as parent.
		 * TODO: Add new settings tab for Net Profit specific settings
		 */
		$args = array(
			'id'           => 'aoc_wc_options_page',
			'menu_title'   => 'Additional Order Costs Options', // Use menu title, & not title to hide main h2.
			'object_types' => array( 'options-page' ),
			'option_key'   => 'aoc_wc_options',
			'parent_slug'  => 'the_rite_plugins_settings',
			'tab_group'    => 'the_rite_plugins_settings',
			'tab_title'    => 'Additional Costs Options',
		);
		// 'tab_group' property is supported in > 2.4.0.
		if ( version_compare( CMB2_VERSION, '2.4.0' ) ) {
			$args['display_cb'] = array( $this, 'aoc_wc_options_display_with_tabs' );
		}

		$aoc_wc_options = new_cmb2_box( $args );

		$aoc_wc_options->add_field( array(
			'name'    => 'Plugin Settings',
			'desc'	  => 'General plugin settings',
			'id'      => 'aoc_wc_settings_title',
			'type'    => 'title',
		) );

		$aoc_wc_options->add_field( array(
			'name'    => 'Enable debug mode?',
			'id'      => 'aoc_wc_debug',
			'desc'	  => 'Check this to enable output to the WooCommerce debug log for this plugin',
			'type'    => 'checkbox',
		) );

		$aoc_wc_options->add_field( array(
			'name'    => 'Set default number of additional cost lines?',
			'id'      => 'aoc_wc_default_aoc',
			'desc'	  => 'Choose a number to display that many additional cost lines by default in the order screen.',
			'type'    => 'text_small',
			'default' => '5',
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '1',
			),
		) );

	}

	/**
	 * Initiates tab driven settings page
	 * 
	 * @since 1.0.0
	 */
	public function aoc_wc_options_display_with_tabs( $cmb_options ) {
		$tabs = $this->aoc_wc_options_page_tabs( $cmb_options );
		?>
		<div class="wrap cmb2-options-page option-<?php echo $cmb_options->option_key; ?>">
			<?php if ( get_admin_page_title() ) : ?>
				<h2><?php echo wp_kses_post( get_admin_page_title() ); ?></h2>
			<?php endif; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $option_key => $tab_title ) : ?>
					<a class="nav-tab<?php if ( isset( $_GET['page'] ) && $option_key === $_GET['page'] ) : ?> nav-tab-active<?php endif; ?>" href="<?php menu_page_url( $option_key ); ?>"><?php echo wp_kses_post( $tab_title ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="<?php echo $cmb_options->cmb->cmb_id; ?>" enctype="multipart/form-data" encoding="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo esc_attr( $cmb_options->option_key ); ?>">
				<?php $cmb_options->options_page_metabox(); ?>
				<?php submit_button( esc_attr( $cmb_options->cmb->prop( 'save_button' ) ), 'primary', 'submit-cmb' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Helper function to get tabs for settings tabular view
	 * 
	 * @since 1.0.0
	 */
	public function aoc_wc_options_page_tabs( $cmb_options ) {
		$tab_group = $cmb_options->cmb->prop( 'tab_group' );
		$tabs      = array();
		foreach ( CMB2_Boxes::get_all() as $cmb_id => $cmb ) {
			if ( $tab_group === $cmb->prop( 'tab_group' ) ) {
				$tabs[ $cmb->options_page_keys()[0] ] = $cmb->prop( 'tab_title' )
					? $cmb->prop( 'tab_title' )
					: $cmb->prop( 'title' );
			}
		}
		return $tabs;
	}

	/**
	 * Wrapper function around cmb2_get_option.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $key     Options array key
	 * @param  mixed  $default Optional default value
	 * @return mixed           Option value
	 */
	public static function get_key_value( $key = '', $value = '', $default = false ) {
		if ( function_exists( 'cmb2_get_option' ) ) {

			// Use cmb2_get_option as it passes through some key filters.
			return cmb2_get_option( $key, $value, $default );
		}

		// Fallback to get_option if CMB2 is not loaded yet.
		$opts = get_option( $key, $default );

		$val = $default;

		if ( 'all' == $value ) {
			$val = $opts;
		} elseif ( is_array( $opts ) && array_key_exists( $value, $opts ) && false !== $opts[ $value ] ) {
			$val = $opts[ $value ];
		}

		return $val;
	}

	/**
	 * Wrapper function around cmb2_get_option.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $key     Options array key
	 * @param  mixed  $default Optional default value
	 * @return mixed           Option value
	 */
	public static function get_value( $key = '', $default = false ) {
		if ( function_exists( 'cmb2_get_option' ) ) {

			// Use cmb2_get_option as it passes through some key filters.
			return cmb2_get_option( 'the_rite_plugins_settings', $key, $default );
		}

		// Fallback to get_option if CMB2 is not loaded yet.
		$opts = get_option( 'the_rite_plugins_settings', $default );

		$val = $default;

		if ( 'all' == $key ) {
			$val = $opts;
		} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
			$val = $opts[ $key ];
		}

		return $val;
	}
}
