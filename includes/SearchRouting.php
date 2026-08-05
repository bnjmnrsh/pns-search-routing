<?php
/**
 * Native WordPress search routing and query policy.
 *
 * @package PNS_Search_Routing
 */

namespace PNS\SearchRouting;

use WP_Query;
use WP_Rewrite;

/**
 * Keeps WordPress search URLs canonical without changing Core Search block markup.
 */
final class SearchRouting {
	/**
	 * Register public search hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_editorial_post_types' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_legacy_query_string_search' ), 1 );
	}

	/**
	 * Limit native WordPress search to editorial content.
	 *
	 * Store and product search remain owned by Ecwid. Additional editorial post
	 * types can opt in through the plugin filter without depending on a theme.
	 *
	 * @param WP_Query $query Query object.
	 * @return void
	 */
	public static function apply_editorial_post_types( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		$query->set( 'post_type', self::get_editorial_post_types() );
		$query->set( 'post_status', array( 'publish' ) );
	}

	/**
	 * Return post types included in native site search.
	 *
	 * @return string[]
	 */
	public static function get_editorial_post_types(): array {
		$post_types = apply_filters(
			'pns_search_routing_editorial_post_types',
			array( 'post', 'page' )
		);

		/*
		 * Retain the former theme filter as a compatibility bridge for any local
		 * integration which opted a future editorial post type into search.
		 */
		$post_types = apply_filters(
			'pns_theme_editorial_search_post_types',
			$post_types
		);

		$post_types = array_filter(
			array_map( 'sanitize_key', (array) $post_types ),
			'post_type_exists'
		);

		return array_values( array_unique( $post_types ) );
	}

	/**
	 * Redirect legacy query-string searches to WordPress's pretty search route.
	 *
	 * Core Search blocks submit to the site root as `?s=term`. The pretty route
	 * is already owned by WordPress, so no custom rewrite rule is required.
	 * `/search/?s=term` is included to repair the landing-page/query collision.
	 *
	 * @return void
	 */
	public static function redirect_legacy_query_string_search(): void {
		if (
			! (bool) apply_filters( 'pns_search_routing_enabled', true )
			|| ! self::is_public_get_request()
			|| ! self::uses_pretty_permalinks()
		) {
			return;
		}

		$search_term = self::get_requested_search_term();

		if ( null === $search_term || ! self::is_legacy_query_string_path() ) {
			return;
		}

		$target = get_search_link( $search_term );
		$paged  = isset( $_GET['paged'] ) && is_string( $_GET['paged'] )
			? absint( wp_unslash( $_GET['paged'] ) )
			: 0;

		if ( $paged > 1 ) {
			global $wp_rewrite;

			$target = user_trailingslashit(
				trailingslashit( $target ) . $wp_rewrite->pagination_base . '/' . $paged,
				'paged'
			);
		}

		wp_safe_redirect( $target, self::get_redirect_status(), 'PNS Search Routing' );
		exit;
	}

	/**
	 * Return a non-empty search term from a regular browser request.
	 *
	 * @return string|null
	 */
	private static function get_requested_search_term(): ?string {
		if ( ! isset( $_GET['s'] ) || ! is_string( $_GET['s'] ) ) {
			return null;
		}

		$search_term = sanitize_text_field( wp_unslash( $_GET['s'] ) );

		return '' === trim( $search_term ) ? null : $search_term;
	}

	/**
	 * Check that this is a public browser GET or HEAD request.
	 *
	 * @return bool
	 */
	private static function is_public_get_request(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] )
			? strtoupper( $_SERVER['REQUEST_METHOD'] )
			: 'GET';

		return in_array( $method, array( 'GET', 'HEAD' ), true );
	}

	/**
	 * Check the two legacy paths that can carry a query-string search.
	 *
	 * @return bool
	 */
	private static function is_legacy_query_string_path(): bool {
		$current_path = self::normalise_path( self::get_request_path() );
		$home_path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$landing_path = wp_parse_url( home_url( '/search/' ), PHP_URL_PATH );

		$home_path    = self::normalise_path( is_string( $home_path ) ? $home_path : '/' );
		$landing_path = self::normalise_path( is_string( $landing_path ) ? $landing_path : '/' );

		return $current_path === $home_path || $current_path === $landing_path;
	}

	/**
	 * Return the path of the current request without its query string.
	 *
	 * @return string
	 */
	private static function get_request_path(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || ! is_string( $_SERVER['REQUEST_URI'] ) ) {
			return '/';
		}

		$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );

		return is_string( $path ) ? $path : '/';
	}

	/**
	 * Normalise a URL path for an exact route comparison.
	 *
	 * @param string|null $path URL path.
	 * @return string
	 */
	private static function normalise_path( ?string $path ): string {
		$path = trim( (string) $path, '/' );

		return '' === $path ? '/' : '/' . $path . '/';
	}

	/**
	 * Check whether WordPress can generate a distinct pretty search URL.
	 *
	 * @return bool
	 */
	private static function uses_pretty_permalinks(): bool {
		global $wp_rewrite;

		return $wp_rewrite instanceof WP_Rewrite && $wp_rewrite->using_permalinks();
	}

	/**
	 * Return the response status for canonical redirects.
	 *
	 * @return int
	 */
	private static function get_redirect_status(): int {
		$status = (int) apply_filters( 'pns_search_routing_redirect_status', 301 );

		return in_array( $status, array( 301, 302, 307, 308 ), true ) ? $status : 301;
	}
}
