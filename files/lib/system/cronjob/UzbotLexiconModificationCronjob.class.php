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
namespace lexicon\system\cronjob;

use lexicon\data\entry\Entry;
use lexicon\data\entry\EntryAction;
use lexicon\data\entry\EntryEditor;
use lexicon\data\entry\EntryList;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\User;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\UzbotEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\condition\ConditionHandler;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\label\LabelHandler;
use wcf\system\label\object\UzbotActionLabelObjectHandler;
use wcf\system\label\object\UzbotConditionLabelObjectHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Lexicon modification cronjob for Bot.
 */
class UzbotLexiconModificationCronjob extends AbstractCronjob
{
    /**
     * list with entries to be modified
     */
    protected $entryList;

    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        if (!MODULE_UZBOT) {
            return;
        }

        // Read all active, valid bots, abort if none
        $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'lexicon_modification']);
        if (empty($bots)) {
            return;
        }

        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        // Step through all bots and get entries to be modified
        foreach ($bots as $bot) {
            // set data
            $modifications = \unserialize($bot->lexiconModificationData);
            $userData = [];

            // check executer
            $user = new User($modifications['lexiconModificationExecuterID']);
            if (!$user->userID) {
                $editor = new UzbotEditor($bot);
                $editor->update(['isDisabled' => 1]);
                UzbotEditor::resetCache();

                if ($bot->enableLog) {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => 0,
                        'status' => 2,
                        'additionalData' => $defaultLanguage->get('wcf.acp.uzbot.lexicon.error.executerInvalid'),
                    ]);
                }
                continue;
            }

            // change user
            $oldUser = WCF::getUser();
            WCF::getSession()->changeUser(new User($modifications['lexiconModificationExecuterID']), true);

            // get all entryIDs matching conditions
            $conditions = ConditionHandler::getInstance()->getConditions('com.uz.wcf.bot.condition.lexicon', $bot->botID);
            $conditionEntryIDs = [];
            if (\count($conditions)) {
                $entryList = new EntryList();
                foreach ($conditions as $condition) {
                    $condition->getObjectType()->getProcessor()->addObjectListCondition($entryList, $condition->conditionData);
                }
                $entryList->readObjectIDs();
                $conditionEntryIDs = $entryList->getObjectIDs();
                if (empty($conditionEntryIDs)) {
                    $conditionEntryIDs[] = 0;
                }
            }
            $conditionCount = \count($conditionEntryIDs);

            // same for labels
            $labelEntryIDs = [];
            $useLabels = 0;
            $labels = UzbotConditionLabelObjectHandler::getInstance()->getAssignedLabels([$bot->botID], false);
            if (!empty($labels)) {
                $useLabels = 1;
                $labelIDs = [];
                foreach ($labels as $temp) {
                    foreach ($temp as $labelID => $label) {
                        $labelIDs[] = $labelID;
                    }
                }

                $objectType = LabelHandler::getInstance()->getObjectType('com.viecode.lexicon.entry');
                $entryList = new EntryList();
                foreach ($labelIDs as $labelID) {
                    $entryList->getConditionBuilder()->add('entry.entryID IN (SELECT objectID FROM wcf' . WCF_N . '_label_object WHERE objectTypeID = ? AND labelID = ?)', [$objectType->objectTypeID, $labelID]);
                }
                $entryList->readObjectIDs();
                $labelEntryIDs = $entryList->getObjectIDs();
                if (empty($labelEntryIDs)) {
                    $labelEntryIDs[] = 0;
                }
            }
            $labelCount = \count($labelEntryIDs);

            // category
            $categoryEntryIDs = [];
            if ($modifications['lexiconModificationCategoryID']) {
                $entryList = new EntryList();
                $entryList->getConditionBuilder()->add('entry.entryID IN (SELECT entryID FROM lexicon' . WCF_N . '_entry_to_category WHERE categoryID = ?)', [$modifications['lexiconModificationCategoryID']]);
                $entryList->readObjectIDs();
                $categoryEntryIDs = $entryList->getObjectIDs();
                if (empty($categoryEntryIDs)) {
                    $categoryEntryIDs[] = 0;
                }
            }
            $categoryCount = \count($categoryEntryIDs);

            // merge entryIDs
            if (!$conditionCount && !$labelCount && !$categoryCount) {    // all entries
                $entryList = new EntryList();
                $entryList->readObjectIDs();
                $entryIDs = $entryList->getObjectIDs();
            } elseif ((isset($conditionEntryIDs[0]) && $conditionEntryIDs[0] == 0) || (isset($labelEntryIDs[0]) && $labelEntryIDs[0] == 0) || (isset($categoryEntryIDs[0]) && $categoryEntryIDs[0] == 0)) {
                $entryIDs[0] = 0;
            } else {
                $tempArray[] = $conditionEntryIDs;
                $tempArray[] = $labelEntryIDs;
                $tempArray[] = $categoryEntryIDs;

                $tempArray = \array_filter($tempArray);
                $entryIDs = \array_shift($tempArray);
                foreach ($tempArray as $array) {
                    $entryIDs = \array_intersect($entryIDs, $array);
                }
            }

            // if no entries, log and abort
            $entryCount = \count($entryIDs);

            // log found entries (not action)
            if ($bot->enableLog) {
                if ($entryCount == 1 && isset($entryIDs[0]) && $entryIDs[0] == 0) {
                    $count = 0;
                } else {
                    $count = $entryCount;
                }
                $result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.lexicon.entry.affected', ['count' => $count]);

                if (!$bot->testMode) {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => $count,
                        'additionalData' => $result,
                    ]);
                } else {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => $count,
                        'testMode' => 1,
                        'additionalData' => \serialize(['', '', $result]),
                    ]);
                }
            }

            // abort if no entries
            if ($entryCount == 1 && isset($entryIDs[0]) && $entryIDs[0] == 0) {
                // Reset to old user
                WCF::getSession()->changeUser($oldUser, true);
                continue;
            }

            // get actionLabelIDs and related data
            $actionLabelIDs = [];
            $actionLabels = UzbotActionLabelObjectHandler::getInstance()->getAssignedLabels([$bot->botID], false);
            if (\count($actionLabels)) {
                foreach ($actionLabels as $temp) {
                    foreach ($temp as $label) {
                        $actionLabelIDs[] = $label->labelID;
                    }
                }
            }
            $objectType = LabelHandler::getInstance()->getObjectType('com.viecode.lexicon.entry');
            $labelObjectTypeID = $objectType->objectTypeID;

            // step through entries until at least one entry was modified
            $found = 0;
            for ($i = 0; $i < $entryCount; $i += UZBOT_DATA_LIMIT_LEXICON) {
                $ids = \array_slice($entryIDs, $i, UZBOT_DATA_LIMIT_LEXICON);

                // step through action in sequence of add form
                if ($modifications['lexiconModificationEnable']) {
                    $entryList = new EntryList();
                    $entryList->getConditionBuilder()->add('entry.entryID IN (?)', [$ids]);
                    $entryList->getConditionBuilder()->add('entry.isDisabled = ?', [1]);
                    $entryList->readObjects();
                    $entries = $entryList->getObjects();
                    if (\count($entries)) {
                        $found = 1;
                        $this->executeBot($bot, $entries, 'enable', $defaultLanguage);
                    }
                }

                if ($modifications['lexiconModificationDisable']) {
                    $entryList = new EntryList();
                    $entryList->getConditionBuilder()->add('entry.entryID IN (?)', [$ids]);
                    $entryList->getConditionBuilder()->add('entry.isDisabled = ?', [0]);
                    $entryList->readObjects();
                    $entries = $entryList->getObjects();
                    if (\count($entries)) {
                        $found = 1;
                        $this->executeBot($bot, $entries, 'disable', $defaultLanguage);
                    }
                }

                if ($modifications['lexiconModificationComplete']) {
                    $entryList = new EntryList();
                    $entryList->getConditionBuilder()->add('entry.entryID IN (?)', [$ids]);
                    $entryList->getConditionBuilder()->add('entry.isCompleted = ?', [0]);
                    $entryList->readObjects();
                    $entries = $entryList->getObjects();
                    if (\count($entries)) {
                        $found = 1;
                        $this->executeBot($bot, $entries, 'done', $defaultLanguage);
                    }
                }

                if ($modifications['lexiconModificationUncomplete']) {
                    $entryList = new EntryList();
                    $entryList->getConditionBuilder()->add('entry.entryID IN (?)', [$ids]);
                    $entryList->getConditionBuilder()->add('entry.isCompleted = ?', [1]);
                    $entryList->readObjects();
                    $entries = $entryList->getObjects();
                    if (\count($entries)) {
                        $found = 1;
                        $this->executeBot($bot, $entries, 'undone', $defaultLanguage);
                    }
                }

                if ($modifications['lexiconModificationSetLabel']) {
                    // user wants to delete labels
                    if (!\count($actionLabelIDs)) {
                        $entryList = new EntryList();
                        $entryList->getConditionBuilder()->add('entry.entryID IN (?)', [$ids]);
                        $entryList->getConditionBuilder()->add('entry.hasLabels = ?', [1]);
                        $entryList->readObjects();
                        $entries = $entryList->getObjects();
                        if (\count($entries)) {
                            $found = 1;
                            $this->executeBot($bot, $entries, 'deleteLabels', $defaultLanguage);
                        }
                    }

                    // user wants to add / change labels
                    else {
                        // read actual label assignment and get entries to be modified
                        $assign = $modifyID = [];
                        $conditionBuilder = new PreparedStatementConditionBuilder();
                        $conditionBuilder->add('objectTypeID = ?', [$labelObjectTypeID]);
                        $conditionBuilder->add('objectID IN (?)', [$ids]);
                        $sql = "SELECT     objectID, labelID
                                FROM    wcf" . WCF_N . "_label_object
                                " . $conditionBuilder;
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute($conditionBuilder->getParameters());
                        while ($row = $statement->fetchArray()) {
                            $assign[$row['objectID']][] = $row['labelID'];
                        }

                        $modifyID = [];
                        foreach ($ids as $entryID) {
                            if (!isset($assign[$entryID])) {
                                $modifyID[] = $entryID;
                                continue;
                            }
                            if (\count($assign[$entryID]) != \count($actionLabelIDs)) {
                                $modifyID[] = $entryID;
                                continue;
                            }
                            if (!empty(\array_diff($actionLabelIDs, $assign[$entryID]))) {
                                $modifyID[] = $entryID;
                            }
                        }

                        if (\count($modifyID)) {
                            $entryList = new EntryList();
                            $entryList->getConditionBuilder()->add('entry.entryID IN (?)', [$modifyID]);
                            $entryList->readObjects();
                            $entries = $entryList->getObjects();
                            if (\count($entries)) {
                                $found = 1;
                                $this->executeBot($bot, $entries, 'setLabels', $defaultLanguage, $actionLabels);
                            }
                        }
                    }
                }

                // break if entry was found
                if ($found) {
                    break;
                }
            }

            // Reset to old user
            WCF::getSession()->changeUser($oldUser, true);
        }
    }

    protected function executeBot($bot, $entries, $action, $defaultLanguage, $labels = [])
    {
        $affectedUserIDs = $countToUserID = $placeholders = $entryIDs = $entryToUser = [];

        if (!\count($entries)) {
            if ($bot->enableLog) {
                if (!$bot->testMode) {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => 0,
                        'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.lexicon.entry.modified', [
                            'action' => $defaultLanguage->get('wcf.acp.uzbot.lexicon.entryModification.action.' . $action),
                            'entryIDs' => '',
                        ]),
                    ]);

                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => \count($affectedUserIDs),
                        'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
                            'total' => 0,
                            'userIDs' => '',
                        ]),
                    ]);
                } else {
                    $result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
                        'objects' => 0,
                        'users' => 0,
                        'userIDs' => '',
                    ]);
                    if (\mb_strlen($result) > 64000) {
                        $result = \mb_substr($result, 0, 64000) . ' ...';
                    }
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => \count($entryIDs),
                        'testMode' => 1,
                        'additionalData' => \serialize(['', '', $result]),
                    ]);
                }
            }

            return;
        }

        foreach ($entries as $entry) {
            $entryIDs[] = $entry->entryID;

            if (!$entry->userID) {
                continue;
            }

            $entryToUser[$entry->entryID] = $entry->userID;

            $affectedUserIDs[] = $entry->userID;
            if (isset($countToUserID[$entry->userID])) {
                $countToUserID[$entry->userID]++;
            } else {
                $countToUserID[$entry->userID] = 1;
            }
        }

        $affectedUserIDs = \array_unique($affectedUserIDs);

        // change user for action + execute unless test mode
        if (!$bot->testMode) {
            if ($action != 'setLabels' && $action != 'deleteLabels') {
                $entryAction = new EntryAction($entries, $action);
                $entryAction->executeAction();
            } else {
                // label object type
                $objectType = LabelHandler::getInstance()->getObjectType('com.viecode.lexicon.entry');
                $objectTypeID = $objectType->objectTypeID;

                // delete ...
                if ($action == 'deleteLabels') {
                    $objectTypeID = LabelHandler::getInstance()->getObjectType('com.viecode.lexicon.entry')->objectTypeID;
                    $oldLabels = LabelHandler::getInstance()->getAssignedLabels($objectTypeID, $entryIDs);

                    // remove labels
                    foreach ($entries as $entry) {
                        LabelHandler::getInstance()->setLabels([], $objectTypeID, $entry->entryID);

                        // update hasLabels flag
                        $editor = new EntryEditor($entry);
                        $editor->update(['hasLabels' => 0]);
                    }
                }

                // set ...
                if ($action == 'setLabels') {
                    foreach ($labels as $temp) {
                        foreach ($temp as $label) {
                            $labelIDs[] = $label->labelID;
                        }
                    }
                    $botLabels = $labels;
                    $botLabelIDs = $labelIDs;

                    // almost same as above
                    $objectTypeID = LabelHandler::getInstance()->getObjectType('com.viecode.lexicon.entry')->objectTypeID;
                    $oldLabels = LabelHandler::getInstance()->getAssignedLabels($objectTypeID, $entryIDs);

                    foreach ($entries as $entry) {
                        LabelHandler::getInstance()->setLabels($botLabelIDs, $objectTypeID, $entry->entryID);

                        $editor = new EntryEditor($entry);
                        $editor->update(['hasLabels' => !empty($botLabelIDs) ? 1 : 0]);
                    }
                }
            }
        }

        if ($bot->enableLog) {
            $result1 = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.lexicon.entry.modified', [
                'action' => $defaultLanguage->get('wcf.acp.uzbot.lexicon.entryModification.action.' . $action),
                'entryIDs' => \implode(', ', $entryIDs),
            ]);
            if (\mb_strlen($result1) > 64000) {
                $result1 = \mb_substr($result1, 0, 64000) . ' ...';
            }

            $result2 = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
                'total' => \count($affectedUserIDs),
                'userIDs' => \implode(', ', $affectedUserIDs),
            ]);
            if (\mb_strlen($result2) > 64000) {
                $result2 = \mb_substr($result2, 0, 64000) . ' ...';
            }

            UzbotLogEditor::create([
                'bot' => $bot,
                'testMode' => !$bot->testMode ? 0 : 1,
                'count' => \count($entryIDs),
                'additionalData' => !$bot->testMode ? $result1 : \serialize(['', '', $result1]),
            ]);

            UzbotLogEditor::create([
                'bot' => $bot,
                'testMode' => !$bot->testMode ? 0 : 1,
                'count' => \count($affectedUserIDs),
                'additionalData' => !$bot->testMode ? $result2 : \serialize(['', '', $result2]),
            ]);
        }

        // check for and prepare notification
        if ($bot->notifyID) {
            $notify = $bot->checkNotify(true, true);
            if ($notify === null) {
                return;
            }

            $placeholders['count'] = \count($entryIDs);
            $placeholders['object-ids'] = \implode(', ', $entryIDs);
            $placeholders['action'] = $defaultLanguage->get('wcf.acp.uzbot.lexicon.entryModification.action.' . $action);

            // test mode
            $testUserIDs = $testToUserIDs = [];
            if (\count($affectedUserIDs)) {
                $userID = \reset($affectedUserIDs);
                $testUserIDs[] = $userID;
                $testToUserIDs[$userID] = $countToUserID[$userID];
            }

            // send to scheduler, if not test mode
            if ($bot->testMode) {
                // only one notification
                $data = [
                    'bot' => $bot,
                    'placeholders' => $placeholders,
                    'affectedUserIDs' => !$bot->testMode ? $affectedUserIDs : $testUserIDs,
                    'countToUserID' => !$bot->testMode ? $countToUserID : $testToUserIDs,
                ];

                $job = new NotifyScheduleBackgroundJob($data);
                BackgroundQueueHandler::getInstance()->performJob($job);
            } else {
                $data = [
                    'bot' => $bot,
                    'placeholders' => $placeholders,
                    'affectedUserIDs' => !$bot->testMode ? $affectedUserIDs : $testUserIDs,
                    'countToUserID' => !$bot->testMode ? $countToUserID : $testToUserIDs,
                ];

                $job = new NotifyScheduleBackgroundJob($data);
                BackgroundQueueHandler::getInstance()->performJob($job);
            }
        }
    }
}
