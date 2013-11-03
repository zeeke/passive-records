<?php

namespace laborra\passiverecords;

class PassiveRecord
{/*
    public static function getSchema ()
    {
        self::$schemas[$class] = self::createSchemaFromReflection($class);
        return self::$schemas[$class];
    }
*/
    /**
     * Override this function to declare the passive record schema
     * 
     * @return boolean
     */
    public static function getSchemaDef ()
    {
        return false;
    }

    /**
     * 
     * @param type $q
     * @return PassiveQuery|PassiveRecord
     * @throws InvalidConfigException
     */
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
        return $query->where(array('id' => $q))->one();
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

    /**
     * Creates a model instance from the given array, according to the schema.
     * 
     * @param array $row
     * @return PassiveRecord the passive record instance
     */
    public static function create ($row)
    {
        $record = new static;
        $columns = PassiveSchema::getForClass(get_called_class())->columns;
        foreach ($columns as $index => $key) {
            $record->{$key} = $row[$index];
        }
        return $record;
    }
}

