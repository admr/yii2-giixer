<?php
/**
 * @link http://www.digitaldeals.cz/
 * @copyright Copyright (c) 2014 Digital Deals s.r.o.
 * @license http://www.digitaldeals.cz/license/
 */

namespace dlds\giixer\generators\ultimate;

use Yii;
use ReflectionClass;
use yii\gii\CodeFile;
use yii\helpers\ArrayHelper;
use dlds\giixer\components\helpers\GxModelHelper;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Jiri Svoboda <jiri.svoboda@dlds.cz>
 */
class Generator extends \yii\gii\generators\model\Generator {

    /**
     * TMPLs
     */
    const TMPL_MODEL_DIR_PATH = 'model';
    const TMPL_CRUD_DIR_PATH = 'crud';
    const TMPL_COMPONENT_DIR_PATH = 'component';
    const TMPL_IDENTICIFATION = 'default';

    /**
     * NSs
     */
    const NS_ACTIVE_RECORD = 'app\models\db';

    /**
     * Paths
     */
    const PATH_MODEL_MESSAGE_CATEGORY = 'models/%s';

    /**
     * Defaults
     */
    const DEFAULT_TIMESTAMP_CREATED_AT_ATTR = 'created_at';
    const DEFAULT_TIMESTAMP_UPDATED_AT_ATTR = 'updated_at';

    /**
     * Components names
     */
    const COMPONENT_IMAGE_HELPER = 'imageHelper';

    /**
     * Suffix
     */
    const SUFFIX_CLASS_IMAGE_HELPER = 'ImageHelper';

    /**
     * @var string default ns
     */
    public $ns = 'app\models\db';

    /**
     * @var string model baseClass
     */
    public $modelBaseClass = 'dlds\giixer\components\GxActiveRecord';

    /**
     * @var string query baseClass
     */
    public $queryBaseClass = 'dlds\giixer\components\GxActiveQuery';

    /**
     * @var string imageHelper baseClass
     */
    public $helperImageBaseClass = 'dlds\giixer\components\helpers\GxImageHelper';

    /**
     * @var boolean indicates if language mutations should be generated
     */
    public $generateMutation = false;

    /**
     * @var string mutation join table means table holds relation between model and langauge
     */
    public $mutationJoinTableName;

    /**
     * @var string mutation source table name means table which holds languages
     */
    public $mutationSourceTableName;

    /**
     * @var boolean indicates if sluggable behavior should be generated
     */
    public $generateSluggableMutation = false;

    /**
     * @var string defines sluggable source attributes
     * for multiple use comma separation like "firstname,lastname"
     */
    public $sluggableMutationAttribute;

    /**
     * @var boolean indicates if sluggable behavior should ensure uniqueness
     */
    public $sluggableMutationEnsureUnique = true;

    /**
     * @var boolean indicates if sluggable behavior should be imutable
     */
    public $sluggableMutationImutable = true;

    /**
     * @var boolean indicates timestamp behavior should be generated
     */
    public $generateTimestampBehavior = false;

    /**
     * @var string defines timestamp created at attribute
     */
    public $timestampCreatedAtAttribute = 'created_at';

    /**
     * @var string defines timestamp updated at attribute
     */
    public $timestampUpdatedAtAttribute = 'updated_at';

    /**
     * @var boolean indicates if gallery behavior should be generated
     */
    public $generateGalleryBehavior = false;

    /**
     * @var array models namespaces
     */
    public $nsMap = array(
        'model' => 'common\{ns}\base',
        'query' => 'common\{ns}',
        'helper' => 'common\{ns}\components\helpers',
        'commonModel' => 'common\{ns}',
        'frontendModel' => 'app\{ns}',
        'backendModel' => 'app\{ns}',
        'frontendQuery' => 'app\{ns}',
        'backendQuery' => 'app\{ns}',
    );

