<div id="headline-envy-settings" class="wrap">
	<h2>HeadlineEnvy</h2>
	<h3><?php esc_html_e( 'About Optimizely', 'headline-envy' ); ?></h3>
	<p><?php esc_html_e( 'Simple, fast, and powerful.', 'headline-envy' ); ?> <a href="http://www.optimizely.com" target="_blank">Optimizely</a> <?php esc_html_e( 'is a dramatically easier way for you to improve your website through A/B testing. Create an experiment in minutes with absolutely no coding or engineering required. Convert your website visitors into customers and earn more revenue: create an account at', 'headline-envy' ) ?> <a href="http://www.optimizely.com" target="_blank">optimizely.com</a> <?php esc_html_e( 'and start A/B testing today!', 'headline-envy' ) ?></p>
	<form method="post" action="<?php echo esc_url( 'admin.php?page=' . headline_envy()->slug . '-settings' ); ?>">
		<table class="form-table">
			<tbody>
				<tr class="<?php echo esc_attr( $invalid_optimizely_api_key ? 'invalid-key' : '' ); ?>">
					<th scope="row"><?php esc_html_e( 'Optimizely API key', 'headline-envy' ); ?><br></th>
					<td>
						<?php
						$this->text_field( 'optimizely_api_key' );
						if ( $invalid_optimizely_api_key ) {
							?>
							<div class="invalid-key">
								<?php
								esc_html_e( 'The Optimizely API key that was entered is invalid.', 'headline-envy' ); ?> <a href="https://www.optimizely.com/tokens" target="_blank"><?php esc_html_e( 'Please verify the API key and try again.', 'headline-envy' ); ?></a>
							</div>
							<?php
						} else {
							?>
							<p>
								<em><a href="https://www.optimizely.com/tokens"><?php esc_html_e( 'Get your API key', 'headline-envy' ); ?></a></em>.
							</p>
							<?php
						}//end else
						?>
					</td>
				</tr>
				<?php
				if ( ! empty( $options['optimizely_api_key'] ) && ! $invalid_optimizely_api_key ) {
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Optimizely project ID', 'headline-envy' ); ?></th>
						<td>
							<?php
							$field = 'optimizely_project_id';

							if ( $this->core->optimizely() && $projects = $this->core->optimizely()->get_projects() ) {
								$projects = wp_list_pluck( $projects, 'project_name', 'id' );
								$selected_project_id = $this->core->get_options( $field );
								?>
								<select id="<?php echo esc_attr( $this->get_field_id( $field ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $field ) ); ?>">
									<option></option>
									<?php
									foreach ( $projects as $project_id => $project_name ) {
										?>
										<option value="<?php echo absint( $project_id );?>" <?php selected( $selected_project_id, $project_id ); ?>><?php echo esc_html( $project_name ); ?></option>
										<?php
									}// end foreach
									?>
								</select>
								<?php
							}//end if
							else {
								$this->text_field( $field );
							}//end else
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post types', 'headline-envy' ); ?></th>
						<td>
							<p><?php esc_html_e( 'Please choose the post types you would like to conduct A/B testing on.', 'headline-envy' ); ?></p>
							<?php
							$post_types = get_post_types( array(), 'objects' );
							foreach ( $post_types as $post_type ) {
								if (
									! $post_type->show_ui
									|| 'attachment' === $post_type->name
								) {
									continue;
								}//end if
								?>
								<label for="headline_envy_settings_post_types_<?php echo esc_attr( $post_type->name ); ?>">
									<input type="checkbox" name="headline_envy_settings[post_types][<?php echo esc_attr( $post_type->name ); ?>]" id="headline_envy_settings_post_types_<?php echo esc_attr( $post_type->name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( TRUE, in_array( $post_type->name, $options['post_types'] ) ); ?>>
									<?php echo esc_html( $post_type->labels->name ); ?>
								</label>
								<br>
								<?php
							}//end foreach
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-select winning headlines?', 'headline-envy' ); ?></th>
						<td>
							<?php $this->check_box( 'auto_select_winner' ); ?>
						</td>
					</tr>
					<?php
				}//end if

				if ( ! empty( $options['optimizely_shell_experiment_id'] ) ) {
					// generate the full URL for better escaping
					$experiment_url = add_query_arg( 'experiment_id', absint( $options['optimizely_shell_experiment_id'] ), 'https://www.optimizely.com/edit' );
					?>
					<tr>
						<td colspan="2">
							<a href="<?php echo esc_url( $experiment_url ) ?>"><?php esc_html_e( 'Experiment template', 'headline-envy' ); ?></a>
						</td>
					</tr>
					<?php
				}//end if
				?>
			</tbody>
		</table>
		<?php
		wp_nonce_field( "{$this->core->slug}-save-settings", "{$this->core->slug}-save-settings-nonce" );
		submit_button();
		?>
	</form>
</div>
<?php
include __DIR__ . '/credits.php';
