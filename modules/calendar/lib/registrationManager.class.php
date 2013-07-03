<?php

/**
 * Calendar Class Host Manager
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  module_calendar
 * @todo        Edit PHP DocBlocks!
 */

class CalendarRegistrationManager extends CalendarLibrary {
    private $eventId;
    private $formId;       
    private $getRegistrations;
    private $getDeregistrations;
    private $getWaitlist;
    
    public $registrationList = array();
    
    function __construct($eventId, $getRegistrations=true, $getDeregistrations=false, $getWaitlist=false)
    {   
        $this->eventId = intval($eventId);
        $this->getRegistrations = $getRegistrations;
        $this->getDeregistrations = $getDeregistrations;
        $this->getWaitlist = $getWaitlist;
        
        $objEvent = new CalendarEvent($eventId); 
        $this->formId = $objEvent->registrationForm;                    
    }  
    
    function getRegistrationList()
    {
        global $objDatabase;
        
        $blnFirst = true;
        $arrWhere = array();
        if ($this->getRegistrations)   { $arrWhere[] = 1; }
        if ($this->getDeregistrations) { $arrWhere[] = 0; }
        if ($this->getWaitlist)        { $arrWhere[] = 2; }
        $strWhere = ' AND (';
        foreach ($arrWhere as $value) {
            $strWhere .=  $blnFirst ? '`type` = '.$value : ' OR `type` = '.$value;
            $blnFirst = false;
        }
        $strWhere .= ')';
        
        $query = '
            SELECT `id`
            FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration`
            WHERE `event_id` = '.$this->eventId.'
            '.$strWhere.'
            ORDER BY `id` DESC'
        ;
        $objResult = $objDatabase->Execute($query);
        
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $objRegistration = new CalendarRegistration($this->formId, intval($objResult->fields['id']));
                $this->registrationList[$objResult->fields['id']] = $objRegistration;
                $objResult->MoveNext();
            }
        }
    }
    
    function showRegistrationList($objTpl)
    {
        global $objDatabase, $_LANGID, $_ARRAYLANG;
        
        $objResult = $objDatabase->Execute('SELECT count(`field_id`) AS `count_form_fields` FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name` WHERE `form_id` = '.$this->formId.' AND `lang_id` = '.$_LANGID);
        $objTpl->setVariable($this->moduleLangVar.'_COUNT_FORM_FIELDS', $objResult->fields['count_form_fields'] + 3);
        
        $query = '
            SELECT `n`.`field_id`, `n`.`name`, `f`.`type`
            FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name` AS `n`
            INNER JOIN `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field` AS `f`
            ON `n`.`field_id` = `f`.`id`
            WHERE `n`.`form_id` = '.$this->formId.'
            AND `n`.`lang_id` = '.$_LANGID.'
            ORDER BY `f`.`order`
        ';
        $objResult = $objDatabase->Execute($query);
        
        if ($objResult !== false) {
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', '#');
            $objTpl->parse('eventRegistrationName');
            
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', $_ARRAYLANG['TXT_CALENDAR_DATE']);
            $objTpl->parse('eventRegistrationName');
            
            while (!$objResult->EOF) {
                if ($objResult->fields['type'] != 'agb') {
                    $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', $objResult->fields['name']);
                    $objTpl->parse('eventRegistrationName');
                }
                $objResult->MoveNext();
            }
            
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', $_ARRAYLANG['TXT_CALENDAR_PAYMENT_METHOD']);
            $objTpl->parse('eventRegistrationName');
        }
        
        $query = '
            SELECT `v`.`reg_id`, `v`.`field_id`, `v`.`value`, `n`.`default`, `f`.`type`
            FROM (`'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value` AS `v`
            INNER JOIN `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name` AS `n`
            ON `v`.`field_id` = `n`.`field_id`)
            INNER JOIN `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field` AS `f`
            ON `v`.`field_id` = `f`.`id`
            WHERE `n`.`lang_id` = '.$_LANGID.'
            ORDER BY `f`.`order`
        ';
        $objResult = $objDatabase->Execute($query);
        
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if ($objResult->fields['type'] != 'agb') {
                    $value = '';
                    if (!empty($objResult->fields['default'])) {
                        $arrDefaultValues = explode(',', $objResult->fields['default']);
                        $value = $arrDefaultValues[$objResult->fields['value'] - 1];
                    } else {
                        $value = $objResult->fields['value'];
                    }
                    $arrValues[$objResult->fields['reg_id']][$objResult->fields['field_id']] = $value;
                }
                $objResult->MoveNext();
            }
        }
        
        $i = 0;

        $paymentMethods = split(',', $_ARRAYLANG["TXT_PAYMENT_METHODS"]);

        foreach ($this->registrationList as $objRegistration) {
            $checkbox = '<input type="checkbox" name="selectedRegistrationId[]" id="selectedRegistrationId" value="'.$objRegistration->id.'" />';
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', $checkbox);
            $objTpl->parse('eventRegistrationValue');
            
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', date("d.m.Y", $objRegistration->eventDate));
            $objTpl->parse('eventRegistrationValue');
            
            foreach ($arrValues[$objRegistration->id] as $value) {
                $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', $value);
                $objTpl->parse('eventRegistrationValue');
            }
            
            unset($paymentMethod);
            switch ($objRegistration->paymentMethod) {
                case 1:
                    $paymentMethod = $paymentMethods[1];
                    break;
                case 2:
                    $paymentMethod = $paymentMethods[2];
                    break;
                default:
                    $paymentMethod = $paymentMethods[0];
                    break;
            }

            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', $paymentMethod . " (" . ($objRegistration->payed ? $_ARRAYLANG["TXT_PAYMENT_COMPLETED"] : $_ARRAYLANG["TXT_PAYMENT_INCOMPLETED"]) . ")");
            $objTpl->parse('eventRegistrationValue');
            
            $links = '
                <a style="float: right;" href="javascript:deleteNote(\''.$objRegistration->id.'\');" title="'.$_ARRAYLANG['TXT_CALENDAR_DELETE'].'"><img src="images/icons/delete.gif" width="17" height="17" border="0" alt="'.$_ARRAYLANG['TXT_CALENDAR_DELETE'].'" /></a>
                <a style="float: right;" href="index.php?cmd='.$this->moduleName.'&act=modify_registration&tpl='.$_GET['tpl'].'&event_id='.$this->eventId.'&amp;reg_id='.$objRegistration->id.'" title="'.$_ARRAYLANG['TXT_CALENDAR_EDIT'].'"><img src="images/icons/edit.gif" width="16" height="16" border="0" alt="'.$_ARRAYLANG['TXT_CALENDAR_EDIT'].'" /></a>
            ';
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', $links);
            $objTpl->parse('eventRegistrationValue');
            
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_ROW', $i % 2 == 0 ? 'row1' : 'row2');
            $objTpl->parse('eventRegistrationList');
            $i++;
        }
    }
    
    function showRegistrationInputfields($formId, $regId = null, $objTpl)
    {
        global $objDatabase, $_LANGID, $_ARRAYLANG;
        
        $i = 0;
        $objForm = new CalendarForm(intval($formId));
        
        foreach ($objForm->inputfields as $arrInputfield) {
            $inputfield = '';
            $options = explode(',', $arrInputfield['default_value'][$_LANGID]);
            
            if(isset($_POST['registrationField'][$arrInputfield['id']])) {
                $value = $_POST['registrationField'][$arrInputfield['id']];
            } else {
                $value = $regId != null ? $this->registrationList[$regId]->fields[$arrInputfield['id']]['value'] : '';
            }
            
            switch ($arrInputfield['type']) {
                case 'inputtext':
                case 'mail':
                case 'seating':
                case 'firstname':
                case 'lastname':
                    $inputfield = '<input style="width: 200px;" type="text" class="calendarInputText" name="registrationField['.$arrInputfield['id'].']" value="'.$value.'" />';
                    break;
                case 'textarea':
                    $inputfield = '<textarea style="width: 196px;" class="calendarTextarea" name="registrationField['.$arrInputfield['id'].']">'.$value.'</textarea>';
                    break ;
                case 'select':
                case 'salutation':
                    $inputfield = '<select style="width: 202px;" class="calendarSelect" name="registrationField['.$arrInputfield['id'].']">';
                    $selected =  empty($_POST) ? 'selected="selected"' : '';  
                    $inputfield .= '<option value="" '.$selected.'>'.$_ARRAYLANG['TXT_CALENDAR_PLEASE_CHOOSE'].'</option>';    
                    foreach ($options as $key => $name)  {
                        $selected =  ($key+1 == $value)  ? 'selected="selected"' : '';        
                        $inputfield .= '<option value="'.intval($key+1).'" '.$selected.'>'.$name.'</option>';       
                    }
                    $inputfield .= '</select>';
                    break;
                 case 'radio':
                    foreach ($options as $key => $name)  {
                        $checked =  ($key+1 == $value) || (empty($_POST) && $key == 0) ? 'checked="checked"' : '';     
                        $textfield = '<input type="text" class="calendarInputCheckboxAdditional" name="registrationFieldAdditional['.$arrInputfield['id'].']['.$key.']" />';
                        $name = str_replace('[[INPUT]]', $textfield, $name);
                        $inputfield .= '<input type="radio" class="calendarInputCheckbox" name="registrationField['.$arrInputfield['id'].']" value="'.intval($key+1).'" '.$checked.'/>&nbsp;'.$name.'<br />';  
                    }
                    break;
                 case 'checkbox':
                    foreach ($options as $key => $name)  {    
                        $textfield = '<input type="text" class="calendarInputCheckboxAdditional" name="registrationFieldAdditional['.$arrInputfield['id'].']['.$key.']" />';
                        $name = str_replace('[[INPUT]]', $textfield, $name);
                        $checked =  (in_array($key+1, $_POST['registrationField'][$arrInputfield['id']]))  ? 'checked="checked"' : '';       
                        $inputfield .= '<input '.$checked.' type="checkbox" class="calendarInputCheckbox" name="registrationField['.$arrInputfield['id'].'][]" value="'.intval($key+1).'" />&nbsp;'.$name.'<br />';  
                    }
                    break;
            }
            
            if ($arrInputfield['type'] != 'agb') {
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_ROW'                             => $i % 2 == 0 ? 'row1' : 'row2',
                    $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_NAME'    => $arrInputfield['name'][$_LANGID],
                    $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_VALUE'   => $inputfield,
                ));
                $objTpl->parse('calendar_registration_inputfield');
                $i++;
            }
        }
    }
}