<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>
<div class="modal-dialog modal-lg" style="width:89%;">
	<div class="modal-content">
		<div class="modal-header">
			<i class="close fa fa-times" title="" data-dismiss="modal" aria-hidden="true" data-original-title="Close"></i>
		</div>
		<div class="modal-header">
			<?php
			if (isset($new_insert_tmp_data_seq)) {
				?>
				<form id="form-update-to-database" action="<?= base_url($base_path . '/mutasi/push-mutasi-data');?>" method="post">
					<div class="row">
						<div class="col-md-12 product-information">
							<div class="form-group text-center">
								<input type="hidden" class="input-group" id="push_to_database" name="push_to_database" value="on" />
								<input type="hidden" name="tmp_seq" value="<?=$new_insert_tmp_data_seq;?>" />
								<input type="hidden" name="account_seq" value="<?= (isset($collect['account_bank_data']->seq) ? $collect['account_bank_data']->seq : 0);?>" />
								<button id="btn-save-this-item" type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save to database</button>
							</div>
						</div>
					</div>
				</form>
				<?php
			}
			?>
		</div>
		
		<div class="modal-body">
			<div class="row">
				<div class="col-md-12 product-information">
					<div id="quick-shop-container">
						<div class="text-center">
							<h4 id="quick-shop-title" class="alert alert-info">
								Pull data from bank
							</h4>
							<div id="quick-shop-infomation">
								<?php
								if (isset($collect['account_bank_data']->rekening_data)) {
									if (is_array($collect['account_bank_data']->rekening_data) && (count($collect['account_bank_data']->rekening_data) > 0)) {
										foreach ($collect['account_bank_data']->rekening_data as $rekdata) {
											?>
											<div class="row">
												<div class="col-md-4">Rekening</div>
												<div class="col-md-8"><?=$rekdata->rekening_number;?></div>
											</div>
											<div class="row">
												<div class="col-md-4">Bank</div>
												<div class="col-md-8">
													<a href="<?= base_url($base_path . '/mutasi/listaccount/' . $collect['account_bank_data']->bank_code);?>">
														<?=$collect['account_bank_data']->bank_name;?>
													</a>
												</div>
											</div>
											<?php
										}
									}
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="box">
						<div class="box-header">
							<h2 class="box-title">
								<?=$collect['account_bank_data']->account_title;?>
							</h2>
							<p class="form-control">
								List of pulled data from bank periode 
								<span class="txt_red"><?= $collect['transaction_date']['starting']->format('Y-m-d');?></span>
								s/d
								<span class="txt_red"><?= $collect['transaction_date']['stopping']->format('Y-m-d');?></span>
							</p>
						</div>
						<div class="box-body table-responsive no-padding">
							<?php
							if (isset($collect['transactions_data']['items'])) {
								if(is_array($collect['transactions_data']['items'])) {
									if (count($collect['transactions_data']['items']) > 0) {
										?>
										<table class="table table-hover">
											<thead>
												<tr>
													<th>No.</th>
													<th>Pulled Date</th>
													<th>Trans Date</th>
													<th>Acc Name</th>
													<th>Type</th>
													<th>Status</th>
													<th>Amount</th>
													<th>Description</th>
													<th>Saldo</th>
												</tr>
											</thead>
											<tbody>
												<?php
												$dateObject = new DateTime();
												$for_i = 1;
												foreach($collect['transactions_data']['items'] as $val) {
													$query_params = array();
													// SET bank-transaction-date
													if (isset($val['transaction_detail'][1])) {
														$query_params['transaction_remark_date'] = 	$val['transaction_detail'][1];
														$query_params['transaction_remark_date'] = sprintf("%s", $query_params['transaction_remark_date']);
														if (strtoupper($val['transaction_code']) !== 'DB') {
															$date_finds = array(
																$dateObject->format('m'),
																$dateObject->format('d'),
															);
															$date_is_match = 0;
															foreach ($date_finds as $datefindval) {
																$datefindval = sprintf("%s", $datefindval);
																if (strpos($query_params['transaction_remark_date'], $datefindval) !== FALSE) {
																	$date_is_match += 1;
																}
															}
															if ($date_is_match === 2) {
																try {
																	$make_transaction_date = DateTime::createFromFormat('d/m/Y', sprintf("%s", "{$dateObject->format('d')}/{$dateObject->format('m')}/{$dateObject->format('Y')}"));
																} catch (Exception $ex) {
																	throw $ex;
																	$make_transaction_date = new DateTime(date('Y-m-d H:i:s'));
																}
															} else {
																$make_transaction_date = $dateObject;
															}
														} else {
															$make_transaction_date = $dateObject;
														}
														if ($make_transaction_date != FALSE) {
															$query_params['transaction_date'] = $make_transaction_date->format('Y-m-d');
														} else {
															$query_params['transaction_date'] = '-';
														}
													} else {
														$query_params['transaction_date'] = (isset($val['transaction_date_string']) ? $val['transaction_date_string'] : '-');
													}
													switch (count($val['transaction_detail'])) {
														case 4:
															$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
															$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][0]) ? $val['transaction_detail'][0] : '');
														break;
														case 5:
															$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
															$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
														break;
														case 6:
															if (strtoupper($val['transaction_code']) === 'DB') {
																$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
																$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
															} else {
																if (isset($val['transaction_detail'][4])) {
																	if (sprintf("%s", $val['transaction_detail'][4]) === '0000') {
																		$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
																		$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
																	} else {
																		$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
																		$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
																	}
																} else {
																	$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
																	$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
																}
															}
														break;
														case 7:
															$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][4]) ? $val['transaction_detail'][4] : '');
															$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
														break;
														case 8:
															$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][5]) ? $val['transaction_detail'][5] : '');
															$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][4]) ? $val['transaction_detail'][4] : '');
														break;
														default:
															$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
															$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
														break;
													}
													?>
													<tr>
														<td><?=$for_i;?></td>
														<td>
															<?php
															echo $query_params['transaction_date'];
															?>
														</td>
														<td>
															<?= (isset($val['transaction_date_string']) ? $val['transaction_date_string'] : '');?>
														</td>
														<td><?=$query_params['transaction_from_acc_name'];?></td>
														<td>
															<?php
															if (strtoupper($val['transaction_code']) === 'CR') {
																echo "<span class='text-sm text-success'><i class='fa fa-sign-in'></i></span>";
															} else {
																echo "<span class='text-sm text-danger'><i class='fa fa-sign-out'></i></span>";
															}
															?>
														</td>
														<td>
															<?=$val['transaction_type'];?>
														</td>
														<td><?= number_format($val['transaction_amount'], 2);?></td>
														<td><?=$val['transaction_description'];?></td>
														<td>
															<?php
															if (isset($val['actual_rekening_saldo'])) {
																echo number_format($val['actual_rekening_saldo'], 2);
															} else {
																echo "-";
															}
															?>
														</td>
														<!--
														<td>
															<form action="#<?= base_url($base_path . '/mutasi/force-insert');?>" method="post">
																<input type="hidden" name="force_insert_this" value="<?=$for_i;?>" />
																<button type="submit" class="btn btn-sm btn-info" title="Force insert this data"><i class="fa fa-save"></i></button>
															</form>
														</td>
														-->
													</tr>
													<?php
													$for_i++;
												}
												?>
											</tbody>
										</table>
										<?php
									} else {
										?>
										<div class="alert alert-danger alert-dismissable">
											<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
											There is no transaction data on this date range selected
										</div>
										<?php
									}
								}
							} else {
								?>
								<div class="alert alert-danger alert-dismissable">
									<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
									There is no transaction data on this date range selected with empty value
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
			
		<div class="modal-footer">
			<?php
			if (isset($new_insert_tmp_data_seq)) {
				?>
				<form id="form-update-to-database" action="<?= base_url($base_path . '/mutasi/push-mutasi-data');?>" method="post">
					<div class="row">
						<div class="col-md-12 product-information">
							<div class="form-group text-center">
								<input type="hidden" class="input-group" id="push_to_database" name="push_to_database" value="on" />
								<input type="hidden" name="tmp_seq" value="<?=$new_insert_tmp_data_seq;?>" />
								<input type="hidden" name="account_seq" value="<?= (isset($collect['account_bank_data']->seq) ? $collect['account_bank_data']->seq : 0);?>" />
								<button id="btn-save-this-item" type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save to database</button>
							</div>
						</div>
					</div>
				</form>
				<?php
			}
			?>
			<button type="button" class="btn btn-sm btn-danger" data-dismiss="modal">(&times;) Close</button>
		</div>
	</div>
</div>