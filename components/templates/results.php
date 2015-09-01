<div id="headline-envy-results" class="wrap">
	<h2><?php esc_html_e( 'HeadlineEnvy Optimizely Results', 'headline-envy' ); ?></h2>
	<div class="result-container">
		<?php
		if ( ! $experiments ) {
			?>
			<p>Couldn't find any results to display! If you believe this is in error, please reload the page to try again.</p>
			<?php
		} else {
			foreach ( $experiments as $data ) {
				$title = $data['experiment']->description;
				if ( ! preg_match( '/HeadlineEnvy \[(\d+)\]: (.*)/', $title, $matches ) ) {
					continue;
				}
				$post_id        = (int) $matches[1];
				$title          = sanitize_text_field( $matches[2] );
				$post_edit_link = get_edit_post_link( $post_id, 'raw' );
				?>
					<div id="experiment-<?php echo esc_attr( $data['experiment']->id ); ?>" data-exp-id="<?php echo esc_attr( $data['experiment']->id ); ?>" class="headline-results" data-exp-title="<?php echo esc_attr( $data['experiment']->description ); ?>">
						<header>
							<span class="experiment-options">
								<?php esc_html_e( 'Goals', 'headline-envy' ); ?>:
								<select>
									<?php
									$goals = array_keys( $data['results'] );
									foreach ( $goals as $goal ) {
										?>
										<option value="<?php echo esc_attr( $goal ); ?>"><?php echo esc_html( $goal ); ?></option>
										<?php
									}//end foreach
									?>
								</select>
								<a class="button" href="<?php echo esc_url( 'https://www.optimizely.com/results?experiment_id=' . absint( $data['experiment']->id ) ); ?>" target="_blank" title="<?php esc_attr_e( 'View full results', 'headline-envy' ); ?>"><span class="dashicons dashicons-chart-bar"></span></a>
								<?php
								if ( $post_edit_link ) {
									?>
									<a href="<?php echo esc_url( $post_edit_link ); ?>" class="button" title="<?php esc_html_e( 'Edit', 'headline-envy' ); ?>">
										<span class="dashicons dashicons-edit"></span>
									</a>
								<?php } ?>
							</span>
							<?php if ( $post_edit_link ) { ?>
								<span class="experiment-title">
									<a href="<?php echo esc_url( $post_edit_link ); ?>"><?php echo esc_html( $title ); ?></a>
								</span>
							<?php } ?>
						</header>
						<div class="experiment-results">
							<?php
							foreach ( $data['results'] as $goal => $goal_data ) {
								?>
								<table class="wp-list-table widefat" data-goal="<?php echo esc_attr( $goal ); ?>">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Variation', 'headline-envy' ); ?></th>
											<th><?php esc_html_e( 'Visitors', 'headline-envy' ); ?></th>
											<th><?php esc_html_e( 'Unique conversions', 'headline-envy' ); ?></th>
											<th><?php esc_html_e( 'Conversion rate', 'headline-envy' ); ?></th>
											<th><?php esc_html_e( 'Improvement', 'headline-envy' ); ?></th>
											<th><?php esc_html_e( 'Statistical significance', 'headline-envy' ); ?></th>
											<th class="status"><?php esc_html_e( 'Status', 'headline-envy' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ( $goal_data as $variation ) {
											$improvement  = 'baseline' == $variation->status ? 'baseline' : round( $variation->improvement * 100, 1) . '%';
											$significance = 'baseline' == $variation->status ? '-' : round( $variation->confidence * 100, 1 ) . '%';
											?>
											<tr>
												<td><a href="<?php echo esc_url( $data['experiment']->edit_url . '?optimizely_x' . $data['experiment']->id . '=' . $variation->variation_id ); ?>"><?php echo esc_html( $variation->variation_name ); ?></td>
												<td><?php echo number_format( absint( $variation->visitors ) ); ?></td>
												<td><?php echo number_format( absint( $variation->conversions ) ); ?></td>
												<td><?php echo esc_html( round( $variation->conversion_rate * 100, 1 ) ) . '%'; ?></td>
												<td><?php echo esc_html( $improvement ); ?></td>
												<td><?php echo esc_html( $significance ); ?></td>
												<td class="status">
													<?php
													switch ( $variation->status ) {
														case 'winner':
															$icon = 'awards';
															$icon_info = esc_html__( 'Winning title', 'headline-envy' );
															break;
														case 'loser':
															$icon = 'no';
															$icon_info = esc_html__( 'Losing title', 'headline-envy' );
															break;
														case 'baseline':
															$icon = 'minus';
															$icon_info = esc_html__( 'Baseline', 'headline-envy' );
															break;
														case 'inconclusive':
														default:
															$icon = 'clock';
															$icon_info = esc_html__( 'Inconclusive results. More time is needed.', 'headline-envy' );
															break;
													}//end switch
													?>
													<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" title="<?php echo esc_attr( $icon_info ); ?>"></span>
												</td>
											</tr>
											<?php
										}//end foreach
										?>
									</tbody>
								</table>
								<?php
							}//end foreach
							?>
						</div>
					</div>
				<?php
			}//end foreach
		}//end else
		?>
	</div>
</div>
<?php
include __DIR__ . '/credits.php';
