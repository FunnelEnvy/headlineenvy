<?php

class Headline_Envy_Admin {
	private $core;
	private $settings_fields = array(
		'optimizely_api_key' => 'sanitize_text_field',
		'optimizely_project_id' => 'absint',
		'optimizely_shell_experiment_id' => 'absint',
		'test_images' => 'absint',
		'auto_select_winner' => 'absint',
		'post_types' => 'sanitize_text_field',
	);

	/**
	 * constructor
	 */
	public function __construct( $core ) {
		$this->core = $core;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		if ( $this->core->optimizely() ) {
			add_action( 'save_post', array( $this, 'save_post' ) );

			$version = get_bloginfo( 'version' );
			if ( version_compare( $version, '4.1' ) >= 0 ) {
				add_action( 'edit_form_before_permalink', array( $this, 'edit_form_before_permalink' ) );
			} else {
				// this location doesn't look as good in the UI, but gives us compatibility back to 3.5
				add_action( 'edit_form_after_title', array( $this, 'edit_form_before_permalink' ) );
			}//end else
		}//end if
	}//end __construct

	/**
	 * Hooked to the admin_enqueue_scripts action
	 */
	public function admin_enqueue_scripts( $hook ) {
		// we only want the CSS enqueued on the edit page
		if (
			'post.php' !== $hook
			&& 'post-new.php' !== $hook
			&& 'headlineenvy_page_headline-envy-settings' !== $hook
			&& 'headlineenvy_page_headline-envy-results' !== $hook
			&& 'toplevel_page_headline-envy-settings' !== $hook
		) {
			return;
		}//end if

		// if we're on the post page, fetch the experiment if it exists so we can localize the titles
		if ( 'post.php' == $hook ) {
			global $post;

			$title_experiment = $this->core->get_experiment( $post->ID );
			$image_experiment = $this->core->get_experiment( $post->ID, 'image' );

			if ( $title_experiment ) {
				$data = array(
					'experiment_titles' => array(),
				);

				if ( ! empty( $title_experiment['experiment_titles'] ) ) {
					foreach ( $title_experiment['experiment_titles'] as $title ) {
						$data['experiment_titles'][] = $title;
					}//end foreach
				}//end if
			}//end if

			if ( $image_experiment ) {
				$data = array(
					'experiment_images' => array(),
				);

				if ( ! empty( $title_experiment['experiment_images'] ) ) {
					foreach ( $title_experiment['experiment_images'] as $image ) {
						$data['experiment_titles'][] = $image;
					}//end foreach
				}//end if
			}

			$data['test_images'] = $this->core->get_options( 'test_images' );

			if ( isset( $data ) ) {
				wp_localize_script( 'headline-envy-admin', 'headline_envy_admin', $data );
			}
		}//end if

		wp_enqueue_style( 'headline-envy' );
		wp_enqueue_script( 'headline-envy-admin' );
	}//end admin_enqueue_scripts

