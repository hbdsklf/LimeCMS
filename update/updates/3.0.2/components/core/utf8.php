<?php
function _utf8Update()
{
	global $objUpdate, $_DBCONFIG, $objDatabase, $_ARRAYLANG, $_CORELANG;

	if (!isset($_DBCONFIG['charset']) || $_DBCONFIG['charset'] != 'utf8') {
		$preferedCollation = 'utf8_unicode_ci';

		$arrCollations = _getUtf8Collations();
		if (!is_array($arrCollations)) {
			return $arrCollations;
		}

		if (empty($_SESSION['contrexx_update']['update']['core']['utf8_collation'])) {
			if (isset($_POST['dbCollation']) && in_array($objUpdate->stripslashes($_POST['dbCollation']), $arrCollations)) {
				$_SESSION['contrexx_update']['update']['core']['utf8_collation'] = $objUpdate->stripslashes($_POST['dbCollation']);
			} else {
				$collationMenu = '<select name="dbCollation">';
				foreach ($arrCollations as $collation) {
					$collationMenu .= '<option value="'.$collation.'"'.($collation == $preferedCollation ? ' selected="selected"' : '').'>'.$collation.'</option>';
				}
				$collationMenu .= '</select><br />';

				setUpdateMsg($_ARRAYLANG['TXT_SELECT_DB_COLLATION'], 'title');
				setUpdateMsg(sprintf($_ARRAYLANG['TXT_SELECT_DB_COLLATION_MSG'].'<br /><br />', $collationMenu), 'msg');
				setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
				return false;
			}
		}

		// SET DATABASE CHARSET AND COLLATION
		$query = "SHOW CREATE DATABASE `".$_DBCONFIG['database']."`";
		$objDbStatement = $objDatabase->Execute($query);
		if ($objDbStatement !== false) {
			if (!preg_match('#DEFAULT\sCHARACTER\sSET\sutf8\sCOLLATE\s'.$_SESSION['contrexx_update']['update']['core']['utf8_collation'].'#s', $objDbStatement->fields['Create Database'])) {
				$query = "ALTER DATABASE `".$_DBCONFIG['database']."` DEFAULT CHARACTER SET utf8 COLLATE ".$objUpdate->addslashes($_SESSION['contrexx_update']['update']['core']['utf8_collation']);
				if ($objDatabase->Execute($query) === false) {
					return _databaseError($query, $objDatabase->ErrorMsg());
				}
			}
		} else {
			return _databaseError($query, $objDatabase->ErrorMsg());
		}


		// CHANGE TABLE CHARSET AND COLLATION
		$arrContrexxTables = array(
			DBPREFIX.'access_group_dynamic_ids',
			DBPREFIX.'access_group_static_ids',
			DBPREFIX.'access_rel_user_group',
			DBPREFIX.'access_settings',
			DBPREFIX.'access_users',
			DBPREFIX.'access_user_attribute',
			DBPREFIX.'access_user_attribute_name',
			DBPREFIX.'access_user_attribute_value',
			DBPREFIX.'access_user_core_attribute',
			DBPREFIX.'access_user_groups',
			DBPREFIX.'access_user_mail',
			DBPREFIX.'access_user_profile',
			DBPREFIX.'access_user_title',
			DBPREFIX.'access_user_validity',
			DBPREFIX.'module_block_blocks',
			DBPREFIX.'module_block_categories',
			DBPREFIX.'module_block_rel_lang_content',
			DBPREFIX.'module_block_rel_pages',
			DBPREFIX.'module_block_settings',
			DBPREFIX.'module_blog_categories',
			DBPREFIX.'module_blog_comments',
			DBPREFIX.'module_blog_messages',
			DBPREFIX.'module_blog_messages_lang',
			DBPREFIX.'module_blog_message_to_category',
			DBPREFIX.'module_blog_networks',
			DBPREFIX.'module_blog_networks_lang',
			DBPREFIX.'module_blog_settings',
			DBPREFIX.'module_blog_votes',
			DBPREFIX.'module_calendar',
			DBPREFIX.'module_calendar_categories',
			DBPREFIX.'module_calendar_form_data',
			DBPREFIX.'module_calendar_form_fields',
			DBPREFIX.'module_calendar_registrations',
			DBPREFIX.'module_calendar_settings',
			DBPREFIX.'module_calendar_style',
			DBPREFIX.'module_checkout_settings_general',
			DBPREFIX.'module_checkout_settings_mails',
			DBPREFIX.'module_checkout_settings_yellowpay',
			DBPREFIX.'module_checkout_transactions',
			DBPREFIX.'module_contact_form',
			DBPREFIX.'module_contact_form_data',
			DBPREFIX.'module_contact_form_field',
			DBPREFIX.'module_contact_form_field_lang',
			DBPREFIX.'module_contact_form_lang',
			DBPREFIX.'module_contact_form_submit_data',
			DBPREFIX.'module_contact_recipient',
			DBPREFIX.'module_contact_recipient_lang',
			DBPREFIX.'module_contact_settings',
			DBPREFIX.'backend_areas',
			DBPREFIX.'backups',
			DBPREFIX.'content_node',
			DBPREFIX.'content_page',
			DBPREFIX.'core_country',
			DBPREFIX.'core_mail_template',
			DBPREFIX.'core_setting',
			DBPREFIX.'core_text',
			DBPREFIX.'ids',
			DBPREFIX.'languages',
			DBPREFIX.'lib_country',
			DBPREFIX.'log',
			DBPREFIX.'log_entry',
			DBPREFIX.'modules',
			DBPREFIX.'module_repository',
			DBPREFIX.'sessions',
			DBPREFIX.'settings',
			DBPREFIX.'settings_image',
			DBPREFIX.'settings_smtp',
			DBPREFIX.'skins',
			DBPREFIX.'module_data_categories',
			DBPREFIX.'module_data_messages',
			DBPREFIX.'module_data_messages_lang',
			DBPREFIX.'module_data_message_to_category',
			DBPREFIX.'module_data_placeholders',
			DBPREFIX.'module_data_settings',
			DBPREFIX.'module_directory_categories',
			DBPREFIX.'module_directory_dir',
			DBPREFIX.'module_directory_inputfields',
			DBPREFIX.'module_directory_levels',
			DBPREFIX.'module_directory_mail',
			DBPREFIX.'module_directory_rel_dir_cat',
			DBPREFIX.'module_directory_rel_dir_level',
			DBPREFIX.'module_directory_settings',
			DBPREFIX.'module_directory_settings_google',
			DBPREFIX.'module_directory_vote',
			DBPREFIX.'module_docsys',
			DBPREFIX.'module_docsys_categories',
			DBPREFIX.'module_docsys_entry_category',
			DBPREFIX.'module_downloads_category',
			DBPREFIX.'module_downloads_category_locale',
			DBPREFIX.'module_downloads_download',
			DBPREFIX.'module_downloads_download_locale',
			DBPREFIX.'module_downloads_group',
			DBPREFIX.'module_downloads_group_locale',
			DBPREFIX.'module_downloads_rel_download_category',
			DBPREFIX.'module_downloads_rel_download_download',
			DBPREFIX.'module_downloads_rel_group_category',
			DBPREFIX.'module_downloads_settings',
			DBPREFIX.'module_ecard_ecards',
			DBPREFIX.'module_ecard_settings',
			DBPREFIX.'module_egov_configuration',
			DBPREFIX.'module_egov_orders',
			DBPREFIX.'module_egov_products',
			DBPREFIX.'module_egov_product_calendar',
			DBPREFIX.'module_egov_product_fields',
			DBPREFIX.'module_egov_settings',
			DBPREFIX.'module_feed_category',
			DBPREFIX.'module_feed_news',
			DBPREFIX.'module_feed_newsml_association',
			DBPREFIX.'module_feed_newsml_categories',
			DBPREFIX.'module_feed_newsml_documents',
			DBPREFIX.'module_feed_newsml_providers',
			DBPREFIX.'module_filesharing',
			DBPREFIX.'module_filesharing_mail_template',
			DBPREFIX.'module_forum_access',
			DBPREFIX.'module_forum_categories',
			DBPREFIX.'module_forum_categories_lang',
			DBPREFIX.'module_forum_notification',
			DBPREFIX.'module_forum_postings',
			DBPREFIX.'module_forum_rating',
			DBPREFIX.'module_forum_settings',
			DBPREFIX.'module_forum_statistics',
			DBPREFIX.'module_gallery_categories',
			DBPREFIX.'module_gallery_comments',
			DBPREFIX.'module_gallery_language',
			DBPREFIX.'module_gallery_language_pics',
			DBPREFIX.'module_gallery_pictures',
			DBPREFIX.'module_gallery_settings',
			DBPREFIX.'module_gallery_votes',
			DBPREFIX.'module_guestbook',
			DBPREFIX.'module_guestbook_settings',
			DBPREFIX.'module_jobs',
			DBPREFIX.'module_jobs_categories',
			DBPREFIX.'module_jobs_location',
			DBPREFIX.'module_jobs_rel_loc_jobs',
			DBPREFIX.'module_jobs_settings',
			DBPREFIX.'module_knowledge_articles',
			DBPREFIX.'module_knowledge_article_content',
			DBPREFIX.'module_knowledge_categories',
			DBPREFIX.'module_knowledge_categories_content',
			DBPREFIX.'module_knowledge_settings',
			DBPREFIX.'module_knowledge_tags',
			DBPREFIX.'module_knowledge_tags_articles',
			DBPREFIX.'module_livecam',
			DBPREFIX.'module_livecam_settings',
			DBPREFIX.'module_market',
			DBPREFIX.'module_market_categories',
			DBPREFIX.'module_market_mail',
			DBPREFIX.'module_market_paypal',
			DBPREFIX.'module_market_settings',
			DBPREFIX.'module_market_spez_fields',
			DBPREFIX.'module_media_settings',
			DBPREFIX.'module_mediadir_categories',
			DBPREFIX.'module_mediadir_categories_names',
			DBPREFIX.'module_mediadir_comments',
			DBPREFIX.'module_mediadir_entries',
			DBPREFIX.'module_mediadir_forms',
			DBPREFIX.'module_mediadir_form_names',
			DBPREFIX.'module_mediadir_inputfields',
			DBPREFIX.'module_mediadir_inputfield_names',
			DBPREFIX.'module_mediadir_inputfield_types',
			DBPREFIX.'module_mediadir_inputfield_verifications',
			DBPREFIX.'module_mediadir_levels',
			DBPREFIX.'module_mediadir_level_names',
			DBPREFIX.'module_mediadir_mails',
			DBPREFIX.'module_mediadir_mail_actions',
			DBPREFIX.'module_mediadir_masks',
			DBPREFIX.'module_mediadir_order_rel_forms_selectors',
			DBPREFIX.'module_mediadir_rel_entry_categories',
			DBPREFIX.'module_mediadir_rel_entry_inputfields',
			DBPREFIX.'module_mediadir_rel_entry_levels',
			DBPREFIX.'module_mediadir_settings',
			DBPREFIX.'module_mediadir_settings_num_categories',
			DBPREFIX.'module_mediadir_settings_num_entries',
			DBPREFIX.'module_mediadir_settings_num_levels',
			DBPREFIX.'module_mediadir_settings_perm_group_forms',
			DBPREFIX.'module_mediadir_votes',
			DBPREFIX.'module_memberdir_directories',
			DBPREFIX.'module_memberdir_name',
			DBPREFIX.'module_memberdir_settings',
			DBPREFIX.'module_memberdir_values',
			DBPREFIX.'module_news',
			DBPREFIX.'module_news_categories',
			DBPREFIX.'module_news_categories_locale',
			DBPREFIX.'module_news_comments',
			DBPREFIX.'module_news_locale',
			DBPREFIX.'module_news_settings',
			DBPREFIX.'module_news_settings_locale',
			DBPREFIX.'module_news_stats_view',
			DBPREFIX.'module_news_teaser_frame',
			DBPREFIX.'module_news_teaser_frame_templates',
			DBPREFIX.'module_news_ticker',
			DBPREFIX.'module_news_types',
			DBPREFIX.'module_news_types_locale',
			DBPREFIX.'module_newsletter',
			DBPREFIX.'module_newsletter_access_user',
			DBPREFIX.'module_newsletter_attachment',
			DBPREFIX.'module_newsletter_category',
			DBPREFIX.'module_newsletter_confirm_mail',
			DBPREFIX.'module_newsletter_email_link',
			DBPREFIX.'module_newsletter_email_link_feedback',
			DBPREFIX.'module_newsletter_rel_cat_news',
			DBPREFIX.'module_newsletter_rel_usergroup_newsletter',
			DBPREFIX.'module_newsletter_rel_user_cat',
			DBPREFIX.'module_newsletter_settings',
			DBPREFIX.'module_newsletter_template',
			DBPREFIX.'module_newsletter_tmp_sending',
			DBPREFIX.'module_newsletter_user',
			DBPREFIX.'module_newsletter_user_title',
			DBPREFIX.'module_podcast_category',
			DBPREFIX.'module_podcast_medium',
			DBPREFIX.'module_podcast_rel_category_lang',
			DBPREFIX.'module_podcast_rel_medium_category',
			DBPREFIX.'module_podcast_settings',
			DBPREFIX.'module_podcast_template',
			DBPREFIX.'module_recommend',
			DBPREFIX.'module_shop_article_group',
			DBPREFIX.'module_shop_attribute',
			DBPREFIX.'module_shop_categories',
			DBPREFIX.'module_shop_currencies',
			DBPREFIX.'module_shop_customer_group',
			DBPREFIX.'module_shop_discountgroup_count_name',
			DBPREFIX.'module_shop_discountgroup_count_rate',
			DBPREFIX.'module_shop_discount_coupon',
			DBPREFIX.'module_shop_importimg',
			DBPREFIX.'module_shop_lsv',
			DBPREFIX.'module_shop_manufacturer',
			DBPREFIX.'module_shop_option',
			DBPREFIX.'module_shop_orders',
			DBPREFIX.'module_shop_order_attributes',
			DBPREFIX.'module_shop_order_items',
			DBPREFIX.'module_shop_payment',
			DBPREFIX.'module_shop_payment_processors',
			DBPREFIX.'module_shop_pricelists',
			DBPREFIX.'module_shop_products',
			DBPREFIX.'module_shop_rel_countries',
			DBPREFIX.'module_shop_rel_customer_coupon',
			DBPREFIX.'module_shop_rel_discount_group',
			DBPREFIX.'module_shop_rel_payment',
			DBPREFIX.'module_shop_rel_product_attribute',
			DBPREFIX.'module_shop_rel_shipper',
			DBPREFIX.'module_shop_shipment_cost',
			DBPREFIX.'module_shop_shipper',
			DBPREFIX.'module_shop_vat',
			DBPREFIX.'module_shop_zones',
			DBPREFIX.'stats_browser',
			DBPREFIX.'stats_colourdepth',
			DBPREFIX.'stats_config',
			DBPREFIX.'stats_country',
			DBPREFIX.'stats_hostname',
			DBPREFIX.'stats_javascript',
			DBPREFIX.'stats_operatingsystem',
			DBPREFIX.'stats_referer',
			DBPREFIX.'stats_requests',
			DBPREFIX.'stats_requests_summary',
			DBPREFIX.'stats_screenresolution',
			DBPREFIX.'stats_search',
			DBPREFIX.'stats_spiders',
			DBPREFIX.'stats_spiders_summary',
			DBPREFIX.'stats_visitors',
			DBPREFIX.'stats_visitors_summary',
			DBPREFIX.'module_u2u_address_list',
			DBPREFIX.'module_u2u_message_log',
			DBPREFIX.'module_u2u_sent_messages',
			DBPREFIX.'module_u2u_settings',
			DBPREFIX.'module_u2u_user_log',
			DBPREFIX.'voting_additionaldata',
			DBPREFIX.'voting_email',
			DBPREFIX.'voting_rel_email_system',
			DBPREFIX.'voting_results',
			DBPREFIX.'voting_system',
			DBPREFIX.'module_feed_newsml_content_item',
			DBPREFIX.'module_newsletter_system',
			DBPREFIX.'module_newsletter_config',
			DBPREFIX.'module_shop_shipment'
		);

		$query = "SHOW TABLE STATUS LIKE '".DBPREFIX."%'";
		$objInstalledTable = $objDatabase->Execute($query);
		if ($objInstalledTable !== false) {
			while (!$objInstalledTable->EOF) {
				$arrInstalledTables[$objInstalledTable->fields['Name']] = $objInstalledTable->fields['Collation'];
				$objInstalledTable->MoveNext();
			}
		} else {
			return _databaseError($query, $objDatabase->ErrorMsg());
		}

		foreach ($arrContrexxTables as $table) {
			$converted = false;

			if (in_array($table, array_keys($arrInstalledTables))) {
				if ($arrInstalledTables[$table] == $_SESSION['contrexx_update']['update']['core']['utf8_collation']) {
					continue;
				} else {
					if (!in_array($table.'_new', $arrInstalledTables)) {
						$objTableStructure = $objDatabase->Execute("SHOW CREATE TABLE `".$table."`");
						if ($objTableStructure === false) {
							return _databaseError($query, $objDatabase->ErrorMsg());
						}

						$objTableStructure->fields['Create Table'] = preg_replace(
							array(
								'/'.$table.'/',
								'/default current_timestamp on update current_timestamp/i',
								'/collate[\s|=][a-z0-9_]+/i',
								'/default charset=[a-z0-9_]+/i',
								'/engine=myisam/i'
							),
							array(
								$table.'_new',
								'',
							),
							$objTableStructure->fields['Create Table']
						);

						$query = $objTableStructure->fields['Create Table']." TYPE=MyISAM DEFAULT CHARSET=utf8 COLLATE=".$objUpdate->addslashes($_SESSION['contrexx_update']['update']['core']['utf8_collation']).";\n";
						if ($objDatabase->Execute($query) === false) {
							return _databaseError($query, $objDatabase->ErrorMsg());
						}
					}

					$query = "SELECT COUNT(1) AS rowCount FROM `".$table."`";
					if (($objResult = $objDatabase->SelectLimit($query, 1)) !== false) {
						$oriCount = $objResult->fields['rowCount'];
					} else {
						return _databaseError($query, $objDatabase->ErrorMsg());
					}

					$query = "SELECT COUNT(1) AS rowCount FROM `".$table."_new`";
					if (($objResult = $objDatabase->SelectLimit($query, 1)) !== false) {
						$newCount = $objResult->fields['rowCount'];
					} else {
						return _databaseError($query, $objDatabase->ErrorMsg());
					}

					if ($oriCount !== $newCount) {
						$query = "TRUNCATE TABLE `".$table."_new`";
						if ($objDatabase->Execute($query) === false) {
							return _databaseError($query, $objDatabase->ErrorMsg());
						}

						$query = "INSERT INTO `".$table."_new` SELECT * FROM `".$table."`";
						if ($objDatabase->Execute($query) === false) {
							return _databaseError($query, $objDatabase->ErrorMsg());
						}
					}

					$query = "DROP TABLE `".$table."`";
					if ($objDatabase->Execute($query) === false) {
						return _databaseError($query, $objDatabase->ErrorMsg());
					}

					$converted = true;
				}
			}

			if (in_array($table.'_new', $arrInstalledTables) || $converted) {
				$query = "RENAME TABLE `".$table."_new`  TO `".$table."`";
				if ($objDatabase->Execute($query) === false) {
					return _databaseError($query, $objDatabase->ErrorMsg());
				}
			}
		}

		if (!isset($_SESSION['contrexx_update']['update']['utf'])) {
			if (_convertThemes2UTF()) {
				$_SESSION['contrexx_update']['update']['utf'] = true;
			} else {
				return false;
			}
		}
	}

	return true;
}

