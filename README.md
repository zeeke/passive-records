Passive Records for PHP
===============

[![Build Status](https://travis-ci.org/zeeke/passive-records.png?branch=master)](https://travis-ci.org/zeeke/passive-records) [![Coverage Status](https://coveralls.io/repos/zeeke/passive-records/badge.png)](https://coveralls.io/r/zeeke/passive-records)


This extension aims to provide a way to model read only databases. These
kind of databases are useful when a software component needs to access
data that doesn't change so often. For instance, if you have to model
the list of all the world countries, you can choose between two
solution:
- Make a static array (or any other collection) of countries and access
  them with basic operations. This solution offers high performance and
  a good data maintainability. When data needs to change, the version
  control system helps you to bring the change in every environment of
  the application.
- Store the information in the database. In this way data is accessed
  using plain SQL statements or through an ORM library, providing a more
  productive way to build the application. When data needs to change, an
  SQL script (or something similar if you are using a NoSQL db) must be
  produced and it must run on every application environment. Advanced
  PHP frameworks offer tools to make this task less painful, but it is
  always a pleasant job.

## The passive approach

This library tries to achieve the best of the two methods described
above. We would like to have a developer friendly data access interface,
with good performances and a maintainable data set.

The application interface is ispired to Yii2 framework Active Record and
it is based on model class that describes data structure and data
contents.

## Model definition

To declare a PassiveRecord class you need to extend
laborra\db\PassiveRecord and implement `getSchema` and `getData`
methods like the following.

~~~
class Country extends PassiveRecord
{
    public static function getSchema ()
    {
        return array(
            'iso' => array('pk'),
            'label',
        );
    }

    public static function getData ()
    {
        return array(
            array('it', 'Italy'),
            array('us', 'United States'),
            ...
        );
    }
}
~~~

## Accessing data

In the example we have a country data model that stores all countries we
need. The structure is similar to relational databases: we have two
columns, iso and label. The 'iso' property is the primary key of the
model, so it has to be unique in the data set. 
The getData() function provides the data of the model.
To access data we can use the ORM methods like:

~~~
// Find by primary key
$country = Country::find('it');
$this->assertInstanceOf('Country', $country);
$this->assertEquals('it', $country->iso);
$this->assertEquals('Italy', $country->label);

// Find by column condition
$countries = Country::find()->where('label', 'like', 'Ital%')->all();
$this->assertInstanceOf('Country', $countries[0]);

// Count by condition
$nCountries = Country::find()->where(array('label' => 'Italy'))->count();
$this->assertEquals($nCountries, 1);
~~~

The `getSchema` function declare the class schema and it can be
expressed as array or as `PassiveSchema`. In this way we declare the
properties our passive objects have, enabling read only access to them
and allowing criteria search.

The `getData` function declare the content of the class passive record
collection. It has to return an array matrix with the following syntax:

~~~
array(
    array('value 1 column 1', 'value 1 column 2', ...), // First row content
    array('value 2 column 1', 'value 2 column 2', ...), // Second row content
    ...
);
~~~

Each row is expressed by an array of values and the order is expected to
be compliant with the return value of `getSchema`.


## Adanced usage: mixing active and passive records

Consider an application that must be secured by classic access
permission: users have roles and each role can access a set of
application functionalities. We have to model the user, roles and
functionality data. Thus, we have a meny to meny relationship between
users and roles and another between roles and functionality. 
Obviously, user data must be kept in a read/write database, so we will
use classic active records for users and user_role models.
In this example, application roles are fixed and they cannot be modified
at runtime. So, roles, functionalities and role_functionlity models will
be implemented using passive records.

~~~
class Role extends PassiveRecord
{
    public static function getSchema ()
    {
        return array(
            'name' => array('PK'),
            'label',
        );
    }
    
    public static function getData ()
    {
        return array(
            array('ADMIN', 'Administrator'),
        );
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
            array('func1', 'Basic functionality'),
            array('func2', 'Other functionality'),
            array('func3', 'Admin only functionality'),
        );
    }
}

class RoleFunctionality extends PassiveRecord
{
    public static function getSchema ()
    {
        return array(
            'id' => array('PK'),
            'label',
        );
    }
}
~~~

## Documentation

### Declaring passive record classes
[TBD]

