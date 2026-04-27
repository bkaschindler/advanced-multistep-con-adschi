<?php
global $wpdb;

$form_id            = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$existing_form_data = array(
	'id'    => 0,
	'title' => __( 'New Form', 'smart-multistep-lead-forms' ),
	'steps' => array(),
);

if ( $form_id ) {
	$table_name = $wpdb->prefix . 'smlf_forms';
	$form       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $form_id ) );

	if ( $form ) {
		$decoded = json_decode( $form->form_data, true );
		$decoded = is_array( $decoded ) ? $decoded : array();

		$existing_form_data = array(
			'id'    => absint( $form->id ),
			'title' => sanitize_text_field( $form->title ),
			'steps' => isset( $decoded['steps'] ) && is_array( $decoded['steps'] ) ? $decoded['steps'] : array(),
		);
	}
}
?>
<div class="wrap smlf-builder-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Form Builder', 'smart-multistep-lead-forms' ); ?></h1>
	<hr class="wp-header-end">

	<div id="smlf-builder-app">
		<div class="smlf-builder-sidebar">
			<h3><?php esc_html_e( 'Blocks', 'smart-multistep-lead-forms' ); ?></h3>
			<ul class="smlf-draggable-blocks">
				<li data-type="text"><?php esc_html_e( 'Text Input', 'smart-multistep-lead-forms' ); ?></li>
				<li data-type="email"><?php esc_html_e( 'Email Input', 'smart-multistep-lead-forms' ); ?></li>
				<li data-type="phone"><?php esc_html_e( 'Phone Input', 'smart-multistep-lead-forms' ); ?></li>
				<li data-type="textarea"><?php esc_html_e( 'Long Text', 'smart-multistep-lead-forms' ); ?></li>
				<li data-type="file"><?php esc_html_e( 'File Upload', 'smart-multistep-lead-forms' ); ?></li>
				<li data-type="message"><?php esc_html_e( 'Message Text', 'smart-multistep-lead-forms' ); ?></li>
				<li data-type="cards"><?php esc_html_e( 'Clickable Cards', 'smart-multistep-lead-forms' ); ?></li>
				<li data-type="radio"><?php esc_html_e( 'Radio Buttons', 'smart-multistep-lead-forms' ); ?></li>
			</ul>
			<button class="button button-primary" id="smlf-add-step"><?php esc_html_e( '+ Add Step', 'smart-multistep-lead-forms' ); ?></button>
		</div>

		<div class="smlf-builder-canvas">
			<div class="smlf-form-settings">
				<input type="text" id="smlf-form-title" placeholder="<?php esc_attr_e( 'Form Title', 'smart-multistep-lead-forms' ); ?>" value="<?php echo esc_attr( $existing_form_data['title'] ); ?>" />
				<button class="button button-primary" id="smlf-save-form"><?php esc_html_e( 'Save Form', 'smart-multistep-lead-forms' ); ?></button>
			</div>

			<div id="smlf-steps-container">
				<!-- Steps will be rendered here via JS -->
			</div>
		</div>
	</div>
</div>
<script>
	window.smlf_existing_form_data = <?php echo wp_json_encode( $existing_form_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
</script>
