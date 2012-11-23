<?
/**
 * Draw Pager
 *
 * @param $id
 * @param $visualPagesCount
 * @param $exclude
 * @return string
 */
function smarty_function_draw_pager($params, Smarty_Internal_Template &$smarty){
	$id = null;
	$visualPagesCount = null;
	$excludedGetsArray = array();
	
	extract($params);
	
	if(empty($visualPagesCount)){
		$visualPagesCount = ConfigManager::getConfig("Pager","Pager")->AuxConfig->defaultVidualPagesCount;
	}
	if(empty($id)){
		$id = null;
	}
	
	if(isset($exclude) and !empty($exclude)){
		$excludedGetsArray = explode(",", str_replace(" ", "", $exclude));
	}
	
	$pager = Pager::getPager($id);
	
	if($pager instanceof  Pager){
		if(isset($baseLink) and !empty($baseLink)){
			// Remove heading slash if present and ensure last slash
			$link = ltrim($baseLink, "/");
			RewriteURL::ensureLastSlash($link);
		}
		else{
			/*$link = RewriteURL::generateCleanBaseLink(
	                                Reg::get('nav')->module,
	                                Reg::get('nav')->page,
	                                ConfigManager::getConfig("SiteNavigation")->AuxConfig->firstLevelDefaultValue) . 
	                                	getAllGetParams()
	                              );*/
			$link = getCurrentUrl(array_merge(array($pager->getUrlParam()), $excludedGetsArray));
		}
		$urlParam = $pager->getUrlParam();
		$currentPageNumber = $pager->getCurrentPageNumber();
		$pagesCount = $pager->getTotalPagesCount();
		
		
		if($pagesCount > 1){
			$pageNumStart = $currentPageNumber - floor($visualPagesCount / 2);
			if($pageNumStart < 1){
				$pageNumStart = 1;
			}
			
			$pageNumEnd = $pageNumStart + $visualPagesCount - 1;
			if($pageNumEnd > $pagesCount){
				$pageNumEnd = $pagesCount;
				$pageNumStart = $pageNumEnd - $visualPagesCount + 1;
				if($pageNumStart < 1){
					$pageNumStart = 1;
				}
			}
			
			if($pageNumStart > 1){
				$pagerFirstPageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':1');
				$smarty->assign('pagerFirstPageLink', $pagerFirstPageLink);
			}
			
			if($pageNumEnd < $pagesCount){
				$pagerLastPageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':' .$pagesCount);
				$smarty->assign('pagerLastPageLink', $pagerLastPageLink);
			}
			
			if($currentPageNumber > 1){
				$prevPageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':' . ($currentPageNumber - 1));
				$smarty->assign('pagerPreviousPageLink', $prevPageLink);
			}
			
			$pagerNumbersArray = array();
			for($pgNum = $pageNumStart; $pgNum <= $pageNumEnd; $pgNum++){
				$isCurrent = false;
				if($pgNum == $currentPageNumber){
					$isCurrent = true;
				}
				$pageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':' . $pgNum);
				
				array_push($pagerNumbersArray, array("pageNum" => $pgNum, "pageLink" => $pageLink, "isCurrent" => $isCurrent));
			}
			
			if($currentPageNumber < $pagesCount){
				$nextPageLink = Reg::get(ConfigManager::getConfig("RewriteURL")->Objects->rewriteURL)->glink($link . $pager->getUrlParam() . ':' . ($currentPageNumber + 1));
				$smarty->assign('pagerNextPageLink', $nextPageLink);
			}
			
			$smarty->assign("pagerPageNumStart", $pageNumStart);
			$smarty->assign("pagerPageNumEnd", $pageNumEnd);
			$smarty->assign("pagerCurrentPageNumber", $currentPageNumber);
			$smarty->assign("pagerTotalPagesCount", $pagesCount);
			$smarty->assign("pagerNumbersArray", $pagerNumbersArray);
		}
		
		if(isset($tplChunkFile)){
			$pagerChunkFileName = $tplChunkFile;
		}
		else{
			$pagerChunkFileName = ConfigManager::getConfig("Pager","Pager")->AuxConfig->pagerChunkFileName;
		}
		
		return $smarty->fetch($smarty->getChunkPath($pagerChunkFileName));
	}
}
?>