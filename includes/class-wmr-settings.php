<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WMR_Settings{
    
    private $option_name = 'wmr_options';
    public  $options;


    public function __construct() 
    {

        $this->options = get_option( $this->option_name, array(
            'site_key' => '',
            'secret_key' => '',
            'enabled_forms' => array(
            'myaccount_login' => 1,
            'myaccount_register' => 1,
            'product_review' => 1,
            'checkout_login' => 1,
            'checkout' => 1,
            ),
        ));

        add_action( 'admin_menu', array( $this, 'addAdminMenu' ));
        add_action( 'admin_init', array( $this, 'registerSettings'));
    }

    public function addAdminMenu() 
    {
        add_options_page( 'WP MT Recaptcha', 'WP MT Recaptcha', 'manage_options', 'wmr-settings', array( $this, 'optionsPage' ) );
    }

    public function registerSettings() 
    {
        register_setting( 'wmr_options_group', $this->option_name );

        add_settings_section( 'wmr_main_section', 'Main settings', null, 'wmr-settings' );

        add_settings_field( 'site_key', 'Site Key (V2)', array( $this, 'fieldSiteKey' ), 'wmr-settings', 'wmr_main_section' );
        add_settings_field( 'secret_key', 'Secret Key', array( $this, 'fieldSecretKey' ), 'wmr-settings', 'wmr_main_section' );
        add_settings_field( 'enabled_forms', 'Enable on forms', array( $this, 'fieldEnableForms' ), 'wmr-settings', 'wmr_main_section' );
    }


    public function fieldSiteKey() 
    {
        printf('<input type="text" name="%s[site_key]" value="%s" style="width:400px" />', esc_attr( $this->option_name ), esc_attr( $this->options['site_key'] ));
    }

    public function fieldSecretKey() 
    {
        printf( '<input type="text" name="%s[secret_key]" value="%s" style="width:400px" />', esc_attr( $this->option_name ), esc_attr( $this->options['secret_key'] ) );
    
    }

    public function fieldEnableForms() 
    {
        $enabled = isset( $this->options['enabled_forms'] ) ? $this->options['enabled_forms'] : array();

        $forms = array(
            'myaccount_login' => 'My Account - Login',
            'myaccount_register' => 'My Account - Register',
            'product_review' => 'Product Reviews (single product)',
            'checkout_login' => 'Checkout - Login',
            'checkout' => 'Checkout - Main Form',
        );

        foreach($forms as $key => $label){
            $checked = ! empty( $enabled[ $key ] ) ? 'checked' : '';
            printf( '<label><input type="checkbox" name="%s[enabled_forms][%s]" value="1" %s /> %s</label><br>', esc_attr( $this->option_name ), esc_attr( $key ), $checked, esc_html( $label ) );
        }
    }

    public function optionsPage() 
    {
    ?>
        <div class="wrap">
            <h1>WP MT Recaptcha - Settings</h1>
            <form method="post" action="options.php">
    <?php
                settings_fields( 'wmr_options_group' );
                do_settings_sections( 'wmr-settings' );
                submit_button();
    ?>
            </form>
            <p>Note: This plugin renders explicit reCAPTCHA V2 widgets and will not inject any reCAPTCHA V3 scripts. If you have an external V3 script on the same page, the plugin will avoid using v3 APIs so they don't conflict.</p>
        </div>
    <?php
    }
    
    public function get($key, $default = '') 
    {
        return isset($this->options[$key]) ? $this->options[ $key ] : $default;
    }
}