<div class="paylineHolder">
	{if isset($paylineProduction) AND !$paylineProduction}
		<div class="error">
			{l s='The payment Payline is in test mode.' mod='payline'}
		</div>
	{/if}
<!-- WALLET //-->
	{if (isset($cardData) && $paylineWallet && $cardData)}
		<div class="paylineSpecificBlock cardsWallet">
			<img src="{$sBaseUrl}modules/payline/img/Payline-Fr-NB.png" class="right"/>
			<div class="title">{$PAYLINE_WALLET_TITLE}</div>
			<div class="subtitle">{$PAYLINE_WALLET_SUBTITLE}</div>

			<div class="clearfix">
				{foreach from=$cardData item=card name=myLoop}
					<ul class="cardWallet {if $smarty.foreach.myLoop.last}last_item{elseif $smarty.foreach.myLoop.first}first_item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{else}item{/if}">
						<li class="card_title">
							<input type="radio" name="card" value="{$card.cardInd|intval}" data-id-card="{$card.cardInd|intval}" data-new-location="{$base_dir}modules/payline/redirect.php?cardInd={$card.cardInd}&type={$card.type}" /> {l s='Card' mod='payline'} {$card.cardInd}
						</li>
						<li class="card_type">{l s='Type' mod='payline'} <b>{$card.type}</b></li>
						<li class="card_name">{l s='Holder' mod='payline'} <b>{$card.firstName} {$card.lastName}</b></li>
						<li class="card_number">{l s='Card number' mod='payline'} <b>{$card.number}</b></li>
						<li class="card_expiration">{l s='Expiration date' mod='payline'} <b>{$card.expirationDate}</b></li>
					</ul>
				{/foreach}
			</div>
			<input type="button" class="exclusive exclusive_disabled" name="submitWalletPayment" disabled="disabled" value="{l s='Commande avec obligation de paiement' mod='payline'}">
		</div>
		<p class="clear"> </p>
	{/if}
<!-- /WALLET //-->

<!-- WEBCASH //-->
	{if isset($cards) AND $paylineWebcash}
		<div class="paylineSpecificBlock">
      <img src="{$sBaseUrl}modules/payline/img/Payline-Fr-NB.png" class="right"/>
			{foreach from=$cards item=card name=cards}
				<p class="payment_module payline_payment" data-card-type="{$card.type}" data-card-contract="{$card.contract}" data-mode="webCash">
					<img src="{$card.logo}" alt="{$card.type}" title="{$card.type}" class="logo"  alt="{l s='Click here to pay with Payline gateway' mod='payline'}" />
					<span>{$card.label}</span>
				</p>
			{/foreach}
		</div>
		<p class="clear"> </p>
	{/if}
<!-- /WEBCASH //-->

<!-- DIRECT //-->
{if $paylineDirect}
	<div class="paylineSpecificBlock cardsWallet">
		<img src="{$sBaseUrl}modules/payline/img/Payline-Fr-NB.png" class="right"/>
		<div class="title">{$PAYLINE_DIRECT_TITLE}</div>
		<div class="subtitle">{$PAYLINE_DIRECT_SUBTITLE}</div>
		<div class="">
			<form action="{$base_dir}modules/payline/redirect.php" method="post" name="directPaymentPayline" id="directPaymentPayline" class="payline-form">
				<input type="hidden" name="directmode" id="directmode" value="direct" />
				<div>
					{foreach from=$cardsNX item=card name=cards}
						<input type="radio" name="contractNumber" value="{$card.contract}" /> <img src="{$base_dir}modules/payline/img/{$card.type}.gif" alt="{$card.type}" title="{$card.type}" />
					{/foreach}
				</div>
				<label for="holder">{l s='Holder' mod='payline'}</label>
				<input type="text" name="holder" value="" id="holder" size="30" />
				<label for="cardNumber">{l s='Card number' mod='payline'}</label>
				<input type="text" name="cardNumber" value="" id="cardNumber" size="30" maxlength="16" />
				<label for="ExpirationDate">{l s='Expiration date' mod='payline'}</label>
				<select name="monthExpire" style="width:60px;">
					<option value="">{l s='Month'}</option>
					{section name=foo start=1 loop=13 step=1}
  						<option value="{$smarty.section.foo.index}">{$smarty.section.foo.index}</option>
					{/section}
				</select>
				<select name="yearExpire" style="width:60px;">
					<option value="">{l s='Year'}</option>
					{assign var=year value=$smarty.now|date_format:"%Y"}
					{section name=foo start=$year loop=$year+7 step=1}
  						<option value="{$smarty.section.foo.index}">{$smarty.section.foo.index}</option>
					{/section}
				</select>
				<label for="crypto">{l s='Verification number' mod='payline'}</label>
				<input type="text" name="crypto" value="" id="crypto" size="5" maxlength="4" />
				<p class="clear"> </p>
				<input type="submit" name="submit" value="{l s='Order now' mod='payline'}" class="button" />
			</form>
		</div>
	</div>
	<p class="clear"> </p>
{/if}
<!-- /DIRECT //-->


