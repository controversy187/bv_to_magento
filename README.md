#BV to Magento Migration
This is a series of scripts that will be run to migrate from a BV Commerce store to a Magento store.

##Prerequisties
It is assumed that the BV data has been convereted from MSSQL to MySQL (see MySQL Workbench for help with that) and that you have a SOAP API access to the Magento installation.
MySQL requests are made using PHP PDO (so you can probably use the MSSQL database from BV directly, if you have access to it).

##Usage
Copy the config.php.sample to config.php and update the information for BV database connections and your Magento SOAP API. Run migrate.php through a webserver and follow the items in order.

##Thanks
I am using Jim Myhrberg's CSV parsing class, located at https://code.google.com/p/parsecsv-for-php/?