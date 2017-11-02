function updatePaylineContractsPositions() {
	$('input.payline-primary-contracts-value').val(JSON.stringify($('div.payline-primary-contracts-list ol').sortable('toArray', {attribute: 'data-id-card'})));
	$('input.payline-secondary-contracts-value').val(JSON.stringify($('div.payline-secondary-contracts-list ol').sortable('toArray', {attribute: 'data-id-card'})));
}

var nbPaylineSelectedContracts = 0;
$(document).ready(function() {
	$('div.payline-available-contracts-list ul li').each(function() {
		$(this).data('selected', false);
	});
	$('div.payline-available-contracts-list ul li').click(function() {
		$(this).data('selected', !$(this).data('selected'));
		$(this).toggleClass('payline-selected-contract');
		nbPaylineSelectedContracts += ($(this).data('selected') ? 1 : -1);
		if (nbPaylineSelectedContracts > 0) {
			$('input.submitAddNewContract, input.submitAddNewAlternateContract').removeAttr('disabled');
		} else {
			$('input.submitAddNewContract, input.submitAddNewAlternateContract').attr('disabled', 'disabled');
		}
	});
	$('input.submitAddNewContract').click(function() {
		$('div.payline-available-contracts-list ul li').each(function() {
			if ($(this).data('selected') && $('div.payline-primary-contracts-list ol li[data-id-card="' + $(this).attr('data-id-card') + '"]').size() == 0) {
				$(this).clone().appendTo('div.payline-primary-contracts-list ol');
				updatePaylineContractsPositions();
			}
			if ($(this).data('selected'))
				$(this).data('selected', false).toggleClass('payline-selected-contract');
		});
	});
	$('input.submitAddNewAlternateContract').click(function() {
		$('div.payline-available-contracts-list ul li').each(function() {
			if ($(this).data('selected') && $('div.payline-secondary-contracts-list ol li[data-id-card="' + $(this).attr('data-id-card') + '"]').size() == 0) {
				$(this).clone().appendTo('div.payline-secondary-contracts-list ol');
				updatePaylineContractsPositions();
			}
			if ($(this).data('selected'))
				$(this).data('selected', false).toggleClass('payline-selected-contract');
		});
	});
	
	$('div.payline-primary-contracts-list ol').sortable({
		update: function(event, ui) {
			updatePaylineContractsPositions();
		}
	}).disableSelection();
	$('div.payline-secondary-contracts-list ol').sortable({
		update: function(event, ui) {
			updatePaylineContractsPositions();
		}
	}).disableSelection();
	
	$(document).on('click', 'input.paylineRemoveContract', function() {
		if (confirm(paylineRemovePaymentMethodAlert)) {
			$(this).parent().remove();
			updatePaylineContractsPositions();
		}
	});
	
	$(document).on('click', 'input.payline-cfg-button', function() {
		$(this).parent().parent().find('div.payline-payment-cfg-fields').toggle();
	});
	
	$(document).on('change', 'select#PAYLINE_WEB_CASH_ENABLE, select#PAYLINE_DIRECT_ENABLE, select#PAYLINE_SUBSCRIBE_ENABLE, select#PAYLINE_RECURRING_ENABLE, select#PAYLINE_WALLET_ENABLE', function() {
		$(this).parents('div.payline-payment-cfg-container').find('div.payline-payment-cfg-fields').hide();
	});
	
	$('.submitPayline').click(function() {
		updatePaylineContractsPositions();
	});
});

function DisplayField(field,showField) {
	var valField = $("#"+field).val();
	if(valField == 1) {
		$("#"+showField).show("normal");
	}
	else if(valField == "100") {
		$("#"+showField).show("normal");
	}
	else {
		$("#"+showField).hide("normal");
	}
}