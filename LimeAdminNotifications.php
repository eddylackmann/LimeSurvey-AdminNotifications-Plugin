<?php

/**
 * Plugin to send notifications to all survey administrators / backend users  
 * 
 *  
 * @author Eddy Lackmann <a.eddy@hotmail.de>
 * @license GPL 2.0 or later
 */


//Get necessary libraries and component plugins
spl_autoload_register(function ($class_name) {

    if (preg_match("/^LSAN.*/", $class_name)) {
        if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $class_name . '.php')) {
            include __DIR__ . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $class_name . '.php';
        } elseif (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $class_name . '.php')) {
            include __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $class_name . '.php';
        } elseif (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR . $class_name . '.php')) {
            include __DIR__ . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR . $class_name . '.php';
        }
    }

});


class LimeAdminNotifications extends PluginBase
{
    const DBNAME = "{{ls_admin_notifications}}";

    protected static $name = 'LimeAdminNotifications';

    protected static $description = 'Send Notifications to other users';
    
    protected $storage = 'DbStorage';

    protected $settings = array(

        'autodeletedExpired' => array(
            'type' => 'select',
            'label' => 'Auto delete expired notifications.',
            'default' => '0',
            'options' => [
                '0' => 'No',
                '1' => 'Yes',
            ],
            'help' => 'This option deletes all expired notifications when admin is logged in.'
        ),
    );

    /** 
     * Init the plugin / Suscribing to plugin events 
     *
     * @return void
     *
     */
    public function init()
    {
        $this->subscribe('direct');
        $this->subscribe('beforeActivate');
        $this->subscribe('beforeDeactivate');
        $this->subscribe('beforeAdminMenuRender');
        $this->subscribe('afterSuccessfulLogin');
    }

    /** 
     * Install operation
     *
     * @return void
     */
    public function beforeActivate()
    {
        //Register new table and populate it.
        LSANPluginInstaller::instance()->install();

        //create demo notification after install
        $this->createDemoNotification();
    }

    /**
     * Uninstall operation
     * Plugin event
     *
     * @return void
     */
    public function beforeDeactivate()
    {
        //Delete plugin table in the db
        LSANPluginInstaller::instance()->uninstall();
    }

    /**
     * Append new menu item to the admin topbar
     * @return void
     */
    public function beforeAdminMenuRender()
    {
        $oEvent = $this->getEvent();

        $this->pageScripts();
        if ($this->dbTableExist()) {
            $counts = LSANNotification::countNotifications();
            $badge = '';
            $additionalClass = "";

            if ($counts > 0) {
                $badge = '<span class="badge badge-success">' . $counts . '</span>';
                $additionalClass = "text-warning";
            }
            //Visible for all users
            $aMenuItemUserOptions = [
                'isDivider' => false,
                'isSmallText' => false,
                'label' => 'Notifications ' . $badge,
                'href' => $this->api->createUrl('admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index', []),
                'iconClass' => 'fa fa-comments  ',
            ];
            $aMenuItems[] = (new \LimeSurvey\Menu\MenuItem($aMenuItemUserOptions));

            //Visible for users with users update permission
            if (Permission::model()->hasGlobalPermission('users', 'update')) {
                $aMenuItemAdminOptions = [
                    'isDivider' => false,
                    'isSmallText' => false,
                    'label' => 'Administration',
                    'href' => $this->api->createUrl('admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/admin', []),
                    'iconClass' => 'fa fa-gears',
                ];
                $aMenuItems[] = (new \LimeSurvey\Menu\MenuItem($aMenuItemAdminOptions));
                $aNewMenuOptions = [
                    'isDropDown' => true,
                    'label' => ' Admin Notifications ' . $badge,
                    'href' => '#',
                    'menuItems' => $aMenuItems,
                    'iconClass' => 'fa fa-comments ' . $additionalClass,
                ];
            } else {

                $aNewMenuOptions = [
                    'isDropDown' => false,
                    'label' => ' Admin Notifications ' . $badge,
                    'href' => $this->api->createUrl('admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index', []),
                    'iconClass' => 'fa fa-comments ' . $additionalClass,
                ];
            }

            $oNewMenu = new LSANMenuClass($aNewMenuOptions);

            $oEvent->set('extraMenus', [$oNewMenu]);
        }
    }


    /**
     * Method to run after user logged in. 
     * Plugin event
     * 
     * @return void
     */
    public function afterSuccessfulLogin()
    {
        if ($this->dbTableExist()) {
            
            //Check high priorities notifications and pop up windows to user
            LSANNotification::initHighPriorityNotifications();
        }
    }

