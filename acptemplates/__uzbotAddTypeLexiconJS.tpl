$('.lexicon_new, .lexicon_modification, .lexicon_change').hide();
$('.lexiconConditionSettings').hide();

if (value == 200) {
	$('.lexicon_change, .uzbotUserConditions').show();
	$('#receiverAffected').show();
}

if (value == 201) {
	$('.lexicon_modification, .lexiconConditionSettings').show();
	$('#receiverAffected, #actionLabelContainer, #conditionLabelContainer').show();
}

if (value == 202) {
	$('.lexicon_new, .uzbotUserConditions').show();
	$('#receiverAffected').show();
}
