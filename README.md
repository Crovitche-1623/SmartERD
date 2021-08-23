# SmartERD
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Crovitche-1623/SmartERD/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Crovitche-1623/SmartERD/?branch=master)

SmartERD is a Symfony project designed to create Entity Relationship Diagrams. 
These are used to have a model of the database while ignoring the RDBMS used.

***Disclaimer: SmartERD is still in development mode.***

## Setup
 0. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
 1. Run `docker compose build --pull --no-cache` to build fresh images
 2. Run `docker compose up -d` to run the services
 3. Run `docker compose exec php composer install -n` to install Composer dependencies
 4. Run `docker compose exec php bin/console app:setup` to set up the app
 5. Open [the app](http://localhost:9000) in your favorite Web Browser.
 
## Tests
Execute these commands to run tests:
 1. Execute steps 0 to 3 from the [setup section](#Setup).
 2. Run `docker compose exec php bin/console app:setup --env=test` to set up the app in test environment
 3. Run `docker compose exec php vendor/bin/phpunit --testdox`
