<script id="he-image-template" type="text/he-template">
	<div class="he-image">
		<img>
		<label>
			<span class="num"></span>
			<a class="dashicons dashicons-dismiss" href="#" title="<?php esc_attr_e( 'Remove alternate image', 'headline-envy' ); ?>"></a>
			<input type="radio" name="he-image-winner">
		</label>
		<input type="hidden">
		<span class="he-status"></span>
	</div>
</script>
<script id="he-image-ui" type="text/he-template">
	<div class="he-container he-image-ui<?php echo esc_attr( $image_container_class ); ?>">
		<div class="he-image he-image-original">
			<label>
				<input type="radio" name="he-image-winner" value="original">
			</label>
			<?php esc_html_e( 'Keep the original image', 'headline-envy' ); ?>
		</div>
		<button class="button add-image" type="button"><span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add alternate image', 'headline-envy' ); ?></button>
		<button class="button select-image" type="button"><span class="dashicons dashicons-awards"></span> <?php esc_html_e( 'Pick winner', 'headline-envy' ); ?></button>
		<button class="button cancel-select-image" type="button"><?php esc_html_e( 'Cancel', 'headline-envy' ); ?></button>
		<a href="https://www.optimizely.com/results?experiment_id=<?php echo absint( $image_experiment_id ); ?>" class="view-experiment" target="_blank"><?php esc_html_e( 'View image experiment details', 'headline-envy' ); ?></a>
	</div>
</script>