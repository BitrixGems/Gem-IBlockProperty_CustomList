<?php
CModule::IncludeModule("iblock");
$oIBlocks = CIBlock::GetList(
	array('NAME' => 'ASC'),
	array(
		'CHECK_PERMISSIONS' => 'Y',
		'MIN_PERMISSION' => 'W',
	)
);
$aAvailIBlocks = array();
$aIBlockTypeCache = array();
$aIBlockTypeCacheID = array();
while( $aIBlock = $oIBlocks->Fetch() ){
	if( !isset( $aIBlockTypeCache[ $aIBlock['IBLOCK_TYPE_ID'] ] ) ){
		$aIBlockTypeCache[ $aIBlock['IBLOCK_TYPE_ID'] ] = CIBlockType::GetByIDLang( $aIBlock['IBLOCK_TYPE_ID'], LANG );
		$aIBlockTypeCache[ $aIBlock['IBLOCK_TYPE_ID'] ] = $aIBlockTypeCache[ $aIBlock['IBLOCK_TYPE_ID'] ]['NAME'];
	}
	$aAvailIBlocks[ $aIBlock['ID'] ] = $aIBlockTypeCache[ $aIBlock['IBLOCK_TYPE_ID'] ].' | '.$aIBlock['NAME'];
}


$sTableID = "tbl_form_result_list_CustomList_IBlockProperty";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$lAdmin->InitFilter(
	array('find_f_iblock_id', 'find_f_property_id', 'find_f_value', 'find_f_description')
);
$oGem = BitrixGems::getGem('IBlockProperty_CustomList');

if($lAdmin->EditAction()){
	foreach($_POST['FIELDS'] as $ID=>$arFields){
		$oGem->updateListItem(
			$ID,
			array(
				'VALUE' => $arFields['VALUE'],
				'DESCRIPTION' => $arFields['DESCRIPTION'],
			)
		);
	}
}

$aFilter = array(
	'IBLOCK_ID' => array_keys($aAvailIBlocks),
);

$find_f_iblock_id 	= (isset($_GET['find_f_iblock_id']))?$_GET['find_f_iblock_id']:$GLOBALS['find_f_iblock_id'];
$find_f_property_id = (isset($_GET['find_f_property_id']))?$_GET['find_f_property_id']:$GLOBALS['find_f_property_id'];
$find_f_value 		= $GLOBALS['find_f_value'];
$find_f_description = $GLOBALS['find_f_description'];

if( !empty( $find_f_iblock_id ) && isset( $aAvailIBlocks[ $find_f_iblock_id ] ) ){
	$aFilter['IBLOCK_ID'] = $find_f_iblock_id;
}

if( !empty( $find_f_property_id ) ) $aFilter['PROPERTY_ID'] = $find_f_property_id;
if( !empty( $find_f_value ) ) $aFilter['VALUE'] = $find_f_value;
if( !empty( $find_f_description ) ) $aFilter['DESCRIPTION'] = $find_f_description;

if( ($arID = $lAdmin->GroupAction()) && check_bitrix_sessid() ){

	if($_REQUEST['action_target']=='selected'){
		$arID = Array();
		$result = $oGem->getList(array(), $aFilter);
		while($arRes = $result->Fetch()) $arID[] = $arRes['ID'];
	}
	foreach($arID as $ID){
		if(strlen($ID)<=0)continue;
		$ID = IntVal($ID);
		switch($_REQUEST['action'])
		{
			case "delete":
				@set_time_limit(0);
				$oGem->deleteListItem( $ID );
				break;
		}
		
	}
	
	if ($_SERVER['REQUEST_METHOD'] == 'GET') LocalRedirect('/bitrix/admin/bitrixgems_simpleresponder.php?gem=CustomList_IBlockProperty');
}

$rResult = $oGem->getList(array(), $aFilter);


$oResult = new CAdminResult($rResult, $sTableID);
$oResult->NavStart();
$lAdmin->NavText($oResult->GetNavPrint('Элементы списка'));

$aHeaders = array(
	array("id"=>"ID", "content"=>"ID", "sort"=>"s_id", "default"=>true),
	array("id"=>"IBLOCK_ID", "content"=>"Инфоблок", "sort"=>"s_IBLOCK_ID", "default"=>true),
	array("id"=>"PROPERTY_ID", "content"=>"Свойство инфоблока", "sort"=>"s_PROPERTY_ID", "default"=>true),
	array("id"=>"VALUE", "content"=>"Значение", "sort"=>"s_VALUE", "default"=>true),
	array("id"=>"DESCRIPTION", "content"=>"Описание", "sort"=>"s_DESCRIPTION", "default"=>true),
);

