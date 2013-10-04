<?php

use extensions\passiverecords\PassiveSchema;
use extensions\passiverecords\PassiveChecker;
use extensions\passiverecords\PassiveQueryException;

class PassiveCheckerTest extends yii\test\TestCase
{
    public function testCompare ()
    {
        $schema = $schema = new PassiveSchema();
        $schema->columns = array('id', 'intVal1', 'intVal2');
        
        $checker = new PassiveChecker(array('=', 'intVal1', 4), $schema);
        $this->assertTrue($checker->isAcceptable(array(1,4,4)));
        $this->assertFalse($checker->isAcceptable(array(1,5,4)));
        
        $checker = new PassiveChecker(array('!=', 'intVal1', 4), $schema);
        $this->assertFalse($checker->isAcceptable(array(1,5,4)));
        $this->assertTrue($checker->isAcceptable(array(1,4,4)));
        $this->assertFalse($checker->isAcceptable(array(1,3,4)));
        
        $checker = new PassiveChecker(array('>', 'intVal1', 4), $schema);
        $this->assertTrue($checker->isAcceptable(array(1,5,4)));
        $this->assertFalse($checker->isAcceptable(array(1,4,4)));
        $this->assertFalse($checker->isAcceptable(array(1,3,4)));
        
        $checker = new PassiveChecker(array('>=', 'intVal1', 4), $schema);
        $this->assertTrue($checker->isAcceptable(array(1,5,4)));
        $this->assertTrue($checker->isAcceptable(array(1,4,4)));
        $this->assertFalse($checker->isAcceptable(array(1,3,4)));
        
        $checker = new PassiveChecker(array('<', 'intVal1', 4), $schema);
        $this->assertFalse($checker->isAcceptable(array(1,5,4)));
        $this->assertFalse($checker->isAcceptable(array(1,4,4)));
        $this->assertTrue($checker->isAcceptable(array(1,3,4)));
        
        $checker = new PassiveChecker(array('<=', 'intVal1', 4), $schema);
        $this->assertFalse($checker->isAcceptable(array(1,5,4)));
        $this->assertTrue($checker->isAcceptable(array(1,4,4)));
        $this->assertTrue($checker->isAcceptable(array(1,3,4)));
        
        try
        {
            $checker = new PassiveChecker(array('X', 'intVal1', 4), $schema);
            $checker->isAcceptable(array(1,5,4));
            $this->fail();
        } catch (PassiveQueryException $e) {}
    }
    
    public function testInCondition ()
    {
        $schema = $schema = new PassiveSchema();
        $schema->columns = array('id', 'intVal1', 'intVal2');
        
        $checker = new PassiveChecker(array('IN', 'intVal1', array(1,2,3)), $schema);
        $this->assertFalse($checker->isAcceptable(array(1,4,4)));
        $this->assertTrue($checker->isAcceptable(array(1,3,4)));
        $this->assertFalse($checker->isAcceptable(array(1,5,4)));
        $this->assertTrue($checker->isAcceptable(array(1,2,4)));
    }
    
    public function testBadInCondition ()
    {
        $schema = $schema = new PassiveSchema();
        $schema->columns = array('id', 'intVal1', 'intVal2');
        
        try {
            $checker = new PassiveChecker(array('IN', 0, array(1,2,3)), $schema);
            $checker->isAcceptable(array(1,4,4));
            $this->fail();
        } catch (PassiveQueryException $e) {}
        
        try {
            $checker = new PassiveChecker(array('IN', 'badColumn', array(1,2,3)), $schema);
            $checker->isAcceptable(array(1,4,4));
            $this->fail();
        } catch (PassiveQueryException $e) {}
        
        try {
            $checker = new PassiveChecker(array('IN', 'intVal1', 'bad value'), $schema);
            $checker->isAcceptable(array(1,4,4));
            $this->fail();
        } catch (PassiveQueryException $e) {}
    }
}

