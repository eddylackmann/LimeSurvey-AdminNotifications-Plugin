<?php

/**
 * Installer class for the LSANPlugin
 * A collecton of static helpers to install the Plugin
 */
class LSANPluginInstaller
{
    public static $instance = null;
    private $errors = [];

    /**
     * Singleton get Instance
     *
     * @return LSANPluginInstaller
     */
    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new LSANPluginInstaller();
        }
        return self::$instance;
    }

    /**
     * Combined installation for all necessary options
     * 
     * @throws CHttpException
     * @return void
     */
    public function install()
    {
        try {
            $this->installTables();
        } catch (CHttpException $e) {
            $this->errors[] = $e;
        }

        if (count($this->errors) > 0) {
            throw new CHttpException(500, join(",\n", array_map(function ($oError) {
                return $oError->getMessage();
            }, $this->errors)));
        }
    }

    /**
     * Combined uninstallation for all necessary options
     * 
     * @throws CHttpException
     * @return void
     */
    public function uninstall()
    {
        try {
            $this->uninstallTables();
        } catch (CHttpException $e) {
            $this->errors[] = $e;
        }

        if (count($this->errors) > 0) {
            throw new CHttpException(500, join(",\n", array_map(function ($oError) {
                return $oError->getMessage();
            }, $this->errors)));
        }
    }

    /**
     * Install tables for the plugin
     * 
     * @throws CHttpException
     * @return boolean
     */
    public function installTables()
    {
        $oDB = Yii::app()->db;
        $oTransaction = $oDB->beginTransaction();
        try {
            $oDB->createCommand()->createTable('{{ls_admin_notifications}}', array(
                'id' => 'pk',
                'uid' => 'integer NOT NULL',
                'username' => 'string NOT NULL',
                'title' => 'string NOT NULL',
                'message' => 'text',
                'created' => 'datetime',
                'priority' => 'integer NOT NULL',
                'modified' => 'datetime',
                'expires' => 'date',
                'read_by_users' => 'text',
            ));

            $oTransaction->commit();
            return true;
        } catch (Exception $e) {
            $oTransaction->rollback();
            throw new CHttpException(500, $e->getMessage());
        }
    }

    /**
     * Uninstall tables for the plugin
     * 
     * @throws CHttpException
     * @return boolean
     */
    public function uninstallTables()
    {
        $oDB = Yii::app()->db;
        $oTransaction = $oDB->beginTransaction();
        try {
            $oDB->createCommand()->dropTable('{{ls_admin_notifications}}');
            $oTransaction->commit();
            return true;
        } catch (Exception $e) {
            $oTransaction->rollback();
            throw new CHttpException(500, $e->getMessage());
        }
    }
}
