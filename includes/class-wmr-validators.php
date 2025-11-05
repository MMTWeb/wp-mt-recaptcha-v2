<?php
/**
 * class-wmr-validators.php
 *
 * Server-side verification + AJAX endpoint for reCAPTCHA V2 tokens.
 *
 * This class reads the secret key from the same option used elsewhere (rcm_options),
 * performs the POST to Google's siteverify endpoint, and exposes an AJAX action
 * for client-side verification if needed.
 *
 * A thin wrapper RCM_Validators is created (if not present) so older code that
 * referenced RCM_Validators continues to function.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WMR_Validators' ) ) :

class WMR_Validators{
    /**
     * @var array|object Settings instance or options array (optional)
     */
    private static $settings = null;

    /**
     * Constructor: accepts plugin settings object (optional) and hooks AJAX actions.
     *
     * @param mixed $settings RCM_Settings instance (optional)
     */
    public function __construct( $settings = null ) {
        if ( $settings ) {
            self::$settings = $settings;
        }
        add_action( 'wp_ajax_nopriv_wmr_verify', array( $this, 'ajax_verify' ) );
        add_action( 'wp_ajax_wmr_verify', array( $this, 'ajax_verify' ) );

        // Backwards-compatible alias in case other code calls wmr_* actions (not required).
        add_action( 'wp_ajax_nopriv_wmr_verify', array( $this, 'ajax_verify' ) );
        add_action( 'wp_ajax_wmr_verify', array( $this, 'ajax_verify' ) );
    }

    /**
     * Verify a reCAPTCHA token using Google's siteverify endpoint.
     *
     * Returns true on successful verification, false otherwise.
     *
     * @param string $token
     * @return bool
     */
    public static function verify_token( $token ) {
        if ( empty( $token ) ) {
            return false;
        }

        // Pull secret key from plugin options (same option name used by settings class).
        $opts = get_option('wmr_options', array());
        $secret = isset( $opts['secret_key'] ) ? trim( $opts['secret_key'] ) : '';

        if ( empty( $secret ) ) {
            // No secret configured — fail safely.
            return false;
        }

        $body = array(
            'secret'   => $secret,
            'response' => $token,
            // Optionally you can send remoteip => $_SERVER['REMOTE_ADDR'] here.
        );

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
            'body'    => $body,
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $resp_body = wp_remote_retrieve_body( $response );
        if ( empty( $resp_body ) ) {
            return false;
        }

        $data = json_decode( $resp_body, true );
        if ( ! is_array( $data ) ) {
            return false;
        }

        // For reCAPTCHA v2, 'success' boolean is the key piece of truth.
        if ( ! empty( $data['success'] ) ) {
            return true;
        }

        return false;
    }

    /**
     * AJAX handler to verify token server-side via AJAX.
     *
     * Expects:
     *  - POST['nonce'] the ajax nonce (rcm-verify)
     *  - POST['token'] the g-recaptcha-response token
     *
     * Responses: wp_send_json_success() or wp_send_json_error( array('message' => '...') )
     *
     * @return void (sends JSON and exits)
     */
    public function ajax_verify() {
        // Verify nonce. Use same nonce name produced in rcm-init via wp_create_nonce('rcm-verify').
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wmr-verify' ) ) {
            wp_send_json_error( array( 'message' => 'invalid_nonce' ), 400 );
        }

        $token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

        if ( empty( $token ) ) {
            wp_send_json_error( array( 'message' => 'token_missing' ), 400 );
        }

        $ok = self::verify_token( $token );
        if ( $ok ) {
            wp_send_json_success( array( 'message' => 'verified' ) );
        } else {
            wp_send_json_error( array( 'message' => 'verification_failed' ), 403 );
        }
    }
}

endif; // end if class_exists WMR_Validators

/**
 * Compatibility wrapper: if the plugin code (or earlier files) reference RCM_Validators,
 * provide a thin proxy so calls like RCM_Validators::verify_token() continue to work.
 */
if ( ! class_exists( 'WMR_Validators' ) ) {

    class RCM_Validators {
        /**
         * Proxy constructor to instantiate WMR_Validators for hook registrations.
         *
         * @param mixed $settings
         */
        public function __construct( $settings = null ) {
            // instantiate actual implementation to attach AJAX hooks etc.
            new WMR_Validators( $settings );
        }

        /**
         * Static proxy to the real verification method.
         *
         * @param string $token
         * @return bool
         */
        public static function verify_token( $token ) {
            return WMR_Validators::verify_token( $token );
        }
    }

}
