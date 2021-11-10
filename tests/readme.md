Most of the tests work with dama/doctrine-test-bundle which
create a transaction and rollback when the test is finished,
so the database stay in a known state for each test.
Feel free to update, delete or create new data but remember
the data will never be persisted outside the fixtures.
