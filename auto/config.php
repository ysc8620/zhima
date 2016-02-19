<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
set_time_limit(0);
$root_path = realpath(dirname(dirname(__FILE__)));
$db_config = include($root_path . '/App/Common/Conf/db_config.php');

class db{
    static $conn = null;
    public function __construct(){
        if( is_null(self::$conn) ){
            global $db_config;
            self::$conn = mysql_connect($db_config['DB_HOST'], $db_config['DB_USER'], $db_config['DB_PWD'])or die('error connect');
            mysql_select_db($db_config['DB_NAME'], self::$conn)or die('error db select');
            mysql_query("SET NAMES UTF8");
        }
    }

    /**
     * @param $sql
     * @return resource
     */
    public function query($sql){
        return mysql_query($sql);
    }

    public function get_list($sql){
        $res = mysql_query($sql);
        $data = array();
        while($row = mysql_fetch_assoc($res)){
            $data[] = $row;
        }
        return $data;
    }

    public function get_row($sql){
        $res = mysql_query($sql);
        $data = array();
        while($row = mysql_fetch_assoc($res)){
            return $row;
        }
        return $data;
    }

    /**
     *
     */
    public function __destruct(){
        if(!is_null(self::$conn)){
            mysql_close(self::$conn);
        }
    }
}

