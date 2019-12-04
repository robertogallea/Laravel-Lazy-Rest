# Laravel-Lazy-Rest

This package provides full loading of RESTful paginated resources using Laravel `LazyCollection` capabilities.
In this way, no complicate mechanisms for handling pagination is required because the package manages all the complexity
for you, if required.

```php
$paginatedEndpoint = 'http://api.with-15-elements-per-page';

$collection = \LazyRest::load($paginatedEndpoint);

// Loads only first page
dump($collection->skip(10)->first());
 
// loads all the pages
dump($collection->last());
```

## 1. Configuration
By default, the package is configured to search for `data` field in the json response and `next_page_url` to detect how 
to fetch the next page of results. However you could easily change this behavior by overriding default configuration:

1. Publish config:
`php artisan vendor:publish --provider="robertogallea\LaravelLazyRest\LazyRestServiceProvider" --tag=config`

2. Edit `config/lazy-rest.php`
```php
'fields' => [
    'next_page_url' => 'next_page_url',
    'data' => 'data',

    'timeout' => 5.0,
];
```
if your data are in the root of the response use `_` character as field name (even though this means your endpoint 
doesn't provide paginated data.

# 2. Issues, Questions and Pull Requests
You can report issues and ask questions in the 
[issues section](https://github.com/robertogallea/Laravel-Lazy-Rest/issues).
Please start your issue with ISSUE: and your question 
with QUESTION:

If you have a question, check the closed issues first. Over time, I've been able to answer quite a few.

To submit a Pull Request, please fork this repository, create a new branch and commit your new/updated code in there. 
Then open a Pull Request from your new branch. Refer to 
[this guide](https://help.github.com/articles/about-pull-requests/) for more info.