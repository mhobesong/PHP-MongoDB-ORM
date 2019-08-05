<?php

/**
 * Simple ORM wrapper for MongoDB
 * @author Moses Besong Besong
 * @email mosbesong@gmail.com
 * @link https://github.com/mhobesong/Simple-PHP-MongoDB-ORM
 */
class MongoDB_ORM{
	
	public static $_DB_HOST			= "";

	public static $_DB_USER			= "";

	public static $_DB_PASSWORD		= "";

	public static $_DB_DATABASE		= "";

	public static $_DB_PORT 		= '27017';

	/**
	* @var MongoDB\Driver\Manager
	*/
	public static $_manager;

    /**
     * the mongo auto id
     *
     * @var \MongoDB\BSON\ObjectId
     */
    public $_id = NULL;

    protected $_mongo_timeout;

    /**
     * @var MongoDate
     */
    public $insertDate;

    /**
     * @var MongoDate
     */
    public $updateDate;

    /***
     * the constructor
     * @param $condition array populate instance with first condition match
     */
    function __construct($condition = array()){
        $this->connection();
	
		if ($condition) {
			$document = $this->first($condition);

			foreach ($this as $key=>$value){
				if ($key[0] != '_')	$this->$key = $document->$key;
			}

			$this->_id = $document->_id;
		}

    }

    /**
     * Setup database connection
     */
    public function connection($host="", $user="", $password="", $database="", $port=null){

		self::$_DB_HOST 		= $host 	? $host 	: self::$_DB_HOST;
		self::$_DB_USER 		= $user 	? $user 	: self::$_DB_USER;
		self::$_DB_PASSWORD 	= $password ? $password : self::$_DB_PASSWORD;
		self::$_DB_PORT 		= $port 	? $port 	: self::$_DB_PORT;
		self::$_DB_DATABASE 	= $database ? $database : self::$_DB_DATABASE;

		$connectionString = 'mongodb://';

		$connectionString .= (self::$_DB_USER) ? self::$_DB_USER : '';

		$connectionString .= (self::$_DB_USER) ? ':' : '';

		$connectionString .= (self::$_DB_USER && self::$_DB_PASSWORD) ? self::$_DB_PASSWORD : '';

		$connectionString .= (self::$_DB_USER) ? '@' : '';

		$connectionString .= self::$_DB_HOST;

		$connectionString .= (self::$_DB_PORT) ? (':' . self::$_DB_PORT) : '';

		$connectionString .= (self::$_DB_DATABASE) ? ('/' . self::$_DB_DATABASE) : '';

        //connect to the DB
        self::$_manager = new \MongoDB\Driver\Manager($connectionString);

        $this->_mongo_timeout = 300000;
    }

    //store the current object
    function save(){
		$document = [];

		foreach ($this as $key=>$value){
			if ($key[0] != '_') $document[$key] = $value;
		}

		if ($this->_id != NULL) $document['_id'] = $value;

		$bulk = new MongoDB\Driver\BulkWrite;

		try{
			if ($this->_id) {
				unset($document['_id']);
				$document['updateDate'] = new MongoDB\BSON\UTCDateTime;

				$bulk->update(
					['_id' => $this->_id],
					['$set' => $document],
					['multi' => false, 'upsert' => false]
				);
			}else{
				$document['createDate'] = new MongoDB\BSON\UTCDateTime;
				$this->_id = $bulk->insert($document);
			}
			$result = self::$_manager->executeBulkWrite(self::$_DB_DATABASE . ".{$this->_collection}", $bulk);
		}catch(Exception $e){
			echo $e->getMessage();
		}

		return $this->_id;
    }

	/**
	* Find first occurent that meets filter criteria
	* @param $filter array Associative array
	* @return mixt
	*/
	public function first($filter)
	{
		$mongo 		= self::$_manager;
		$options 	= ['limit' => 1];
		$query 		= new \MongoDB\Driver\Query($filter, $options);
		$cursor   	= $mongo->executeQuery(self::$_DB_DATABASE . ".{$this->_collection}", $query);
		
		foreach ($cursor as $document) return $document;

		return NULL;
	}

	/**
	* Find all documents that meets filter criteria
	* @param $filter array Associative array
	* @param $option array Associative array
	* @return array
	*/
	public function find($filter, $options = [])
	{
		$mongo 		= self::$_manager;

		try{
			$query 		= new \MongoDB\Driver\Query($filter, $options);
			$cursor   	= $mongo->executeQuery(self::$_DB_DATABASE . ".{$this->_collection}", $query);
		}catch(Exception $e){
			echo $e->getMessage();
		}
		
		return $cursor->toArray();
	}

    /**
     * insert new data into the collection
     */
    protected function insert(){

        //write an insert date
        $this->_data["insertDate"] = new MongoDB\BSON\UTCDateTime;
        $insertDate = new stdclass;
        $millisec = (string)$this->_data["insertDate"];
        $insertDate->sec = intval($millisec/1000);
        $insertDate->usec = intval($millisec);
        $this->insertDate = $insertDate;
        //insert into db
		$bulk = new MongoDB\Driver\BulkWrite; 
		$bulk->insert($this->_data);
		//Log this activity
		SysLogger::logactivity($this->_database, 'insert', $this->_collection, $this->_data);

		try {
			$manager = MongoDBConnection::getManager();
			$result = $manager->executeBulkWrite($this->_database.'.'.$this->_collection, $bulk);

			$query = new MongoDB\Driver\Query($this->_data);
            $cursor = $manager->executeQuery($this->_database.'.'.$this->_collection, $query);

            foreach ($cursor as $document) {
                $this->_id = $document->_id;
                break;
            }

		} catch (Exception $e) {
			return false;
		}
        return true;
    }

    /**
     * Update existing data of the collection
	 * @param $set array key/value pair associative array of field to set.
	 * @param $condition array key/value associative array of searh condition 
	 * @param $multi boolean TRUE for multiple document updates. False otherwise
	 * @return int Number of updated documents.
     */
    public function update($condition=array(),$set=array(), $multi=true){
		$bulk = new MongoDB\Driver\BulkWrite;

		try{
			$set['updateDate'] = new MongoDB\BSON\UTCDateTime;
			$bulk->update(
				$condition,
				['$set' => $set],
				['multi' => $multi, 'upsert' => false]
			);

			$result = self::$_manager->executeBulkWrite(self::$_DB_DATABASE . ".{$this->_collection}", $bulk);

		}catch(Exception $e){
			echo $e->getMessage();
		}

		return $result->getModifiedCount();
    }


    /**
     * delete documents that match given conditin filter
	 * @return int Number of deleted documents
     */
    function delete($condition = array()){
		$bulk = new MongoDB\Driver\BulkWrite;
		$bulk->delete($condition);
		$result = self::$_manager->executeBulkWrite(self::$_DB_DATABASE . ".{$this->_collection}", $bulk);
		return $result->getDeletedCount();
    }

    /**
     * @return int Number of document matching filter condition
     */
    function count($condition = array()){
        return count($this->find($condition));
    }

}

?>
