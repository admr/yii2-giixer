<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

<?= $generator->indexWidgetType !== 'grid' ? "use yii\helpers\Html;" : "" ?>
use dlds\metronic\Metronic;
use dlds\metronic\widgets\Link;
use dlds\metronic\widgets\Portlet;
use <?= $generator->indexWidgetType === 'grid' ? "dlds\\metronic\\widgets\\GridView" : "dlds\\metronic\\widgets\\ListView" ?>;

/* @var $this yii\web\View */
<?= !empty($generator->searchModelClass) ? "/* @var \$searchModel " . ltrim($generator->searchModelClass, '\\') . " */\n" : '' ?>
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">

    <?php if (!empty($generator->searchModelClass)): ?>
<?= "<?php " . ($generator->indexWidgetType === 'grid' ? "// " : "") ?>echo $this->render('_search', ['model' => $searchModel]); ?>
    <?php endif; ?>
    <?php if ($generator->indexWidgetType === 'grid'): ?>

<?= "<?php 
    Portlet::begin([
        'icon' => 'icon-grid',
        'title' => " . $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) . ",
        'actions' => [
            Link::widget([
                'icon' => 'fa fa-plus',
                'iconPosition' => Link::ICON_POSITION_LEFT,
                'label' => Yii::t('app', 'New {modelClass}', ['modelClass' => '" . Inflector::camel2words(StringHelper::basename($generator->modelClass)) . "']),
                'url' => ['" . Inflector::slug(Inflector::camel2words(StringHelper::basename($generator->modelClass))) . "/create'],
                'options' => [
                    'class' => 'btn btn-default btn-circle'
                ],
                'labelOptions' => [
                    'class' => 'hidden-480'
                ],
            ]),
        ],
    ]);
    ?>

    <?php \yii\widgets\Pjax::begin(); ?>" ?>

        <?= "<?= " ?>GridView::widget([
        'dataProvider' => $dataProvider,
        <?= !empty($generator->searchModelClass) ? "'filterModel' => \$searchModel,\n        'columns' => [\n" : "'columns' => [\n"; ?>
        ['class' => 'yii\grid\SerialColumn'],

        <?php
        $count = 0;
        if (($tableSchema = $generator->getTableSchema()) === false)
        {
            foreach ($generator->getColumnNames() as $name)
            {
                if (++$count < 6)
                {
                    echo "            '" . $name . "',\n";
                }
                else
                {
                    echo "            // '" . $name . "',\n";
                }
            }
        }
        else
        {
            foreach ($tableSchema->columns as $column)
            {
                $format = $generator->generateColumnFormat($column);
                if (++$count < 6)
                {
                    echo "            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
                }
                else
                {
                    echo "            // '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
                }
            }
        }
        ?>

        ['class' => 'yii\grid\ActionColumn'],
        ],
        ]); ?>

    <?= "<?php \yii\widgets\Pjax::end(); ?>
		
    <?php Portlet::end(); ?>" ?>

    <?php else: ?>
        <?= "<?= " ?>ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['class' => 'item'],
        'itemView' => function ($model, $key, $index, $widget) {
        return Html::a(Html::encode($model-><?= $nameAttribute ?>), ['view', <?= $urlParams ?>]);
        },
        ]) ?>
    <?php endif; ?>

</div>