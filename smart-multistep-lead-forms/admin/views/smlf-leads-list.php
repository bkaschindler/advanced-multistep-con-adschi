<?php
global $wpdb;

$forms_table = $wpdb->prefix . 'smlf_forms';
$leads_table = $wpdb->prefix . 'smlf_leads';
$forms       = $wpdb->get_results( "SELECT id, title FROM {$forms_table} ORDER BY id DESC" );
$today_start = gmdate( 'Y-m-d 00:00:00', current_time( 'timestamp' ) );

$total_leads          = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$leads_table}" );
$completed_leads      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$leads_table} WHERE status = %s", 'completed' ) );
$auto_saved_leads     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$leads_table} WHERE status IN (%s, %s)", 'partial', 'started' ) );
$today_leads          = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$leads_table} WHERE created_at >= %s", $today_start ) );
$completion_rate      = $total_leads > 0 ? round( ( $completed_leads / $total_leads ) * 100 ) : 0;
$auto_saved_rate      = $total_leads > 0 ? round( ( $auto_saved_leads / $total_leads ) * 100 ) : 0;

$selected_form        = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0;
$selected_status      = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$selected_lead_status = isset( $_GET['lead_status'] ) ? sanitize_key( wp_unslash( $_GET['lead_status'] ) ) : '';
$search               = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$orderby              = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id';
$order                = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
$order                = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
$selected_lead_id     = isset( $_GET['lead_id'] ) ? absint( wp_unslash( $_GET['lead_id'] ) ) : 0;
$date_from            = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
$date_to              = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

if ( ! function_exists( 'smlf_get_lead_statuses' ) ) {
	function smlf_get_lead_statuses() {
		$raw      = get_option( 'smlf_lead_statuses', "new:New\ncontacted:Contacted\nqualified:Qualified\nwon:Won\nlost:Lost" );
		$lines    = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$statuses = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( ':', $line, 2 ) );
			$key   = sanitize_key( $parts[0] );
			$label = isset( $parts[1] ) && '' !== $parts[1] ? sanitize_text_field( $parts[1] ) : sanitize_text_field( $parts[0] );
			if ( '' !== $key && '' !== $label ) {
				$statuses[ $key ] = $label;
			}
		}

		return ! empty( $statuses ) ? $statuses : array(
			'new'       => __( 'New', 'smart-multistep-lead-forms' ),
			'contacted' => __( 'Contacted', 'smart-multistep-lead-forms' ),
			'qualified' => __( 'Qualified', 'smart-multistep-lead-forms' ),
			'won'       => __( 'Won', 'smart-multistep-lead-forms' ),
			'lost'      => __( 'Lost', 'smart-multistep-lead-forms' ),
		);
	}
}

$lead_statuses = smlf_get_lead_statuses();

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

if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
	$where[]  = 'l.created_at >= %s';
	$params[] = $date_from . ' 00:00:00';
}

if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
	$where[]  = 'l.created_at <= %s';
	$params[] = $date_to . ' 23:59:59';
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

