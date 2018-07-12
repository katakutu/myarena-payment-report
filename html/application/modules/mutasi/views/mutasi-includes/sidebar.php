<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }

/*
echo "<pre>";
print_r($collect);
exit;
*/
?>


		<!-- BEGIN SIDEBAR -->
		<div class="page-sidebar-wrapper">
			<div class="page-sidebar navbar-collapse collapse">
			<!-- BEGIN SIDEBAR MENU -->
				<ul class="page-sidebar-menu">
					<li class="sidebar-toggler-wrapper">
						<!-- BEGIN SIDEBAR TOGGLER BUTTON -->
						<div class="sidebar-toggler hidden-phone"></div>
					</li>
					<li class="<?= set_sidebar_active('about', 'service', $collect['match']);?>">
						<a href="<?= base_url($base_dashboard_path . '/about');?>">
							<i class="fa fa-question-circle"></i>
							<span class="title">About</span>
							<span class="selected"></span>
						</a>
					</li>
					<li class="start <?= set_sidebar_active('index', 'service', $collect['match']);?>">
						<a href="<?= base_url($base_dashboard_path . '/index');?>">
							<i class="fa fa-home"></i>
							<span class="title">Home</span>
							<span class="selected"></span>
						</a>
					</li>
					<?php
					if (in_array($collect['userdata']['account_role'], base_config('admin_role'))) {
						?>
						<li class="<?= set_sidebar_active('users', 'service', $collect['match']);?>">
							<a href='<?= base_url($base_dashboard_path . '/users');?>'>
								<i class='fa fa-users'></i>
								<span class='title'>Users</span>
								<span class='selected'></span>
								<span class='arrow'></span>
							</a>
							<ul class='sub-menu'>
								<li class='<?= set_sidebar_active(array('service' => 'users', 'method' => 'lists'), 'method', $collect['match']);?>'>
									<a href='<?= base_url($base_dashboard_path . '/users/lists');?>'><i class='fa fa-user'></i> Lists</a>
								</li>
								<li class='<?= set_sidebar_active(array('service' => 'users', 'method' => 'add'), 'method', $collect['match']);?>'>
									<a href='<?= base_url($base_dashboard_path . '/users/add');?>'><i class='fa fa-plus'></i> Add</a>
								</li>
								<li class='<?= set_sidebar_active(array('service' => 'users', 'method' => 'edit'), 'method', $collect['match']);?>'>
									<a href='<?= base_url($base_dashboard_path . '/users/edit');?>'><i class='fa fa-pencil'></i> Edit</a>
								</li>
							</ul>
						</li>
						<?php
					}
					if (in_array($collect['userdata']['account_role'], base_config('editor_role'))) {
						?>
						<li class="<?= set_sidebar_active('mutasi', 'service', $collect['match']);?>">
							<a href='<?= base_url($base_path . '/mutasi');?>'>
								<i class='fa fa-book'></i>
								<span class='title'>Mutasi Bank</span>
								<span class='selected'></span>
								<span class='arrow'></span>
							</a>
							<ul class='sub-menu'>
								<li class='<?= set_sidebar_active(array('service' => 'mutasi', 'method' => 'listbank'), 'method', $collect['match']);?>'>
									<a href='<?= base_url($base_path . '/mutasi/listbank');?>'>
										<i class='fa fa-university'></i> Banks
									</a>
								</li>
								<li class='<?= set_sidebar_active(array('service' => 'mutasi', 'method' => 'listaccount'), 'method', $collect['match']);?>'>
									<a href='<?= base_url($base_path . '/mutasi/listaccount');?>'>
										<i class='fa fa-copy'></i> Accounts
									</a>
								</li>
								<li class="<?= set_sidebar_active(array('service' => 'mutasi', 'method' => 'add'), 'methodsub', $collect['match']);?>">
									<a href='<?= base_url($base_path . '/mutasi/add');?>'>
										<i class='fa fa-plus-square'></i>
										<span class='title'>Add</span>
										<span class='selected'></span>
										<span class='arrow'></span>
									</a>
									<ul class='sub-menu'>
										<li class='<?= set_sidebar_active(array('service' => 'mutasi', 'method' => 'addbank'), 'method', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/mutasi/addbank');?>'><i class='fa fa-list-alt'></i> Banks</a>
										</li>
										<li class='<?= set_sidebar_active(array('service' => 'mutasi', 'method' => 'additem'), 'method', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/mutasi/additem');?>'><i class='fa fa-file'></i> Accounts</a>
										</li>
									</ul>
								</li>
							</ul>
						</li>
						
						<li class="<?= set_sidebar_active('suksesbugil', 'service', $collect['match']);?>">
							<a href='<?= base_url($base_path . '/suksesbugil');?>'>
								<i class='fa fa-table'></i>
								<span class='title'>Data Deposit</span>
								<span class='selected'></span>
								<span class='arrow'></span>
							</a>
							<ul class='sub-menu'>
								<li class="<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'deposit'), 'methodsub', $collect['match']);?>">
									<a href='<?= base_url($base_path . '/suksesbugil/deposit');?>'>
										<i class='fa fa-bars'></i>
										<span class='title'>Collected Deposit</span>
										<span class='selected'></span>
										<span class='arrow'></span>
									</a>
									<ul class='sub-menu'>
										<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'deposit', 'segment' => 'waiting'), 'methodchild', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/suksesbugil/deposit/waiting');?>'>
												<i class='fa fa-clock-o'></i> Waiting
											</a>
										</li>
										<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'deposit', 'segment' => 'approved'), 'methodchild', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/suksesbugil/deposit/approved');?>'>
												<i class='fa fa-check'></i> Approved
											</a>
										</li>
										<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'deposit', 'segment' => 'already'), 'methodchild', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/suksesbugil/deposit/already');?>'>
												<i class='fa fa-repeat'></i> Already
											</a>
										</li>
										<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'deposit', 'segment' => 'deleted'), 'methodchild', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/suksesbugil/deposit/deleted');?>'>
												<i class='fa fa-trash'></i> Deleted
											</a>
										</li>
										<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'deposit', 'segment' => 'failed'), 'methodchild', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/suksesbugil/deposit/failed');?>'>
												<i class='fa fa-exclamation-triangle'></i> Rejected/Failed
											</a>
										</li>
										<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'deposit', 'segment' => 'all'), 'methodchild', $collect['match']);?>'>
											<a href='<?= base_url($base_path . '/suksesbugil/deposit/all');?>'>
												<i class='fa fa-table'></i> All Data
											</a>
										</li>
									</ul>
								</li>
								<?php
								if (in_array($collect['userdata']['account_role'], base_config('admin_role'))) {
									?>
									<li class="<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'sbdetails'), 'methodsub', $collect['match']);?>">
										<a href='<?= base_url($base_path . '/suksesbugil/sbdetails');?>'>
											<i class='fa fa-cogs'></i>
											<span class='title'>Configuration</span>
											<span class='selected'></span>
											<span class='arrow'></span>
										</a>
										<ul class='sub-menu'>
											<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'sbdetails', 'segment' => 'all'), 'methodchild', $collect['match']);?>'>
												<a href='<?= base_url($base_path . '/suksesbugil/sbdetails/all');?>'>
													<i class='fa fa-table'></i> Descriptions
												</a>
											</li>
											<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'sbdetailsofsbscheduler', 'segment' => 'all'), 'methodchild', $collect['match']);?>'>
												<a href='<?= base_url($base_path . '/suksesbugil/sbdetailsofsbscheduler/all');?>'>
													<i class='fa fa-power-off'></i> Scheduler
												</a>
											</li>
											<li class='<?= set_sidebar_active(array('service' => 'suksesbugil', 'method' => 'mutasibanktime', 'segment' => 'all'), 'methodchild', $collect['match']);?>'>
												<a href='<?= base_url($base_path . '/suksesbugil/mutasibanktime/all');?>'>
													<i class='fa fa-clock-o'></i> Active Time
												</a>
											</li>
										</ul>
									</li>
									<?php
								}
								?>
							</ul>
						</li>
						
						<li class="<?= set_sidebar_active('report', 'service', $collect['match']);?>">
							<a href='<?= base_url($base_path . '/report');?>'>
								<i class='fa fa-bar-chart'></i>
								<span class='title'>Report Mutasi</span>
								<span class='selected'></span>
								<span class='arrow'></span>
							</a>
							<ul class='sub-menu'>
								<li class="<?= set_sidebar_active(array('service' => 'report', 'method' => 'viewmutasi'), 'methodsub', $collect['match']);?>">
									<a href='<?= base_url($base_path . '/report/viewmutasi');?>'>
										<i class='fa fa-exchange'></i> Mutasi
									</a>
								</li>
								<li class="<?= set_sidebar_active(array('service' => 'report', 'method' => 'viewdeposit'), 'methodsub', $collect['match']);?>">
									<a href='<?= base_url($base_path . '/report/viewdeposit');?>'>
										<i class='fa fa-download'></i> Auto Deposit
									</a>
								</li>
							</ul>
						</li>
						<?php
					}
					?>
					
					
				</ul>
				<!-- END SIDEBAR MENU -->
			</div>
		</div>
		<!-- END SIDEBAR -->	