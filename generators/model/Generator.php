<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tsh96\yii2doublemodel\generators\model;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\base\NotSupportedException;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends \yii\gii\Generator
{
    public $db = 'db';
    public $ns = 'common\models';
    public $tableName;
    public $modelClass;
    public $baseClass = 'yii\db\ActiveRecord';
    public $generateRelations = true;
    public $generateLabelsFromComments = false;
    public $useTablePrefix = false;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Double Model Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates two ActiveRecord class for the specified database table. An empty one you can extend and a Base one which is the same as the original model generatior.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['db', 'ns', 'tableName', 'modelClass', 'baseClass'], 'filter', 'filter' => 'trim'],
            [['ns'], 'filter', 'filter' => function($value) { return trim($value, '\\'); }],

            [['db', 'ns', 'tableName', 'baseClass'], 'required'],
            [['db', 'modelClass'], 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            [['ns', 'baseClass'], 'match', 'pattern' => '/^[\w\\\\]+$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['tableName'], 'match', 'pattern' => '/^(\w+\.)?([\w\*]+)$/', 'message' => 'Only word characters, and optionally an asterisk and/or a dot are allowed.'],
            [['db'], 'validateDb'],
            [['ns'], 'validateNamespace'],
            [['tableName'], 'validateTableName'],
            [['modelClass'], 'validateModelClass', 'skipOnEmpty' => false],
            [['baseClass'], 'validateClass', 'params' => ['extends' => ActiveRecord::className()]],
            [['generateRelations', 'generateLabelsFromComments'], 'boolean'],
            [['enableI18N'], 'boolean'],
            [['useTablePrefix'], 'boolean'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'ns' => 'Namespace',
            'db' => 'Database Connection ID',
            'tableName' => 'Table Name',
            'modelClass' => 'Model Class',
            'baseClass' => 'Base Class',
            'generateRelations' => 'Generate Relations',
            'generateLabelsFromComments' => 'Generate Labels from DB Comments',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'ns' => 'This is the namespace of the ActiveRecord class to be generated, e.g., <code>app\models</code>',
            'db' => 'This is the ID of the DB application component.',
            'tableName' => 'This is the name of the DB table that the new ActiveRecord class is associated with, e.g. <code>post</code>.
                The table name may consist of the DB schema part if needed, e.g. <code>public.post</code>.
                The table name may end with asterisk to match multiple table names, e.g. <code>tbl_*</code>
                will match tables who name starts with <code>tbl_</code>. In this case, multiple ActiveRecord classes
                will be generated, one for each matching table name; and the class names will be generated from
                the matching characters. For example, table <code>tbl_post</code> will generate <code>Post</code>
                class.',
            'modelClass' => 'This is the name of the ActiveRecord class to be generated. The class name should not contain
                the namespace part as it is specified in "Namespace". You do not need to specify the class name
                if "Table Name" ends with asterisk, in which case multiple ActiveRecord classes will be generated.',
            'baseClass' => 'This is the base class of the new ActiveRecord class. It should be a fully qualified namespaced class name.',
            'generateRelations' => 'This indicates whether the generator should generate relations based on
                foreign key constraints it detects in the database. Note that if your database contains too many tables,
                you may want to uncheck this option to accelerate the code generation process.',
            'generateLabelsFromComments' => 'This indicates whether the generator should generate attribute labels
                by using the comments of the corresponding DB columns.',
            'useTablePrefix' => 'This indicates whether the table name returned by the generated ActiveRecord class
                should consider the <code>tablePrefix</code> setting of the DB connection. For example, if the
                table name is <code>tbl_post</code> and <code>tablePrefix=tbl_</code>, the ActiveRecord class
                will return the table name as <code>{{%post}}</code>.',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function autoCompleteData()
    {
        $db = $this->getDbConnection();
        if ($db !== null) {
            return [
                'tableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
            ];
        } else {
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return ['model.php','modelbase.php'];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['ns', 'db', 'baseClass', 'generateRelations', 'generateLabelsFromComments']);
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $files = [];
        $relations = $this->generateRelations();
        $db = $this->getDbConnection();
        foreach ($this->getTableNames() as $tableName) {
            $className = $this->generateClassName($tableName);
            $tableSchema = $db->getTableSchema($tableName);
            $params = [
                'tableName' => $tableName,
                'className' => $className,
                'tableSchema' => $tableSchema,
                'labels' => $this->generateLabels($tableSchema),
                'rules' => $this->generateRules($tableSchema),
                'relations' => isset($relations[$className]) ? $relations[$className] : [],
            ];
            $realClassPath = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $className . '.php';
            if(!file_exists($realClassPath)) {
                $files[] = new CodeFile(
                    $realClassPath,
                    $this->render('model.php', $params)
                );
            }
            $files[] = new CodeFile(
                Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/base/' . $className . 'Base.php',
                $this->render('modelbase.php', $params)
            );
        }

        return $files;
    }

    /**
     * Generates the attribute labels for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated attribute labels (name => label)
     */
    public function generateLabels($table)
    {
        $labels = [];
        foreach ($table->columns as $column) {
            if ($this->generateLabelsFromComments && !empty($column->comment)) {
                $labels[$column->name] = $column->comment;
            } elseif (!strcasecmp($column->name, 'id')) {
                $labels[$column->name] = 'ID';
            } else {
                $label = Inflector::camel2words($column->name);
                if (strcasecmp(substr($label, -3), ' id') === 0) {
                    $label = substr($label, 0, -3) . ' ID';
                }
                $labels[$column->name] = $label;
            }
        }

        return $labels;
    }

    /**
     * Generates validation rules for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated validation rules
     */
    public function generateRules($table)
    {
        $types = [];
        $lengths = [];
        foreach ($table->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (!$column->allowNull && $column->defaultValue === null) {
                $types['required'][] = $column->name;
            }
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_TINYINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $types['safe'][] = $column->name;
                    break;
                default: // strings
                    if ($column->size > 0) {
                        $lengths[$column->size][] = $column->name;
                    } else {
                        $types['string'][] = $column->name;
                    }
            }
        }
        $rules = [];
        foreach ($types as $type => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
        }
        foreach ($lengths as $length => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], 'string', 'max' => $length]";
        }

        // Unique indexes rules
        try {
            $db = $this->getDbConnection();
            $uniqueIndexes = $db->getSchema()->findUniqueIndexes($table);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount == 1) {
                        $rules[] = "[['" . $uniqueColumns[0] . "'], 'unique']";
                    } elseif ($attributesCount > 1) {
                        $labels = array_intersect_key($this->generateLabels($table), array_flip($uniqueColumns));
                        $lastLabel = array_pop($labels);
                        $columnsList = implode("', '", $uniqueColumns);
                        $rules[] = "[['" . $columnsList . "'], 'unique', 'targetAttribute' => ['" . $columnsList . "'], 'message' => Yii::t('app','The combination of {firstLabels} and {lastLabel} has already been taken.',['firstLabels'=>'" . implode(', ', $labels) . "','lastLabel'=>'" . $lastLabel . "'])]";
                    }
                }
            }
        } catch (NotSupportedException $e) {
            // doesn't support unique indexes information...do nothing
        }

        return $rules;
    }

    /**
     * @return array the generated relation declarations
     */
    protected function generateRelations()
    {
        if (!$this->generateRelations) {
            return [];
        }

        $db = $this->getDbConnection();

        if (($pos = strpos($this->tableName, '.')) !== false) {
            $schemaName = substr($this->tableName, 0, $pos);
        } else {
            $schemaName = '';
        }

        $relations = [];
        foreach ($db->getSchema()->getTableSchemas($schemaName) as $table) {
            $tableName = $table->name;
            $className = $this->generateClassName($tableName);
            foreach ($table->foreignKeys as $refs) {
                $refTable = $refs[0];
                unset($refs[0]);
                $fks = array_keys($refs);
                $refClassName = $this->generateClassName($refTable);

                // Add relation for this table
                $link = $this->generateRelationLink(array_flip($refs));
                $relationName = $this->generateRelationName($relations, $className, $table, $fks[0], false);
                $relations[$className][$relationName] = [
                    "return \$this->hasOne($refClassName::className(), $link);",
                    $refClassName,
                    false,
                ];

                // Add relation for the referenced table
                $hasMany = $this->isHasManyRelation($table, $fks);
                $link = $this->generateRelationLink($refs);
                $relationName = $this->generateRelationName($relations, $refClassName, $refTable, $className, $hasMany);
                $relations[$refClassName][$relationName] = [
                    "return \$this->" . ($hasMany ? 'hasMany' : 'hasOne') . "($className::className(), $link);",
                    $className,
                    $hasMany,
                ];
            }

            if (($fks = $this->checkPivotTable($table)) === false) {
                continue;
            }
            $table0 = $fks[$table->primaryKey[0]][0];
            $table1 = $fks[$table->primaryKey[1]][0];
            $className0 = $this->generateClassName($table0);
            $className1 = $this->generateClassName($table1);

            $link = $this->generateRelationLink([$fks[$table->primaryKey[1]][1] => $table->primaryKey[1]]);
            $viaLink = $this->generateRelationLink([$table->primaryKey[0] => $fks[$table->primaryKey[0]][1]]);
            $relationName = $this->generateRelationName($relations, $className0, $db->getTableSchema($table0), $table->primaryKey[1], true);
            $relations[$className0][$relationName] = [
                "return \$this->hasMany($className1::className(), $link)->viaTable('{$table->name}', $viaLink);",
                $className1,
                true,
            ];

            $link = $this->generateRelationLink([$fks[$table->primaryKey[0]][1] => $table->primaryKey[0]]);
            $viaLink = $this->generateRelationLink([$table->primaryKey[1] => $fks[$table->primaryKey[1]][1]]);
            $relationName = $this->generateRelationName($relations, $className1, $db->getTableSchema($table1), $table->primaryKey[0], true);
            $relations[$className1][$relationName] = [
                "return \$this->hasMany($className0::className(), $link)->viaTable('{$table->name}', $viaLink);",
                $className0,
                true,
            ];
        }

        return $relations;
    }

    /**
     * Generates the link parameter to be used in generating the relation declaration.
     * @param array $refs reference constraint
     * @return string the generated link parameter.
     */
    protected function generateRelationLink($refs)
    {
        $pairs = [];
        foreach ($refs as $a => $b) {
            $pairs[] = "'$a' => '$b'";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Checks if the given table is a pivot table.
     * For simplicity, this method only deals with the case where the pivot contains two PK columns,
     * each referencing a column in a different table.
     * @param \yii\db\TableSchema the table being checked
     * @return array|boolean the relevant foreign key constraint information if the table is a pivot table,
     * or false if the table is not a pivot table.
     */
    protected function checkPivotTable($table)
    {
        $pk = $table->primaryKey;
        if (count($pk) !== 2) {
            return false;
        }
        $fks = [];
        foreach ($table->foreignKeys as $refs) {
            if (count($refs) === 2) {
                if (isset($refs[$pk[0]])) {
                    $fks[$pk[0]] = [$refs[0], $refs[$pk[0]]];
                } elseif (isset($refs[$pk[1]])) {
                    $fks[$pk[1]] = [$refs[0], $refs[$pk[1]]];
                }
            }
        }
        if (count($fks) === 2 && $fks[$pk[0]][0] !== $fks[$pk[1]][0]) {
            return $fks;
        } else {
            return false;
        }
    }

    /**
     * Generate a relation name for the specified table and a base name.
     * @param array $relations the relations being generated currently.
     * @param string $className the class name that will contain the relation declarations
     * @param \yii\db\TableSchema $table the table schema
     * @param string $key a base name that the relation name may be generated from
     * @param boolean $multiple whether this is a has-many relation
     * @return string the relation name
     */
    protected function generateRelationName($relations, $className, $table, $key, $multiple)
    {
        if (strcasecmp(substr($key, -2), 'id') === 0 && strcasecmp($key, 'id')) {
            $key = rtrim(substr($key, 0, -2), '_');
        }
        if ($multiple) {
            $key = Inflector::pluralize($key);
        }
        $name = $rawName = Inflector::id2camel($key, '_');
        $i = 0;
        while (isset($table->columns[lcfirst($name)])) {
            $name = $rawName . ($i++);
        }
        while (isset($relations[$className][lcfirst($name)])) {
            $name = $rawName . ($i++);
        }

        return $name;
    }

    /**
     * Validates the [[db]] attribute.
     */
    public function validateDb()
    {
        if (!Yii::$app->has($this->db)) {
            $this->addError('db', 'There is no application component named "db".');
        } elseif (!Yii::$app->get($this->db) instanceof Connection) {
            $this->addError('db', 'The "db" application component must be a DB connection instance.');
        }
    }

    /**
     * Validates the [[ns]] attribute.
     */
    public function validateNamespace()
    {
        $this->ns = ltrim($this->ns, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $this->ns), false);
        if ($path === false) {
            $this->addError('ns', 'Namespace must be associated with an existing directory.');
        }
    }

    /**
     * Validates the [[modelClass]] attribute.
     */
    public function validateModelClass()
    {
        if ($this->isReservedKeyword($this->modelClass)) {
            $this->addError('modelClass', 'Class name cannot be a reserved PHP keyword.');
        }
        if (substr($this->tableName, -1) !== '*' && $this->modelClass == '') {
            $this->addError('modelClass', 'Model Class cannot be blank if table name does not end with asterisk.');
        }
    }

    /**
     * Validates the [[tableName]] attribute.
     */
    public function validateTableName()
    {
        if (strpos($this->tableName, '*') !== false && substr($this->tableName, -1) !== '*') {
            $this->addError('tableName', 'Asterisk is only allowed as the last character.');

            return;
        }
        $tables = $this->getTableNames();
        if (empty($tables)) {
            $this->addError('tableName', "Table '{$this->tableName}' does not exist.");
        } else {
            foreach ($tables as $table) {
                $class = $this->generateClassName($table);
                if ($this->isReservedKeyword($class)) {
                    $this->addError('tableName', "Table '$table' will generate a class which is a reserved PHP keyword.");
                    break;
                }
            }
        }
    }

    private $_tableNames;
    private $_classNames;

    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     */
    protected function getTableNames()
    {
        if ($this->_tableNames !== null) {
            return $this->_tableNames;
        }
        $db = $this->getDbConnection();
        if ($db === null) {
            return [];
        }
        $tableNames = [];
        if (strpos($this->tableName, '*') !== false) {
            if (($pos = strrpos($this->tableName, '.')) !== false) {
                $schema = substr($this->tableName, 0, $pos);
                $pattern = '/^' . str_replace('*', '\w+', substr($this->tableName, $pos + 1)) . '$/';
            } else {
                $schema = '';
                $pattern = '/^' . str_replace('*', '\w+', $this->tableName) . '$/';
            }

            foreach ($db->schema->getTableNames($schema) as $table) {
                if (preg_match($pattern, $table)) {
                    $tableNames[] = $schema === '' ? $table : ($schema . '.' . $table);
                }
            }
        } elseif (($table = $db->getTableSchema($this->tableName, true)) !== null) {
            $tableNames[] = $this->tableName;
            $this->_classNames[$this->tableName] = $this->modelClass;
        }

        return $this->_tableNames = $tableNames;
    }

    /**
     * Generates the table name by considering table prefix.
     * If [[useTablePrefix]] is false, the table name will be returned without change.
     * @param string $tableName the table name (which may contain schema prefix)
     * @return string the generated table name
     */
    public function generateTableName($tableName)
    {
        if (!$this->useTablePrefix) {
            return $tableName;
        }

        $db = $this->getDbConnection();
        if (preg_match("/^{$db->tablePrefix}(.*?)$/", $tableName, $matches)) {
            $tableName = '{{%' . $matches[1] . '}}';
        } elseif (preg_match("/^(.*?){$db->tablePrefix}$/", $tableName, $matches)) {
            $tableName = '{{' . $matches[1] . '%}}';
        }
        return $tableName;
    }

    /**
     * Generates a class name from the specified table name.
     * @param string $tableName the table name (which may contain schema prefix)
     * @return string the generated class name
     */
    protected function generateClassName($tableName)
    {
        if (isset($this->_classNames[$tableName])) {
            return $this->_classNames[$tableName];
        }

        if (($pos = strrpos($tableName, '.')) !== false) {
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$db->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$db->tablePrefix}$/";
        if (strpos($this->tableName, '*') !== false) {
            $pattern = $this->tableName;
            if (($pos = strrpos($pattern, '.')) !== false) {
                $pattern = substr($pattern, $pos + 1);
            }
            $patterns[] = '/^' . str_replace('*', '(\w+)', $pattern) . '$/';
        }
        $className = $tableName;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $className = $matches[1];
                break;
            }
        }

        return $this->_classNames[$tableName] = Inflector::id2camel($className, '_');
    }

    /**
     * @return Connection the DB connection as specified by [[db]].
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * Checks if any of the specified columns is auto incremental.
     * @param \yii\db\TableSchema $table the table schema
     * @param array $columns columns to check for autoIncrement property
     * @return boolean whether any of the specified columns is auto incremental.
     */
    protected function isColumnAutoIncremental($table, $columns)
    {
        foreach ($columns as $column) {
            if (isset($table->columns[$column]) && $table->columns[$column]->autoIncrement) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if relation is of has many type
     *
     * @param TableSchema $table
     * @param array $fks
     * @return boolean
     * @since 2.0.5
     */
    protected function isHasManyRelation($table, $fks)
    {
        $uniqueKeys = [$table->primaryKey];
        try {
            $uniqueKeys = array_merge($uniqueKeys, $this->getDbConnection()->getSchema()->findUniqueIndexes($table));
        } catch (NotSupportedException $e) {
            // ignore
        }
        foreach ($uniqueKeys as $uniqueKey) {
            if (count(array_diff(array_merge($uniqueKey, $fks), array_intersect($uniqueKey, $fks))) === 0) {
                return false;
            }
        }
        return true;
    }
}
