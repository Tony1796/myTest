<?php
/*配置类 */

namespace Core;

class config{
    //初始化配置
    private static $frameInitialize = [];
    //配置类对象
    private static  $configObject = null;
    //执行配置组
    private static $projectConfig = [];
    //是否初始化配置
    //private static $initialize = true;

    private function __construct(){}

    //框架初始化
    public static function _frameInitialize(){
        //载入公共方法
        require_once ROOT_DIR . 'Common/common.php';
        //引入初始化配置
        self::_loadFrameConfig();
        //定义默认配置文件目录名
        defined('CONFIG_DIR_NAME') || define('CONFIG_DIR_NAME', self::$frameInitialize['CONFIG_DIR_NAME']);
        //是否使用session
        if(self::$frameInitialize['USE_SESSION'] == true) session_start();
        //是否使用缓存
//        if(self::$frameInitialize['USE_CACHE']) cache();
        //设置报错级别
        self::$frameInitialize['DEBUG_OPEN'] == true ? error_reporting('E~ALL') : error_reporting(0);
        //项目名称
        defined('PROJECT_NAME') || define('PROJECT_NAME', self::$frameInitialize['PROJECT_NAME']);
        //定义项目路径并创建
        defined('PROJECT_DIR') || define('PROJECT_DIR', ROOT_DIR . self::$frameInitialize['PROJECT_NAME'] . '/');
        file_exists(PROJECT_DIR) || mkdir(PROJECT_DIR, 0777, true);
        //默认模块
        defined('DEFAULT_MODEL') || define('DEFAULT_MODEL', self::$frameInitialize['DEFAULT_MODEL']);
        //默认单元
        defined('DEFAULT_UNIT') || define('DEFAULT_UNIT', self::$frameInitialize['DEFAULT_UNIT']);
        //默认类
        defined('DEFAULT_CLASS') || define('DEFAULT_CLASS', self::$frameInitialize['DEFAULT_CLASS']);
        //默认方法
        defined('DEFAULT_ACTION') || define('DEFAULT_ACTION', self::$frameInitialize['DEFAULT_ACTION']);
        //默认文件后缀
        defined('CLASS_SUFFIX') || define('CLASS_SUFFIX', self::$frameInitialize['CLASS_SUFFIX']);
        //默认配置文件
        defined('DEFAULT_CONFIG') || define('DEFAULT_CONFIG', self::$frameInitialize['DEFAULT_CONFIG']);
        //是否使用读写分离
        defined('USE_READ_DB') || define('USE_READ_DB', self::$frameInitialize['USE_READ_DB']);
        //定义模块结构
        foreach(self::$frameInitialize['MODEL_ARRAY'] as $key => $modelName){
            $modelDirName = strtoupper($modelName) . '_DIR';
            $modelDir = PROJECT_DIR . $modelName . '/';
            //定义模块路径
            defined($modelDirName) || define($modelDirName, $modelDir);
            file_exists($modelDir) || mkdir($modelDir, 0777, true);
            //自动生成单元结构
            foreach(self::$frameInitialize['UNIT_ARRAY'] as $unitName){
                //生成单元路径
                $unitDir = $modelDir . $unitName . '/';
                file_exists($unitDir) || mkdir($unitDir, 0777, true);
                //生成默认执行文件
                $defaultAction = $unitDir . self::$frameInitialize['DEFAULT_CLASS'] .CLASS_SUFFIX;
                file_exists($defaultAction) || fopen($defaultAction, 'a');
                //是否使用单元内配置
                if(self::$frameInitialize['USE_UNIT_CONFIG'] == true && $modelName == DEFAULT_MODEL){
                    file_exists($unitDir . self::$frameInitialize['CONFIG_DIR_NAME'] . '/') || mkdir($unitDir . self::$frameInitialize['CONFIG_DIR_NAME'] . '/', 0777, true);
                    file_exists($unitDir . self::$frameInitialize['CONFIG_DIR_NAME'] . '/' . DEFAULT_CONFIG . CLASS_SUFFIX) || fopen($unitDir . self::$frameInitialize['CONFIG_DIR_NAME'] . '/' . DEFAULT_CONFIG . CLASS_SUFFIX, 'a');
                }
            }
        }
    }

    //载入初始化配置
    private static function _loadFrameConfig(){
        //定义框架配置目录路径
        defined('FRAME_CONFIG_DIR') || define('FRAME_CONFIG_DIR', ROOT_DIR . 'Config/');
        if(empty(self::$frameInitialize)){
            self::$frameInitialize = include_once FRAME_CONFIG_DIR . 'frameInitialize.php';
        }
    }

    //获取model列表
    public static function _getModelList(){
        self::_loadFrameConfig();
        return self::$frameInitialize['MODEL_ARRAY'];
    }

    //获取unit列表
    public static function _getUnitList(){
        self::_loadFrameConfig();
        return self::$frameInitialize['UNIT_ARRAY'];
    }

    //获取config实例
    public static function _getInstance(){
        if(self::$configObject == null || !(self::$configObject instanceof self)){
            self::$configObject = new self();
        }
        return self::$configObject;
    }
    
    //获取配置
    public static function _getConfig($module = '', $path = '', $info = [], $initialize = false){
        //默认值
        if(empty($module)) $module = CONFIG_DIR_NAME;
        if(empty($path)) $path = DEFAULT_CONFIG;
        //单元配置
        $unitList = self::_getUnitList();
        if(in_array($module, $unitList)){
            if(substr($path, 0, 7) !== 'Config/') $path = CONFIG_DIR_NAME . '/' . $path;
        }
        $configPath = $module . '/' . $path;
        //已有数据直接返回
        if(!isset(self::$projectConfig[$configPath]) || $initialize == true){
            $configData = [];
            //获取单元配置
            if(in_array($module, $unitList)){
                //获取公共配置
                if(file_exists(ROOT_DIR . $path . CLASS_SUFFIX)){
                    $configData = include_once ROOT_DIR . $path . CLASS_SUFFIX;
                }
                //获取单元配置
                if(self::$frameInitialize['USE_UNIT_CONFIG'] == true){
                    $unitConfig = include_once PROJECT_DIR . $configPath . CLASS_SUFFIX;
                    if(!empty($unitConfig)){
                        foreach($unitConfig as $key => $value){
                            $configData[$key] = $value;
                        }
                    }
                }
            }else{
                $configData = include_once ROOT_DIR . $configPath . CLASS_SUFFIX;
            }
            self::$projectConfig[$configPath] = $configData;
        }
        return self::_getFilteredConfig(self::$projectConfig[$configPath], $info);
    }

    public function _setConfig(){

    }

    //获取指定的配置
    private static function _getFilteredConfig($configList, $getList = []){
        //info为空，返回整条配置
        if(empty($getList)) return $configList;
        //单个元素直接返回
        if(!is_array($getList)) return $configList[$getList];
        $return = [];
        foreach($getList as $key => $value){
            if(is_int($key)){
                $return[$value] = $configList[$value];
            }else{
                $return[$key] = self::_getFilteredConfig($configList[$key], $value);
            }
        }
        return $return;
    }
}
