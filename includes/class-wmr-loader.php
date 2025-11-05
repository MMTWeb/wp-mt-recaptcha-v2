<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class WMR_Loader 
{

    private $settings;

    public function __construct($settings) 
    {
        $this->settings = $settings;
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_head', array( $this, 'printGrecaptchaOnce' ), 1 );
    }

    public function enqueue_assets() 
    {
        wp_register_script( 'wmr-init', WMR_URL . 'assets/js/wmr-init.js', array(), '1.0.0', true );
        wp_localize_script( 'wmr-init', 'wmr_settings', array(
            'site_key' => $this->settings->get( 'site_key' ),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wmr-verify' ),
        ));
        wp_enqueue_script( 'wmr-init' );
        wp_enqueue_style( 'wmr-style', WMR_URL . 'assets/css/wmr-style.css' );
    }

    /**
        * Prints the grecaptcha v2 script tag only if we have a site key configured.
        * We use render=explicit so widgets are rendered by grecaptcha.render and we avoid auto execution.
        * Only print once.
    */

    public function printGrecaptchaOnce() 
    {
        $site_key = $this->settings->get( 'site_key' );
        if(empty($site_key )){
            return;
        }

        // If some other script has already loaded grecaptcha (often v3), we still request explicit render but do not append a second script.
        // To be safe we will always print our script tag only when grecaptcha global is not present.
        // But when grecaptcha exists (external v3), we still need to render v2 widgets later — grecaptcha.render works when grecaptcha object exists.

        // Use a small inline check: if window.rcm_grecaptcha_loaded is not set, add the script.
        
        printf("<script>if(typeof window.wmr_grecaptcha_loaded === 'undefined'){window.wmr_grecaptcha_loaded = false;} </script>\n");

        // Output script to load grecaptcha if grecaptcha is not present. We do this with render=explicit so it doesn't auto-render.
        echo "<script>(function(){\n";
        echo " if(typeof window.grecaptcha === 'undefined'){\n";
        // load Google's API with explicit render and onload callback name that rcm-init.js expects (rcmOnload)
        $src = esc_url("https://www.google.com/recaptcha/api.js?onload=wmrOnload&render=explicit");
        echo " var s = document.createElement('script'); s.src = '" . $src . "'; s.async = true; s.defer = true; document.head.appendChild(s);\n";
        echo " window.wmr_grecaptcha_loaded = false;\n";
        echo " }else{\n";
        echo " // grecaptcha already exists (likely V3). mark as loaded so wmr-init.js can render widgets against it.\n";
        echo " window.wmr_grecaptcha_loaded = true;\n";
        echo " if (typeof wmrOnload === 'function') { wmrOnload(); }\n";
        echo " }\n";
        echo "})();</script>\n";
    }
    
}