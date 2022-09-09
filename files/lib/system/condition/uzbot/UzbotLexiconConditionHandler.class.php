<?php
namespace lexicon\system\condition\uzbot;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\SingletonFactory;

/**
 * Handles bot conditions.
 *
 * @author		2019-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3.lexicon
 */
class UzbotLexiconConditionHandler extends SingletonFactory {
	/**
	 * list of grouped user group / inactive assignment condition object types
	 */
	protected $groupedObjectTypes = [];

	/**
	 * Returns the list of grouped user group / inactive assignment condition object types.
	 */
	public function getGroupedObjectTypes() {
		return $this->groupedObjectTypes;
	}
	
	/**
	 * @inheritDoc
	 */
	protected function init() {
		$objectTypes = ObjectTypeCache::getInstance()->getObjectTypes('com.uz.wcf.bot.condition.lexicon');
		
		foreach ($objectTypes as $objectType) {
			if (!$objectType->conditiongroup) continue;
			
			if (!isset($this->groupedObjectTypes[$objectType->conditiongroup])) {
				$this->groupedObjectTypes[$objectType->conditiongroup] = [];
			}
			
			$this->groupedObjectTypes[$objectType->conditiongroup][$objectType->objectTypeID] = $objectType;
		}
	}
}
