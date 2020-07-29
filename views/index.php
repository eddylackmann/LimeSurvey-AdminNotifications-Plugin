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
                    <div class="panel panel-<?php echo $panelClass; ?> ls-space margin bottom-25 top-15">
                        <div class="panel-heading">
                            <h4><?php echo $notification->title; ?></h4>
                        </div>
                        <div class="panel-body">
                            <?php echo $notification->message;  ?>
                        </div>
                        <div class="panel-footer">
                            <div class="row">
                                <div class="col-md-12">
                                    <p>

                                        <span><i class="fa fa-user" aria-hidden="true"></i> <?php echo ' ' . $notification->username . ' ' ?></span>
                                        <span><i class="fa fa-calendar" aria-hidden="true"></i> <?php echo ' ' .  date('Y-m-d', strtotime($notification->created)) . ' '; ?></span>
                                        <?php if ($notification->priority == 2) : ?>
                                            &nbsp; &nbsp;<span title="<?php echo gT("High priority") ?>"><i class="fa fa-exclamation-triangle" style="color: red;" aria-hidden="false"></span></i>
                                        <?php endif; ?>
                                        <?php if (!$notification->isReadByUser()) : ?>
                                            <a type="button" class="btn btn-<?php echo $panelClass ?> btn-sm pull-right " href="<?php echo \Yii::app()->createUrl("admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/markasread", ["notif_id" => $notification->id]) ?>"><?php echo gT("Mark as read") ?></a>
                                        <?php endif; ?>

                                    </p>
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