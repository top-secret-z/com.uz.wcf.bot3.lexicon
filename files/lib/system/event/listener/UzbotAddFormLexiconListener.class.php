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
namespace lexicon\system\event\listener;

use lexicon\data\category\LexiconCategoryNodeTree;
use lexicon\system\condition\uzbot\UzbotLexiconConditionHandler;
use wcf\data\user\User;
use wcf\data\uzbot\notification\UzbotNotify;
use wcf\data\uzbot\type\UzbotType;
use wcf\system\condition\ConditionHandler;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

/**
 * Listen to addForm events for Bot
 */

class UzbotAddFormLexiconListener implements IParameterizedEventListener
{
    /**
     * instance of UzbotAddForm
     */
    protected $eventObj;

    /**
     * lexicon data
     */
    protected $lexiconModificationData = 0;

    protected $lexiconModificationExecuter = '';

    protected $lexiconModificationExecuterID = 0;

    protected $lexiconModificationCategoryID = 0;

    protected $lexiconModificationDisable = 0;

    protected $lexiconModificationEnable = 0;

    protected $lexiconModificationComplete = 0;

    protected $lexiconModificationUncomplete = 0;

    protected $lexiconModificationSetLabel = 0;

    protected $lexiconModificationTrash = 0;

    /**
     * further bot data
     */
    protected $lexiconCountAction = 'entryTotal';

    protected $topLexiconCount = 1;

    protected $topLexiconInterval = 1;

    protected $lexiconChangeUpdate = 1;

    protected $lexiconChangeDelete = 1;

