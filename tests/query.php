<?php

namespace pheasant\tests\query;

use \Pheasant;
use pheasant\query\Query;
use pheasant\query\Criteria;

require_once('autorun.php');
require_once(__DIR__.'/base.php');

class QueryTestCase extends \pheasant\tests\MysqlTestCase
{
	public function setUp()
	{
		$table = Pheasant::connection()->table('user');
		$table
			->integer('userid', 8, array('primary', 'auto_increment'))
			->string('firstname')
			->string('lastname')
			->create()
			;

		// create some users
		$table->insert(array('userid'=>null,'firstname'=>'Frank','lastname'=>'Castle'));
		$table->insert(array('userid'=>null,'firstname'=>'Cletus','lastname'=>'Kasady'));
	}

	public function testQuerying()
	{
		$query = new Query();
		$query
			->select('firstname')
			->from('user')
			->where('lastname=?','Castle')
			;

		$this->assertEqual(1, $query->count());
		$this->assertEqual(1, $query->execute()->count());
		$this->assertEqual(array('firstname'=>'Frank'), $query->execute()->offsetGet(0));
	}

	public function testJoins()
	{
		// outer query
		$query = new Query();
		$query
			->from('user')
			->innerJoin('mytable', 'using(tableid)')
			->where('userid=?',55)
			;

		$this->assertEqual('SELECT * FROM user '.
			'INNER JOIN mytable using(tableid) '.
			'WHERE userid=55',
			$query->toSql()
			);
	}

	public function testInnerJoinOnObjects()
	{
		// inner query
		$innerQuery = new Query();
		$innerQuery
			->select('groupname', 'groupid')
			->from('group')
			;

		// outer query
		$query = new Query();
		$query
			->select('firstname')
			->from('user')
			->innerJoin($innerQuery, 'USING(groupid)')
			->where('lastname=?','Castle')
			;

		$innerQuery
			->where('derived.firstname = ?', 'frank');

		$this->assertEqual('SELECT firstname FROM user '.
			'INNER JOIN (SELECT groupname, groupid FROM group) derived USING(groupid) '.
			'WHERE lastname=\'Castle\'',
			$query->toSql()
			);
	}
}

