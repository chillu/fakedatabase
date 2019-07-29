<?php

namespace Chillu\FakeDatabase\Tests;

use Chillu\FakeDatabase\FakeObject;
use Chillu\FakeDatabase\Tests\Fixtures\FakeDBTestObject;
use Chillu\FakeDatabase\Tests\Fixtures\FakeObjectTestWithDefaults;
use PHPUnit\Framework\TestCase;

class FakeObjectTest extends TestCase
{

    public function testDefaults()
    {
        $obj = new FakeObjectTestWithDefaults();
        $this->assertEquals($obj->foo, 'default');

        $objWithOverride = new FakeObjectTestWithDefaults(array('foo' => 'bar'));
        $this->assertEquals($objWithOverride->foo, 'bar');
    }

    public function testToArray()
    {
        $obj = new FakeDBTestObject(array(
            'foo' => 'bar',
            'relation' => array(new FakeDBTestObject(array('bar' => 'baz')))
        ));

        $this->assertEquals(
            $obj->toArray(),
            array(
                'foo' => 'bar',
                'relation' => array(
                    array(
                        'bar' => 'baz',
                        '_type' => FakeDBTestObject::class
                    )
                ),
                '_type' => FakeDBTestObject::class
            )
        );
    }

    public function testFromArray()
    {
        $arr = array(
            '_type' => FakeDBTestObject::class,
            'foo' => 'bar',
            'hasOne' => array(
                '_type' => FakeDBTestObject::class,
                'hasOneProp' => 'baz',
            ),
            'hasMany' => array(
                array(
                    '_type' => FakeDBTestObject::class,
                    'hasManyProp' => 'baz',
                )
            ),
            'bar' => 'baz'
        );
        $obj = FakeObject::createFromArray($arr);
        $this->assertNotNull($obj);
        $this->assertInstanceOf(FakeDBTestObject::class, $obj);
        $this->assertInstanceOf(FakeDBTestObject::class, $obj->hasOne);
        $this->assertInstanceOf(FakeDBTestObject::class, $obj->hasMany[0]);
        $this->assertEquals($obj->bar, 'baz');
        $this->assertEquals('baz', $obj->hasOne->hasOneProp);
        $this->assertEquals('baz', $obj->hasMany[0]->hasManyProp);
    }
}
