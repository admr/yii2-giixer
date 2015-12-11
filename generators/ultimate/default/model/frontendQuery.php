<?php
/* @var $this yii\web\View */
/* @var $generator \dlds\giixer\generators\ultimate\Generator */

echo "<?php\n";
?>

namespace <?= $generator->helperModel->getNsByPattern(basename(__FILE__, '.php'), $generator->helperModel->getQueryClass(true)) ?>;

/**
 * This is frontend ActiveQuery class for [[<?= $generator->helperModel->getModelClass() ?>]].
 *
 * @see <?= $generator->helperModel->getModelClass()."\n" ?>
 */
class <?= $generator->helperModel->getQueryClass(true) ?> extends <?= $generator->helperModel->getQueryParentClass(basename(__FILE__, '.php'), false, true) ?> {

}