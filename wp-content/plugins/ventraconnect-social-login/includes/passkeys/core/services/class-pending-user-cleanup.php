<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Pending_User_Cleanup {

	const META_PENDING_REGISTRATION          = 'ventraconnect_passkeys_pending_registration';
	const META_PENDING_CONTEXT               = 'ventraconnect_passkeys_pending_context';
	const META_PENDING_REGISTRATION_CONTEXT  = 'ventraconnect_passkeys_pending_registration_context';
	const META_PENDING_SOURCE                = 'ventraconnect_passkeys_pending_source';
	const META_PENDING_REGISTRATION_SOURCE   = 'ventraconnect_passkeys_pending_registration_source';
	const META_PENDING_CREATED_AT            = 'ventraconnect_passkeys_pending_created_at';
	const META_PENDING_REGISTRATION_CREATED_AT = 'ventraconnect_passkeys_pending_registration_created_at';
	const META_PENDING_CLEANUP_TOKEN         = 'ventraconnect_passkeys_pending_cleanup_token';
	const LEGACY_TEST_META_KEY               = '_ventraconnect_passkeys_public_registration_test';
	const LEGACY_PENDING_META_KEY            = '_ventraconnect_passkeys_public_registration_pending';
	const CLEANUP_TRANSIENT_KEY              = 'ventraconnect_passkeys_pending_cleanup_lock';
	const DEFAULT_STALE_AGE                  = HOUR_IN_SECONDS;

	protected $passkey_repository = null;
	protected $challenge_service  = null;

	public function mark_user_pending( $user_id, $context, $source ) {
		$user_id = absint( $user_id );
		$context = sanitize_key( $context );
		$source  = sanitize_key( $source );

		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, self::META_PENDING_REGISTRATION, 1 );
		update_user_meta( $user_id, self::META_PENDING_CONTEXT, $context );
		update_user_meta( $user_id, self::META_PENDING_REGISTRATION_CONTEXT, $context );
		update_user_meta( $user_id, self::META_PENDING_SOURCE, $source );
		update_user_meta( $user_id, self::META_PENDING_REGISTRATION_SOURCE, $source );
		update_user_meta( $user_id, self::META_PENDING_CREATED_AT, time() );
		update_user_meta( $user_id, self::META_PENDING_REGISTRATION_CREATED_AT, time() );
		update_user_meta( $user_id, self::LEGACY_TEST_META_KEY, 1 );
		update_user_meta( $user_id, self::LEGACY_PENDING_META_KEY, 1 );
	}

	public function clear_pending_state( $user_id ) {
		$user_id = absint( $user_id );

		if ( $user_id <= 0 ) {
			return;
		}

		delete_user_meta( $user_id, self::META_PENDING_REGISTRATION );
		delete_user_meta( $user_id, self::META_PENDING_CONTEXT );
		delete_user_meta( $user_id, self::META_PENDING_REGISTRATION_CONTEXT );
		delete_user_meta( $user_id, self::META_PENDING_SOURCE );
		delete_user_meta( $user_id, self::META_PENDING_REGISTRATION_SOURCE );
		delete_user_meta( $user_id, self::META_PENDING_CREATED_AT );
		delete_user_meta( $user_id, self::META_PENDING_REGISTRATION_CREATED_AT );
		delete_user_meta( $user_id, self::META_PENDING_CLEANUP_TOKEN );
		delete_user_meta( $user_id, self::LEGACY_TEST_META_KEY );
		delete_user_meta( $user_id, self::LEGACY_PENDING_META_KEY );
	}

	public function issue_cleanup_token( $user_id ) {
		$user_id = absint( $user_id );

		if ( $user_id <= 0 || ! $this->is_pending_registration_user( $user_id ) ) {
			return '';
		}

		$token = wp_generate_password( 32, false, false );
		update_user_meta( $user_id, self::META_PENDING_CLEANUP_TOKEN, $token );

		return $token;
	}

	public function get_cleanup_token_validation_reason( $user_id, $token ) {
		$user_id = absint( $user_id );
		$token   = is_string( $token ) ? trim( $token ) : '';

		if ( $user_id <= 0 || '' === $token ) {
			return 'missing_token_or_user';
		}

		$stored_token = (string) get_user_meta( $user_id, self::META_PENDING_CLEANUP_TOKEN, true );

		if ( '' === $stored_token ) {
			return 'missing_cleanup_token';
		}

		if ( ! hash_equals( $stored_token, $token ) ) {
			return 'token_mismatch';
		}

		return 'valid';
	}

	public function delete_pending_user_with_token( $user_id, $token, $stale_age = 0 ) {
		$token_reason = $this->get_cleanup_token_validation_reason( $user_id, $token );

		if ( 'valid' !== $token_reason ) {
			$this->debug_log( 'Pending cleanup token validation failed.', array( 'user_id' => absint( $user_id ), 'reason' => $token_reason ) );
			return false;
		}

		return $this->delete_pending_user_if_safe( $user_id, $stale_age );
	}

	public function count_pending_users() {
		return count( $this->get_pending_user_ids() );
	}

	public function count_stale_pending_users( $stale_age = self::DEFAULT_STALE_AGE ) {
		$count = 0;

		foreach ( $this->get_pending_user_ids() as $user_id ) {
			if ( $this->is_user_eligible_for_cleanup( $user_id, $stale_age ) ) {
				$count++;
			}
		}

		return $count;
	}

	public function maybe_run_scheduled_cleanup( $stale_age = self::DEFAULT_STALE_AGE ) {
		$lock = (int) get_transient( self::CLEANUP_TRANSIENT_KEY );

		if ( $lock > 0 ) {
			return 0;
		}

		set_transient( self::CLEANUP_TRANSIENT_KEY, time(), HOUR_IN_SECONDS );

		return $this->cleanup_stale_pending_users( $stale_age );
	}

	public function cleanup_stale_pending_users( $stale_age = self::DEFAULT_STALE_AGE ) {
		$deleted = 0;

		foreach ( $this->get_pending_user_ids() as $user_id ) {
			if ( $this->delete_pending_user_if_safe( $user_id, $stale_age ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	public function delete_pending_user_if_safe( $user_id, $stale_age = self::DEFAULT_STALE_AGE ) {
		$user_id = absint( $user_id );
		$reason  = $this->get_cleanup_refusal_reason( $user_id, $stale_age );

		if ( 'eligible' !== $reason ) {
			$this->debug_log( 'Pending cleanup refused.', array( 'user_id' => $user_id, 'reason' => $reason ) );
			return false;
		}

		$this->get_challenge_service()->cleanup_unused_challenges_for_user( $user_id );
		$this->get_challenge_service()->cleanup_expired();

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		if ( ! function_exists( 'wp_delete_user' ) ) {
			$this->debug_log( 'Pending cleanup failed: wp_delete_user unavailable.', array( 'user_id' => $user_id ) );
			return false;
		}

		$deleted = (bool) wp_delete_user( $user_id );

		$this->debug_log(
			$deleted ? 'Pending cleanup succeeded.' : 'Pending cleanup failed: wp_delete_user returned false.',
			array( 'user_id' => $user_id )
		);

		return $deleted;
	}

	public function is_user_eligible_for_cleanup( $user_id, $stale_age = self::DEFAULT_STALE_AGE ) {
		return 'eligible' === $this->get_cleanup_refusal_reason( $user_id, $stale_age );
	}

	public function get_cleanup_refusal_reason( $user_id, $stale_age = self::DEFAULT_STALE_AGE ) {
		$user_id = absint( $user_id );
		$user    = $user_id > 0 ? get_user_by( 'id', $user_id ) : false;

		if ( ! $user instanceof WP_User ) {
			return 'user_not_found';
		}

		if ( ! $this->is_pending_registration_user( $user_id ) ) {
			return 'missing_pending_meta';
		}

		if ( ! $this->was_created_by_passkeys_flow( $user_id ) ) {
			return 'not_passkeys_flow_user';
		}

		if ( ! $this->is_pending_registration_stale( $user_id, $stale_age ) ) {
			return 'not_stale';
		}

		if ( user_can( $user, 'manage_options' ) ) {
			return 'user_has_manage_options';
		}

		if ( $this->has_role_cleanup_risk( $user ) ) {
			return 'user_has_elevated_role_or_caps';
		}

		if ( ! empty( $this->get_passkey_repository()->get_user_passkeys( $user_id, true ) ) ) {
			return 'user_has_active_passkeys';
		}

		if ( $this->user_has_authored_content( $user_id ) ) {
			return 'user_has_authored_content';
		}

		if ( $this->user_has_woocommerce_orders( $user_id ) ) {
			return 'user_has_orders';
		}

		return 'eligible';
	}

	protected function get_pending_user_ids() {
		$query = new WP_User_Query(
			array(
				'fields'     => 'ID',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Targeted cleanup lookup limited to two plugin-owned pending-registration meta keys.
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'   => self::META_PENDING_REGISTRATION,
						'value' => '1',
					),
					array(
						'key'   => self::LEGACY_PENDING_META_KEY,
						'value' => '1',
					),
				),
			)
		);

		$user_ids = $query->get_results();

		return array_map( 'absint', is_array( $user_ids ) ? $user_ids : array() );
	}

	protected function is_pending_registration_user( $user_id ) {
		return '1' === (string) get_user_meta( $user_id, self::META_PENDING_REGISTRATION, true )
			|| '1' === (string) get_user_meta( $user_id, self::LEGACY_PENDING_META_KEY, true );
	}

	protected function was_created_by_passkeys_flow( $user_id ) {
		$source = sanitize_key( (string) get_user_meta( $user_id, self::META_PENDING_SOURCE, true ) );

		if ( '' === $source ) {
			$source = sanitize_key( (string) get_user_meta( $user_id, self::META_PENDING_REGISTRATION_SOURCE, true ) );
		}

		return in_array( $source, array( 'passkey_register_test', 'woo_register_form', 'wp_register_form' ), true )
			|| '1' === (string) get_user_meta( $user_id, self::LEGACY_TEST_META_KEY, true );
	}

	protected function is_pending_registration_stale( $user_id, $stale_age ) {
		$created_at = absint( get_user_meta( $user_id, self::META_PENDING_CREATED_AT, true ) );

		if ( $created_at <= 0 ) {
			$created_at = absint( get_user_meta( $user_id, self::META_PENDING_REGISTRATION_CREATED_AT, true ) );
		}

		if ( $created_at <= 0 ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! $user instanceof WP_User || empty( $user->user_registered ) ) {
				return false;
			}

			$registered_time = strtotime( (string) $user->user_registered );

			if ( false === $registered_time ) {
				return false;
			}

			$created_at = absint( $registered_time );
		}

		return ( time() - $created_at ) >= absint( $stale_age );
	}

	protected function has_role_cleanup_risk( WP_User $user ) {
		$roles        = array_values( array_filter( array_map( 'sanitize_key', (array) $user->roles ) ) );
		$default_role = sanitize_key( (string) get_option( 'default_role', 'subscriber' ) );
		$allowed      = array_filter(
			array_unique(
				array(
					$default_role,
					'subscriber',
					'customer',
				)
			)
		);

		if ( empty( $roles ) ) {
			return true;
		}

		if ( 1 !== count( $roles ) ) {
			return true;
		}

		if ( user_can( $user, 'edit_posts' ) || user_can( $user, 'publish_posts' ) || user_can( $user, 'delete_users' ) || user_can( $user, 'promote_users' ) ) {
			return true;
		}

		return ! in_array( $roles[0], $allowed, true );
	}

	protected function user_has_authored_content( $user_id ) {
		$posts = get_posts(
			array(
				'author'                 => absint( $user_id ),
				'post_type'              => 'any',
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return ! empty( $posts );
	}

	protected function user_has_woocommerce_orders( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return false;
		}

		$order_ids = wc_get_orders(
			array(
				'customer_id' => absint( $user_id ),
				'limit'       => 1,
				'return'      => 'ids',
			)
		);

		return ! empty( $order_ids );
	}

	protected function get_passkey_repository() {
		if ( ! $this->passkey_repository instanceof VentraConnect_SL_Passkeys_Core_Passkey_Repository ) {
			$this->passkey_repository = new VentraConnect_SL_Passkeys_Core_Passkey_Repository();
		}

		return $this->passkey_repository;
	}

	protected function get_challenge_service() {
		if ( ! $this->challenge_service instanceof VentraConnect_SL_Passkeys_Core_Challenge_Service ) {
			$this->challenge_service = new VentraConnect_SL_Passkeys_Core_Challenge_Service();
		}

		return $this->challenge_service;
	}

	protected function debug_log( $message, array $context = array() ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		\VentraConnect\SocialLogin\Logger::auth(
			'passkeys',
			array_merge(
				$context,
				array(
					'event' => $message,
				)
			)
		);
	}
}
