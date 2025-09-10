<?php
defined( 'ABSPATH' ) || exit;

/**
 * REST routes used by settings test & media modal browser.
 */
class Doka_Bunny_REST {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() : void {
        register_rest_route( 'doka-bunny/v1', '/videos', [
            'methods'  => 'GET',
            'callback' => [ $this, 'videos' ],
            'permission_callback' => function () {
                // Only editors/admins can browse Stream via admin.
                return current_user_can( 'upload_files' );
            },
            'args' => [
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
                'search' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ]
        ] );

        register_rest_route( 'doka-bunny/v1', '/test', [
            'methods'  => 'GET',
            'callback' => [ $this, 'test' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            }
        ] );
    }

    /**
     * List videos for media browser (normalized minimal schema).
     */
    public function videos( WP_REST_Request $request ) {
        $page   = max( 1, (int) $request->get_param( 'page' ) );
        $search = sanitize_text_field( (string) $request->get_param( 'search' ) );

        $data = Doka_Bunny_API::list_videos( $page, 48, $search );
        if ( is_wp_error( $data ) ) {
            return $this->error_for_editor_only( $data );
        }

        $items = [];
        if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
            foreach ( $data['items'] as $item ) {
                // Guard & sanitize array keys we actually use.
                $video_id = isset( $item['guid'] ) ? sanitize_text_field( $item['guid'] ) : '';
                $title    = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
                $thumb    = '';
                if ( ! empty( $item['thumbnailFileName'] ) && ! empty( $item['thumbnailPath'] ) ) {
                    // stream thumbnails can be accessed via the library/thumb path, but many dashboards use generated URLs.
                    // Prefer using thumbnailUrl if present in response; otherwise construct best-effort.
                    if ( ! empty( $item['thumbnailUrl'] ) ) {
                        $thumb = esc_url_raw( $item['thumbnailUrl'] );
                    }
                }
                $items[] = [
                    'id'    => $video_id,
                    'title' => $title,
                    'thumb' => $thumb,
                ];
            }
        }

        return rest_ensure_response( [
            'page'  => (int) $page,
            'items' => $items,
        ] );
    }

    /**
     * Settings "Test connection".
     */
    public function test() {
        $ok = Doka_Bunny_API::test();
        if ( is_wp_error( $ok ) ) {
            return $this->error_for_editor_only( $ok );
        }
        return rest_ensure_response( [ 'ok' => true ] );
    }

    /**
     * Show friendly message to capable users, but empty results otherwise.
     */
    private function error_for_editor_only( WP_Error $err ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return new WP_REST_Response( [
                'ok'    => false,
                'error' => sanitize_text_field( $err->get_error_message() ),
            ], 200 );
        }
        // Visitors get empty payloads.
        return rest_ensure_response( [ 'ok' => false, 'items' => [] ] );
    }
}

// Boot REST.
new Doka_Bunny_REST();
