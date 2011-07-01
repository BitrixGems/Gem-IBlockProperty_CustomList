<?
IncludeModuleLangFile(__FILE__);

AddEventHandler('iblock', 'OnIBlockPropertyBuildList', array('BG_CustomList_IBlockProperty', 'GetUserTypeDescription'), 5000);

/**
 *
 * @TODO - приделать фильтрацию.
 */
class BG_CustomList_IBlockProperty{
	function GetUserTypeDescription(){
		return array(
			"PROPERTY_TYPE"				=> "S",
			"USER_TYPE"					=> "BG_CustomList",
			"DESCRIPTION"				=> 'Произвольный список значений',
			"GetPropertyFieldHtml"		=> array("BG_CustomList_IBlockProperty","GetPropertyFieldHtml"),
			"GetAdminListViewHTML"		=> array("BG_CustomList_IBlockProperty","GetAdminListViewHTML"),
			"GetPublicViewHTML"			=> array("BG_CustomList_IBlockProperty","GetPublicViewHTML"),
			"GetPublicEditHTML"			=> array("BG_CustomList_IBlockProperty","GetPublicEditHTML"),
			"ConvertFromDB"				=> array("BG_CustomList_IBlockProperty","ConvertFromDB"),
		);
	}
	function GetPropertyFieldHtml($aProperty, $aValue, $sHTMLControlName){
		$aList = BitrixGems::getGem('IBlockProperty_CustomList')->getCustomList( $aProperty['IBLOCK_ID'], $aProperty['ID'] );
		$sSelect = '<select name="'.$sHTMLControlName['VALUE'].'"><option value=""></option>';
		foreach( $aList as $aListItem ){
			$sSelect .= '<option '.(( $aListItem['ID'] == @$aValue['VALUE']['ID'] )?'selected="selected"':'').' value="'.$aListItem['ID'].'">'.$aListItem['DESCRIPTION'].' ('.$aListItem['VALUE'].')'.'</option>';
		}
		$sSelect.= '</select> <a href="/bitrix/admin/bitrixgems_simpleresponder.php?gem=CustomList_IBlockProperty&find_f_iblock_id='.$aProperty['IBLOCK_ID'].'&find_f_property_id='.$aProperty['ID'].'">Редактировать &gt;&gt;&gt;</a>';
		$sCustomInput = '<input type="text" name="'.str_replace( '[VALUE]', '[BG_CustomList_IBlockProperty_VALUE]', $sHTMLControlName['VALUE']).'" value="" /> Описание: <input type="text" name="'.str_replace( '[DESCRIPTION]', '[BG_CustomList_IBlockProperty_DESCRIPTION]', $sHTMLControlName['DESCRIPTION']).'" value="" />';
		$sResult = $sSelect.'<br />'.$sCustomInput;
		return $sResult;
	}
	
	/**
	 *
	 * @TODO Пофиксить копипасту!
	 */
	function GetPublicEditHTML( $aProperty, $aValue, $sHTMLControlName ){
		$aList = BitrixGems::getGem('IBlockProperty_CustomList')->getCustomList( $aProperty['IBLOCK_ID'], $aProperty['ID'] );
		$sSelect = '<select name="'.$sHTMLControlName['VALUE'].'"><option value=""></option>';
		foreach( $aList as $aListItem ){
			$sSelect .= '<option '.(( $aListItem['ID'] == @$aValue['VALUE']['ID'] )?'selected="selected"':'').' value="'.$aListItem['ID'].'">'.$aListItem['DESCRIPTION'].' ('.$aListItem['VALUE'].')'.'</option>';
		}
		$sSelect.= '</select>';
		$sCustomInput = '<input type="text" name="'.str_replace( '[VALUE]', '[BG_CustomList_IBlockProperty_VALUE]', $sHTMLControlName['VALUE']).'" value="" /> Описание: <input type="text" name="'.str_replace( '[DESCRIPTION]', '[BG_CustomList_IBlockProperty_DESCRIPTION]', $sHTMLControlName['DESCRIPTION']).'" value="" />';
		$sResult = $sSelect.'<br />'.$sCustomInput;
		return $sResult;
	}
	
	
	function GetPublicViewHTML( $aProperty, $aValue, $sHTMLControlName ){
		$mResult = '';
		if( !empty( $aValue['VALUE']['VALUE'] ) ){
			$mResult = $aValue['VALUE']['VALUE'].' ('.$aValue['VALUE']['DESCRIPTION'].')';
		}
		return $mResult;
	}

	function GetAdminListViewHTML($aProperty, $aValue, $sHTMLControlName){
		return self::GetPublicViewHTML( $aProperty, $aValue, $sHTMLControlName );
	}
	
	function ConvertFromDB($arProperty, $value){
		if(strlen($value["VALUE"])>0){
			$value["VALUE"] = BitrixGems::getGem('IBlockProperty_CustomList')->getValueByID($value["VALUE"]);
		}
		return $value;
	}
	
	
}

?>
