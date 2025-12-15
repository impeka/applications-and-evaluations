<?php

namespace Impeka\Applications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	private static ?Settings $instance = null;

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public static function get_instance() : self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register_menu() : void {
		add_options_page(
			__( 'A&E Settings', 'applications-and-evaluations' ),
			__( 'A&E Settings', 'applications-and-evaluations' ),
			'manage_options',
			'ae-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings() : void {
		register_setting(
			'ae_settings_group',
			'ae_settings',
			[
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
			]
		);

		add_settings_section(
			'ae_settings_email',
			__( 'Email Settings', 'applications-and-evaluations' ),
			function () {
				echo '<p>' . esc_html__( 'Configure the sender and admin copy options used by Applications & Evaluations emails.', 'applications-and-evaluations' ) . '</p>';
			},
			'ae-settings'
		);

		add_settings_field(
			'ae_sender_name',
			__( 'Sender Name', 'applications-and-evaluations' ),
			[ $this, 'render_sender_name_field' ],
			'ae-settings',
			'ae_settings_email'
		);

		add_settings_field(
			'ae_sender_email',
			__( 'Sender Email', 'applications-and-evaluations' ),
			[ $this, 'render_sender_email_field' ],
			'ae-settings',
			'ae_settings_email'
		);

		add_settings_field(
			'ae_disable_cc',
			__( 'Do Not CC Confirmation Email to Admin', 'applications-and-evaluations' ),
			[ $this, 'render_disable_cc_field' ],
			'ae-settings',
			'ae_settings_email'
		);
	}

	public function sanitize_settings( $input ) : array {
		$output = [
			'sender_name'  => isset( $input['sender_name'] ) ? sanitize_text_field( $input['sender_name'] ) : '',
			'sender_email' => isset( $input['sender_email'] ) ? sanitize_email( $input['sender_email'] ) : '',
			'disable_cc'   => isset( $input['disable_cc'] ) ? 1 : 0,
		];

		return $output;
	}

	public function render_sender_name_field() : void {
		$options = \ae_get_settings();
		?>
		<input type="text" class="regular-text" name="ae_settings[sender_name]" value="<?php echo esc_attr( $options['sender_name'] ?? '' ); ?>" />
		<p class="description"><?php esc_html_e( 'Name to use as the email sender.', 'applications-and-evaluations' ); ?></p>
		<?php
	}

	public function render_sender_email_field() : void {
		$options = \ae_get_settings();
		?>
		<input type="email" class="regular-text" name="ae_settings[sender_email]" value="<?php echo esc_attr( $options['sender_email'] ?? '' ); ?>" />
		<p class="description"><?php esc_html_e( 'Email address used in the From header.', 'applications-and-evaluations' ); ?></p>
		<?php
	}

	public function render_disable_cc_field() : void {
		$options = \ae_get_settings();
		$checked = ! empty( $options['disable_cc'] );
		?>
		<label>
			<input type="checkbox" name="ae_settings[disable_cc]" value="1" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Do not CC admin on confirmation emails.', 'applications-and-evaluations' ); ?>
		</label>
		<?php
	}

	public function render_settings_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'A&E Settings', 'applications-and-evaluations' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'ae_settings_group' );
				do_settings_sections( 'ae-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
