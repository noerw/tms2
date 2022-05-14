# The Soldat Mapping Showcase

## What is this?

Around 2007-2011, at the height of the [Soldat1](https://soldat.pl) community, we needed a website for uploading and sharing maps. I stepped up and created a mapping community website, called [The Soldat Mapping Showcase (TMS)](https://tms.jrgp.org).

Recently, Soldat2 needed a similar site, and the community asked me to make a copy of TMS for it.

So, I am open sourcing this by community request. We have an instance running at https://tms2.jrgp.org intended for Soldat2.

Be aware: this is written in 2006-style PHP, in that it does not use MVC or prepared statements or have unit tests. That said, the code works as expected, best practices of the time were followed, and I am not aware of any vulnerabilities, XSS/sql-injection/remote-inclusion or otherwise.

## Built-in features

- map uploading, including screenshots
- map searching
- map rating + viewing
- polls
- shoutbox
- auth via Discord's oauth2
- user profiles
- thumbnail generation
- multiple UI themes, based on Soldat community sites from mid 2000s, pickable via user
- light, fast, server-rendered UI
- IE6 compatibility

## Things which would be good to change (outside of a full rewrite)

- Modify CSS to be mobile friendly 
- Migrate from legacy mysql plugin (we use a shim to get compat with php7+) to PDO

## Runs on

- MySQL 5+
- PHP >= 7.2+, verified with PHP 7.4
- Apache (verified using `mod_php` with `mod_ruid2`)
- nginx + php-fpm would also work

## Dev install:

- Create vhost pointed at $gitcheckout/webroot
- Copy config.sample.php to config.php and fill out mysql settings
- Import db from schema.sql 
