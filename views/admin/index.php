<?php
/**
 * Notification administarion view
 */

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('lsanIndex');

?>


<div class="container-fluid ls-space padding left-50 right-50">
    <div class="row">
        <div class="col-xs-12 h1 pagetitle">
            <?php echo gT('Notifications administration') ?>
        </div>

        <a type="button" class="btn btn-primary btn-sm pull-left " href="<?php echo \Yii::app()->createUrl("admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/create") ?>"><i class="fa fa-plus" aria-hidden="true"></i> <?php echo gT("Add new") ?></a>

    </div>
    <hr>
    <div class="row">
        <?php
        $this->widget('bootstrap.widgets.TbGridView', array(
            'id' => 'lsan-notificationmanagement-gridPanel',
            'itemsCssClass' => 'table table-striped items',
            'dataProvider' => $model->search(),
            'columns' => $model->colums,
            'filter' => $model,
            'summaryText'   => "<div class='row'>"
                . "<div class='col-xs-6'></div>"
                . "<div class='col-xs-6'>"
                . gT('Displaying {start}-{end} of {count} result(s).') . ' '
                . sprintf(
                    gT('%s rows per page'),
                    CHtml::dropDownList(
                        'pageSize',
                        $pageSize,
                        Yii::app()->params['pageSizeOptions'],
                        array('class' => 'changePageSize form-control', 'style' => 'display: inline; width: auto')
                    )
                )
                . "</div></div>",
        ));
        ?>
    </div>
</div>