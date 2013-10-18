<?php

use extensions\passiverecords\PassiveRecord;

class SampleAccessTest extends yii\test\TestCase
{
    public function testBasic ()
    {
        $this->assertEquals(1,
            count(Role::find('ANONYMOUS')->getAllAllowedFunctionality()));
        $this->assertEquals(2,
            count(Role::find('USER')->getAllAllowedFunctionality()));
        $this->assertEquals(3,
            count(Role::find('MODERATOR')->getAllAllowedFunctionality()));
        $this->assertEquals(4,
            count(Role::find('ADMIN')->getAllAllowedFunctionality()));
    }
}

class Role extends PassiveRecord
{
    public static function getSchema ()
    {
        return array(
            'id' => array('PK'),
            'label',
        );
    }

    public static function getData ()
    {
        return array(
            array('ANONYMOUS', 'User'),
            array('USER', 'User'),
            array('MODERATOR', 'Moderator'),
            array('ADMIN', 'Administrator'),
        );
    }

    public function allowedFunctionalities ()
    {
        return RoleFunctionality::find()
            ->where(array('role_id' => $this->id));
    }

    public function getAllAllowedFunctionalities ()
    {
        $roleFunctionalities = $this->allowedFunctionality->all();
        $ret = array();
        foreach ($roleFunctionalities as $roleFunctionality) {
            $ret[] = Functionality::find($roleFunctionality->Functionality_id);
        }

        return $ret;
    }
}

class Functionality extends PassiveRecord
{
    public static function getSchema ()
    {
        return array(
            'id' => array('PK'),
            'label',
        );
    }

    public static function getData ()
    {
        return array(
            array('READ_POST', 'Read posts'),
            array('WRITE_POST', 'Write posts'),
            array('MODERATE_POST', 'Moderate posts'),
            array('CREATE_USER', 'Create user'),
        );
    }
}

class RoleFunctionality extends PassiveRecord
{
    public static function getSchema ()
    {
        return array(
            'role_id',
            'Functionality_id',
        );
    }

    public static function getData ()
    {
        return array(
            array('ANONYMOUS', 'READ_POST'),
            array('USER', 'READ_POST'),
            array('USER', 'WRITE_POST'),
            array('MODERATOR', 'READ_POST'),
            array('MODERATOR', 'WRITE_POST'),
            array('MODERATOR', 'MODERATE_POST'),
            array('ADMIN', 'READ_POST'),
            array('ADMIN', 'WRITE_POST'),
            array('ADMIN', 'MODERATE_POST'),
            array('ADMIN', 'CREATE_USER'),
        );
    }
}

