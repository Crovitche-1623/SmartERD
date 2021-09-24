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
 4. Run `docker compose exec php bin/console app:setup` to set up the app (do not use this command in production)
 5. Open [the app](http://localhost:9000) in your favorite Web Browser.
 
## Tests
Execute these commands to run tests:
 1. Execute steps 0 to 2 from the [setup section](#Setup).
 2. Run `docker compose exec php make tests` to set up the app in test environment

## Known problems when developing
When working on Windows, WSL2 is necessary for SmartERD to be fast enough (go 
from ~4 seconds for an api call to 40ms). Sometimes there are some problems:
```
The command 'docker' could not be found in this WSL 2 distro.
We recommand to activate the WSL integration in Docker Desktop settings.

See https://docs.docker.com/desktop/windows/wsl/ for details.
```
Make sure your distro is set as default and use the version 2 of WSL. You can
see this using the 
`wsl --list --verbose` command.  
  
If the distro isn't default one, run this command:  
`wsl --set-default <distro-name>` (for example Ubuntu-20.04)  
  
If the distro isn't on version 2 of WSL:  
`wsl --set-version <distro-name> 2`

Then be sure that Docker is configured this way:
![docker configuration](https://i.stack.imgur.com/2FO7x.png)

Then finally restart Docker from the taskbar using a right click.
