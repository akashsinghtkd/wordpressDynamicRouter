So if you want an accessible url in wordpress with out creating a page or post in the database then you could use a Dynamic Router plugin or php class.

DynamicRouter.lib.php
We have developed a PHP class lib which makes creating dynamic page urls in wordpress easy.

Here is the DynamicRouter.lib.php class file with no dependancies...

https://gist.github.com/joshmoto/DynamicRouter.lib.php

How to use DynamicRouter.lib.php
First add this lib file to your theme, in a folder called lib for example.

Then in your functions.php require this class lib once...

// class libraries
require_once(__DIR__ . '/lib/DynamicRouter.lib.php');
Then after this required lib in your functions (not before) you can begin creating dynamic urls without creating physical pages/posts in the wordpress cms.


Now create a function (example below) to create and configure your dynamic routes.

// register custom url routes
function register_routes () {

    // make sure our DynamicRouter class exists
    if(!class_exists('DynamicRouter')) return false;

    // create page url /something
    DynamicRouter::create(
        '^something$',
        'router.php',
        'Something | ',
        [
            'post_name' => 'something'
        ]
    );

    // create page url /some-other-thing
    DynamicRouter::create(
        '^some-other-thing$',
        'router.php',
        'Some Other Thing | ',
        [
            'post_name' => 'some-other-thing'
        ]
    );

    // create page url /parent-thing/child-thing
    DynamicRouter::create(
        '^parent-thing/child-thing$',
        'router.php',
        'Child Thing | ',
        [
            'post_name' => 'child-thing'
        ]
    );

    // handle our page routes
    DynamicRouter::handle();

}

Then also make sure you run the above function in your functions.php.

// register the routes
register_routes();

Then create a php called router.php in the root of your theme folder to handle routes by argument post_name, using this code below...

<?php
/**
 * router.php handler
 */

// current route array
$route = DynamicRouter::getCurrentRoute();

// handle custom routes 
switch ($route['arguments']['post_name']) {

    case 'something':
        
        // do your stuff here for url: example.com/something
        // get other custom php template or do functions
        var_dump('something');
        break;

    case 'some-other-thing':

        // do your stuff here for url: example.com/some-other-thing
        // get other custom php template or do functions
        var_dump('some-other-thing');
        break;

    case 'child-thing':

        // do your stuff here for url: example.com/parent-thing/child-thing
        // get other custom php template or do functions
        var_dump('parent-thing/child-thing');
        break;

    default:

        return false;

}

Very important, update permalinks
This is very important, none of the above code will work until you update the permalinks in your wordpress admin settings...

enter image description here

Simply go to the Permalinks section as shown above and hit the Save Changes button. You do not need to modify any of the current permalink settings, just simply hit the Save Changes button.

Now all of your custom url routes (as per example above) will work and not return a 404 page.

example.com/something
example.com/some-other-thing
example.com/parent-thing/child-thing
These urls will now do what ever you've configured in router.php (in your theme folder).


Final very important end notes
If you make any changes to your register_routes() php configuration code, your new changes will not work until you re-save your permalinks again.

If you deploy your local or staging environment to a live/production environment, then also make sure you re-save your permalinks in settings to apply latest configurations in your register_routes() function.
