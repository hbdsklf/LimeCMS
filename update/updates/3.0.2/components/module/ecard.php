<?php

function _ecardUpdate() {
    global $objDatabase, $_ARRAYLANG, $_CORELANG;
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_ecard_ecards', array(
                'code' => array('type' => 'VARCHAR(35)', 'notnull' => true, 'default' => '', 'primary' => true),
                'date' => array('type' => 'INT(10)', 'notnull' => true, 'default' => 0, 'unsigned' => true),
                'TTL' => array('type' => 'INT(10)', 'notnull' => true, 'default' => 0, 'unsigned' => true),
                'salutation' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'senderName' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'senderEmail' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'recipientName' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'recipientEmail' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'message' => array('type' => 'TEXT', 'notnull' => true),
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_ecard_settings', array(
                'setting_name' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'primary' => true),
                'setting_value' => array('type' => 'TEXT', 'notnull' => true, 'default' => 0)
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    $ins_tpl = "
        INSERT INTO " . DBPREFIX . "module_ecard_settings (setting_name, setting_value)
        VALUES ('%s', '%s')
        ON DUPLICATE KEY UPDATE `setting_name` = `setting_name`
    ";
    $insert_values = array(
        array('maxCharacters', '100'),
        array('maxLines', '50'),
        array('motive_0', 'Bild_001.jpg'),
        array('motive_1', 'Bild_002.jpg'),
        array('motive_2', ''),
        array('motive_3', ''),
        array('motive_4', ''),
        array('motive_5', ''),
        array('motive_6', ''),
        array('motive_7', ''),
        array('motive_8', ''),
        array('maxHeight', '300'),
        array('validdays', '30'),
        array('maxWidth', '300'),
        array('maxHeightThumb', '80'),
        array('maxWidthThumb', '80'),
        array('subject', 'Sie haben eine E-Card erhalten!'),
        array('emailText', "[[ECARD_SENDER_NAME]] hat Ihnen eine E-Card geschickt.<br />\n Sie können diese während den nächsten [[ECARD_VALID_DAYS]] Tagen unter [[ECARD_URL]] abrufen.")
    );

    foreach ($insert_values as $setting) {
        $query = sprintf($ins_tpl, addslashes($setting[0]), addslashes($setting[1]));
        if (!$objDatabase->Execute($query)) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    /*     * **********************************************
     * BUGFIX:	Set write access to the image dir   *
     * ********************************************** */
    $arrImagePaths = array(
        array(ASCMS_DOCUMENT_ROOT . '/images/modules/ecard', ASCMS_PATH_OFFSET . '/images/modules/ecard'),
        array(ASCMS_ECARD_OPTIMIZED_PATH, ASCMS_ECARD_OPTIMIZED_WEB_PATH),
        array(ASCMS_ECARD_SEND_ECARDS_PATH, ASCMS_ECARD_SEND_ECARDS_WEB_PATH),
        array(ASCMS_ECARD_THUMBNAIL_PATH, ASCMS_ECARD_THUMBNAIL_WEB_PATH)
    );


    foreach ($arrImagePaths as $arrImagePath) {
        if (\Cx\Lib\FileSystem\FileSystem::makeWritable($arrImagePath[0])) {
            if ($mediaDir = @opendir($arrImagePath[0])) {
                while ($file = readdir($mediaDir)) {
                    if ($file != '.' && $file != '..') {
                        if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($arrImagePath[0] . '/' . $file)) {
                            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_FILE'], $arrImagePath[0] . '/' . $file, $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
                            return false;
                        }
                    }
                }
            } else {
                setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], $arrImagePath[0] . '/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
                return false;
            }
        } else {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], $arrImagePath[0] . '/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
            return false;
        }
    }

    return true;
}



function _ecardInstall()
{
    try {

        /**************************************************************************
         * EXTENSION:   Initial creation (for contrexx editions <> premium with   *
         *              version < 3.0.0 which have this module not yet installed) *
         *                                                                        *
         * ADDED:       Contrexx v3.0.1                                           *
         **************************************************************************/
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_ecard_ecards',
            array(
                'code'               => array('type' => 'VARCHAR(35)', 'notnull' => true, 'default' => '', 'primary' => true),
                'date'               => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'code'),
                'TTL'                => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'date'),
                'salutation'         => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'TTL'),
                'senderName'         => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'salutation'),
                'senderEmail'        => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'senderName'),
                'recipientName'      => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'senderEmail'),
                'recipientEmail'     => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'recipientName'),
                'message'            => array('type' => 'text', 'after' => 'recipientEmail')
            ),
            null,
            'MyISAM',
            'cx3upgrade'
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_ecard_settings',
            array(
                'setting_name'       => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'primary' => true),
                'setting_value'      => array('type' => 'text', 'after' => 'setting_name')
            ),
            null,
            'MyISAM',
            'cx3upgrade'
        );
        \Cx\Lib\UpdateUtil::sql("
            INSERT INTO `".DBPREFIX."module_ecard_settings` (`setting_name`, `setting_value`)
            VALUES  ('emailText', '[[ECARD_SENDER_NAME]] hat Ihnen eine E-Card geschickt.<br />\r\nSie können diese während den nächsten [[ECARD_VALID_DAYS]] Tagen unter [[ECARD_URL]] abrufen.'),
                    ('maxCharacters', '100'),
                    ('maxHeight', '300'),
                    ('maxHeightThumb', '80'),
                    ('maxLines', '50'),
                    ('maxWidth', '300'),
                    ('maxWidthThumb', '80'),
                    ('motive_0', 'Bild_001.jpg'),
                    ('motive_1', 'Bild_002.jpg'),
                    ('motive_2', ''),
                    ('motive_3', ''),
                    ('motive_4', ''),
                    ('motive_5', ''),
                    ('motive_6', ''),
                    ('motive_7', ''),
                    ('motive_8', ''),
                    ('subject', 'Sie haben eine E-Card erhalten!'),
                    ('validdays', '30')
            ON DUPLICATE KEY UPDATE `setting_name` = `setting_name`
        ");

    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}
