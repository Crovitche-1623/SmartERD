# SmartERD
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Crovitche-1623/SmartERD/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Crovitche-1623/SmartERD/?branch=master)

SmartERD is a Symfony project designed to create Entity Relationship Diagrams. 
These are used to have a model of the database while ignoring the RDBMS used.

***Disclaimer: SmartERD is still in development mode.***

## Requirements
 * PHP 7.4.3 or higher;
 * PostgreSQL 10.11-1 or higher (The proper functioning of other RDBMS is not guaranteed);
 * PDO-PGSQL PHP Extension Enabled;
 * The [Symfony Binary](https://symfony.com/download) if you want to run the project in development mode;
 * and the usual [Symfony application requirements](https://symfony.com/doc/current/reference/requirements.html).
 
## Setup
 1. Get into the project directory:
    ```
    $   cd SmartERD/
    ```
 2. Modify the DATABASE_URL config in .env if necessary and start PostgreSQL.
 3. Launch the setup command:
    ```
    $   php bin/console app:setup
    ```
 4. Launch the Symfony local server:
    ```
    $   symfony serve --no-tls --no-ansi
    ``` 
 5. Open a browser and access the application with the given URL and enjoy !
 
## Tests
Execute theses commands to run tests:
A SQLite database is used for tests so we must configure it: 
 1. Delete the previous database if there is any:
    ```
    $   php bin/console d:d:d --if-exists --force --env=test
    ```
 2. Create a new one:
    ```
    $   php bin/console d:d:c --env=test
     ```
 3. Create the schema:
    ```
    $   php bin/console d:s:c --env=test
    ```
 4. Run the tests:
    ```
    $   php bin/phpunit
    ```