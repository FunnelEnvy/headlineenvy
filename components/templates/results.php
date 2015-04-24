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
				$title = esc_html( $data['experiment']->description );
				$title = preg_replace( '/HeadlineEnvy \[([0-9]+)\]: (.*)/', '<a href="' . admin_url( 'post.php?post=' ) . '$1&action=edit">$2</a>', $title );
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
								<a class="button" href="https://www.optimizely.com/results?experiment_id=<?php echo absint( $data['experiment']->id ); ?>" target="_blank" title="<?php esc_html_e( 'View full results', 'headline-envy' ); ?>"><span class="dashicons dashicons-chart-bar"></span></a>
								<?php
								$edit_button = preg_replace( '!(<a[^>]+)>([^<]+)</a>!', '$1 class="button" title="' . __( 'Edit', 'headline-envy' ) . '"><span class="dashicons dashicons-edit"></span></a>', $title );
								echo $edit_button;
								?>
							</span>
							<span class="experiment-title">
								<?php
								// escaped up above - will contain HTML
								echo $title;
								?>
							</span>
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
															$icon_info = __( 'Winning title', 'headline-envy' );
															break;
														case 'loser':
															$icon = 'no';
															$icon_info = __( 'Losing title', 'headline-envy' );
															break;
														case 'baseline':
															$icon = 'minus';
															$icon_info = __( 'Baseline', 'headline-envy' );
															break;
														case 'inconclusive':
														default:
															$icon = 'clock';
															$icon_info = __( 'Inconclusive results. More time is needed.', 'headline-envy' );
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
