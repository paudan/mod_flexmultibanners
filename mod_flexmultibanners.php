<?php
/**
 * @copyright Copyright (C) 2009 - 2012 inch communications ltd
 * @license     GNU General Public License version 2 or later.
 */

// no direct access
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

// Include the functions only once
require_once JPATH_SITE . '/components/com_flexbanners/helpers/flexbanners.php';

// locationid must be an integer
$locationid = intval($params -> get('locationid', ''));
$moduleclass_sfx = htmlspecialchars($params -> get('moduleclass_sfx'));

$botlist = "/(Google|msnbot|Rambler|Yahoo|AbachoBOT|accoona|dotbot|AcioRobot|ASPSeek|CocoCrawler|Dumbot|FAST-WebCrawler|GeonaBot|Gigabot|Lycos|MSRBOT|Scooter|AltaVista|IDBot|eStyle|Scrubby|Googlebot|Yahoo! Slurp|VoilaBot|ZyBorg|WebCrawler|DeepIndex|Teoma|appie|HenriLeRobotMirago|psbot|Szukacz|Openbot|Naver)+/i";
$isBrowser = true;
if(preg_match($botlist, $_SERVER['HTTP_USER_AGENT']))
	$isBrowser = false;

$app = JFactory::getApplication();
$flexbannerid = intval(JRequest::getVar('id', NULL));
$task = NULL;
$menu = $app->getMenu();
if($menu -> getActive() == $menu -> getDefault()) { $task = "frontpage";
} else {
	$task = JRequest::getVar('view', NULL);
}
$loadlast = ($params -> get('loadlast', 0));
$enablecsa = ($params -> get('enablecsa', 0));
$enabletrans = ($params -> get('enabletrans', 0));
$enablenofollow = ($params -> get('enablenofollow', 0));
$details = array("sectionid" => NULL, "categoryid" => NULL, "contentid" => NULL, "langaugeid" => NULL, "frontpage" => NULL);
$blankimageurl = JURI::base() . JRoute::_('modules/mod_flexbanners/trans.gif');
$nofollow = '';
if($enablenofollow) {
	$nofollow = ' rel="nofollow"';
}
$database = JFactory::getDBO();
$conf = JFactory::getConfig();
$fb_language = 0;
$iso_client_lang = $conf -> get('language');
$iso_client_lang = '"' . $iso_client_lang . '"';

//Get the active menu item
switch($task) {

	case 'article' :
		$contentitem = new flexAdContent($database);
		$contentitem -> load($flexbannerid);
		$details = array("sectionid" => $contentitem -> sectionid, "categoryid" => $contentitem -> catid, "contentid" => $contentitem -> id);
		break;
	case 'blogcategory' :

	case 'category' :
		$categoryid = $flexbannerid;
		$category = new flexAdCategories($database);
		$category -> load($flexbannerid);
		$details = array("sectionid" => $category -> section, "categoryid" => $category -> id, "contentid" => NULL);
		break;
	case 'blogsection' :

	case 'section' :
		$details = array("sectionid" => $flexbannerid, "categoryid" => NULL, "contentid" => NULL);
		break;
	case 'frontpage' :
		$details = array("sectionid" => NULL, "categoryid" => NULL, "contentid" => NULL, "langaugeid" => NULL, "frontpage" => 1);
		break;
	default :

	// echo "Not in a category, section or content item view";
		break;
}

$contentif = '';

if($enablecsa) {
	$contentif = modFlexBannersHelper::FlexBannersQuery($details);
}

