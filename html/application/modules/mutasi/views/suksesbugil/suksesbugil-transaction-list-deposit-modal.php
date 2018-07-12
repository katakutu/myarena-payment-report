<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>
<div class="row">
	<div class="col-md-12">
		<div class="box">
			<div class="box-header">
				<h2 class="box-title">
					
				</h2>
			</div>
			<div class="box-body table-responsive no-padding">
				<?php
				if (isset($trans_data->seq)) {
					?>
					<table class="table table-hover">
						<thead>
							<tr>
								<th>Date</th>
								<th>From Account</th>
								<th>From Bank</th>
								<th>To Bank Account</th>
								<th>Amount</th>
								<th>Mutasi Data</th>
								<th>Status</th>
								<th>Insert</th>
								<th>Approved</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?=$trans_data->transaction_date;?></td>
								<td>
									<?php
									$transaction_sb_account = explode('Deposit', $trans_data->transaction_sb_account);
									if (isset($transaction_sb_account[0])) {
										echo $transaction_sb_account[0];
									} else {
										echo "-";
									}
									?>
								</td>
								<td>
									<ul class="list-unstyled">
										<li><?=$trans_data->transaction_from_acc_bank;?></li>
										<li><?=$trans_data->transaction_from_acc_rekening;?></li>
										<li><?=$trans_data->transaction_from_acc_name;?></li>
									</ul>
								</td>
								<td>
									<?php
									if (isset($trans_data->trans_details_bank_to)) {
										echo $trans_data->trans_details_bank_to;
									} else {
										echo "-";
									}
									?>
								</td>
								<td>
									<?php
									echo number_format($trans_data->transaction_amount);
									?>
								</td>
								<td>
									<?php
									if (isset($collect['mb_trans_data'])) {
										if (is_array($collect['mb_trans_data']) && (count($collect['mb_trans_data']) > 0)) {
											foreach ($collect['mb_trans_data'] as $mbVal) {
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
									switch (strtolower($trans_data->auto_approve_status)) {
										case 'approved':
											echo '<span class="btn btn-sm btn-primary" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-check"></i></span>';
										break;
										case 'deleted':
											echo '<span class="btn btn-sm btn-danger" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-trash"></i></span>';
										break;
										case 'canceled':
											echo '<span class="btn btn-sm btn-default" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-ban"></i></span>';
										break;
										case 'failed':
											echo '<span class="btn btn-sm btn-warning" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-exclamation-triangle"></i></span>';
										break;
										case 'already':
											echo '<span class="btn btn-sm btn-default" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-repeat"></i></span>';
										break;
										case 'waiting':
										default:
											echo '<span class="btn btn-sm btn-warning" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-clock-o"></i></span>';
										break;
									}
									?>
								</td>
								<td><?=$trans_data->transaction_datetime;?></td>
								<td>
									<?php
									if (strlen($trans_data->auto_approve_datetime_executed) > 0) {
										echo $trans_data->auto_approve_datetime_executed;
									} else {
										echo "-";
									}
									?>
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






<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<i class="close fa fa-times" title="" data-dismiss="modal" aria-hidden="true" data-original-title="Close"></i>
		</div>
		<div class="modal-body">
			
		</div>
			
		<div class="modal-footer">
			<div class="row">
				<div class="col-md-12 product-information">
					<div class="form-group text-center">
						<div class="col-md-12">
							<a id="btn-save-this-item" class="btn btn-info" href="javascript:;">
								<i class="fa fa-save"></i> Save
							</a>
							<a href="javascript:;" class="btn btn-danger" data-dismiss="modal">
								<i class="fa fa-ban"></i> Cancel
							</a>
						</div>
					</div>
				</div>
			</div>
			<button type="button" class="btn btn-default" data-dismiss="modal">(&times;) Close</button>
		</div>
	</div>
</div>