    /**
     * condition data
     */
    public $lexiconConditions = [];

    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $this->eventObj = $eventObj;
        $this->{$eventName}();
    }

    /**
     * Handles the readData event. Only in UzbotEdit!
     */
    protected function readData()
    {
        if (empty($_POST)) {
            if (!empty($this->eventObj->uzbot->lexiconModificationData)) {
                $lexiconModificationData = \unserialize($this->eventObj->uzbot->lexiconModificationData);
                $this->lexiconModificationExecuter = $lexiconModificationData['lexiconModificationExecuter'];
                $this->lexiconModificationExecuterID = $lexiconModificationData['lexiconModificationExecuterID'];
                $this->lexiconModificationCategoryID = $lexiconModificationData['lexiconModificationCategoryID'];
                $this->lexiconModificationDisable = $lexiconModificationData['lexiconModificationDisable'];
                $this->lexiconModificationEnable = $lexiconModificationData['lexiconModificationEnable'];
                $this->lexiconModificationComplete = $lexiconModificationData['lexiconModificationComplete'];
                $this->lexiconModificationUncomplete = $lexiconModificationData['lexiconModificationUncomplete'];
                $this->lexiconModificationSetLabel = $lexiconModificationData['lexiconModificationSetLabel'];
            }

            $this->lexiconCountAction = $this->eventObj->uzbot->lexiconCountAction;
            $this->topLexiconCount = $this->eventObj->uzbot->topLexiconCount;
            $this->topLexiconInterval = $this->eventObj->uzbot->topLexiconInterval;
            $this->lexiconChangeUpdate = $this->eventObj->uzbot->lexiconChangeUpdate;
            $this->lexiconChangeDelete = $this->eventObj->uzbot->lexiconChangeDelete;

            // conditions
            $this->lexiconConditions = UzbotLexiconConditionHandler::getInstance()->getGroupedObjectTypes();
            $conditions = ConditionHandler::getInstance()->getConditions('com.uz.wcf.bot.condition.lexicon', $this->eventObj->botID);

            foreach ($conditions as $condition) {
                $this->lexiconConditions[$condition->getObjectType()->conditiongroup][$condition->objectTypeID]->getProcessor()->setData($condition);
            }
        }
    }

    /**
     * Handles the assignVariables event.
     */
    protected function assignVariables()
    {
        $this->lexiconConditions = UzbotLexiconConditionHandler::getInstance()->getGroupedObjectTypes();

        $categoryTree = new LexiconCategoryNodeTree('com.viecode.lexicon.category');
        $categoryNodeList = $categoryTree->getIterator();

        WCF::getTPL()->assign([
            'lexiconCountAction' => $this->lexiconCountAction,
            'topLexiconCount' => $this->topLexiconCount,
            'topLexiconInterval' => $this->topLexiconInterval,
            'lexiconChangeUpdate' => $this->lexiconChangeUpdate,
            'lexiconChangeDelete' => $this->lexiconChangeDelete,

            'lexiconCategoryNodeList' => $categoryNodeList,

            'lexiconModificationExecuter' => $this->lexiconModificationExecuter,
            'lexiconModificationExecuterID' => $this->lexiconModificationExecuterID,
            'lexiconModificationCategoryID' => $this->lexiconModificationCategoryID,
            'lexiconModificationDisable' => $this->lexiconModificationDisable,
            'lexiconModificationEnable' => $this->lexiconModificationEnable,
            'lexiconModificationComplete' => $this->lexiconModificationComplete,
            'lexiconModificationUncomplete' => $this->lexiconModificationUncomplete,
            'lexiconModificationSetLabel' => $this->lexiconModificationSetLabel,

            'lexiconConditions' => $this->lexiconConditions,
        ]);
    }

    /**
     * Handles the readFormParameters event.
     */
    protected function readFormParameters()
    {
        if (isset($_POST['lexiconCountAction'])) {
            $this->lexiconCountAction = StringUtil::trim($_POST['lexiconCountAction']);
        }
        if (isset($_POST['topLexiconCount'])) {
            $this->topLexiconCount = \intval($_POST['topLexiconCount']);
        }
        if (isset($_POST['topLexiconInterval'])) {
            $this->topLexiconInterval = \intval($_POST['topLexiconInterval']);
        }
        $this->lexiconChangeUpdate = $this->lexiconChangeDelete = 0;
        if (isset($_POST['lexiconChangeUpdate'])) {
            $this->lexiconChangeUpdate = \intval($_POST['lexiconChangeUpdate']);
        }
        if (isset($_POST['lexiconChangeDelete'])) {
            $this->lexiconChangeDelete = \intval($_POST['lexiconChangeDelete']);
        }

        $this->lexiconModificationDisable = 0;
        $this->lexiconModificationEnable = $this->lexiconModificationComplete = 0;
        $this->lexiconModificationUncomplete = $this->lexiconModificationSetLabel = 0;
        if (isset($_POST['lexiconModificationExecuter'])) {
            $this->lexiconModificationExecuter = StringUtil::trim($_POST['lexiconModificationExecuter']);
        }
        if (isset($_POST['lexiconModificationExecuterID'])) {
            $this->lexiconModificationExecuterID = \intval($_POST['lexiconModificationExecuterID']);
        }
        if (isset($_POST['lexiconModificationCategoryID'])) {
            $this->lexiconModificationCategoryID = \intval($_POST['lexiconModificationCategoryID']);
        }
        if (isset($_POST['lexiconModificationDisable'])) {
            $this->lexiconModificationDisable = \intval($_POST['lexiconModificationDisable']);
        }
        if (isset($_POST['lexiconModificationEnable'])) {
            $this->lexiconModificationEnable = \intval($_POST['lexiconModificationEnable']);
        }
        if (isset($_POST['lexiconModificationComplete'])) {
            $this->lexiconModificationComplete = \intval($_POST['lexiconModificationComplete']);
        }
        if (isset($_POST['lexiconModificationUncomplete'])) {
            $this->lexiconModificationUncomplete = \intval($_POST['lexiconModificationUncomplete']);
        }
        if (isset($_POST['lexiconModificationSetLabel'])) {
            $this->lexiconModificationSetLabel = \intval($_POST['lexiconModificationSetLabel']);
        }

        $this->lexiconModificationData = [
            'lexiconModificationExecuter' => $this->lexiconModificationExecuter,
            'lexiconModificationExecuterID' => $this->lexiconModificationExecuterID,
            'lexiconModificationCategoryID' => $this->lexiconModificationCategoryID,
            'lexiconModificationDisable' => $this->lexiconModificationDisable,
            'lexiconModificationEnable' => $this->lexiconModificationEnable,
            'lexiconModificationComplete' => $this->lexiconModificationComplete,
            'lexiconModificationUncomplete' => $this->lexiconModificationUncomplete,
            'lexiconModificationSetLabel' => $this->lexiconModificationSetLabel,
        ];

        // read conditions
        $this->lexiconConditions = UzbotLexiconConditionHandler::getInstance()->getGroupedObjectTypes();
        foreach ($this->lexiconConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->readFormParameters();
            }
        }
    }

    /**
     * Handles the validate event.
     */
    protected function validate()
    {
        // Get type / notify data
        $type = UzbotType::getTypeByID($this->eventObj->typeID);
        $notify = UzbotNotify::getNotifyByID($this->eventObj->notifyID);

        // need notify?
        if ($type->needNotify && !$notify->notifyID) {
            throw new UserInputException('notifyID', 'missing');
        }

        // need count for trigger values
        if ($type->needCount && $type->typeTitle == 'lexicon_count') {
            if ($this->lexiconCountAction == 'entryTotal' || $this->lexiconCountAction == 'entryX') {
                $counts = ArrayUtil::trim(\explode(',', $this->eventObj->userCount));
                $counts = ArrayUtil::toIntegerArray($counts);

                if (!\count($counts)) {
                    throw new UserInputException('userCount', 'empty');
                }
            }
        }

        // lexicon_change
        if ($type->typeTitle == 'lexicon_change') {
            if (!$this->lexiconChangeUpdate && !$this->lexiconChangeDelete) {
                throw new UserInputException('lexiconChangeAction', 'notConfigured');
            }
        }

        // entry modification
        if ($type->typeTitle == 'lexicon_modification') {
            // unset change labels if no labels
            if (empty($this->eventObj->labelGroups) || empty($this->eventObj->availableLabels)) {
                $this->lexiconModificationData['lexiconModificationSetLabel'] = 0;
                $this->lexiconModificationSetLabel = 0;
            }

            if (\array_sum($this->lexiconModificationData) == 0) {
                throw new UserInputException('lexiconModificationAction', 'notConfigured');
            }

            // executer must exist
            if (empty($this->lexiconModificationExecuter)) {
                throw new UserInputException('lexiconModificationExecuter');
            }
            $user = User::getUserByUsername($this->lexiconModificationExecuter);
            if (!$user->userID) {
                throw new UserInputException('lexiconModificationExecuter', 'invalid');
            }
            $this->lexiconModificationExecuterID = $user->userID;
            $this->lexiconModificationData['lexiconModificationExecuterID'] = $user->userID;

            // conditions
            foreach ($this->lexiconConditions as $conditions) {
                foreach ($conditions as $condition) {
                    $condition->getProcessor()->validate();
                }
            }
        }
    }

    /**
     * Handles the save event.
     */
    protected function save()
    {
        $this->eventObj->additionalFields = \array_merge($this->eventObj->additionalFields, [
            'lexiconModificationData' => \serialize($this->lexiconModificationData),
            'lexiconCountAction' => $this->lexiconCountAction,
            'topLexiconCount' => $this->topLexiconCount,
            'topLexiconInterval' => $this->topLexiconInterval,
            'topLexiconNext' => 0,
            'lexiconChangeUpdate' => $this->lexiconChangeUpdate,
            'lexiconChangeDelete' => $this->lexiconChangeDelete,
        ]);
    }

    /**
     * Handles the saved event.
     */
    protected function saved()
    {
        // transform conditions array into one-dimensional array and save
        $conditions = [];
        foreach ($this->lexiconConditions as $groupedObjectTypes) {
            $conditions = \array_merge($conditions, $groupedObjectTypes);
        }

        $oldConditions = ConditionHandler::getInstance()->getConditions('com.uz.wcf.bot.condition.lexicon', $this->eventObj->botID);
        ConditionHandler::getInstance()->updateConditions($this->eventObj->botID, $oldConditions, $conditions);
    }
}
