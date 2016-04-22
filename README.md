Router
=====

Router is a simple, open source PHP router base on macaw. It's super small (~150 LOC), fast, and has some great annotated source code. This class allows you to just throw it into your project and start using it immediately.

### Install

If you have Composer, just include Router as a project dependency in your `composer.json`. If you don't just install it by downloading the .ZIP file and extracting it to your project directory.

```
require: {
    "mombol/router": "^1.0"
}
```

### Examples

First, `use` the Router namespace:

```PHP
use \Mombol\Router\Router;
```

Router is not an object, so you can just make direct operations to the class. Here's the Hello World:

```PHP
Router::get('/', function() {
  return 'Hello world!';
});
```

Router also supports lambda URIs, such as:

```PHP
Router::get('/(:any)', function($slug) {
  return 'The slug is: ' . $slug;
});
```

You can also make requests for HTTP methods in Router, so you could also do:

```PHP
Router::get('/', function() {
  return 'I <3 GET commands!';
});

Router::post('/', function() {
  return  'I <3 POST commands!';
});
```

Lastly, if there is no route defined for a certain location, you can make Router run a custom callback, like:

```PHP
Router::error(function() {
  return '404 :: Not Found';
});
```

If you don't specify an error callback, Router will just echo `404`.

After call Router function do this:

```PHP
Router::dispatch(function($content){
  if (!empty($content) && is_string($content)) {
    echo $content;
  }
});
```

<hr>

### Orther
See https://github.com/noahbuscher/Macaw
