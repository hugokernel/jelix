<?php
/**
* @package     jelix
* @subpackage  cache
* @author      Tahina Ramaroson
* @contributor Sylvain de Vathaire
* @copyright   2009 Neov
* @link        http://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
* Interface for cache drivers
* @package     jelix
* @subpackage  cache
*/
interface jICacheDriver {
    /**
     * constructor
     * @param array $params driver parameters, written in the ini file
     */
    function __construct($params);

    /**
    * read a specific data in the cache.
    * @param mixed $key     key or array of keys used for storing data in the cache
    */
    public function get ($key);

    /**
    * write a specific data in the cache.
    * @param string $key    key used for storing data in the cache
    * @param mixed  $value    data to store
    * @param mixed  $ttl    data time expiration. 0 means no expire, use a timestamp UNIX or a delay in secondes which mustn't exceed 30 days i.e 2592000s or a string in date format US
    */
    public function set ($key, $value, $ttl = 0);

    /**
    * delete a specific data in the cache
    * @param string $key       key used for storing data in the cache
    */
    public function delete ($key);

    /**
    * increment a specific data value by $incvalue
    * @param string $key       key used for storing data in the cache
    * @param mixed  $incvalue    value used to increment
    */
    public function increment ($key, $incvalue = 1);

    /**
    * decrement a specific data value by $decvalue
    * @param string $key       key used for storing data in the cache
    * @param mixed  $decvalue    value used to decrement
    */
    public function decrement ($key, $decvalue = 1);

    /**
    * replace a specific data value by $var
    * @param string $key       key used for storing data in the cache
    * @param mixed  $value    data to replace
    * @param mixed  $ttl    data time expiration. 0 means no expire, use a timestamp UNIX or a delay in secondes which mustn't exceed 30 days i.e 2592000s or a string in date format US
    */
    public function replace ($key, $value, $ttl = 0);

    /**
    * remove from the cache data of which TTL was expired
    */
    public function garbage ();

    /**
    * clear data in the cache
    */
    public function flush ();

}

/**
 * Global caching data provided from whatever sources
 * @since 1.2
 */
class jCache {

    /**
    * retrieve data in the cache 
    *
    * @param mixed   $key   key or array of keys used for storing data in the cache
    * @param string  $profile the cache profile name to use. if empty, use the default profile
    * @return mixed  $data      data stored
    */
    public static function get ($key, $profile='') {

        $drv = self::_getDriver($profile);

        if (!$drv->enabled) {
            return false;
        }

        if (is_array($key)) {
            foreach ($key as $value) {
                self::_checkKey($value);
            }
        }
        else {
            self::_checkKey($key);
        }

        return $drv->get($key);

    }

    /**
    * set a specific data in the cache
    * @param string $key    key used for storing data
    * @param mixed  $value    data to store
    * @param mixed  $ttl    data time expiration. 0 means no expire, use a timestamp UNIX or a delay in secondes which mustn't exceed 30 days i.e 2592000s or a string in date format US
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function set ($key, $value, $ttl=null, $profile='') {

        $drv = self::_getDriver($profile);

        if (!$drv->enabled || is_resource($value)) {
            return false;
        }

        self::_checkKey($key);

        if (is_null($ttl)) {
            $ttl = $drv->ttl;
        }
        elseif (is_string($ttl)) {
            if (($ttl = strtotime($ttl)) === FALSE) {
                throw new jException('jelix~cache.error.wrong.date.value');
            }
        }

        if ($ttl > 2592000 && $ttl < time()) {
            return $drv->delete($key);
        }

        //automatic cleaning cache
        if($drv->automatic_cleaning_factor > 0 &&  rand(1, $drv->automatic_cleaning_factor) == 1){
            $drv->garbage();
        }

        return $drv->set($key, $value, $ttl);
    }

    /**
    * call a specified method/function or get the result from cache
    * @param mixed  $fn        method/function name ($functionName or array($object, $methodName) or array($className, $staticMethodName))
    * @param array  $fnargs    arguments used by the method/function
    * @param mixed  $ttl    data time expiration. 0 means no expire, use a timestamp UNIX or a delay in secondes which mustn't exceed 30 days i.e 2592000s or a string in date format US
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return mixed         method/function result
    */
    public static function call ($fn, $fnargs=array(), $ttl=null, $profile='') {

        $drv = self::_getDriver($profile);

        if($drv->enabled){

            $key = md5(serialize($fn).serialize($fnargs));

            if (!($data = $drv->get($key))) {

                $data = self::_doFunctionCall($fn,$fnargs);

                if (!is_resource($data)) {
                    if (is_null($ttl)) {
                        $ttl = $drv->ttl;
                    } elseif (is_string($ttl)) {
                        if (($ttl = strtotime($ttl))===FALSE) {
                            throw new jException('jelix~cache.error.wrong.date.value');
                        }
                    }
                    if (!($ttl > 2592000 && $ttl < time())) {
                        //automatic cleaning cache
                        if($drv->automatic_cleaning_factor > 0 &&  rand(1,$drv->automatic_cleaning_factor)==1){
                            $drv->garbage();
                        }
                        $drv->set($key,$data,$ttl);
                    }
                }
            }

            return $data;

        }else{
            return self::_doFunctionCall($fn,$fnargs);
        }
    }