$detail_lead       = null;
$detail_email_logs = array();
if ( $selected_lead_id ) {
	$detail_lead = $wpdb->get_row( $wpdb->prepare(
		"SELECT l.*, f.title AS form_title FROM {$leads_table} l LEFT JOIN {$forms_table} f ON f.id = l.form_id WHERE l.id = %d",
		$selected_lead_id
	) );

	if ( $detail_lead ) {
		$email_logs_table   = $wpdb->prefix . 'smlf_email_logs';
		$detail_email_logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$email_logs_table} WHERE lead_id = %d ORDER BY sent_at DESC", $selected_lead_id ) );
	}
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Prospects', 'smart-multistep-lead-forms' ); ?></h1>
	<hr class="wp-header-end">

	<div class="smlf-admin-stats-grid" aria-label="<?php esc_attr_e( 'Prospects overview', 'smart-multistep-lead-forms' ); ?>">
		<div class="smlf-admin-stat-card smlf-admin-stat-card-leads">
			<span><?php esc_html_e( 'Total Requests', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $total_leads ) ); ?></strong>
			<small><?php esc_html_e( 'All captured records', 'smart-multistep-lead-forms' ); ?></small>
		</div>
		<div class="smlf-admin-stat-card smlf-admin-stat-card-completed">
			<span><?php esc_html_e( 'Completed Requests', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $completed_leads ) ); ?></strong>
			<small><?php echo esc_html( sprintf( __( '%s%% completion rate', 'smart-multistep-lead-forms' ), number_format_i18n( $completion_rate ) ) ); ?></small>
		</div>
		<div class="smlf-admin-stat-card smlf-admin-stat-card-partial">
			<span><?php esc_html_e( 'Auto-saved Requests', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $auto_saved_leads ) ); ?></strong>
			<small><?php echo esc_html( sprintf( __( '%s%% still in progress', 'smart-multistep-lead-forms' ), number_format_i18n( $auto_saved_rate ) ) ); ?></small>
		</div>
		<div class="smlf-admin-stat-card smlf-admin-stat-card-today">
			<span><?php esc_html_e( 'New Today', 'smart-multistep-lead-forms' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $today_leads ) ); ?></strong>
			<small><?php esc_html_e( 'Requests created today', 'smart-multistep-lead-forms' ); ?></small>
		</div>
	</div>

	<?php if ( $selected_lead_id ) : ?>
		<?php if ( $detail_lead ) : ?>
			<?php
			$detail_data           = json_decode( $detail_lead->lead_data, true );
			$detail_data           = is_array( $detail_data ) ? $detail_data : array();
			$detail_current_status = isset( $detail_lead->lead_status ) && $detail_lead->lead_status ? $detail_lead->lead_status : 'new';
			?>
			<div class="smlf-lead-detail-page">
				<div class="smlf-lead-detail-header">
					<div>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=smlf-leads' ) ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Prospects', 'smart-multistep-lead-forms' ); ?></a>
						<h2><?php echo esc_html( sprintf( __( 'Prospect #%d', 'smart-multistep-lead-forms' ), $detail_lead->id ) ); ?></h2>
						<p><?php echo esc_html( $detail_lead->form_title ? $detail_lead->form_title : sprintf( __( 'Form #%d', 'smart-multistep-lead-forms' ), $detail_lead->form_id ) ); ?></p>
					</div>
					<div>
						<select class="smlf-lead-status-select" data-lead-id="<?php echo esc_attr( $detail_lead->id ); ?>" data-previous="<?php echo esc_attr( $detail_current_status ); ?>">
							<?php foreach ( $lead_statuses as $status_key => $status_label ) : ?>
								<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $detail_current_status, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="smlf-lead-detail-grid">
					<section class="smlf-lead-detail-card">
						<h3><?php esc_html_e( 'Request Details', 'smart-multistep-lead-forms' ); ?></h3>
						<dl class="smlf-lead-detail-list">
							<?php foreach ( $detail_data as $key => $value ) : ?>
								<dt><?php echo esc_html( $key ); ?></dt>
								<dd><?php echo wp_kses_post( smlf_render_lead_value( $key, $value ) ); ?></dd>
							<?php endforeach; ?>
						</dl>
					</section>

					<section class="smlf-lead-detail-card">
						<h3><?php esc_html_e( 'Source and Contact', 'smart-multistep-lead-forms' ); ?></h3>
						<dl class="smlf-lead-detail-list">
							<dt><?php esc_html_e( 'Email', 'smart-multistep-lead-forms' ); ?></dt>
							<dd><?php echo esc_html( $detail_lead->email ? $detail_lead->email : '-' ); ?></dd>
							<dt><?php esc_html_e( 'Phone', 'smart-multistep-lead-forms' ); ?></dt>
							<dd><?php echo esc_html( $detail_lead->phone ? $detail_lead->phone : '-' ); ?></dd>
							<dt><?php esc_html_e( 'Source page', 'smart-multistep-lead-forms' ); ?></dt>
							<dd>
								<?php if ( $detail_lead->referrer ) : ?>
									<a href="<?php echo esc_url( $detail_lead->referrer ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $detail_lead->referrer ); ?></a>
								<?php else : ?>
									<?php esc_html_e( 'Unknown source', 'smart-multistep-lead-forms' ); ?>
								<?php endif; ?>
							</dd>
							<dt><?php esc_html_e( 'IP address', 'smart-multistep-lead-forms' ); ?></dt>
							<dd><?php echo esc_html( $detail_lead->ip_address ? $detail_lead->ip_address : '-' ); ?></dd>
							<dt><?php esc_html_e( 'Created at', 'smart-multistep-lead-forms' ); ?></dt>
							<dd><?php echo esc_html( $detail_lead->created_at ); ?></dd>
							<dt><?php esc_html_e( 'Completed at', 'smart-multistep-lead-forms' ); ?></dt>
							<dd><?php echo esc_html( $detail_lead->completed_at ? $detail_lead->completed_at : '-' ); ?></dd>
						</dl>
					</section>

					<section class="smlf-lead-detail-card">
						<h3><?php esc_html_e( 'Internal Notes', 'smart-multistep-lead-forms' ); ?></h3>
						<textarea class="large-text smlf-lead-notes" rows="8" data-lead-id="<?php echo esc_attr( $detail_lead->id ); ?>"><?php echo esc_textarea( $detail_lead->admin_notes ); ?></textarea>
						<p><button type="button" class="button button-primary smlf-save-lead-notes"><?php esc_html_e( 'Save Notes', 'smart-multistep-lead-forms' ); ?></button></p>
					</section>

					<section class="smlf-lead-detail-card">
						<h3><?php esc_html_e( 'Email Logs', 'smart-multistep-lead-forms' ); ?></h3>
						<?php if ( $detail_email_logs ) : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Recipient', 'smart-multistep-lead-forms' ); ?></th>
										<th><?php esc_html_e( 'Subject', 'smart-multistep-lead-forms' ); ?></th>
										<th><?php esc_html_e( 'Status', 'smart-multistep-lead-forms' ); ?></th>
										<th><?php esc_html_e( 'Date', 'smart-multistep-lead-forms' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $detail_email_logs as $log ) : ?>
										<tr>
											<td><?php echo esc_html( $log->recipient_email ); ?></td>
											<td><?php echo esc_html( $log->subject ); ?></td>
											<td><?php echo esc_html( $log->status ); ?></td>
											<td><?php echo esc_html( $log->sent_at ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p><?php esc_html_e( 'No email logs found.', 'smart-multistep-lead-forms' ); ?></p>
						<?php endif; ?>
					</section>
				</div>
			</div>
		<?php else : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Lead not found.', 'smart-multistep-lead-forms' ); ?></p></div>
		<?php endif; ?>
	<?php endif; ?>

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
		<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" aria-label="<?php esc_attr_e( 'Date from', 'smart-multistep-lead-forms' ); ?>">
		<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" aria-label="<?php esc_attr_e( 'Date to', 'smart-multistep-lead-forms' ); ?>">
		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'smart-multistep-lead-forms' ); ?>">
	</form>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="smlf-export-panel">
		<input type="hidden" name="action" value="smlf_export_leads_csv">
		<?php wp_nonce_field( 'smlf_export_leads_csv' ); ?>
		<input type="hidden" name="form_id" value="<?php echo esc_attr( $selected_form ); ?>">
		<input type="hidden" name="status" value="<?php echo esc_attr( $selected_status ); ?>">
		<input type="hidden" name="lead_status" value="<?php echo esc_attr( $selected_lead_status ); ?>">
		<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
		<input type="hidden" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
		<input type="hidden" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
		<strong><?php esc_html_e( 'Export columns', 'smart-multistep-lead-forms' ); ?></strong>
		<?php
		$export_columns = array(
			'id'          => __( 'ID', 'smart-multistep-lead-forms' ),
			'form'        => __( 'Form', 'smart-multistep-lead-forms' ),
			'status'      => __( 'Submission', 'smart-multistep-lead-forms' ),
			'lead_status' => __( 'Lead Status', 'smart-multistep-lead-forms' ),
			'contact'     => __( 'Contact', 'smart-multistep-lead-forms' ),
			'source'      => __( 'Source page', 'smart-multistep-lead-forms' ),
			'data'        => __( 'Request', 'smart-multistep-lead-forms' ),
			'notes'       => __( 'Internal Notes', 'smart-multistep-lead-forms' ),
			'date'        => __( 'Date', 'smart-multistep-lead-forms' ),
		);
		?>
		<?php foreach ( $export_columns as $column_key => $column_label ) : ?>
			<label>
				<input type="checkbox" name="columns[]" value="<?php echo esc_attr( $column_key ); ?>" checked>
				<?php echo esc_html( $column_label ); ?>
			</label>
		<?php endforeach; ?>
		<button type="submit" class="button"><?php esc_html_e( 'Export filtered CSV', 'smart-multistep-lead-forms' ); ?></button>
	</form>

	<div class="smlf-leads-bulk-actions">
		<button type="button" class="button smlf-delete-selected-leads"><?php esc_html_e( 'Delete selected', 'smart-multistep-lead-forms' ); ?></button>
		<button type="button" class="button button-link-delete smlf-delete-all-leads"><?php esc_html_e( 'Delete all requests', 'smart-multistep-lead-forms' ); ?></button>
		<span class="smlf-selected-leads-count" aria-live="polite"></span>
	</div>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<td class="manage-column column-cb check-column">
					<input type="checkbox" class="smlf-select-all-leads" aria-label="<?php esc_attr_e( 'Select all requests', 'smart-multistep-lead-forms' ); ?>">
				</td>
				<th><?php esc_html_e( 'ID', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Form / Source', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Submission', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Lead Status', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Contact', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Request', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Date', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'smart-multistep-lead-forms' ); ?></th>
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
					<tr data-lead-id="<?php echo esc_attr( $lead->id ); ?>">
						<th scope="row" class="check-column">
							<input type="checkbox" class="smlf-lead-checkbox" value="<?php echo esc_attr( $lead->id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select request #%d', 'smart-multistep-lead-forms' ), $lead->id ) ); ?>">
						</th>
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
						<td>
							<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=smlf-leads&lead_id=' . absint( $lead->id ) ) ); ?>"><?php esc_html_e( 'View Details', 'smart-multistep-lead-forms' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="9"><?php esc_html_e( 'No leads found.', 'smart-multistep-lead-forms' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
