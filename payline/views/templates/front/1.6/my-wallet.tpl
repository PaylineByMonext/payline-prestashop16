<script type="text/javascript">
//<![CDATA[
	var baseDir = '{$base_dir_ssl}';
//]]>
</script>

{capture name=path}<a href="{$link->getPageLink('my-account', true)}">{l s='My account' mod='payline'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='My wallet' mod='payline'}{/capture}

<h1 class="page-heading">{l s='My wallet Payline' mod='payline'}</h1>

<p>{l s='To facilitate your order and avoid the systematic capture of your payment data, they are assigned to the secure solution payline. You can always update or delete them.' mod='payline'}</p>

{if isset($cardData)}
	<div class="cards">
		<p><strong class="dark">{l s='Your cards are listed below.' mod='payline'}</strong></p>
	
		<p class="p-indent">{l s='Be sure to update them if they have changed.' mod='payline'}</p>
	
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
	
		<div class="bloc_cards row">
			{foreach from=$cardData item=card name=myLoop}
				<div class="col-xs-12 col-sm-6 card">
					<ul class="box {if $smarty.foreach.myLoop.last}last_item{elseif $smarty.foreach.myLoop.first}first_item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{else}item{/if}">
						<li class="card_title"><h3 class="page-subheading">{l s='Card' mod='payline'} {$card.cardInd}</h3></li>
						<li><span class="card_key">{l s='Type' mod='payline'}:</span> <span class="card_value">{$card.type}</span></li>
						<li><span class="card_key">{l s='Holder' mod='payline'}:</span> <span class="card_value">{$card.firstName} {$card.lastName}</span></li>
						<li><span class="card_key">{l s='Card number' mod='payline'}:</span> <span class="card_value">{$card.number}</span></li>
						<li><span class="card_key">{l s='Expiration date' mod='payline'}:</span> <span class="card_value-value">{$card.expirationDate}</span></li>
						<li class="card_update">
							{if isset($updateData) AND $updateData}<a class="btn btn-default button button-small" href="{$link->getModuleLink('payline', 'wallet', ['id_card'=>$card.cardInd|intval], true)}" title="{l s='Update' mod='payline'}"><span>{l s='Update' mod='payline'} <i class="icon-chevron-right right"></i></span></a>{/if}
							<a class="btn btn-default button button-small" href="{$link->getModuleLink('payline', 'wallet', ['id_card'=>$card.cardInd|intval, 'delete'=>1], true)}" onclick="return confirm('{l s='Are you sure?' mod='payline'}');" title="{l s='Delete' mod='payline'}"><span>{l s='Delete' mod='payline'} <i class="icon-remove right"></i></span></a>
						</li>
					</ul>
				</div>
			{/foreach}
		</div>
	</div>
{/if}

<!-- If customer hasn't wallet //-->
<div class="clearfix main-page-indent">
	<form method="post" action="{$link->getModuleLink('payline', 'wallet', array(), true)}" class="">
		{if !isset($cardData)}
			<button type="submit" name="createMyWallet" id="createMyWallet" class="btn btn-default button button-medium">
				<span>{l s='Create my wallet' mod='payline'} <i class="icon-chevron-right right"></i></span>
			</button>
		{else}
			<button type="submit" name="createMyWallet" id="createMyWallet" class="btn btn-default button button-medium">
				<span>{l s='Add card' mod='payline'} <i class="icon-chevron-right right"></i></span>
			</button>
			<button type="submit" name="deleteMyWallet" id="deleteMyWallet" class="btn btn-default button button-medium deletewallet" onclick="return confirm('{l s='Are you sure?'}');">
				<span>{l s='Delete my wallet' mod='payline'} <i class="icon-remove right"></i></span>
			</button>
		{/if}
	</form>
</div>

<ul class="footer_links clearfix">
	<li><a class="btn btn-default button button-small" href="{$link->getPageLink('my-account', true)}"><span><i class="icon-chevron-left"> </i> {l s='Back to Your Account' mod='payline'}</span></a></li>
	<li><a class="btn btn-default button button-small" href="{$base_dir}"><span><i class="icon-chevron-left"> </i> {l s='Home' mod='payline'}</span></a></li>
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