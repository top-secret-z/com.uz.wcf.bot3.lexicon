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

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\SingletonFactory;

/**
 * Handles bot conditions.
 */
class UzbotLexiconConditionHandler extends SingletonFactory
{
    /**
     * list of grouped user group / inactive assignment condition object types
     */
    protected $groupedObjectTypes = [];

    /**
     * Returns the list of grouped user group / inactive assignment condition object types.
     */
    public function getGroupedObjectTypes()
    {
        return $this->groupedObjectTypes;
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $objectTypes = ObjectTypeCache::getInstance()->getObjectTypes('com.uz.wcf.bot.condition.lexicon');

        foreach ($objectTypes as $objectType) {
            if (!$objectType->conditiongroup) {
                continue;
            }

            if (!isset($this->groupedObjectTypes[$objectType->conditiongroup])) {
                $this->groupedObjectTypes[$objectType->conditiongroup] = [];
            }

            $this->groupedObjectTypes[$objectType->conditiongroup][$objectType->objectTypeID] = $objectType;
        }
    }
}
