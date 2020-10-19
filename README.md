![version](https://img.shields.io/badge/StrongMonkey-v0.0.3--beta-green.svg)
![license](https://img.shields.io/github/license/GramThanos/StrongMonkey.svg)
<!--![downloads](https://img.shields.io/github/downloads/GramThanos/StrongMonkey/total.svg)
![downloads](https://img.shields.io/github/downloads/GramThanos/StrongMonkey/v0.0.3-beta/total.svg)-->

# StrongMonkey

A PHP library for interacting with the [StrongKey FIDO2](https://github.com/StrongKey/fido2) Server

![strongmonkey-banner](strongmonkey-banner.png)

*The strong monkey that bullies and steals USB tokens from the strong octopus*

• [StrongMonkey Library](StrongMonkey.php) • [StrongMonkey API](docs/library_api.md) • [Example Application Setup Guide](docs/setup_guide.md) •

---
## Example usage

Download the [StrongMonkey](StrongMonkey.php) library and make a simple PING request to your StrongKey FIDO2 server.

```php
// Include the library
include('StrongMonkey.php');
// Specify the FIDO server's URL and the authentication method to be used
$monkey = new StrongMonkey('https://localhost:8080', 1, 'REST', 'HMAC', '162a5684336fa6e7', '7edd81de1baab6ebcc76ebe3e38f41f4');
// Send a ping request to the server
$result = $monkey->ping();
// If there is an error print it
if ($error = $monkey->getError($result)) {
	die($error . "\n");
}
// Print the ping results
die($result);
```

---
## About
This library was developed by [GramThanos](https://www.linkedin.com/in/gramthanos/) in collaboration with the [Systems Security Laboratory](https://ssl.ds.unipi.gr/) at Department of Digital Systems at [University of Piraeus](https://www.unipi.gr/).

---
## License
This project is under The GNU LGPLv2.1 license.

Copyright (c) 2020 Grammatopoulos Athanasios-Vasileios
