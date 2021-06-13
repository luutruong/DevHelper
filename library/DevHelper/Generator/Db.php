<?php

class DevHelper_Generator_Db
{
    public static function createTable(DevHelper_Config_Base $config, array $dataClass)
    {
        $tableName = self::getTableName($config, $dataClass['name']);
        $fieldConfigs = $dataClass['fields'];
        $indexConfigs = $dataClass['indeces'];

        foreach ($config->getDataPatches() as $patchTableName => $patchTablePatches) {
            if ($patchTableName !== $tableName) {
                continue;
            }

            foreach ($patchTablePatches as $dataPatch) {
                if (!empty($dataPatch['index'])) {
                    $indexConfigs[$dataPatch['name']] = $dataPatch;
                } else {
                    $fieldConfigs[$dataPatch['name']] = $dataPatch;
                }
            }
        }

        $fields = array();
        foreach ($fieldConfigs as $field) {
            $fields[] = "`$field[name]` " . self::_getFieldDefinition($field);
        }
        $fields = implode("\n    ,", $fields);

        if (!empty($dataClass['primaryKey'])) {
            $primaryKey = ", PRIMARY KEY (`" . implode('`,`', $dataClass['primaryKey']) . "`)";
        } else {
            $primaryKey = '';
        }

        $indeces = array();
        foreach ($indexConfigs as $index) {
            $indeces[] = self::_getIndexDefinition($index);
        }
        $indeces = implode("\n    ,", $indeces);
        if (!empty($indeces)) {
            $indeces = ',' . $indeces;
        }

        $sql = <<<EOF
CREATE TABLE IF NOT EXISTS `$tableName` (
    $fields
    $primaryKey
    $indeces
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
EOF;

        return $sql;
    }

    public static function dropTable(DevHelper_Config_Base $config, array $dataClass)
    {
        $tableName = self::getTableName($config, $dataClass['name']);

        $sql = "DROP TABLE IF EXISTS `$tableName`";

        return $sql;
    }

    public static function showTables(DevHelper_Config_Base $config, $table)
    {
        return "SHOW TABLES LIKE '$table'";
    }

    public static function showColumns(DevHelper_Config_Base $config, $table, array $field)
    {
        $fieldName = $field['name'];

        return "SHOW COLUMNS FROM `$table` LIKE '$fieldName'";
    }

    public static function alterTableAddColumn(DevHelper_Config_Base $config, $table, array $field)
    {
        $fieldName = $field['name'];
        $fieldDefinition = self::_getFieldDefinition($field);

        return "ALTER TABLE `$table` ADD COLUMN `$fieldName` $fieldDefinition";
    }

    public static function alterTableModifyColumn(DevHelper_Config_Base $config, $table, array $field)
    {
        $fieldName = $field['name'];
        $fieldDefinition = self::_getFieldDefinition($field);

        return "ALTER TABLE `$table` MODIFY COLUMN `$fieldName` $fieldDefinition";
    }

    public static function alterTableDropColumn(DevHelper_Config_Base $config, $table, array $field)
    {
        $fieldName = $field['name'];

        return "ALTER TABLE `$table` DROP COLUMN `$fieldName`";
    }

    public static function showIndexes(DevHelper_Config_Base $config, $table, array $index)
    {
        $indexName = $index['name'];

        return "SHOW INDEXES FROM `$table` WHERE Key_name LIKE '$indexName'";
    }

    public static function alterTableAddIndex(DevHelper_Config_Base $config, $table, array $index)
    {
        $indexDefinition = self::_getIndexDefinition($index);

        return "ALTER TABLE `$table` ADD $indexDefinition";
    }

    public static function alterTableDropIndex(DevHelper_Config_Base $config, $table, array $index)
    {
        $indexName = $index['name'];

        return "ALTER TABLE `$table` DROP INDEX `$indexName`";
    }

    public static function getTableName(DevHelper_Config_Base $config, $name)
    {
        if (substr($name, 0, 3) === 'xf_'
            || stripos($name, $config->getPrefix()) !== false
        ) {
            return $name;
        } else {
            return 'xf_' . self::getFieldName($config, $name, true);
        }
    }

    public static function getFieldName(DevHelper_Config_Base $config, $name, $ignoreDash = false)
    {
        if ($ignoreDash OR strpos($name, '_') === false) {
            return strtolower($config->getPrefix() . '_' . $name);
        } else {
            return strtolower($name);
        }
    }

    public static function getConditionFields(array $fields)
    {
        $conditionsFields = array();

        $intTypes = array(
            XenForo_DataWriter::TYPE_BOOLEAN,
            XenForo_DataWriter::TYPE_INT,
            XenForo_DataWriter::TYPE_UINT,
            XenForo_DataWriter::TYPE_UINT_FORCED,
        );
        $imageFields = self::getImageFields($fields);

        foreach ($fields as $field) {
            if (in_array($field['name'], $imageFields)) {
                continue;
            }

            if (in_array($field['type'], $intTypes)) {
                $conditionsFields[] = $field['name'];
                continue;
            }

            if ($field['type'] == 'string' AND isset($field['length']) AND $field['length'] <= 255) {
                // this is a VARCHAR one
                $conditionsFields[] = $field['name'];
                continue;
            }
        }

        return $conditionsFields;
    }

    public static function getImageFields(array $fields)
    {
        $imageFields = array();

        foreach ($fields as $field) {
            if (substr($field['name'], -10) == 'image_date') {
                $imageFields[] = $field['name'];
            }
        }

        return $imageFields;
    }

    public static function getImageField(array $fields)
    {
        $imageFields = self::getImageFields($fields);

        if (count($imageFields) == 1) {
            // only return the image field if there is 1 image field
            // if there is no image fields or more than 1, simply ignore them all
            return $imageFields[0];
        } else {
            return false;
        }
    }

    public static function getOptionsFields(array $fields)
    {
        $optionsFields = array();

        foreach ($fields as $field) {
            if (substr($field['name'], -8) == '_options' AND $field['type'] == XenForo_DataWriter::TYPE_SERIALIZED) {
                $optionsFields[] = $field['name'];
            }
        }

        return $optionsFields;
    }

    public static function getParentField($className, array $fields)
    {
        $parentFieldNames = array(
            sprintf('%s_parent_id', $className),
            sprintf('parent_%s_id', $className),
            'parent_id',
        );

        foreach ($fields as $field) {
            if (in_array($field['name'], $parentFieldNames)) {
                return $field['name'];
            }
        }

        return false;
    }

    public static function getBreadcrumbField($className, array $fields)
    {
        $breadcrumbFieldNames = array(
            sprintf('%s_breadcrumb', $className),
            'breadcrumb',
        );

        foreach ($fields as $field) {
            if (in_array($field['name'], $breadcrumbFieldNames)) {
                return $field['name'];
            }
        }

        return false;
    }

    public static function getDataTypes()
    {
        return $types = array(
            XenForo_DataWriter::TYPE_BOOLEAN,
            XenForo_DataWriter::TYPE_STRING,
            XenForo_DataWriter::TYPE_BINARY,
            XenForo_DataWriter::TYPE_INT,
            XenForo_DataWriter::TYPE_UINT,
            XenForo_DataWriter::TYPE_UINT_FORCED,
            XenForo_DataWriter::TYPE_FLOAT,
            XenForo_DataWriter::TYPE_SERIALIZED,
        );
    }

    protected static function _getFieldDefinition($field)
    {
        switch ($field['type']) {
            case XenForo_DataWriter::TYPE_BOOLEAN:
                $dbType = 'TINYINT(4) UNSIGNED';
                break;
            case XenForo_DataWriter::TYPE_STRING:
                if (!empty($field['allowedValues'])) {
                    // ENUM
                    $dbType = 'ENUM (\'' . implode('\',\'', $field['allowedValues']) . '\')';
                } elseif (!isset($field['length']) || $field['length'] > 255) {
                    $dbType = 'TEXT';
                    if (isset($field['length'])) {
                        if ($field['length'] >= 4294967295) {
                            $dbType = 'LONGTEXT';
                        } elseif ($field['length'] >= 16777215) {
                            $dbType = 'MEDIUMTEXT';
                        }
                    }
                    if (isset($field['default'])) {
                        // BLOB/TEXT column can't have a default value
                        unset($field['default']);
                    }
                } else {
                    $dbType = 'VARCHAR(' . $field['length'] . ')';
                }
                break;
            case XenForo_DataWriter::TYPE_BINARY:
                if (!isset($field['length']) || $field['length'] > 255) {
                    $dbType = 'BLOB';
                    if (isset($field['length'])) {
                        if ($field['length'] >= 4294967295) {
                            $dbType = 'LONGBLOB';
                        } elseif ($field['length'] >= 16777215) {
                            $dbType = 'MEDIUMBLOB';
                        }
                    }
                } else {
                    $dbType = 'VARBINARY(' . $field['length'] . ')';
                }
                if (isset($field['default'])) {
                    // BLOB/TEXT column can't have a default value
                    unset($field['default']);
                }
                break;
            case XenForo_DataWriter::TYPE_INT:
                $dbType = 'INT(11)';
                if (isset($field['length']) && $field['length'] == 4) {
                    $dbType = 'TINYINT(' . $field['length'] . ')';
                }
                break;
            case XenForo_DataWriter::TYPE_UINT:
            case XenForo_DataWriter::TYPE_UINT_FORCED:
                $dbType = 'INT(10) UNSIGNED';
                if (isset($field['length']) && $field['length'] <= 3) {
                    $dbType = 'TINYINT(' . $field['length'] . ') UNSIGNED';
                }
                break;
            case XenForo_DataWriter::TYPE_FLOAT:
                $dbType = 'FLOAT';
                break;
            case 'money':
                $dbType = 'DECIMAL(13,4)';
                break;
            case XenForo_DataWriter::TYPE_SERIALIZED:
            default:
                $dbType = 'MEDIUMBLOB';
                if (isset($field['default'])) {
                    unset($field['default']);
                }
                // BLOB/TEXT column can't have a default value
                break;
        }

        $default = '';
        if (array_key_exists('default', $field)) {
            if ($field['default'] !== null) {
                $default = " DEFAULT '{$field['default']}'";
            } else {
                $default = " DEFAULT NULL";
            }
        }

        return $dbType . (!empty($field['required']) ? ' NOT NULL' : '') . $default .
            (!empty($field['autoIncrement']) ? ' AUTO_INCREMENT' : '');
    }

    public static function getIndexTypes()
    {
        return array(
            'NORMAL',
            'UNIQUE',
            'FULLTEXT',
            'SPATIAL',
        );
    }

    protected static function _getIndexDefinition($index)
    {
        $indexName = $index['name'];
        $indexType = strtoupper($index['type']);

        $definition = ($indexType != 'NORMAL' ? ($index['type'] . ' ') : '')
            . "INDEX `{$indexName}` (`" . implode('`,`', $index['fields']) . "`)";

        return $definition;
    }
}
