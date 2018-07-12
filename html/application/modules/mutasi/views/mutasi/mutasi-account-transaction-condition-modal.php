<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>

<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<i class="close fa fa-times" title="" data-dismiss="modal" aria-hidden="true" data-original-title="Close"></i>
		</div>
		<?php
		if (isset($error)) {
			?>
			<div class="modal-body">
				<?php
				if (isset($error['message'])) {
					if (is_array($error['message']) && (count($error['message']) > 0)) {
						foreach ($error['message'] as $message) {
							if (is_string($message) || is_numeric($message)) {
								?>
								<div class="alert alert-danger alert-dismissable">
									<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
									<?=$message;?>
								</div>
								<?php
							} else {
								// Nothing to do
								unset($message);
							}
						}
					}
				}
				?>
			</div>
			<?php
		} else {
			?>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12 product-information">
						<div id="quick-shop-container">
							<div class="text-center">
								<h4 id="quick-shop-title" class="alert alert-info">
									Deposit Information
								</h4>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<div class="box">
							<div class="box-body table-responsive no-padding">
								<?php
								if (isset($collect['sb_trans_data']->seq)) {
									?>
									<table class="table table-responsive">
										<thead>
											<tr>
												<th>Date</th>
												<th>From Account</th>
												<th>From Bank</th>
												<th>To Bank Account</th>
												<th>Amount</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td><?=$collect['sb_trans_data']->transaction_date;?></td>
												<td>
													<?php
													$transaction_sb_account = explode('Deposit', $collect['sb_trans_data']->transaction_sb_account);
													if (isset($transaction_sb_account[0])) {
														echo $transaction_sb_account[0];
													} else {
														echo "-";
													}
													?>
												</td>
												<td>
													<ul class="list-unstyled">
														<li><?=$collect['sb_trans_data']->transaction_from_acc_bank;?></li>
														<li><?=$collect['sb_trans_data']->transaction_from_acc_rekening;?></li>
														<li><?=$collect['sb_trans_data']->transaction_from_acc_name;?></li>
													</ul>
												</td>
												<td>
													<?php
													if (isset($collect['sb_trans_data']->trans_details_bank_to)) {
														echo $collect['sb_trans_data']->trans_details_bank_to;
													} else {
														echo "-";
													}
													?>
												</td>
												<td>
													<?php
													echo number_format($collect['sb_trans_data']->transaction_amount);
													?>
												</td>
											</tr>
										</tbody>
									</table>
									
									<table class="table table-responsive">
										<thead>
											<tr>
												<th>Mutasi Data</th>
												<th>Status</th>
												<th>Insert</th>
												<th>Approved</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>
													<?php
													if (isset($transaction_data)) {
														if (is_array($transaction_data) && (count($transaction_data) > 0)) {
															foreach ($transaction_data as $mbVal) {
																?>
																<div class="row">
																	<div class="col-md-12">
																		<?=$mbVal->transaction_description;?>
																	</div>
																</div>
																<?php
															}
														}
													} else {
														echo "-";
													}
													?>
												</td>
												<td>
													<?php
													switch (strtolower($collect['sb_trans_data']->auto_approve_status)) {
														case 'approved':
															echo '<span class="btn btn-sm btn-primary" title="' . $collect['sb_trans_data']->auto_approve_status . '"><i class="fa fa-check"></i></span>';
														break;
														case 'deleted':
															echo '<span class="btn btn-sm btn-default" title="' . $collect['sb_trans_data']->auto_approve_status . '"><i class="fa fa-trash"></i></span>';
														break;
														case 'canceled':
															echo '<span class="btn btn-sm btn-default" title="' . $collect['sb_trans_data']->auto_approve_status . '"><i class="fa fa-ban"></i></span>';
														break;
														case 'failed':
															echo '<span class="btn btn-sm btn-warning" title="' . $collect['sb_trans_data']->auto_approve_status . '"><i class="fa fa-exclamation-triangle"></i></span>';
														break;
														case 'already':
															echo '<span class="btn btn-sm btn-default" title="' . $collect['sb_trans_data']->auto_approve_status . '"><i class="fa fa-repeat"></i></span>';
														break;
														case 'waiting':
														default:
															echo '<span class="btn btn-sm btn-warning" title="' . $collect['sb_trans_data']->auto_approve_status . '"><i class="fa fa-clock-o"></i></span>';
														break;
													}
													?>
												</td>
												<td><?=$collect['sb_trans_data']->transaction_datetime;?></td>
												<td>
													<?php
													if (strlen($collect['sb_trans_data']->auto_approve_datetime_executed) > 0) {
														echo $collect['sb_trans_data']->auto_approve_datetime_executed;
													} else {
														echo "-";
													}
													?>
												</td>
											</tr>
										</tbody>
									</table>
									
									<table class="table table-responsive">
										<thead>
											<tr>
												<th>Action By</th>
												<th>Action Remark</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>
													<?php
													if (isset($collect['sb_trans_data']->auto_approve_log_by_account_email)) {
														echo $collect['sb_trans_data']->auto_approve_log_by_account_email;
													} else {
														echo "-";
													}
													?>
												</td>
												<td>
													
												</td>
											</tr>
										</tbody>
									</table>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">(&times;) Close</button>
		</div>
	</div>
</div>