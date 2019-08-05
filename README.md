# Simple-PHP-MongoDB-ORM
A simple PHP ORM wrapper for MongoDB.

## SetUp
```php
require('src/MongoDB_ORM.php');

//set DB credentials
MongoDB_ORM::$_DB_HOST      = "localhost";
MongoDB_ORM::$_DB_USER      = "root";
MongoDB_ORM::$_DB_DATABASE  = "test";
MongoDB_ORM::$_DB_PASSWORD  = "1234";
MongoDB_ORM::$_DB_PORT      = 27017;
```

## Usage

Say we have a Book class defined as follows. You have to specify the collection to which the class will link to as a protected class property with name **$_collection**. This way, every query is done against the specified collection.

```php
class Book extends MongoDB_ORM
{
	protected $_collection = 'books';
	/**
	* @var string
	*/
	var $title;
	/**
	* @var string[]
	*/
	var $authors;
	/**
	* Price in USD
	* @var int
	*/
	var $price;
}
```

### Insert

```php
$book = new Book();
$author = 'John Wayne';
$title = 'Solid Liquids';
$book->authors = [$author];
$book->title = $title;
$book->save();
```

### Find first occurence

Returns NULL if the document is not found.

```php
$book = new Book();
$document = $book->first(['title' => 'Solid Liquids']);
```

You can also directly load the instance.

```php
$book = new Book();
$author = 'John Wayne';
$title = 'Solid Liquids';
$book->authors = [$author];
$book->title = $title;
$book->save();

$book2 = new Book(['authors'=>['John Wayne']]);
echo $book2->authors[0]; //"John Wayne";
echo $book2->title; //"Solid Liquids"
```

### Find
Returns an array of objects or an empty array if no match is found.

```php
$book = new Book();
$documents = $book->find(['authors' => [$author]]);
```

Select all documents

```php
$book = new Book();
$documents = $book->find();
```

You can specify pipeline stage options as second parameter.

```php
$book = new Book();
$options = ['limit' => 2, 'sort' => ['_id' => -1]];
$documents = $book->find(['authors' => [$author]], $options);
```

### Update

#### Instance update

```php
$book = new Book();
$book->authors = ['Tim Bakley', 'Rose Javan', 'Mike Andrew'];
$book->title = 'No Way';
$id = $book->save();

$new_title = 'Many Ways';
$book->title = $new_title;
$book->save(); //Updates this instance only
```

#### Update all match

```php
$book = new Book();
$filter = ['_id'=>$id];
$set = ['authors'=>['Moses', 'Besong']];

$document = $book->update($filter, $set);
```

### Delete

```php
$book = new Book();
$filter = ['title' => 'No Way'];
$book->delete($filter);
```

Delete all documents in the collection

```php
$book = new Book();
$book->delete();
```

### Count

Count all document with 'No Way' as 'title'.
```php
$book = new Book();
$filter = ['title' => 'No Way'];
$count = $book->count($filter);
```

Count all documents in the collection

```php
$book = new Book();
$count = $book->count();
```
