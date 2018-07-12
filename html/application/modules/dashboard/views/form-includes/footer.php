<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }
?>


	<!-- BEGIN COPYRIGHT -->
	<div class="copyright"><?= base_config('site-copyright');?></div>
	<!-- END COPYRIGHT -->
	<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->
	<!-- BEGIN CORE PLUGINS -->
	<!--[if lt IE 9]>
		<script type="text/javascript" src="<?= base_url('assets/js/respond.min.js');?>"></script>
		<script type="text/javascript" src="<?= base_url('assets/js/excanvas.min.js');?>"></script>
	<![endif]-->
	<script type="text/javascript" src="<?= base_url('assets/js/jquery-1.10.2.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/js/jquery-migrate-1.2.1.min.js');?>"></script>
	
	<script type="text/javascript" src="<?= base_url('assets/plugins/jquery-ui/jquery-ui-1.10.3.custom.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/bootstrap/js/bootstrap.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/bootstrap-hover-dropdown/twitter-bootstrap-hover-dropdown.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/jquery-slimscroll/jquery.slimscroll.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/js/jquery.blockui.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/js/jquery.cokie.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/jquery-uniform/jquery.uniform.min.js');?>"></script>
	<script type="text/javascript" src="<?= base_url('assets/plugins/backstretch/jquery.backstretch.min.js');?>"></script>
	<!-- END CORE PLUGINS -->
	<!-- BEGIN PAGE LEVEL SCRIPTS -->
	<script type="text/javascript" src="<?= base_url('assets/js/app.js');?>"></script>
	<!-- END PAGE LEVEL SCRIPTS -->
	<script type="text/javascript">
		// jQuery(document).ready(function() {    
		// 	App.init();
		// 	Login.init();
		//     $('.form-group.error').addClass('has-error');
		// });
	</script>
	<!-- END JAVASCRIPTS -->
</body>
<!-- END BODY -->
</html>