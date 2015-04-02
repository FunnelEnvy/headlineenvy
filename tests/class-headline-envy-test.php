<?php

/**
 * Headline_Envy unit tests
 */
class Headline_Envy_Test extends PHPUnit2_Framework_TestCase
{
	public $admin_user_id;

	/**
	 * this is run before each test* function in this class, to set
	 * up the environment each test runs in.
	 */
	public function setUp()
	{
		parent::setUp();

		$this->admin_user_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		wp_set_current_user( $this->admin_user_id );
	}//end setUp

	/**
	 * Tests that relevant hooks are attached
	 */
	public function test_hooks() {
		headline_envy()->admin();

		$this->assertEquals( 10, has_action( 'init', array( headline_envy(), 'init' ) ) );
		$this->assertEquals( 10, has_action( headline_envy()->cron, array( headline_envy(), 'winner_cron' ) ) );
		$this->assertEquals( 10, has_action( 'wp_kses_allowed_html', array( headline_envy(), 'wp_kses_allowed_html' ) ) );
		$this->assertEquals( 0, has_filter( 'the_title', array( headline_envy(), 'the_title' ) ) );

		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', array( headline_envy()->admin(), 'admin_enqueue_scripts' ) ) );
		$this->assertEquals( 10, has_action( 'admin_menu', array( headline_envy()->admin(), 'admin_menu' ) ) );
		$this->assertEquals( 10, has_action( 'save_post', array( headline_envy()->admin(), 'save_post' ) ) );
		$this->assertEquals( 10, has_action( 'edit_form_before_permalink', array( headline_envy()->admin(), 'edit_form_before_permalink' ) ) );
	}//end test_hooks

	/**
	 * test option saving and retrieval
	 */
	/*
	public function test_options() {
		headline_envy()->admin();

		$options = headline_envy()->get_options();
		$this->assertTrue( is_array( $options ), 'Options is an array' );

		$data = array(
			'headline-envy-save-settings-nonce' => wp_create_nonce( headline_envy()->slug . '-save-settings' ),
			'headline_envy_settings' => array(
				'funnelenvy_api_key' => 'abcd',
				'optimizely_api_key' => 'efgh',
				'optimizely_project_id' => 1234567,
				'auto_select_winner' => 1,
				'post_types' => array(
					'post' => 1,
				),
			),
		);

		// test post saving
		headline_envy()->admin()->update_settings( $data );
		$options = headline_envy()->get_options();

		fwrite( STDOUT, print_r( $options, TRUE ) );

		$this->assertEquals( 'abcd', $options['funnelenvy_api_key'] );
		$this->assertEquals( 'efgh', $options['optimizely_api_key'] );
		$this->assertEquals( 1234567, $options['optimizely_project_id'] );
		$this->assertEquals( 1, $options['auto_select_winner'] );
		$this->assertArrayHasKey( 'post', $options['post_types'] );
	}//end test_options
	 */
}//end class