<!-- RECURRING //-->
{if $paylineRecurring}
	<div class="paylineSpecificBlock">
		<div class="title">{$PAYLINE_RECURRING_TITLE}</div>
		<div class="subtitle">{$PAYLINE_RECURRING_SUBTITLE}</div>
		<p class="description">
			<i>{l s='You will make a payment of' mod='payline'} <b>
				{displayPrice price=$firstNxAmount currency=$cart->id_currency no_utf8=false convert=false}
			</b>
			<br/>
				{l s='The amount of the following dates will be' mod='payline'} <b>
        {displayPrice price=$nxAmount currency=$cart->id_currency no_utf8=false convert=false}</b>
			</i>
		</p>
		<div class="recurringCard">
			{foreach from=$cardsNX item=card name=cards}
				<p class="payment_module payline_payment" data-card-type="{$card.type}" data-card-contract="{$card.contract}" data-mode="recurring">
					<img src="{$card.logo}" alt="{$card.type}" title="{$card.type}" class="logo"  alt="{l s='Click here to pay with Payline gateway' mod='payline'}" />
					<span>{$card.label}</span>
				</p>
			{/foreach}
		</div>
	</div>
	<p class="clear"> </p>

{/if}
<!-- /RECURRING //-->



<!-- SUBSCRIBE //-->
{if $paylineSubscribe}
	<div class="paylineSpecificBlock">
		<div class="title">{$PAYLINE_SUBSCRIBE_TITLE}</div>
		<div class="subtitle">{$PAYLINE_SUBSCRIBE_SUBTITLE}</div>
		<p class="description">
			<i>{l s='You will make a payment of' mod='payline'} <b>
			{if isset($orderReduceAmount)}
				{displayPrice price=$orderReduceAmount currency=$cart->id_currency no_utf8=false convert=false}
			{else}
				{displayPrice price=$orderAmount currency=$cart->id_currency no_utf8=false convert=false}{/if}
			</b>
			<br/>
			{if isset($numberReduceSchedule) AND ($numberReduceSchedule-1) > 1}
				{l s='You will be charged' mod='payline'} <b>{displayPrice price=$orderReduceAmount currency=$cart->id_currency no_utf8=false convert=false}</b> {l s='for the' mod='payline'} {$numberReduceSchedule-1} {l s='forthcoming' mod='payline'}
			{else if isset($numberReduceSchedule) AND ($numberReduceSchedule-1) == 1}
				{l s='You will be charged' mod='payline'} <b>{displayPrice price=$orderReduceAmount currency=$cart->id_currency no_utf8=false convert=false}</b> {l s='for the next deadline' mod='payline'}
			{/if}
			{if isset($orderReduceAmount)}<br/>
				{l s='The amount of the following dates will be' mod='payline'} <b>{displayPrice price=$orderAmount currency=$cart->id_currency no_utf8=false convert=false}</b>
			{/if}
			</i>

		</p>
		<div class="recurringCard">
			{foreach from=$cardsNX item=card name=cards}
				<p class="payment_module payline_payment" data-card-type="{$card.type}" data-card-contract="{$card.contract}" data-mode="subscribe">
					<img src="{$card.logo}" alt="{$card.type}" title="{$card.type}" class="logo"  alt="{l s='Click here to pay with Payline gateway' mod='payline'}" />
					<span>{$card.label}</span>
				</p>
			{/foreach}
		</div>

	</div>
{/if}
<!-- END SUBSCRIBE PAYMENT //-->