	/**
	 * Hooked to the admin_menu action
	 */
	public function admin_menu() {
		$options = $this->core->get_options();

		if ( empty( $options['optimizely_api_key'] ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices_optimizely' ) );
		}//end elseif

		add_menu_page( __( 'HeadlineEnvy', 'headline-envy' ), __( 'HeadlineEnvy', 'headline-envy' ), 'manage_options', 'headline-envy-settings', array( $this, 'settings' ) );
		add_submenu_page( 'headline-envy-settings', __( 'Results', 'headline-envy' ), __( 'Results', 'headline-envy' ), 'edit_posts', 'headline-envy-results', array( $this, 'results' ) );

		// if the settings page was submitted, let's save the data and redirect to the settings page again
		// to avoid the silly reload POST behavior
		if (
			'POST' == $_SERVER['REQUEST_METHOD']
			&& isset( $_POST[ $this->core->option_key ] )
			&& $this->verify_nonce( "{$this->core->slug}-save-settings" )
		) {
			$this->update_settings( $_POST );

			// we need to do a JS redirect here because the page has already begun to render
			wp_redirect( admin_url( 'admin.php?page=headline-envy-settings' ) );
			die;
		}//end if
	}//end admin_menu

	/**
	 * Updates settings
	 */
	public function update_settings( $data ) {
		if ( ! isset( $data[ $this->core->option_key ] ) ) {
			return;
		}//end if

		$options = $this->sanitize_settings( $data[ $this->core->option_key ] );
		$options = $this->create_shell_experiment( $options );
		$options = wp_parse_args( $options, $this->core->get_options() );

		update_option( $this->core->option_key, $options );

		// schedule cron (or remove it) if necessary
		if ( empty( $options['auto_select_winner'] ) ) {
			wp_clear_scheduled_hook( $this->core->cron );
		} else {
			$this->core->schedule_cron();
		}//end else
	}//end update_settings

	/**
	 * hooked to the admin_notices action to inject a message if the Optimizely API key is missing
	 */
	public function admin_notices_optimizely() {
		?>
		<div class="error">
			<p>
				<?php esc_html_e( 'It looks like the Headline Envy plugin is activated but still needs your', 'headline-envy' ); ?> <a href="https://www.optimizely.com/tokens" target="_blank"><?php esc_html_e( 'Optimizely API key', 'headline-envy' ); ?></a>!
				<?php esc_html_e( 'You can set that on the Headline Envy', 'headline-envy' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=headline-envy-settings' ) ); ?>"><?php esc_html_e( 'settings page', 'headline-envy' ); ?></a>.
			</p>
		</div>
		<?php
	}//end admin_notices_optimizely

	/**
	 * Create shell experiment that will be used as a template for HeadlineEnvy experiments
	 */
	public function create_shell_experiment( $options ) {
		if ( empty( $options['optimizely_project_id'] ) || ! empty( $options['optimizely_shell_experiment_id'] ) ) {
			return $options;
		}//end if

		$experiments = $this->core->optimizely()->get_experiments( $options['optimizely_project_id'] );

		if ( $experiments && ! is_wp_error( $experiments ) ) {
			$experiment_name = 'HeadlineEnvy experiment template for ' . home_url();
			$experiment_id = FALSE;

			foreach ( $experiments as $experiment ) {
				if ( $experiment_name === $experiment->description ) {
					$experiment_id = $experiment->id;
				}//end if
			}//end foreach

			if ( ! $experiment_id ) {
				$experiment = $this->core->optimizely()->create_experiment( $options['optimizely_project_id'], array(
					'description' => $experiment_name,
					'edit_url' => home_url(),
					'url_conditions' => array(
						(object) array(
							'match_type' => 'regex',
							'value' => home_url() . '.*',
						),
					),
					'status' => 'Not started',
					'activation_mode' => 'manual',
				) );

				// @TODO: determine what goals we should create and assign this experiment to by default
			}//end if

			$options['optimizely_shell_experiment_id'] = $experiment->id;
		}//end if

		return $options;
	}//end create_shell_experiment

	/**
	 * Hooked to the save_post action
	 */
	public function save_post( $post_id ) {
		// Check that this isn't an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}// end if

		// Don't run on post revisions (almost always happens just before the real post is saved)
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}// end if

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return;
		}// end if

		$options = $this->core->get_options();

		// check post type matches what you intend
		$whitelisted_post_types = apply_filters( 'headline_envy_post_types', $options['post_types'] );
		if ( ! isset( $post->post_type ) || ! in_array( $post->post_type, $whitelisted_post_types ) ) {
			return;
		}// end if

		// Check the nonce
		if ( ! $this->verify_nonce( $this->core->slug . '-save-post' ) ) {
			return;
		}// end if

		// Check the permissions
		if ( ! current_user_can( 'edit_post' , $post->ID  ) ) {
			return;
		}// end if

		if ( ! isset( $_POST['headline_envy_titles_loaded'] ) ) {
			return;
		}//end if

		if ( ! empty( $_POST['he-winner'] ) ) {
			remove_action( 'save_post', array( $this, 'save_post' ) );
			$this->core->select_winner( $post->ID, 'title', absint( $_POST['he-winner'] ) );
			return;
		}//end if

		$titles = array();

		if ( ! empty( $_POST['headline_envy_title'] ) ) {
			$titles = $_POST['headline_envy_title'];
		}//end if

		$this->save_titles( $post->ID, $titles );
	}//end save_post

	/**
	 * verify the nonce
	 */
	public function verify_nonce( $action )
	{
		$key = "{$action}-nonce";

		if ( ! isset( $_POST[ $key ] ) ) {
			return FALSE;
		}// end if

		return wp_verify_nonce( $_POST[ $key ], $action );
	}//end verify_nonce

