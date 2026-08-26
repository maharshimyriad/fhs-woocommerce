jQuery(function($){
	$(document.body).on('change', '#pay_later_po_file', function(){
		const file = this.files[0];
		if(!file) {
			return;
		}

		const maxSize = 20 * 1024 * 1024;
		if (file.size > maxSize) {
			alert(fhsCheckoutPoUpload.fileTooLarge);
			this.value = '';
			$('.po-file-name').text(fhsCheckoutPoUpload.noFileChosen);
			return;
		}

		$('.po-file-name').text(fhsCheckoutPoUpload.uploadingText + file.name);

		const $placeOrderBtn = $('button#place_order');
		const originalBtnText = $placeOrderBtn.text();
		$placeOrderBtn.prop('disabled', true).html('<span class="po-spinner"></span> ' + fhsCheckoutPoUpload.uploadingButton);
		$('.po-upload-box').addClass('is-uploading');

		let formData = new FormData();
		formData.append('action','upload_po_file');
		formData.append('po_file',file);

		$.ajax({
			url: (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.ajax_url) ? wc_checkout_params.ajax_url : fhsCheckoutPoUpload.ajaxUrl,
			type:'POST',
			data:formData,
			processData:false,
			contentType:false,
			success:function(response){
				if(response.success){
					$('#pay_later_po_file_url').val(response.data.url);
					$('.po-file-name').text(fhsCheckoutPoUpload.successPrefix + file.name);
				} else {
					alert(fhsCheckoutPoUpload.uploadFailedPrefix + (response.data ? response.data.message : 'Unknown error'));
					$('.po-file-name').text(fhsCheckoutPoUpload.uploadFailed);
					$('#pay_later_po_file').val('');
				}
			},
			error: function(){
				alert(fhsCheckoutPoUpload.serverError);
				$('.po-file-name').text(fhsCheckoutPoUpload.serverErrorShort);
				$('#pay_later_po_file').val('');
			},
			complete: function() {
				$placeOrderBtn.prop('disabled', false).html(originalBtnText);
				$('.po-upload-box').removeClass('is-uploading');
			}
		});
	});
});
