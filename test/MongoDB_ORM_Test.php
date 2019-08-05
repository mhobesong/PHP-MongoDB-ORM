<?php
use PHPUnit\Framework\TestCase;

require __DIR__.'/../src/MongoDB_ORM.php';

class MongoDB_ORM_Test extends TestCase
{
	private $test_db = "test";

	protected function setUp():void
	{
		//setup connection credentials
		MongoDB_ORM::$_DB_HOST = "localhost";
		MongoDB_ORM::$_DB_DATABASE = "test";

		//Clear collection
		$book = new Book();
		$bulk = new MongoDB\Driver\BulkWrite;
		$bulk->delete([]);
		$result = BOOK::$_manager->executeBulkWrite("{$this->test_db}.books", $bulk);
	}
	
	/**
	* @test
	*/
	public function should_throw_exception_on_database_connection_failure()
	{
		$exception_message = '';

		try{
			$book = new Book();
			$book2->connection("unknownHost");//overide default conn
			Book::$_manager->executeCommand('book', new MongoDB\Driver\Command(['ping' => 1]));
		}catch(Exception $e){
			$exception_message = $e->getMessage();
		}

		$this->assertNotEquals('', $exception_message);
	}
	
	/**
	* @test
	*/
	public function should_connect_to_mongodb()
	{
		$exception_message = '';

		try{
			$book = new Book();
			Book::$_manager->executeCommand('book', new MongoDB\Driver\Command(['ping' => 1]));
		}catch(Exception $e){
			$exception_message = $e->getMessage();
		}

		$this->assertEquals('', $exception_message);
	}
	
	/**
	* @test
	*/
	public function should_save_data_to_the_database()
	{
		$book = new Book();
		$author = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book->authors = [$author];
		$book->title = $title;
		$book->save();

		$mongo 		= BOOK::$_manager;
		$filter  	= ['_id' => $book->_id];
		$options 	= ['limit' => 1];
		$query 		= new \MongoDB\Driver\Query($filter, $options);
		$cursor   	= $mongo->executeQuery("{$this->test_db}.books", $query);
		$result = NULL;

		foreach ($cursor as $document) $result = $document;

		$this->assertEquals($author, $result->authors[0]);
		$this->assertEquals($title, $result->title);
	}
	
	/**
	* @test
	*/
	public function should_find_first_document_match_()
	{
		$book = new Book();
		$author = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book->authors = [$author];
		$book->title = $title;
		$book->save();

		$found = $book->first(['authors' => [$author]]);
		
		$this->assertEquals($author, $found->authors[0]);
		$this->assertEquals($title, $found->title);


		$found = $book->first(['_id' => "xyz"]);
		$this->assertEquals(NULL, $found);
	}

	
	/**
	* @test
	*/
	public function should_find_all_matching_documents()
	{
		$book1 = new Book();
		$author = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book1->authors = [$author];
		$book1->title = $title;
		$book1->save();

		$book2 = new Book();
		$book2->authors = [$author];
		$book2->title = $title;
		$book2->save();

		$book3 = new Book();
		$book3->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book3->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book3->save();

		$documents = $book1->find(['authors' => [$author]]);
		
		$this->assertEquals(2, count($documents));
		$this->assertEquals($title, $documents[0]->title);
		$this->assertEquals($title, $documents[1]->title);
	}
	
	/**
	* @test
	*/
	public function should_find_with_pipeline_options()
	{
		$book1 = new Book();
		$book1->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book1->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book1->save();

		$book2 = new Book();
		$book2->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book2->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book2->save();

		$book3 = new Book();
		$book3->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book3->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book3->save();

		$options = ['limit'=>2];

		$documents = $book1->find([], $options);
		
		$this->assertEquals(2, count($documents));
	}

	/**
	* @test
	*/
	public function should_update_object_on_save()
	{
		$book = new Book();
		$book->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$id = $book->save();

		$new_title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book->title = $new_title;
		$book->save();

		$document = $book->first(['_id'=>$id]);

		$this->assertEquals($new_title, $document->title);
	}

	/**
	* @test
	*/
	public function should_update_existing_documents_based_on_filter()
	{
		$book = new Book();
		$book->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$id = $book->save();

		$book1 = new Book();
		$book1->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book1->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book1->save();

		$book2 = new Book();
		$book2->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book2->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book2->save();

		$document = $book->update(['_id'=>$id],['authors'=>['Moses', 'Besong']]);

		$document = $book->first(['_id'=>$id]);

		$this->assertEquals(['Moses', 'Besong'], $document->authors);
	}

	/**
	* @test
	*/
	public function should_delete_existing_documents_based_on_filter()
	{
		$book = new Book();
		$book->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$id = $book->save();

		$book1 = new Book();
		$book1->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book1->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$id1 = $book1->save();

		$book->delete(['_id'=>$id]);
		$document = $book->first(['_id'=>$id]);
		$document1 = $book->first(['_id'=>$id1]);

		$this->assertEquals(NULL, $document);
		$this->assertNotEquals(NULL, $document1);
	}

	/**
	* @test
	*/
	public function should_count_document_matching_filter_condition()
	{
		$book = new Book();
		$book->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book->title = 'TDD';
		$book->save();

		$book1 = new Book();
		$book1->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book1->title = str_shuffle('abcdefghijklmnopqresuvwxyz');
		$book1->save();

		$book2 = new Book();
		$book2->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book2->title = 'TDD';
		$book2->save();

		$this->assertEquals(3, $book->count([]));
		$this->assertEquals(2, $book->count(['title'=>'TDD']));
	}

	/**
	* @test
	*/
	public function should_be_able_to_get_object_from_constructor()
	{
		$book = new Book();
		$book->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book->title = 'TDD';
		$book->save();

		$book1 = new Book();
		$book1->authors = ['John Wayne'];
		$book1->title = 'TDD';
		$id = $book1->save();

		$book2 = new Book();
		$book2->authors = [str_shuffle('abcdefghijklmnopqresuvwxyz')];
		$book2->title = 'TDD';
		$book2->save();

		$book = new Book(['authors'=>['John Wayne']]);

		$this->assertEquals(['John Wayne'], $book->authors);
		$this->assertEquals('TDD', $book->title);
		$this->assertEquals($id, $book->_id);
	}
}

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
