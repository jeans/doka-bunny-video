<?php
defined( 'ABSPATH' ) || exit;

/**
 * Lightweight client for Bunny Stream API using WP HTTP API.
 * - Uses 20s timeout
 * - Sends Accept: application/json
 * - Caches list responses via transient
 */
class Doka_Bunny_API {

    const API_BASE   = 'https://video.bunnycdn.com';
    const CACHE_TTL  = 120; // seconds

    /** Build headers with AccessKey */
    private static function headers() : array {
        $access_key = Doka_Bunny_Settings::get( 'access_key' );
        $headers    = [
            'Accept'    => 'application/json',
            'User-Agent'=> 'Doka-Bunny-Video/' . DOKA_BUNNY_VIDEO_VERSION . '; ' . home_url(),
        ];

        // Send AccessKey only if present (required for Stream API).
        if ( ! empty( $access_key ) ) {
            $headers['AccessKey'] = $access_key;
        }

        return $headers;
    }

    /**
     * List videos from a library. (GET /library/{libraryId}/videos)
     * @param int $page
     * @param int $per_page
     * @param string $search
     * @return array|WP_Error
     */
    public static function list_videos( int $page = 1, int $per_page = 48, string $search = '' ) {
        $library_id = (int) Doka_Bunny_Settings::get( 'library_id' );
        if ( ! $library_id ) {
            return new WP_Error( 'doka_bunny_missing_settings', __( 'Bunny Stream is not configured yet.', 'doka-bunny-video' ) );
        }

        // Cache key.
        $key = 'doka_bunny_videos_' . $library_id . '_' . md5( $page . '|' . $per_page . '|' . $search );

        $cached = get_transient( $key );
        if ( false !== $cached ) {
            return $cached;
        }

        $args = [
            'timeout' => 20,
            'headers' => self::headers(),
        ];

        $query = [
            'page'         => max( 1, $page ),
            'itemsPerPage' => max( 1, min( 100, $per_page ) ),
            'orderBy'      => 'date',
        ];
        if ( $search ) {
            $query['search'] = $search;
        }

        $url  = add_query_arg( array_map( 'rawurlencode', $query ), trailingslashit( self::API_BASE ) . 'library/' . $library_id . '/videos' ); // Docs: GET /library/{libraryId}/videos

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== (int) $code ) {
            return new WP_Error( 'doka_bunny_http_' . (int) $code, __( 'Failed to fetch videos from Bunny Stream.', 'doka-bunny-video' ) );
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return new WP_Error( 'doka_bunny_json', __( 'Unexpected response from Bunny Stream.', 'doka-bunny-video' ) );
        }

        // Cache for a short period.
        set_transient( $key, $data, self::CACHE_TTL );
        return $data;
    }

    /**
     * Quick connection test (fetch first page with 1 item).
     * @return true|WP_Error
     */
    public static function test() {
        $result = self::list_videos( 1, 1, '' );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return true;
    }
}
