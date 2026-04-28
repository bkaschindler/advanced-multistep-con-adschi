<?php
global $wpdb;

$forms_table = $wpdb->prefix . 'smlf_forms';
$leads_table = $wpdb->prefix . 'smlf_leads';
$forms       = $wpdb->get_results( "SELECT id, title FROM {$forms_table} ORDER BY id DESC" );

$selected_form        = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0;
$selected_status      = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$selected_lead_status = isset( $_GET['lead_status'] ) ? sanitize_key( wp_unslash( $_GET['lead_status'] ) ) : '';
$search               = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$orderby              = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id';
$order                = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
$order                = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

$lead_statuses = array(
	'new'       => __( 'New', 'smart-multistep-lead-forms' ),
	'contacted' => __( 'Contacted', 'smart-multistep-lead-forms' ),
	'qualified' => __( 'Qualified', 'smart-multistep-lead-forms' ),
	'won'       => __( 'Won', 'smart-multistep-lead-forms' ),
	'lost'      => __( 'Lost', 'smart-multistep-lead-forms' ),
);

$order_columns = array(
	'id'          => 'l.id',
	'form'        => 'f.title',
	'status'      => 'l.status',
	'lead_status' => 'l.lead_status',
	'email'       => 'l.email',
	'date'        => 'l.created_at',
);
$order_sql = isset( $order_columns[ $orderby ] ) ? $order_columns[ $orderby ] : 'l.id';

$where  = array( '1=1' );
$params = array();

if ( $selected_form ) {
	$where[]  = 'l.form_id = %d';
	$params[] = $selected_form;
}

if ( in_array( $selected_status, array( 'started', 'partial', 'completed' ), true ) ) {
	$where[]  = 'l.status = %s';
	$params[] = $selected_status;
}

if ( in_array( $selected_lead_status, array_keys( $lead_statuses ), true ) ) {
	$where[]  = 'l.lead_status = %s';
	$params[] = $selected_lead_status;
}

if ( '' !== $search ) {
	$like     = '%' . $wpdb->esc_like( $search ) . '%';
	$where[]  = '(l.email LIKE %s OR l.phone LIKE %s OR l.lead_data LIKE %s OR l.referrer LIKE %s OR f.title LIKE %s)';
	$params[] = $like;
	$params[] = $like;
	$params[] = $like;
	$params[] = $like;
	$params[] = $like;
}

$sql = "SELECT l.*, f.title AS form_title FROM {$leads_table} l LEFT JOIN {$forms_table} f ON f.id = l.form_id WHERE " . implode( ' AND ', $where ) . " ORDER BY {$order_sql} {$order} LIMIT 100";
if ( ! empty( $params ) ) {
	$sql = $wpdb->prepare( $sql, $params );
}
$leads = $wpdb->get_results( $sql );

$max_id = $wpdb->get_var( "SELECT MAX(id) FROM {$leads_table}" );
if ( $max_id ) {
	update_option( 'smlf_last_viewed_lead_id', $max_id );
}

