<?php

class Headline_Envy {
	private $admin;
	private $optimizely;

	public $cron = 'headline_envy_winner_cron';
	public $optimizely_project_id;
	public $option_key = 'headline_envy_settings';
	public $slug = 'headline-envy';
	public $script_version = 1;

	// cache for experiment details
	private $optimizely_experiments = array();

	/**
	 * constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( $this->cron, array( $this, 'winner_cron' ) );
		add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html' ), 10, 2 );

		$this->optimizely();

		if ( is_admin() ) {
			$this->admin();
		} else {
			add_filter( 'the_title', array( $this, 'the_title' ), 0, 2 );
			add_filter( 'post_thumbnail_html', array( $this, 'post_thumbnail_html' ), 0, 5 );
		}//end else
	}//end __construct

	/**
	 * admin object accessor method
	 */
	public function admin() {
		if ( ! $this->admin ) {
			require_once __DIR__ . '/class-headline-envy-admin.php';

			$this->admin = new Headline_Envy_Admin( $this );
		}//end if

		return $this->admin;
	}//end admin

	/**
	 * optimizely object accessor method
	 */
	public function optimizely() {
		$api_key = $this->get_options( 'optimizely_api_key' );

		if ( ! $this->optimizely ) {
			require_once __DIR__ . '/external/wp-optimizely.php';

			$this->optimizely = new WP_Optimizely( $api_key );
		}//end if

		return $this->optimizely;
	}//end optimizely

