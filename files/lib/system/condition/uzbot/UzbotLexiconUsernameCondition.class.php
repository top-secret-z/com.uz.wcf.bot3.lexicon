<?php
namespace lexicon\system\condition\uzbot;
use lexicon\data\entry\Entry;
use wcf\system\condition\AbstractObjectTextPropertyCondition;
use wcf\system\WCF;

/**
 * Condition implementation for the name of the user who created an entry.
 * 
 * @author		2019-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3.lexicon
 */
class UzbotLexiconUsernameCondition extends AbstractObjectTextPropertyCondition {
	/**
	 * @inheritDoc
	 */
	protected $className = Entry::class;
	
	/**
	 * @inheritDoc
	 */
	protected $label = 'wcf.acp.uzbot.lexicon.condition.username';
	protected $description = 'wcf.acp.uzbot.lexicon.condition.username.description';
	
	/**
	 * @inheritDoc
	 */
	protected $fieldName = 'lexiconEntryUsername';
	
	/**
	 * @inheritDoc
	 */
	protected $propertyName = 'username';
	
	/**
	 * @inheritDoc
	 */
	protected $supportsMultipleValues = true;
}
