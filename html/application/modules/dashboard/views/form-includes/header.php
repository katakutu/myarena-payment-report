<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }
?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
	<html lang="en" class="no-js">
<!--<![endif]-->
	<!-- BEGIN HEAD -->
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?= (isset($title) ? $title : '');?></title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
		<meta name="MobileOptimized" content="320">
		<link href="<?= base_url('assets/icon/favicon.ico');?>" type="image/x-icon" rel="icon" />
		<link href="<?= base_url('assets/icon/favicon.ico');?>" type="image/x-icon" rel="shortcut icon" />
		<!-- BEGIN GLOBAL MANDATORY STYLES -->
		<link rel="stylesheet" type="text/css" href="<?= base_url('assets/font-awesome/css/font-awesome.min.css');?>" />
		<link rel="stylesheet" type="text/css" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css');?>" />
		<link rel="stylesheet" type="text/css" href="<?= base_url('assets/plugins/uniform/css/uniform.default.css');?>" />
		<!-- END GLOBAL MANDATORY STYLES -->
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
		<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/login.css');?>" />
		<link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/custom.css');?>" />
		<!-- END THEME STYLES -->
	</head>
	<!-- END HEAD -->
<!-- BEGIN BODY -->
<body class="login">
	<!-- BEGIN LOGO -->
	<div class="logo">
		<a href="<?= base_url($base_path);?>">
			<img src="<?= base_url('assets/img/logo.png');?>" alt="img-logo" />
		</a>
	</div>
	<!-- END LOGO -->
	
	
	
	
	
	
	
	
	
	
	
	
	