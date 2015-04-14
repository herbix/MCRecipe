<?php

$wgExtensionCredits['MCRecipe'][] = array(
    'path' => __FILE__,
    'name' => 'MCRecipe',
    'author' => 'Herbix', 
    'url' => 'https://github.com/herbix/MCRecipe', 
    'description' => 'An extension facilitates minecraft recipe rendering for minecraft modders building their wiki.',
    'version'  => 1.0,
    'license-name' => "GPL2",
);

$wgHooks['BeforePageDisplay'][] = 'addMCRecipeScripts';
$wgHooks['ParserFirstCallInit'][] = 'addMCRecipeFunctionHook';

$wgExtensionMessagesFiles['ExampleExtension'] = __DIR__ . '/MCRecipe.i18n.php';

$wgResourceModules['ext.mcrecipe'] = array(
	'scripts' => array(),
	'styles' => array(),
	'dependencies' => array(),
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => basename( dirname( __FILE__ ) ),
	'position' => 'top'
);

$wgResourceModules['ext.mcrecipe']['scripts'][] = 'js/recipe.js';
$wgResourceModules['ext.mcrecipe']['styles'][] = 'css/recipe.css';

$supportedMCRecipeType = array('craft', 'craft shapeless', 'smelt');

function addMCRecipeScripts( $out, $skin ) {
	$out->addModules( 'ext.mcrecipe' );
	return true;
}

function addMCRecipeFunctionHook( &$parser ) {
	$parser->setFunctionHook( 'recipe', 'mcRecipeFunctionHook' );
	return true;
}

function mcRecipeFunctionHook( $parser, $type = 'craft', $data = '' ) {
	global $supportedMCRecipeType;
	if(!in_array($type, $supportedMCRecipeType)) {
		return "";
	}

	$datajson = "";
	$datalist = explode(')', $data);
	$isfirst = true;
	foreach( $datalist as $item ) {
		$item = trim($item);
		if($item[0] != '(') {
			continue;
		}
		$item = substr( $item, 1 );
		$itemlist = explode(',', $item);
		if(count($itemlist) < 2) {
			$itemlist[1] = $itemlist[0];
		}
		if(count($itemlist) < 3) {
			$itemlist[2] = $itemlist[0] . '.png';
		}
		
		if($itemlist[2][0] == '[') {
			$picurl = substr( $itemlist[2], 1, strlen($itemlist[2]) - 2 );
		} else {
			$pic = wfFindFile( $itemlist[2] );
			if(!$pic) {
				$picurl = "broken";
			} else {
				$picurl = $pic->getFullUrl();
			}
		}
		
		if($itemlist[0][0] == '[') {
			$linkurl = substr( $itemlist[0], 1, strlen($itemlist[0]) - 2 );
		} else {
			$linkurl = Title::newFromText($itemlist[0])->getFullUrl();
		}
		
		$itemstr = "['{$itemlist[1]}','$picurl','$linkurl']";
		
		$datajson .= $isfirst ? $itemstr : ",$itemstr";
		$isfirst = false;
	}
	
	$maxcount = $type == 'smelt' ? 1 : 9;

	$listnum = func_num_args();
	$list = func_get_args();
	
	$fromjson = "";
	$fromcount = 0;
	
	$isfirst = true;
	for($i=3; $i<$listnum-1; $i++) {
		$fromjson .= $isfirst ? $list[$i] : ",{$list[$i]}";
		$isfirst = false;
		$fromcount++;
	}
	
	for(; $fromcount<$maxcount; $fromcount++) {
		$fromjson .= $isfirst ? "-1" : ",-1";
		$isfirst = false;
	}
	
	if($listnum < 4) {
		$tojson = "-1";
	} else {
		$tojson = $list[min($listnum - 1, 3 + $maxcount)];
	}

	$output = "<div class=\"recipe $type\" data=\"{'data':[$datajson],'from':[$fromjson],'to':$tojson}\"></div>";	
	
	return array( $output, 'noparse' => true, 'isHTML' => true );
}
