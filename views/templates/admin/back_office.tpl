<script type="text/javascript">
	var pos_select = {$tab};
	var id_language = Number('{$default_id_lang|intval}');
	var paylineRemovePaymentMethodAlert = '{l s='Voulez-vous vraiment supprimer ce moyen de paiement ?' mod='payline' js=1}';
</script>

{if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>=')}
	<div class="toolbarBox">
		<h3>{l s='Avec la solution de paiement en ligne sécurisée Payline, recrutez et fidélisez vos cyber-consommateurs' mod='payline'}</h3>
	</div>
{else}
	<div class="toolbar-placeholder">
		<div class="toolbarBox toolbarHead">
			<div class="pageTitle">
				<h3>
					<span id="current_obj" style="font-weight: normal;">
						<span class="breadcrumb item-0">
							<img src="{$path}img/paylineHeaderLogo.png" width="150" height="55"/>
							<span>&nbsp;&nbsp;{l s='Avec la solution de paiement en ligne sécurisée Payline, recrutez et fidélisez vos cyber-consommateurs' mod='payline'}</span>
						</span>
					</span>
				</h3>
			</div>
		</div>
		<div class="leadin"></div>
	</div>
{/if}

<div id="paylinePresentationBlock" class="paylineHeaderBlock">
	<p>{l s='Simple à installer, ce module vous permet d’intégrer Payline sur votre site et de bénéficier de fonctionnalités inégalées.' mod='payline'}</p>
	<p>
		{l s='Plus de ventes : ' mod='payline'}<em>{l s='paiements en 1 clic, paiement en plusieurs fois, 3D Secure débrayable' mod='payline'}</em><br />
		{l s='Plus de 50 moyens de paiement dont Leetchi, WeXpay, Paysafecard.' mod='payline'}</em>
	</p>
	<p>{l s='Moins de fraudes : un module unique par sa simplicité et son efficacité. Un exemple : soyez alerté sur vos risques avant d’envoyer vos colis !' mod='payline'}</p>
	<p>
		{l s='Sur tous les canaux : Payline est nativement sur mobiles, tablettes, SVI…' mod='payline'}<br />
		{l s='Partout : avec Payline,  vous n’avez plus de frontières !' mod='payline'}
	</p>
	<p>
		{l s='Vous pouvez accéder au meilleur du paiement en ligne à partir de ' mod='payline'}<strong>{l s='15 euros par mois' mod='payline'}</strong><br />
	</p>
	<p>
		{l s='Pour plus d\'infos :' mod='payline'} <a href="http://www.payline.com" target="_blank">http://www.payline.com</a>
	</p>
</div>

<div id="paylineOpenAccountBlock" class="paylineHeaderBlock">
	<p><strong>{l s='Vous possédez un contrat de vente à distance (V.A.D.) auprès de votre banque ?' mod='payline'}</strong></p>
	<p><strong>{l s='N\'attendez plus, et testez notre service immédiatement, et sans aucun engagement :' mod='payline'}</strong></p>
	<p><a target="_blank" href="{l s='http://www.payline.com/index.php/fr/support/tester-payline.html' mod='payline'}" id="paylineCreateAccountButton"><span>{l s='Ouvrez un compte' mod='payline'}</span> {l s='immédiatement et gratuitement' mod='payline'}</a></p>
	<p>
		{l s='Vous avez des questions ?' mod='payline'}<br />
		{l s='Nos chargés de clientèle sont à votre disposition.' mod='payline'}<br />
		{l s='Contactez nous ou faites vous rappeler :' mod='payline'}<br />
		{l s='Mail :' mod='payline'} <a href="mailto:support@payline.com">support@payline.com</a><br />
		{l s='Tel : 04 42 25 15 43' mod='payline'}
	</p>
</div>
<div class="clear"><br /></div>

<form method="post" action="{$request_uri}">
	<script type="text/javascript" src="{$js_dir}jquery/plugins/tabpane/jquery.tabpane.js"></script>
	<link type="text/css" rel="stylesheet" href="{$js_dir}jquery/plugins/tabpane/jquery.tabpane.css" />
	<input type="hidden" name="tabs" id="tabs" value="0" />

	<div class="tab-pane payline-pane" id="tab-pane-1" style="width:100%;">
		<div class="tab-page" id="step1">
			<h4 class="tab">{l s='Configuration' mod='payline'}</h4>
			{if isset($errors) && count($errors)}
				<div class="error">
					<h4>{if $errors|@count > 1}{l s='There are' mod='payline'}{else}{l s='There is' mod='payline'}{/if} {$errors|@count} {if $errors|@count > 1}{l s='server configuration errors' mod='payline'}{else}{l s='server configuration error' mod='payline'}{/if}</h4>
					<ol>
						{foreach from=$errors item=error name=errors}
							<li>{$error}</li>
						{/foreach}
					</ol>
					<p>{l s='Votre administrateur serveur doit corriger ces erreurs afin de pouvoir configurer le module.' mod='payline'}</p>
				</div>
			{/if}
			<div class="configuration-generale">
				<h3>{l s='Configuration générale' mod='payline'}</h3>
				{if isset($api_error) && !empty($api_error)}
					<p class="error">{$api_error}</p>
				{/if}
				{$html_access}
			</div>
			<div class="configuration-proxy">
				<h3>{l s='Configuration proxy' mod='payline'}</h3>
				<p class="info">{l s='Si les connexions depuis vers votre serveur vers le réseau Internet passent par un proxy, renseignez ses informations dans cet onglet. Sinon, laissez tous les champs vides.'}</p>
				{$html_proxy}
			</div>
			<p class="center clear"><input class="button submitPayline" type="submit" name="submitPayline" value="{l s='Save settings' mod='payline'}" /></p>
		</div>
		<div class="{if isset($api_error)}paylineHiddenBlock{else}tab-page{/if}" id="step2">
			<h4 class="tab">{l s='Type of cards' mod='payline'}</h4>
			{if isset($paylineContracts) && is_array($paylineContracts) && count($paylineContracts)}
				<div class="payline-available-contracts-container">
					<p class="info">
						{l s='Sélectionnez les moyens de paiement que vous souhaitez proposer à vos clients dans votre tunnel de commande et cliquez sur le bouton "Ajouter ces moyens de paiement".' mod='payline'}<br /><br />
						{l s='Vous avez la possibilité de proposer des moyens de paiement alternatifs aux clients ayant été confrontés à une erreur de paiement.' mod='payline'}
					</p>
					<div class="payline-available-contracts-list">
						<h3>{l s='Moyens de paiement disponibles :' mod='payline'}<br /><br /></h3>
						<ul>
							{foreach from=$paylineContracts item=paylineContract}
								<li data-id-card="{$paylineContract.id_card}" data-label="{$paylineContract.label|escape:'htmlall':'UTF-8'}">
									<img src="{$paylineContract.logo}" title="{$paylineContract.label|escape:'htmlall':'UTF-8'} - {l s='Contrat :' mod='payline'} {$paylineContract.contract|escape:'htmlall':'UTF-8'}" />
									<span class="payline-contract-label">&nbsp;{$paylineContract.label|escape:'htmlall':'UTF-8'}</span>
									<input type="button" class="button paylineRemoveContract" value="{l s='Supprimer' mod='payline'}" />
								</li>
							{/foreach}
						</ul>
						<br class="clear"></br>
						<p class="center clear"><input disabled="disabled" class="button submitAddNewContract" type="button" name="submitAddNewContract" value="{l s='Ajouter ces moyens de paiement' mod='payline'}" /></p>
						<p class="center clear"><input disabled="disabled" class="button submitAddNewAlternateContract" type="button" name="submitAddNewAlternateContract" value="{l s='Ajouter en tant que moyens de paiement alternatifs' mod='payline'}" /></p>
					</div>
				</div>
				
				<div class="payline-shop-contracts-container">
					<p class="info">{l s='Vous pouvez ordonner l\'ordre d\'apparition des différents moyens de paiement actifs en les faisant glisser de haut en bas.' mod='payline'}</p>
					<div class="payline-primary-contracts-container">
						<div class="payline-primary-contracts-list">
							<h3>{l s='Moyens de paiement proposés dans la boutique :' mod='payline'}<br /><br /></h3>
							<ol>
								{foreach from=$paylineContracts item=paylineContract}
									{if $paylineContract.primary}
										<li data-id-card="{$paylineContract.id_card}" data-label="{$paylineContract.label|escape:'htmlall':'UTF-8'}">
											<img src="{$paylineContract.logo}" title="{$paylineContract.label|escape:'htmlall':'UTF-8'} - {l s='Contrat :' mod='payline'} {$paylineContract.contract|escape:'htmlall':'UTF-8'}" />
											<span class="payline-contract-label">&nbsp;{$paylineContract.label|escape:'htmlall':'UTF-8'}</span>
											<input type="button" class="button paylineRemoveContract" value="{l s='Supprimer' mod='payline'}" />
										</li>
									{/if}
								{/foreach}
							</ol>
						</div>
					</div>
					
					<div class="clear"></div>
					
					<div class="payline-secondary-contracts-container">
						<div class="payline-secondary-contracts-list">
							<h3>{l s='Moyens de paiement alternatifs en cas d\'échec de paiement :' mod='payline'}<br /><br /></h3>
							<ol>
								{foreach from=$paylineContracts item=paylineContract}
									{if $paylineContract.secondary}
										<li data-id-card="{$paylineContract.id_card}" data-label="{$paylineContract.label|escape:'htmlall':'UTF-8'}">
											<img src="{$paylineContract.logo}" title="{$paylineContract.label|escape:'htmlall':'UTF-8'} - {l s='Contrat :' mod='payline'} {$paylineContract.contract|escape:'htmlall':'UTF-8'}" />
											<span class="payline-contract-label">&nbsp;{$paylineContract.label|escape:'htmlall':'UTF-8'}</span>
											<input type="button" class="button paylineRemoveContract" value="{l s='Supprimer' mod='payline'}" />
										</li>
									{/if}
								{/foreach}
							</ol>
						</div>
					</div>
				</div>
				
				<input type="hidden" class="payline-primary-contracts-value" name="paylinePrimaryContractsList" value="" />
				<input type="hidden" class="payline-secondary-contracts-value" name="paylineSecondaryContractsList" value="" />
				
				<p class="center clear"><br /><input class="button submitPayline" type="submit" name="submitPayline" value="{l s='Save settings' mod='payline'}" /></p>
			{else}
				<p class="error">{l s='Aucun contrat n\'est associé à votre compte. Merci de contacter votre chargé de clientèle Payline par mail à support@payline.com, ou par téléphone au 04 42 25 15 43.' mod='payline'}</p>
			{/if}
			<div class="clear"></div>
		</div>
		<div class="{if isset($api_error)}paylineHiddenBlock{else}tab-page{/if} clearfix" id="step3">
			<h4 class="tab">{l s='Payment method' mod='payline'}</h4>
			<p class="info">{l s='Dans cet écran, vous avez la possibilité d\'activer et de configurer les différentes méthodes de paiement que vous souhaitez proposer à vos clients.' mod='payline'}</p>
			{$html_payment}
		</div>
	</div>
	<div class="clear"></div>
	<script type="text/javascript">
		function loadTab(id){}
		setupAllTabs();
	</script>
</form>

<p class="center">{l s='Module version' mod='payline'} : <strong>v{$module_version}</strong></p>