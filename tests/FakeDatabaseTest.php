<?php

namespace Chillu\FakeDatabase\Tests;

use Exception;
use \LogicException;
use Chillu\FakeDatabase\FakeDatabase;
use Chillu\FakeDatabase\FakeObject;
use Chillu\FakeDatabase\Tests\Fixtures\FakeDBTestObject;
use Chillu\FakeDatabase\Tests\Fixtures\FakeDBTestOtherObject;
use PHPUnit\Framework\TestCase;

class FakeDatabaseTest extends TestCase
{

    /** @var FakeDatabase */
    protected $db;

    /** @var string */
    protected $path;

    protected function setUp(): void
    {
        $path = sys_get_temp_dir() . '/FakeDatabaseTest_' . uniqid() . '.json';
        touch($path);

        $this->path = $path;
        $this->db = new FakeDatabase($path);
    }

    protected function tearDown(): void
    {
        $this->db->reset();
    }

    public function testGetSet()
    {
        $objIn = new FakeDBTestObject(array('foo' => 'bar'));
        $this->db->set('FakeDBTestObject', 'id1', $objIn);
        $objOut = $this->db->get('FakeDBTestObject', 'id1');
        $this->assertInstanceOf(FakeDBTestObject::class, $objOut);
        $this->assertEquals($objOut->foo, 'bar');
    }

    public function testUpdate()
    {
        $objIn = new FakeDBTestObject(array(
            'foo' => 'bar',
            'nested' => array(
                'bar' => 'baz'
            )
        ));
        $this->db->set('FakeDatabaseTest_Object', 'id1', $objIn);

        $objIn = new FakeDBTestObject(array(
            'foo' => 'newbar',
            'nested' => array(
                'bar' => null,
                'new' => 'newval'
            )
        ));
        $this->db->update('FakeDatabaseTest_Object', 'id1', $objIn);

        $objOut = $this->db->get('FakeDatabaseTest_Object', 'id1');
        $this->assertInstanceOf(FakeDBTestObject::class, $objOut);
        $this->assertEquals($objOut->foo, 'newbar', 'Updates existing toplevel values');
        $this->assertEquals($objOut->nested->bar, null, 'Nulls nested values');
        $this->assertEquals($objOut->nested->new, 'newval', 'Inserts new nested values');

        $this->db->update('TypeThatDoesNotExist', 'DummyKey', $fakeObj = new FakeObject());
        $this->assertEquals($fakeObj, $this->db->get('TypeThatDoesNotExist', 'DummyKey'));
    }

    public function testReset()
    {
        $dbPath = $this->db->getPath();
        $objIn = new FakeDBTestObject(array('foo' => 'bar'));
        $this->db->set('FakeDatabaseTest_Object', 'id1', $objIn);
        $this->db->reset();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('FakeDatabase at %s no longer exists', $dbPath));
        $this->db->get('FakeDatabaseTest_Object', 'id1'); // Should throw the exception
    }

    public function testToArray()
    {
        $expected = [
            'obj' => [
                'obj1' => [
                    '_type' => FakeDBTestObject::class,
                    'foo' => 'bar',
                    '_key' => 'obj1'
                ]
            ],
            'otherobj' => [
                'obj2' => [
                    '_type' => FakeDBTestOtherObject::class,
                    'foo' => 'bar',
                    '_key' => 'obj2'
                ]
            ]
        ];

        $this->db->set('obj', 'obj1', new FakeDBTestObject(array('foo' => 'bar')));
        $this->db->set('otherobj', 'obj2', new FakeDBTestOtherObject(array('foo' => 'bar')));
        $this->assertEquals($expected, $this->db->toArray());
        $this->assertEquals([0 => $this->db->get('obj', 'obj1')], $this->db->getAll('obj'));
    }

    public function testGetPath()
    {
        $this->assertEquals($this->path, $this->db->getPath());
        $this->assertFileExists($this->path);
    }

    public function testFindWithoutType()
    {
        $this->assertSame(false, $this->db->find('InvalidType', 'key', 'value'));
    }

    public function testFindNested()
    {
        $this->db->set('mytype', 'obj1', new FakeDBTestObject(array(
            'array' => array('foo' => 'bar', 'bar' => 'baz')
        )));
        $this->db->set('mytype', 'obj2', new FakeDBTestOtherObject(array(
            'array' => array('foo' => 'not matching', 'bar' => 'not matching')
        )));
        $match = $this->db->find('mytype', 'array.foo', 'bar');
        $this->assertInstanceOf(FakeObject::class, $match);
        $this->assertEquals(
            'bar',
            $match->array['foo']
        );

        $this->assertEquals(null, $this->db->find('mytype', 'unknownpart.foo', 'bar'));
    }

    public function testGetDataIsntReadable()
    {
        $this->expectException(LogicException::class);
        chmod($this->db->getPath(), '200');
        $data = $this->db->get('DummyType', 'DummyKey');
    }

    public function testGetAllWithNoKey()
    {
        $this->assertSame(false, $this->db->getAll('InvalidType'));
    }
}