<!-- DIRECT DEBIT //-->
{if $paylineDirDebit}
	<div class="paylineSpecificBlock">
		<div class="title">{$PAYLINE_DIRDEBIT_TITLE}</div>
		<div class="subtitle">{$PAYLINE_DIRDEBIT_SUBTITLE}</div>
		<p class="description">
			{l s='You will be charged' mod='payline'} <b>{displayPrice price=$orderAmount currency=$cart->id_currency no_utf8=false convert=false}</b> {l s='for the' mod='payline'} {$paylineDirDebitNb} {l s='forthcoming' mod='payline'}
		</p>
		<div class="recurringCard">
			{foreach from=$directDebitContract item=card name=cards}
				<p class="payment_module payline_payment" data-card-type="{$card.type}" data-card-contract="{$card.contract}" data-mode="directdebit">
					<img src="{$card.logo}" alt="{$card.type}" title="{$card.type}" class="logo"  alt="{l s='Click here to pay with Payline gateway' mod='payline'}" />
					<span>{$card.label}</span>
				</p>
			{/foreach}
		</div>
	</div>
{/if}
<!-- END DIRECT DEBIT PAYMENT //-->

{if isset($payline)}
	{$payline}
{/if}
<script type="text/javascript">
{literal}
	$(document).ready(function(){

		$(".payline_payment").each(function(){

			$(this).click(function(){
				var cardType = $(this).data('cardType');
				var contract = $(this).attr('data-card-contract');
				var mode = $(this).data('mode');
				$('#contractNumber').val(contract);
				$('#mode').val(mode);
				$('#type').val(cardType);
				$("#WebPaymentPayline").submit();
			});
		});

		$('#directPaymentPayline').bind('submit',function(){

			$('#directPaymentPayline input').each(function(){
				$(this).removeClass('invalid');
			});
			$('#directPaymentPayline select').each(function(){
				$(this).removeClass('invalid');
			});

			var allow = false;

			var contractNumber = $('input[name="contractNumber"]:checked').val();
			if(typeof(contractNumber) == 'undefined')
				$('input[name="contractNumber"]').each(function(){
					$(this).addClass('invalid');
				});

			var holder = $('#holder').val();
			if(holder.length <= 0)
				$('#holder').addClass('invalid');

			var cardNumber = $('#cardNumber').val();
			var digitOnlyRegex = /[^0-9]/;
			if(cardNumber.length <= 0 || cardNumber.length < 16 || digitOnlyRegex.test(cardNumber))
				$('#cardNumber').addClass('invalid');

			var monthExpire = $('select[name="monthExpire"] option:selected').val();
			if(monthExpire.length <= 0 || digitOnlyRegex.test(monthExpire))
				$('select[name="monthExpire"]').addClass('invalid');

			var yearExpire = $('select[name="yearExpire"] option:selected').val();
			if(yearExpire.length <= 0 || digitOnlyRegex.test(yearExpire))
				$('select[name="yearExpire"]').addClass('invalid');

			var crypto = $('#crypto').val();
			if(crypto.length <= 0 || crypto.length < 3 || digitOnlyRegex.test(crypto))
				$('#crypto').addClass('invalid');

			allow = (($('#directPaymentPayline').find('.invalid').length > 0)? false : true);

			return allow;
		});
		
		$('input[name=card]').bind('change click',function(){
			if ($('input[name=card]').val() > 0) {
				$('input[name=submitWalletPayment]').removeAttr('disabled').removeClass('exclusive_disabled');
			} else {
				$('input[name=submitWalletPayment]').attr('disabled', 'disabled').addClass('exclusive_disabled');
			}
		});
		
		$('input[name=submitWalletPayment]').bind('click',function(){
			if ($('input[name=card]').val() > 0)
				document.location.href = $('input[name=card][data-id-card='+ $('input[name=card]').val() +']').attr('data-new-location');
			return false;
		});
	});
{/literal}
</script>

</div>