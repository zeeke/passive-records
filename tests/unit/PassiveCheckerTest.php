<?php

use extensions\passiverecords\PassiveSchema;
use extensions\passiverecords\PassiveChecker;
use extensions\passiverecords\PassiveQueryException;

class PassiveCheckerTest extends yii\test\TestCase
{
    private $ROW = array(1,4,4);
    
    private static function create ($condition, $schema = null)
    {
        if (!$schema) {
            $schema = new PassiveSchema();
            $schema->columns = array('id', 'intVal1', 'intVal2');
        }
        return new PassiveChecker($condition, $schema);
    }
    
    public function testCompare ()
    {
        $schema = new PassiveSchema();
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
        
        try {
            $checker = new PassiveChecker(array('X', 'intVal1', 4), $schema);
            $checker->isAcceptable(array(1,5,4));
            $this->fail();
        } catch (PassiveQueryException $e) {}
    }
    
    public function testInCondition ()
    {
        $schema = new PassiveSchema();
        $schema->columns = array('id', 'intVal1', 'intVal2');
        
        $checker = new PassiveChecker(array('IN', 'intVal1', array(1,2,3)), $schema);
        $this->assertFalse($checker->isAcceptable(array(1,4,4)));
        $this->assertTrue($checker->isAcceptable(array(1,3,4)));
        $this->assertFalse($checker->isAcceptable(array(1,5,4)));
        $this->assertTrue($checker->isAcceptable(array(1,2,4)));
    }
    
    public function testBadInCondition ()
    {
        $schema = new PassiveSchema();
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
    
    public function testNotCondition ()
    {
        $schema = new PassiveSchema();
        $schema->columns = array('id', 'intVal1', 'intVal2');
        
        try {
            $checker = new PassiveChecker(array('!', 'operator1', 'operator2'), $schema);
            $checker->isAcceptable(array(1,4,4));
            $this->fail();
        } catch (PassiveQueryException $e) {}
        
        $checker = new PassiveChecker(array('!', true), $schema);
        $this->assertFalse($checker->isAcceptable(array(1,4,4)));
    }
    
    public function testLogicConditions () {
        
        $schema = new PassiveSchema();
        $schema->columns = array('id', 'intVal1', 'intVal2');
        
        $this->assertTrue(
            self::create(array('AND', true, true))
                ->isAcceptable($this->ROW));
        
        $this->assertTrue(
            self::create(array('OR', false, true))
                ->isAcceptable($this->ROW));
        
        $this->assertFalse(
            self::create(array('AND', false, array('OR', false, true)))
                ->isAcceptable($this->ROW));
    }
}