    /**
     * @var array models baseClasses
     */
    public $baseClassesMap = array(
        'commonModel' => 'common\{ns}\base\{class}',
        'backendModel' => 'common\{ns}\{class}',
        'frontendModel' => 'common\{ns}\{class}',
        'backendQuery' => 'common\{ns}\{class}',
        'frontendQuery' => 'common\{ns}\{class}',
    );

    /**
     * @var array containing files to be generated
     */
    public $modelFilesMap = array(
        'model' => 'common/{ns}/base',
        'commonModel' => 'common/{ns}',
        'backendModel' => 'backend/{ns}',
        'frontendModel' => 'frontend/{ns}',
    );

    /**
     * @var array containing files to be generated
     */
    public $queryFilesMap = array(
        'query' => 'common/{ns}',
        'backendQuery' => 'backend/{ns}',
        'frontendQuery' => 'frontend/{ns}',
    );

    /**
     * @var array components map
     */
    public $componentsFilesMap = array(
        self::COMPONENT_IMAGE_HELPER => 'common\{ns}\images',
    );

    /**
     * @var array static namespaces
     */
    public $staticNs = [];

    /**
     * @var array used classes
     */
    public $usedClasses = [];

    /**
     * Inits generator
     */
    public function init()
    {
        if (!isset($this->templates[self::TMPL_IDENTICIFATION]))
        {
            $this->templates[self::TMPL_IDENTICIFATION] = $this->tmplsRootDir();
        }

        $this->staticNs = Yii::$app->getModule('gii')->modelsNamespaces;

        if (!empty($this->staticNs) && !is_array($this->staticNs))
        {
            throw new \yii\base\ErrorException('Gii Model Namespaces should be array');
        }

        $this->generateQuery = true;
        $this->generateRelations = true;
        $this->enableI18N = true;
        $this->template = self::TMPL_IDENTICIFATION;
        $this->messageCategory = null;
        $this->queryNs = null;
        $this->ns = self::NS_ACTIVE_RECORD;

        return parent::init();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'ns' => 'Default Namespace',
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
    public function rules()
    {
        $rules = parent::rules();

        GxModelHelper::removeValidationRules($rules, 'required', ['queryNs']);

        return ArrayHelper::merge([
                ['modelClass', 'default', 'value' => function($model, $attribute) {
                        return $this->generateModelClass();
                    }],
                ['messageCategory', 'default', 'value' => function($model, $attribute) {
                        return $this->generateModelMessageCategory();
                    }],
                [['generateMutation', 'generateSluggableMutation', 'sluggableMutationEnsureUnique', 'sluggableMutationImutable', 'generateTimestampBehavior', 'generateGalleryBehavior'], 'boolean'],
                [['mutationJoinTableName', 'mutationSourceTableName'], 'filter', 'filter' => 'trim'],
                [['mutationJoinTableName', 'mutationSourceTableName'], 'required', 'when' => function($model) {
                    return $model->generateMutation;
                }, 'whenClient' => "function (attribute, value) {
                        return $('#generator-generatemutation').is(':checked');
                    }"],
                [['mutationJoinTableName', 'mutationSourceTableName'], 'match', 'pattern' => '/^(\w+\.)?([\w\*]+)$/', 'message' => 'Only word characters, and optionally an asterisk and/or a dot are allowed.'],
                [['mutationJoinTableName', 'mutationSourceTableName'], 'validateTableNameExtended'],
                [['sluggableMutationAttribute'], 'required', 'when' => function($model) {
                    return $model->generateSluggableMutation;
                }, 'whenClient' => "function (attribute, value) {
                        return $('#generator-generatesluggablemutation').is(':checked');
                    }"],
                [['sluggableMutationAttribute'], 'validateAttributeExistence', 'params' => ['tblAttr' => 'mutationJoinTableName']],
                [['timestampCreatedAtAttribute', 'timestampUpdatedAtAttribute'], 'validateAttributeExistence', 'params' => ['tblAttr' => 'tableName'], 'when' => function($model) {
                    return $model->generateTimestampBehavior;
                }, 'whenClient' => "function (attribute, value) {
                        return $('#generator-generatetimestampbehavior').is(':checked');
                    }"],
                [['timestampCreatedAtAttribute', 'timestampUpdatedAtAttribute'], 'required', 'when' => function($model) {
                    return $model->generateTimestampBehavior;
                }, 'whenClient' => "function (attribute, value) {
                        return $('#generator-generatetimestampbehavior').is(':checked');
                    }"],
                ], $rules);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Ultimate Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator handles all in one. It generates model together with controlers. Supports multilangual (dlds/yii2-rels), sortable (dlds/yii2-sortable).';
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'ns' => 'This is the default namespace of the ActiveRecord class to be generated, e.g., <code>app\models</code>. It is used when no static (predefind) namespace is found',
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
            'generateMutation' => 'This indicates whether the generator should generate relation model between application language table (model) and generating model.',
            'mutationJoinTableName' => 'This is the of "Mapping" table representing the many-to-many relationship between application languages and generating model.',
            'mutationSourceTableName' => 'This is the name of the source table holds application languages used in many-to-many relationship.',
            'generateSluggable' => 'This indicates whether the generator should generate Yii2 Sluggable behavior in main model class.',
            'generateTimestampBehavior' => 'This indicates whether the generator should generate Yii2 Timestamp behavior in main model class.',
            'timestampCreatedAtAttribute' => 'This is the name of the table attribute which should be used ad created at timestamp value.',
            'timestampUpdatedAtAttribute' => 'This is the name of the table attribute which should be used ad updated at timestamp value.',
            'generateGalleryBehavior' => 'This indicates whether the generator should generate dlds/yii2-gallerymanager behavior in main model class.',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function autoCompleteData()
    {
        $db = $this->getDbConnection();
        if ($db !== null)
        {
            return [
                'tableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
                'mutationJoinTableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
                'mutationSourceTableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
            ];
        }
        else
        {
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        if (self::TMPL_IDENTICIFATION !== $this->template)
        {
            return parent::generate();
        }

        $files = [];
        $relations = $this->generateRelations();
        //$relations = [];
        $db = $this->getDbConnection();

        foreach ($this->getTableNames() as $tableName)
        {
            // model :
            $modelClassName = $this->generateClassName($tableName);
            $queryClassName = ($this->generateQuery) ? $this->generateQueryClassName($modelClassName) : false;
            $tableSchema = $db->getTableSchema($tableName);
            $params = [
                'tableName' => $tableName,
                'className' => $modelClassName,
                'queryClassName' => $queryClassName,
                'tableSchema' => $tableSchema,
                'labels' => $this->generateLabels($tableSchema),
                'rules' => $this->generateRules($tableSchema),
                'relations' => isset($relations[$tableName]) ? $relations[$tableName] : [],
            ];

            if ($this->generateGalleryBehavior)
            {
                $helperClassName = sprintf('%s%s', $modelClassName, self::SUFFIX_CLASS_IMAGE_HELPER);

                $ns = $this->getComponentNs(self::COMPONENT_IMAGE_HELPER, $helperClassName);

                $this->usedClasses[] = sprintf('%s\%s', $ns, $helperClassName);

                $path = str_replace('\\', '/', $ns);

                $files[] = new CodeFile(
                    Yii::getAlias('@'.$path).'/'.$helperClassName.'.php', $this->render(sprintf('%s/%s.php', self::TMPL_COMPONENT_DIR_PATH, self::COMPONENT_IMAGE_HELPER), [
                        'namespace' => $ns,
                        'className' => $helperClassName,
                        'assignedModelName' => $modelClassName,
                    ])
                );
            }

            foreach ($this->modelFilesMap as $tmpl => $ns)
            {
                $path = '@'.str_replace('\\', '/', str_replace('{ns}', $this->getNs($modelClassName), $ns));

                $files[] = new CodeFile(
                    Yii::getAlias($path).'/'.$modelClassName.'.php', $this->render(sprintf('%s/%s.php', self::TMPL_MODEL_DIR_PATH, $tmpl), $params)
                );
            }

            // query :
            if ($queryClassName)
            {
                $params = [
                    'className' => $queryClassName,
                    'modelClassName' => $modelClassName,
                ];

                foreach ($this->queryFilesMap as $tmpl => $ns)
                {
                    $path = '@'.str_replace('\\', '/', str_replace('{ns}', $this->getNs($queryClassName), $ns));

                    $files[] = new CodeFile(
                        Yii::getAlias($path).'/'.$queryClassName.'.php', $this->render(sprintf('%s/%s.php', self::TMPL_MODEL_DIR_PATH, $tmpl), $params)
                    );
                }
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

        $schema = $db->getSchema();
        if ($schema->hasMethod('getSchemaNames'))
        { // keep BC to Yii versions < 2.0.4
            try
            {
                $schemaNames = $schema->getSchemaNames();
            }
            catch (\yii\base\NotSupportedException $e)
            {
                // schema names are not supported by schema
            }
        }
        if (!isset($schemaNames))
        {
            if (($pos = strpos($this->tableName, '.')) !== false)
            {
                $schemaNames = [substr($this->tableName, 0, $pos)];
            }
            else
            {
                $schemaNames = [''];
            }
        }

        $relations = [];
        foreach ($schemaNames as $schemaName)
        {
            foreach ($db->getSchema()->getTableSchemas($schemaName) as $table)
            {
                $className = $this->generateClassName($table->fullName);
                foreach ($table->foreignKeys as $refs)
                {
                    $refTable = $refs[0];
                    $refTableSchema = $db->getTableSchema($refTable);
                    unset($refs[0]);
                    $fks = array_keys($refs);
                    $refClassName = $this->generateClassName($refTable);

                    // Add relation for this table
                    $link = $this->generateRelationLink(array_flip($refs));
                    $relationName = $this->generateRelationName($relations, $table, $fks[0], false);
                    $relations[$table->fullName][$relationName] = [
                        "return \$this->hasOne(\\".$this->getNs($refClassName, true)."\\$refClassName::className(), $link);",
                        $refClassName,
                        false,
                    ];

                    // Add relation for the referenced table
                    $uniqueKeys = [$table->primaryKey];
                    try
                    {
                        $uniqueKeys = array_merge($uniqueKeys, $db->getSchema()->findUniqueIndexes($table));
                    }
                    catch (NotSupportedException $e)
                    {
                        // ignore
                    }
                    $hasMany = true;
                    foreach ($uniqueKeys as $uniqueKey)
                    {
                        if (count(array_diff(array_merge($uniqueKey, $fks), array_intersect($uniqueKey, $fks))) === 0)
                        {
                            $hasMany = false;
                            break;
                        }
                    }
                    $link = $this->generateRelationLink($refs);
                    $relationName = $this->generateRelationName($relations, $refTableSchema, $className, $hasMany);
                    $relations[$refTableSchema->fullName][$relationName] = [
                        "return \$this->".($hasMany ? 'hasMany' : 'hasOne')."(\\".$this->getNs($className, true)."\\$className::className(), $link);",
                        $className,
                        $hasMany,
                    ];
                }

                if (($fks = $this->checkPivotTable($table)) === false)
                {
                    continue;
                }

                $relations = $this->generateManyManyRelations($table, $fks, $relations);
            }
        }

        return $relations;
    }

    /**
     * Generates relations using a junction table by adding an extra viaTable().
     * @param \yii\db\TableSchema the table being checked
     * @param array $fks obtained from the checkPivotTable() method
     * @param array $relations
     * @return array modified $relations
     */
    private function generateManyManyRelations($table, $fks, $relations)
    {
        $db = $this->getDbConnection();
        $table0 = $fks[$table->primaryKey[0]][0];
        $table1 = $fks[$table->primaryKey[1]][0];
        $className0 = $this->generateClassName($table0);
        $className1 = $this->generateClassName($table1);
        $table0Schema = $db->getTableSchema($table0);
        $table1Schema = $db->getTableSchema($table1);

        $link = $this->generateRelationLink([$fks[$table->primaryKey[1]][1] => $table->primaryKey[1]]);
        $viaLink = $this->generateRelationLink([$table->primaryKey[0] => $fks[$table->primaryKey[0]][1]]);
        $relationName = $this->generateRelationName($relations, $table0Schema, $table->primaryKey[1], true);
        $relations[$table0Schema->fullName][$relationName] = [
            "return \$this->hasMany(\\".$this->getNs($className1, true)."\\$className1::className(), $link)->viaTable('".$this->generateTableName($table->name)."', $viaLink);",
            $className1,
            true,
        ];

        $link = $this->generateRelationLink([$fks[$table->primaryKey[0]][1] => $table->primaryKey[0]]);
        $viaLink = $this->generateRelationLink([$table->primaryKey[1] => $fks[$table->primaryKey[1]][1]]);
        $relationName = $this->generateRelationName($relations, $table1Schema, $table->primaryKey[0], true);
        $relations[$table1Schema->fullName][$relationName] = [
            "return \$this->hasMany(\\".$this->getNs($className0, true)."\\$className0::className(), $link)->viaTable('".$this->generateTableName($table->name)."', $viaLink);",
            $className0,
            true,
        ];

        return $relations;
    }

    /**
     * Generates model class name
     */
    public function generateModelClass()
    {
        return \yii\helpers\BaseInflector::id2camel($this->tableName, '_');
    }

    /**
     * Generates model class name
     */
    public function generateModelMessageCategory()
    {
        $modelClass = $this->generateModelClass();

        return \yii\helpers\BaseInflector::camel2id($modelClass, '/');
    }

    /**
     * Retrieves namespace
     */
    public function getNs($className, $root = false)
    {
        $namespace = false;

        foreach ($this->staticNs as $regex => $ns)
        {
            if (preg_match('%'.$regex.'%', $className))
            {
                $namespace = $ns;

                break;
            }
        }

        if (false === $namespace)
        {
            $namespace = $this->ns;
        }

        if (!$root)
        {
            return str_replace('app\\', '', $namespace);
        }

        return $namespace;
    }

    /**
     * @return string current file ns
     */
    public function getFileNs($file, $className = null)
    {
        if (isset($this->nsMap[$file]))
        {
            return str_replace('{ns}', $this->getNs($className), $this->nsMap[$file]);
        }

        return $this->ns;
    }

    /**
     * @return string cuurent file baseClass
     */
    public function getBaseClass($file, $className, $default = false)
    {
        if (isset($this->baseClassesMap[$file]))
        {
            $baseClass = str_replace('{ns}', $this->getNs($className), $this->baseClassesMap[$file]);

            return str_replace('{class}', $className, $baseClass);
        }

        return (false !== $default) ? $default : $this->modelBaseClass;
    }

    /**
     * @return string current component ns
     */
    public function getComponentNs($file, $className)
    {
        if (isset($this->componentsFilesMap[$file]))
        {
            return str_replace('{ns}', $this->getNs($className), $this->componentsFilesMap[$file]);
        }

        return $this->ns;
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        // @todo make 'query.php' to be required before 2.1 release
        return [
            self::TMPL_MODEL_DIR_PATH => [
                'model.php',
                'backendModel.php',
                'frontendModel.php',
                'commonModel.php',
                'query.php',
                'backendQuery.php',
                'frontendQuery.php',
            ],
            self::TMPL_CRUD_DIR_PATH => [
                'controller.php',
                'search.php',
                'views/view.php',
                'views/update.php',
                'views/index.php',
                'views/create.php',
                'views/_search.php',
                'views/_form.php',
            ],
            self::TMPL_COMPONENT_DIR_PATH => [
                'imageHelper.php'
            ],
        ];
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
     * Validates given attribute as table name.
     */
    public function validateTableNameExtended($attribute, $params)
    {
        if (strpos($this->$attribute, '*') !== false && substr_compare($this->$attribute, '*', -1, 1))
        {
            $this->addError($attribute, 'Asterisk is only allowed as the last character.');

            return;
        }
        $tables = $this->getTableNamesExtended($attribute);
        if (empty($tables))
        {
            $this->addError($attribute, "Table '{$this->$attribute}' does not exist.");
        }
        else
        {
            foreach ($tables as $table)
            {
                $class = $this->generateClassName($table);
                if ($this->isReservedKeyword($class))
                {
                    $this->addError($attribute, "Table '$table' will generate a class which is a reserved PHP keyword.");
                    break;
                }
            }
        }
    }

    /**
     * Validates the template selection.
     * This method validates whether the user selects an existing template
     * and the template contains all required template files as specified in [[requiredTemplates()]].
     */
    public function validateTemplate()
    {
        $templates = $this->templates;
        if (!isset($templates[$this->template]))
        {
            $this->addError('template', 'Invalid template selection.');
        }
        else
        {
            $templateRoot = $this->templates[$this->template];
            foreach ($this->requiredTemplates() as $subDir => $tmpls)
            {
                foreach ($tmpls as $tmpl)
                {
                    $filePath = sprintf('%s/%s/%s', $templateRoot, $subDir, $tmpl);

                    if (!is_file($filePath))
                    {
                        $this->addError('template', "Unable to find the required code template file '$filePath'.");
                    }
                }
            }
        }
    }

    /**
     * Validates given attribute as table attribute name
     * @param string $attribute
     * @param array $params must contains attribute "tblAttribute" which holds
     * name of generator attribute where appropriate table name is held.
     */
    public function validateAttributeExistence($attribute, $params)
    {
        if (is_array($params))
        {
            $tblAttr = ArrayHelper::getValue($params, 'tblAttr');
        }
        else
        {
            $tblAttr = false;
        }

        if (!$tblAttr || !isset($this->$tblAttr))
        {
            throw new \yii\base\InvalidConfigException('Invalid validator rule: a rule "validateAttributeExistence" requires additional parameter "tblAttr" to be specified which represents one of the generator\'s attribute holding appropriate table name.');
        }

        $db = $this->getDbConnection();
        $schema = $db->getTableSchema($this->$tblAttr, true);

        if ($schema)
        {
            $attributes = explode(',', $this->$attribute);

            foreach ($attributes as $attr)
            {
                $attr = trim($attr);

                if (!in_array($attr, $schema->columnNames))
                {
                    $this->addError($attribute, sprintf("Table '%s' does not contain attribute '%s'.", $this->$tblAttr, $attr));
                }
            }
        }
        else
        {
            $this->addError($attribute, sprintf("Schema for join table '%s' does not available.", $this->$tblAttr));
        }
    }

    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     */
    protected function getTableNamesExtended($attribute)
    {
        $db = $this->getDbConnection();
        if ($db === null)
        {
            return [];
        }
        $tableNames = [];
        if (strpos($this->$attribute, '*') !== false)
        {
            if (($pos = strrpos($this->$attribute, '.')) !== false)
            {
                $schema = substr($this->$attribute, 0, $pos);
                $pattern = '/^'.str_replace('*', '\w+', substr($this->$attribute, $pos + 1)).'$/';
            }
            else
            {
                $schema = '';
                $pattern = '/^'.str_replace('*', '\w+', $this->$attribute).'$/';
            }

            foreach ($db->schema->getTableNames($schema) as $table)
            {
                if (preg_match($pattern, $table))
                {
                    $tableNames[] = $schema === '' ? $table : ($schema.'.'.$table);
                }
            }
        }
        elseif (($table = $db->getTableSchema($this->$attribute, true)) !== null)
        {
            $tableNames[] = $this->$attribute;
            $this->classNames[$this->$attribute] = $this->modelClass;
        }

        return $tableNames;
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

        return dirname($classFileName).'/default';
    }

    /**
     * Returns the root path to the extended code template files.
     * The extended implementation will return the "templates" subdirectory of the
     * directory containing the generator class file.
     * @return string the root path to the extended code template files.
     */
    public function tmplsRootDir()
    {
        $class = new ReflectionClass($this);

        return sprintf('%s/%s', dirname($class->getFileName()), self::TMPL_IDENTICIFATION);
    }

    /**
     * Indicates if behaviors should be generated into model
     * @return boolean
     */
    public function canGenerateBehaviors()
    {
        return !empty($this->getBehaviorsToGenerate());
    }

    /**
     * Retrieves behavior specification
     * @return array behavior specification
     */
    public function getBehaviorsToGenerate()
    {
        $behaviors = [];

        if ($this->generateMutation)
        {
            $modelClassName = $this->generateClassName($this->mutationJoinTableName);

            $db = $this->getDbConnection();
            $tableSchema = $db->getTableSchema($this->mutationJoinTableName);

            $mutationableAttrs = array_diff($tableSchema->columnNames, $tableSchema->primaryKey);

            $behaviors['languages'] = [
                'class' => '\dlds\rels\components\Behavior::className()',
                'config' => [
                    'AppCategoryLanguage::className()',
                    'AppCategoryLanguage::RELNAME_CATEGORY',
                    'AppCategoryLanguage::RELNAME_LANGUAGE',
                    'static::RELNAME_CURRENT_LANGUAGE',
                ],
                'attrs' => $mutationableAttrs,
            ];
        }

        if ($this->generateGalleryBehavior)
        {
            $modelClassName = $this->generateClassName($this->tableName);

            $helperClassName = sprintf('%s%s', $modelClassName, self::SUFFIX_CLASS_IMAGE_HELPER);

            $behaviors['gallery_manager'] = [
                'class' => '\dlds\galleryManager\GalleryBehavior::className()',
                'type' => sprintf('%s::getType()', $helperClassName),
                'directory' => sprintf('%s::getDirectory()', $helperClassName),
                'url' => sprintf('%s::getUrl()', $helperClassName),
                'versions' => sprintf('%s::getVersions()', $helperClassName),
                'extension' => sprintf('%s::getExtension()', $helperClassName),
                'hasName' => 'false',
                'hasDescription' => 'false',
                //'host' => UrlRuleHelper::getHostDefinition(UrlRuleHelper::HOST_WWW),
            ];

            /*
            $relation['AppGalleryCover'] = '$this->hasOne(\dlds\galleryManager\GalleryImageProxy::className(), ['owner_id' => 'id'])
                ->where(['type' => EduPostImageHelper::getType()])
                ->orderBy(['rank' => SORT_ASC]);';

            $relation['AppGalleryImages'] = '$this->hasMany(\dlds\galleryManager\GalleryImageProxy::className(), ['owner_id' => 'id'])
                ->where(['type' => EduPostImageHelper::getType()]);'
             *
             */
        }

        if ($this->generateTimestampBehavior)
        {
            $behaviors['timestamp'] = [
                'class' => '\yii\behaviors\TimestampBehavior::className()',
            ];

            if ($this->timestampCreatedAtAttribute && self::DEFAULT_TIMESTAMP_CREATED_AT_ATTR != $this->timestampCreatedAtAttribute)
            {
                $behaviors['timestamp'] = [
                    'createdAtAttribute' => $this->timestampCreatedAtAttribute,
                ];
            }

            if ($this->timestampUpdatedAtAttribute && self::DEFAULT_TIMESTAMP_UPDATED_AT_ATTR != $this->timestampUpdatedAtAttribute)
            {
                $behaviors['timestamp'] = [
                    'updatedAtAttribute' => $this->timestampUpdatedAtAttribute,
                ];
            }
        }

        return $behaviors;
    }

    /**
     * Retrieves behavior constant name
     * @param string $key given behavior identification
     * @return string constant name
     */
    public function getBehaviorConstantName($key)
    {
        return 'BN_'.strtoupper($key);
    }

    /**
     * Retrieves behavior constant name
     * @param string $key given behavior identification
     * @return string constant name
     */
    public function getBehaviorName($key)
    {
        return 'b_'.$key;
    }
}