	/**
	 * Hooked to the init action
	 */
	public function init() {
		$options = $this->get_options();

		$this->register_resources( $options );

		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

		if ( $options && ! empty( $options['optimizely_project_id'] ) ) {
			$this->optimizely_project_id = $options['optimizely_project_id'];

			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts_optimizely' ), 0 );
		}//end if

		if (
			! empty( $options['auto_select_winner'] )
			&& ! is_admin()
			&& ( ! defined( 'DOING_CRON' ) || ( defined( 'DOING_CRON' ) && ! DOING_CRON ) )
			&& ( ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) )
		) {
			$this->schedule_cron();
		}//end if
	}//end init

	/**
	 * Register scripts and styles
	 */
	public function register_resources( $options ) {
		// @TODO: output minified CSS

		wp_register_style(
			'headline-envy',
			plugins_url( 'css/headline-envy.css', __FILE__ ),
			array(),
			headline_envy()->script_version
		);

		wp_register_script(
			'headline-envy-admin',
			plugins_url( 'js/lib/headline-envy-admin.js', __FILE__ ),
			array(),
			headline_envy()->script_version,
			TRUE
		);

		wp_register_script(
			'headline-envy',
			plugins_url( 'js/lib/headline-envy.js', __FILE__ ),
			array( 'jquery' ),
			headline_envy()->script_version,
			TRUE
		);

		wp_register_script(
			'headline-envy-tag',
			plugins_url( 'js/lib/headline-envy-tag.js', __FILE__ ),
			array(),
			headline_envy()->script_version,
			FALSE
		);
	}//end register_resources

	/**
	 * Hooked to the wp_enqueue_scripts action
	 */
	public function wp_enqueue_scripts_optimizely() {
		$optimizely_project_id = preg_replace( '/[^0-9]/', '', $this->optimizely_project_id );

		wp_enqueue_script(
			'optimizely',
			"//cdn.optimizely.com/js/{$optimizely_project_id}.js",
			array( 'jquery' )
		);
	}//end wp_enqueue_scripts_optimizely

	/**
	 * Hooked to the wp_enqueue_scripts action
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_script( 'headline-envy-tag' );
		wp_enqueue_script( 'headline-envy' );
	}//end wp_enqueue_scripts

	/**
	 * Schedules pseudo cron for auto-selecting winners
	 */
	public function schedule_cron() {
		$timestamp = wp_next_scheduled( $this->cron );

		if ( FALSE === $timestamp ) {
			wp_schedule_event( time(), 'hourly', $this->cron );
		}//end if
	}//end schedule_cron

	/**
	 * Hooked to the headline_envy_winner_cron declared in the wp_schedule_event call
	 */
	public function winner_cron() {
		$options = $this->get_options();

		if (
			empty( $options['optimizely_project_id'] )
			|| empty( $options['optimizely_api_key'] )
			|| empty( $options['auto_select_winner'] )
		) {
			return;
		}//end if

		$experiments = $this->optimizely()->get_experiments( $options['optimizely_project_id'] );

		if ( ! $experiments || is_wp_error( $experiments ) ) {
			return;
		}//end if

		foreach ( $experiments as $experiment ) {
			preg_match( '/HeadlineEnvy \[([0-9]+)\](\[([a-z]+)\])?:/', $experiment->description, $matches );
			$post_id = absint( $matches[1] );
			$type    = isset( $matches[3] ) ? $matches[3] : 'title';

			if ( ! $post_id || 'Running' !== $experiment->status ) {
				continue;
			}//end if

			$results = $this->optimizely()->get_experiment_results( $experiment->id );
			$primary_results = $this->get_experiment_primary_results( $experiment, $results );

			// if there is a winner in the primary results, mark it as such
			foreach ( $primary_results as $result ) {
				if ( 'winner' !== $result->status ) {
					continue;
				}//end if

				$this->select_winner( $post_id, $type, $result->variation_id );
				break;
			}//end foreach
		}//end foreach
	}//end winner_cron

	public function wp_kses_allowed_html( $allowedposttags, $context ) {
		if ( 'post' != $context ) {
			return $allowedposttags;
		}//end if

		$allowedposttags['headline-envy'] = array(
			'data-experiment' => TRUE,
		);

		return $allowedposttags;
	}//end wp_kses_allowed_html

	/**
	 * Hooked to the the_title filter to wrap text in our custom element
	 */
	public function the_title( $title, $post_id ) {
		$post = get_post( $post_id );
		$options = $this->get_options();

		// is this a valid post type for headline experiments?
		$whitelisted_post_types = apply_filters( 'headline_envy_post_types', $options['post_types'] );
		if ( ! isset( $post->post_type ) || ! in_array( $post->post_type, $whitelisted_post_types ) ) {
			return $title;
		}//end if

		$experiment = $this->get_experiment( $post_id, 'title', FALSE );

		if ( ! $experiment || ! $experiment['experiment_titles'] ) {
			return $title;
		}//end if

		return '<headline-envy data-experiment="' . esc_attr( $experiment['experiment_id'] ) . '">' . $title . '</headline-envy>';
	}//end the_title

	/**
	 * Hooked to the the_title filter to wrap text in our custom element
	 */
	public function post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		$options = $this->get_options();

		// If we aren't testing images we'll stop here
		if ( ! $options['test_images'] ) {
			return $html;
		}

		$post = get_post( $post_id );

		// is this a valid post type for headline experiments?
		$whitelisted_post_types = apply_filters( 'headline_envy_post_types', $options['post_types'] );
		if ( ! isset( $post->post_type ) || ! in_array( $post->post_type, $whitelisted_post_types ) ) {
			return $html;
		}//end if

		$experiment = $this->get_experiment( $post_id, 'image', FALSE );

		if ( ! $experiment || ! $experiment['experiment_images'] ) {
			return $html;
		}//end if
		
		$additions = ' data-experiment="' . esc_attr( $experiment['experiment_id'] ) . '" data-size="' . esc_attr( $size ) . '"$0';
		
		return '<headline-envy-image data-experiment="' . esc_attr( $experiment['experiment_id'] ) . '" data-size="' . esc_attr( $size ) . '">' . trim( $html ) . '</headline-envy-image>';
	}//end the_title

	/**
	 * Select winner
	 */
	public function select_winner( $post_id, $type = 'title', $variation_id ) {
		// get title for the variant
		$experiment = $this->get_experiment( $post_id, $type, FALSE );

		// Set the title to the new 'winning' value
		if ( 'title' == $type ) {
			$experiment = $this->set_winning_title( $post_id, $experiment, $variation_id );
		} elseif ( 'image' == $type ) {
			$experiment = $this->set_winning_image( $post_id, $experiment, $variation_id );
		}

		// Get the existing meta
		$meta = $this->get_post_meta( $post_id );

		// Make sure the key is initialized
		if ( ! isset( $meta['previous_experiments'] ) ) {
			$meta['previous_experiments'] = array();
		}//end if

		// pause the experiment
		$this->optimizely()->update_experiment( $experiment['experiment_id'], array(
			'status' => 'Archived',
		) );

		// archive experiment id
		$experiment['previous_experiments'][ $type ][] = $experiment['experiment_id'];

		// since the experiment is no longer running for this post, let's remove the experiment_id key
		unset( $experiment['experiment_id'] );

		$meta[ $type ] = $experiment;

		// save experiment to post meta!
		update_post_meta( $post_id, 'headline-envy', $meta );

		return TRUE;
	}//end select_winner

	/**
	 * Update the post with the winning title
	 */
	public function set_winning_title( $post_id, $experiment, $variation_id ) {
		if ( $variation_id > 0 ) {
			$new_title = FALSE;
			foreach ( $experiment['experiment_titles'] as $title ) {
				if ( $variation_id == $title['variation'] ) {
					$new_title = $title['value'];
				}//end if
			}// end foreach

			if ( ! $new_title ) {
				return FALSE;
			}//end if

			// update post title
			wp_update_post( array(
				'ID' => absint( $post_id ),
				'post_title' => $new_title,
			) );
		}//end if

		// clear experiment titles
		$experiment['experiment_titles'] = array();

		return $experiment;
	}

	/**
	 * Update the post with the winning image
	 */
	public function set_winning_image( $post_id, $experiment, $variation_id ) {
		if ( $variation_id > 0 ) {
			$new_title = FALSE;
			foreach ( $experiment['experiment_images'] as $image ) {
				if ( $variation_id == $image['variation'] ) {
					$new_thumbnail = $image['value'];
				}//end if
			}// end foreach

			if ( ! $new_thumbnail ) {
				return FALSE;
			}//end if

			// update post thumbnail
			set_post_thumbnail( $post_id, $new_thumbnail );
		}//end if

		// clear experiment titles
		$experiment['experiment_images'] = array();

		return $experiment;
	}

	/**
	 * Retrieves a post's headline variations
	 */
	public function get_experiment( $post_id, $type = 'title', $include_live_optimizely_data = TRUE ) {
		$meta = $this->get_post_meta( $post_id );
		$meta = $meta[ $type ];

		$meta_key = 'image' == $type ? 'experiment_images' : 'experiment_titles';

		if ( empty( $meta[ $meta_key ] ) || empty( $meta['experiment_id'] ) || ! $this->optimizely() ) {
			return array();
		}//end if

		if ( $include_live_optimizely_data ) {
			// check the object cache, if it is empty, fill it!
			if ( empty( $this->optimizely_experiments[ $post_id ][ $type ] ) ) {
				$this->optimizely_experiments[ $post_id ][ $type ][ $meta_key ] = $meta[ $meta_key ];

				$experiment = $this->optimizely()->get_experiment( $meta['experiment_id'] );
				$this->optimizely_experiments[ $post_id ][ $type ]['experiment'] = $experiment;

				$results = $this->optimizely()->get_experiment_results( $meta['experiment_id'] );
				$this->optimizely_experiments[ $post_id ][ $type ]['results'] = $results;
				$this->optimizely_experiments[ $post_id ][ $type ]['primary_results'] = $this->get_experiment_primary_results( $experiment, $results );

				foreach ( $this->optimizely_experiments[ $post_id ][ $type ][ $meta_key ] as &$item ) {
					if ( empty( $this->optimizely_experiments[ $post_id ][ $type ]['primary_results'][ $item['variation'] ] ) ) {
						continue;
					}//end if

					$result = $this->optimizely_experiments[ $post_id ][ $type ]['primary_results'][ $item['variation'] ];

					$item['winner'] = 'winner' == $result->status ? TRUE : FALSE;
					$item['improvement'] = round( $result->improvement * 100, 1 ) . '%';
					$item['conversion_rate'] = round( $result->conversion_rate * 100, 1 ) . '%';
				}//end foreach
			}//end if

			$meta[ $meta_key ] = $this->optimizely_experiments[ $post_id ][ $type ][ $meta_key ];
		}//end if

		return $meta;
	}//end get_experiment

	/**
	 * Collects the primary results for a given experiment
	 */
	public function get_experiment_primary_results( $experiment, $results ) {
		$primary_results = array();

		// Do we actually have some valid experiment results yet?
		if ( ! is_array( $results ) ) {
			return $primary_results;
		}//end if

		foreach ( $results as $result ) {
			if ( $result->goal_id != $experiment->primary_goal_id ) {
				continue;
			}//end if

			$primary_results[ $result->variation_id ] = $result;
		}//end foreach

		return $primary_results;
	}//end get_experiment_primary_results

	/**
	 * Fetches HeadlineEnvy options
	 */
	public function get_options( $key = FALSE ) {
		static $options;

		if ( ! $options )
		{
			$options = get_option( $this->option_key, array(
				'post_types' => array(
					'post',
				),
			) );
		}//end if

		if ( $key ) {
			return isset( $options[ $key ] ) ? $options[ $key ] : FALSE;
		}//end if

		return $options;
	}//end get_options

	/**
	 * Gets the HeadlineEnvy meta for a post
	 */
	public function get_post_meta( $post_id ) {
		$meta = get_post_meta( $post_id, 'headline-envy', TRUE );

		if ( ! isset( $meta['title'] ) ) {
			$meta = array(
				'title' => $meta,
				'image' => '',
			);

			if ( isset( $meta['title']['previous_experiments'] ) ) {
				$meta['previous_experiments']['title'] = $meta['title']['previous_experiments'];
				unset( $meta['title']['previous_experiments'] );
			}//end if
		}//end if

		return $meta;
	}//end get_options
}//end class

/**
 * singleton function
 */
function headline_envy() {
	global $headline_envy;

	if ( ! $headline_envy ) {
		$headline_envy = new Headline_Envy;
	}//end if

	return $headline_envy;
}//end headline_envy
