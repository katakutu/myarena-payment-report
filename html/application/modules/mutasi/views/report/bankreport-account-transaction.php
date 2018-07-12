<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>
<!-- BEGIN CONTENT -->
<div class="page-content-wrapper">
	<div class="page-content">
		<!-- BEGIN FLASH MESSAGE-->
		<div class="row">
			<div class="col-md-12">
				<?php
				if ($this->session->flashdata('error')) {
					if ($this->session->flashdata('action_message')) {
						?>
						<div class="alert alert-danger alert-dismissable">
							<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
							<?=$this->session->flashdata('action_message');?>
						</div>
						<?php 
					}
				} else {
					if ($this->session->flashdata('action_message')) {
						?>
						<div class="alert alert-success alert-dismissable">
							<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
							<?=$this->session->flashdata('action_message');?>
						</div>
						<?php 
					}
				}
				?>
			</div>
		</div>
		<!-- END FLASH MESSAGE-->
		
		<!-- BEGIN PAGE HEADER-->
		<div class="row">
			<div class="col-md-12">
				<!-- BEGIN PAGE TITLE & BREADCRUMB-->
				<h3 class="page-title">
					<?= (isset($title) ? $title : '');?>	
				</h3>
				<ul class="page-breadcrumb breadcrumb">
					<li>
						<i class="fa fa-home"></i>
						<a href="<?= base_url($base_path . '/');?>">Home</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-bar-chart"></i>
						<a href="<?= base_url($base_path . '/report');?>">Report</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-university"></i>
						<a href="<?= base_url($base_path . '/report/viewmutasi/' . $collect['account_data']->bank_code);?>"><?=$collect['account_data']->bank_name;?></a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-list"></i> <?=$collect['account_data']->account_title;?>
					</li>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<!-- BEGIN FORM-->
			<form action="<?= base_url($base_path . '/report/viewmutasitransaction/' . $collect['account_data']->seq);?>" role="form" id="date-range-form" method="post">
				<div class="col-md-8 col-sm-12 col-xs-12">
					<div class="portlet box blue">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-filter"></i> 
								Filter
							</div>
						</div>
						<div class="portlet-body form">
							<ul class="menu nav form-group">
								<li class="row">
									<div class="col-md-6">
										<div class="input-group">
											<label for="transaction-date-month">Month</label>
											<select id="transaction-date-month" name="transaction_date[month]" class="form-control">
												<?php
												if (isset($collect['input_dates']['month'])) {
													if (is_array($collect['input_dates']['month']) && (count($collect['input_dates']['month']) > 0)) {
														foreach ($collect['input_dates']['month'] as $monthval) {
															if (isset($transaction_date['month'])) {
																if ($transaction_date['month'] == $monthval['code']) {
																	$month_is_selected = ' selected="selected"';
																} else {
																	$month_is_selected = '';
																}
															} else {
																$month_is_selected = '';
															}
															echo "<option value='{$monthval['code']}'{$month_is_selected}>{$monthval['name']}</option>";
														}
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="input-group">
											<label for="transaction-date-year">Year</label>
											<select id="transaction-date-year" name="transaction_date[year]" class="form-control">
												<?php
												if (isset($collect['input_dates']['year'])) {
													if (is_array($collect['input_dates']['year']) && (count($collect['input_dates']['year']) > 0)) {
														foreach ($collect['input_dates']['year'] as $yearval) {
															if (isset($transaction_date['year'])) {
																if ($transaction_date['year'] == $yearval) {
																	$year_is_selected = ' selected="selected"';
																} else {
																	$year_is_selected = '';
																}
															} else {
																$year_is_selected = '';
															}
															echo "<option value='{$yearval}'{$year_is_selected}>{$yearval}</option>";
														}
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-2 pull-right">
										<label for="submit-this-transaction-date"> &nbsp; </label>
										<span class="input-group-btn">
											<input class="btn btn-primary" type="submit" value="Submit" id="submit-this-transaction-date" />
										</span>
									</div>
								</li>
								<li class="row">
									<div class="col-md-12">
										&nbsp;
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-md-4 col-xs-12 pull-right">
					<div class="input-group">
						<input name="search_text" class="form-control" placeholder="Search...." type="text" id="search_text" value="<?= (isset($search_text)? base_safe_text($search_text, 64) : '');?>" />
						<span class="input-group-btn">
							<input class="btn btn-primary" type="submit" value="Search" />
						</span>
					</div>	
				</div>
			</form>
			<!-- END FORM-->
		</div>		
		
		
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12">
				<div class="box">
					<div class="box-header">
						<h2 class="box-title">
							<?= (isset($title) ? $title : '-');?>
						</h2>
					</div>
					<div class="box-body table-responsive no-padding">
						<?php
						$summary_of = array(
							'transfer'		=> array(
								'unit'				=> 0,
								'amount'			=> 0,
							),
							'deposit'		=> array(
								'unit'				=> 0,
								'amount'			=> 0,
							),
							'selisih'		=> 0,
						);
						?>
						<table class="table table-hover">
							<thead>
								<tr class="alert alert-info">
									<th rowspan="2">No.</th>
									<th rowspan="2">Date</th>
									<th colspan="2">Deposit</th>
									<th colspan="2">Transfer</th>
									<th rowspan="2">Selisih</th>
								</tr>
								<tr class="alert alert-info">
									<th>Unit</th>
									<th>Amount</th>
									<th>Unit</th>
									<th>Amount</th>
								</tr>
							</thead>
							<!--
							<tfoot>
								<tr>
									<th>No.</th>
									<th>Date</th>
									<th colspan="2">Deposit</th>
								</tr>
							</tfoot>
							-->
							<tbody>
								<?php
								if (isset($collect['transaction_data_by_date'])) {
									if (is_array($collect['transaction_data_by_date']) && (count($collect['transaction_data_by_date']) > 0)) {
										$for_i = 1;
										foreach($collect['transaction_data_by_date'] as $keval) {
											$summary_of['transfer']['unit'] += $keval['transfer']->count_unit;
											$summary_of['transfer']['amount'] += $keval['transfer']->sum_amount;
											$summary_of['deposit']['unit'] += $keval['deposit']->count_unit;
											$summary_of['deposit']['amount'] += $keval['deposit']->sum_amount;
											?>
											<tr>
												<td><?=$for_i;?></td>
												<td><?=$keval['date']; ?></td>
												<td><?= (((int)$keval['deposit']->count_unit > 0) ? number_format($keval['deposit']->count_unit) : '-');?></td>
												<td><?= ($keval['deposit']->sum_amount > 0) ? number_format($keval['deposit']->sum_amount, 2) : '-';?></td>
												<td><?= (((int)$keval['transfer']->count_unit > 0) ? number_format($keval['transfer']->count_unit) : '-');?></td>
												<td><?= ($keval['transfer']->sum_amount > 0) ? number_format($keval['transfer']->sum_amount, 2) : '-';?></td>
												<td>
													<?php
													$selisih_amount = ($keval['deposit']->sum_amount - $keval['transfer']->sum_amount);
													if ($selisih_amount <> 0) {
														echo number_format($selisih_amount, 2);
													} else {
														echo "-";
													}
													$summary_of['selisih'] += $selisih_amount;
													?>
												</td>
											</tr>
											<?php
											$for_i += 1;
										}
									}
								} else {
									?>
									<div class="alert alert-danger alert-dismissable">
										<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
										There is no data on this date range periode
									</div>
									<?php
								}
								?>
							</tbody>
							<tbody>
								<tr class="alert alert-default">
									<th colspan="2">Summaries</th>
									<th><?= number_format($summary_of['deposit']['unit']);?></th>
									<th><?= number_format($summary_of['deposit']['amount'], 2);?></th>
									<th><?= number_format($summary_of['transfer']['unit']);?></th>
									<th><?= number_format($summary_of['transfer']['amount'], 2);?></th>
									<th>
										<?= number_format($summary_of['selisih'], 2);?>
									</th>
								</tr>
							</tbody>
						</table>
					</div>
				</div>


			</div>
		</div>
	
		<!-- END MAIN CONTENT -->



		
		
		
		<div class="modal fade" id="quick-shop-modal"></div>
	</div>
</div>




<script src="<?= base_url('assets/plugins/datepick/jquery.plugin.js');?>" type="text/javascript"></script>
<link href="<?= base_url('assets/plugins/datepick/jquery.datepick.css');?>" rel="stylesheet" />
<script src="<?= base_url('assets/plugins/datepick/jquery.datepick.js');?>" type="text/javascript"></script>
<script type="text/javascript">
	$(function() {
		var datepickParams = {
			showSpeed: 'fast',
			dateFormat: 'yyyy-mm-dd',
			minDate: new Date(2017, 10 - 1, 01),
			maxDate: '0'
		};
		$('#transaction-date-starting').datepick(datepickParams);
		$('#transaction-date-stopping').datepick(datepickParams);
		//$('#inlineDatepicker').datepick({onSelect: showDate});
	});
</script>






