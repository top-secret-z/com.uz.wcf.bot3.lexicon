<?php
namespace lexicon\system\condition\uzbot;
use lexicon\data\entry\Entry;
use lexicon\data\entry\EntryList;
use wcf\data\condition\Condition;
use wcf\data\DatabaseObjectList;
use wcf\system\condition\AbstractIntegerCondition;
use wcf\system\condition\IObjectListCondition;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Condition implementation for an integer to day property of an entry.
 * 
 * @author		2019-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3.lexicon
 */
class UzbotLexiconEditDateIntervalCondition extends AbstractIntegerCondition implements IObjectListCondition {
	/**
	 * @inheritDoc
	 */
	protected $label = 'wcf.acp.uzbot.lexicon.condition.editTime';
	
	/**
	 * @inheritDoc
	 */
	protected $minValue = 0;
	
	/**
	 * @inheritDoc
	 */
	public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData) {
		if (!($objectList instanceof EntryList)) {
			throw new \InvalidArgumentException("Object list is no instance of '".EntryList::class."', instance of '".get_class($objectList)."' given.");
		}
		
		if (isset($conditionData['greaterThan'])) {
			$objectList->getConditionBuilder()->add('entry.lastEditTime > ? AND entry.lastEditTime < ?', [0, TIME_NOW - $conditionData['greaterThan'] * 86400]);
		}
		if (isset($conditionData['lessThan'])) {
			$objectList->getConditionBuilder()->add('entry.lastEditTime > ?', [TIME_NOW - $conditionData['lessThan'] * 86400]);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getIdentifier() {
		return 'lexicon_editDateInterval';
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getLabel() {
		return WCF::getLanguage()->get('wcf.acp.uzbot.lexicon.condition.edit');
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		if ($this->lessThan !== null) {
			if ($this->getMinValue() !== null && $this->lessThan <= $this->getMinValue()) {
				$this->errorMessage = 'wcf.condition.lessThan.error.minValue';
				
				throw new UserInputException('lessThan', 'minValue');
			}
			else if ($this->getMaxValue() !== null && $this->lessThan > $this->getMaxValue()) {
				$this->errorMessage = 'wcf.condition.lessThan.error.maxValue';
				
				throw new UserInputException('lessThan', 'maxValue');
			}
		}
		if ($this->greaterThan !== null) {
			if ($this->getMinValue() !== null && $this->greaterThan < $this->getMinValue()) {
				$this->errorMessage = 'wcf.condition.greaterThan.error.minValue';
				
				throw new UserInputException('greaterThan', 'minValue');
			}
			else if ($this->getMaxValue() !== null && $this->greaterThan >= $this->getMaxValue()) {
				$this->errorMessage = 'wcf.condition.greaterThan.error.maxValue';
				
				throw new UserInputException('greaterThan', 'maxValue');
			}
		}
		
		if ($this->lessThan !== null && $this->greaterThan !== null && $this->greaterThan >= $this->lessThan) {
			$this->errorMessage = 'wcf.condition.greaterThan.error.lessThan';
			
			throw new UserInputException('greaterThan', 'lessThan');
		}
	}
}