if ( ! function_exists( 'smlf_render_lead_value' ) ) {
	function smlf_render_lead_value( $key, $value ) {
		if ( 'uploaded_files' === $key && is_array( $value ) ) {
			$links = array();
			foreach ( $value as $file ) {
				if ( empty( $file['url'] ) ) {
					continue;
				}
				$name    = isset( $file['name'] ) ? $file['name'] : basename( $file['url'] );
				$links[] = '<a class="button button-small" href="' . esc_url( $file['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $name ) . '</a>';
			}
			return '<div class="smlf-lead-files">' . implode( '', $links ) . '</div>';
		}

		return esc_html( is_scalar( $value ) ? $value : wp_json_encode( $value ) );
	}
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Prospects', 'smart-multistep-lead-forms' ); ?></h1>
	<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=smlf_export_leads_csv' ), 'smlf_export_leads_csv' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export to CSV', 'smart-multistep-lead-forms' ); ?></a>
	<hr class="wp-header-end">

	<form method="get" class="smlf-leads-toolbar">
		<input type="hidden" name="page" value="smlf-leads" />
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search leads', 'smart-multistep-lead-forms' ); ?>">
		<select name="form_id">
			<option value=""><?php esc_html_e( 'All Forms', 'smart-multistep-lead-forms' ); ?></option>
			<?php foreach ( $forms as $form ) : ?>
				<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $selected_form, $form->id ); ?>><?php echo esc_html( $form->title ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="status">
			<option value=""><?php esc_html_e( 'All Submission Types', 'smart-multistep-lead-forms' ); ?></option>
			<option value="completed" <?php selected( $selected_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'smart-multistep-lead-forms' ); ?></option>
			<option value="partial" <?php selected( $selected_status, 'partial' ); ?>><?php esc_html_e( 'Auto-saved', 'smart-multistep-lead-forms' ); ?></option>
			<option value="started" <?php selected( $selected_status, 'started' ); ?>><?php esc_html_e( 'Started', 'smart-multistep-lead-forms' ); ?></option>
		</select>
		<select name="lead_status">
			<option value=""><?php esc_html_e( 'All Lead Statuses', 'smart-multistep-lead-forms' ); ?></option>
			<?php foreach ( $lead_statuses as $status_key => $status_label ) : ?>
				<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $selected_lead_status, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="orderby">
			<option value="id" <?php selected( $orderby, 'id' ); ?>><?php esc_html_e( 'Sort by ID', 'smart-multistep-lead-forms' ); ?></option>
			<option value="date" <?php selected( $orderby, 'date' ); ?>><?php esc_html_e( 'Sort by date', 'smart-multistep-lead-forms' ); ?></option>
			<option value="form" <?php selected( $orderby, 'form' ); ?>><?php esc_html_e( 'Sort by form', 'smart-multistep-lead-forms' ); ?></option>
			<option value="lead_status" <?php selected( $orderby, 'lead_status' ); ?>><?php esc_html_e( 'Sort by lead status', 'smart-multistep-lead-forms' ); ?></option>
		</select>
		<select name="order">
			<option value="DESC" <?php selected( $order, 'DESC' ); ?>><?php esc_html_e( 'Descending', 'smart-multistep-lead-forms' ); ?></option>
			<option value="ASC" <?php selected( $order, 'ASC' ); ?>><?php esc_html_e( 'Ascending', 'smart-multistep-lead-forms' ); ?></option>
		</select>
		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'smart-multistep-lead-forms' ); ?>">
	</form>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Form / Source', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Submission', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Lead Status', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Contact', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Request', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Date', 'smart-multistep-lead-forms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $leads ) ) : ?>
				<?php foreach ( $leads as $lead ) : ?>
					<?php
					$lead_data           = json_decode( $lead->lead_data, true );
					$lead_data           = is_array( $lead_data ) ? $lead_data : array();
					$current_lead_status = isset( $lead->lead_status ) && $lead->lead_status ? $lead->lead_status : 'new';
					$is_completed        = 'completed' === $lead->status;
					$status_class        = $is_completed ? 'updated' : 'neutral';
					$status_label        = $is_completed ? __( 'Completed', 'smart-multistep-lead-forms' ) : __( 'Auto-saved', 'smart-multistep-lead-forms' );
					?>
					<tr>
						<td><?php echo esc_html( $lead->id ); ?></td>
						<td class="smlf-lead-source">
							<strong><?php echo esc_html( $lead->form_title ? $lead->form_title : sprintf( __( 'Form #%d', 'smart-multistep-lead-forms' ), $lead->form_id ) ); ?></strong><br>
							<?php if ( ! empty( $lead->referrer ) ) : ?>
								<a href="<?php echo esc_url( $lead->referrer ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $lead->referrer ); ?></a>
							<?php else : ?>
								<span><?php esc_html_e( 'Unknown source', 'smart-multistep-lead-forms' ); ?></span>
							<?php endif; ?>
						</td>
						<td><span class="smlf-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
						<td>
							<select class="smlf-lead-status-select" data-lead-id="<?php echo esc_attr( $lead->id ); ?>" data-previous="<?php echo esc_attr( $current_lead_status ); ?>">
								<?php foreach ( $lead_statuses as $status_key => $status_label_item ) : ?>
									<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $current_lead_status, $status_key ); ?>><?php echo esc_html( $status_label_item ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><?php echo esc_html( trim( $lead->email . ' / ' . $lead->phone, ' /' ) ); ?></td>
						<td class="smlf-lead-details">
							<?php foreach ( array_slice( $lead_data, 0, 3, true ) as $key => $value ) : ?>
								<?php if ( 'uploaded_files' === $key ) { continue; } ?>
								<strong><?php echo esc_html( $key ); ?>:</strong> <?php echo esc_html( is_scalar( $value ) ? $value : wp_json_encode( $value ) ); ?><br>
							<?php endforeach; ?>
							<details class="smlf-lead-detail-panel">
								<summary><?php esc_html_e( 'View full request', 'smart-multistep-lead-forms' ); ?></summary>
								<dl>
									<?php foreach ( $lead_data as $key => $value ) : ?>
										<dt><?php echo esc_html( $key ); ?></dt>
										<dd><?php echo wp_kses_post( smlf_render_lead_value( $key, $value ) ); ?></dd>
									<?php endforeach; ?>
								</dl>
							</details>
						</td>
						<td><?php echo esc_html( $lead->created_at ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="7"><?php esc_html_e( 'No leads found.', 'smart-multistep-lead-forms' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
