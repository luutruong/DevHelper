<?php
class DevHelper_Generator_Code_Model extends DevHelper_Generator_Code_Common {
	
	protected $_addOn = null;
	protected $_config = null;
	protected $_dataClass = null;
	
	protected function __construct(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		$this->_addOn = $addOn;
		$this->_config = $config;
		$this->_dataClass = $dataClass;
	}
	
	protected function _generate() {
		$className = $this->_getClassName();
		$tableName = DevHelper_Generator_Db::getTableName($this->_config, $this->_dataClass['name']);
		$getFunctionName = self::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
		$countFunctionName = self::generateCountDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
		
		$tableAlias = $this->_dataClass['name'];
		if (in_array($tableAlias, array('group'))) {
			$tableAlias = '_' . $tableAlias;
		}
		
		$conditionFields = DevHelper_Generator_Db::getConditionFields($this->_dataClass['fields']);
		
		$this->_setClassName($className);
		$this->_setBaseClass('XenForo_Model');
		
		$this->_addCustomizableMethod("_{$getFunctionName}Customized", 'protected', array('array &$data', 'array $fetchOptions'));
		$this->_addCustomizableMethod("_prepare{$this->_dataClass['camelCase']}ConditionsCustomized", 'protected', array('array &$sqlConditions', 'array $conditions', 'array $fetchOptions'));
		$this->_addCustomizableMethod("_prepare{$this->_dataClass['camelCase']}FetchOptionsCustomized", 'protected', array('&$selectFields', '&$joinTables', 'array $fetchOptions'));
		$this->_addCustomizableMethod("_prepare{$this->_dataClass['camelCase']}OrderOptionsCustomized", 'protected', array('array &$choice', 'array &$fetchOptions'));
		
		$this->_addMethod('getList', 'public', array(
			'$conditions' => 'array $conditions = array()',
			'$fetchOptions' => 'array $fetchOptions = array()'
		), "
		
\$data = \$this->{$getFunctionName}(\$conditions, \$fetchOptions);
\$list = array();

foreach (\$data as \$id => \$row) {
	\$list[\$id] = \$row" . (empty($this->_dataClass['title_field'])
		?("['{$this->_dataClass['id_field']}']")
		:((is_array($this->_dataClass['title_field'])
		? ("['{$this->_dataClass['title_field'][0]}']['{$this->_dataClass['title_field'][1]}']")
		: ("['{$this->_dataClass['title_field']}']")))) . ";
}

return \$list;
		
		");
		
		$this->_addMethod("get{$this->_dataClass['camelCase']}ById", 'public', array('$id', '$fetchOptions' => 'array $fetchOptions = array()'), "

\$data = \$this->{$getFunctionName}(array ('{$this->_dataClass['id_field']}' => \$id), \$fetchOptions);

return reset(\$data);

		");
		
		$this->_addMethod($getFunctionName, 'public', array(
			'$conditions' => 'array $conditions = array()',
			'$fetchOptions' => 'array $fetchOptions = array()'
		), "

\$whereConditions = \$this->prepare{$this->_dataClass['camelCase']}Conditions(\$conditions, \$fetchOptions);

\$orderClause = \$this->prepare{$this->_dataClass['camelCase']}OrderOptions(\$fetchOptions);
\$joinOptions = \$this->prepare{$this->_dataClass['camelCase']}FetchOptions(\$fetchOptions);
\$limitOptions = \$this->prepareLimitFetchOptions(\$fetchOptions);

\$all = \$this->fetchAllKeyed(\$this->limitQueryResults(\"
	SELECT {$tableAlias}.*
		\$joinOptions[selectFields]
	FROM `{$tableName}` AS {$tableAlias}
		\$joinOptions[joinTables]
	WHERE \$whereConditions
		\$orderClause
	\", \$limitOptions['limit'], \$limitOptions['offset']
), '{$this->_dataClass['id_field']}');

		", '001');
			
		$this->_addMethod($getFunctionName, 'public', array(
			'$conditions' => 'array $conditions = array()',
			'$fetchOptions' => 'array $fetchOptions = array()'
		), "

\$this->_{$getFunctionName}Customized(\$all, \$fetchOptions);

return \$all;

		", '999');
		
		$this->_addMethod($countFunctionName, 'public', array(
			'$conditions' => 'array $conditions = array()',
			'$fetchOptions' => 'array $fetchOptions = array()'
		), "

\$whereConditions = \$this->prepare{$this->_dataClass['camelCase']}Conditions(\$conditions, \$fetchOptions);

\$orderClause = \$this->prepare{$this->_dataClass['camelCase']}OrderOptions(\$fetchOptions);
\$joinOptions = \$this->prepare{$this->_dataClass['camelCase']}FetchOptions(\$fetchOptions);
\$limitOptions = \$this->prepareLimitFetchOptions(\$fetchOptions);

return \$this->_getDb()->fetchOne(\"
	SELECT COUNT(*)
	FROM `{$tableName}` AS {$tableAlias}
		\$joinOptions[joinTables]
	WHERE \$whereConditions
\");

		");
		
		$this->_addMethod("prepare{$this->_dataClass['camelCase']}Conditions", 'public', array(
			'$conditions' => 'array $conditions = array()',
			'$fetchOptions' => 'array $fetchOptions = array()'
		), "

\$sqlConditions = array();
\$db = \$this->_getDb();

		");
		
		foreach ($conditionFields as $conditionField) {
			$this->_addMethod("prepare{$this->_dataClass['camelCase']}Conditions", '', array(), "

if (isset(\$conditions['{$conditionField}'])) {
	if (is_array(\$conditions['{$conditionField}'])) {
		if (!empty(\$conditions['{$conditionField}'])) {
			// only use IN condition if the array is not empty (nasty!)
			\$sqlConditions[] = \"{$tableAlias}.{$conditionField} IN (\" . \$db->quote(\$conditions['{$conditionField}']) . \")\";
		}
	} else {
		\$sqlConditions[] = \"{$tableAlias}.{$conditionField} = \" . \$db->quote(\$conditions['{$conditionField}']);
	}
}

			");
		}

		$this->_addMethod("prepare{$this->_dataClass['camelCase']}Conditions", '', array(), "

\$this->_prepare{$this->_dataClass['camelCase']}ConditionsCustomized(\$sqlConditions, \$conditions, \$fetchOptions);

return \$this->getConditionsForClause(\$sqlConditions);

		");
		
		$this->_addMethod("prepare{$this->_dataClass['camelCase']}FetchOptions", 'public', array(
			'$fetchOptions' => 'array $fetchOptions = array()'
		), "

\$selectFields = '';
\$joinTables = '';

\$this->_prepare{$this->_dataClass['camelCase']}FetchOptionsCustomized(\$selectFields,  \$joinTables, \$fetchOptions);

return array(
	'selectFields' => \$selectFields,
	'joinTables'   => \$joinTables
);

		");
		
		$this->_addMethod("prepare{$this->_dataClass['camelCase']}OrderOptions", 'public', array(
			'$fetchOptions' => 'array $fetchOptions = array()',
			'$defaultOrderSql' => '$defaultOrderSql = \'\'',
		), "

\$choices = array(	
);

\$this->_prepare{$this->_dataClass['camelCase']}OrderOptionsCustomized(\$choices, \$fetchOptions);

return \$this->getOrderByClause(\$choices, \$fetchOptions, \$defaultOrderSql);

		");

		return parent::_generate();
	}
	
	protected function _generateImageCode() {
		$imageField = DevHelper_Generator_Db::getImageField($dataClass['fields']);
		if ($imageField === false) {
			// no image field...
			return '';
		}
		
		$getFunctionName = self::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
		$dwClassName = DevHelper_Generator_Code_DataWriter::getClassName($this->_addOn, $this->_config, $this->_dataClass);
		$configPrefix = $this->_config->getPrefix();
		$imagePath = "{$configPrefix}/{$this->_dataClass['camelCase']}";
		$imagePath = strtolower($imagePath);
		
		$this->_addMethod($getFunctionName, '', array(), "

\$imageSizes = XenForo_DataWriter::create('{$dwClassName}')->getImageSizes();
foreach (\$all as &\$record) {
	\$record['images'] = array();
	foreach (\$imageSizes as \$imageSizeCode => \$imageSize) {
		\$record['images'][\$imageSizeCode] = \$this->getImageUrl(\$record, \$imageSizeCode);
	}
}

		", '100');
		
		$this->_addMethod('getImageFilePath', 'public static', array(
			'$record' => 'array $record',
			'$size' => '$size = \'l\''
		), "

\$internal = self::_getImageInternal(\$record, \$size);
		
if (!empty(\$internal)) {
	return XenForo_Helper_File::getExternalDataPath() . \$internal;
} else {
	return '';
}

		");
		
		$this->_addMethod('getImageUrl', 'public static', array(
			'$record' => 'array $record',
			'$size' => '$size = \'l\''
		), "

\$internal = self::_getImageInternal(\$record, \$size);
		
if (!empty(\$internal)) {
	return XenForo_Application::\$externalDataPath . \$internal;
} else {
	return '';
}

		");
		
		$this->_addMethod('_getImageInternal', 'protected static', array(
			'$record' => 'array $record',
			'$size'
		), "

if (empty(\$record['{$this->_dataClass['id_field']}']) OR empty(\$record['{$imageField}'])) return '';

return '/{$imagePath}/' . \$record['{$this->_dataClass['id_field']}']  . '_' . \$record['{$imageField}'] . strtolower(\$size) . '.jpg';

		");
		
		return true;
	}
	
	protected function _getClassName() {
		return self::getClassName($this->_addOn, $this->_config, $this->_dataClass);
	}
	
	public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		$g = new self($addOn, $config, $dataClass);

		return array($g->_getClassName(), $g->_generate());
	}
	
	public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'Model_' . $dataClass['camelCase']);
	}
	
	public static function generateGetDataFunctionName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return 'get' . (empty($dataClass['camelCasePlural']) ? ('All' . $dataClass['camelCase']) : $dataClass['camelCasePlural']);
	}
	
	public static function generateCountDataFunctionName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return 'count' . (empty($dataClass['camelCasePlural']) ? ('All' . $dataClass['camelCase']) : $dataClass['camelCasePlural']);
	}
}