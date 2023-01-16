<?php
/**
 * The main file for the Yoast integration
 */

namespace CoAuthors\Integrations;

use CoAuthors\Integrations\Yoast\CoAuthor;
use Yoast\WP\SEO\Config\Schema_Types;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;
use WP_User;

/**
 * The main Yoast integration class
 */
class Yoast {

	/**
	 * This integration relies on the wpseo_schema_graph added in Yoast 18.7
	 */
	const YOAST_MIN_VERSION = '18.7';

	/**
	 * Public method to be used to initialize this integration
	 *
	 * It will register a callback to the init hook where the actual initializatino happens
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'plugins_loaded', [ __CLASS__, 'do_initialization' ] );
	}

	/**
	 * Do the actual initialization. We don't want to do call before plugins_loaded to make sure we have everything in place to verify our conditions
	 *
	 * @return void
	 */
	public static function do_initialization() {
		if ( self::should_initialize() ) {
			require_once dirname( __FILE__ ) . '/yoast/class-coauthor.php';
			self::register_hooks();
		}
	}

	/**
	 * Checks if we should initialize this integration
	 *
	 * @return boolean
	 */
	protected static function should_initialize() {
		return self::is_yoast_active() && ! self::is_yoast_legacy_integration_enabled();
	}

	/**
	 * Checks if the Yoast plugin is active and running the required version
	 *
	 * @return boolean
	 */
	protected static function is_yoast_active() {
		return defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, self::YOAST_MIN_VERSION, '>=' );
	}

	/**
	 * This integration was originally built in Yoast and left behind a feature flag
	 *
	 * Now that we are moving it to this plugin, lets make sure to not load it if the Yoast version is enabled to avoid conflicts
	 *
	 * @return boolean
	 */
	protected static function is_yoast_legacy_integration_enabled() {
		return defined( 'YOAST_SEO_COAUTHORS_PLUS' ) && YOAST_SEO_COAUTHORS_PLUS;
	}

	/**
	 * Register the hooks
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wpseo_schema_graph', [ __CLASS__, 'filter_graph' ], 11, 2 );
		add_filter( 'wpseo_schema_author', [ __CLASS__, 'filter_author_graph' ], 11, 4 );
		add_filter( 'wpseo_schema_profilepage', [ __CLASS__, 'filter_schema_profilepage' ], 11, 4 );
		add_filter( 'wpseo_meta_author', [ __CLASS__, 'filter_author_meta' ], 11, 2 );
		add_filter( 'wpseo_robots_array', [ __CLASS__, 'allow_indexing_guest_author_archive' ], 10, 2 );
		add_filter( 'wpseo_opengraph_url', [ __CLASS__, 'fix_guest_author_archive_url_presenter' ], 10, 2 );
	}

	/**
	 * Filters the graph output of authors archive for guest authors.
	 *
	 * @param array                   $data                   The schema graph.
	 * @param Meta_Tags_Context       $context                The context object.
	 * @param Abstract_Schema_Piece   $graph_piece_generator  The graph piece generator.
	 * @param Abstract_Schema_Piece[] $graph_piece_generators The graph piece generators.
	 *
	 * @return array The (potentially altered) schema graph.
	 */
	public static function filter_schema_profilepage( $data, $context, $graph_piece_generator, $graph_piece_generators ) {

		if ( ! is_author() ) {
			return $data;
		}

		$user = get_queried_object();

		if ( empty( $user->type ) || $user->type !== 'guest-author' ) {
			return $data;
		}

		// Fix author URL.
		$author_url                                     = get_author_posts_url( $user->ID, $user->user_nicename );
		$graph_piece_generator->context->canonical      = $author_url;
		$graph_piece_generator->context->main_schema_id = $author_url;

		return $graph_piece_generator->generate();
	}

	/**
	 * Filters the graph output to add authors.
	 *
	 * @param array                   $data                   The schema graph.
	 * @param Meta_Tags_Context       $context                The context object.
	 * @param Abstract_Schema_Piece   $graph_piece_generator  The graph piece generator.
	 * @param Abstract_Schema_Piece[] $graph_piece_generators The graph piece generators.
	 *
	 * @return array The (potentially altered) schema graph.
	 */
	public static function filter_author_graph( $data, $context, $graph_piece_generator, $graph_piece_generators ) {
		if ( ! isset( $data['image']['url'] ) ) {
			return $data;
		}

		if ( isset( $data['image']['@id'] ) ) {
			$data['image']['@id'] .= md5( $data['image']['url'] );
		}

		if ( isset( $data['logo']['@id'] ) ) {
			$data['logo']['@id'] .= md5( $data['image']['url'] );
		}

		return $data;
	}

	/**
	 * Filters the graph output to add authors.
	 *
	 * @param array             $data    The schema graph.
	 * @param Meta_Tags_Context $context Context object.
	 *
	 * @return array The (potentially altered) schema graph.
	 */
	public static function filter_graph( $data, $context ) {
		if ( ! is_singular() ) {
			return $data;
		}

		if ( ! function_exists( 'get_coauthors' ) ) {
			return $data;
		}

		/**
		 * Contains the authors from the CoAuthors Plus plugin.
		 *
		 * @var WP_User[] $author_objects
		 */
		$author_objects = get_coauthors( $context->post->ID );

		$ids     = [];
		$authors = [];

		// Add the authors to the schema.
		foreach ( $author_objects as $author ) {
			$author_generator          = new CoAuthor();
			$author_generator->context = $context;
			$author_generator->helpers = YoastSEO()->helpers;

			if ( $author instanceof WP_User ) {
				$author_data = $author_generator->generate_from_user_id( $author->ID );
			} elseif ( ! empty( $author->type ) && $author->type === 'guest-author' ) {
				$author_data = $author_generator->generate_from_guest_author( $author );
			}

			if ( ! empty( $author_data ) ) {
				$ids[]     = [ '@id' => $author_data['@id'] ];
				$authors[] = $author_data;
			}
		}
		$schema_types  = new Schema_Types();
		$article_types = array_map(
			function( $article_type ) {
				return $article_type['value'];
			},
			$schema_types->get_article_type_options()
		);

		// Change the author reference to reference our multiple authors.
		$add_to_graph = false;
		foreach ( $data as $key => $piece ) {
			if ( in_array( $piece['@type'], $article_types, true ) ) {
				$data[ $key ]['author'] = $ids;
				$add_to_graph           = true;
				break;
			}
		}

		if ( $add_to_graph ) {
			// Clean all Persons from the schema, as the user stored as post owner might be incorrectly added if the post post has only guest authors as authors.
			$data = array_filter(
				$data,
				function( $piece ) {
					return empty( $piece['@type'] ) || $piece['@type'] !== 'Person';
				}
			);

			if ( ! empty( $author_data ) ) {
				if ( $context->site_represents !== 'person' || $author->ID !== $context->site_user_id ) {
					$data = array_merge( $data, $authors );
				}
			}
		}

		return $data;
	}

	/**
	 * Filters the author meta tag
	 *
	 * @param string                 $author_name  The article author's display name. Return empty to disable the tag.
	 * @param Indexable_Presentation $presentation The presentation of an indexable.
	 * @return string
	 */
	public static function filter_author_meta( $author_name, $presentation ) {
		$author_objects = get_coauthors( $presentation->context->post->id );

		// Fallback in case of error.
		if ( empty( $author_objects ) ) {
			return $author_name;
		}

		$output = '';
		foreach ( $author_objects as $i => $author ) {
			$output .= $author->display_name;
			if ( $i <= ( count( $author_objects ) - 2 ) ) {
				$output .= ', ';
			}
		}
		return $output;
	}

	/**
	 * CoAuthors Plus and Yoast are incompatible where the author archives for guest authors are output as noindex.
	 * This filter will determine if we're on an author archive and reset the robots.txt string properly.
	 *
	 * See https://github.com/Yoast/wordpress-seo/issues/9147.
	 *
	 * @param string                 $robots       The meta robots directives to be echoed.
	 * @param Indexable_Presentation $presentation The presentation of an indexable.
	 */
	public static function allow_indexing_guest_author_archive( $robots, $presentation ) {
		if ( ! is_author() ) {
			return $robots;
		}

		if ( ! is_a( $presentation, '\Yoast\WP\SEO\Presentations\Indexable_Author_Archive_Presentation' ) ) {
			return $robots;
		}

		$post_type = get_post_type( get_queried_object_id() );
		if ( 'guest-author' !== $post_type ) {
			return $robots;
		}

		/*
		 * If this is a guest author archive and hasn't manually been set to noindex,
		 * make sure the robots.txt string is set properly.
		 */
		if ( empty( $presentation->model->is_robots_noindex ) || 0 === intval( $presentation->model->is_robots_noindex ) ) {
			if ( ! is_array( $robots ) ) {
				$robots = [];
			}
			$robots['index']  = 'index';
			$robots['follow'] = 'follow';
		}

		return $robots;
	}

	public static function fix_guest_author_archive_url_presenter( $url, $presenter ) {
		if ( ! is_author() ) {
			return $url;
		}

		$user = get_queried_object();

		if ( empty( $user->type ) || $user->type !== 'guest-author' ) {
			return $url;
		}

		return get_author_posts_url( $user->ID, $user->user_nicename );
	}
}

Yoast::init();
