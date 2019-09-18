# Pipit
Pipit is a Perch app. It comes with a collection of helper functions.

## Installation
* Download the latest version of the app.
* Unzip the download
* Place the `pipit` folder in `perch/addons/apps`
* Add `pipit` to your `perch/config/apps.php`


## Requirements
* Perch or Perch Runway 3.0 or higher
* PHP 7+


## Functions

### pipit_get()

`pipit_get()` is a utility function similar to [perch_get()](https://docs.grabaperch.com/functions/utilities/perch-get/). The main difference is `pipit_get()` can handle arrays.

Given the URL `/?country%5B%5D=brazil&country%5B%5D=australia` you can get an array of the country values using:

```php
$countries = pipit_get('country');
```

Given the URL `/?country=brazil`, the above will get you a string like `perch_get()` does. You can have this returned as an array by setting the second parameter to `true`:

```php
$countries = pipit_get('country', true);
```


Given the URL `/` (no `country` parameter set), you can specify a default value in the third parameter:

```php
$countries = pipit_get('country', false, 'Italy');
```




### pipit_r()

Outputs a formmated `print_r()`. This is just for convenience if you want to use it for debugging.

p.s. you can use `PerchUtil::debug()` to output to the debug messages at the bottom of the page. See [Printing Debug Messages](https://grabapipit.com/blog/printing-debug-messages)




### pipit_perch_user_logged_in()

Check if a the site visitor is logged into the site's Perch control panel. Note there is no public API to perform this. So use this at your own risk.




### pipit_template()

Renders a template at run time similar to `perch_template()`. 

`pipit_template()` allows you to set your own template name space and to paginate your items.

```php
pipit_template($template, $data, $opts, $return);
```

| Type       | Description                                                    |
|------------|----------------------------------------------------------------|
| String     | Template path                                                  |
| Array      | Data array                                                     |
| Array      | Options array, see below                                       |
| Boolean    | Set to `true` to have the templated html returned instead of echoed.    |


Options array:

| Option     | Description                                                    |
|------------|----------------------------------------------------------------|
| namespace  | Template name space. Default `content`                         |
| count      | The number of items to display.                                |
| paginate   | Boolean. Set to `true` to use pagination                       |
| page-links | Create numbered page links for pagination.                     |


```php
pipit_template('render/list.html', $data, [
    'count' => 6,
    'paginate' => true,
]);
```




### pipit_version()

Adds the last modified time of a file for easier browser cache busting.

To add the last modified time as a query string (default):

```php
<script src="<?php echo pipit_version('/js/app.js'); ?>"></script>
```

Output:

```php
<script src="/js/app.js?v=1511342726"></script>
```

To add the last modified time as part of the file's name you need to set the second argument to `name`:

```php
<script src="<?php echo pipit_version('/js/app.js', 'name'); ?>"></script>
```

Output:

```php
<script src="/js/app.1511342726.js"></script>
```

And you'll need to add the following .htaccess rules:

```
RewriteRule ^(.+)\.(\d+)\.(js|css)$ $1.$3 [L]
```



### pipit_get_lang()

The function gets the language string from a URL with the patter `/{lang}/my-page`.

```php
pipit_get_lang($accepted_langs, $default_lang)
```


| Type       | Description                                                    |
|------------|----------------------------------------------------------------|
| Array      | Array of accepted language strings                             |
| String     | Default language if none found                                 |


```php
$lang = pipit_get_lang(['en', 'ru', 'ar'], 'en');
```







## Category Functions

### pipit_category_set()

Outputs a Category Set.

```php
pipit_category_set($slug, $opts, $return);
```

| Type       | Description                                                    |
|------------|----------------------------------------------------------------|
| String     | Category Set slug                                              |
| Array      | Options array, see below                                       |
| Boolean    | Set to `true` to have the templated html returned instead of echoed.    |


Options array:

| Option     | Description                                                    |
|------------|----------------------------------------------------------------|
| template   | Template path. Default `categories/set.html`                   |
| skip-template  | True or false. Bypass template processing and return the content in an associative array. |
| return-html    | True or false. For use with `skip-template`. Adds the HTML onto the end of the returned array with key `html`. |



```php
pipit_category_set('products');
```

```php
$set_html = pipit_category_set('products', [], true);
echo $set_html;
```

```php
$set = pipit_category_set('products', [
    'skip-template' => true,
    'return-html' => true,
]);

echo $set['html'];
```




### pipit_category_get_path()

`pipit_category_get_path()` takes the returned category source (typically when you use the `skip-template` option) and returns the category path.

```php
pipit_category_get_path($source);
```

When you have a categories field in a template (e.g. Collection item), Perch in some cases stores categories by their IDs and in other cases by their category paths. This makes it inconvenient when you skip templating and try to use the categories values. 

```html
<perch:content id="name" type="text" label="Product Name">
<perch:content id="slug" type="slug" for="name">
<perch:categories id="categories" set="products" label="Categories" />
```

```php
    $products = perch_collection('Products', [
        'skip-template' => true,
        'filter' => 'slug',
        'value' => 'my-product',
    ]);

    foreach($products as $product) {
        foreach($product['categories'] as $category) {
            // $category can be a path e.g. products/shoes/
            // or it can be an ID e.g. 34
        }
    }
```

A lot of the time you need the category path instead of the ID because [category filtering](https://docs.grabaperch.com/perch/categories/filtering/) requires paths. A common use-case is using the category paths for outputting similar items on an item's detail page.


```php
    $products = perch_collection('Products', [
        'skip-template' => true,
        'filter' => 'slug',
        'value' => 'my-product',
    ]);

    $categories = array();
    foreach($products as $product) {
        foreach($product['categories'] as $category) {
            $categories[] = pipit_category_get_path($category);
        }
    }

    // similar products
    perch_collection('Products', [
        'category' => $categories,
        'filter' => 'slug',
        'match' => 'neq',
        'value' => 'my-product',
    ]);
```


The function only attempts to get a category path if `$source` is numerical. If `$source` is a string, the function will return the string without checking whether the string is in fact a category path. So it is your responsibility to use the function in the right context.





### pipit_category_get_id()

`pipit_category_get_id()` takes the returned category source (typically when you use the `skip-template` option) and returns the category ID. The function is similar to `pipit_category_get_path()`, but returns the category ID.

Perch in some cases stores categories by their IDs and in other cases by their category paths. A common use-case for needing the category ID instead of the path is for rendering templates with `perch_template()`.


```php
    $product = perch_collection('Products', [
        'template' => 'products/detail.html',
        'skip-template' => true,
        'return-html' => true,
        'filter' => 'slug',
        'value' => 'my-product',
    ]);


    if(isset($product[0])) {
        array_walk($product[0]['categories'], function(&$category) {
            $category = pipit_category_get_id($category);
        })

        // output rendered content/products/detail.html
        echo $product['html'];

        // render another template using the same product data without making another database query
        perch_template('content/products/another_template.html', $product[0]);
    }
    
```