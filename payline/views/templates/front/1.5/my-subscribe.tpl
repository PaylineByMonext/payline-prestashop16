<script type="text/javascript">
//<![CDATA[
	var baseDir = '{$base_dir_ssl}';
//]]>
</script>

{capture name=path}<a href="{$link->getPageLink('my-account', true)}">{l s='My account' mod='payline'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='My subscribe' mod='payline'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='My subscribe Payline' mod='payline'}</h2>

{if isset($error)}
	<div class="error">
		<p>{$error}</p>
	</div>
{/if}
{if isset($success)}
	<div class="success">
		<p>{$success}</p>
	</div>
{/if}
<div class="block-center" id="block-history">
	{if $subscribes && count($subscribes)}
	<table id="order-list" class="std">
		<thead>
			<tr>
				<th class="first_item">{l s='N°subscribe' mod='payline'}</th>
				<th class="item">{l s='Start Date' mod='payline'}</th>
				<th class="item">{l s='Next deadline' mod='payline'}</th>
				<th class="item">{l s='Next amount' mod='payline'}</th>
				<th class="item">{l s='Status' mod='payline'}</th>
				<th class="last_item">{l s='Action' mod='payline'}</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$subscribes item=subscribe name=myLoop}
			<tr class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{/if}">
				<td class="history_link bold">
					<a class="color-myaccount" href="javascript:displayTab('subscribe_{$subscribe.id_payline_subscribe}','show')">{l s='#'}{$subscribe.paymentRecordId|string_format:"%06d"}</a>
				</td>
				<td class="history_date">{if isset($subscribe.startDate)}{dateFormat date=$subscribe.startDate full=0}{/if}</td>
				<td class="history_date bold"><span>{if isset($subscribe.nextDate)}{dateFormat date=$subscribe.nextDate full=0}{/if}</span></td>
				<td class="history_price"><span class="price">{if isset($subscribe.amount)}{displayPrice price=$subscribe.amount currency=$subscribe.currency no_utf8=false convert=false}{/if}</span></td>
				<td class="history_state bold">{if $subscribe.state == 1}{l s='In progress' mod='payline'}{else if $subscribe.state == 0}{l s='Pending' mod='payline'}{else if $subscribe.state == -1}{l s='Finished' mod='payline'}{/if}</td>
				<td class="history_method">
					<a class="color-myaccount" href="javascript:displayTab('subscribe_{$subscribe.id_payline_subscribe}','show')">{l s='details' mod='payline'}</a>{if ($subscribe.state > -1 OR empty($subscribe.state))} | 
					<a class="color-myaccount suspended" id="{$subscribe.paymentRecordId}|{$subscribe.nextDate}|{$subscribe.2nextDate}|{$paylineNumberPending}|{$subscribe.pendingNumber}" href="#suspendSubscribe">{l s='suspend' mod='payline'}</a> | 
					<a class="color-myaccount terminated" id="{$subscribe.paymentRecordId}" href="#terminatedSubscribe">{l s='stop' mod='payline'}</a>{/if}
				</td>
			</tr>
			<!--We display order //-->
			{if isset($subscribe.orders)}
			<tr id="subscribe_{$subscribe.id_payline_subscribe}" style="display:none;">
				<td colspan='6'>
					<table id="order-list" class="std">
						<thead>
							<tr>
								<th class="first_item">{l s='Order' mod='payline'}</th>
								<th class="item">{l s='Date' mod='payline'}</th>
								<th class="item">{l s='Total price' mod='payline'}</th>
								<th class="item">{l s='Payment' mod='payline'}</th>
								<th class="item">{l s='Status' mod='payline'}</th>
								<th class="item">{l s='Invoice' mod='payline'}</th>
								<th class="last_item" style="width:65px"><a class="color-myaccount" href="javascript:displayTab('subscribe_{$subscribe.id_payline_subscribe}','hide')">{l s='hide' mod='payline'}</a></th>
							</tr>
						</thead>
						<tbody>
						{foreach from=$subscribe.orders item=order name=myOrder}
							<tr class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{/if}">
								<td class="history_link bold">
									{if isset($order.invoice) && $order.invoice && isset($order.virtual) && $order.virtual}<img src="{$img_dir}icon/download_product.gif" class="icon" alt="{l s='Products to download' mod='payline'}" title="{l s='Products to download' mod='payline'}" />{/if}
									<a class="color-myaccount" href="javascript:showOrder(1, {$order.id_order|intval}, '../../order-detail');">{l s='#'}{$order.id_order|string_format:"%06d"}</a>
								</td>
								<td class="history_date bold">{dateFormat date=$order.date_add full=0}</td>
								<td class="history_price"><span class="price">{displayPrice price=$order.total_paid_real currency=$order.id_currency no_utf8=false convert=false}</span></td>
								<td class="history_method">{$order.payment|escape:'htmlall':'UTF-8'}</td>
								<td class="history_state">{if isset($order.order_state)}{$order.order_state|escape:'htmlall':'UTF-8'}{/if}</td>
								<td class="history_invoice">
									{if (isset($order.invoice) && $order.invoice && isset($order.invoice_number) && $order.invoice_number) && isset($invoiceAllowed) && $invoiceAllowed == true}
										<a href="{$link->getPageLink('pdf-invoice', true)}?id_order={$order.id_order|intval}" title="{l s='Invoice'}"><img src="{$img_dir}icon/pdf.gif" alt="{l s='Invoice'}" class="icon" /></a>
										<a href="{$link->getPageLink('pdf-invoice', true)}?id_order={$order.id_order|intval}" title="{l s='Invoice'}">{l s='PDF' mod='payline'}</a>
									{else}-{/if}
								</td>
								<td class="history_detail">
									<a class="color-myaccount" href="javascript:showOrder(1, {$order.id_order|intval}, '../../order-detail');">{l s='details' mod='payline'}</a>
								</td>
							</tr>
						{/foreach}
						</tbody>
					</table>
				</td>
			</tr>
			{/if}
		{/foreach}
		</tbody>
	</table>
	<div id="block-order-detail" class="hidden">&nbsp;</div>
	{else}
		<p class="warning">{l s='You have not placed any subscribe.' mod='payline'}</p>
	{/if}
