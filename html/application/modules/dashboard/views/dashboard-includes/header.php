<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }


?>
<!DOCTYPE html>
<!--[if IE 8]> 
	<html lang="en" class="ie8 no-js"> 
<![endif]-->
<!--[if IE 9]>
	<html lang="en" class="ie9 no-js"> 
<![endif]-->
<!--[if !IE]>-->
	<html lang="en" class="no-js">
<!--<![endif]-->
<!-- BEGIN HEAD -->
<head>
	<title><?= (isset($title) ? $title : '');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
	<meta name="MobileOptimized" content="320">
	<meta name="keywords" content="" />
	<meta name="description" content="" />
	<meta name="author" content="" />
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/font-awesome/css/font-awesome.min.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/plugins/uniform/css/uniform.default.css');?>" />
	<!-- BEGIN THEME STYLES -->
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/bootstrap-theme.min.css');?>" />
	<?php
	switch (strtolower(ConstantConfig::$templates)) {
		case 'blue':
			?><link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/blue.css');?>" /><?php
		break;
		case 'brown':
			?><link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/brown.css');?>" /><?php
		break;
		case 'purple':
			?><link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/purple.css');?>" /><?php
		break;
		case 'red':
			?><link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/red.css');?>" /><?php
		break;
		case 'grey':
			?><link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/grey.css');?>" /><?php
		break;
		case 'light':
			?><link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/light.css');?>" /><?php
		break;
		case 'default':
		default:
			?><link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/themes/default.css');?>" /><?php
		break;
	}
	?>
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/style-metronic.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/style.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/style-responsive.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/plugins.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/custom.css');?>" />
	<!-- END THEME STYLES -->
	<link rel="stylesheet" type="text/css" media="screen" href="<?= base_url('assets/plugins/bootstrap-datepicker/bootstrap-datepicker.css');?>" />
	<script type="text/javascript" src="<?= base_url('assets/js/jquery-1.10.2.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/js/jquery-migrate-1.2.1.min.js');?>"></script>
	
	<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->
	<!-- BEGIN CORE PLUGINS -->
	<!--[if lt IE 9]>
		<script type="text/javascript" src="<?= base_url('assets/js/respond.min.js');?>"></script>
		<script type="text/javascript" src="<?= base_url('assets/js/excanvas.min.js');?>"></script>
	<![endif]-->
	<!-- IMPORTANT! Load jquery-ui-1.10.3.custom.min.js before bootstrap.min.js to fix bootstrap tooltip conflict with jquery ui tooltip -->
	<script type="text/javascript" src="<?= base_url('assets/plugins/jquery-ui/jquery-ui-1.10.3.custom.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/bootstrap/js/bootstrap.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/bootstrap-hover-dropdown/twitter-bootstrap-hover-dropdown.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/jquery-slimscroll/jquery.slimscroll.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/js/jquery.blockui.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/js/jquery.cokie.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/jquery-uniform/jquery.uniform.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/bootstrap-datepicker/bootstrap-datepicker.js');?>"></script>
	<!-- END CORE PLUGINS -->
	<script type="text/javascript" src="<?= base_url('assets/js/app.js');?>"></script>
	<script type="text/javascript">
		jQuery(document).ready(function() {    
			App.init();
			$('.form-group.error').addClass('has-error');
		});
   	</script>
	<!-- END JAVASCRIPTS -->
</head>
<body class="page-header-fixed-later">
	<!-- BEGIN HEADER -->
	<div class="header navbar navbar-inverse navbar-static-top">
		<!-- BEGIN TOP NAVIGATION BAR -->
		<div class="header-inner">
			<!-- BEGIN LOGO -->
			<a href="<?= base_url();?>" class="navbar-brand">
				<img src="<?= base_url('assets/img/logo.png');?>" class="img-responsive" alt="img-logo" />
			</a>		
			<!-- END LOGO -->
			<!-- BEGIN RESPONSIVE MENU TOGGLER -->
			<a href="javascript:;" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
				<img src="<?= base_url('assets/img/menu-toggler.png');?>" alt="menu-toggler" />		
			</a>
			<!-- END RESPONSIVE MENU TOGGLER -->
			<!-- BEGIN TOP NAVIGATION MENU -->
			<ul class="nav navbar-nav pull-right">
				<!-- BEGIN USER LOGIN DROPDOWN -->
				<li class="dropdown user">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
						<span class="username"><?= (isset($collect['userdata']['account_email']) ? $collect['userdata']['account_email'] : 'un-known');?></span>
						<i class="fa fa-angle-down"></i>
					</a>
					<ul class="dropdown-menu">
						<li>
							<a href="<?= base_url("{$base_path}/profile/view/edit");?>"><i class="fa fa-gear"></i> Setting</a>					
						</li>
						<li>
							<a href="<?= base_url("{$base_path}/profile/view");?>"><i class="fa fa-user"></i> Profile</a>
						</li>
						<li>
							<a href="<?= base_url("{$base_path}/account/logout");?>"><i class="fa fa-key"></i> Log Out</a>				
						</li>
					</ul>
				</li>
				<!-- END USER LOGIN DROPDOWN -->
			</ul>
			<!-- END TOP NAVIGATION MENU -->
		</div>
		<!-- END TOP NAVIGATION BAR -->
	</div>
	<!-- END HEADER -->
	<div class="clearfix"></div>
	
	<!-- BEGIN CONTAINER -->
	<div class="page-container">
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	