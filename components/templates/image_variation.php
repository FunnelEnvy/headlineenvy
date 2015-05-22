var <?php echo esc_attr( $object_name ); ?> = $( 'img[data-experiment="<?php echo esc_attr( $experiment_id ); ?>"][data-size="<?php echo esc_attr( $size ); ?>"]' );
<?php echo esc_attr( $object_name ); ?>.attr( 'src', <?php echo json_encode( $src_url ); ?> );
<?php echo esc_attr( $object_name ); ?>.attr( 'height', <?php echo absint( $img_height ); ?> );
<?php echo esc_attr( $object_name ); ?>.attr( 'width', <?php echo absint( $img_width ); ?> );
<?php echo esc_attr( $object_name ); ?>.attr( 'alt', <?php echo json_encode( esc_attr( $alt_text ) ); ?> );