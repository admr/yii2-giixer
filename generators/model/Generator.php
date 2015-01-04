<?php

/**
 * @link http://www.digitaldeals.cz/
 * @copyright Copyright (c) 2014 Digital Deals s.r.o.
 * @license http://www.digitaldeals.cz/license/
 */

namespace dlds\giixer\generators\model;

use Yii;
use ReflectionClass;
use yii\gii\CodeFile;
use yii\helpers\Inflector;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Jiri Svoboda <jiri.svoboda@dlds.cz>
 */
class Generator extends \yii\gii\generators\model\Generator {

    const TMPL_NAME = 'extended';

    /**
     * @var string namespace
     */
    public $ns = 'app\models\db';

    /**
     * @var string baseClass
     */
    public $baseClass = 'dlds\giixer\components\GxActiveRecord';

    /**
     * @var array containing files to be generated
     */
    public $files = array(
        'model' => 'common/models/db/base',
        'commonModel' => 'common/models/db',
        'backendModel' => 'backend/models/db',
        'frontendModel' => 'frontend/models/db',
    );

    /**
     * @var array models namespaces
     */
    public $namespaces = array(
        'model' => 'common\{ns}\base',
        'commonModel' => 'common\{ns}',
    );

    /**
     * @var array models baseClasses
     */
    public $baseClasses = array(
        'commonModel' => 'common\{ns}\base\{class}',
        'backendModel' => 'common\{ns}\{class}',
        'frontendModel' => 'common\{ns}\{class}',
    );

    /**
     * Inits generator
     */
    public function init()
    {
        if (!isset($this->templates[self::TMPL_NAME]))
        {
            $this->templates[self::TMPL_NAME] = $this->extendedTemplate();
        }

        return parent::init();
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        if (self::TMPL_NAME !== $this->template)
        {
            return parent::generate();
        }

        $files = [];
        $relations = $this->generateRelations();
        $db = $this->getDbConnection();
        foreach ($this->getTableNames() as $tableName)
        {
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

            foreach ($this->files as $tmpl => $ns)
            {
                $files[] = new CodeFile(
                        Yii::getAlias('@' . str_replace('\\', '/', $ns)) . '/' . $className . '.php', $this->render(sprintf('%s.php', $tmpl), $params)
                );
            }
        }

        return $files;
    }

    /**
     * @return array the generated relation declarations
     */
    protected function generateRelations()
    {
        if (!$this->generateRelations)
        {
            return [];
        }

        $db = $this->getDbConnection();

        if (($pos = strpos($this->tableName, '.')) !== false)
        {
            $schemaName = substr($this->tableName, 0, $pos);
        }
        else
        {
            $schemaName = '';
        }

        $relations = [];
        foreach ($db->getSchema()->getTableSchemas($schemaName) as $table)
        {
            $tableName = $table->name;
            $className = $this->generateClassName($tableName);
            foreach ($table->foreignKeys as $refs)
            {
                $refTable = $refs[0];
                unset($refs[0]);
                $fks = array_keys($refs);
                $refClassName = $this->generateClassName($refTable);

                // Add relation for this table
                $link = $this->generateRelationLink(array_flip($refs));
                $relationName = $this->generateRelationName($relations, $className, $table, $fks[0], false);
                $relations[$className][$relationName] = [
                    "return \$this->hasOne(\\$this->ns\\$refClassName::className(), $link);",
                    $refClassName,
                    false,
                ];

                // Add relation for the referenced table
                $hasMany = false;
                if (count($table->primaryKey) > count($fks))
                {
                    $hasMany = true;
                }
                else
                {
                    foreach ($fks as $key)
                    {
                        if (!in_array($key, $table->primaryKey, true))
                        {
                            $hasMany = true;
                            break;
                        }
                    }
                }
                $link = $this->generateRelationLink($refs);
                $relationName = $this->generateRelationName($relations, $refClassName, $refTable, $className, $hasMany);
                $relations[$refClassName][$relationName] = [
                    "return \$this->" . ($hasMany ? 'hasMany' : 'hasOne') . "(\\$this->ns\\$className::className(), $link);",
                    $className,
                    $hasMany,
                ];
            }

            if (($fks = $this->checkPivotTable($table)) === false)
            {
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
                "return \$this->hasMany(\\$this->ns\\$className1::className(), $link)->viaTable('" . $this->generateTableName($table->name) . "', $viaLink);",
                $className1,
                true,
            ];

            $link = $this->generateRelationLink([$fks[$table->primaryKey[0]][1] => $table->primaryKey[0]]);
            $viaLink = $this->generateRelationLink([$table->primaryKey[1] => $fks[$table->primaryKey[1]][1]]);
            $relationName = $this->generateRelationName($relations, $className1, $db->getTableSchema($table1), $table->primaryKey[0], true);
            $relations[$className1][$relationName] = [
                "return \$this->hasMany(\\$this->ns\\$className0::className(), $link)->viaTable('" . $this->generateTableName($table->name) . "', $viaLink);",
                $className0,
                true,
            ];
        }

        return $relations;
    }

    /**
     * @return string current file ns
     */
    public function getNs($file)
    {
        if (isset($this->namespaces[$file]))
        {
            $namespace = str_replace('app\\', '', $this->ns);

            return str_replace('{ns}', $namespace, $this->namespaces[$file]);
        }

        return $this->ns;
    }

    /**
     * @return string cuurent file baseClass
     */
    public function getBaseClass($file, $class)
    {
        if (isset($this->baseClasses[$file]))
        {
            $namespace = str_replace('app\\', '', $this->ns);

            $baseClass = str_replace('{ns}', $namespace, $this->baseClasses[$file]);

            return str_replace('{class}', $class, $baseClass);
        }

        return $this->baseClass;
    }

    /**
     * Validates the [[ns]] attribute.
     */
    public function validateNamespace()
    {
        parent::validateNamespace();

        $this->ns = ltrim($this->ns, '\\');
        if (false === strpos($this->ns, 'app\\'))
        {
            $this->addError('ns', '@app namespace must be used.');
        }
    }

    /**
     * Returns the root path to the default code template files.
     * The default implementation will return the "templates" subdirectory of the
     * directory containing the generator class file.
     * @return string the root path to the default code template files.
     */
    public function defaultTemplate()
    {
        $class = new ReflectionClass($this);

        $classFileName = str_replace(Yii::getAlias('@dlds/giixer'), Yii::getAlias('@yii/gii'), $class->getFileName());

        return dirname($classFileName) . '/default';
    }

    /**
     * Returns the root path to the extended code template files.
     * The extended implementation will return the "templates" subdirectory of the
     * directory containing the generator class file.
     * @return string the root path to the extended code template files.
     */
    public function extendedTemplate()
    {
        $class = new ReflectionClass($this);

        return dirname($class->getFileName()) . '/' . self::TMPL_NAME;
    }

}