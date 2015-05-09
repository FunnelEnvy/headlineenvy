<script id="he-image-template" type="text/he-template">
	<div class="he-image">
		<a href="#set-alternate-image" title="<?php esc_attr_e( 'Set alternate image', 'headline-envy' ); ?>"></a>
		<label>
			<span class="num"></span>
			<a class="dashicons dashicons-dismiss" href="#" title="<?php esc_attr_e( 'Remove alternate image', 'headline-envy' ); ?>"></a>
		</label>
		<input type="text">
		<span class="he-status"></span>
	</div>
</script>
<script id="he-image-ui" type="text/he-template">
	<div class="he-container he-image-ui <?php echo esc_attr( $image_container_class ); ?>">
		<button class="button add-image" type="button"><span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add alternate image', 'headline-envy' ); ?></button>
		<div class="alternate-images"></div>
		<a href="https://www.optimizely.com/results?experiment_id=<?php echo absint( $image_experiment_id ); ?>" class="view-experiment" target="_blank"><?php esc_html_e( 'View image experiment details', 'headline-envy' ); ?></a>
	</div>
</script>