    /**
     * Renders the list of all active notifications posted by admin users
     * To be called by fullpagewrapper
     *
     * @return mixed
     */
    public function index()
    {
        $notifications = LSANNotification::getNotifications();
       
        $this->pageScripts();
        return $this->renderPartial(
            'index',
            [
                'notifications' => $notifications,
            ],
            true
        );
    }

    /**
     * Method to create new notification 
     * To be called by fullpagewrapper
     *
     * @return mixed
     */
    public function create()
    {
        //check user permission
        if (!Permission::model()->hasGlobalPermission('users', 'update')) {
            Yii::app()->setFlashMessage(gT("you are not allowed to enter this page."), 'error');
            Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index'));
        }

        $model = new LSANNotification();

        //Post request 
        if (Yii::app()->request->getPost('LSANNotification')) {

            //loads data into notification model
            $model->attributes = Yii::app()->request->getPost('LSANNotification');

            $model->priority =  Yii::app()->request->getPost('LSANNotification')['priority'];

            if ($model->save()) {

                Yii::app()->setFlashMessage($model->title . ' ' . gT("published to all users."), 'success');
                $model->appendToSystemNotification();

                Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/admin'));
            } else {
                Yii::app()->setFlashMessage(gT("Error while creating new notification"), 'error');
            }
        }

        $this->pageScripts();
        return $this->renderPartial('admin/create', [
            "model" => $model
        ], true);
    }

    /**
     * Method to update a notification
     * To be called by fullpagewrapper
     *
     * @return mixed
     */
    public function update()
    {
        //check user permission
        if (!Permission::model()->hasGlobalPermission('users', 'update')) {
            Yii::app()->setFlashMessage(gT("you are not allowed to enter this page."), 'error');
            Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index'));
        }

        $model = new LSANNotification();

        //chek request params
        if (Yii::app()->getRequest()->getParam('id')) {

            //find notification by id
            $model = LSANNotification::model()->findByAttributes(
                array("id" => Yii::app()->getRequest()->getParam('id'))
            );

            if ($model) {

                //check post request for update
                if (Yii::app()->request->getPost('LSANNotification')) {

                    //loads data in to notification model
                    $model->attributes = Yii::app()->request->getPost('LSANNotification');
                    $model->priority =  Yii::app()->request->getPost('LSANNotification')['priority'];
                    $model->read_by_users = "[]";

                    if ($model->save()) {
                        Yii::app()->setFlashMessage($model->title . ' ' . gT("Notification updated"), 'success');
                        Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/admin'));
                    } else {
                        Yii::app()->setFlashMessage(gT("Error while creating new notification"), 'error');
                    }
                }
                $this->pageScripts();
                return $this->renderPartial('admin/update', [
                    "model" => $model
                ], true);
            }
        } else {

            Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index'));
        }
    }

    /**
     * Method to mark a notification as read for connected user
     * To be called by fullpagewrapper
     *
     * @return mixed
     */
    public function markasread()
    {
        //get notification by id 
        if (Yii::app()->getRequest()->getParam('notif_id')) {

            $notification = LSANNotification::model()->findByAttributes(
                array("id" => Yii::app()->getRequest()->getParam('notif_id'))
            );
        }

        //mark notification als read
        if ($notification->markedAsRead()) {
            Yii::app()->setFlashMessage($notification->title . ' ' . gT("marked as read"), 'success');
        }

        Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index'));
    }

    /**
     * This method delete a notification 
     * To be called by fullpagewrapper
     *
     * @return mixed
     */
    public function delete()
    {
        //check user permission
        if (!Permission::model()->hasGlobalPermission('users', 'update')) {
            Yii::app()->setFlashMessage(gT("you are not allowed to enter this page."), 'error');
            Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index'));
        }

        //check request params
        if (Yii::app()->getRequest()->getParam('notif_id')) {

            //Find notification to delete by ID 
            $notification = LSANNotification::model()->findByAttributes(
                array("id" => Yii::app()->getRequest()->getParam('notif_id'))
            );
            if ($notification) {
                if ($notification->delete()) {
                    Yii::app()->setFlashMessage(gT("Notification deleted!"), 'success');
                }
            }
        }

        Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/admin'));
    }


    /**
     * Renders the administration part for the Admin users | management of the notifications 
     * To be called by fullpagewrapper
     *
     * @return mixed
     */
    public function admin()
    {
        //check user permission
        if (!Permission::model()->hasGlobalPermission('users', 'update')) {
            Yii::app()->setFlashMessage(gT("you are not allowed to enter this page."), 'error');
            Yii::app()->getController()->redirect(array('/admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/index'));
        }

        if (Yii::app()->getRequest()->getQuery('pageSize')) {
            Yii::app()->user->setState('pageSize', (int) Yii::app()->getRequest()->getQuery('pageSize'));
        }

        $model = new LSANNotification('search');
        $iPageSize = Yii::app()->user->getState('pageSize', Yii::app()->params['defaultPageSize']);
        $aData = [
            'model' => $model,
            'pageSize' => $iPageSize
        ];
        $this->pageScripts();
        return $this->renderPartial('admin/index', $aData, true);
    }


