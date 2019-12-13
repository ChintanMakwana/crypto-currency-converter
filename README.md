# Crypto Currency Converter

Crypto Currency Converter adds a shortcode to your WordPress site that shows a form to convert the crypto currency(BTC, ETH, XMR) to the specified currency(USD, GBP, etc).

## Files

This repository contains three files as follow:
 - crypto-currency-converter.php
 - cc-form.js
 - style.css

Please follow below simple steps to make things work.

### 1. Please download or clone it within your theme's /inc folder
Make the sure the folder name must be **/crypto-currency-converter**

### 2. Add the following code
Add the following code to your theme's **functions.php** file.

```php
require get_template_directory() . '/inc/crypto-currency-converter/crypto-currency-converter.php';
```

### 3. Create a new page in WordPress Admin area
Create a new page in WordPress Admin and put shortcode **[cc_form]** in page content editor now save it and visit that page.

If everything goes fine you can see a form with **From**, **To** and **Show change** fields.

**From**: Specify crypto currencies like BTC, ETH, XMR, etc. (use comma to separate if multiple)
**To**:  Specify the currency to what currency you want to convert. (use comma to separate if multiple)
**Show change**: Whether to show change or not.

## Feedback

Feel free to post your feedbacks or comments on [mchintan92@gmail.com](mailto:mchintan92@gmail.com).
