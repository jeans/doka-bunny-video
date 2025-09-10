<?php
/**
 * Plugin Name: Doka Bunny Video
 * Description: Deep Media Library integration for bunny.net Stream videos with a settings UI, REST proxy, and a responsive player shortcode/block.
 * Version: 1.0.0
 * Author: SPORT1 / Jens Doka
 * License: GPL-2.0-or-later
 * Text Domain: doka-bunny-video
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Doka_Bunny_Video_Plugin' ) ) {

    /**
     * Main plugin class.
     */
    final class Doka_Bunny_Video_Plugin {

        /** @var Doka_Bunny_Video_Plugin */
        private static $instance;

        /** Singleton */
        public static function instance() : self {
            if ( ! self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /** Boot the plugin */
        private function __construct() {
            $this->define_constants();
            $this->includes();
            $this->hooks();
        }

        private function define_constants() : void {
            define( 'DOKA_BUNNY_VIDEO_VERSION', '1.0.0' );
            define( 'DOKA_BUNNY_VIDEO_FILE', __FILE__ );
            define( 'DOKA_BUNNY_VIDEO_DIR', plugin_dir_path( __FILE__ ) );
            define( 'DOKA_BUNNY_VIDEO_URL', plugin_dir_url( __FILE__ ) );
        }

        private function includes() : void {
            require_once DOKA_BUNNY_VIDEO_DIR . 'includes/class-doka-bunny-settings.php';
            require_once DOKA_BUNNY_VIDEO_DIR . 'includes/class-doka-bunny-api.php';
            require_once DOKA_BUNNY_VIDEO_DIR . 'includes/class-doka-bunny-rest.php';
            require_once DOKA_BUNNY_VIDEO_DIR . 'includes/class-doka-bunny-render.php';
        }

        private function hooks() : void {
            // Admin assets and media tab.
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

            // Register shortcode and block on init.
            add_action( 'init', [ 'Doka_Bunny_Render', 'register_shortcode' ] );
            add_action( 'init', [ 'Doka_Bunny_Render', 'register_block' ] );
        }

        /**
         * Enqueue admin scripts for settings and Media Modal integration.
         */
        public function enqueue_admin_assets( $hook ) : void {
            // Settings page assets.
            if ( 'settings_page_doka-bunny-video' === $hook ) {
                wp_enqueue_style( 'doka-bunny-admin', DOKA_BUNNY_VIDEO_URL . 'assets/admin.css', [], DOKA_BUNNY_VIDEO_VERSION );
                wp_enqueue_script( 'doka-bunny-admin', DOKA_BUNNY_VIDEO_URL . 'assets/admin.js', [ 'jquery' ], DOKA_BUNNY_VIDEO_VERSION, true );
                wp_localize_script( 'doka-bunny-admin', 'DokaBunnyAdmin', [
                    'nonce'     => wp_create_nonce( 'wp_rest' ),
                    'testRoute' => esc_url_raw( rest_url( 'doka-bunny/v1/test' ) ),
                ] );
            }

            // Media Modal assets (for all admin where media enqueue happens).
            wp_enqueue_script(
                'doka-bunny-media',
                DOKA_BUNNY_VIDEO_URL . 'assets/media-modal.js',
                [ 'jquery', 'media-views', 'wp-util' ],
                DOKA_BUNNY_VIDEO_VERSION,
                true
            );

            wp_localize_script( 'doka-bunny-media', 'DokaBunny', [
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'videos'    => esc_url_raw( rest_url( 'doka-bunny/v1/videos' ) ),
                'insertTpl' => '[doka_bunny_video id="%s"]',
                'i18n'      => [
                    'tabTitle' => esc_html__( 'Bunny Stream', 'doka-bunny-video' ),
                    'search'   => esc_html__( 'Search videos…', 'doka-bunny-video' ),
                    'insert'   => esc_html__( 'Insert Video', 'doka-bunny-video' ),
                ],
            ] );
            wp_enqueue_style( 'doka-bunny-media', DOKA_BUNNY_VIDEO_URL . 'assets/media-modal.css', [], DOKA_BUNNY_VIDEO_VERSION );
        }
    }

    // Boot.
    add_action( 'plugins_loaded', [ 'Doka_Bunny_Video_Plugin', 'instance' ] );
}
