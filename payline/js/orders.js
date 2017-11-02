$(document).ready(function() {
	$('button[name=cancelProduct], input[name=cancelProduct]').click(function() {
		if (!$('input[name=generateCreditSlip]').is(':checked'))
			return confirm(paylineNoCreditSlipAlert);
		else if ($('input[name=generateDiscount]').is(':checked'))
			return confirm(paylineVoucherAlert);
		else
			return confirm(paylineConfirmAlertRefund);
	});
	$('input[name=partialRefund], button[name=partialRefund]').click(function() {
		if ($('input[name=generateDiscountRefund]').is(':checked'))
			return confirm(paylineVoucherAlert);
		else
			return confirm(paylineConfirmAlertRefund);
	});
	$('a#desc-order-partial_refund').click(function() {
		$('input[name^="partialRefundProductQuantity"]').val(0);
	});
});