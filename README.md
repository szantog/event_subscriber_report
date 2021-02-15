Add to you composer.json:
```
{
    "type": "package",
    "package": {
        "name": "szantog/event_subscriber_report",
        "version": "dev-1.x",
        "source": {
            "url": "git@github.com:szantog/event_subscriber_report.git",
            "type": "git",
            "reference": "1.x"
        },
        "type": "drupal-module"
    }
}
```
Then:
`composer install szantog/event_subscriber_report`
