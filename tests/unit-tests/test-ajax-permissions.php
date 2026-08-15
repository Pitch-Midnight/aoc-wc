<?php
/**
 * aoc_wc_set_costs_callback must not be reachable without capability.
 *
 * WHY THIS TEST EXISTS
 * -----------------------------------------------------------------------------
 * Until 2026-08-15, AOC_WC_AJAX::aoc_wc_set_costs_callback() checked only
 * wp_verify_nonce( $_POST['security'], 'wp_rest' ) - no is_user_logged_in()
 * and no current_user_can(). Any request carrying a valid wp_rest nonce
 * (which is broadly issued, not scoped to this action) could write
 * _aoc_wc_additional_costs to any order. Filed as S-2 in
 * pitch-midnight/02-plugin-suite-inventory.md.
 *
 * These tests assert the gate from the outside, through WordPress's own
 * `wp_ajax_*` dispatch - not by calling the method directly - so they fail
 * the same way a real request would if the check is ever removed or
 * commented out again. Same shape as wc-net-profit's
 * test-rest-permissions.php, adapted for an AJAX action instead of a REST
 * route: this handler signals failure through the JSON body's `success`
 * key, not an HTTP status code, since it never calls status_header() -
 * assert on that, not on a 403 that this endpoint does not actually send.
 *
 * @package AOC_WC
 */
class Test_AJAX_Permissions extends WP_Ajax_UnitTestCase {

	/**
	 * A real order to write against - not a WC test helper (WooCommerce's
	 * own test suite is not loaded here, only its plugin code), just
	 * wc_create_order() directly.
	 *
	 * @var WC_Order
	 */
	protected $order;

	public function set_up() {
		parent::set_up();

		$this->order = wc_create_order();

		$_POST['post_id'] = $this->order->get_id();
		$_POST['aoc']     = array(
			array( 'label' => 'Handling fee', 'cost' => '4.50' ),
		);
		$_REQUEST['post_id'] = $_POST['post_id'];
		$_REQUEST['aoc']     = $_POST['aoc'];
	}

	public function tear_down() {
		unset(
			$_POST['post_id'], $_POST['security'], $_POST['aoc'],
			$_REQUEST['post_id'], $_REQUEST['security'], $_REQUEST['aoc']
		);

		parent::tear_down();
	}

	/**
	 * Dispatch the AJAX action and decode its JSON response.
	 *
	 * The nonce is minted here, not in set_up() - wp_create_nonce() bakes
	 * the CURRENT user into the hash, so a nonce created before a test
	 * calls wp_set_current_user() belongs to the wrong user and every
	 * check_ajax_referer() call fails regardless of role. Call
	 * wp_set_current_user() first, then dispatch().
	 *
	 * @return array Decoded response body.
	 */
	protected function dispatch() {
		$nonce = wp_create_nonce( 'wp_rest' );
		$_POST['security']    = $nonce;
		$_REQUEST['security'] = $nonce;

		// check_ajax_referer() reads $_REQUEST, not $_POST - PHP only
		// populates $_REQUEST from a real HTTP request body, so a test that
		// manually assigns $_POST alone leaves $_REQUEST empty and every
		// nonce check fails regardless of role. Both are set above.

		try {
			$this->_handleAjax( 'aoc_wc_set_costs' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected - wp_send_json() ends in wp_die(), which the test
			// harness converts into this exception instead of exiting.
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * THIS IS THE REGRESSION. Before the fix, a logged-out request with a
	 * valid nonce successfully wrote order meta.
	 */
	public function test_anonymous_is_denied() {
		wp_set_current_user( 0 );

		$response = $this->dispatch();

		$this->assertFalse(
			$response['success'],
			'Anonymous request must not succeed. A capability-less check ' .
			'lets any request carrying a wp_rest nonce write order data.'
		);
		$this->assertSame(
			'',
			$this->order->get_meta( '_aoc_wc_additional_costs' ),
			'Anonymous request must not have written order meta.'
		);
	}

	/**
	 * A logged-in user without manage_woocommerce must be refused too - the
	 * defect was "no capability check", not "no login check", so a
	 * low-privilege account is the more realistic exploit path than a
	 * fully anonymous one.
	 */
	public function test_subscriber_is_denied() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = $this->dispatch();

		$this->assertFalse(
			$response['success'],
			'Subscriber request must not succeed.'
		);
		$this->assertSame(
			'',
			$this->order->get_meta( '_aoc_wc_additional_costs' ),
			'Subscriber request must not have written order meta.'
		);
	}

	/**
	 * The gate must not be so tight that the plugin stops working.
	 */
	public function test_administrator_is_allowed() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$response = $this->dispatch();

		// The success path never sets $data['success'] - only $data['payload']
		// (pre-existing, not part of this fix; the response contract is
		// asymmetric between success and failure). Assert what actually
		// signals success rather than a key that was never there.
		$this->assertArrayNotHasKey(
			'error',
			$response,
			'Administrator request must not be rejected.'
		);
		$this->assertArrayHasKey(
			'payload',
			$response,
			'Administrator request must succeed and return a payload.'
		);

		$order = wc_get_order( $this->order->get_id() );
		$stored = json_decode( $order->get_meta( '_aoc_wc_additional_costs' ), true );

		$this->assertSame( 'Handling fee', $stored[0]['label'] );
		$this->assertEqualsWithDelta( 4.50, $stored[0]['cost'], 0.001 );
	}

	/**
	 * The other half of the same commit: a rejected request must be
	 * visible to the client, not a silent empty 200. Before the fix, the
	 * denied-path branch did `return new WP_Error(...)` from inside an
	 * AJAX callback, which WordPress discards - wp_send_json( $data ) is
	 * the only real exit, so the caller received `{"html":...}` with no
	 * indication anything failed.
	 */
	public function test_denied_request_reports_failure_in_the_response_body() {
		wp_set_current_user( 0 );

		$response = $this->dispatch();

		$this->assertArrayHasKey(
			'success',
			$response,
			'The response must say whether the request succeeded - silence ' .
			'here is the exact defect this test guards against.'
		);
		$this->assertFalse( $response['success'] );
	}
}
