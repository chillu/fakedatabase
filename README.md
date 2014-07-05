# Fake JSON Database

[![Build Status](https://api.travis-ci.org/chillu/fakedatabase.png)](https://travis-ci.org/chillu/fakedatabase)

## Introduction

Simple "database" storage adapter which persists into a JSON file, 
making it easy to see the whole database state at a glance.
It also carries all the benefits of "schema-less" storage,
and doesn't have external dependencies on database drivers.
This makes it useful for lightweight storage of test data,
e.g. when recording and tracking data changes in integration tests.

The storage is optimized for developer readability as opposed to 
performance, since the file is persisted on disk every time a change is made.

## Usage

```php
// Instanciation
$db = new FakeDatabase('/tmp/my_database.json');

// Set a new object with the key 'john' (or override an existing one)
$db->set('User', 'john', new FakeObject(array(
	'email' => 'john@test.com', 
	'firstname' => 'John', 
	'address' => array(
		'street' => 'Test Road',
		'city' => 'Testington',
		'postcode' => 9999
	)
)));

// Update (merges with existing data through array_merge())
$db->update('User', 'john', new FakeObject(array('surname' => 'Test')));

// Get all objects for a certain type
$objs = $db->getAll('User');

// Get by key
$obj = $db->get('User', 'john');

// Simple find
$obj = $db->find('User', 'email', 'john@test.com');

// Complex find by dot notation
$obj - $db->find('User', 'address.postcode', 9999);

// Reset the whole database
$db->reset('User', 'address.postcode', 9999);
```