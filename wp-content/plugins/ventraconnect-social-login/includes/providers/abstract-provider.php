<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Base provider contract.
 */
abstract class Abstract_Provider {
    protected $client_id;
    protected $client_secret;

    public function __construct( $client_id, $client_secret ) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    /** Get slug. */
    abstract public function get_slug();

    /** Get label. */
    abstract public function get_label();

    /** Get icon path (URL). */
    public function get_icon() {
        return ( defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL : plugin_dir_url( __FILE__ ) . '../../' ) . 'assets/img/provider-icons/' . $this->get_slug() . '.svg';
    }

    /** Build auth URL. */
    abstract public function get_auth_url( $state, $redirect_uri );

    /** Exchange code for token. @return array|\WP_Error */
    abstract public function exchange_code_for_token( $code, $redirect_uri );

    /** Fetch profile normalized. @return array|\WP_Error */
    abstract public function fetch_user_profile( $access_token );

    /** Get default scopes array. */
    public function get_scopes() { return []; }
}
