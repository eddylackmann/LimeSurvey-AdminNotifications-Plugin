<?php

/**
 * Class LSANNotification
 * Abstracted Admin Notification model for Notification administration view.
 * Incorporating an alternative seach method.
 * @author Eddy Lackmann <a.eddy@hotmail.de>
 * @license GPL 2.0 or later
 * 
 * @property integer $id Notification id
 * @property integer $uid UserID 
 * @property string $username Notification Owner
 * @property string $title Title of the notification 
 * @property string $message Message rediged bei the user
 * @property string $created Creation date
 * @property string $modified Creation date
 * @property integer $priority Priority level of the notification
 * @property string $exprires Expiration date / Will not be displayed anymore. 
 * @property string $read_by_users Contains the id users thats mark it as read
 */


class LSANNotification extends LSActiveRecord
{
    const NORMAL_PRIORITY = 1; // Normal notification
    const HIGH_PRIORITY = 2; // Popup windows after user is logged in

    /**
     * Descriptions for the possible priority type options
     *
     * @var array
     */
    public static $priorityOptions = [
        '1' => 'Normal',
        '2' => 'High (Pop up window after user is logged in)',
    ];

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{ls_admin_notifications}}';
    }

    /**
     * @return array with all db attribute label
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => gT('User ID'),
            'title' => gT('Title'),
            'username' => gT('Username'),
            'message' => gT('Message'),
            'created' => gT('Created'),
            'priority' => gT('Priority'),
            'modified' => gT('Modified'),
            'expires' => gT('Expiration date'),
            'read_by_users' => gT('Read by users'),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function model($className = __CLASS__)
    {
        /** @var self $model */
        $model = parent::model($className);
        return $model;
    }

    /** @inheritdoc */
    public function primaryKey()
    {
        return array('id', 'id');
    }



    /** @inheritDoc */
    public function rules()
    {
        return array(
            array('id', 'numerical', 'integerOnly' => true),
            array('username', 'length', 'max' => 64),
            array('title', 'length', 'max' => 255),
            array('message, created, expires', 'safe'),
            array('id,message, priority, created, title, read_by_users', 'safe', 'on' => 'search'),
            array(
                'modified', 'default',
                'value' => new CDbExpression('NOW()'),
                'setOnEmpty' => false, 'on' => 'update'
            ),
            array(
                'created,modified', 'default',
                'value' => new CDbExpression('NOW()'),
                'setOnEmpty' => false, 'on' => 'insert'
            )

        );
    }

    /**
     * Returns the action column buttons
     *
     * @return string
     */
    public function getButtons()
    {
        $deleteBtn = CHtml::link(
            '<i class="fa fa-trash"></i>',
            "#",
            array(
                "submit" => array('admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/delete', 'notif_id' => $this->id),
                'confirm' => gT('Are you sure to delete this notification?'), 'csrf' => true,
                'class' => 'btn btn-icon btn-danger btn-sm'
            )
        );
        $updateBtn = CHtml::link(
            '<i class="fa fa-edit"></i>',
            "#",
            array(
                "submit" => array('admin/pluginhelper/sa/fullpagewrapper/plugin/LimeAdminNotifications/method/update', 'id' => $this->id),
                'class' => 'btn btn-icon btn-default btn-sm',
                'csrf' => true
            )
        );



        $btns = $deleteBtn . $updateBtn;

        return  $btns;
    }


    /**
     * 
     * @return array
     */
    public function getColums()
    {
        // TODO should be static
        $cols = array(
            array(
                "name" => 'id',
                "header" => gT("Id"),
            ),
            array(
                "name" => 'title',
                "header" => gT("Title"),
            ),
            array(
                "name" => 'username',
                "header" => gT("Owner"),
            ),
            array(
                "name" => 'priority',
                "header" => gT("Priority"),
            ),
            array(
                "name" => 'created',
                "header" => gT("Created"),

            ),
            array(
                "name" => 'modified',
                "header" => gT("Modified"),
            ),
            array(
                "name" => 'expires',
                "header" => gT("Expiration date"),

            ),
            array(
                "name" => 'buttons',
                "type" => 'raw',
                "filter" => false,
                "header" => gT("Action")
            ),

        );
        return $cols;
    }


    /**
     * This method checks if Notification is expired
     * @return bool
     */
    public function isExpired()
    {
        if ($this->expires != NULL) {
            if (strtotime($this->expires) <= strtotime("now")) {
                return true;
            }
        }
        return false;
    }

    /**
     * method to get all valid notifications
     * @return array of LSANNotifications
     */
    public static function getNotifications()
    {

        $notifications = self::model()->findAll(["order" => "priority DESC",]);
        $results = [];

        foreach ($notifications as $n) {
            if ($n->expires == NULL || strtotime($n->expires) > strtotime("now")) {
                $results[] = $n;
            }
        }

        return $results;
    }

    /**
     * method to count all valid notifications for the active user
     * @return int 
     */
    public static function countNotifications()
    {

        $notifications = self::model()->findAll();
        $count = 0;

        foreach ($notifications as $n) {
            if ($n->expires == NULL || strtotime($n->expires) >= strtotime(date('Y-m-d'))) {
                if (!$n->isReadByUser()) {
                    $count += 1;
                }
            }
        }

        return $count;
    }

    /**
     * This method search and insert high priority notification to the system notification system
     * @return bool save result
     */
    public static function initHighPriorityNotifications()
    {
        $notifications = self::model()->findAll();
        foreach ($notifications as $n) {
            if (!$n->isReadByUser()) {
                $n->appendToSystemNotification();
            }
        }
    }

    /**
     * This method marks a notification as read for the connected user
     * @return bool save result
     */
    public function markedAsRead()
    {
        $uid = Yii::app()->user->id;
        $readByUsers = json_decode($this->read_by_users);
        if ($readByUsers) {
            if (!in_array($uid, $readByUsers)) {
                $readByUsers[] = $uid;
                $this->read_by_users = json_encode($readByUsers);
                return $this->save();
            }
        } else {
            $newRead = [];
            $newRead[] = $uid;
            $this->read_by_users = json_encode($newRead);
            return $this->save();
        }
    }

    /**
     * This method checks if a notification is already read by the connected user 
     * @return bool 
     */

    public function isReadByUser()
    {
        $uid = Yii::app()->user->id;
        $readByUsers = json_decode($this->read_by_users);
        if ($readByUsers) {
            return in_array($uid, $readByUsers);
        } else {
            return false;
        }
    }

    /**
     * This append a single notification to the system notification 
     * @return bool save result
     */
    public function appendToSystemNotification()
    {
        $oldSytemNotification = Notification::model()->findByAttributes([
            "title" => $this->title,
            "entity" => "user",
            'entity_id' => Yii::app()->user->id,
            'status' => 'read',
        ]);

        if ($oldSytemNotification) {
            if (!$this->isExpired() && $this->priority == self::HIGH_PRIORITY) {
                $oldSytemNotification->status = "new";
                return $oldSytemNotification->save();
            }
        } else {
            if (!$this->isExpired() && $this->priority == self::HIGH_PRIORITY) {
                $aOption = [
                    "entity" => "user",
                    "title" => $this->title,
                    'entity_id' => Yii::app()->user->id,
                    'message' => $this->message,
                    'display_class' => 'success',
                    'importance' => 3,
                ];

                $systemNotification = new Notification($aOption);

                return $systemNotification->save();
            }
        }



        return false;
    }

    /**
     * Method to check some valid notification attributes before save it 
     * @return mixed
     */
    public function beforeSave()
    {
        $user = User::model()->findByPk(Yii::app()->user->id);

        //clean up html tags
        $cleanMessage = strip_tags($this->message, "<a>");
        $cleanMessage = strip_tags($this->message, "<strong>");
        $cleanMessage = strip_tags($this->message, "<span>");
        $cleanMessage = strip_tags($this->message, "<p>");

        if ($this->isNewRecord) {
            $this->created = new CDbExpression('NOW()');
            $this->uid = Yii::app()->user->id;
            $this->username = $user->users_name;
            $this->read_by_users = "";
            $this->message = $cleanMessage;
            if (!$this->expires) {
                $this->expires = NULL;
            }
        } else {
            $this->modified = new CDbExpression('NOW()');
            if (!$this->expires) {
                $this->message = $cleanMessage;
                $this->expires = NULL;
            }
        }

        return parent::beforeSave();
    }

    /**
     * Search function 
     * @return CActiveDataProvider 
     */
    public function search()
    {

        $criteria = new CDbCriteria;
        $criteria->compare('id', $this->id, true);
        $criteria->compare('username', $this->username, true);
        $criteria->compare('title', $this->title, true);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('modified', $this->created, true);
        $criteria->compare('priority', $this->priority, true);
        $criteria->compare('expires', $this->expires, true);

        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
            'sort' => array(
                'defaultOrder' => 'id ASC',
            ),
            'pagination' => array(
                'pageSize' => 20
            ),
        ));
    }
}
