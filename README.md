
# Kimai Mobile Setup Plugin

Kimai Mobile Setup Plugin provides an easy way to configure your workspace in the Kimai Mobile app. With this plugin installed, you'll gain access to a new "Kimai Mobile Setup" screen where you can generate an API token and view its corresponding QR code. Simply scan the QR code using the Kimai Mobile app to effortlessly set up your workspace.

## Installation

Download and extract the repo in `var/plugins/` (see [plugin docs](https://www.kimai.org/documentation/plugin-management.html)).

The file structure needs to look like this afterward:

```
var/plugins/
├── KimaiMobileSetupBundle
│   ├── KimaiMobileSetupBundle.php
|   └ ... more files and directories follow here ... 
```

Then rebuild the cache:
```bash
bin/console cache:clear
```

Please refer to the [Reloading cache docs](https://www.kimai.org/documentation/cache.html) for more information about how to reload the Kimai cache system, especially if you encounter the 500 error after the plugin installation.

