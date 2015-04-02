<?php wp_nonce_field( "{$this->core->slug}-save-post", "{$this->core->slug}-save-post-nonce" ); ?>
<div id="he-titles" class="he-container <?php echo esc_attr( $container_class ); ?>">
	<div class="saving">
		<p>
			Saving your Optimizely experiment...
		</p>
	</div>
	<div class="he-title he-title-original">
		<p><?php esc_html_e( 'Select the title that should be used for this post.', 'headline-envy' ); ?></p>
		<label>
			<input type="radio" name="he-winner" value="original">
		</label>
		<input type="text" autocomplete="off" value="<?php esc_attr_e( 'Keep the original title', 'headline-envy' ); ?>" disabled>
		<span class="he-status">-</span>
	</div>
	<button class="button add-title" type="button"><span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add alternate title', 'headline-envy' ); ?></button>
	<button class="button select-title" type="button"><span class="dashicons dashicons-awards"></span> <?php esc_html_e( 'Pick winner', 'headline-envy' ); ?></button>
	<button class="button cancel-select-title" type="button">Cancel</button>
	<a href="https://www.optimizely.com/results?experiment_id=<?php echo absint( $experiment_id ); ?>" class="view-experiment" target="_blank"><?php esc_html_e( 'View experiment details', 'headline-envy' ); ?></a>
</div>
<script id="he-title-template" type="text/he-template">
	<div class="he-title">
		<label>
			<span class="num"></span>
			<a class="dashicons dashicons-dismiss" href="#" title="<?php esc_attr_e( 'Remove headline', 'headline-envy' ); ?>"></a>
			<input type="radio" name="he-winner">
		</label>
		<input type="text" autocomplete="off">
		<span class="he-status"></span>
	</div>
</script>
