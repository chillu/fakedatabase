<?php

namespace Chillu\FakeDatabase\Tests\Fixtures;

use Chillu\FakeDatabase\FakeObject;

class FakeObjectTestWithDefaults extends FakeObject
{
    public function getDefaults()
    {
        return array('foo' => 'default');
    }
}