$lAdmin->AddHeaders($aHeaders);

$aIBlocks 		= array();
$aProperties 	= array();

$arActions = array(
	array("ICON"=>"edit", "TITLE"=>GetMessage("FORM_EDIT_ALT"), "TEXT"=>GetMessage("FORM_EDIT"), "ACTION"=>$lAdmin->ActionRedirect("form_result_edit.php?lang=".LANGUAGE_ID."&WEB_FORM_ID=$WEB_FORM_ID&RESULT_ID=$f_ID&WEB_FORM_NAME=$WEB_FORM_NAME"), 'DEFAULT' => 'Y'),
	array("ICON"=>"delete", "TITLE"=>GetMessage("FORM_DELETE_ALT"), "TEXT"=>GetMessage("FORM_DELETE"), "ACTION"=>$lAdmin->ActionRedirect("javascript:if(confirm('".GetMessage("FORM_CONFIRM_DELETE")."')) window.location='?lang=".LANGUAGE_ID."&WEB_FORM_ID=$WEB_FORM_ID&WEB_FORM_NAME=$WEB_FORM_NAME&action=delete&ID=$f_ID&".bitrix_sessid_get()."'")),
);
while($arRes = $oResult->NavNext(true, "f_")){
	$row =& $lAdmin->AddRow($arRes['ID'], $arRes);
	foreach( $arRes as $sKey => $sValue ){
		switch( $sKey ){
			
			case 'ID':
				continue;
				break;
			
			case 'PROPERTY_ID':
				if( empty( $aProperties[ $sValue ] ) ){
					$aProperties[ $sValue ] = CIBlockProperty::GetByID( $sValue ) -> Fetch();
				}
				$sValue = $aProperties[ $sValue ]['NAME'].' ('.$sValue.')';
				$row->AddViewField($sKey,$sValue);
				break;
			
			case 'IBLOCK_ID':
				$sValue = $aAvailIBlocks[ $sValue ].' ('.$sValue.')';
				$row->AddViewField($sKey,$sValue);
				break;
				
			default:
				$row->AddInputField($sKey,$sValue);
				break;
		}
		
	}
	//$row->AddActions($arActions);
}

$lAdmin->AddGroupActionTable(array(
"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
));
$lAdmin->NavText($oResult->GetNavPrint('Элементы списка'));

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$oResult->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

$lAdmin->AddAdminContextMenu(array());

$lAdmin->CheckListMode();
global $APPLICATION;
?>
<form method="GET" name="find_form" id="find_form" action="<?echo $APPLICATION->GetCurPage()?>">
<?php

$arFindFields = array(
	'find_f_property_id' => 'Свойство инфоблока',
	'find_f_value' => 'Значение',
	'find_f_description' => 'Описание'
);

$oFilter = new CAdminFilter($sTableID."_filter", $arFindFields);
$oFilter->Begin();
?>
<tr>
	<td>Инфоблок</td>
	<td>
		<select name="find_f_iblock_id">
			<option value=""></option>
			<?foreach( $aAvailIBlocks as $iID => $sName ):?>
				<option value="<?=$iID?>" <?if( $iID == $find_f_iblock_id ):?>selected="selected"<?endif;?>><?=$sName?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr>
	<td>Свойство инфоблока</td>
	<td><input type="text" name="find_f_property_id" value="<?echo htmlspecialcharsex($find_f_property_id)?>" size="30"></td>
</tr>
<tr>
	<td>Значение</td>
	<td><input type="text" name="find_f_value" value="<?echo htmlspecialcharsex($find_f_value)?>" size="30">&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<tr>
	<td>Описание</td>
	<td><input type="text" name="find_f_description" value="<?echo htmlspecialcharsex($find_f_description)?>" size="30">&nbsp;<?=ShowFilterLogicHelp()?></td>
</tr>
<?php
$oFilter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage().'?gem=CustomList_IBlockProperty', "form"=>"find_form"));
$oFilter->End();
?>
</form>
<?php
$lAdmin->DisplayList();
?>