</div>

<ul class="footer_links">
	<li><a href="{$link->getPageLink('my-account', true)}"><img src="{$img_dir}icon/my-account.gif" alt="" class="icon" /></a><a href="{$link->getPageLink('my-account', true)}">{l s='Back to Your Account' mod='payline'}</a></li>
	<li class="f_right"><a href="{$base_dir}"><img src="{$img_dir}icon/home.gif" alt="" class="icon" /></a><a href="{$base_dir}">{l s='Home' mod='payline'}</a></li>
</ul>


<!-- Terminated subscibe //-->
<div style="display:none">
	<div id="terminatedSubscribe">
	<form action="index.php?controller=subscription" method="post">
		<p>{l s='Are you sure you want to unsubscribe?' mod='payline'}</p>
		<p>{l s='What is the reason for unsubscribing?' mod='payline'}</p>
		<p><b>{l s='Subject:' mod='payline'}</b> {l s='Unsubscribe' mod='payline'}</p>
		<p><b>{l s='Message:' mod='payline'}</b></p>
		<p>
			<textarea name="message" cols="40" rows="10"></textarea><br/>
			{l s='* This field is not mandatory' mod='payline'}
		</p>
		<input type="hidden" id="paymentRecordId" name="paymentRecordId" value="" /><br/>
		<p>
			<input type="submit" class="button" name="submitUnsubscribeY" value="{l s='Yes' mod='payline'}" />
			<input type="button" class="button" name="submitUnsubscribeN" value="{l s='No' mod='payline'}" onclick="javascript:$.fancybox.close();" />
		</p>
	</form>
	</div>
</div>

<!-- Suspend subscibe //-->
<div style="display:none">
	<div id="suspendSubscribe">
	<form action="index.php?controller=subscription" method="post">
		<p>{l s='Are you sure you want to suspend your subscription?' mod='payline'}</p>
		<p><b><span id="presentDate"></span></b> {l s='The deadline will not be charged' mod='payline'}</p>
		<p>{l s='The removal of your next deadline will be' mod='payline'} <b><span id="nextSubscribeDate"></span></b></p>
		{if $paylineNumberPending}
		<p><i>{l s='Warning: you can suspend your subscription' mod='payline'} {$paylineNumberPending} {l s='times per year (1 January to 31 December)' mod='payline'}
		<br/>
		<b>{l s='This year you\'ve suspended your subscription' mod='payline'} <span id="numberPending"></span> {l s='times this year' mod='payline'}</b>
		</i></p>
		{/if}
		<input type="hidden" id="paymentRecordIdPending" name="paymentRecordId" value="" />
		<input type="hidden" id="dateStart" name="dateStart" value="" /><br/>
		<p>
			<input type="submit" class="button" name="submitSuspendY" id="submitSuspendYButton" value="{l s='Yes' mod='payline'}" />
			<input type="button" class="button" name="submitUnsubscribeN" value="{l s='No' mod='payline'}" onclick="javascript:$.fancybox.close();" />
		</p>
	</form>
	</div>
</div>
<script type="text/javascript">
function displayTab(tabId,action) {

		if (action == 'show')
			$("#"+tabId).show("normal");
		else
			$("#"+tabId).hide("normal");
}
</script>

<script>
	{literal}
	$(document).ready(function() {
    	$("a.terminated").fancybox({
    		'hideOnContentClick': false,
    		'hideOnOverlayClick': false,
    		'showCloseButton'	: true,
        	'width'             : '80%',
        	'height'            : '100%',
        	'autoScale'         : true,
        	'transitionIn'      : 'elastic',
        	'transitionOut'     : 'elastic',
        	'type'              : 'inline'
    		});
		});

	$("a.terminated").click(function() {
		$("#paymentRecordId").val($(this).attr("id"));
	});

	$(document).ready(function() {
    	$("a.suspended").fancybox({
    		'hideOnContentClick': false,
    		'hideOnOverlayClick': false,
    		'showCloseButton'	: true,
        	'width'             : '75%',
        	'height'            : '100%',
        	'autoScale'         : false,
        	'transitionIn'      : 'elastic',
        	'transitionOut'     : 'elastic',
        	'type'              : 'inline'
    		});
		});

	$("a.suspended").click(function() {
		var data = $(this).attr("id").split("|");
		$("#paymentRecordIdPending").val(data[0]);
		$("#dateStart").val(data[2]);
		$("#presentDate").text(data[1]);
		$("#nextSubscribeDate").text(data[2]);
		$("#numberPending").text(data[4]);

		if((parseInt(data[3]) > 0) && (parseInt(data[4]) >= parseInt(data[3])))
			$("#submitSuspendYButton").css("display","none");
		else
			$("#submitSuspendYButton").css("display","");
	});
	{/literal}
	</script>