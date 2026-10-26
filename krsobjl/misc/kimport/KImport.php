<?php
class KImport implements iImport {
	
	public function create($action, $id = null, $filesContainer = null, $uploadTemplate = null, $downloadTemplate = null) {
		
		if (!isset($id)) {
			$id = "file_upload";
		}
		
		if (!isset($filesContainer)) {
			$filesContainer = ".files";
		}
		?>
		
		<!-- The template to display files available for upload -->
		<script type="text/javascript">
			<?php if (!isset($uploadTemplate)) { ?>
				var uploadTemplate = function (o) {
			        var rows = $();
			        $.each(o.files, function (index, file) {
			            var row = $('<tr">' +
			                '<td><p class="name"></p>' +
			                (file.error ? '<div class="error"></div>' : '') +
			                '</td>' +
			                '<td><p class="size"></p>' +
			                (o.files.error ? '' : '<div class="progress"></div>') +
			                '</td>' +
			                '<td>' +
			                (!o.files.error && !index && !o.options.autoUpload ?
			                    '<button class="start">Start</button>' : '') +
			                (!index ? '<button class="cancel">Cancel</button>' : '') +
			                '</td>' +
			                '</tr>');
		                
			            row.find('.name').text(file.name);
			            row.find('.size').text(o.formatFileSize(file.size));
			            if (file.error) {
			                row.find('.error').text(file.error);
			            }
			            rows = rows.add(row);
			        });
			        return rows;
			    };
			<?php } else { ?>
				var uploadTemplate = <?php echo $uploadTemplate; ?>;
			<?php } ?>
		</script>
		
		<!-- The template to display files available for download -->
		<script type="text/javascript">
			<?php if (!isset($downloadTemplate)) { ?>
				var downloadTemplate = function (o) {
			        var rows = $();
			        $.each(o.files, function (index, file) {
			            var row = $('<tr>' +
			                '<td><p class="name"></p>' +
			                (file.error ? '<div class="error"></div>' : '') +
			                '</td>' +
			                '<td><span class="size"></span></td>' +
			                '<td><button class="delete">Delete</button></td>' +
			                '</tr>');
		                
			            row.find('.size').text(o.formatFileSize(file.size));
			            if (file.error) {
			                row.find('.name').text(file.name);
			                row.find('.error').text(file.error);
			            } else {
			                row.find('.name').append($('<a></a>').text(file.name));
			                row.find('.delete')
			                    .attr('data-type', file.delete_type)
			                    .attr('data-url', file.delete_url);
			            }
			            rows = rows.add(row);
			        });
			        return rows;
			    };
			<?php } else { ?>
				var downloadTemplate = <?php echo $downloadTemplate; ?>;
			<?php } ?>
		</script>
		
		<!-- The file upload form used as target for the file upload widget -->
		<form id="<?php echo $id; ?>" action="<?php echo $action; ?>" method="POST" enctype="multipart/form-data">
		    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
		    <div class="fileupload-buttonbar">
		        <div class="fileupload-buttons">
		            <!-- The fileinput-button span is used to style the file input field as button -->
		            <span class="fileinput-button">
		                <span>Add files...</span>
		                <input type="file" name="file">
		            </span>
		        </div>
		        <!-- The global progress state -->
		        <div class="fileupload-progress fade" style="display:none">
		            <!-- The global progress bar -->
		            <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
		            <!-- The extended global progress state -->
		            <div class="progress-extended">&nbsp;</div>
		        </div>
		    </div>
		    
		    <?php
			if ($filesContainer == '.files') { ?>
				<!-- The table listing the files available for upload/download -->
				<table role="presentation"><tbody class="files"></tbody></table>
			<?php } ?>
		</form>
		
		<script>
		$(function () {
			$('#<?php echo $id; ?>').fileupload({
				autoUpload: true,
				filesContainer: "<?php echo $filesContainer; ?>",
				uploadTemplateId: null,
			    downloadTemplateId: null,
			    uploadTemplate: uploadTemplate,
			    downloadTemplate: downloadTemplate
			});
		});
		</script>
		<?php
	}
}