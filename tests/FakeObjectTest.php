<?php
class FakeObjectTest extends PHPUnit_Framework_TestCase {

	public function testDefaults() {
		$obj = new FakeObjectTest_ObjectWithDefault();
		$this->assertEquals($obj->foo, 'default');

		$objWithOverride = new FakeObjectTest_ObjectWithDefault(array('foo' => 'bar'));
		$this->assertEquals($objWithOverride->foo, 'bar');
	}

	public function testToArray() {
		$obj = new FakeObjectTest_Object(array(
			'foo' => 'bar',
			'relation' => array(new FakeObjectTest_Object(array('bar' => 'baz')))
		));

		$this->assertEquals(
			$obj->toArray(),
			array(
				'foo' => 'bar',
				'relation' => array(
					array(
						'bar' => 'baz',
						'_type' => 'FakeObjectTest_Object'
					)
				),
				'_type' => 'FakeObjectTest_Object'
			)
		);
	}

	public function testFromArray() {
		$arr = array(
			'_type' => 'FakeObjectTest_Object',
			'foo' => 'bar',
			'hasOne' => array(
				'_type' => 'FakeObjectTest_Object',
				'hasOneProp' => 'baz',
			),
			'hasMany' => array(
				array(
					'_type' => 'FakeObjectTest_Object',
					'hasManyProp' => 'baz',
				)
			),
			'bar' => 'baz'
		);
		$obj = FakeObject::create_from_array($arr);
		$this->assertNotNull($obj);
		$this->assertInstanceOf('FakeObjectTest_Object', $obj);
		$this->assertInstanceOf('FakeObjectTest_Object', $obj->hasOne);
		$this->assertInstanceOf('FakeObjectTest_Object', $obj->hasMany[0]);
		$this->assertEquals($obj->bar, 'baz');
		$this->assertEquals('baz', $obj->hasOne->hasOneProp);
		$this->assertEquals('baz', $obj->hasMany[0]->hasManyProp);
	}

}

class FakeObjectTest_Object extends FakeObject {
}

class FakeObjectTest_ObjectWithDefault extends FakeObject {
	public function getDefaults() {
		return array('foo' => 'default');
	}
}