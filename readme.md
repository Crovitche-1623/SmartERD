# SmartERD
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Crovitche-1623/SmartERD/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Crovitche-1623/SmartERD/?branch=master)

SmartERD is a Symfony project designed to create Entity Relationship Diagrams. 
These are used to have a model of the database while ignoring the RDBMS used.

***Disclaimer: SmartERD is still in development mode.***

## Requirements
 * PHP 7.4.3 or higher;
 * PostgreSQL 10.11-1 or higher (The proper functioning of other RDBMS isn't 
   guaranteed);
 * PDO-PGSQL PHP Extension Enabled and all extensions specified in the 
   "composer.json" file;
 * Docker (if you want to run the project in development mode);
 * The usual [Symfony application requirements](https://symfony.com/doc/current/reference/requirements.html).
 
## Setup
 1. Create and run all services using Docker
    ```
    $   docker compose up -d
    ```
 2. Install dependencies with Composer
    ```
    $   docker compose exec php composer install -n
    ```
 3. Launch the setup command:
    ```
    $   php bin/console app:setup
    ```
 4. Open [the app](http://localhost:9000) in your favorite Web Browser.
 
## Tests
Execute these commands to run tests:
 1. Execute steps 1 & 2 from the [setup section](#Setup).
 2. Launch the setup command:
    ```
    $   php bin/console app:setup --env=test
    ```
 3. Run the tests:
    ```
    $   php vendor/bin/phpunit .
    ```