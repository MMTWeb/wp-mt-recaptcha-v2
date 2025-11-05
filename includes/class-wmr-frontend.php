<?php
if (!defined('ABSPATH')) exit;


class WMR_Frontend 
{

    private $settings;

    public function __construct($settings) 
    {
        $this->settings = $settings;

        // Render reCAPTCHA placeholders
        add_action('woocommerce_login_form', array( $this, 'renderMyAccountLogin'));
        add_action('woocommerce_register_form', array( $this, 'renderMyAccountRegister'), 20);
        add_action('comment_form_after_fields', array( $this, 'renderProductReview' ));
        add_action( 'comment_form_logged_in_after', array( $this, 'renderProductReview'));
        add_action( 'woocommerce_checkout_login', array( $this, 'renderCheckoutLogin' ));
        add_action( 'woocommerce_after_order_notes', array( $this, 'renderCheckout'));

        // Validation hooks
        add_filter( 'woocommerce_process_login_errors', array( $this, 'validateMyAccountLogin' ), 10, 3);
        add_action( 'woocommerce_register_post', array( $this, 'validateMyAccountRegister'), 10, 3);
        add_filter( 'preprocess_comment', array( $this, 'validateCommentRecaptcha'));
        add_action( 'woocommerce_checkout_process', array( $this, 'validateCheckoutRecaptcha'));
    }

    private function isEnabled($key) 
    {
        $enabledForm = $this->settings->get('enabled_forms', array());
        return !empty($enabledForm[$key]);
    }

    private function renderPlaceholder($id) 
    {
        printf( '<div class="wmr-widget" id="wmr-%1$s" data-wmr-id="%1$s"></div>', esc_attr($id));
    }

    // --- Rendering ---
    public function renderMyAccountLogin() 
    {
        if(!$this->isEnabled('myaccount_login')) return;
        echo '<p class="form-row form-row-wide">';
        $this->renderPlaceholder( 'myaccount_login');
        echo '</p>';
    }

    public function renderMyAccountRegister() 
    {
        if(!$this->isEnabled('myaccount_register')) return;
        echo '<p class="form-row form-row-wide">';
        $this->renderPlaceholder( 'myaccount_register' );
        echo '</p>';
    }

    public function renderProductReview() 
    {
        if(!is_singular('product')) return;
        if(! $this->isEnabled('product_review')) return;
        echo '<p class="comment-form-recaptcha">';
        $this->renderPlaceholder('product_review');
        echo '</p>';
    }

    public function renderCheckoutLogin() 
    {
        if (!$this->isEnabled('checkout_login')) return;
        echo '<div class="wmr-checkout-login">';
        $this->renderPlaceholder( 'checkout_login' );
        echo '</div>';
    }

    public function renderCheckout() 
    {
        if(!$this->isEnabled('checkout')) return;
        echo '<div class="wmr-checkout">';
        $this->renderPlaceholder('checkout');
        echo '</div>';
    }

    /** Recaptcha validations */

    //Login
    public function validateMyAccountLogin($validation_error, $user, $password) 
    {
        if(!$this->isEnabled('myaccount_login')) return $validation_error;

        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash( $_POST['g-recaptcha-response'])) : '';

        if(empty( $token ) || ! WMR_Validators::verify_token($token)){
            $validation_error->add( 'wmr_error', __( '<strong>Error</strong>: reCAPTCHA verification failed. Please try again.', 'wmr' ) );
        }

        return $validation_error;
    }

    //Registration
    public function validateMyAccountRegister($username, $email, $errors) 
    {
        if(!$this->isEnabled('myaccount_register')) return;

        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';

        if(empty($token) || ! WMR_Validators::verify_token( $token )){
            $errors->add( 'wmr_error', __( '<strong>Error</strong>: reCAPTCHA verification failed. Please try again.', 'wmr' ) );
        }
    }

    //Reviews
    public function validateCommentRecaptcha($commentdata) 
    {
        if(!$this->isEnabled('product_review')) return $commentdata;

        if(!is_singular('product')) return $commentdata;

        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';

        if(empty( $token ) || ! WMR_Validators::verify_token($token)){
            wp_die( esc_html__( 'reCAPTCHA validation failed. Please go back and try again.', 'wmr' ) );
        }

        return $commentdata;
    }

    //Checkout
    public function validateCheckoutRecaptcha() 
    {
        if(!$this->isEnabled('checkout')) return;

        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';

        if(empty($token) || ! WMR_Validators::verify_token($token)){
            wc_add_notice( __( 'reCAPTCHA validation failed. Please complete the reCAPTCHA checkbox.', 'wmr' ), 'error' );
        }
    }
}