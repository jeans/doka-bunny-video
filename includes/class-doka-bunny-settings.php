<?php
defined( 'ABSPATH' ) || exit;

/**
 * Settings page that stores Library ID and Access Key.
 */
class Doka_Bunny_Settings {

    const OPTION = 'doka_bunny_video_options';

    public static function get_options() : array {
        $defaults = [
            'library_id' => '',
            'access_key' => '',
        ];
        $opts = get_option( self::OPTION, [] );
        return wp_parse_args( is_array( $opts ) ? $opts : [], $defaults );
    }

    public static function get( string $key, $default = '' ) {
        $opts = self::get_options();
        return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
    }

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_init', [ $this, 'register' ] );
    }

    public function menu() : void {
        add_options_page(
            esc_html__( 'Doka Bunny Video', 'doka-bunny-video' ),
            esc_html__( 'Doka Bunny Video', 'doka-bunny-video' ),
            'manage_options',
            'doka-bunny-video',
            [ $this, 'render_page' ]
        );
    }

    public function register() : void {
        register_setting(
            'doka_bunny_video',
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize' ],
                'default'           => [],
            ]
        );

        add_settings_section(
            'doka_bunny_video_section',
            esc_html__( 'Bunny.net Stream Credentials', 'doka-bunny-video' ),
            function () {
                echo '<p>' . esc_html__( 'Enter your Stream Library ID and AccessKey. The Stream API uses its own AccessKey (from the library), not the account key.', 'doka-bunny-video' ) . '</p>';
                // (Mirrors the clarity of the official plugin’s setup flow)
            },
            'doka_bunny_video'
        );

        add_settings_field(
            'library_id',
            esc_html__( 'Library ID', 'doka-bunny-video' ),
            [ $this, 'field_library_id' ],
            'doka_bunny_video',
            'doka_bunny_video_section'
        );

        add_settings_field(
            'access_key',
            esc_html__( 'Stream AccessKey', 'doka-bunny-video' ),
            [ $this, 'field_access_key' ],
            'doka_bunny_video',
            'doka_bunny_video_section'
        );
    }

    public function sanitize( $data ) : array {
        $out = [];
        $out['library_id'] = isset( $data['library_id'] ) ? preg_replace( '/[^0-9]/', '', (string) $data['library_id'] ) : '';
        $out['access_key'] = isset( $data['access_key'] ) ? sanitize_text_field( $data['access_key'] ) : '';
        return $out;
    }

    public function field_library_id() : void {
        $val = esc_attr( self::get( 'library_id' ) );
        echo '<input type="text" class="regular-text" name="' . esc_attr( self::OPTION ) . '[library_id]" value="' . $val . '" />';
        echo '<p class="description">' . esc_html__( 'Numeric ID of your Stream Library.', 'doka-bunny-video' ) . '</p>';
    }

    public function field_access_key() : void {
        $val = esc_attr( self::get( 'access_key' ) );
        echo '<input type="password" class="regular-text" name="' . esc_attr( self::OPTION ) . '[access_key]" value="' . $val . '" autocomplete="new-password" />';
        echo '<p class="description">' . esc_html__( 'Stream AccessKey from your Library settings.', 'doka-bunny-video' ) . '</p>';
        echo '<p><button type="button" class="button button-secondary" id="doka-bunny-test">' . esc_html__( 'Test connection', 'doka-bunny-video' ) . '</button> <span id="doka-bunny-test-result" aria-live="polite"></span></p>';
    }

    public function render_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Doka Bunny Video', 'doka-bunny-video' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
        settings_fields( 'doka_bunny_video' );
        do_settings_sections( 'doka_bunny_video' );
        submit_button( esc_html__( 'Save Changes', 'doka-bunny-video' ) );
        echo '</form>';
        echo '</div>';
    }
}

// Init settings page.
new Doka_Bunny_Settings();