	/**
	 * Hooked to the edit_form_before_permalink action
	 */
	public function edit_form_before_permalink( $post ) {
		$options = $this->core->get_options();

		$whitelisted_post_types = apply_filters( 'headline_envy_post_types', $options['post_types'] );

		// If the post type isn't one of the supported ones
		if ( ! isset( $post->post_type ) || ! in_array( $post->post_type, $whitelisted_post_types ) ) {
			return;
		}//end if

		$experiment = $this->core->get_experiment( $post->ID );

		$container_class = '';

		if ( $experiment ) {
			$container_class = 'has-experiment';
		}//end if

		$experiment_id = empty( $experiment['experiment_id'] ) ? 0 : $experiment['experiment_id'];

		include_once __DIR__ . '/templates/title-ui.php';
	}//end edit_form_before_permalink

	/**
	 * Helper function for generating consistent field ids
	 *
	 * @param $name string slug name for the field
	 */
	private function get_field_id( $name )
	{
		return $this->core->option_key . '-' . $name;
	}// end get_field_id

	/**
	 * Helper function for generating consistent field names
	 *
	 * @param $name string slug name for the field
	 */
	private function get_field_name( $name )
	{
		return str_replace( '-', '_', $this->core->option_key ) . "[{$name}]";
	}// end get_field_name

	/**
	 * sanitizes setting submissions
	 */
	public function sanitize_settings( $data ) {
		$sanitized = array();

		// Handle checkboxes where a missing value means they were deselected
		$sanitized['test_images']        = isset( $data['test_images'] ) ? 1 : '';
		$sanitized['auto_select_winner'] = isset( $data['auto_select_winner'] ) ? 1 : '';

		foreach ( $this->settings_fields as $field => $sanitization ) {
			if ( ! isset( $data[ $field ] ) ) {
				continue;
			}//end if

			// if the item isn't an array, clean the value and continue
			if ( ! is_array( $data[ $field ] ) ) {
				$sanitized[ $field ] = $sanitization( trim( $data[ $field ] ) );
				continue;
			}//end if

			if ( ! isset( $sanitized[ $field ] ) ) {
				$sanitized[ $field ] = array();
			}//end if

			// sanitize each value in the array
			foreach ( $data[ $field ] as $key => $value ) {
				$sanitized[ $field ][ $key ] = $sanitization( trim( $value ) );
			}//end foreach
		}//end foreach

		return $sanitized;
	}//end sanitize_settings

	/**
	 * Output the settings page
	 */
	public function settings() {
		$options = $this->core->get_options();

		if ( ! isset( $options['post_types'] ) ) {
			$options['post_types'] = array(
				'post',
			);
		}//end if

		$invalid_optimizely_api_key = FALSE;

		if ( ! empty( $options['optimizely_api_key'] ) ) {
			$projects = $this->core->optimizely()->get_projects();

			if ( ! $projects || is_wp_error( $projects ) || 'Authentication failed' == $projects ) {
				$invalid_optimizely_api_key = TRUE;
			}//end if
		}//end if

		include_once __DIR__ . '/templates/settings.php';
	}//end settings

	/**
	 * Display the results page
	 */
	public function results() {
		$options = $this->core->get_options();

		$experiments = array();

		if ( ! empty( $options['optimizely_api_key'] ) && ! empty( $options['optimizely_project_id'] ) ) {
			$temp_experiments = array();
			$temp_experiments = $this->core->optimizely()->get_experiments( $options['optimizely_project_id'] );

			foreach ( $temp_experiments as $experiment ) {
				if ( 'Running' !== $experiment->status ) {
					continue;
				}//end if

				if ( ! preg_match( '/^HeadlineEnvy \[[0-9]+\]:/', $experiment->description ) ) {
					continue;
				}//end if

				$data = array(
					'experiment' => $experiment,
					'results' => array(),
				);

				$temp_results = $this->core->optimizely()->get_experiment_results( $experiment->id );

				foreach ( $temp_results as $result ) {
					if ( ! isset( $data['results'][ $result->goal_name ] ) ) {
						$data['results'][ $result->goal_name ] = array();
					}//end if

					$data['results'][ $result->goal_name ][] = $result;
				}//end foreach

				$experiments[] = $data;
			}//end foreach
		}//end if

		include_once __DIR__ . '/templates/results.php';
	}//end results

