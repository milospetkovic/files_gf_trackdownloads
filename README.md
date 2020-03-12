# Files In Group Folder Track Downloads
Place this app in **nextcloud/apps/**

## Building the app

The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web
* npm: for building everything JS, only required if a package.json exists

The make command will install or update Composer dependencies if a composer.json is present and also **npm run build** if a package.json is present.