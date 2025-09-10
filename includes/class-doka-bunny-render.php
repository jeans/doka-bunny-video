<?php
defined( 'ABSPATH' ) || exit;

/**
 * Renders embeds via Bunny iframe player and registers shortcode & block.
 * Docs for embed URL pattern: https://iframe.mediadelivery.net/embed/{libraryId}/{videoId}
 */
class Doka_Bunny_Render {

    /**
     * Build iframe src URL (sanitized).
     */
    private static function embed_src( string $video_id, ?int $library_id = null, array $params = [] ) : string {
        $library_id = $library_id ?: (int) Doka_Bunny_Settings::get( 'library_id' );
        $library_id = max( 1, (int) $library_id );
        $video_id   = preg_replace( '/[^a-f0-9-]/i', '', $video_id );

        $base  = sprintf( 'https://iframe.mediadelivery.net/embed/%d/%s', $library_id, $video_id );

        // Allow a constrained set of player params (booleans true/false).
        $allowed = [ 'autoplay', 'muted', 'loop', 'preload', 'responsive', 'playsinline', 'showSpeed', 'rememberPosition' ];
        $q       = [];
        foreach ( $allowed as $k ) {
            if ( isset( $params[ $k ] ) ) {
                $v     = $params[ $k ];
                $q[$k] = $v ? 'true' : 'false';
            }
        }

        return esc_url( empty( $q ) ? $base : add_query_arg( $q, $base ) );
    }

    /**
     * Public render function used by shortcode and block.
     * - Friendly errors only to editors
     * - Visitors get empty output on error
     */
    public static function render( array $atts ) : string {
        $atts = shortcode_atts( [
            'id'        => '',
            'library'   => '',
            'responsive'=> 'true',
            'autoplay'  => 'false',
            'muted'     => 'false',
            'loop'      => 'false',
            'preload'   => 'true',
        ], $atts, 'doka_bunny_video' );

        $video_id   = sanitize_text_field( $atts['id'] );
        $library_id = $atts['library'] ? (int) preg_replace( '/[^0-9]/', '', $atts['library'] ) : null;
        if ( empty( $video_id ) ) {
            return self::maybe_editor_error( __( 'Bunny video ID is missing.', 'doka-bunny-video' ) );
        }

        $params = [
            'responsive'       => filter_var( $atts['responsive'], FILTER_VALIDATE_BOOLEAN ),
            'autoplay'         => filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN ),
            'muted'            => filter_var( $atts['muted'], FILTER_VALIDATE_BOOLEAN ),
            'loop'             => filter_var( $atts['loop'], FILTER_VALIDATE_BOOLEAN ),
            'preload'          => filter_var( $atts['preload'], FILTER_VALIDATE_BOOLEAN ),
            'playsinline'      => true,
            'rememberPosition' => false,
        ];

        $src = self::embed_src( $video_id, $library_id, $params );

        // Output fully escaped, responsive wrapper with iframe player.
        ob_start();
        ?>
        <div class="doka-bunny-embed" style="position:relative;padding-top:56.25%;">
            <iframe
                src="<?php echo esc_url( $src ); ?>"
                loading="lazy"
                style="border:0;position:absolute;top:0;left:0;width:100%;height:100%;"
                allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
                allowfullscreen="true"
                title="<?php echo esc_attr__( 'Bunny Stream video', 'doka-bunny-video' ); ?>"
            ></iframe>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private static function maybe_editor_error( string $msg ) : string {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<div class="notice notice-error"><p>' . esc_html( $msg ) . '</p></div>';
        }
        return '';
    }

    /** Shortcode registration */
    public static function register_shortcode() : void {
        add_shortcode( 'doka_bunny_video', [ __CLASS__, 'render' ] );
    }

    /** Lightweight dynamic block wrapper (server-rendered) */
    public static function register_block() : void {
        $args = [
            'render_callback' => function( $attributes ) {
                $map = [
                    'videoId'   => 'id',
                    'libraryId' => 'library',
                    'responsive'=> 'responsive',
                    'autoplay'  => 'autoplay',
                    'muted'     => 'muted',
                    'loop'      => 'loop',
                    'preload'   => 'preload',
                ];
                $atts = [];
                foreach ( $map as $from => $to ) {
                    if ( isset( $attributes[ $from ] ) ) {
                        $atts[ $to ] = $attributes[ $from ];
                    }
                }
                return self::render( $atts );
            },
            'attributes' => [
                'videoId'   => [ 'type' => 'string' ],
                'libraryId' => [ 'type' => 'string' ],
                'responsive'=> [ 'type' => 'boolean', 'default' => true ],
                'autoplay'  => [ 'type' => 'boolean', 'default' => false ],
                'muted'     => [ 'type' => 'boolean', 'default' => false ],
                'loop'      => [ 'type' => 'boolean', 'default' => false ],
                'preload'   => [ 'type' => 'boolean', 'default' => true ],
            ]
        ];

        register_block_type( 'doka-bunny-video/embed', $args );
    }
}