function _getUtf8Collations()
{
	global $objDatabase;

	$arrCollate = array();

	$query = 'SHOW COLLATION';
	$objCollation = $objDatabase->Execute($query);
	if ($objCollation !== false) {
		while (!$objCollation->EOF) {
			if ($objCollation->fields['Charset'] == 'utf8') {
				$arrCollate[] = $objCollation->fields['Collation'];
			}
			$objCollation->MoveNext();
		}

		return $arrCollate;
	} else {
		return _databaseError($query, $objDatabase->ErrorMsg());
	}
}

function _convertThemes2UTF()
{
	global $objDatabase, $_CORELANG, $_ARRAYLANG;

	// get installed themes
	$query = 'SELECT themesname, foldername FROM `'.DBPREFIX.'skins`';
	$objTheme = $objDatabase->Execute($query);
	$arrThemes = array();
	if ($objTheme !== false) {
		while (!$objTheme->EOF) {
			$arrThemes[$objTheme->fields['themesname']] = $objTheme->fields['foldername'];
			$objTheme->MoveNext();
		}
	} else {
		return _databaseError($query, $objDatabase->ErrorMsg());
	}

	if (count($arrThemes)) {
		foreach ($arrThemes as $path) {
			if (!isset($_SESSION['contrexx_update']['update']['utf_themes'][$path])) {
				$_SESSION['contrexx_update']['update']['utf_themes'][$path] = array();
			}
			$dh = @opendir(ASCMS_THEMES_PATH.'/'.$path);
			if ($dh !== false) {
				while (($file = @readdir($dh)) !== false) {
					if (substr($file, -5) == '.html') {
						if (!in_array($file, $_SESSION['contrexx_update']['update']['utf_themes'][$path])) {
							$content = file_get_contents(ASCMS_THEMES_PATH.'/'.$path.'/'.$file);
					    	$fh = @fopen(ASCMS_THEMES_PATH.'/'.$path.'/'.$file, 'wb');
					    	if ($fh !== false) {
					    		$status = true;
						    	if (@fwrite($fh, utf8_encode($content)) !== false) {
						    		$_SESSION['contrexx_update']['update']['utf_themes'][$path][] = $file;
						    	} else {
						    		setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_CONVERT_FILE'], ASCMS_THEMES_PATH.'/'.$path.'/'.$file));
						    		$status = false;
						    	}
						    	@fclose($fh);

						    	if (!$status) {
						    		return false;
						    	}
					    	} else {
					    		setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_WRITE_FILE'], ASCMS_THEMES_PATH.'/'.$path.'/'.$file));
					    		setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_FILE'], ASCMS_THEMES_PATH.'/'.$path.'/'.$file, $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
					    		return false;
					    	}
						}
					}
				}

				@closedir($dh);
			}
		}
	}

	return true;
}
?>