	/**
	 * Spits out a text field
	 */
	public function text_field( $key ) {
		$options = $this->core->get_options();
		$value = NULL;

		if ( ! empty( $options[ $key ] ) ) {
			$value = $options[ $key ];
		}//end if

		$field_id = $this->get_field_id( $key );
		$field_name = $this->get_field_name( $key );

		?>
		<input class="widefat" type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $value ); ?>" autocomplete="false">
		<?php
	}//end text_field

	/**
	 * Spits out a text field
	 */
	public function check_box( $key ) {
		$options = $this->core->get_options();
		$value = NULL;

		if ( ! empty( $options[ $key ] ) ) {
			$value = $options[ $key ];
		}//end if

		$field_id = $this->get_field_id( $key );
		$field_name = $this->get_field_name( $key );

		?>
		<input type="checkbox" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="1" <?php checked( TRUE, (bool) $value ); ?>>
		<?php
	}//end check_box

	/**
	 * Saves titles as variations on an experiment. If there isn't an experiment, one is created.
	 *
	 * @param $post_id int Post ID
	 * @param $titles array Collection of titles on the post (as a string)
	 */
	public function save_titles( $post_id, $titles ) {
		$experiment = $this->core->get_experiment( $post_id, 'title', FALSE );

		$junk_variation = FALSE;
		if ( empty( $experiment['experiment_id'] ) ) {
			// if there aren't any titles, bail.
			if ( ! $titles ) {
				return;
			}//end if

			$experiment = $this->initialize_experiment( $post_id, 'title', $experiment );
			$variations = $this->core->optimizely()->get_variations( $experiment['experiment_id'] );

			$junk_variation = $variations[1]->id;
		}//end if

		if ( ! isset( $experiment['experiment_titles'] ) ) {
			$experiment['experiment_titles'] = array();
		}//end if

		// sanitize titles
		if ( $titles ) {
			foreach ( $titles as &$title ) {
				$title = sanitize_text_field( $title );
			}//end foreach
		}//end if

		$current_experiment_titles = wp_list_pluck( $experiment['experiment_titles'], 'value' );
		$titles_to_delete = array_diff( $current_experiment_titles, $titles );
		$titles_to_add = array_diff( $titles, $current_experiment_titles );

		// if there aren't any titles to remove and there aren't any titles to add, let's bail
		if ( ! $titles_to_delete && ! $titles_to_add ) {
			return;
		}//end if

		// if there are titles to delete, let's remove them from the experiment
		if ( $titles_to_delete ) {
			$experiment = $this->remove_titles_from_experiment( $experiment, $titles_to_delete );
		}//end if

		// if there are titles to add, let's create the appropriate variations in Optimizely
		if ( $titles && $titles_to_add ) {
			$experiment = $this->add_titles_to_experiment( $experiment, $titles_to_add );
		}//end if

		// clear out the automagically added variant
		if ( $junk_variation ) {
			$res = $this->core->optimizely()->delete_variation( $junk_variation );
		}//end if

		// if titles were added or removed, we'll need to rebalance the weights on the variations
		if ( $titles_to_add || $titles_to_delete || $junk_variation ) {
			$this->balance_variation_weights( $experiment, $titles );
		}//end if

		/**
		 * SHOULD WE DO THIS?  It pauses fine, but when you switch an experiment back to running, it looks like it is creating a new project
		// if there aren't any titles, let's pause the experiment
		if ( $current_experiment_titles && ! $titles ) {
			// if we're removing all titles, let's pause the experiment
			$this->core->optimizely()->update_experiment( $experiment['experiment_id'], array(
				'status' => 'Paused',
			) );
		} elseif ( $titles && ! $current_experiment_titles && ! $junk_variation ) {
			// if we are adding titles and there weren't any stored in meta (and this isn't the first
			// time creation of the experiment), let's unpause the experiment
			$this->core->optimizely()->update_experiment( $experiment['experiment_id'], array(
				'status' => 'Running',
			) );
		}//end elseif
		*/

		update_post_meta( $post_id, 'headline-envy', $experiment );
	}//end save_titles

	/**
	 * Initializes an experiment for a post
	 */
	public function initialize_experiment( $post_id, $type = 'title', $experiment ) {
		$options = $this->core->get_options();

		$experiment_args = array(
			'description' => apply_filters( 'headline_envy_experiment_description', "HeadlineEnvy [$post_id][$type]: " . get_the_title( $post_id ), $post_id ),
			'edit_url' => get_permalink( $post_id ),
			'url_conditions' => array(
				(object) array(
					'match_type' => 'substring',
					'value' => home_url(),
				),
			),
			'status' => 'Running',
			'activation_mode' => 'manual',
		);

		$shell_experiment = NULL;

		// find the shell experiment if there is one and use that as a template
		if ( ! empty( $options['optimizely_shell_experiment_id'] ) ) {
			$shell_experiment = $this->core->optimizely()->get_experiment( $options['optimizely_shell_experiment_id'] );

			if ( $shell_experiment && ! is_wp_error( $shell_experiment ) ) {
				$experiment_args['activation_mode'] = $shell_experiment->activation_mode;
				$experiment_args['percentage_included'] = $shell_experiment->percentage_included;
				$experiment_args['url_conditions'] = $shell_experiment->url_conditions;
			}//end if
		}//end if

		$exp = $this->core->optimizely()->create_experiment( $this->core->optimizely_project_id, $experiment_args );
		$experiment['experiment_id'] = $exp->id;

		// create a custom pageview goal for this specific page URL
		$result = $this->core->optimizely()->create_goal( $this->core->optimizely_project_id, array(
			'title' => 'Views to page [' . absint( $post_id ) . ']',
			// pageview goal
			'goal_type' => 3,
			// substring match
			'url_match_types' => array( 4 ),
			'urls' => array( get_permalink( $post_id ) ),
			// don't make this available to add to other experiments
			'addable' => FALSE,
			'experiment_ids' => array( $experiment['experiment_id'] ),
		) );

		// if there is a shell experiment, copy its goals
		if ( $shell_experiment && ! is_wp_error( $shell_experiment ) ) {
			// make sure the new experiment has any goals that the shell experiment has
			$goals = $this->core->optimizely()->get_goals( $this->core->optimizely_project_id );
			if ( $goals && ! is_wp_error( $goals ) ) {
				foreach ( $goals as $goal ) {
					if ( ! in_array( $shell_experiment->id, $goal->experiment_ids ) ) {
						continue;
					}//end if

					$this->core->optimizely()->add_goal( $experiment['experiment_id'], $goal->id );
				}//end foreach
			}//end if
		}//end if

		return $experiment;
	}//end initialize_experiment

	/**
	 * Given a list of titles, thie method adds those titles to the given experiment
	 *
	 * @param $experiment Array HeadlineEnvy experiment data
	 * @param $titles_to_add Array Collection of titles to add to the experiment
	 */
	public function add_titles_to_experiment( $experiment, $titles_to_add ) {
		foreach ( $titles_to_add as $title ) {
			$clean_title = json_encode( $title );
			$variation = $this->core->optimizely()->create_variation( $experiment['experiment_id'], array(
				'description' => $title,
				'js_component' =>  "$( 'headline-envy[data-experiment=\"{$experiment['experiment_id']}\"]' ).text( {$clean_title} );",
			) );

			$experiment['experiment_titles'][] = array(
				'variation' => $variation->id,
				'value' => $title,
			);
		}//end foreach

		return $experiment;
	}//end add_titles_to_experiment

	/**
	 * Given a list of titles, thie method adds those titles to the given experiment
	 *
	 * @param $experiment Array HeadlineEnvy experiment data
	 * @param $titles_to_delete Array Collection of titles to remove from the experiment
	 */
	public function remove_titles_from_experiment( $experiment, $titles_to_delete ) {
		// let's delete the titles we need to delete from Optimizely
		foreach ( $experiment['experiment_titles'] as $key => $title ) {
			if ( ! in_array( $title['value'], $titles_to_delete ) ) {
				continue;
			}//end if

			// @TODO: if Optimizely fixes their weirdness around deleting, this should become unnecessary
			$this->core->optimizely()->update_variation( $title['variation'], array(
				'weight' => 0,
				'is_paused' => TRUE,
			) );
			$this->core->optimizely()->delete_variation( $title['variation'] );
			unset( $experiment['experiment_titles'][ $key ] );
		}//end foreach

		return $experiment;
	}//end remove_titles_from_experiment

	/**
	 * Given a list of titles, this method rebalances the weights of variations within an experiment
	 *
	 * @param $experiment Array HeadlineEnvy experiment data
	 * @param $titles Array Collection of titles that exist within the experiment
	 */
	public function balance_variation_weights( $experiment, $titles ) {
		// rebalance weights
		$count = count( $titles ) + 1;
		$weight = floor( 10000 / $count );
		$extra = 10000 - ( $weight * $count );

		$variations = $this->core->optimizely()->get_variations( $experiment['experiment_id'] );
		foreach ( $variations as $variation ) {
			$new_weight = $weight;
			if ( 'Original' == $variation->description ) {
				$new_weight += $extra;
			}//end if

			if ( $variation->is_paused ) {
				continue;
			}//end if

			$this->core->optimizely()->update_variation( $variation->id, array( 'weight' => $new_weight ) );
		}// end foreach
	}//end balance_variation_weights
}//end class
