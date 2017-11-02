<script type="text/javascript">
//<![CDATA[
	var baseDir = '{$base_dir_ssl}';
//]]>
</script>

{capture name=path}<a href="{$link->getPageLink('my-account', true)}">{l s='My account' mod='payline'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='My wallet' mod='payline'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='My wallet Payline' mod='payline'}</h2>

{l s='To facilitate your order and avoid the systematic capture of your payment data, they are assigned to the secure solution payline. You can always update or delete them.' mod='payline'}
{if isset($cardData)}
<div class="cards clearfix">
	<h3>{l s='Your cards are listed below.' mod='payline'}</h3>
	<p>{l s='Be sure to update them if they have changed.' mod='payline'}</p>
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
	{foreach from=$cardData item=card name=myLoop}
		<ul class="card {if $smarty.foreach.myLoop.last}last_item{elseif $smarty.foreach.myLoop.first}first_item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{else}item{/if}">
			<li class="card_title">{l s='Card' mod='payline'} {$card.cardInd}</li>
			<li class="card_type">{l s='Type' mod='payline'} <b>{$card.type}</b></li>
			<li class="card_name">{l s='Holder' mod='payline'} <b>{$card.firstName} {$card.lastName}</b></li>
			<li class="card_number">{l s='Card number' mod='payline'} <b>{$card.number}</b></li>
			<li class="card_expiration">{l s='Expiration date' mod='payline'} <b>{$card.expirationDate}</b></li>
			{if isset($updateData) AND $updateData}
				<li class="card_update"><a href="{$link->getModuleLink('payline', 'wallet', ['id_card'=>$card.cardInd|intval], true)}" title="{l s='Update' mod='payline'}">{l s='Update' mod='payline'}</a></li>
			{/if}
			<li class="card_delete"><a href="{$link->getModuleLink('payline', 'wallet', ['id_card'=>$card.cardInd|intval, 'delete'=>1], true)}" onclick="return confirm('{l s='Are you sure?' mod='payline'}');" title="{l s='Delete' mod='payline'}">{l s='Delete' mod='payline'}</a></li>
		</ul>
	{/foreach}
	<p class="clear"> </p>
</div>
{/if}

<form method="post" action="{$link->getModuleLink('payline', 'wallet', array(), true)}" class="std clearfix">
<!-- If customer hasn't wallet //-->
{if !isset($cardData)}
<p class="submit">
	<input type="submit" value="{l s='Create my wallet' mod='payline'}" name="createMyWallet" id="createMyWallet" class="button_large" />
</p>
{else}
<p class="submit">
	<input type="submit" value="{l s='Add card' mod='payline'}" name="createMyWallet" id="createMyWallet" class="button_large" />
	<input type="submit" value="{l s='Delete my wallet' mod='payline'}" name="deleteMyWallet" id="deleteMyWallet" class="button_large" onclick="return confirm('{l s='Are you sure?'}');" />
</p>
{/if}
</form>
<ul class="footer_links">
	<li><a href="{$link->getPageLink('my-account', true)}"><img src="{$img_dir}icon/my-account.gif" alt="" class="icon" /></a><a href="{$link->getPageLink('my-account', true)}">{l s='Back to Your Account' mod='payline'}</a></li>
	<li class="f_right"><a href="{$base_dir}"><img src="{$img_dir}icon/home.gif" alt="" class="icon" /></a><a href="{$base_dir}">{l s='Home' mod='payline'}</a></li>
</ul>

{if isset($iframe)}
	<a href="{$iframe}" id="iframe"></a>
	<script>
	{literal}
	$(document).ready(function() {
    	$("#iframe").fancybox({
    		'hideOnContentClick': false,
    		'hideOnOverlayClick': false,
    		'showCloseButton'	: false,
        	'width'             : '75%',
        	'height'            : '100%',
        	'autoScale'         : false,
        	'transitionIn'      : 'elastic',
        	'transitionOut'     : 'elastic',
        	'type'              : 'iframe'
    		}).trigger("click");
		});
	{/literal}
	</script>
{/if}