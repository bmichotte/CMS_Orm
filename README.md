# CMS_Orm #

This is a set of php classes for Zend that manage creation/modification of your database for you.

__warning, do not use on production__

CMS_Orm is a work in progress. Please feel free to pull request to add new features, corrections.  
__Atm, only Pdo_Mysql is implemented.__  

## Usage ##

Simply add the CMS library to your library folder, register the namespace CMS_.  
Create a plugin to launch the orm parser. 

```
<?php
class My_Controller_Plugin_Orm extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $orm = new CMS_Db_Orm();
        /* set the cache dir */
        $orm->setCacheDir(APPLICATION_DIRECTORY . '/cache/orm/');
        /* set the database adapter */
        $orm->setDbAdapter(Zend_Registry::get("Zend_Db"));

        /* add your models path */
        foreach (Zend_Loader_Autoloader::getInstance()->getAutoloaders() as $autoloader)
        {
            if (!$autoloader instanceof Zend_Loader_Autoloader_Resource)
            {
                continue;
            }
            /** @var $autoloader Zend_Loader_Autoloader_Resource */
            $resourceTypes = $autoloader->getResourceTypes();
            if (!isset($resourceTypes['model']))
            {
                continue;
            }
            $orm->addPath($resourceTypes['model']['path']);
        }
        $orm->run();
    }
}
```

### Annotate your classes ###

#### Entity ####
__Entity__ tells CMS_Orm that your class is an entity  
You can omit the table name, in this case, the last fragment (_) of the class name will be used.
```
<?php
/**
 * @Entity(table=users)
 */
class Model_User extends CMS_Db_Orm_Row_Abstract
{
}

/**
 * the table name will be "group"
 * @Entity
 */
class Model_Group extends CMS_Db_Orm_Row_Abstract
{
}

```

#### Column ####
__Column__ tells CMS_Orm that this property is a column of your entity
```
/**
  * @Column
  */    
  protected $firstname;
```

__Column__ accepts the following arguments
* __type__: The type of the column. You can omit this argument if you set the ```@var``` 
  * __valid values__: integer, string, smallint, bigint, boolean, decimal, datetime, timestamp, text, object, array, json, float, double, blob
* __name__: If you want a different name in the database
* __length__: The length of the field. 
  * varchar have a default length of 255
  * integer have a default length of 10
  * decimal have a default length of 5, 2
* __unique__: true/false, default is false
* __nullable__: true/false, default is false
* __default__: the default value for the column
* __unsigned__: true/false, default is false

#### Id ####
__Id__ will set this column as primary key
```
/**
  * @Id
  */    
  protected $id;
```

### Example ###
```
<?php
/**
 * @Entity(table=users)
 */
class Model_User extends CMS_Db_Orm_Row_Abstract
{
    /**
      * @var integer
      * @Id
      * @Column(name=user_id)
      */
     protected $id;

     /**
      * @Column(type=varchar, length=80)
      */
     protected $firstname;

     /**
      * @Column(type=varchar, length=80)
      */
     protected $lastname;

     /**
      * @Column(type=varchar, length=150, unique=true)
      */
     protected $email;

     /**
      * @var bool
      * @Column(default=true)
      */
     protected $enabled;

     /**
      * @var string
      * @Column
      */
     protected $password;
}

class someClass
{
    function someFunction()
    {
        $user = new Model_User();
        // use magic __*() functions
        $user->setFirstname("Benjamin")
            ->setLastname("Michotte")
            ->setEmail("someEmail")
            ->save();        
    }
    
    function otherFunction()
    {
        try
        {
            $user = new Model_User();
            
            // use magic function, can call findBy[\w+]()
            $user->findById(1);
            
            $user->setPassword("foo")->save();
        }
        catch(Zend_Db_Exception $e)
        {
            // user not found
        }
    }
}
```
