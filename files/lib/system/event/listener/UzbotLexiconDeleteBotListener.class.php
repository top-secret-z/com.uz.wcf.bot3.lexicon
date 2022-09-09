<?php
namespace lexicon\system\event\listener;
use wcf\system\condition\ConditionHandler;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Listen to Uzbot deletion for conditions
 *  
 * @author		2019-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3.lexicon
 */
class UzbotLexiconDeleteBotListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// check module
		if (!MODULE_UZBOT) return;
		
		// only delete
		if ($eventObj->getActionName() != 'delete') return;
		
		$botIDs = $eventObj->getObjectIDs();
		if (!empty($botIDs)) {
			$oldConditions = ConditionHandler::getInstance()->deleteConditions('com.uz.wcf.bot.condition.lexicon', $botIDs);
		}
	}
}