    /**
    * delete a specific data in the cache
    * @param string $key    key used for storing data in the cache
    * @param string $profile the cache profil name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function delete ($key, $profile=''){

        $drv = self::_getDriver($profile);

        if (!$drv->enabled) {
            return false;
        }

        self::_checkKey($key);

        return $drv->delete($key);

    }

    /**
    * increment a specific data value by $incvalue
    * @param string $key    key used for storing data in the cache
    * @param mixed  $incvalue    value used
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function increment ($key, $incvalue=1, $profile='') {

        $drv = self::_getDriver($profile);

        if (!$drv->enabled) {
            return false;
        }

        self::_checkKey($key);

        return $drv->increment($key, $incvalue);
    }

    /**
    * decrement a specific data value by $decvalue
    * @param string $key    key used for storing data in the cache
    * @param mixed  $decvalue    value used
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function decrement ($key, $decvalue=1, $profile=''){

        $drv = self::_getDriver($profile);

        if (!$drv->enabled) {
            return false;
        }

        self::_checkKey($key);
        return $drv->decrement($key, $decvalue);
    }

    /**
    * replace a specific data value by $value
    * @param string $key    key used for storing data in the cache
    * @param mixed  $value    data to replace
    * @param mixed  $ttl    data time expiration. 0 means no expire, use a timestamp UNIX or a delay in secondes which mustn't exceed 30 days i.e 2592000s or a string in date format US
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function replace ($key, $value, $ttl=null, $profile=''){

        $drv = self::_getDriver($profile);

        if(!$drv->enabled || is_resource($value)){
            return false;
        }

        self::_checkKey($key);

        if (is_null($ttl)) {
            $ttl = $drv->ttl;
        }
        elseif (is_string($ttl)) {
            if (($ttl=strtotime($ttl))===FALSE) {
                throw new jException('jelix~cache.error.wrong.date.value');
            }
        }

        if ($ttl > 2592000 && $ttl < time()) {
            return $drv->delete($key);
        }

        return $drv->replace($key, $value, $ttl);
    }

    /**
    * add data in the cache
    * @param string $key    key used for storing data in the cache
    * @param mixed  $value    data to add
    * @param mixed  $ttl    data time expiration. 0 means no expire, use a timestamp UNIX or a delay in secondes which mustn't exceed 30 days i.e 2592000s or a string in date format US
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function add ($key, $value, $ttl=null, $profile=''){

        $drv = self::_getDriver($profile);

        if (!$drv->enabled || is_resource($value)) {
            return false;
        }

        self::_checkKey($key);

        if($drv->get($key)){
            return false;
        }

        if (is_null($ttl)) {
            $ttl = $drv->ttl;
        }
        elseif (is_string($ttl)) {
            if (($ttl = strtotime($ttl))===FALSE) {
                throw new jException('jelix~cache.error.wrong.date.value');
            }
        }

        if ($ttl > 2592000 && $ttl < time()) {
            return false;
        }

        //automatic cleaning cache
        if ($drv->automatic_cleaning_factor > 0 &&  rand(1, $drv->automatic_cleaning_factor)==1) {
            $drv->garbage();
        }

        return $drv->set($key, $value, $ttl);
    }

    /**
    * remove from the cache data of which TTL was expired
    *
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function garbage ($profile=''){

        $drv = self::_getDriver($profile);

        if (!$drv->enabled) {
            return false;
        }

        return $drv->garbage();
    }

    /**
    * clear data in the cache
    *
    * @param string $profile the cache profile name to use. if empty, use the default profile
    * @return boolean false if failure
    */
    public static function flush ($profile='') {

        $drv = self::_getDriver($profile);

        if (!$drv->enabled) {
            return false;
        }

        return $drv->flush();
    }