$sql = "SELECT `#__flexbanners`.`id`,
		               `#__flexbannerslocations`.`locationname`,
		               `#__flexbannerslocations`.`locationid`,
		               `#__flexbanners`.`imageurl`,
		               `#__flexbanners`.`imagealt`,
		               `#__flexbanners`.`type`,
		               `#__flexbanners`.`customcode`,
		               `#__flexbanners`.`startdate`,
		               `#__flexbanners`.`enddate`,
		               `#__flexbanners`.`lastreset`,
		               `#__flexbanners`.`impmade`,
		               `#__flexbanners`.`clicks`,
		               `#__flexbanners`.`maximpressions`,
		               `#__flexbanners`.`maxclicks`,
		               `#__flexbanners`.`linkid`,
		               `#__flexbanners`.`language`,
		               `#__flexbanners`.`newwin`,
		               `#__flexbannerssize`.`width`,
		               `#__flexbannerssize`.`height`,
		               `#__flexbanners`.`restrictbyid`,
					   `#__flexbanners`.`ordering`,
		               `#__flexbanners`.`dailyimpressions`,
		               if(`#__flexbanners`.`finished`OR NOT `#__flexbanners`.`state`, 0, 1) as `valid`
		        FROM   `#__flexbanners`
		        Inner Join `#__flexbannerslocations` ON `#__flexbanners`.`locationid` = `#__flexbannerslocations`.`locationid` 
				Inner Join `#__flexbannerssize` ON `#__flexbanners`.`sizeid` = `#__flexbannerssize`.`sizeid`
		        WHERE `#__flexbannerslocations`.`locationid` = $locationid $contentif
		          AND `#__flexbanners`.`state` = 1 
				  AND (`#__flexbanners`.`language` = $iso_client_lang or `#__flexbanners`.`language` = '*')
		        ORDER BY `ordering` asc, `restrictbyid` desc, `dailyimpressions`";

$database -> setQuery($sql);
$database -> query();

$newwindow = ($params -> get('newwin', 0));

if($database -> getNumRows() > 0) {
	$flexbanners = $database -> loadObjectList();
	$displayobjs = array();
	foreach ($flexbanners as $flexbanner) {
		$flexbannerdetails = new flexAdBanner($database);
		$flexbannerdetails -> load($flexbanner -> id);

		$link = JRoute::_('index.php?option=com_flexbanners&amp;task=click&amp;id=' . $flexbanner -> id);
		$imageurl = JURI::base() . JRoute::_('images/banners/' . $flexbanner -> imageurl);
		$flexbannerwidth = $flexbanner -> width;
		$flexbannerheight = $flexbanner -> height;
		$flexbannerimagealt = $flexbanner -> imagealt;
		$newwindow = $flexbanner -> newwin;

		if($flexbanner -> type == 1) {
			trim($flexbanner -> customcode);
			array_push($displayobjs, stripslashes($flexbanner -> customcode));
			//echo stripslashes($flexbanner -> customcode);
			if($isBrowser == 1) {
				$flexbannerdetails -> impmade += 1;
				$flexbannerdetails -> dailyimpressions += 1;
			}
		} elseif(preg_match("/\.swf/", $flexbanner -> imageurl)) {
			$flexbannerdisplay = modFlexBannersHelper::FlexBannersSWF($flexbannerwidth, $flexbannerheight, $link, $imageurl, $blankimageurl, $newwindow, $moduleclass_sfx, $nofollow);
			array_push($displayobjs, $flexbannerdisplay);
			if($isBrowser == 1) {
				$flexbannerdetails -> impmade += 1;
				$flexbannerdetails -> dailyimpressions += 1;
			}
		} else {
			if($loadlast) {
				$flexbannerdisplay = modFlexBannersHelper::FlexBannersloadlast($flexbannerwidth, $flexbannerheight, $link, $imageurl, $flexbannerimagealt, $newwindow, $moduleclass_sfx, $nofollow);
				array_push($displayobjs, $flexbannerdisplay);
				if($isBrowser == 1) {
					$flexbannerdetails -> impmade += 1;
					$flexbannerdetails -> dailyimpressions += 1;
				}
			} else {
				$flexbannerdisplay = modFlexBannersHelper::FlexBannersloadfirst($flexbannerwidth, $flexbannerheight, $link, $imageurl, $flexbannerimagealt, $newwindow, $moduleclass_sfx, $nofollow);
				array_push($displayobjs, $flexbannerdisplay);
				if($isBrowser == 1) {
					$flexbannerdetails -> impmade += 1;
					$flexbannerdetails -> dailyimpressions += 1;
				}
			}
		}
		$flexbannerdetails -> store();
	}
	require (JModuleHelper::getLayoutPath('mod_flexmultibanners'));
}
?>
