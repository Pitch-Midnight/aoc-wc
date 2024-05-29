<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://www.theritesites.com
 * @since      1.0.0
 * @package    AOC_WC
 * @subpackage AOC_WC/admin
 * @author     TheRiteSites <contact@theritesites.com>
 */
class AOC_WC_Admin {

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->hooks();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __DIR__ ) . 'assets/css/aoc-wc-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		global $post_type, $pagenow;

		if ( ( isset( $_GET['action'] ) && ( $post_type === 'shop_order' && $_GET['action'] === 'edit' ) )
			|| ( $pagenow === 'post-new.php' && $post_type === 'shop_order' )
			|| ( isset( $_GET['page'] ) && $pagenow == 'admin.php' && $_GET['page'] == 'wc-orders' )
		) {
			$js_file = '';
			$css_file = '';
			$path = realpath(dirname(__FILE__) . '/../');
			
			if ( file_exists( $path . '/assets/js/aoc-wc-admin.min.js' ) && !( WP_DEBUG ) ) {
				$js_file =  plugins_url( '/assets/js/aoc-wc-admin.min.js', dirname( __FILE__ ) );
			} else if ( file_exists( $path . '/build/aoc-wc-admin.js' ) && !( WP_DEBUG ) ) {
				$js_file =  plugins_url( '/build/aoc-wc-admin.js', dirname( __FILE__ ) );
			}
			else
				$js_file =  plugins_url( '/assets/js/aoc-wc-admin.js', dirname( __FILE__ ) );

			if ( ! empty( $js_file ) ) {

				wp_register_script( $this->plugin_name, $js_file, array( 'jquery' ), $this->version, false );
					
				wp_localize_script( $this->plugin_name, 'AOCWC', array(
					'currency' => get_woocommerce_currency_symbol(),
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'debug' => defined( 'AOC_WC_DEBUG' ) ? AOC_WC_DEBUG : false,
				));
				wp_enqueue_script( $this->plugin_name );

				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __DIR__ ) . 'assets/js/aoc-wc-admin.js', array( 'jquery' ), $this->version, false );
			}
		}
	}

	public function hooks() {

		// display the order total cost on the order admin page
		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_additional_cost_fields' ), 20, 1 );

	}

	public function display_order_additional_cost_fields( $order_id ) {
		$default = 5;
		
		$new_default = aoc_wc_get_key_value( 'aoc_wc_options', 'aoc_wc_default_aoc', false );
		if ( ! empty( $new_default ) && (int) $new_default > 0 ) {
			$default = (int) $new_default;
			if ( AOC_WC_DEBUG || WP_DEBUG ) {
				AOC_WC_Logger::add_debug( 'Default overriden. the new default is: ' . $default );
			}
		} 
		$order = wc_get_order( $order_id );
		$additional_costs = maybe_unserialize( $order->get_meta( '_aoc_wc_additional_costs' ) );
		if ( gettype( $additional_costs ) == 'string' ) {
			$additional_costs = json_decode( $additional_costs );
		} else {
			if ( AOC_WC_DEBUG || WP_DEBUG ) {
				AOC_WC_Logger::add_debug( "unknown type in additional costs: " . gettype( $additional_costs ) );
			}
		}
		if ( AOC_WC_DEBUG || WP_DEBUG ) {
			AOC_WC_Logger::add_debug( 'Displaying Order ID: ' . $order_id );
			AOC_WC_Logger::add_debug( wc_print_r( $additional_costs, true ) );
		}
		?>
			<tr>
				<td colspan="2" class="label">
					<?php _e( 'Additional Costs:', 'additional-order-costs-for-woocommerce' ) ?>
				</td>
				<td colspan="2">
					<a data-orderid="<?php esc_attr_e( $order_id ); ?>" class="edit-aoc"><?php _e( 'edit', 'additional-order-costs-for-woocommerce' ) ?></a>
					<div class="edit-aoc-buttons" style="display: none;">
						<button class="aoc-cancel-button button" type="button"><?php esc_html( _e( 'Cancel', 'additional-order-costs-for-woocommerce' ) ) ?></button>
						<button class="aoc-save-button button button-primary" type="button"><?php esc_html( _e( 'Save', 'additional-order-costs-for-woocommerce' ) ) ?></button>
					</div>
				</td>
			</tr>

		<?php
		for ( $i = 0; $i < $default; $i++ ) {
			$current_label = '';
			$current_cost = 0.0;
			if ( is_array( $additional_costs )  ) {
				if ( isset( $additional_costs[ $i ] ) ) {
					if ( isset( $additional_costs[ $i ]->label ) ) {
						$current_label = $additional_costs[ $i ]->label;
					} else if ( isset( $additional_costs[ $i ]['label'] ) ) {
						$current_label = $additional_costs[ $i ]->label;
					} else {
						$current_label = 'dead';
					}
					if ( isset( $additional_costs[ $i ]->cost ) ) {
						$current_cost = $additional_costs[ $i ]->cost;
					} else if ( isset( $additional_costs[ $i ]['cost'] ) ) {
						$current_cost = $additional_costs[ $i ]->label;
					} else {
						$current_cost = 1.0;
					}
				}
			}
			$hidden = ( ! empty( $current_cost ) && ! empty( $current_label ) ) ? '' : ' display:none;';

			?>
				<tr class="aoc-row" style="<?php esc_attr_e( $hidden ) ?>" >
					<td colspan="2" class="label">
						<input id="aoc_label_<?php esc_attr_e( $i ); ?>" name="aoc_label[]" placeholder="Note for cost..." class="aoc-edit aoc-label aoc-edit-<?php esc_attr_e( $i ) ?>" type="text" value="<?php esc_attr_e( $current_label ) ?>" style="display:none;" />
						<span id="aoc-label-view[]" class="aoc-label aoc-view "><?php esc_html_e( $current_label ) ?></span>
					</td>
					<td class="total aoc">
						<span id="aoc-cost-view[]" class="aoc-value aoc-view"><?php echo wc_price( floatval( esc_html( $current_cost ) ) ) ?></span>
						<input id="aoc_cost_<?php esc_attr_e( $i ); ?>" name="aoc_cost[]" style="display:none; width: 5em;" class="aoc-edit aoc-value aoc-edit-<?php esc_attr_e( $i ) ?>" type="number" step="0.01" value="<?php echo round( floatval( esc_attr( $current_cost ) ), 2 ) ?>" />
					</td>
				</tr>
			<?php
		}
	}
}
