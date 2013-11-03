<?php

use laborra\passiverecords\PassiveRecord;

class PassiveRecordTest extends yii\test\TestCase
{
    public function testFindAllByHash ()
    {
        $models = Model::findAll(array('attr1' => 'x'));

        $this->assertTrue(count($models) === 2);
        $this->assertTrue($models[0] instanceof Model);
        $this->assertTrue($models[1] instanceof Model);

        $this->assertEquals(1, $models[0]->id);
        $this->assertEquals(2, $models[1]->id);
    }

    public function testFindByHash ()
    {
        $model = Model::find(array('attr1' => 'y'));

        $this->assertTrue($model instanceof Model);
        $this->assertEquals(3, $model->id);
        $this->assertEquals(10, $model->attr3);
    }

    public function testFindById ()
    {
        $model = Model::find(3);

        $this->assertTrue($model instanceof Model);
        $this->assertEquals(3, $model->id);
        $this->assertEquals(10, $model->attr3);
    }

    public function testNumericOrderBy ()
    {
        $data = Model::find()->orderBy('attr3')->one();
        $this->assertEquals(4, $data->id);
    }

    public function testStringOrderBy ()
    {
        $data = Model::find()->orderBy('attr2 asc')->one();
        $this->assertEquals(4, $data->id);
    }

    public function testMultipleOrder ()
    {
        $data = Model::find()
            ->orderBy('attr3 asc')
            ->orderBy('attr1 desc')
            ->one();
        $this->assertEquals(3, $data->id);
    }

    public function testOrderByWithCriteria ()
    {
        $data = Model::find()
            ->where(array('attr1' => 'x'))
            ->orderBy('attr3 desc')
            ->one();
        $this->assertEquals(2, $data->id);
    }
}

class Model extends PassiveRecord
{
    public $id;
    public $attr1;
    public $attr2;
    public $attr3;
    
    public static function getData ()
    {
        return array(
            array(1,'x', 'z', 10),
            array(2,'x', 'w', 15),
            array(3,'y', 'a', 10),
            array(4,'y', 'W', 30),
        );
    }
}

class InvalidConfigModel extends PassiveRecord
{
	
}

