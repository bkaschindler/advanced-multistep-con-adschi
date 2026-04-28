<?php
global $wpdb;

$forms_table = $wpdb->prefix . 'smlf_forms';
$leads_table = $wpdb->prefix . 'smlf_leads';

$total_forms     = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$forms_table}" );
$published_forms = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$forms_table} WHERE status = %s", 'publish' ) );
$total_leads     = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$leads_table}" );
$completed_leads = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$leads_table} WHERE status = %s", 'completed' ) );
$partial_leads   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$leads_table} WHERE status IN (%s, %s)", 'partial', 'started' ) );
$conversion_rate = $total_leads > 0 ? round( ( $completed_leads / $total_leads ) * 100 ) : 0;
?>
<div class="wrap smlf-forms-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Forms', 'smart-multistep-lead-forms' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=smlf-add-form' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'smart-multistep-lead-forms' ); ?></a>
	<hr class="wp-header-end">

	<div class="smlf-admin-stats-grid" aria-label="<?php esc_attr_e( 'Forms overview', 'smart-multistep-lead-forms' ); ?>">
		<div class="smlf-admin-stat-card smlf-admin-stat-card-forms">
			<span><?php esc_html_e( 'Total Forms', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $total_forms ) ); ?></strong>
			<small><?php echo esc_html( sprintf( __( '%s published', 'smart-multistep-lead-forms' ), number_format_i18n( $published_forms ) ) ); ?></small>
		</div>
		<div class="smlf-admin-stat-card smlf-admin-stat-card-leads">
			<span><?php esc_html_e( 'Total Leads', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $total_leads ) ); ?></strong>
			<small><?php esc_html_e( 'All captured records', 'smart-multistep-lead-forms' ); ?></small>
		</div>
		<div class="smlf-admin-stat-card smlf-admin-stat-card-completed">
			<span><?php esc_html_e( 'Completed Leads', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $completed_leads ) ); ?></strong>
			<small><?php echo esc_html( sprintf( __( '%s%% completion rate', 'smart-multistep-lead-forms' ), number_format_i18n( $conversion_rate ) ) ); ?></small>
		</div>
		<div class="smlf-admin-stat-card smlf-admin-stat-card-partial">
			<span><?php esc_html_e( 'Partial Leads', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $partial_leads ) ); ?></strong>
			<small><?php esc_html_e( 'Saved before final submit', 'smart-multistep-lead-forms' ); ?></small>
		</div>
	</div>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Title', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Date', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'smart-multistep-lead-forms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$table_name = $wpdb->prefix . 'smlf_forms';
			$forms = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC LIMIT 20" );

			if ( ! empty( $forms ) ) {
				foreach ( $forms as $form ) {
					?>
					<tr>
						<td><?php echo esc_html( $form->id ); ?></td>
						<td>
							<strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=smlf-add-form&id=' . $form->id ) ); ?>"><?php echo esc_html( $form->title ); ?></a></strong>
						</td>
						<td><code>[smlf_form id="<?php echo esc_attr( $form->id ); ?>"]</code></td>
						<td><?php echo esc_html( $form->created_at ); ?></td>
						<td>
							<button type="button" class="button button-link-delete smlf-delete-form" data-form-id="<?php echo esc_attr( $form->id ); ?>">
								<?php esc_html_e( 'Delete', 'smart-multistep-lead-forms' ); ?>
							</button>
						</td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No forms found.', 'smart-multistep-lead-forms' ); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