    /**
     * Applies the necessary page scripts to the page through CClientScript derivate
     *
     * @return void
     */
    protected function pageScripts()
    {
        $this->registerScript('assets/script.js', null, LSYii_ClientScript::POS_HEAD);
        $this->registerCss('assets/style.css', null);
    }

    /**
     * Adding a script depending on path of the plugin
     * This method checks if the file exists depending on the possible different plugin locations, which makes this Plugin LimeSurvey Pro safe.
     *
     * @param string $relativePathToScript
     * @param integer $pos See LSYii_ClientScript constants for options, default: LSYii_ClientScript::POS_BEGIN
     * @return void
     */
    protected function registerScript($relativePathToScript, $pos = LSYii_ClientScript::POS_BEGIN)
    {
        $parentPlugin = get_class($this);

        $pathPossibilities = [
            YiiBase::getPathOfAlias('userdir') . '/plugins/' . $parentPlugin . '/' . $relativePathToScript,
            YiiBase::getPathOfAlias('webroot') . '/plugins/' . $parentPlugin . '/' . $relativePathToScript,
            Yii::app()->getBasePath() . '/application/core/plugins/' . $parentPlugin . '/' . $relativePathToScript,
            //added limesurvey 4 compatibilities
            YiiBase::getPathOfAlias('webroot') . '/upload/plugins/' . $parentPlugin . '/' . $relativePathToScript,
        ];

        $scriptToRegister = null;

        foreach ($pathPossibilities as $path) {
            if (file_exists($path)) {
                $scriptToRegister = Yii::app()->getAssetManager()->publish($path);
            }
        }

        Yii::app()->getClientScript()->registerScriptFile($scriptToRegister, $pos);
    }

    /**
     * Adding a stylesheet depending on path of the plugin
     * This method checks if the file exists depending on the possible different plugin locations, which makes this Plugin LimeSurvey Pro safe.
     *
     * @param string $relativePathToCss
     * @return void
     */
    protected function registerCss($relativePathToCss, $parentPlugin = null)
    {
        $parentPlugin = get_class($this);

        $pathPossibilities = [

            YiiBase::getPathOfAlias('userdir') . '/plugins/' . $parentPlugin . '/' . $relativePathToCss,
            YiiBase::getPathOfAlias('webroot') . '/plugins/' . $parentPlugin . '/' . $relativePathToCss,
            Yii::app()->getBasePath() . '/application/core/plugins/' . $parentPlugin . '/' . $relativePathToCss,
            //added limesurvey 4 compatibilities
            YiiBase::getPathOfAlias('webroot') . '/upload/plugins/' . $parentPlugin . '/' . $relativePathToCss,
        ];

        $cssToRegister = null;
        foreach ($pathPossibilities as $path) {
            if (file_exists($path)) {
                $cssToRegister = Yii::app()->getAssetManager()->publish($path);
            }
        }

        Yii::app()->getClientScript()->registerCssFile($cssToRegister);
    }


    /**
     * method to check if database exist before doing db operation || Some events are fired even when the puglin is not activated 
     * 
     * @return bool false if db not exists and true if db exits
     */
    protected function dbTableExist()
    {
        return (Yii::app()->db->schema->getTable(self::DBNAME, true) !== null);
    }
    /**
     * Create a dummy notification
     * This method creates the first notification when the plugin is activated 
     *
     * @return bool save state of the notification
     */
    protected function createDemoNotification()
    {
        $user = User::model()->findByPk(Yii::app()->user->id);
        $notification = new LSANNotification();
        $notification->uid = Yii::app()->user->id;
        $notification->priority = $notification::HIGH_PRIORITY;
        $notification->username = $user->users_name;
        $notification->title = gT("Welcome to Lime Admin Notifications");
        $notification->message = gT("This notification is automatically generated by the admin notification plugin. The admin notification section will contains messages, announcements, news and notifications shared by the administrators") . ". High priority messages will be displayed in a pop up box when you are logged in." . ' <p> Visit our <a  href="https://account.limesurvey.org/limestore">Plugins store (LimeStore) </a> for more informations.</p>';
        $notification->created = date('Y-m-d H:i:s');
        $notification->modified = date('Y-m-d H:i:s');
        $notification->expires = date('Y-m-d', strtotime($notification->created . ' +1 day'));
        if ($notification->save()) {
            $notification->appendToSystemNotification();
            return true;
        } else {
            return false;
        }
    }
}
