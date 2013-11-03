<?php

namespace laborra\passiverecords;

class PassiveSchema
{
    /**
     * Used for schema caching
     * @var array 
     */
    private static $schemas = array();
    
    public $columns = array();
    
    /**
     * 
     * @param string $columnName the name of the propery
     * @param array $row a passive record row
     * @return mixed
     */
    public function getValue ($columnName, $row)
    {
        return $row[$this->getColumnIndex($columnName)];
    }

    public function getColumnIndex ($columnName)
    {
        return array_search($columnName, $this->columns);
    }
    
    /**
     * 
     * @param string $className the name of the class
     * @return PassiveSchema
     */
    public static function getForClass ($className)
    {
        //$class = get_called_class();
        if (isset(self::$schemas[$className])) {
            return self::$schemas[$className];
        }
        
        if (($schemaDef = $className::getSchemaDef()) !== false) {
            self::$schemas[$className] = self::createFromDef($schemaDef);
            return self::$schemas[$className];
        }
        
        self::$schemas[$className] = self::createFromReflection(new \ReflectionClass($className));
        return self::$schemas[$className];
    }
    
    public static function createFromReflection ($class)
    {
        $schema = new PassiveSchema();
		$names = array();
        
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			$name = $property->getName();
			if (!$property->isStatic()) {
				$names[] = $name;
			}
		}
        
        $schema->columns = $names;
		return $schema;
    }
    
    public static function createFromDef (array $schemaDef)
    {
        $columns = array();

        foreach ($schemaDef as $key => $value) {
            if (is_numeric($key)) {
                $columns[] = $value;
                continue;
            }
        }
        
        $schema = new PassiveSchema();
        $schema->columns = $columns;
		return $schema;
    }
}

