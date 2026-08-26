jQuery(function($){
	function isPayLaterSelected(){
		const selected = String($('input[name="payment_method"]:checked').val() || '');
		return selected === 'pay_later' || selected.indexOf('pay_later') !== -1;
	}

	function togglePayLaterUpload(){
		const isPayLater = isPayLaterSelected();
		$('#pay-later-po-upload').toggle(isPayLater);
	}

	function togglePoRequired(){
		const isPayLater = isPayLaterSelected();
		const field = $('#billing_po_number');
		const row = $('#pay-later-po-number-wrap #billing_po_number_field');
		const label = row.find('label');

		$('#pay-later-po-number-wrap').toggle(isPayLater);

		if(!row.length || !field.length){
			return;
		}

		if(isPayLater){
			row.addClass('validate-required');
			row.find('.optional').hide();
			if(!label.find('.required').length){
				label.append(' <span class="required" aria-hidden="true">*</span>');
			}
			field.prop('required', true).attr('aria-required','true');
		}else{
			row.removeClass('validate-required');
			row.find('.optional').show();
			label.find('.required').remove();
			field.prop('required', false).attr('aria-required','false');
		}
	}

	$(document.body).on('change','input[name="payment_method"]',function(){
		togglePayLaterUpload();
		togglePoRequired();
	});
	$(document.body).on('updated_checkout',function(){
		togglePayLaterUpload();
		togglePoRequired();
	});

	togglePayLaterUpload();
	togglePoRequired();
});
