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