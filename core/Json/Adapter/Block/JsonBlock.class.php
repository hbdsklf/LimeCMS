<?php

/**
 * JSON Adapter for Block module
 * @copyright   Comvation AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     contrexx
 * @subpackage  core_json
 */

namespace Cx\Core\Json\Adapter\Block;
use \Cx\Core\Json\JsonAdapter;

/**
 * JSON Adapter for Block module
 * @copyright   Comvation AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     contrexx
 * @subpackage  core_json
 */
class JsonBlock implements JsonAdapter {
    /**
     * List of messages
     * @var Array 
     */
    private $messages = array();
    
    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'block';
    }
    
    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('getBlocks');
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return implode('<br />', $this->messages);
    }
    
    /**
     * Returns all available blocks for each language
     * @return array List of blocks (lang => id )
     */
    public function getBlocks() {
        global $objInit;
        
        if (!\FWUser::getFWUserObject()->objUser->login() || $objInit->mode != 'backend') {
            throw new \Exception($_CORELANG['TXT_ACCESS_DENIED_DESCRIPTION']);
        }
        
        $blockLib = new \blockLibrary();
        $blocks = $blockLib->getBlocks();
        $data = array();
        foreach ($blocks as $id=>$block) {
            $data[$id] = $block['name'];
        }
        return $data;
    }
}
