<!DOCTYPE html>
<html>

<head><?php echo $header; ?></head>

<body>

    <div id="wrapper">

        <nav class="navbar navbar-default navbar-fixed-top" role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only"><?php echo $text_toggle_navigation; ?></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href=""><?php echo $heading_title; ?></a>
            </div>
            <!-- /.navbar-header -->
			 
            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-user fa-fw"></i>  <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li>
                        	<a href="#" id="link-change-password"><i class="fa fa-key fa-fw"></i> <?php echo $text_change_password; ?></a>
                        </li>
                        <li>
                        	<a href="<?php echo $action_logout; ?>"><i class="fa fa-sign-out fa-fw"></i> <?php echo $text_logout; ?></a>
                        </li>
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
            <!-- /.navbar-top-links -->
            
            <div class="navbar-header navbar-right" id="info"></div>
            
            <div class="navbar-default navbar-static-side" role="navigation">
                <div class="sidebar-collapse" id="menudinamico">
                    <!-- /#side-menu -->
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
       		<div class="content" id="content"></div>
            
            <div class="foot" id="sitefoot">
				<?php echo $footer; ?>
			</div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

    <!-- Change Password modal: present only once in the shell (this
         template is loaded once per session, the content of the pages
         below is then loaded via AJAX into #content) -->
    <div class="modal fade" id="modal-change-password" tabindex="-1" role="dialog">
    	<div class="modal-dialog" role="document">
    		<div class="modal-content">
    			<div class="modal-header">
    				<button type="button" class="close" data-dismiss="modal">&times;</button>
    				<h4 class="modal-title"><?php echo $text_change_password; ?></h4>
    			</div>
    			<div class="modal-body">
    				<div class="alert alert-danger" id="change-password-error" style="display:none;"></div>
    				<div class="form-group">
    					<label for="change-password-current"><?php echo $entry_current_password; ?></label>
    					<input type="password" class="form-control" id="change-password-current" autocomplete="current-password" />
    				</div>
    				<div class="form-group">
    					<label for="change-password-new"><?php echo $entry_new_password; ?></label>
    					<input type="password" class="form-control" id="change-password-new" autocomplete="new-password" />
    				</div>
    				<div class="form-group">
    					<label for="change-password-confirm"><?php echo $entry_confirm_password; ?></label>
    					<input type="password" class="form-control" id="change-password-confirm" autocomplete="new-password" />
    				</div>
    			</div>
    			<div class="modal-footer">
    				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $button_cancel; ?></button>
    				<button type="button" class="btn btn-success" id="button-conferma-change-password"><?php echo $text_change_password; ?></button>
    			</div>
    		</div>
    	</div>
    </div>

    <script type="text/javascript"><!--
    	$('#link-change-password').click(function() {
    		$('#change-password-current').val('');
    		$('#change-password-new').val('');
    		$('#change-password-confirm').val('');
    		$('#change-password-error').hide().text('');
    		$('#modal-change-password').modal('show');
    		return false;
    	});

    	$('#button-conferma-change-password').click(function() {
    		var $btn = $(this);
    		$btn.prop('disabled', true);

    		$.ajax({
    			url: '<?php echo $action_change_password; ?>',
    			type: 'POST',
    			dataType: 'json',
    			data: {
    				'current_password': $('#change-password-current').val(),
    				'new_password': $('#change-password-new').val(),
    				'confirm_password': $('#change-password-confirm').val()
    			},
    			success: function(response) {
    				$btn.prop('disabled', false);
    				if (response.error) {
    					$('#change-password-error').text(response.error).show();
    				} else {
    					$('#modal-change-password').one('hidden.bs.modal', function() {
    						alert(response.success);
    					});
    					$('#modal-change-password').modal('hide');
    				}
    			},
    			error: function() {
    				$btn.prop('disabled', false);
    				$('#change-password-error').text(<?php echo json_encode(html_entity_decode($error_communication, ENT_QUOTES, 'UTF-8')); ?>).show();
    			}
    		});

    		return false;
    	});
    //--></script>

</body>

</html>
