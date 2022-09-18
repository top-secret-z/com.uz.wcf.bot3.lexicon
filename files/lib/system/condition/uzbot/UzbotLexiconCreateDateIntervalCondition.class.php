<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace lexicon\system\condition\uzbot;

use InvalidArgumentException;
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
 */
class UzbotLexiconCreateDateIntervalCondition extends AbstractIntegerCondition implements IObjectListCondition
{
    /**
     * @inheritDoc
     */
    protected $label = 'wcf.acp.uzbot.lexicon.condition.createTime';

    /**
     * @inheritDoc
     */
    protected $minValue = 0;

    /**
     * @inheritDoc
     */
    public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData)
    {
        if (!($objectList instanceof EntryList)) {
            throw new InvalidArgumentException("Object list is no instance of '" . EntryList::class . "', instance of '" . \get_class($objectList) . "' given.");
        }

        if (isset($conditionData['greaterThan'])) {
            $objectList->getConditionBuilder()->add('entry.time < ?', [TIME_NOW - $conditionData['greaterThan'] * 86400]);
        }
        if (isset($conditionData['lessThan'])) {
            $objectList->getConditionBuilder()->add('entry.time > ?', [TIME_NOW - $conditionData['lessThan'] * 86400]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getIdentifier()
    {
        return 'lexicon_createDateInterval';
    }

    /**
     * @inheritDoc
     */
    protected function getLabel()
    {
        return WCF::getLanguage()->get('wcf.acp.uzbot.lexicon.condition.create');
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        if ($this->lessThan !== null) {
            if ($this->getMinValue() !== null && $this->lessThan <= $this->getMinValue()) {
                $this->errorMessage = 'wcf.condition.lessThan.error.minValue';

                throw new UserInputException('lessThan', 'minValue');
            } elseif ($this->getMaxValue() !== null && $this->lessThan > $this->getMaxValue()) {
                $this->errorMessage = 'wcf.condition.lessThan.error.maxValue';

                throw new UserInputException('lessThan', 'maxValue');
            }
        }
        if ($this->greaterThan !== null) {
            if ($this->getMinValue() !== null && $this->greaterThan < $this->getMinValue()) {
                $this->errorMessage = 'wcf.condition.greaterThan.error.minValue';

                throw new UserInputException('greaterThan', 'minValue');
            } elseif ($this->getMaxValue() !== null && $this->greaterThan >= $this->getMaxValue()) {
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
