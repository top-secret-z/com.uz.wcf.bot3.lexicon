<script data-relocate="true">
	require(['WoltLabSuite/Core/Ui/User/Search/Input'], function(UiUserSearchInput) {
		new UiUserSearchInput(elBySel('input[name="lexiconModificationExecuter"]'));
	});
</script>

<div class="section lexicon_new">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
	</header>
</div>

<div class="section lexicon_change">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
	</header>
	
	<dl{if $errorField == 'lexiconChangeAction'} class="formError"{/if}>
		<dt>{lang}wcf.acp.uzbot.lexicon.entryChange.action{/lang}</dt>
		<dd>
			<label><input type="checkbox" name="lexiconChangeUpdate" value="1"{if $lexiconChangeUpdate} checked{/if}> {lang}wcf.acp.uzbot.lexicon.entryChange.update{/lang}</label>
			<label><input type="checkbox" name="lexiconChangeDelete" value="1"{if $lexiconChangeDelete} checked{/if}> {lang}wcf.acp.uzbot.lexicon.entryChange.delete{/lang}</label>
			
			{if $errorField == 'lexiconChangeAction'}
				<small class="innerError">
					{lang}wcf.acp.uzbot.lexicon.entryChange.action.error.notConfigured{/lang}
				</small>
			{/if}
		</dd>
	</dl>
</div>

<div class="section lexicon_modification">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}wcf.acp.uzbot.lexicon.entryModification.setting{/lang}</h2>
	</header>
	
	<dl{if $errorField == 'lexiconModificationExecuter'} class="formError"{/if}>
		<dt><label for="lexiconModificationExecuter">{lang}wcf.acp.uzbot.lexicon.entryModification.executer{/lang}</label></dt>
		<dd>
			<input type="text" id="lexiconModificationExecuter" name="lexiconModificationExecuter" value="{$lexiconModificationExecuter}" class="medium" maxlength="255">
			<small>{lang}wcf.acp.uzbot.lexicon.entryModification.executer.description{/lang}</small>
			
			{if $errorField == 'lexiconModificationExecuter'}
				<small class="innerError">
					{if $errorField == 'lexiconModificationExecuter'}
						{lang}wcf.acp.uzbot.lexicon.entryModification.executer.error.{@$errorType}{/lang}
					{/if}
				</small>
			{/if}
		</dd>
	</dl>
	
	<dl{if $errorField == 'lexiconModificationAction'} class="formError"{/if}>
		<dt>{lang}wcf.acp.uzbot.lexicon.entryModification.action{/lang}</dt>
		<dd>
			<label><input type="checkbox" name="lexiconModificationEnable" value="1"{if $lexiconModificationEnable} checked{/if}> {lang}wcf.acp.uzbot.lexicon.entryModification.enable{/lang}</label>
			<label><input type="checkbox" name="lexiconModificationDisable" value="1"{if $lexiconModificationDisable} checked{/if}> {lang}wcf.acp.uzbot.lexicon.entryModification.disable{/lang}</label>
			<label><input type="checkbox" name="lexiconModificationComplete" value="1"{if $lexiconModificationComplete} checked{/if}> {lang}wcf.acp.uzbot.lexicon.entryModification.complete{/lang}</label>
			<label><input type="checkbox" name="lexiconModificationUncomplete" value="1"{if $lexiconModificationUncomplete} checked{/if}> {lang}wcf.acp.uzbot.lexicon.entryModification.uncomplete{/lang}</label>
			{if $labelGroups|count && $availableLabels|count}
				<label><input type="checkbox" name="lexiconModificationSetLabel" value="1"{if $lexiconModificationSetLabel} checked{/if}> {lang}wcf.acp.uzbot.lexicon.entryModification.setLabels{/lang}</label>
			{/if}
			
			{if $errorField == 'lexiconModificationAction'}
				<small class="innerError">
					{lang}wcf.acp.uzbot.lexicon.entryModification.action.error.notConfigured{/lang}
				</small>
			{/if}
		</dd>
	</dl>
</div>

<div class="section lexiconConditionSettings">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}wcf.acp.uzbot.lexicon.entry.condition{/lang}</h2>
		<p class="sectionDescription">{lang}wcf.acp.uzbot.lexicon.entry.condition.description{/lang}</p>
	</header>
	
	<section>
		{foreach from=$lexiconConditions key='conditionGroup' item='conditionObjectTypes'}
			<div id="lexicon_{$conditionGroup}">
				<section class="section">
					{foreach from=$conditionObjectTypes item='condition'}
						{@$condition->getProcessor()->getHtml()}
					{/foreach}
				</section>
			</div>
		{/foreach}
		
		<dl>
			<dt><label for="lexiconModificationCategoryID">{lang}wcf.acp.uzbot.lexicon.entryModification.category{/lang}</label></dt>
			<dd>
				<select name="lexiconModificationCategoryID" id="lexiconModificationCategoryID">
					<option value="0">{lang}wcf.global.noSelection{/lang}</option>
					
					{foreach from=$lexiconCategoryNodeList item=category}
						<option value="{@$category->categoryID}"{if $category->categoryID == $lexiconModificationCategoryID} selected{/if}>{if $category->getDepth() > 1}{@"&nbsp;&nbsp;&nbsp;&nbsp;"|str_repeat:($category->getDepth() - 1)}{/if}{$category->getTitle()}</option>
					{/foreach}
				</select>
			</dd>
		</dl>
	</section>
</div>
