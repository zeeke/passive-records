<?php

class PassiveRecord extends CModel
{
    private static $schemas = array();
    
    public function attributeNames ()
	{
        throw new CException('XXX - To implement!!!');
	}
    
    public static function primaryKey ()
    {
        return array('id');
    }
    
    public static function getColumns()
    {
        $class = new ReflectionClass(get_called_class());
		$names = array();
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			$name = $property->getName();
			if (!$property->isStatic()) {
				$names[] = $name;
			}
		}
		return $names;
    }
    
    public static function getData ()
    {
        throw new CException('Passive models must implement static function getData()');
    }
    
    public static function getSchema ()
    {
        $class = get_called_class();
        if (!isset(self::$schemas[$class])) {
            $schema = new PassiveSchema();
            $schema->primaryKey = static::primaryKey();
            $schema->columns = static::getColumns();
            self::$schemas[$class] = $schema;
        }
        return self::$schemas[$class];
    }
    
    public static function find ($q = null)
	{
		$query = static::createQuery();
        
        if ($q === null) {
            return $query;
        }
		if (is_array($q)) {
			return $query->where($q)->one();
        }
        // query by primary key
        $primaryKey = static::primaryKey();
        if (isset($primaryKey[0])) {
            return $query->where(array($primaryKey[0] => $q))->one();
        }
        throw new InvalidConfigException(get_called_class() . ' must have a primary key.');
	}
    
    public static function findAll (array $q = null)
    {
		return static::createQuery()->where($q)->all();
    }
    
    public static function createQuery ()
    {
        $query = new PassiveQuery();
        $query->modelClass = get_called_class();
        return $query;
    }
    
    public static function create ($row)
    {
        $record = new static;
        $columns = static::getSchema()->columns;
        foreach ($columns as $index => $key) {
            $record->{$key} = $row[$index];
        }
        return $record;
    }
}

