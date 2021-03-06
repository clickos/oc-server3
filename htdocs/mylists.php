<?php
/***************************************************************************
 *  For license information see doc/license.txt
 *
 *  Unicode Reminder メモ
 ***************************************************************************/

	require('./lib2/web.inc.php');
	require_once('./lib2/logic/cachelist.class.php');

	$tpl->name = 'mylists';
	$tpl->menuitem = MNU_MYPROFILE_LISTS;

	$login->verify();
	if ($login->userid == 0)
		$tpl->redirect('login.php?target=' . urlencode($tpl->target));

	$list_name = isset($_REQUEST['list_name']) ? trim($_REQUEST['list_name']) : '';
	$list_public = isset($_REQUEST['list_public']) ? $_REQUEST['list_public'] + 0 : 0;
	$list_caches = isset($_REQUEST['list_caches']) ? strtoupper(trim($_REQUEST['list_caches'])) : '';
	$watch = isset($_REQUEST['watch']);
	$desctext = isset($_REQUEST['desctext']) ? $_REQUEST['desctext'] : '';
	$descMode = isset($_REQUEST['descMode']) ? min(3,max(2,$_REQUEST['descMode']+0)) : 3;
	$switchDescMode = isset($_REQUEST['switchDescMode']) && $_REQUEST['switchDescMode'] == 1;
	$fromsearch = isset($_REQUEST['fromsearch']) && $_REQUEST['fromsearch'] == 1;

	$edit_list = false;
	$name_error = false;

	// open a 'create new list' form
	if (isset($_REQUEST['new']))
	{
		$tpl->assign('newlist_mode', true);
		$tpl->assign('show_editor', false);
		$list_name = '';
		$list_public = false;
		$watch = false;
		$desctext = '';
		// keep descMode of previous operation
		$list_caches = '';
	}

	// save the data entered in the 'create new list' form
	if (isset($_REQUEST['create']))
	{
		$list = new cachelist(ID_NEW, $login->userid);
		$name_error = $list->setNameAndPublic($list_name, $list_public);
		if ($name_error)
			$tpl->assign('newlist_mode', true);
		else
		{
			$list->setDescription($desctext, $descMode == 3);
			if ($list->save())
			{
				if ($list_caches != '')
				{
					$result = $list->addCachesByWPs($list_caches);
					$tpl->assign('invalid_waypoints', $result === true ? false : implode(", ", $result));
				}
				if ($watch)
					$list->watch(true);
			}
		}
	}

	// open an 'edit list' form
	if (isset($_REQUEST['edit']))
	{
		$list = new cachelist($_REQUEST['edit'] + 0);
		if ($list->exist() && $list->getUserId() == $login->userid)
		{
			$edit_list = true;
			$list_name = $list->getName();
			$list_public = $list->isPublic();
			$watch = $list->isWatchedByMe();
			$desctext = $list->getDescription();
			$descMode = $list->getDescHtmledit() ? 3 : 2;
			$list_caches = '';
		}
	}

	// switch between HTML and Wysiwyg editor mode
	if ($switchDescMode)
	{
		if (isset($_REQUEST['listid']))
		{
			// switching editor mode while editing existing list
			$list = new cachelist($_REQUEST['listid'] + 0);
			if ($list->exist() && $list->getUserId() == $login->userid)
			{
				$edit_list = true;
				$tpl->assign('show_editor', true);
			}
		}
		else
		{
			// switching desc mode while creating new list
			$tpl->assign('newlist_mode', true);
			$tpl->assign('show_editor', true);
		}
	}

	// save data entered in the 'edit list' form
	if (isset($_REQUEST['save']) && isset($_REQUEST['listid']))
	{
		$list = new cachelist($_REQUEST['listid'] + 0);
		if ($list->exist() && $list->getUserId() == $login->userid)
		{
			$name_error = $list->setNameAndPublic($list_name, $list_public);
			if ($name_error)
				$edit_list = true;
			$list->setDescription($desctext, $descMode == 3);
			$list->save();

			$list->watch($watch);
			if ($list_caches != '')
			{
				$result = $list->addCachesByWPs($list_caches);
				$tpl->assign('invalid_waypoints', $result === true ? false : implode(", ", $result));
				$list_caches = '';
			}
			foreach ($_REQUEST as $key => $value)
				if (substr($key, 0, 7) == 'remove_')
					$list->removeCacheById(substr($key,7));
		}
	}

	// delete a list
	if (isset($_REQUEST['delete']))
	{
		sql("DELETE FROM `cache_lists` WHERE `user_id`='&1' AND `id`='&2'",
		    $login->userid, $_REQUEST['delete'] + 0);
		// All dependent deletion and cleanup is done via trigger.
	}

	// redirect to list search output after editing a list from the search output page
	if ($fromsearch && !$switchDescMode && isset($_REQUEST['listid']))
	{
		$tpl->redirect('cachelist.php?id=' . ($_REQUEST['listid'] + 0));
	}

	// prepare editor and editing
	if ($descMode == 3)
	{
		$tpl->add_header_javascript('resource2/tinymce/tiny_mce_gzip.js');
		$tpl->add_header_javascript('resource2/tinymce/config/list.js.php?lang='.strtolower($opt['template']['locale']));
	}
	if ($edit_list)
	{
		$tpl->assign('edit_list', true);
		$tpl->assign('listid', $list->getId());
		$tpl->assign('caches', $list->getCaches());
	}

	// prepare rest of template
	$tpl->assign('cachelists', cachelist::getMyLists());
	$tpl->assign('show_status', true);
	$tpl->assign('show_user', false);
	$tpl->assign('show_watchers', true);
	$tpl->assign('show_edit', true);
	$tpl->assign('togglewatch', false);
	$tpl->assign('fromsearch', $fromsearch);
	$tpl->assign('name_error', $name_error);

	$tpl->assign('list_name', $list_name);
	$tpl->assign('list_public', $list_public);
	$tpl->assign('watch', $watch);
	$tpl->assign('desctext', $desctext);
	$tpl->assign('descMode', $descMode);
	$tpl->assign('list_caches', $list_caches);

	$tpl->display();

?>
