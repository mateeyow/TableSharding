<?php

/**
 * Sharding class file
 * Sharding is a yii extension that performs horizontal partitioning of a table
 * The purpose of this is to reduce the index size and thus reduces query time
 * 
 * @author Matthew Torres
 * @version 1.0 2/12/2014
 */
class Sharding
{
	private $currTable, $nextTable, $shardTally, $attr;

	/**
	 * Initializes table name, near max and limit of the table
	 * @param string $tableName the name of the table to be sharded
	 * @param integer $limit     the limit of the table
	 * for asynchronous-like sharding
	 */
	public function __construct($tableName, $limit){
		$this->table = $tableName;
		$this->limit = $limit;
	}

	/**
	 * Checks if table exists in shard table and initializes table names and tally
	 * @return string method that counts the number of rows of the table to be sharded
	 */
	public function useTable(){
		$this->attr = $this->findTable();
		if($this->attr)
		{
			$lastAttr         = count($this->attr) - 1;
			$this->shardTally = $this->attr[$lastAttr]->count;
			$this->currTable  = $this->table . $this->shardTally;
			$this->nextTable  = $this->table . ($this->shardTally + 1);
		}else {
			$this->currTable  = $this->table;
			$this->nextTable  = $this->table . 1;
			$this->shardTally = 1;
		}	
		return $this->countRow();
	}

	/**
	 * Counts the number of rows based on the defined max number of rows
	 * @return string returns new name if row count is greater than or equal to the row count
	 * returns current table if it does not 
	 */
	private function countRow(){
		$dataReader = $this->queryBuilder('SELECT * FROM ' . $this->currTable);
		$rowCount   = $dataReader->rowCount;

		if($rowCount >= $this->limit)
		{
			$this->createTable();
			if($this->attr){
				$model             = new Shardtable;
				$model->columnName = $this->table;
				$this->shardTally  = $model->count = $this->shardTally + 1;
				$model->save();
				$this->mergeTable();
			}else {
				$model             = new Shardtable;
				$model->columnName = $this->table;
				$model->count      = 1;
				$model->save();
			}
			return $this->nextTable;
		}else {
			return $this->currTable;
		}
	}

	/**
	 * Creates a new schema copied from the pervious table and changed some names 
	 * to create an empty table
	 * @return [type] [description]
	 */
	private function createTable(){
		if($this->attr){
			$db      = Yii::app()->db;
			$command = $db->createCommand('SHOW CREATE TABLE' . $db->quoteTableName($this->currTable) . ';')->queryRow();
			$sql     = $command['Create Table'];
			preg_match_all('/(_\d+)/', $sql, $matches);

			for($i=0;$i<count($matches[1]);$i++){
				$sql = preg_replace('/' . $matches[1][$i] . '/', '_' . ($this->shardTally + 1), $sql);
			}
			$sql = preg_replace('/`' . $this->currTable .'`/', '`' . $this->nextTable . '`', $sql);
		}else {
			$db      = Yii::app()->db;
			$command = $db->createCommand('SHOW CREATE TABLE' . $db->quoteTableName($this->table) . ';')->queryRow();
			$sql     = $command['Create Table'];
			preg_match_all('/T\s`(\w+)`\sF/', $sql, $matches);

			for($i=0;$i<count($matches[1]);$i++){
				$sql = preg_replace('/' . $matches[1][$i] . '/', $matches[1][$i] . '_' . 1, $sql);
			}
			$sql = preg_replace('/`' . $this->table .'`/', '`' . $this->nextTable . '`', $sql);
		}

		$tableExist = Yii::app()->db->schema->getTable($this->nextTable);
		$tableExist ? $dataReader = '' : $dataReader = $this->queryBuilder($sql);
	}

	/**
	 * Inserts previously sharded table and
	 * merges it to the main table
	 */
	private function mergeTable(){
		if($this->shardTally >= 2){
			$this->shardTally -= 1;
			$dropTable = $this->table.$this->shardTally;
			Yii::app()->db->createCommand('INSERT INTO ' . $this->table . ' SELECT * FROM ' . $dropTable)->execute();
			Yii::app()->db->createCommand()->dropTable($dropTable);
		}
	}

	/**
	 * Query builder and execution
	 * @param  string $sql retrieves query to be executed
	 */			
	private function queryBuilder($sql){
		$conn       = Yii::app()->db;
		$command    = $conn->createCommand($sql);
		$dataReader = $command->query();
		return $dataReader;
	}

	/**
	 * Checks if table name has been stored in the shard table
	 * @return string attributes of the table name
	 * returns null if empty
	 */
	private function findTable(){
		$citeria = new CDbCriteria;
		$citeria->addCondition(' columnName = "' . $this->table . '"');
		$shard   = Shardtable::model()->findAll($citeria);
		return $shard;
	}
}

?>