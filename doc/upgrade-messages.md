# Upgrade Messages: Editorial Guidance/FAQ

### Q: What is an upgrade "message"?

A message is an alert/status note displayed to a site administrator (*permission: `administer CiviCRM`*). It includes:

* `name` (symbolic identifier)
* `severity` (notice, warning, critical, etc)
* `title` (printable text, brief)
* `message` (printable text, longer, HTML)

Examples:
* http://latest.civicrm.org/stable.php?format=devPreview&versions=5.0.2
* http://latest.civicrm.org/stable.php?format=devPreview&versions=5.15.0

### Q: I want to change some text. Isn't the following text ("...") better than old text ("...")?

Maybe. Probably. However, if you are only talking about one example text, then expect skepticism - text that reads well from one perspective may not read well from another perspective.

You should start by considering how messages should look *in several different versions*. The following URL is useful for comparing messages in different use-cases:

http://latest.civicrm.org/stable.php?format=devPreview&versions=5.16.0,5.15.0,5.13.6,5.0.0

### Q: What upgrade/maintenance strategy is used by CiviCRM admins?

There is no single upgrade strategy. In Civi's open distribution system, authority over upgrade/maintenance is widely diffuse. There are, in fact, several different ways in which people may decide on upgrading.

* "Upgrade frequently and in small increments"
* "Upgrade infrequently and in large increments"
* "Upgrade MAJOR.MINOR every 6 months; in between, only update MAJOR.MINOR.PATCH"
* "Never upgrade."
* "Only upgrade when there's a security release, and go for the newest secure version at the time"
* "Only upgrade when there's a security release, and go for the oldest secure version at the time"
* "Review each changelog and decide if there's anything interesting enough to merit an upgrade"

These are just examples - and somewhat idealized at that. You may, e.g., have a philosophical argument which leads to one blanket rule or another - and then (when it comes time to do something real) discover an exception or detail that hadn't been handled well in that plan.

In short - there is no single upgrade strategy.

### Q: How are upgrade messages composed?

Each CiviCRM site submits a request to the `civicrm-pingback` service with its current version. The service compares with the list of published versions, picks a message-template, and sends back some message(s).

The response may send *up to* two messages. The two messages are orthogonal. Here's the general gist (paraphrasing) of each message:

* The `patch` message says "You have 5.13.2, but 5.13.6 is newer."
* The `upgrade` message says "You have 5.10, but 5.16 is newer."

These messages are distinct because the calculus (cost/benefit; risk/reward; technical-debt; etc) tends to be different depending on what versions are changing. Also, each piece of information will be used differently depending on the site admin's upgrade/maintenance strategy.

In both cases, the message content should draw more attention to facts than to judgments.

* *Example*: It is a *fact* that version X.Y.Z was released on date Y-M-D with changes for A, B, and C. It is *fact* that 5.9 is newer than 5.8. It is a fact that downloads are available at https://example.com. It is a fact that team X has an active support plan for version Y.
* *Example*: It is a *judgment* that every site ought to be upgraded on a incremental/continuous basis. It is a *judgment* that every site ought to peg to an "X.Y" version for 12 months.
* *Exception*: The following judgments are endorsed: It is preferrable to run a version *without any known security vulnerabilities*. It is preferrable to run a version *with active security support*.

### Q: Why do the upgrade messages show a list of versions?

The list can be verbose, and many readers aren't going to think through every detail in there. So why show it?

For me, it's based in a critique that Edward Tufte makes repeatedly about information-display: to give the reader an intuitive understanding of the information, you should present it with a *meaningful scale*.

If a typical reader looks at the text "5.10=>5.11" and the text "5.10=>5.16", they cannot intuit much. Visually, those look the same. To get some meaning from them, you need to understand what each increment means `$X` change, and then do a mental adjustment to determine how big `$X * (11-10)` or `$X * (16-10)`. Of course, it doesn't help that amount of incremental change `$X` varies project-to-project.

The list tries to give the reader better intuition about the scale of changes. It equates "1 version == 1 month == 1 row". If I see 3 rows, then that means my upgrade will have to address 3 months worth of change. Then I ask myself, "Do I want to upgrade now, or should I wait another 3 months?" I can reason about that question more accurately/intuitively because the visual elements correspond to the amount of change, and there are several signals (the month numbers; the length of the release notes) which can reinforce this sense of scale.