    /**
     * load the cache driver
     *
     * get an instance of driver according the settings in the profile file
     * @param string $profile profile name
     * @return jICacheDriver
     */
    protected static function _getDriver($profile) {

        global $gJConfig;

        //cache drivers list : array of object jICacheDriver
        static $drivers = array();

        $profile = ($profile==''?'default':$profile);
        if (isset($drivers[$profile])) {
            return $drivers[$profile];
        }

        $params = self::_getProfile($profile);

        $oDriver = $params['driver'].'CacheDriver';

        if (!class_exists($oDriver,false)) {
            if (!isset($gJConfig->_pluginsPathList_cache) 
                || !isset($gJConfig->_pluginsPathList_cache[$params['driver']])
                || !file_exists($gJConfig->_pluginsPathList_cache[$params['driver']]) ) {
                throw new jException('jelix~cache.error.driver.missing',array($profile,$params['driver']));
            }
            require_once($gJConfig->_pluginsPathList_cache[$params['driver']].$params['driver'].'.cache.php');
        }

        $params['profile'] = $profile;

        $drv = new $oDriver($params);

        if(!$drv instanceof jICacheDriver){
            throw new jException('jelix~cache.driver.object.invalid', array($profile, $params['driver']));
        }

        $drivers[$profile] = $drv;

        return $drv;
    }

    /**
     * check the key for a specific data in the cache : only alphanumeric characters and the character '_' are accepted
     *
     * @param string   $key   key used for storing data
     * @return boolean
     */
    protected static function _checkKey($key){
        if (!preg_match('/^[a-z0-9_]+$/i',$key) || strlen($key) > 255) {
            throw new jException('jelix~cache.error.invalid.key',$key);
        }
    }

    /**
    * load a specific profil from the profile file
    *
    * @param string   $name  profil to load.
    * @return array  profil properties
    */
    protected static function _getProfile($name){

        global $gJConfig;
        static $profiles = null;
        
        if ($profiles === null) {
            $profiles = parse_ini_file(JELIX_APP_CONFIG_PATH.$gJConfig->cacheProfiles, true);
        }
        $profile = null;

        if ($name == 'default') {
            if (isset($profiles['default']) && isset($profiles[$profiles['default']])) {
                $profile = $profiles[$profiles['default']];
            }
            else {
                throw new jException('jelix~cache.error.profile.missing','default');
            }
        }
        else {
            if (isset($profiles[$name])) {
                $profile = $profiles[$name];
            }
            else {
                throw new jException('jelix~cache.error.profile.missing',$name);
            }
        }

        return $profile;
    }

    /**
    * check and call a specified method/function
    * @param mixed  $fn        method/function name
    * @param array  $fnargs    arguments used by the method/function
    * @return mixed  $data      method/function result
    */
    protected static function _doFunctionCall($fn,$fnargs) {

        if (!is_callable($fn)) {
            throw new jException('jelix~cache.error.function.not.callable',self::_functionToString($fn));
        }

        try {
            $data = call_user_func_array($fn,$fnargs);
        }
        catch(Exception $e) {
            throw new jException('jelix~cache.error.call.function',array(self::_functionToString($fn),$e->getMessage()));
        }

        return $data;
    }

    /**
    * get the method/function full name 
    * @param mixed  $fn        method/function name
    * @return string  $fnname      method/function name
    */
    protected static function _functionToString($fn) {

        if (is_array($fn)) {
            if (is_object($fn[0])) {
                $fnname = get_class($fn[0])."-".$fn[1];
            }
            else {
                $fnname = implode("-",$fn);
            }
        }
        else {
            $fnname = $fn;
        }

        return $fnname;
    }
}
