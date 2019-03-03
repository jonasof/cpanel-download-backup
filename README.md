# Download Full Backup script for Cpanel

Is a simple script to download the last full backup from a cpanel server.

## Instructions

```
copy config.example.php config.php

edit config.php

composer install

php index.php
```

You then configure a cron job to `php index.php`

The email notify function uses the mail() function, which needs to be configured
in your server. You can use ssmtp to send emails through a SMTP server.

