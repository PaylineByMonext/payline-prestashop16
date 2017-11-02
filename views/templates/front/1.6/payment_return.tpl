{if isset($error) AND $error}
	<p>{l s='Your order on' mod='payline'} <span class="bold">{$shop_name}</span> {l s='is not complete.' mod='payline'}
		<br /><br />
		{l s='You have chosen the Payline method.' mod='payline'}
		<br /><br />{l s='For any questions or for further information, please contact our' mod='payline'} <a href="{$base_dir_ssl}contact-form.php">{l s='customer support' mod='payline'}</a>.
	</p>
{else}
	<p>{l s='Your order on' mod='payline'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='payline'}
		<br /><br />
		{l s='You have chosen the Payline method.' mod='payline'}
		<br /><br /><span class="bold">{l s='Your order will be sent very soon.' mod='payline'}</span>
		<br /><br />{l s='For any questions or for further information, please contact our' mod='payline'} <a href="{$base_dir_ssl}contact-form.php">{l s='customer support' mod='payline'}</a>.
	</p>
{/if}