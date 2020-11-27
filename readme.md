# Extension Extractor Opencart V2.x or 3.X
---
[![Minimum Opencart Version](https://img.shields.io/badge/Opencart-%3E%3D%203.X-green)](https://www.opencart.com/index.php?route=common/home)
[![Minimum Opencart Version](https://img.shields.io/badge/Donate-Buy%20me%20a%20coffee%2C%20Thanks!!-orange)](https://www.buymeacoffee.com/davidev)
---
### Installation
```text
- This is a plug and play module;
- Install file .ocmod.zip on Extensions/Installer; 
- Reload on Extensions/Modifications;
- PHP >= 5.6.
```
### Fully Automate extension extractor .ocmod
This module is used to extract all the extensions present in opencart. Create a folder in the storage directory with all extensions inside. Very convenient for all developers who use this framework. The module creates the following files:
```text
- File {{name of zip file}}.ocmod.zip file;
- Install.xml for mod core;
- Updalod folder if is present modification file on database;
- Note_mod.txt with the detail of the module.
```

### How to contribute
Fork the repository, edit and submit a pull request. Please be very clear on your commit messages and pull request, empty pull request messages may be rejected without reason. Your code standards should match the OpenCart coding standards. We use an automated code scanner to check for most basic mistakes - if the test fails your pull request will be rejected.
