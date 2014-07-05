<?php
class FakeDatabaseTest extends PHPUnit_Framework_TestCase {

	/** @var FakeDatabase */
	protected $db;

	protected function setUp() {
		$path = sys_get_temp_dir() . '/FakeDatabaseTest_' . uniqid() . '.json';
		touch($path);
		$this->db = new FakeDatabase($path);
	}

	protected function tearDown() {
		$this->db->reset();
	}

	public function testGetSet() {
		$objIn = new FakeDatabaseTest_Object(array('foo' => 'bar'));
		$this->db->set('FakeDatabaseTest_Object', 'id1', $objIn);
		$objOut = $this->db->get('FakeDatabaseTest_Object', 'id1');
		$this->assertInstanceOf('FakeDatabaseTest_Object', $objOut);
		$this->assertEquals($objOut->foo, 'bar');
	}

	public function testUpdate() {
		$objIn = new FakeDatabaseTest_Object(array(
			'foo' => 'bar',
			'nested' => array(
				'bar' => 'baz'
			)
		));
		$this->db->set('FakeDatabaseTest_Object', 'id1', $objIn);

		$objIn = new FakeDatabaseTest_Object(array(
			'foo' => 'newbar',
			'nested' => array(
				'bar' => null,
				'new' => 'newval'
			)
		));
		$this->db->update('FakeDatabaseTest_Object', 'id1', $objIn);
		
		$objOut = $this->db->get('FakeDatabaseTest_Object', 'id1');
		$this->assertInstanceOf('FakeDatabaseTest_Object', $objOut);
		$this->assertEquals($objOut->foo, 'newbar', 'Updates existing toplevel values');
		$this->assertEquals($objOut->nested->bar, null, 'Nulls nested values');
		$this->assertEquals($objOut->nested->new, 'newval', 'Inserts new nested values');
	}

	public function testReset() {
		$objIn = new FakeDatabaseTest_Object(array('foo' => 'bar'));
		$this->db->set('FakeDatabaseTest_Object', 'id1', $objIn);
		$this->db->reset();
		$this->assertNull($this->db->get('FakeDatabaseTest_Object', 'id1'));
	}

	public function testToArray() {
		$this->db->set('obj', 'obj1', new FakeDatabaseTest_Object(array('foo' => 'bar')));
		$this->db->set('otherobj', 'obj2', new FakeDatabaseTest_OtherObject(array('foo' => 'bar')));
		$this->assertEquals(
			array(
				'obj' => array(
					'obj1' => array(
						'_type' => 'FakeDatabaseTest_Object',
						'foo' => 'bar',
						'_key' => 'obj1'
					)
				),
				'otherobj' => array(
					'obj2' => array(
						'_type' => 'FakeDatabaseTest_OtherObject',
						'foo' => 'bar',
						'_key' => 'obj2'
					)
				)
			),
			$this->db->toArray()
		);
	}

	public function testFindNested() {
		$this->db->set('mytype', 'obj1', new FakeDatabaseTest_Object(array(
			'array' => array('foo' => 'bar', 'bar' => 'baz')
		)));
		$this->db->set('mytype', 'obj2', new FakeDatabaseTest_OtherObject(array(
			'array' => array('foo' => 'not matching', 'bar' => 'not matching')
		)));
		$match = $this->db->find('mytype', 'array.foo', 'bar');
		$this->assertInstanceOf('FakeObject', $match);
		$this->assertEquals(
			'bar',
			$match->array['foo']
		);
	}

}

class FakeDatabaseTest_Object extends FakeObject {}
class FakeDatabaseTest_OtherObject extends FakeObject {}