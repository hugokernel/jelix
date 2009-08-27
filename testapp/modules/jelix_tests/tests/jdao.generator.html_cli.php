<?php
/**
* @package     testapp
* @subpackage  jelix_tests module
* @author      Jouanneau Laurent
* @contributor
* @copyright   2007 Jouanneau laurent
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(dirname(__FILE__).'/daotests.lib.php');


class UTDao_generator extends jUnitTestCase {
    protected function getSimpleGenerator(){
        $doc ='<?xml version="1.0"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
   <datasources>
      <primarytable name="product_test" primarykey="id" />
   </datasources>
   <record>
      <property name="id"   fieldname="id" datatype="autoincrement" required="true" />
      <property name="name" fieldname="name" datatype="string"  required="true"/>
      <property name="price" fieldname="price" datatype="float"/>
   </record>
</dao>';
        $parser = new jDaoParser ($this->_selector);
        $parser->parse(simplexml_load_string($doc), $this->_tools);
        return new testMysqlDaoGenerator($this->_selector, $this->_tools, $parser);
    }

    protected $_selector;
    protected $_tools;
    
    function setUp() {
        $this->_selector = new fakejSelectorDao('foo','bar','mysql');
        $this->_tools= new mysqlDbTools(null);
    }


    protected $_generator;
    function _getProp($type, $expr, $checknull, $op='') {
        $u = $this->_tools->getTypeInfo($type);
        $prop = new testDaoProperty();
        $prop->datatype = $type;
        $prop->unifiedType = $u[1];
        $prop->autoIncrement = $u[6];
        return $this->_generator->GetPreparePHPExpr($expr, $prop, $checknull,$op);
    }

    function testPreparePHPExpr(){
        $this->_generator = $this->getSimpleGenerator();

        // with no checknull

        $this->assertEqualOrDiff('intval($foo)',$this->_getProp('int','$foo', false));
        $this->assertEqualOrDiff('intval($foo)',$this->_getProp('integer','$foo', false));
        $this->assertEqualOrDiff('intval($foo)',$this->_getProp('autoincrement','$foo', false));
        $this->assertEqualOrDiff('$this->_conn->quote($foo)',$this->_getProp('string','$foo', false));
        $this->assertEqualOrDiff('(is_numeric ($foo) ? $foo : floatval($foo))',$this->_getProp('double','$foo', false));
        $this->assertEqualOrDiff('doubleval($foo)',$this->_getProp('float','$foo', false));
        $this->assertEqualOrDiff('(is_numeric ($foo) ? $foo : floatval($foo))',$this->_getProp('numeric','$foo', false));
        $this->assertEqualOrDiff('(is_numeric ($foo) ? $foo : floatval($foo))',$this->_getProp('bigautoincrement','$foo', false));

        // with checknull 
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : intval($foo))',$this->_getProp('integer','$foo', true));
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : intval($foo))',$this->_getProp('autoincrement','$foo', true));
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : $this->_conn->quote($foo,false))',$this->_getProp('string','$foo', true));
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : (is_numeric ($foo) ? $foo : floatval($foo)))',$this->_getProp('double','$foo', true));
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : doubleval($foo))',$this->_getProp('float','$foo', true));
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : (is_numeric ($foo) ? $foo : floatval($foo)))',$this->_getProp('numeric','$foo', true));
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : (is_numeric ($foo) ? $foo : floatval($foo)))',$this->_getProp('bigautoincrement','$foo', true));

        // with checknull and operator =
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \' = \'.intval($foo))',$this->_getProp('integer','$foo', true,'='));
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \' = \'.intval($foo))',$this->_getProp('autoincrement','$foo', true,'='));
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \' = \'.$this->_conn->quote($foo,false))',$this->_getProp('string','$foo', true,'='));
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \' = \'.(is_numeric ($foo) ? $foo : floatval($foo)))',$this->_getProp('double','$foo', true,'='));
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \' = \'.doubleval($foo))',$this->_getProp('float','$foo', true,'='));
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \' = \'.(is_numeric ($foo) ? $foo : floatval($foo)))',$this->_getProp('numeric','$foo', true,'='));
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \' = \'.(is_numeric ($foo) ? $foo : floatval($foo)))',$this->_getProp('bigautoincrement','$foo', true,'='));

        // with checknull with default value and operator =
        /*$prop->defaultValue=34;
        $prop->datatype='integer';
        $result = $generator->GetPreparePHPExpr('$foo', $prop, true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : ($foo === \'\'?\'=\'.34:\'=\'.intval($foo)))',$result);
        $prop->datatype='autoincrement';
        $result = $generator->GetPreparePHPExpr('$foo', $prop, true,'=');
        $this->assertEqualOrDiff('\'=\'.intval($foo)',$result);
        $prop->datatype='string';
        $result = $generator->GetPreparePHPExpr('$foo', $prop, true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.$this->_conn->quote($foo,false))',$result);
        $prop->defaultValue=34.6;
        $prop->datatype='double';
        $result = $generator->GetPreparePHPExpr('$foo', $prop, true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : ($foo === \'\'?\'=\'.34.6:\'=\'.doubleval($foo)))',$result);
        $prop->defaultValue=34.6;
        $prop->datatype='float';
        $result = $generator->GetPreparePHPExpr('$foo', $prop, true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : ($foo === \'\'?\'=\'.34.6:\'=\'.doubleval($foo)))',$result);
        $prop->datatype='numeric';
        $result = $generator->GetPreparePHPExpr('$foo', $prop, true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.(is_numeric ($foo) ? $foo : intval($foo)))',$result);
        $prop->datatype='bigautoincrement';
        $result = $generator->GetPreparePHPExpr('$foo', $prop, true,'=');
        $this->assertEqualOrDiff('\'=\'.(is_numeric ($foo) ? $foo : intval($foo))',$result);
        $prop->defaultValue = null;*/

        // with checknull and operator <>
        $result = $this->_getProp('integer','$foo', true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \' <> \'.intval($foo))',$result);
        $result = $this->_getProp('autoincrement','$foo', true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \' <> \'.intval($foo))',$result);
        $result = $this->_getProp('string','$foo', true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \' <> \'.$this->_conn->quote($foo,false))',$result);
        $result = $this->_getProp('double','$foo', true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \' <> \'.(is_numeric ($foo) ? $foo : floatval($foo)))',$result);
        $result = $this->_getProp('float','$foo', true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \' <> \'.doubleval($foo))',$result);
        $result = $this->_getProp('numeric','$foo', true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \' <> \'.(is_numeric ($foo) ? $foo : floatval($foo)))',$result);
        $result = $this->_getProp('bigautoincrement','$foo', true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \' <> \'.(is_numeric ($foo) ? $foo : floatval($foo)))',$result);

        // with checknull and other operator <=
        $result = $this->_getProp('integer','$foo', true,'<=');
        $this->assertEqualOrDiff('\' <= \'.intval($foo)',$result);
        $result = $this->_getProp('autoincrement','$foo', true,'<=');
        $this->assertEqualOrDiff('\' <= \'.intval($foo)',$result);
        $result = $this->_getProp('string','$foo', true,'<=');
        $this->assertEqualOrDiff('\' <= \'.$this->_conn->quote($foo)',$result);
        $result = $this->_getProp('double','$foo', true,'<=');
        $this->assertEqualOrDiff('\' <= \'.(is_numeric ($foo) ? $foo : floatval($foo))',$result);
        $result = $this->_getProp('float','$foo', true,'<=');
        $this->assertEqualOrDiff('\' <= \'.doubleval($foo)',$result);
        $result = $this->_getProp('numeric','$foo', true,'<=');
        $this->assertEqualOrDiff('\' <= \'.(is_numeric ($foo) ? $foo : floatval($foo))',$result);
        $result = $this->_getProp('bigautoincrement','$foo', true,'<=');
        $this->assertEqualOrDiff('\' <= \'.(is_numeric ($foo) ? $foo : floatval($foo))',$result);
    }
 
    function testBuildSQLCondition(){
        $doc ='<?xml version="1.0" encoding="UTF-8"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
    <datasources>
        <primarytable name="grp" realname="jacl_group" primarykey="id_aclgrp" />
    </datasources>
    <record>
      <property name="id_aclgrp" fieldname="id_aclgrp" datatype="autoincrement" required="yes"/>
      <property name="parent_id" required="false" datatype="int" />
      <property name="name" fieldname="name" datatype="string" required="yes"/>
      <property name="grouptype" fieldname="grouptype" datatype="int" required="yes"/>
      <property name="ownerlogin" fieldname="ownerlogin" datatype="string" />
    </record>
    <factory>
        <method name="method1" type="select">
            <conditions>
               <eq property="grouptype" value="1" />
            </conditions>
        </method>

        <method name="method2" type="select">
           <conditions>
              <neq property="grouptype" value="2" />
           </conditions>
           <order>
               <orderitem property="name" way="asc" />
           </order>
        </method>

        <method name="method3" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="ownerlogin" expr="$login" />
           </conditions>
        </method>

        <method name="method4" type="select">
           <parameter name="parent" />
           <parameter name="group" />
           <conditions>
              <eq property="grouptype" expr="$group" />
              <eq property="parent_id" expr="$parent" />
           </conditions>
        </method>
         <method name="method5" type="select">
           <parameter name="parent" />
           <parameter name="group" />
           <conditions>
              <eq property="grouptype" expr="$group" />
              <conditions logic="or">
                <eq property="parent_id" expr="$parent" />
                <eq property="id_aclgrp" expr="$parent" />
              </conditions>
           </conditions>
        </method>
        <method name="method6" type="select">
           <conditions>
              <in property="grouptype" value="1,2,3" />
              <isnull property="parent_id" />
           </conditions>
        </method>
        <method name="method7" type="select">
           <parameter name="parent" />
           <parameter name="group" />
           <conditions>
              <in property="grouptype" expr="$group" />
              <lt property="parent_id" expr="$parent" />
           </conditions>
        </method>
        <method name="method8" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="ownerlogin" expr="TOUPPER($login)" />
           </conditions>
        </method>
        <method name="method9" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="name" expr="TOUPPER($login)" />
           </conditions>
        </method>
        <method name="method10" type="select">
           <parameter name="login" />
           <conditions>
              <like property="name" value="a%" />
              <like property="ownerlogin" expr="$login" />
           </conditions>
        </method>
        <method name="method11" type="select">
           <conditions>
              <neq property="name" value="toto" />
           </conditions>
        </method>
        <method name="method12" type="select">
           <parameter name="login" />
           <conditions>
              <like property="ownerlogin" expr="concat($login,\'%\')" />
           </conditions>
        </method>
    </factory>
</dao>';
        $parser = new jDaoParser ($this->_selector);
        $parser->parse(simplexml_load_string($doc), $this->_tools);
        $generator = new testMysqlDaoGenerator($this->_selector, $this->_tools, $parser);

        $methods = $parser->getMethods();

        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 1',$where);

        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` <> 2',$where);

        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `ownerlogin` \'.($login === null ? \'IS NULL\' : \' = \'.$this->_conn->quote($login,false)).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method4']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method4']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` \'.\' = \'.intval($group).\' AND `parent_id` \'.($parent === null ? \'IS NULL\' : \' = \'.intval($parent)).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method5']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method5']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` \'.\' = \'.intval($group).\' AND ( `parent_id` \'.($parent === null ? \'IS NULL\' : \' = \'.intval($parent)).\' OR `id_aclgrp` \'.\' = \'.intval($parent).\')',$where);

        $where = $generator->BuildSQLCondition ($methods['method6']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method6']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` IN (1,2,3) AND `parent_id` IS NULL ',$where);

        $where = $generator->BuildSQLCondition ($methods['method7']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method7']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` IN (\'.implode(\',\', array_map( create_function(\'$__e\',\'return intval($__e);\'), $group)).\') AND `parent_id` \'.\' < \'.intval($parent).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method8']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method8']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `ownerlogin` = TOUPPER(\'.($login === null ? \'NULL\' : $this->_conn->quote($login,false)).\')',$where);

        $where = $generator->BuildSQLCondition ($methods['method9']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method9']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `name` = TOUPPER(\'.$this->_conn->quote($login).\')',$where);

        $where = $generator->BuildSQLCondition ($methods['method10']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method10']->getParameters(), false);
        $this->assertEqualOrDiff(' `name` LIKE \\\'a%\\\' AND `ownerlogin` \'.\' LIKE \'.$this->_conn->quote($login).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method11']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method11']->getParameters(), false);
        $this->assertEqualOrDiff(' `name` <> \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method12']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method12']->getParameters(), false);
        $this->assertEqualOrDiff(' `ownerlogin` LIKE concat(\'.($login === null ? \'NULL\' : $this->_conn->quote($login,false)).\',\\\'%\\\')',$where);

        // with prefix
        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 1',$where);

        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` <> 2',$where);

        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 2 AND `grp`.`ownerlogin` \'.($login === null ? \'IS NULL\' : \' = \'.$this->_conn->quote($login,false)).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method4']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method4']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` \'.\' = \'.intval($group).\' AND `grp`.`parent_id` \'.($parent === null ? \'IS NULL\' : \' = \'.intval($parent)).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method5']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method5']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` \'.\' = \'.intval($group).\' AND ( `grp`.`parent_id` \'.($parent === null ? \'IS NULL\' : \' = \'.intval($parent)).\' OR `grp`.`id_aclgrp` \'.\' = \'.intval($parent).\')',$where);

        $where = $generator->BuildSQLCondition ($methods['method6']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method6']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` IN (1,2,3) AND `grp`.`parent_id` IS NULL ',$where);

        $where = $generator->BuildSQLCondition ($methods['method7']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method7']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` IN (\'.implode(\',\', array_map( create_function(\'$__e\',\'return intval($__e);\'), $group)).\') AND `grp`.`parent_id` \'.\' < \'.intval($parent).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method8']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method8']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 2 AND `grp`.`ownerlogin` = TOUPPER(\'.($login === null ? \'NULL\' : $this->_conn->quote($login,false)).\')',$where);

        $where = $generator->BuildSQLCondition ($methods['method9']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method9']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 2 AND `grp`.`name` = TOUPPER(\'.$this->_conn->quote($login).\')',$where);

        $where = $generator->BuildSQLCondition ($methods['method11']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method11']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`name` <> \\\'toto\\\'',$where);

    }


    function testBuildSQLConditionWithPattern(){
        $doc ='<?xml version="1.0" encoding="UTF-8"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
    <datasources>
        <primarytable name="grp" realname="jacl_group" primarykey="id_aclgrp" />
    </datasources>
    <record>
      <property name="id_aclgrp" fieldname="id_aclgrp" datatype="autoincrement" required="yes"/>
      <property name="parent_id" required="false" datatype="int" />
      <property name="name" fieldname="name" datatype="string" required="yes" selectpattern="TOUPPER(%s)"/>
      <property name="grouptype" fieldname="grouptype" datatype="int" required="yes"/>
      <property name="ownerlogin" fieldname="ownerlogin" datatype="string" />
    </record>
    <factory>
        <method name="method1" type="select">
            <conditions>
               <eq property="name" value="toto" />
            </conditions>
        </method>

        <method name="method2" type="select">
           <conditions>
              <neq property="name" value="toto" />
           </conditions>
           <order>
               <orderitem property="name" way="asc" />
           </order>
        </method>

        <method name="method3" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="name" expr="$login" />
           </conditions>
        </method>

        <method name="method9" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="name" expr="TOUPPER($login)" />
           </conditions>
        </method>
    </factory>
</dao>';
        $parser = new jDaoParser ($this->_selector);
        $parser->parse(simplexml_load_string($doc), $this->_tools);
        $generator = new testMysqlDaoGenerator($this->_selector, $this->_tools, $parser);

        $methods=$parser->getMethods();

        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), false);
        $this->assertEqualOrDiff(' `name` = \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), false);
        $this->assertEqualOrDiff(' `name` <> \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `name` \'.\' = \'.$this->_conn->quote($login).\'',$where);

        // with prefix
        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`name` = \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`name` <> \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 2 AND `grp`.`name` \'.\' = \'.$this->_conn->quote($login).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method9']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method9']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `name` = TOUPPER(\'.$this->_conn->quote($login).\')',$where);

    }



    function testBuildSimpleCondition(){
        $doc ='<?xml version="1.0" encoding="UTF-8"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
    <datasources>
        <primarytable name="grp" realname="jacl_group" primarykey="id_aclgrp" />
    </datasources>
    <record>
      <property name="id_aclgrp" fieldname="id_aclgrp" datatype="autoincrement" required="yes"/>
      <property name="parent_id" required="false" datatype="int" />
      <property name="name" fieldname="name" datatype="string" required="yes"/>
      <property name="grouptype" fieldname="grouptype" datatype="int" required="yes"/>
      <property name="ownerlogin" fieldname="ownerlogin" datatype="string" />
    </record>
</dao>';
        $parser = new jDaoParser ($this->_selector);
        $parser->parse(simplexml_load_string($doc), $this->_tools);
        $generator = new testMysqlDaoGenerator($this->_selector, $this->_tools, $parser);

        $pkFields=$generator->GetPropertiesBy('PkFields');
        $this->assertTrue(count($pkFields) ==1);
        $this->assertTrue(isset($pkFields['id_aclgrp']));

        $where = $generator->BuildSimpleConditions ($pkFields);
        $this->assertEqualOrDiff(' `grp`.`id_aclgrp`\'.\' = \'.intval($id_aclgrp).\'',$where);

        $where = $generator->BuildSimpleConditions ($pkFields, 'record->');
        $this->assertEqualOrDiff(' `grp`.`id_aclgrp`\'.\' = \'.intval($record->id_aclgrp).\'',$where);

        $where = $generator->BuildSimpleConditions ($pkFields, 'record->', false);
        $this->assertEqualOrDiff(' `id_aclgrp`\'.\' = \'.intval($record->id_aclgrp).\'',$where);
    }


 function testBuildConditions(){
        $doc ='<?xml version="1.0" encoding="UTF-8"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
    <datasources>
        <primarytable name="grp" realname="jacl_group" primarykey="id_aclgrp" />
    </datasources>
    <record>
      <property name="id_aclgrp" fieldname="id_aclgrp" datatype="autoincrement" required="yes"/>
      <property name="parent_id" required="false" datatype="int" />
      <property name="name" fieldname="name" datatype="string" required="yes"/>
      <property name="grouptype" fieldname="grouptype" datatype="int" required="yes"/>
      <property name="ownerlogin" fieldname="ownerlogin" datatype="string" />
    </record>
    <factory>
        <method name="method1" type="select">
            <conditions>
               <eq property="grouptype" value="1" />
            </conditions>
        </method>
        <method name="method2" type="select" groupby="id_aclgrp,parent_id,name">
           <conditions>
              <neq property="grouptype" value="2" />
           </conditions>
           <order>
               <orderitem property="name" way="asc" />
           </order>
        </method>
        <method name="method3" type="select">
           <order>
               <orderitem property="name" way="asc" />
           </order>
        </method>
        <method name="method4" type="select" groupby="id_aclgrp,parent_id,name">
           <order>
               <orderitem property="name" way="asc" />
           </order>
        </method>
    </factory>
</dao>';
        $parser = new jDaoParser ($this->_selector);
        $parser->parse(simplexml_load_string($doc), $this->_tools);
        $generator = new testMysqlDaoGenerator($this->_selector, $this->_tools, $parser);

        $methods = $parser->getMethods();

        $this->assertTrue($methods['method1']->getConditions() != null);
        $sql = $generator->BuildConditions ($methods['method1']->getConditions(), $parser->getProperties(),
                                                $methods['method1']->getParameters(), false,  $methods['method1']->getGroupBy());
        $this->assertEqualOrDiff(' `grouptype` = 1', $sql);

        $this->assertTrue($methods['method2']->getConditions() != null);
        $sql = $generator->BuildConditions ($methods['method2']->getConditions(), $parser->getProperties(),
                                                $methods['method2']->getParameters(), false, $methods['method2']->getGroupBy());
        $this->assertEqualOrDiff(' `grouptype` <> 2 GROUP BY `id_aclgrp`, `parent_id`, `name` ORDER BY `name` asc', $sql);

        $this->assertTrue($methods['method3']->getConditions() !== null);
        $sql = $generator->BuildConditions ($methods['method3']->getConditions(), $parser->getProperties(),
                                                $methods['method3']->getParameters(), false, $methods['method3']->getGroupBy());
        $this->assertEqualOrDiff(' 1=1  ORDER BY `name` asc',$sql);

        $this->assertTrue($methods['method4']->getConditions() !== null);
        $sql = $generator->BuildConditions ($methods['method4']->getConditions(), $parser->getProperties(),
                                                $methods['method4']->getParameters(), false, $methods['method4']->getGroupBy());
        $this->assertEqualOrDiff(' 1=1  GROUP BY `id_aclgrp`, `parent_id`, `name` ORDER BY `name` asc', $sql);

        $sql = $generator->BuildConditions ($methods['method1']->getConditions(), $parser->getProperties(),
                                                $methods['method1']->getParameters(), true,  $methods['method1']->getGroupBy());
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 1', $sql);

        $sql = $generator->BuildConditions ($methods['method2']->getConditions(), $parser->getProperties(),
                                                $methods['method2']->getParameters(), true, $methods['method2']->getGroupBy());
        $this->assertEqualOrDiff(' `grp`.`grouptype` <> 2 GROUP BY `grp`.`id_aclgrp`, `grp`.`parent_id`, `grp`.`name` ORDER BY `grp`.`name` asc',$sql);

        $sql = $generator->BuildConditions ($methods['method3']->getConditions(), $parser->getProperties(),
                                                $methods['method3']->getParameters(), true, $methods['method3']->getGroupBy());
        $this->assertEqualOrDiff(' 1=1  ORDER BY `grp`.`name` asc',$sql);

        $sql = $generator->BuildConditions ($methods['method4']->getConditions(), $parser->getProperties(),
                                                $methods['method4']->getParameters(), true, $methods['method4']->getGroupBy());
        $this->assertEqualOrDiff(' 1=1  GROUP BY `grp`.`id_aclgrp`, `grp`.`parent_id`, `grp`.`name` ORDER BY `grp`.`name` asc',$sql);

    }
}
?>