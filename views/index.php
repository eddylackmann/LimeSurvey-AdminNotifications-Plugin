<?php

/**
 * Notifications feed view
 */

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('lsanIndex');

?>

<div class="container-fluid ls-space padding left-50 right-50 ">
    <div class="row">
        <div class="col-xs-12 col-md-12 h1 pagetitle">
            <?php echo gT("Admin notifications feed") ?>
        </div>
        <?php if (isset($notifications) && count($notifications) > 0) : ?>
            <div class="row">
                <div class="col-md-10">
                </div>

                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-sm pull-right "><?php echo gT("Mark all as read") ?></button>
                </div>

            </div>
            <hr>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php if (isset($notifications) && count($notifications) > 0) : ?>
            <div class="panel-group">
                <?php foreach ($notifications as $notification) : ?>
                    <?php
                    $panelClass = 'primary';
                    if ($notification->priority == 2) {
                        $panelClass = 'danger';
                    }
                    if ($notification->isReadByUser()) {
                        $panelClass = 'default';
                    }
                    ?>

                    <div class="panel notification-card notification-<?php echo $panelClass; ?>">
                        <div class="panel-heading ">
                            <span class="notification-card-title"><?php echo $notification->title; ?></span>
                        </div>
                        <div class="panel-body notification-card-body">
                            <?php echo $notification->message; ?>
                        </div>
                        <div class="panel-footer notification-card-footer">
                            <div class="row">
                                <div class="col-md-9 col-xs-12 notification-card-footer-left">
                                    <span class="notification-card-icon-section">
                                        <i class="fa fa-user" aria-hidden="true"></i>
                                        <?php echo $notification->username ?>
                                    </span>
                                    <span class="notification-card-icon-section">
                                        <i class="fa fa-calendar" aria-hidden="true"></i>
                                        <?php echo  date('Y-m-d', strtotime($notification->created)); ?>
                                    </span>
                                    <?php if ($notification->priority == 2) : ?>
                                        <span class="notification-card-icon-section">
                                            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                                            <?php echo  gT("Important"); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-3 col-xs-12 notification-card-footer-right">
                                    <?php if (!$notification->isReadByUser()) : ?>
                                        <span class="notification-card-icon-section pull-right">
                                            <a class="btn btn-sm btn-default" href="<?php echo \Yii::app()->createUrl("admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/markasread", ["notif_id" => $notification->id]) ?>">
                                                <i class="fa fa-check" aria-hidden="true"></i>
                                                <?php echo  gT("Mark as read"); ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="row" style="text-align:center">
                <p><?php echo gT("No notifications found...") ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>