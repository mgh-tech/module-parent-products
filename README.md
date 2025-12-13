# Magento2 Admin ParentProducts
[![Latest Stable Version](http://poser.pugx.org/mgh-tech/module-parent-products/v)](https://packagist.org/packages/mgh-tech/module-parent-products) [![Total Downloads](http://poser.pugx.org/mgh-tech/module-parent-products/downloads)](https://packagist.org/packages/mgh-tech/module-parent-products) [![Latest Unstable Version](http://poser.pugx.org/mgh-tech/module-parent-products/v/unstable)](https://packagist.org/packages/mgh-tech/module-parent-products) [![License](http://poser.pugx.org/mgh-tech/module-parent-products/license)](https://packagist.org/packages/mgh-tech/module-parent-products) [![PHP Version Require](http://poser.pugx.org/mgh-tech/module-parent-products/require/php)](https://packagist.org/packages/mgh-tech/module-parent-products)

A Magento 2 admin module that displays a fieldset listing all parent products (Configurable, Grouped, Bundle) referencing the current product in the product edit form.

## Overview

**MGH_ParentProducts** enhances the Magento admin product edit experience by providing a clear, ACL-protected overview of parent product relationships. This is especially useful for catalog managers and merchandisers working with complex product structures.

## Problem & Solution

Ever found yourself lost in Magento's admin, desperately trying to figure out which configurable, grouped, or bundle products reference the simple product you're editing? 
Have you ever needed to navigate back to the product grid or, even worse, run a database query just to find a parent product? 

If so, this module is for you. 
Now you get a clear, instant overview of all parent products referencing the current product, right in the admin product edit form. 

No more wild goose chases.

**Before**
<img width="3698" height="1923" alt="Without parent product feature" src="https://github.com/user-attachments/assets/c3688db7-04f0-442e-80cc-ac86fd46dc20" />
**After**
<img width="3698" height="1923" alt="Parent products grouped/bundle" src="https://github.com/user-attachments/assets/dd3e2c8a-96a8-4aea-a4f7-8b9c7f225d43" />
<img width="3698" height="1923" alt="Parent product configurable" src="https://github.com/user-attachments/assets/8d26f6fd-367a-48cf-98f0-ec32da2c2788" />


### How It Works

When editing a product in the Magento admin:

1. The module injects a collapsible fieldset "Parent Products" into the product form (if enabled and permitted).
2. The fieldset displays a grid listing all parent products (ID, SKU, Name, Type, Relation, Edit link).
3. The grid is dynamically populated via a provider interface, allowing for extensibility.
4. The fieldset is hidden if the module is disabled or the admin user lacks ACL permission.

## Key Features

- **Seamless admin integration**: "Parent Products" grid on product edit form
- **Intelligent provider**: Extensible logic for parent product discovery
- **ACL protected**: Only visible to users with `MGH_ParentProducts::parent_products` permission
- **Configurable**: Enable/disable via system configuration
- **Zero impact when disabled**: No UI or performance overhead
- **Graceful fallback**: Parent name falls back to SKU if empty
- **Unit tested**: Ensures reliability and maintainability

## Installation

### Option 1: Via Composer (Recommended)

```bash
composer require mgh-tech/magento2-parent-products
bin/magento setup:upgrade
```

### Option 2: Manual Installation

1. Place the module under `app/code/MGH/ParentProducts` in your Magento 2 project:
   ```bash
   mkdir -p app/code/MGH/ParentProducts
   # Copy module files into this directory
   ```

2. Register the module by running setup upgrade:
   ```bash
   bin/magento setup:upgrade
   ```

3. Configure the module (see Configuration section below)

## Configuration

The module provides the following configuration options under **Stores > Configuration > MGH > Parent Products**:

| Setting | Type | Description |
|---------|------|-------------|
| **Enable Parent Products Fieldset** | Boolean | Toggle the module on/off. Only active in admin area. |

### Configuration via Admin Panel

1. Navigate to **Stores > Configuration > MGH > Parent Products**
2. Enable the module (Enabled by default)

### Configuration via Command Line

Alternatively, configure the module using the command line:

```bash
# Enable the module
bin/magento config:set mgh_parentproducts/general/enabled 1
```

## Requirements

- **PHP** >= 8.1
- **Magento** 2.4.x

## Compatibility

- ✅ Magento Open Source 2.4.x
- ✅ Adobe Commerce 2.4.x

## License

This module is licensed under the **MIT License**. See the [LICENSE.txt](LICENSE.txt) file for details.

## Author

**mgh-tech** - Magento 2 Development & Solutions  
GitHub: [github.com/mgh-tech](https://github.com/mgh-tech)

## Support & Contributions

For issues, questions, or contributions, please contact the author or your Magento integrator.
