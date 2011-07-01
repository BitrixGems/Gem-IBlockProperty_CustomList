<?php
class BitrixGem_IBlockProperty_CustomList extends BaseBitrixGem{

	protected $aGemInfo = array(
		'GEM'			=> 'IBlockProperty_CustomList',
		'AUTHOR'		=> 'Владимир Савенков',
		'AUTHOR_LINK'	=> 'http://bitrixgems.ru/',
		'DATE'			=> '17.03.2011',
		'VERSION'		=> '0.1',
		'NAME' 			=> 'IBlockProperty_CustomList',
		'DESCRIPTION' 	=> "Свойство инфоблока типа \"Список\", но с более удобным интерфейсом, не требующим выдачи прав на управление инфоблоком контентщику.",
		'REQUIREMENTS'	=> '',
		'REQUIRED_MODULES' => array('iblock'),
		'REQUIRED_MIN_MODULE_VERSION' => '1.2.0',
	);

	protected $sTableName = 'bg_customlist_iblockproperty';
	
	//=============PUBLIC API
	
	public function getList( $aSort = array(), $aFilter = array() ){
		global $DB;
		
		$sSQL = 'SELECT * FROM `'.$this->sTableName.'`';
		$aWhere = array(); // Да, я знаю про CreateFilterEx, но так счас сильно быстрее и проще.
		if( isset( $aFilter['IBLOCK_ID'] ) ){
			if( !is_array( $aFilter['IBLOCK_ID'] ) ) $aFilter['IBLOCK_ID'] = array( $aFilter['IBLOCK_ID'] );
			$aFilter['IBLOCK_ID'] = array_map( 'intval', $aFilter['IBLOCK_ID'] );
			$aWhere[] = '(IBLOCK_ID IN ( '.implode(',',$aFilter['IBLOCK_ID']).' ) )';
		}
		if( isset( $aFilter['PROPERTY_ID'] ) ){
			$aWhere[] = '(PROPERTY_ID="'.(int)$aFilter['PROPERTY_ID'].'" )';
		}
		
		if( isset( $aFilter['ID'] ) ){
			$aWhere[] = '(ID="'.(int)$aFilter['ID'].'" )';
		}
		
		if( isset( $aFilter['VALUE'] ) ){
			$aWhere[] = '(VALUE LIKE "%'.$DB->ForSQL($aFilter['VALUE']).'%" )';
		}
		
		if( isset( $aFilter['DESCRIPTION'] ) ){
			$aWhere[] = '(DESCRIPTION LIKE "%'.$DB->ForSQL($aFilter['DESCRIPTION']).'%" )';
		}
		
		if( !empty( $aWhere ) ){
			$sSQL .= ' WHERE '.implode( ' AND ', $aWhere );
		}
		
		return $DB->Query( $sSQL );
	}
	
	public function getCustomList( $iBlockID, $iPropertyID ){
		global $DB;
		$oResult = $this->getList( array( 'DESCRIPTION' => 'ASC' ), array( 'IBLOCK_ID' => $iBlockID, 'PROPERTY_ID' => $iPropertyID ) );
		$aResult = array();
		while( $aResult[] = $oResult->Fetch() );
		array_pop( $aResult );
		return $aResult;
	}
	
	public function getValueByID( $iID ){
		$oResult = $this->getList( array(), array( 'ID' => (int)$iID ) );
		return $oResult->Fetch();
	}
	
	public function addListItem( $iIBlockID, $iPropertyID, $sValue, $sDescription ){
		global $DB;
		
		$iIBlockID = (int)$iIBlockID;
		$iPropertyID = (int)$iPropertyID;
		$sValue = $DB->ForSQL( $sValue );
		$sDescription = $DB->ForSQL( $sDescription );
		
		$mResult = $DB->Query('SELECT * FROM `'. $this->sTableName .'` WHERE IBLOCK_ID="'.$iIBlockID.'" AND PROPERTY_ID="'.$iPropertyID.'" AND VALUE="'.$sValue.'" AND DESCRIPTION="'.$sDescription.'"')->Fetch();

		if( !$mResult ){
			$mResult = $DB->Query(
				'INSERT INTO `'. $this->sTableName .'` SET IBLOCK_ID="'.$iIBlockID.'", PROPERTY_ID="'.$iPropertyID.'", VALUE="'.$sValue.'", DESCRIPTION="'.$sDescription.'"'
			);
			$mResult = $DB->LastID();
		}else{
			$mResult = $mResult['ID'];
		}
		
		return $mResult;
	}
	
	public function updateListItem( $iID, $aValues ){
		global $DB;
		foreach( $aValues as $sKey => &$mValue ){
			$mValue = '"'.$DB->ForSQL( $mValue ).'"';
		}
		return $DB->Update(
			$this->sTableName,
			$aValues,
			'WHERE ID="'.(int)$iID.'"'
		);
	}
	
	public function deleteListItem( $iID ){
		global $DB;
		return $DB->Query('DELETE FROM `'.$this->sTableName.'` WHERE ID="'.(int)$iID.'"');
	}
	
	//==============GEM INFRASTRUCTURE
	
	public function needAdminPage(){
		return true;
	}
	
	public function beforeShowAdminPage( $aRequest ){
		ob_start();
		$this->showAdminPage();
		ob_end_clean();
	}
	
	public function initGem(){
		require_once( 'include/BG_CustomList_IBlockProperty.class.php' );
	}
	
	/**
	 * @TODO: Engine и collation выдирать из БУГа
	 */
	public function installGem(){
		global $DB;
		$DB->Query('CREATE TABLE IF NOT EXISTS `'.$this->sTableName.'` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`IBLOCK_ID` INT NOT NULL ,
			`PROPERTY_ID` INT NOT NULL ,
			`VALUE` TEXT NOT NULL ,
			`DESCRIPTION` TEXT NOT NULL ,
			INDEX ( `IBLOCK_ID` , `PROPERTY_ID` )
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;
		');
	}
	
	public function unInstallGem(){
		global $DB;
		$DB->Query('DROP TABLE IF EXISTS `'.$this->sTableName.'`');
	}
	
	public function event_iblock_OnBeforeIBlockElementAdd_CheckNewValues( &$arFields ){
		if( isset( $arFields['PROPERTY_VALUES'] ) && isset( $arFields['IBLOCK_ID'] ) ){
			foreach( $arFields['PROPERTY_VALUES'] as $iPropID => $aProperty ){
				foreach( $aProperty as $sKey => $aProps ){
					$sNewPropValue = $aProps['BG_CustomList_IBlockProperty_VALUE'];
					$sNewPropDescription = $aProps['BG_CustomList_IBlockProperty_DESCRIPTION'];
					if( !empty( $sNewPropValue ) || !empty( $sNewPropDescription ) ){
						$aProperty = CIBlockProperty::GetByID($iPropID)->Fetch();
						if( $aProperty && $aProperty['USER_TYPE'] == 'BG_CustomList' ){
							$iPropertyID = $this->addListItem( $arFields['IBLOCK_ID'], $iPropID, $sNewPropValue, $sNewPropDescription );
							if( $iPropertyID ){
								$arFields['PROPERTY_VALUES'][ $iPropID ][ $sKey ]['VALUE'] = $iPropertyID;
							}
						}
					}
				}
			}
		}
		
	}
	
	public function event_iblock_OnBeforeIBlockElementUpdate_CheckNewValues( &$arFields ){
		return $this->event_iblock_OnBeforeIBlockElementAdd_CheckNewValues( &$arFields );
	}

}