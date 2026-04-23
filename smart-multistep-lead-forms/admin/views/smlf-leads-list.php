<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Leads', 'smart-multistep-lead-forms' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=smlf_export_leads_csv' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export to CSV', 'smart-multistep-lead-forms' ); ?></a>
	<hr class="wp-header-end">

	<form method="get">
		<input type="hidden" name="page" value="smlf-leads" />
		<div class="tablenav top">
			<div class="alignleft actions">
				<select name="form_id">
					<option value=""><?php esc_html_e( 'All Forms', 'smart-multistep-lead-forms' ); ?></option>
					<?php
					global $wpdb;
					$forms = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}smlf_forms ORDER BY id DESC" );
					$selected_form = isset($_GET['form_id']) ? intval($_GET['form_id']) : '';
					foreach ($forms as $f) {
						echo '<option value="' . esc_attr($f->id) . '" ' . selected($selected_form, $f->id, false) . '>' . esc_html($f->title) . '</option>';
					}
					?>
				</select>
				<select name="status">
					<option value=""><?php esc_html_e( 'All Statuses', 'smart-multistep-lead-forms' ); ?></option>
					<?php $selected_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : ''; ?>
					<option value="completed" <?php selected($selected_status, 'completed'); ?>><?php esc_html_e( 'Completed', 'smart-multistep-lead-forms' ); ?></option>
					<option value="partial lead" <?php selected($selected_status, 'partial lead'); ?>><?php esc_html_e( 'Partial Lead', 'smart-multistep-lead-forms' ); ?></option>
					<option value="started" <?php selected($selected_status, 'started'); ?>><?php esc_html_e( 'Started', 'smart-multistep-lead-forms' ); ?></option>
				</select>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'smart-multistep-lead-forms' ); ?>">
			</div>
		</div>
	</form>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Form ID', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Status', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Email / Phone', 'smart-multistep-lead-forms' ); ?></th>
				<th><?php esc_html_e( 'Date', 'smart-multistep-lead-forms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$table_name = $wpdb->prefix . 'smlf_leads';

			// Update last viewed lead ID
			$max_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name");
			if ($max_id) {
				update_option('smlf_last_viewed_lead_id', $max_id);
			}

			$where = "WHERE 1=1";
			if ( !empty($selected_form) ) {
				$where .= $wpdb->prepare(" AND form_id = %d", $selected_form);
			}
			if ( !empty($selected_status) ) {
				$where .= $wpdb->prepare(" AND status = %s", $selected_status);
			}

			$leads = $wpdb->get_results( "SELECT * FROM $table_name $where ORDER BY id DESC LIMIT 50" );

			if ( ! empty( $leads ) ) {
				foreach ( $leads as $lead ) {
					?>
					<tr>
						<td><?php echo esc_html( $lead->id ); ?></td>
						<td><?php echo esc_html( $lead->form_id ); ?></td>
						<td>
							<?php
								$status_class = ( $lead->status === 'completed' ) ? 'updated' : 'error';
								echo '<span class="smlf-badge ' . esc_attr( $status_class ) . '">' . esc_html( $lead->status ) . '</span>';
							?>
						</td>
						<td><?php echo esc_html( $lead->email . ' / ' . $lead->phone ); ?></td>
						<td><?php echo esc_html( $lead->created_at ); ?></td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No leads found.', 'smart-multistep-lead-forms' ); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
