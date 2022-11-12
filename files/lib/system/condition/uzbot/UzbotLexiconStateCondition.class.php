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
use wcf\data\DatabaseObject;
use wcf\data\DatabaseObjectList;
use wcf\system\condition\AbstractSingleFieldCondition;
use wcf\system\condition\IContentCondition;
use wcf\system\condition\IObjectCondition;
use wcf\system\condition\IObjectListCondition;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Condition implementation for the state of an entry.
 */
class UzbotLexiconStateCondition extends AbstractSingleFieldCondition implements IContentCondition, IObjectCondition, IObjectListCondition
{
    /**
     * @inheritDoc
     */
    protected $label = 'wcf.acp.uzbot.lexicon.condition.state';

    /**
     * values of the possible state
     */
    public $states = [
        'isDisabled' => 0,
        'isEnabled' => 0,
        'isCompleted' => 0,
        'isNotCompleted' => 0,
        'hasLabels' => 0,
        'hasNoLabels' => 0,
        'isEdited' => 0,
        'isNotEdited' => 0,
    ];

    /**
     * @inheritDoc
     */
    public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData)
    {
        if (!($objectList instanceof EntryList)) {
            throw new InvalidArgumentException("Object list is no instance of '" . EntryList::class . "', instance of '" . \get_class($objectList) . "' given.");
        }

        if (isset($conditionData['isDisabled'])) {
            $objectList->getConditionBuilder()->add('entry.isDisabled = ?', [$conditionData['isDisabled']]);
        }

        if (isset($conditionData['isCompleted'])) {
            $objectList->getConditionBuilder()->add('entry.isCompleted = ?', [$conditionData['isCompleted']]);
        }

        if (isset($conditionData['hasLabels'])) {
            $objectList->getConditionBuilder()->add('entry.hasLabels = ?', [$conditionData['hasLabels']]);
        }

        if (isset($conditionData['isEdited'])) {
            if ($conditionData['isEdited']) {
                $objectList->getConditionBuilder()->add('entry.lastEditTime > ?', [0]);
            } else {
                $objectList->getConditionBuilder()->add('entry.lastEditTime = ?', [0]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function checkObject(DatabaseObject $object, array $conditionData)
    {
        if (!($object instanceof Entry) && (!($object instanceof DatabaseObjectDecorator) || !($object->getDecoratedObject() instanceof Entry))) {
            throw new InvalidArgumentException("Object is no (decorated) instance of '" . Entry::class . "', instance of '" . \get_class($object) . "' given.");
        }

        $simpleStates = ['isDisabled', 'isCompleted', 'hasLabels', 'isEdited'];
        foreach ($simpleStates as $state) {
            if (isset($conditionData[$state]) && $object->{$state} != $conditionData[$state]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        $data = [];

        if ($this->states['isDisabled']) {
            $data['isDisabled'] = 1;
        } elseif ($this->states['isEnabled']) {
            $data['isDisabled'] = 0;
        }

        if ($this->states['isCompleted']) {
            $data['isCompleted'] = 1;
        } elseif ($this->states['isNotCompleted']) {
            $data['isCompleted'] = 0;
        }

        if ($this->states['hasLabels']) {
            $data['hasLabels'] = 1;
        } elseif ($this->states['hasNoLabels']) {
            $data['hasLabels'] = 0;
        }

        if ($this->states['isEdited']) {
            $data['isEdited'] = 1;
        } elseif ($this->states['isNotEdited']) {
            $data['isEdited'] = 0;
        }

        if (!empty($data)) {
            return $data;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function getFieldElement()
    {
        $fieldElement = '';

        foreach ($this->states as $state => $value) {
            $fieldElement .= '<label><input type="checkbox" name="uzbotLexiconEntry' . \ucfirst($state) . '" value="1"' . ($value ? ' checked' : '') . '> ' . WCF::getLanguage()->get('wcf.acp.uzbot.lexicon.entry.condition.state.' . $state) . "</label>\n";
        }

        return $fieldElement;
    }

    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        foreach ($this->states as $state => &$value) {
            if (isset($_POST['uzbotLexiconEntry' . \ucfirst($state)])) {
                $value = 1;
            }
        }
        unset($value);
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        foreach ($this->states as $state => $value) {
            $this->states[$state] = 0;
        }
    }

    /**
     * @inheritDoc
     */
    public function setData(Condition $condition)
    {
        $isDisabled = $condition->isDisabled;
        if ($isDisabled !== null) {
            $this->states['isDisabled'] = $isDisabled;
            $this->states['isEnabled'] = 1 - $isDisabled;
        }

        $isCompleted = $condition->isCompleted;
        if ($isCompleted !== null) {
            $this->states['isCompleted'] = $isCompleted;
            $this->states['isNotCompleted'] = 1 - $isCompleted;
        }

        $hasLabels = $condition->hasLabels;
        if ($hasLabels !== null) {
            $this->states['hasLabels'] = $hasLabels;
            $this->states['hasNoLabels'] = 1 - $hasLabels;
        }

        $isEdited = $condition->isEdited;
        if ($isEdited !== null) {
            $this->states['isEdited'] = $isEdited;
            $this->states['isNotEdited'] = 1 - $isEdited;
        }
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        if ($this->states['isDisabled'] && $this->states['isEnabled']) {
            $this->errorMessage = 'wcf.acp.uzbot.lexicon.entry.condition.state.isDisabled.error.conflict';

            throw new UserInputException('isDisabled', 'conflict');
        }

        if ($this->states['isCompleted'] && $this->states['isNotCompleted']) {
            $this->errorMessage = 'wcf.acp.uzbot.lexicon.entry.condition.state.isCompleted.error.conflict';

            throw new UserInputException('isCompleted', 'conflict');
        }

        if ($this->states['hasLabels'] && $this->states['hasNoLabels']) {
            $this->errorMessage = 'wcf.acp.uzbot.lexicon.entry.condition.state.hasLabels.error.conflict';

            throw new UserInputException('hasLabels', 'conflict');
        }

        if ($this->states['isEdited'] && $this->states['isNotEdited']) {
            $this->errorMessage = 'wcf.acp.uzbot.lexicon.entry.condition.state.isEdited.error.conflict';

            throw new UserInputException('isEdited', 'conflict');
        }
    }

    /**
     * @inheritDoc
     */
    public function showContent(Condition $condition)
    {
        // don't need it
        return null;
    }
}
