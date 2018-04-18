civicrm-pingback
================

This repository contains the files used for the https://latest.civicrm.org website. This is used by CiviCRM core to check if any updates are needed.

As part of this update check (ie. pingback), a number of anonymous information is collected on the CiviCRM instance (see https://github.com/civicrm/civicrm-core/blob/master/CRM/Utils/VersionCheck.php). This information is stored in the stats database, and used notably for the CiviCRM statistics (see https://stats.civicrm.org). 

Requirements:
- a recent version of PHP with the mysqli extension enabled
- a copy of the GeoLite2 Country database, available at http://dev.maxmind.com/geoip/geoip2/geolite2/, stored at: /usr/share/GeoIP/GeoLite2-Country.mmdb
- a script to regularely update the GeoIP database - such as https://github.com/maxmind/geoipupdate or a much simpler shell script

### CLI: Listing releases

```
./bin/pb release:list
```

### CLI: Adding a new release

```
### Add a new security release
./bin/pb release:add 4.7.99 --date=2017-01-01 --security=true

## Add a new non-security release
./bin/pb release:add 4.7.99 --date=2017-01-01 --security=false

## Add a new release. (Default: Non-security, today's date)
./bin/pb release:add 4.7.99
```

## Development: Testing

Tests are implemented in PHPUnit. To run all tests, simply call phpunit without any arguments:

```
phpunit4
```

## Development: Working with the summary report

The summary report produces a list of messages advising admins about available upgrades.  A typical request might be
for `http://localhost/stable.php?hash=abcd1234&format=summary&version=5.0.alpha1&...` and it returns a JSON document for
one site. Ex:

```json
{
   "patch" : {
      "name" : "patch",
      "severity" : "warning",
      "title" : "CiviCRM Patch Available"
      "message" : "<p>The site is running 5.0.alpha1. Additional patches are available:</p><ul>\n<li>5.0.0 (2018-04-04)</li></ul>",
   }
}
```

However, during development, you're not just concerned with one version (`5.0.alpha1`) -- you want to check how the
advice is displayed for many versions (`4.7.31,4.7.29,4.6.36,5.0.0,...`).  To preview all these, use a web browser to
navigate to `http://localhost/stable.php?format=devPreview`. This page is handy for quickly iterating on messages.

There is also PHPUnit test coverage for the summary report in `SummaryReportTest` and `DevPreviewReportTest`.

One of the tests, `DevPreviewReportTest::testExampleFile()`, has a special workflow.  Its purpose is to monitor changes
in the HTML output across a series of example version-numbers.  To do this, we store a copy of the `devPreview` and see
if the HTML has changed.

After making any changes to the HTML messages/markup in `SummaryReport` or `DevPreviewReport`, you can run `phpunit4`.
It will highlight the HTML changes.  If the changes look good, then update the example file, e.g.

```
curl 'http://localhost/stable.php?format=devPreview&versionsFile=ex1.json&versions=5.0.0,5.0.beta1,4.7.31,4.7.29,4.6.36,4.6.32,4.5.10' > tests/Report/DevPreview-ex1-a.html
```
