<?php
/**
 * Notification creation view
 */

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('lsanIndex');

?>


<div class="container-fluid ls-space padding left-50 right-50 ">
    <div class="row">
        <div class="col-xs-12 h1 pagetitle">
            <?php echo gT("New notification"); ?>
        </div>
        <div class="col-xs-12">

        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-8" style="float:none; margin:0 auto;">

            <?= TbHtml::formTb(null, App()->createUrl('admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/create'), 'post', []) ?>
            <div class="row ls-space margin bottom-5 top-5">
                <?php echo TbHtml::activeLabel($model, 'title'); ?>
                <?php echo TbHtml::activeTextField($model, 'title', ['required' => true]); ?>
            </div>
            <div class="row ls-space margin bottom-5 top-15">
                <?php echo TbHtml::activeLabel($model, 'message'); ?>
                <span id="" class="help-block"><?php echo gT('Allowed Html tags: a, p, strong, span.')?></span>
                <?php echo TbHtml::activeTextArea($model, 'message', ['rows' => 5, 'required' => true, 'maxlength' => "1024"]); ?>
            </div>


            <div class="row ls-space margin bottom-5 top-5">
                <?php echo TbHtml::activeLabel($model, 'priority'); ?>
                <?php echo TbHtml::activeDropDownList($model, 'priority', $model::$priorityOptions, [
                    'required' => true
                ]); ?>
            </div>
            <div class="row ls-space margin bottom-5 top-5">
                <?php  ?>
                <?php echo TbHtml::activeLabel($model, 'expires'); ?>
                <?php echo TbHtml::activeDateField($model, 'expires', ['min' => date("Y-m-d", strtotime("+1 day"))]) ?>

                <div class="row ls-space margin bottom-5 top-5">
                    <?php echo CHtml::submitButton(gT('Publish'), array('name' => 'publish', 'class' => 'btn btn-primary')); ?>
                </div>
                </form>
            </div>
        </div>

    </div>
</div>